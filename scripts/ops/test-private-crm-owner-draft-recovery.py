#!/usr/bin/env python3
"""CI-only owner-draft snapshot recovery; restored roles remain inert."""
from __future__ import annotations

import base64
import copy
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import uuid

import private_crm_owner_drafts_ci as ci
from private_crm_backup_contract import (
    BASE_ROLES, BASE_TENANT_TABLES, DATABASE, detect_profile,
    isolated_restore_cluster, manifest_digest, security_manifest,
)
from private_crm_backup_catalog import (
    ARRAY_CHECKS, CATALOG_RULE, catalog_comparison, catalog_manifest,
    security_catalog_comparison, security_constraints_match,
)
from private_crm_backup_snapshot import exported_snapshot

PHASE = "preflight"
CHECKS = 0
MAX_BYTES = 67108864
ADDON_TABLES = ("public.crm_private_owner_draft_grants", "public.crm_private_owner_draft_intents")
LEGACY_DENIAL = "Incomplete or unknown private backup relation profile."
UUID = re.compile(r"[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}")
HASH = re.compile(r"[a-f0-9]{64}")

def check(value, label):
    global CHECKS
    CHECKS += 1
    if not value:
        raise ci.Failure("OWNER_DRAFT_RECOVERY_" + label)


def wire(value):
    return json.dumps(value, ensure_ascii=False, sort_keys=True, separators=(",", ":")).encode()


def literal(value):
    check(isinstance(value, str) and "\x00" not in value, "SQL_VALUE_SHAPE")
    return "'" + value.replace("'", "''") + "'"


def restore_run(command, content=None, environment=None):
    # The original helper supplies its fresh password through the environment,
    # never argv. Keep all pg_dump/pg_restore/crypto bytes out of logs and files.
    result = subprocess.run(command, input=content, env=environment, capture_output=True, timeout=120)
    if result.returncode != 0:
        # isolated_restore_cluster retries RuntimeError while its new server
        # reaches readiness. Preserve that adapter contract without raw stderr.
        raise RuntimeError("Private owner draft restore command failed.")
    check(len(result.stdout) <= MAX_BYTES, "RESTORE_OUTPUT_LIMIT")
    return result.stdout


def legacy_must_deny(query, original_tables):
    # Passing the augmented list here would silently redefine the old contract.
    try:
        detect_profile(query, original_tables)
    except RuntimeError as error:
        check(str(error) == LEGACY_DENIAL, "LEGACY_DENIAL_REASON")
    else:
        check(False, "LEGACY_ACCEPTED_UNREVIEWED_ADDON")


def owner_profile(query, original_tables):
    check(query("SELECT current_database()||'|'||mode FROM public.crm_private_installation WHERE id=1")
          == DATABASE + "|synthetic-v1", "SYNTHETIC_DATABASE")
    check(query("SELECT current_setting('server_version_num')::int/10000") == "16", "POSTGRES_MAJOR")
    tables = tuple("public." + name for name in original_tables) + ADDON_TABLES
    manifest = security_manifest(query)
    check([(item["name"], item["owner"]) for item in manifest["schemas"]]
          == [("public", "pg_database_owner")], "CLOSED_SCHEMAS")
    check({(item["name"], item["kind"]) for item in manifest["relations"]}
          == {(name, "r") for name in tables}, "CLOSED_27_TABLES")
    check(all(item["owner"] == "crm_homologation_owner" for item in manifest["relations"]), "TABLE_OWNERS")
    check(not manifest["functions"] and manifest["default_acls"] == 0, "NO_FUNCTION_OR_DEFAULT_GRANT")
    rls_tables = set(BASE_TENANT_TABLES + ADDON_TABLES)
    check(all(item["rls"] and item["force_rls"] for item in manifest["relations"]
              if item["name"] in rls_tables), "FORCE_RLS")
    for table in ADDON_TABLES:
        policies = [item for item in manifest["policies"] if item["table"] == table]
        check(len(policies) == 1 and policies[0]["name"] == "tenant_scope"
              and policies[0]["command"] == "*" and policies[0]["permissive"]
              and policies[0]["roles"] == ["PUBLIC"]
              and policies[0]["using"] and policies[0]["check"], "ADDON_POLICY")
    for kind in ("schemas", "relations", "columns", "functions"):
        for item in manifest[kind]:
            for grantor, grantee, _, _ in item["acl"]:
                check(grantor in (*BASE_ROLES, "pg_database_owner")
                      and grantee in (*BASE_ROLES, "PUBLIC", "pg_database_owner"), "KNOWN_OBJECT_GRANTS")
    check(all(set(item["roles"]) <= set((*BASE_ROLES, "PUBLIC")) for item in manifest["policies"]),
          "KNOWN_POLICY_ROLES")
    check(query("SELECT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','owner_draft_lock_marker','UPDATE')"
                " AND NOT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','status','UPDATE')"
                " AND NOT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','team_role','UPDATE')"
                " AND NOT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','organization_id','UPDATE')"
                " AND has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','state','UPDATE')"
                " AND has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','confirmed_at','UPDATE')"
                " AND NOT has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','business','UPDATE')"
                " AND NOT has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','creator_id','UPDATE')"
                " AND NOT has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_grants','active','UPDATE')"
                " AND NOT has_table_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','DELETE')") == "t",
          "COLUMN_AUTHORITY_BOUNDARY")
    return tables, manifest


def sql_shape(value, identifiers):
    """Diagnostic only: no SQL rewrite, with literals hashed and never printed."""
    if not isinstance(value, str) or len(value.encode()) > 16384:
        return {"sql_shape": None, "literal_tokens_sha256": None}
    token = re.compile(r"(?P<string>(?:\bE|\bU&)?'(?:''|\\.|[^'\\])*')"
                       r'|(?P<quoted>"[A-Za-z_][A-Za-z0-9_]*")'
                       r"|(?P<number>(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?)"
                       r"|(?P<word>[A-Za-z_][A-Za-z0-9_]*)|(?P<space>\s+)"
                       r"|(?P<punct>[().,\[\]+*/%=<>!~?:&|\-])", re.I)
    keywords = set("check primary key foreign references unique nulls distinct match simple full partial "
                   "on update delete no action restrict cascade set default deferrable initially deferred immediate "
                   "not valid array any all is and or not between in true false null "
                   "character varying text integer bigint smallint boolean numeric jsonb "
                   "timestamp with without time zone date double precision real public pg_catalog jsonb_typeof".split())
    literals, pieces = [], []
    position = 0
    while position < len(value):
        match = token.match(value, position)
        if match is None:
            return {"sql_shape": None, "literal_tokens_sha256": None}
        kind, text = match.lastgroup, match.group()
        position = match.end()
        if kind in ("string", "number") or kind == "word" and text.lower() in ("true", "false", "null"):
            literals.append([kind, text])
            pieces.append("'LITERAL'" if kind == "string" else "LITERAL")
        elif kind == "word" and text.lower() not in keywords | identifiers:
            return {"sql_shape": None, "literal_tokens_sha256": None}
        elif kind == "quoted" and text[1:-1].lower() not in identifiers:
            return {"sql_shape": None, "literal_tokens_sha256": None}
        else:
            pieces.append(" " if kind == "space" else text)
    shape = re.sub(r"\s+", " ", "".join(pieces)).strip()
    without_literals = shape.replace("'LITERAL'", "")
    if len(shape) > 4096 or re.search(r"[\x00-\x1f\x7f-\xff'$]", without_literals) or any(
            marker in shape for marker in ("--", "/*", "*/")):
        shape = None
    return {"sql_shape": shape, "literal_tokens_sha256": manifest_digest(literals)}


def verify_catalog(expected, actual, tables):
    comparison = catalog_comparison(expected, actual)
    if comparison is None:
        check(len(tables) == 27 and len(set(tables)) == 27, "CATALOG_DIAGNOSTIC_INVENTORY")
        allowed_tables = set(tables)
        identifiers = {row["name"].lower() for row in expected["columns"] if row["table"] in allowed_tables}
        identifiers |= {name.split(".", 1)[1] for name in allowed_tables}
        differences = []
        for kind in ("constraints", "columns"):
            before = {(row["table"], row["name"]): row for row in expected[kind]}
            after = {(row["table"], row["name"]): row for row in actual[kind]}
            for key in sorted(set(before) | set(after)):
                old, new = before.get(key), after.get(key)
                if old == new:
                    continue
                # Log only fixed schema identities and a bounded shape whose
                # literal tokens have been removed. Equality is still required.
                check(key[0] in allowed_tables
                      and re.fullmatch(r"[a-z0-9_]{1,63}", key[1]), "CATALOG_DIAGNOSTIC_NAMES")
                fields = sorted(name for name in set(old or {}) | set(new or {})
                                if (old or {}).get(name) != (new or {}).get(name))
                details = {field: {"source": sql_shape((old or {}).get(field), identifiers),
                                   "restored": sql_shape((new or {}).get(field), identifiers)}
                           for field in fields if field in ("definition", "default")}
                differences.append({"kind": kind, "table": key[0], "name": key[1], "fields": fields,
                                    "source_sha256": manifest_digest(old), "restored_sha256": manifest_digest(new),
                                    "sql": details})
        report = {"check": "CRM_OWNER_DRAFT_RECOVERY_CATALOG_DIFFERENCE",
                  "object_differences": len(differences),
                  "order_only": not differences and expected.get("database_collation") == actual.get("database_collation"),
                  "database_collation_equal": expected.get("database_collation") == actual.get("database_collation"),
                  "differences": differences[:20]}
        if len(wire(report)) > 65536:
            report["differences"] = [{**item, "sql": {}} for item in differences[:20]]
            report["shapes_omitted_for_size"] = True
        print(json.dumps(report, sort_keys=True), flush=True)
    check(comparison is not None, "CLOSED_CATALOG_COMPARISON")
    return comparison


def security_mutation_probes(expected, actual, source_catalog, restored_catalog):
    """Exercise the new boundary using copies of the four actual PG manifests."""
    originals = expected, actual, source_catalog, restored_catalog
    before_hashes = tuple(manifest_digest(value) for value in originals)
    target = next(i for i, row in enumerate(restored_catalog["constraints"]) if row["type"] == "c")

    changed_security = copy.deepcopy(actual)
    changed_security["constraints"][target]["definition"] = "CHECK (true)"
    check(security_catalog_comparison(expected, changed_security, source_catalog, restored_catalog) is None,
          "SECURITY_MUTATION_UNBOUND_DEFINITION")

    changed_catalog = copy.deepcopy(restored_catalog)
    changed_catalog["constraints"][target]["definition"] = "CHECK (true)"
    check(security_constraints_match(changed_security, changed_catalog), "SECURITY_MUTATION_BOUND_FIXTURE")
    check(security_catalog_comparison(expected, changed_security, source_catalog, changed_catalog) is None,
          "SECURITY_MUTATION_BOUND_CONSTRAINT")

    changed_security = copy.deepcopy(actual)
    changed_security["default_acls"] += 1
    check(security_catalog_comparison(expected, changed_security, source_catalog, restored_catalog) is None,
          "SECURITY_MUTATION_OTHER_METADATA")
    check(tuple(manifest_digest(value) for value in originals) == before_hashes,
          "SECURITY_MUTATIONS_PRESERVED_REAL_MANIFESTS")
    return 3


def catalog_mutation_probes(expected, actual, comparison):
    """Mutate the two catalogs actually captured from PG, never a parser mirror."""
    before_hashes = manifest_digest(expected), manifest_digest(actual)
    target = next((i for i, row in enumerate(expected["constraints"])
                   if (row["table"], row["name"]) in ARRAY_CHECKS
                   and (not comparison["equivalences"] or row != actual["constraints"][i])), None)
    check(target is not None, "REAL_MUTATION_TARGET")
    identity = (expected["constraints"][target]["table"], expected["constraints"][target]["name"])
    column, _, values = ARRAY_CHECKS[identity]
    column_index = next(i for i, row in enumerate(expected["columns"]) if row["table"] == identity[0] and row["name"] == column)
    unrelated = next(i for i, row in enumerate(expected["constraints"]) if (row["table"], row["name"]) not in ARRAY_CHECKS)
    negatives = []

    def deny(label, change, *, both=False):
        left, right = copy.deepcopy(expected), copy.deepcopy(actual)
        change(right)
        if both:
            change(left)
        check(catalog_comparison(left, right) is None, "CATALOG_MUTATION_" + label)
        negatives.append(label)

    def definition(catalog, transform, index=target):
        catalog["constraints"][index]["definition"] = transform(catalog["constraints"][index]["definition"])

    def swap_literals(sql):
        return sql.replace("'" + values[0] + "'", "'ORDER_SWAP_FIXTURE'", 1) \
                  .replace("'" + values[1] + "'", "'" + values[0] + "'", 1) \
                  .replace("'ORDER_SWAP_FIXTURE'", "'" + values[1] + "'", 1)

    deny("LITERAL", lambda value: definition(value, lambda sql: sql.replace("'" + values[0] + "'", "'unreviewed_value'", 1)))
    deny("LITERAL_ORDER", lambda value: definition(value, swap_literals))
    deny("OPERATOR", lambda value: definition(value, lambda sql: sql.replace(" = ANY ", " <> ANY ", 1)))
    deny("UNOBSERVED_FORM", lambda value: definition(value, lambda sql: sql.replace("::character varying", "::varchar", 1)))
    deny("COLUMN_TYPE", lambda value: value["columns"][column_index].__setitem__("type", "character varying(99)"))
    deny("COLUMN_COLLATION", lambda value: value["columns"][column_index].__setitem__("collation", "pg_catalog.C"))
    deny("DATABASE_COLLATION", lambda value: value["database_collation"].__setitem__("collate", "UNREVIEWED_COLLATION"))
    deny("DATABASE_ICU_RULES", lambda value: value["database_collation"].__setitem__("icu_rules", "UNREVIEWED_ICU_RULES"))
    deny("FLAGS", lambda value: value["constraints"][target].__setitem__("validated", False))
    deny("UNKNOWN_OBJECT", lambda value: value["constraints"][target].__setitem__("name", "unreviewed_constraint"))
    deny("UNRELATED_FIELD", lambda value: value["columns"][0].__setitem__("default", "unreviewed_default"))
    deny("UNRELATED_CONSTRAINT", lambda value: definition(value, lambda sql: sql + " ", unrelated))
    deny("MISSING_CONSTRAINT", lambda value: value["constraints"].pop())
    deny("EXTRA_CONSTRAINT", lambda value: value["constraints"].append(copy.deepcopy(value["constraints"][target])))
    deny("CONSTRAINT_ORDER", lambda value: value["constraints"].reverse())
    if comparison["equivalences"]:
        # Equal mutations on both sides must not turn a changed cast pair into
        # a new permitted enum/type/collation merely because their values match.
        deny("SHARED_LITERAL", lambda value: definition(value, lambda sql: sql.replace("'" + values[0] + "'", "'unreviewed_value'", 1)), both=True)
        deny("SHARED_TYPE", lambda value: value["columns"][column_index].__setitem__("type", "character varying(99)"), both=True)
        deny("SHARED_COLLATION", lambda value: value["columns"][column_index].__setitem__("collation", "pg_catalog.C"), both=True)
    check((manifest_digest(expected), manifest_digest(actual)) == before_hashes, "MUTATIONS_PRESERVED_REAL_CATALOGS")
    return len(negatives)


def row_hashes(query, tables):
    check(len(tables) == 27 and len(set(tables)) == 27
          and all(re.fullmatch(r"public\.crm_[a-z0-9_]+", name) for name in tables), "HASH_TABLE_INVENTORY")
    parts = ["SELECT '" + name + "' AS name,jsonb_build_object('rows',count(*),'sha256',"
             "encode(sha256(convert_to(COALESCE(jsonb_agg(to_jsonb(t) ORDER BY to_jsonb(t)::text COLLATE \"C\"),"
             "'[]'::jsonb)::text,'UTF8')),'hex')) AS value FROM " + name + " t" for name in tables]
    value = json.loads(query("SELECT jsonb_object_agg(name,value ORDER BY name) FROM (" + " UNION ALL ".join(parts) + ") inventory"))
    check(set(value) == set(tables) and all(set(item) == {"rows", "sha256"}
          and type(item["rows"]) is int and item["rows"] >= 0 and HASH.fullmatch(item["sha256"])
          for item in value.values()), "HASH_RESULT_SHAPE")
    return value


def tenant_counts(query, tenant, *, transaction):
    check(not tenant or UUID.fullmatch(tenant), "SCOPE_UUID")
    statement = ("SET LOCAL ROLE crm_homologation_app; SET LOCAL app.tenant_id=" + literal(tenant) + ";"
                 " SELECT 'OWNER_DRAFT_SCOPE|'||jsonb_build_object("
                 "'grants',(SELECT count(*) FROM public.crm_private_owner_draft_grants),"
                 "'intents',(SELECT count(*) FROM public.crm_private_owner_draft_intents))::text;")
    if transaction:
        statement = "BEGIN;" + statement + "ROLLBACK;"
    lines = [line for line in query(statement).splitlines() if line.startswith("OWNER_DRAFT_SCOPE|")]
    check(len(lines) == 1, "SCOPE_RESULT")
    return json.loads(lines[0].split("|", 1)[1])


def denied_sql(command, role, company, statement):
    check(role in ("crm_homologation_app", "crm_homologation_iam") and UUID.fullmatch(company), "DENIAL_CONTEXT")
    sql = "BEGIN; SET LOCAL ROLE " + role + "; SET LOCAL app.tenant_id=" + literal(company) + ";" + statement + ";ROLLBACK;"
    result = subprocess.run(command + ["psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-v", "VERBOSITY=sqlstate",
                            "-U", "crm_homologation_restore", "-d", DATABASE], input=sql.encode(),
                            capture_output=True, timeout=15)
    states = re.findall(rb"(?:ERROR|FATAL):\s+([0-9A-Z]{5})\b", result.stderr)
    check(result.returncode != 0 and states == [b"42501"], "EXPECTED_PRIVILEGE_OR_RLS_DENIAL")


def immutable_probes(command, pending, foreign):
    company = pending["organization_id"]
    reference = literal(pending["draft_ref"])
    for column in ("business", "business_hash", "creator_id", "organization_id", "source_run_id"):
        denied_sql(command, "crm_homologation_app", company,
                   "UPDATE public.crm_private_owner_draft_intents SET " + column + "=" + column + " WHERE draft_ref=" + reference)
    denied_sql(command, "crm_homologation_app", company,
               "DELETE FROM public.crm_private_owner_draft_intents WHERE draft_ref=" + reference)
    denied_sql(command, "crm_homologation_app", company,
               "UPDATE public.crm_private_owner_draft_grants SET active=false")
    for column in ("team_role", "status", "organization_id"):
        denied_sql(command, "crm_homologation_iam", company,
                   "UPDATE public.crm_private_accounts SET " + column + "=" + column
                   + " WHERE identity_id=" + literal(pending["creator_id"]))
    for table in ("crm_auth_sessions", "crm_auth_credentials"):
        denied_sql(command, "crm_homologation_app", company, "SELECT 1 FROM public." + table + " LIMIT 1")
    # Valid row shape under the wrong tenant: the failure must be RLS (42501),
    # not a malformed JSON/check/FK or duplicate constraint accidentally denying.
    changed = copy.deepcopy(pending["business"])
    changed["draft_ref"] = str(uuid.uuid4())
    denied_sql(command, "crm_homologation_app", foreign,
               "INSERT INTO public.crm_private_owner_draft_intents(draft_ref,organization_id,source_run_id,creator_id,"
               "business,business_hash,state,created_at) VALUES ("
               + ",".join(literal(changed[key]) for key in ("draft_ref", "company_id", "source_run_id", "creator_id"))
               + "," + literal(wire(changed).decode()) + "::jsonb," + literal(hashlib.sha256(wire(changed)).hexdigest())
               + ",'pending'," + str(pending["created_at"]) + ")")
    return 13


def import_probe(name, filename):
    specification = importlib.util.spec_from_file_location(name, ci.ROOT / "scripts/ops" / filename)
    module = importlib.util.module_from_spec(specification)
    specification.loader.exec_module(module)
    return module


def main():
    global PHASE
    head, _ = ci.preflight()
    check(len(sys.argv) == 2, "VAULT_ARGUMENT")
    vault = Path(sys.argv[1])
    ci.protected(vault)
    check(vault.stat().st_size <= 65536, "VAULT_LIMIT")
    helper = import_probe("owner_recovery_original_http", "test-private-crm-http.py")
    owner = import_probe("owner_recovery_http", "test-private-crm-owner-drafts.py")
    owner.HELPER = helper
    account = helper.FIXTURES["clientA"]
    company, actor = account["organization"], account["identity"]
    foreign = helper.FIXTURES["clientB"]["organization"]
    check(all(UUID.fullmatch(value) for value in (company, actor, foreign)) and company != foreign, "FIXTURE_IDENTITIES")
    check(len(account["recovery"]) == 5 and re.fullmatch(r"[a-f0-9]{24}", account["recovery"][1]), "UNUSED_ORIGINAL_MFA_CODE")
    original_tables = re.findall(r"CREATE TABLE\s+(crm_[a-z0-9_]+)\s*\(",
                                (ci.ROOT / "infra/homologation/schema.sql").read_text())
    check(len(original_tables) == 25 and len(set(original_tables)) == 25, "ORIGINAL_BASE_INVENTORY")

    PHASE = "real-owner-session-and-lost-ack"
    client = helper.Client()
    client.login(account, recovery=account["recovery"][1])
    source = owner.plan(client, account, "RECOVERY")
    native_before = owner.native_snapshot()
    ci.gateway_fault("delay")
    try:
        reference = owner.create(client, source["id"])
    finally:
        ci.gateway_fault(None)
    pending = owner.intent(reference)
    remote = owner.remote_draft(reference)
    check(pending["state"] == "pending" and pending["confirmed_at"] is None
          and pending["creator_id"] == actor and pending["source_run_id"] == source["id"]
          and remote["state"] == "DRAFT" and remote["authorship_source"] == "CRM_AUTHENTICATED_OWNER"
          and remote["creator_id"] == actor and remote["business"] == pending["business"], "REAL_PENDING_AFTER_REMOTE_COMMIT")
    check(owner.native_snapshot() == native_before, "LOST_ACK_PRESERVED_NATIVE_STATE")
    confirmed_raw = ci.sql("SELECT to_jsonb(i)::text FROM public.crm_private_owner_draft_intents i WHERE organization_id="
                           + literal(company) + " AND state='confirmed' ORDER BY draft_ref LIMIT 1")
    check(bool(confirmed_raw), "PRIOR_CONFIRMED_FIXTURE")
    confirmed = json.loads(confirmed_raw)
    folder, provenance = ci.context()
    envfile = Path(os.environ["CRM_PRIVATE_ENV_FILE"])
    ci.protected(envfile)
    source_command = ["docker", "compose", "--env-file", str(envfile), "-p", ci.PROJECT,
                      "-f", str(ci.ROOT / "infra/homologation/compose.yaml"), "-f", str(folder / "compose.json"),
                      "exec", "-T", "postgres"]

    PHASE = "one-exported-snapshot-during-real-reconciliation"
    with exported_snapshot(source_command) as capture:
        def source_query(statement):
            return capture.query("SET LOCAL timezone='UTC'; SET LOCAL search_path=pg_catalog,public;" + statement)

        legacy_must_deny(source_query, original_tables)
        tables, security = owner_profile(source_query, original_tables)
        catalog = catalog_manifest(source_query)
        hashes = row_hashes(source_query, tables)
        scopes = {tenant: tenant_counts(source_query, tenant, transaction=False) for tenant in ("", company, foreign)}
        check(scopes[""] == {"grants": 0, "intents": 0} and scopes[company]["grants"] == 2
              and scopes[company]["intents"] >= 2 and scopes[foreign] == {"grants": 2, "intents": 0}, "SOURCE_TENANT_BOUNDARIES")
        state_query = "SELECT state FROM public.crm_private_owner_draft_intents WHERE draft_ref=" + literal(reference)
        check(source_query(state_query) == "pending", "SNAPSHOT_STARTED_PENDING")
        owner.read(client, reference)
        check(owner.intent(reference)["state"] == "confirmed", "LIVE_GET_RECONCILED")
        check(owner.remote_draft(reference) == remote and owner.native_snapshot() == native_before, "READ_PRESERVED_DRAFT_AND_NATIVE_STATE")
        check(source_query(state_query) == "pending" and row_hashes(source_query, tables) == hashes,
              "SNAPSHOT_DID_NOT_FOLLOW_LIVE_COMMIT")
        check(security_manifest(source_query) == security and catalog_manifest(source_query) == catalog,
              "SNAPSHOT_SCHEMA_STABLE")
        dump = capture.dump()
        check(dump.startswith(b"PGDMP") and len(dump) <= MAX_BYTES, "CUSTOM_DUMP")
        exported_at = capture.exported_at
    check(capture.closed, "EXPORTER_CLOSED_BEFORE_CRYPTO")

    PHASE = "original-crypto-and-authenticated-tamper-rejection"
    encrypted = ci.compose("run", "--rm", "-T", "tools", "scripts/ops/private-backup-crypt.php", "encrypt", data=dump).stdout
    check(0 < len(encrypted) <= MAX_BYTES, "ENCRYPTED_ARCHIVE_LIMIT")
    recovered = ci.compose("run", "--rm", "-T", "tools", "scripts/ops/private-backup-crypt.php", "decrypt", data=encrypted).stdout
    check(recovered == dump, "CRYPTO_EXACT_ROUNDTRIP")
    envelope = json.loads(base64.b64decode(encrypted, validate=True))
    check(isinstance(envelope, dict) and isinstance(envelope.get("value"), str)
          and isinstance(envelope.get("mac"), str) and HASH.fullmatch(envelope["mac"]), "ENCRYPTED_ENVELOPE")
    ciphertext = bytearray(base64.b64decode(envelope["value"], validate=True))
    check(bool(ciphertext), "NONEMPTY_CIPHERTEXT")
    ciphertext[0] ^= 1
    envelope["value"] = base64.b64encode(ciphertext).decode()
    tampered = base64.b64encode(wire(envelope))
    rejected = ci.compose("run", "--rm", "-T", "tools", "scripts/ops/private-backup-crypt.php", "decrypt", data=tampered, check=False)
    check(rejected.returncode != 0 and rejected.stdout == b"", "TAMPER_DENIED_WITHOUT_PLAINTEXT")

    PHASE = "separate-inert-restore-and-catalog-fidelity"
    with isolated_restore_cluster({"roles": BASE_ROLES}, restore_run) as (restored_sql, restore_command):
        container = restore_command[-1]
        check(re.fullmatch(r"imagine-crm-restore-check-[a-f0-9]{10}", container), "FRESH_RESTORE_IDENTITY")
        # Inspect only HostConfig, never Config.Env (which holds the fresh PG password).
        configuration = json.loads(restore_run(["docker", "inspect", "--format", "{{json .HostConfig}}", container]))
        check(configuration["NetworkMode"] == "none" and not configuration.get("PortBindings")
              and not configuration.get("Privileged") and "/var/lib/postgresql/data" in configuration.get("Tmpfs", {}),
              "INERT_NETWORK_AND_STORAGE")
        restore_run(restore_command + ["pg_restore", "--exit-on-error", "--single-transaction",
                    "-U", "crm_homologation_restore", "-d", DATABASE], content=recovered)

        def restored_query(statement):
            return restored_sql("SET timezone='UTC'; SET search_path=pg_catalog,public;" + statement)

        restored_tables, restored_security = owner_profile(restored_query, original_tables)
        restored_catalog = catalog_manifest(restored_query)
        check(security_constraints_match(security, catalog)
              and security_constraints_match(restored_security, restored_catalog), "SECURITY_CATALOG_BINDINGS_EXACT")
        catalog_comparison_result = verify_catalog(catalog, restored_catalog, tables)
        check(restored_tables == tables
              and security_catalog_comparison(security, restored_security, catalog, restored_catalog)
                  == catalog_comparison_result, "OWNERS_ACLS_RLS_COLUMN_GRANTS_EXACT")
        catalog_negatives = catalog_mutation_probes(catalog, restored_catalog, catalog_comparison_result)
        security_negatives = security_mutation_probes(security, restored_security, catalog, restored_catalog)
        check(row_hashes(restored_query, tables) == hashes, "ALL_27_TABLES_EXACT")
        restored_pending = json.loads(restored_query("SELECT to_jsonb(i)::text FROM public.crm_private_owner_draft_intents i WHERE draft_ref=" + literal(reference)))
        restored_confirmed = json.loads(restored_query("SELECT to_jsonb(i)::text FROM public.crm_private_owner_draft_intents i WHERE draft_ref=" + literal(confirmed["draft_ref"])))
        check(restored_pending == pending and restored_pending["state"] == "pending"
              and restored_confirmed == confirmed and restored_confirmed["state"] == "confirmed", "HISTORICAL_STATES_AND_AUTHORSHIP_EXACT")
        names = ",".join(literal(name) for name in BASE_ROLES)
        check(restored_query("SELECT count(*) FROM pg_roles WHERE rolname IN (" + names + ")") == str(len(BASE_ROLES)), "ALL_INERT_ROLES")
        check(restored_query("SELECT count(*) FROM pg_roles WHERE rolname IN (" + names + ") AND "
                            "(rolcanlogin OR rolsuper OR rolbypassrls OR rolcreatedb OR rolcreaterole OR rolreplication OR rolinherit)") == "0",
              "NO_LOGIN_OR_ELEVATED_RESTORE_ROLES")
        check(restored_query("SELECT count(*) FROM pg_authid WHERE rolname IN (" + names + ") AND rolpassword IS NOT NULL") == "0",
              "NO_RESTORED_ROLE_PASSWORDS")
        check(restored_query("SELECT count(*) FROM pg_auth_members WHERE member IN (SELECT oid FROM pg_roles WHERE rolname IN ("
                            + names + ")) OR roleid IN (SELECT oid FROM pg_roles WHERE rolname IN (" + names + "))") == "0", "NO_RESTORED_MEMBERSHIPS")
        check({tenant: tenant_counts(restored_query, tenant, transaction=True) for tenant in scopes} == scopes,
              "RESTORED_TENANT_BOUNDARIES")
        negatives = immutable_probes(restore_command, pending, foreign)
        check(row_hashes(restored_query, tables) == hashes, "DENIALS_LEFT_ALL_TABLES_UNCHANGED")
        legacy_must_deny(restored_query, original_tables)
    removed = subprocess.run(["docker", "inspect", container], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=15)
    check(removed.returncode != 0, "INERT_CONTAINER_REMOVED")
    print(json.dumps({
        "check": "CRM_OWNER_DRAFT_INERT_RECOVERY_PASS_NO_GO", "head": head,
        "agents_sha": provenance["agents_sha"], "central_sha": provenance["central_sha"],
        "checks": CHECKS + owner.CHECKS + helper.CHECKS, "owner_http_requests": owner.REQUESTS,
        "tables": len(tables), "restored_roles": len(BASE_ROLES), "sql_denials": negatives,
        "data_manifest_sha256": manifest_digest(hashes), "security_manifest_sha256": manifest_digest(security),
        "restored_security_manifest_sha256": manifest_digest(restored_security),
        "security_raw_equal": security == restored_security, "security_catalog_bindings_exact": True,
        "security_mutation_denials": security_negatives,
        "source_catalog_sha256": manifest_digest(catalog), "restored_catalog_sha256": manifest_digest(restored_catalog),
        "catalog_raw_equal": catalog == restored_catalog, "catalog_roundtrip_rule": catalog_comparison_result["rule"],
        "catalog_roundtrip_equivalences": catalog_comparison_result["equivalences"],
        "catalog_mutation_denials": catalog_negatives, "snapshot_exported_at": exported_at,
        "legacy_backup_rejects_addon": True, "snapshot_pending_live_confirmed_restored_pending": True,
        "crypto_roundtrip": True, "authenticated_tamper_denied": True,
        "current_authority_restored": False, "restored_session_reused": False,
        "restored_runtime_activated": False, "distributed_restore_verified": False,
        "plaintext_dump_on_disk": False, "dump_or_key_artifacts": False, "operational": False,
    }, sort_keys=True))


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        reason = str(error) if isinstance(error, ci.Failure) else type(error).__name__.upper()
        if not re.fullmatch(r"[A-Z0-9_]{1,180}", reason):
            reason = "OWNER_DRAFT_RECOVERY_UNCLASSIFIED_FAILURE"
        print(json.dumps({"check": "CRM_OWNER_DRAFT_INERT_RECOVERY_DENIED", "phase": PHASE, "reason": reason}), file=sys.stderr)
        sys.exit(2)
