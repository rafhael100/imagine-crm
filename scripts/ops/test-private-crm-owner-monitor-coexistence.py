#!/usr/bin/env python3
"""Actual owner authority plus the optional monitor; closed, inert 35-table CI recovery."""
from __future__ import annotations

import base64
import hashlib
import http.client
import importlib.util
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import time

import private_crm_owner_drafts_ci as ci
from private_crm_backup_catalog import (
    catalog_manifest, catalog_comparison, security_catalog_comparison,
    security_constraints_match,
)
from private_crm_backup_contract import (
    DATABASE, detect_profile, isolated_restore_cluster, manifest_digest, security_manifest,
)
from private_crm_backup_snapshot import exported_snapshot

PHASE = "preflight"
CHECKS = 0
MAX_BYTES = 67108864
UUID = re.compile(r"[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}")
HASH = re.compile(r"[a-f0-9]{64}")
MONITOR = "synthetic-private-v1"
SCHEMAS = ("public", "crm_auto_service")
BASE_TABLES = (
    "crm_private_installation", "crm_global_identities", "crm_organizations", "crm_memberships",
    "crm_auth_credentials", "crm_auth_sessions", "crm_auth_recovery_codes", "crm_auth_operations",
    "crm_auth_mandates", "crm_iam_audit_events", "crm_private_accounts", "crm_private_trials",
    "crm_private_trial_claims", "crm_private_key_versions", "crm_private_signup_requests",
    "crm_private_recovery_attempts", "crm_private_funnels", "crm_private_invitations",
    "crm_private_leads", "crm_private_tasks", "crm_private_events", "crm_private_orders",
    "crm_private_preferences", "crm_private_automation_runs", "crm_private_operator_events",
)
OWNER_TABLES = ("public.crm_private_owner_draft_grants", "public.crm_private_owner_draft_intents")
SERVICE_TABLES = (
    "crm_auto_service.grants", "crm_auto_service.nonces", "crm_auto_service.audit",
    "crm_auto_service.outbound_grants", "crm_auto_service.outbox",
    "crm_auto_service.execution_read_grants", "crm_auto_service.execution_observations",
    "crm_auto_service.execution_projections",
)
TABLES = tuple("public." + name for name in BASE_TABLES) + OWNER_TABLES + SERVICE_TABLES
BUSINESS_TABLES = tuple(name for name in TABLES if name.startswith(("public.crm_private_", "crm_auto_service.")))
SEQUENCE = "crm_auto_service.audit_id_seq"
ROLES = (
    "crm_homologation_owner", "crm_homologation_app", "crm_homologation_iam",
    "crm_homologation_enrollment", "crm_homologation_operator", "crm_auto_reader_owner",
    "crm_auto_transport", "crm_auto_sender", "crm_auto_execution_reconciler",
)
FUNCTIONS = {
    "crm_auto_service.read_state(uuid,text,text,text,uuid,text,text,bigint)",
    "crm_auto_service.stage_outbound(uuid,uuid)", "crm_auto_service.ack_outbound(uuid,uuid,text)",
    "crm_auto_service.receipt_candidate(uuid,uuid)", "crm_auto_service.execution_candidate(uuid,uuid)",
    "crm_auto_service.project_execution(uuid,uuid,jsonb,text)",
    "crm_auto_service.read_execution_projection(uuid,uuid)",
    "crm_auto_service.reject_execution_observation_mutation()", "crm_auto_service.read_execution_monitor(uuid)",
}
RLS_TABLES = tuple("public." + name for name in (
    "crm_memberships", "crm_private_trials", "crm_private_leads", "crm_private_tasks",
    "crm_private_automation_runs", "crm_private_events", "crm_private_orders",
    "crm_private_preferences", "crm_private_funnels", "crm_private_invitations",
)) + OWNER_TABLES + SERVICE_TABLES
PROOF_FILE = "owner-monitor-coexistence.json"


def check(value, label):
    global CHECKS
    CHECKS += 1
    if not value:
        raise ci.Failure("OWNER_MONITOR_" + label)


def wire(value):
    return json.dumps(value, ensure_ascii=False, sort_keys=True, separators=(",", ":")).encode()


def literal(value):
    check(isinstance(value, str) and "\x00" not in value, "SQL_VALUE")
    return "'" + value.replace("'", "''") + "'"


def module(name, filename):
    spec = importlib.util.spec_from_file_location(name, ci.ROOT / "scripts/ops" / filename)
    result = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(result)
    return result


def bounded_json(path, maximum=262144):
    ci.protected(path)
    check(path.stat().st_size <= maximum, "VAULT_BOUND")
    return json.loads(path.read_text())


def row_hashes(query, tables=TABLES):
    check(tables in (TABLES, BUSINESS_TABLES) and len(TABLES) == len(set(TABLES)) == 35, "FIXED_HASH_INVENTORY")
    parts = ["SELECT '" + name + "' AS name,jsonb_build_object('rows',count(*),'sha256',"
             "encode(sha256(convert_to(COALESCE(jsonb_agg(to_jsonb(t) ORDER BY to_jsonb(t)::text COLLATE \"C\"),"
             "'[]'::jsonb)::text,'UTF8')),'hex')) AS value FROM " + name + " t" for name in tables]
    value = json.loads(query("SELECT jsonb_object_agg(name,value ORDER BY name) FROM (" + " UNION ALL ".join(parts) + ") inventory"))
    check(set(value) == set(tables) and all(set(item) == {"rows", "sha256"}
          and type(item["rows"]) is int and item["rows"] >= 0 and HASH.fullmatch(item["sha256"])
          for item in value.values()), "HASH_SHAPE")
    return value


def sequence_state(query):
    return json.loads(query("SELECT jsonb_build_object('last_value',s.last_value,'is_called',s.is_called,"
        "'type',format_type(p.seqtypid,NULL),'start',p.seqstart,'increment',p.seqincrement,"
        "'max',p.seqmax,'min',p.seqmin,'cache',p.seqcache,'cycle',p.seqcycle)"
        " FROM crm_auto_service.audit_id_seq s CROSS JOIN pg_sequence p WHERE p.seqrelid='crm_auto_service.audit_id_seq'::regclass"))


def combined_profile(query):
    check(query("SELECT current_database()||'|'||mode FROM public.crm_private_installation WHERE id=1")
          == DATABASE + "|synthetic-v1", "PRIVATE_DATABASE")
    check(query("SELECT current_setting('server_version_num')::int/10000") == "16", "PG16")
    value = security_manifest(query)
    check([(x["name"], x["owner"]) for x in value["schemas"]]
          == [("crm_auto_service", "crm_auto_reader_owner"), ("public", "pg_database_owner")], "CLOSED_SCHEMAS")
    check({(x["name"], x["kind"]) for x in value["relations"]}
          == {(name, "r") for name in TABLES} | {(SEQUENCE, "S")}, "CLOSED_35_TABLES_ONE_SEQUENCE")
    check({x["signature"] for x in value["functions"]} == FUNCTIONS and len(value["functions"]) == 9,
          "CLOSED_NINE_FUNCTIONS")
    check(value["default_acls"] == 0, "NO_DEFAULT_GRANTS")
    for item in value["relations"]:
        check(item["owner"] == ("crm_auto_reader_owner" if item["name"].startswith("crm_auto_service.")
                                else "crm_homologation_owner"), "RELATION_OWNER")
        if item["name"] in RLS_TABLES:
            check(item["rls"] is True and item["force_rls"] is True, "FORCE_RLS")
    for table in RLS_TABLES:
        policies = [x for x in value["policies"] if x["table"] == table]
        check(len(policies) == 1 and policies[0]["name"] == "tenant_scope"
              and policies[0]["command"] == "*" and policies[0]["permissive"] is True
              and policies[0]["roles"] == ["PUBLIC"] and policies[0]["using"] and policies[0]["check"], "TENANT_POLICY")
    check({x["table"] for x in value["policies"]} == set(RLS_TABLES), "CLOSED_POLICIES")
    for item in value["functions"]:
        check(item["owner"] == "crm_auto_reader_owner" and item["security_definer"] is True
              and item["config"] in (["search_path=pg_catalog, public"], ["search_path=pg_catalog,public"]),
              "RESTRICTED_DEFINER")
        if item["signature"] == "crm_auto_service.read_execution_monitor(uuid)":
            check(item["volatility"] == "s" and item["strict"] is False and item["result"] == "jsonb"
                  and item["arguments"] == "selected_event uuid DEFAULT NULL::uuid"
                  and {tuple(x) for x in item["acl"]} == {
                      ("crm_auto_reader_owner", "crm_auto_reader_owner", "EXECUTE", False),
                      ("crm_auto_reader_owner", "crm_homologation_app", "EXECUTE", False)}, "APP_ONLY_MONITOR_EXECUTE")
        else:
            check(all(x[1] not in ("PUBLIC", "crm_homologation_app") for x in item["acl"]), "NO_OTHER_APP_FUNCTION")
    service = next(x for x in value["schemas"] if x["name"] == "crm_auto_service")
    check([x for x in service["acl"] if x[1] == "crm_homologation_app"]
          == [["crm_auto_reader_owner", "crm_homologation_app", "USAGE", False]], "APP_SERVICE_USAGE_ONLY")
    for kind, key in (("relations", "name"), ("columns", "table")):
        for item in value[kind]:
            if item[key].startswith("crm_auto_service."):
                check(all(x[1] not in ("PUBLIC", "crm_homologation_app") for x in item["acl"]), "NO_DIRECT_APP_SERVICE_ACCESS")
    for kind in ("schemas", "relations", "columns", "functions"):
        for item in value[kind]:
            check(all(x[0] in (*ROLES, "pg_database_owner") and x[1] in (*ROLES, "PUBLIC", "pg_database_owner")
                      for x in item["acl"]), "KNOWN_ACL_ROLES")
    check({(x["table"], x["name"], x["type"], x["enabled"], x["function"]) for x in value["triggers"]} == {
        ("crm_auto_service.execution_observations", "execution_history_immutable", 27, "O", "crm_auto_service.reject_execution_observation_mutation()"),
        ("crm_auto_service.execution_observations", "execution_history_no_truncate", 34, "O", "crm_auto_service.reject_execution_observation_mutation()")}, "IMMUTABLE_HISTORY_TRIGGERS")
    role_list = ",".join(literal(x) for x in ROLES)
    check(query("SELECT count(*) FROM pg_roles WHERE rolname IN (" + role_list + ")") == "9", "ALL_NINE_ROLES")
    check(query("SELECT count(*) FROM pg_roles WHERE rolname IN (" + role_list + ") AND rolname<>'crm_homologation_owner'"
                " AND (rolsuper OR rolbypassrls OR rolcreatedb OR rolcreaterole OR rolreplication)") == "0", "NO_ELEVATED_RUNTIME_ROLES")
    check(query("SELECT count(*) FROM pg_auth_members WHERE member IN (SELECT oid FROM pg_roles WHERE rolname IN ("
                + role_list + ")) OR roleid IN (SELECT oid FROM pg_roles WHERE rolname IN (" + role_list + "))") == "0", "NO_ROLE_MEMBERSHIPS")
    check(query("SELECT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','owner_draft_lock_marker','UPDATE')"
        " AND NOT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','status','UPDATE')"
        " AND NOT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','team_role','UPDATE')"
        " AND NOT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','organization_id','UPDATE')"
        " AND has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','state','UPDATE')"
        " AND has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','confirmed_at','UPDATE')"
        " AND NOT has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','business','UPDATE')"
        " AND NOT has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','creator_id','UPDATE')"
        " AND NOT has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_grants','active','UPDATE')"
        " AND NOT has_table_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','DELETE')"
        " AND NOT has_table_privilege('crm_auto_reader_owner','public.crm_private_owner_draft_intents','SELECT')"
        " AND NOT has_table_privilege('crm_auto_reader_owner','public.crm_private_owner_draft_grants','SELECT')") == "t", "OWNER_COLUMN_AND_ROLE_BOUNDARY")
    return value


def legacy_denials(query, recovery):
    try:
        detect_profile(query, list(BASE_TABLES))
    except RuntimeError as error:
        check(str(error) == "Incomplete or unknown private backup relation profile.", "LEGACY_REASON")
    else:
        check(False, "LEGACY_ACCEPTED_35")
    try:
        recovery.owner_profile(query, BASE_TABLES)
    except ci.Failure as error:
        check(str(error) == "OWNER_DRAFT_RECOVERY_CLOSED_SCHEMAS", "OWNER27_REASON")
    else:
        check(False, "OWNER27_ACCEPTED_35")


def monitor_model(query, company, event, *, transaction):
    check(UUID.fullmatch(company) and (event is None or UUID.fullmatch(event)), "MONITOR_SCOPE")
    sql = ("SET LOCAL ROLE crm_homologation_app; SET LOCAL app.tenant_id=" + literal(company)
           + "; SELECT 'COMBINED_MONITOR|'||crm_auto_service.read_execution_monitor("
           + (literal(event) + "::uuid" if event else "NULL") + ")::text;")
    if transaction:
        sql = "BEGIN; " + sql + " ROLLBACK;"
    lines = [x.split("|", 1)[1] for x in query(sql).splitlines() if x.startswith("COMBINED_MONITOR|")]
    check(len(lines) == 1, "MONITOR_RESULT")
    model = json.loads(lines[0])
    check(set(model) == {"schema", "company_id", "generated_at", "total_events", "events", "selected"}
          and model["schema"] == "CRM_N8N_EXECUTION_MONITOR_V1" and model["company_id"] == company
          and type(model["generated_at"]) is int and 0 <= model["generated_at"] <= 4102444800,
          "MONITOR_ENVELOPE")
    # This timestamp is generated at each read. Persisted events/history and all
    # other fields remain exact; no data or security difference is normalized.
    return {key: value for key, value in model.items() if key != "generated_at"}


def readonly_scope(query, company, *, transaction):
    check(not company or UUID.fullmatch(company), "SCOPE")
    body = ("SET LOCAL ROLE crm_auto_reader_owner; SET LOCAL app.tenant_id=" + literal(company)
            + "; SELECT 'COMBINED_SCOPE|'||jsonb_build_object("
            + ",".join(literal(name) + ",(SELECT count(*) FROM " + name + ")" for name in SERVICE_TABLES) + ")::text;")
    if transaction:
        body = "BEGIN; " + body + " ROLLBACK;"
    lines = [x.split("|", 1)[1] for x in query(body).splitlines() if x.startswith("COMBINED_SCOPE|")]
    check(len(lines) == 1, "SERVICE_SCOPE_RESULT")
    return json.loads(lines[0])


def restore_run(command, content=None, environment=None):
    result = subprocess.run(command, input=content, env=environment, capture_output=True, timeout=120)
    if result.returncode:
        raise RuntimeError("Private combined restore command failed.")
    check(len(result.stdout) <= MAX_BYTES, "COMMAND_OUTPUT_BOUND")
    return result.stdout


def app_configuration():
    identity = ci.compose("ps", "-q", "app").stdout.decode().strip()
    check(re.fullmatch(r"[a-f0-9]{64}", identity), "APP_IDENTITY")
    value = json.loads(ci.run(["docker", "inspect", identity], "OWNER_MONITOR_INSPECTION_FAILED").stdout)[0]
    folder, _ = ci.context()
    environment = dict(item.split("=", 1) for item in value["Config"]["Env"])
    check(environment.get("CRM_PRIVATE_OWNER_DRAFTS") == ci.PROFILE, "OWNER_OPT_IN")
    check(not any(name.startswith(("CRM_AUTO_S2S_", "CRM_AGENTS_", "AGENTS_PRIVATE_")) for name in environment)
          and not {"CRM_PRIVATE_OWNER_PASSWORD", "CRM_PRIVATE_BACKUP_KEY"} & set(environment), "NO_OPERATOR_OR_MONITOR_SERVICE_CREDENTIALS")
    binds = {(x["Source"], x["Destination"], x["RW"]) for x in value["Mounts"] if x["Type"] == "bind"}
    check(binds == {(str(folder / "crm-certs"), "/certs", False),
                    (str(folder / "crm-keys"), "/run/secrets", False)}, "ONLY_EXISTING_OWNER_MOUNTS")
    check(value["Config"]["Labels"].get("com.docker.compose.project") == ci.PROJECT
          and value["Config"]["Labels"].get("com.docker.compose.service") == "app"
          and value["HostConfig"]["ReadonlyRootfs"] and not value["HostConfig"]["Privileged"]
          and not value["HostConfig"].get("PortBindings"), "PRIVATE_APP_CONTAINMENT")
    return {"image": value["Image"], "environment": environment, "binds": binds,
            "networks": set(value["NetworkSettings"]["Networks"])}


def wait_ready():
    for _ in range(40):
        connection = http.client.HTTPConnection("127.0.0.1", 18086, timeout=2)
        try:
            connection.request("GET", "/up", headers={"Host": "crm-dev.imaginecomunica.com.br"})
            response = connection.getresponse(); response.read()
            if response.status == 200:
                return
        except (OSError, http.client.HTTPException):
            pass
        finally:
            connection.close()
        time.sleep(.5)
    check(False, "APP_NOT_READY")


def install(recovery):
    before = app_configuration()
    check(before["environment"].get("CRM_N8N_EXECUTION_MONITOR", "") == "", "MONITOR_WAS_OFF")
    recovery.owner_profile(ci.sql, BASE_TABLES)  # Existing 27-table contract must still pass first.
    for name in ("install-candidate", "outbound-candidate", "execution-feedback-candidate", "execution-monitor-candidate"):
        ci.sql((ci.ROOT / "infra/homologation/s2s" / (name + ".sql")).read_text())
    ci.compose("up", "-d", "--no-build", "--no-deps", "--force-recreate", "app")
    ci.compose("restart", "web")
    wait_ready()
    after = app_configuration()
    check(after["environment"].get("CRM_N8N_EXECUTION_MONITOR") == MONITOR, "MONITOR_OPT_IN")
    before["environment"]["CRM_N8N_EXECUTION_MONITOR"] = MONITOR
    check(after == before, "MONITOR_ACTIVATION_CHANGED_OWNER_RUNTIME")
    combined_profile(ci.sql)
    legacy_denials(ci.sql, recovery)
    print("CRM_OWNER_MONITOR_COMPOSITION_35_READY_NO_GO")


def sql_denial(command, role, company, statement, expected):
    check(role in ("crm_homologation_app", "crm_auto_reader_owner")
          and (not company or UUID.fullmatch(company)) and expected in ("42501", "P0001"), "DENIAL_CONTEXT")
    sql = ("BEGIN; SET LOCAL ROLE " + role + "; SET LOCAL app.tenant_id=" + literal(company)
           + ";" + statement + ";ROLLBACK;")
    result = subprocess.run(command + ["psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-v", "VERBOSITY=sqlstate",
        "-U", "crm_homologation_restore", "-d", DATABASE], input=sql.encode(), capture_output=True, timeout=15)
    states = re.findall(rb"(?:ERROR|FATAL):\s+([0-9A-Z]{5})\b", result.stderr)
    check(result.returncode != 0 and states == [expected.encode()], "EXACT_SQL_DENIAL")


def monitor_http(client, event, owner, *, language="pt", expected=200):
    path = "/painel?section=n8n&lang=" + language + ("&event=" + event if event else "")
    status, html, _ = owner.capture(client, path)
    check(status == expected, "MONITOR_HTTP_STATUS")
    if expected == 200:
        check('data-execution-monitor="' + language + '"' in html, "MONITOR_VISIBLE")
        if event:
            check('data-execution-selected="' + event + '"' in html, "SELECTED_EVENT_VISIBLE")
        check(not any(x in html for x in ("SQLSTATE", "crm_auto_service", "payload_hash", "receipt_hash",
                                         "AUTO_IA_EXECUTION_STATUS_V1")), "MONITOR_CLOSED_PRESENTATION")
    else:
        check('data-execution-monitor=' not in html, "DENIED_MONITOR_NOT_DISCLOSED")
    return html


def audit_count(query, proof):
    return int(query("SELECT count(*) FROM public.crm_private_events WHERE organization_id="
        + literal(proof["company"]) + " AND actor_id=" + literal(proof["actor"])
        + " AND object_id=" + literal(proof["confirmed_ref"]) + " AND action='agents.draft.read'"))


def event_fingerprints(query):
    # Only opaque IDs and full-row hashes leave this fixed table. This also
    # detects mutation/deletion of an earlier event beside the allowed append.
    value = json.loads(query("SELECT COALESCE(jsonb_object_agg(e.id::text,"
        "encode(sha256(convert_to(to_jsonb(e)::text,'UTF8')),'hex') ORDER BY e.id),'{}'::jsonb)"
        " FROM public.crm_private_events e"))
    check(isinstance(value, dict) and len(value) <= 2048
          and all(UUID.fullmatch(key) and isinstance(digest, str) and HASH.fullmatch(digest)
                  for key, digest in value.items()), "BOUNDED_EVENT_FINGERPRINTS")
    return value


def browser_snapshot(vault):
    proof = bounded_json(vault.parent / PROOF_FILE, 16384)
    check(proof.get("schema") == "CRM_OWNER_MONITOR_CI_V1" and proof.get("head") == ci.preflight()[0]
          and all(UUID.fullmatch(proof.get(name, "")) for name in
                  ("company", "actor", "confirmed_ref", "reconciled_ref", "main_event", "foreign_event")), "PROOF_BINDING")
    check(app_configuration()["environment"].get("CRM_N8N_EXECUTION_MONITOR") == MONITOR, "BOTH_FLAGS_ACTIVE")
    combined_profile(ci.sql)
    return {"business": row_hashes(ci.sql, BUSINESS_TABLES), "event_fingerprints": event_fingerprints(ci.sql),
            "owner_read_count": audit_count(ci.sql, proof)}


def execute(vault, recovery):
    global PHASE
    head, _ = ci.preflight()
    check(not (vault.parent / PROOF_FILE).exists()
          and not (vault.parent / "execution-monitor-fixtures.json").exists(), "FRESH_COMBINED_FIXTURES")
    check(app_configuration()["environment"].get("CRM_N8N_EXECUTION_MONITOR") == MONITOR, "BOTH_FLAGS_ACTIVE")
    combined_profile(ci.sql)
    legacy_denials(ci.sql, recovery)
    helper = module("combined_original_http", "test-private-crm-http.py")
    owner = module("combined_owner_http", "test-private-crm-owner-drafts.py")
    owner.HELPER = helper
    account = helper.FIXTURES["clientA"]
    company, actor = account["organization"], account["identity"]
    peer = helper.FIXTURES["clientB"]["organization"]
    check(all(UUID.fullmatch(x) for x in (company, actor, peer)) and company != peer
          and len(account["recovery"]) == 5 and re.fullmatch(r"[a-f0-9]{24}", account["recovery"][2]), "FIXTURE_AND_MFA_RESERVATION")
    check(ci.sql("SELECT count(*) FROM public.crm_private_leads WHERE organization_id=" + literal(company)) == "9",
          "ORIGINAL_OWNER_NINE_LEADS_BEFORE_MONITOR")
    preferences = json.loads(ci.sql("SELECT automation_rules::text FROM public.crm_private_preferences WHERE organization_id=" + literal(company)))
    check(type(preferences["version"]) is int and 1 <= preferences["version"] <= 20
          and preferences["delay_seconds"] == 3600 and "followup_qualified" in preferences["rules"], "EXISTING_NATIVE_PREFERENCES")

    PHASE = "real-current-owner-before-monitor-seed"
    client = helper.Client(); client.login(account, recovery=account["recovery"][2])
    confirmed_source = owner.plan(client, account, "COEXIST_CONFIRMED")
    confirmed_ref = owner.create(client, confirmed_source["id"])
    owner.read(client, confirmed_ref)
    confirmed = owner.intent(confirmed_ref)
    pending_source = owner.plan(client, account, "COEXIST_PENDING")
    native = owner.native_snapshot()
    ci.gateway_fault("delay")
    try:
        pending_ref = owner.create(client, pending_source["id"])
    finally:
        ci.gateway_fault(None)
    pending, remote = owner.intent(pending_ref), owner.remote_draft(pending_ref)
    check(confirmed["state"] == "confirmed" and pending["state"] == "pending" and pending["confirmed_at"] is None
          and pending["creator_id"] == actor and pending["source_run_id"] == pending_source["id"]
          and remote["state"] == "DRAFT" and remote["authorship_source"] == "CRM_AUTHENTICATED_OWNER"
          and remote["creator_id"] == actor and remote["business"] == pending["business"], "REAL_CONFIRMED_AND_LOST_ACK")
    check(owner.native_snapshot() == native and ci.sql("SELECT count(*) FROM public.crm_private_leads WHERE organization_id="
          + literal(company)) == "11", "OWNER_CREATION_BEFORE_QUOTA_FIXTURE")

    PHASE = "existing-synthetic-monitor-seed"
    ci.compose("run", "--rm", "-T", "-e", "CRM_PRIVATE_CI_PROJECT=" + ci.PROJECT,
               "-e", "CRM_PRIVATE_CI_MONITOR_PROFILE=execution-monitor-v1", "tools",
               "scripts/ops/seed-private-crm-execution-monitor.php", "seed")
    fixtures = bounded_json(vault.parent / "execution-monitor-fixtures.json")
    check(fixtures["monitor"]["synthetic_presentation_only"] is True
          and fixtures["monitor"]["n8n_executions_in_this_fixture"] == 0
          and fixtures["clientA"] == account and fixtures["clientB"] == helper.FIXTURES["clientB"], "UNCHANGED_IDENTITIES_AND_SYNTHETIC_PROVENANCE")
    main = fixtures["monitor"]["events"]["clientA"][55]
    foreign = fixtures["monitor"]["events"]["clientB"][0]
    check(all(UUID.fullmatch(x) for x in (main["event_id"], foreign["event_id"]))
          and main["history_count"] == 6 and main["state"] == "ACKED", "MONITOR_PERSISTED_FIXTURE")
    proof = {"schema": "CRM_OWNER_MONITOR_CI_V1", "head": head, "company": company, "actor": actor,
        "confirmed_ref": confirmed_ref, "reconciled_ref": pending_ref,
        "source_run": confirmed_source["id"], "source_lead": confirmed_source["lead_id"],
        "main_event": main["event_id"], "foreign_event": foreign["event_id"],
        "http_recovery_index": 2, "browser_recovery_index": 3}

    PHASE = "same-session-owner-and-immutable-monitor-window"
    before = row_hashes(ci.sql, BUSINESS_TABLES); reads_before = audit_count(ci.sql, proof)
    events_before = event_fingerprints(ci.sql)
    owner.read(client, confirmed_ref)
    after = row_hashes(ci.sql, BUSINESS_TABLES)
    events_after = event_fingerprints(ci.sql)
    check({k: v for k, v in before.items() if k != "public.crm_private_events"}
          == {k: v for k, v in after.items() if k != "public.crm_private_events"}
          and after["public.crm_private_events"]["rows"] == before["public.crm_private_events"]["rows"] + 1
          and audit_count(ci.sql, proof) == reads_before + 1
          and len(events_after) == len(events_before) + 1
          and all(events_after.get(key) == digest for key, digest in events_before.items()), "ONLY_EXPECTED_OWNER_READ_AUDIT")
    for language in ("pt", "en", "es"):
        page = monitor_http(client, None, owner, language=language)
        check(foreign["event_id"] not in page and foreign["lead_title"] not in page, "NO_FOREIGN_MONITOR_EVENT")
        monitor_http(client, main["event_id"], owner, language=language)
    monitor_http(client, foreign["event_id"], owner, expected=404)
    check(row_hashes(ci.sql, BUSINESS_TABLES) == after, "MONITOR_LEFT_OWNER_BUSINESS_AND_SERVICE_UNCHANGED")

    PHASE = "snapshot35-during-real-owner-reconciliation"
    folder, provenance = ci.context()
    command = ["docker", "compose", "--env-file", os.environ["CRM_PRIVATE_ENV_FILE"], "-p", ci.PROJECT,
        "-f", str(ci.ROOT / "infra/homologation/compose.yaml"), "-f", str(folder / "compose.json"), "exec", "-T", "postgres"]
    state_query = "SELECT state FROM public.crm_private_owner_draft_intents WHERE draft_ref=" + literal(pending_ref)
    native = owner.native_snapshot()
    with exported_snapshot(command) as snapshot:
        def source_query(statement):
            return snapshot.query("SET LOCAL timezone='UTC'; SET LOCAL search_path=pg_catalog,public;" + statement)

        security = combined_profile(source_query)
        catalog = catalog_manifest(source_query, schemas=SCHEMAS)
        check(security_constraints_match(security, catalog), "SOURCE_SECURITY_CATALOG_BINDING")
        hashes = row_hashes(source_query)
        sequence = sequence_state(source_query)
        scopes = {tenant: {"owner": recovery.tenant_counts(source_query, tenant, transaction=False),
                          "service": readonly_scope(source_query, tenant, transaction=False)}
                  for tenant in ("", company, peer)}
        check(scopes[""]["owner"] == {"grants": 0, "intents": 0}
              and all(x == 0 for x in scopes[""]["service"].values())
              and scopes[company]["owner"]["grants"] == 2 and scopes[company]["owner"]["intents"] >= 2
              and scopes[peer]["owner"] == {"grants": 2, "intents": 0}, "SOURCE_TENANT_BOUNDARIES")
        models = {company: monitor_model(source_query, company, main["event_id"], transaction=False),
                  peer: monitor_model(source_query, peer, foreign["event_id"], transaction=False)}
        check(models[company]["total_events"] == 56 and len(models[company]["events"]) == 50
              and [x["sequence"] for x in models[company]["selected"]["history"]] == [6, 5, 4, 3, 2, 1]
              and models[peer]["total_events"] == 1
              and monitor_model(source_query, company, foreign["event_id"], transaction=False)["selected"] is None,
              "SNAPSHOT_MONITOR_AND_FOREIGN_BOUNDARIES")
        legacy_denials(source_query, recovery)
        check(source_query(state_query) == "pending", "SNAPSHOT_PENDING")
        owner.read(client, pending_ref)
        check(owner.intent(pending_ref)["state"] == "confirmed" and owner.remote_draft(pending_ref) == remote
              and owner.native_snapshot() == native, "LIVE_RECONCILIATION_ONLY")
        check(source_query(state_query) == "pending" and row_hashes(source_query) == hashes,
              "ALL35_EXCLUDE_LIVE_COMMIT")
        check(security_manifest(source_query) == security and catalog_manifest(source_query, schemas=SCHEMAS) == catalog,
              "SNAPSHOT_CATALOG_STABLE")
        archive = snapshot.dump()
        check(archive.startswith(b"PGDMP") and len(archive) <= MAX_BYTES
              and sequence_state(source_query) == sequence, "BOUNDED_DUMP_AND_STABLE_SEQUENCE")
        exported_at = snapshot.exported_at
    check(snapshot.closed, "EXPORTER_CLOSED_BEFORE_CRYPTO")

    PHASE = "authenticated-encryption-and-tamper-denial"
    encrypted = ci.compose("run", "--rm", "-T", "tools", "scripts/ops/private-backup-crypt.php", "encrypt", data=archive).stdout
    check(0 < len(encrypted) <= MAX_BYTES, "ENCRYPTED_BOUND")
    recovered = ci.compose("run", "--rm", "-T", "tools", "scripts/ops/private-backup-crypt.php", "decrypt", data=encrypted).stdout
    check(recovered == archive, "CRYPTO_ROUNDTRIP")
    envelope = json.loads(base64.b64decode(encrypted, validate=True))
    check(isinstance(envelope, dict) and isinstance(envelope.get("value"), str)
          and HASH.fullmatch(envelope.get("mac", "")), "AUTHENTICATED_ENVELOPE")
    ciphertext = bytearray(base64.b64decode(envelope["value"], validate=True))
    check(bool(ciphertext), "NONEMPTY_CIPHERTEXT")
    ciphertext[0] ^= 1; envelope["value"] = base64.b64encode(ciphertext).decode()
    rejected = ci.compose("run", "--rm", "-T", "tools", "scripts/ops/private-backup-crypt.php", "decrypt",
                          data=base64.b64encode(wire(envelope)), check=False)
    check(rejected.returncode != 0 and rejected.stdout == b"", "TAMPER_NO_PLAINTEXT")

    PHASE = "inert-restore35"
    with isolated_restore_cluster({"roles": ROLES}, restore_run) as (restore_sql, restore_command):
        container = restore_command[-1]
        check(re.fullmatch(r"imagine-crm-restore-check-[a-f0-9]{10}", container), "FRESH_RESTORE_IDENTITY")
        configuration = json.loads(restore_run(["docker", "inspect", "--format", "{{json .HostConfig}}", container]))
        check(configuration["NetworkMode"] == "none" and not configuration.get("PortBindings")
              and not configuration.get("Privileged") and "/var/lib/postgresql/data" in configuration.get("Tmpfs", {}), "INERT_RESTORE")
        restore_run(restore_command + ["pg_restore", "--exit-on-error", "--single-transaction",
                    "-U", "crm_homologation_restore", "-d", DATABASE], content=recovered)

        def restored_query(statement):
            return restore_sql("SET timezone='UTC'; SET search_path=pg_catalog,public;" + statement)

        restored_security = combined_profile(restored_query)
        restored_catalog = catalog_manifest(restored_query, schemas=SCHEMAS)
        comparison = catalog_comparison(catalog, restored_catalog)
        check(comparison is not None and security_constraints_match(restored_security, restored_catalog)
              and security_catalog_comparison(security, restored_security, catalog, restored_catalog) == comparison,
              "COMPLETE_TWO_SCHEMA_CATALOG_AND_SECURITY")
        catalog_negatives = recovery.catalog_mutation_probes(catalog, restored_catalog, comparison)
        security_negatives = recovery.security_mutation_probes(security, restored_security, catalog, restored_catalog)
        check(row_hashes(restored_query) == hashes and sequence_state(restored_query) == sequence, "ALL35_AND_SEQUENCE_EXACT")
        for reference, expected in ((pending_ref, pending), (confirmed_ref, confirmed)):
            row = json.loads(restored_query("SELECT to_jsonb(i)::text FROM public.crm_private_owner_draft_intents i WHERE draft_ref=" + literal(reference)))
            check(row == expected, "OWNER_STATE_AND_AUTHORSHIP_EXACT")
        names = ",".join(literal(name) for name in ROLES)
        check(restored_query("SELECT count(*) FROM pg_roles WHERE rolname IN (" + names + ") AND "
                            "(rolcanlogin OR rolsuper OR rolbypassrls OR rolcreatedb OR rolcreaterole OR rolreplication OR rolinherit)") == "0",
              "NINE_INERT_ROLES")
        check(restored_query("SELECT count(*) FROM pg_authid WHERE rolname IN (" + names + ") AND rolpassword IS NOT NULL") == "0",
              "NO_RESTORED_PASSWORDS")
        check({tenant: {"owner": recovery.tenant_counts(restored_query, tenant, transaction=True),
                        "service": readonly_scope(restored_query, tenant, transaction=True)}
               for tenant in scopes} == scopes, "RESTORED_SCOPES_EXACT")
        check({company: monitor_model(restored_query, company, main["event_id"], transaction=True),
               peer: monitor_model(restored_query, peer, foreign["event_id"], transaction=True)} == models
              and monitor_model(restored_query, company, foreign["event_id"], transaction=True)["selected"] is None,
              "RESTORED_READER_HISTORY_AND_TENANT_EXACT")
        denials = recovery.immutable_probes(restore_command, pending, peer)
        sql_denial(restore_command, "crm_homologation_app", company, "SELECT 1 FROM crm_auto_service.outbox LIMIT 1", "42501")
        sql_denial(restore_command, "crm_auto_reader_owner", company, "SELECT 1 FROM public.crm_private_owner_draft_intents LIMIT 1", "42501")
        sql_denial(restore_command, "crm_homologation_app", "", "SELECT crm_auto_service.read_execution_monitor(NULL)", "P0001")
        sql_denial(restore_command, "crm_auto_reader_owner", company,
                   "UPDATE crm_auto_service.execution_observations SET attempt=attempt WHERE event_id=" + literal(main["event_id"]), "P0001")
        check(row_hashes(restored_query) == hashes and sequence_state(restored_query) == sequence, "DENIALS_CHANGED_NOTHING")
        legacy_denials(restored_query, recovery)
    removed = subprocess.run(["docker", "inspect", container], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=15)
    check(removed.returncode != 0, "INERT_CONTAINER_REMOVED")
    descriptor = os.open(vault.parent / PROOF_FILE, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
    with os.fdopen(descriptor, "w") as stream:
        json.dump(proof, stream, sort_keys=True)
    print(json.dumps({"check": "CRM_OWNER_MONITOR_COEXISTENCE_35_PASS_NO_GO", "head": head,
        "agents_sha": provenance["agents_sha"], "central_sha": provenance["central_sha"],
        "checks": CHECKS + owner.CHECKS + helper.CHECKS + recovery.CHECKS,
        "tables": 35, "sequences": 1, "functions": 9, "inert_roles": 9,
        "sql_denials": denials + 4, "catalog_mutation_denials": catalog_negatives, "security_mutation_denials": security_negatives,
        "data_manifest_sha256": manifest_digest(hashes), "security_manifest_sha256": manifest_digest(security),
        "restored_security_manifest_sha256": manifest_digest(restored_security), "security_raw_equal": security == restored_security,
        "source_catalog_sha256": manifest_digest(catalog), "restored_catalog_sha256": manifest_digest(restored_catalog),
        "catalog_raw_equal": catalog == restored_catalog, "catalog_roundtrip_rule": comparison["rule"],
        "catalog_roundtrip_equivalences": comparison["equivalences"], "security_catalog_bindings_exact": True,
        "snapshot_exported_at": exported_at, "snapshot_pending_live_confirmed_restored_pending": True,
        "same_owner_session_read_both": True, "monitor_window_business_and_s2s_immutable": True,
        "legacy_backup_rejects_35": True, "owner27_rejects_35": True, "new_n8n_executions": 0,
        "synthetic_monitor_presentation_only": True, "authenticated_tamper_denied": True,
        "current_authority_restored": False, "restored_session_reused": False, "restored_runtime_activated": False,
        "distributed_restore_verified": False, "plaintext_dump_on_disk": False, "dump_or_key_artifacts": False,
        "operational": False}, sort_keys=True))


def main():
    global PHASE
    ci.preflight()  # Actual PR, exact image/head and pinned provider before any vault or SQL access.
    check(len(sys.argv) in (2, 3), "ARGUMENTS")
    mode = sys.argv[2] if len(sys.argv) == 3 else "run"
    check(mode in ("install", "run", "browser-snapshot", "after-owner-disabled"), "MODE")
    vault = Path(sys.argv[1]).resolve()
    check(vault.name == "fixtures.json", "ORIGINAL_VAULT")
    bounded_json(vault, 65536)
    check(os.environ.get("CRM_N8N_EXECUTION_MONITOR") == MONITOR
          and os.environ.get("CRM_PRIVATE_CI_MONITOR_PROFILE") == "execution-monitor-v1", "COMBINED_CI_OPT_INS")
    recovery = module("combined_owner_recovery", "test-private-crm-owner-draft-recovery.py")
    PHASE = mode
    if mode == "install":
        install(recovery)
    elif mode == "browser-snapshot":
        print(json.dumps(browser_snapshot(vault), sort_keys=True))
    elif mode == "after-owner-disabled":
        identity = ci.compose("ps", "-q", "app").stdout.decode().strip()
        check(re.fullmatch(r"[a-f0-9]{64}", identity), "APP_IDENTITY")
        config = json.loads(ci.run(["docker", "inspect", identity], "OWNER_MONITOR_INSPECTION_FAILED").stdout)[0]
        env = dict(item.split("=", 1) for item in config["Config"]["Env"])
        check(env.get("CRM_PRIVATE_OWNER_DRAFTS") == "" and env.get("CRM_N8N_EXECUTION_MONITOR") == MONITOR,
              "INDEPENDENT_OPT_INS")
        owner = module("combined_disabled_owner", "test-private-crm-owner-drafts.py")
        status, html, location = owner.capture(None, "/painel?section=n8n")
        check(status == 303 and location.endswith("/login") and "data-execution-monitor=" not in html,
              "MONITOR_REMAINS_PRIVATE_AFTER_OWNER_DISABLE")
        fixtures = bounded_json(vault.parent / "execution-monitor-fixtures.json")
        proof = bounded_json(vault.parent / PROOF_FILE, 16384)
        member = fixtures["member"]
        check(proof.get("schema") == "CRM_OWNER_MONITOR_CI_V1" and proof.get("head") == ci.preflight()[0]
              and member["organization"] == proof["company"]
              and all(UUID.fullmatch(proof.get(name, "")) for name in ("company", "confirmed_ref", "main_event", "foreign_event"))
              and len(member["recovery"]) == 5 and re.fullmatch(r"[a-f0-9]{24}", member["recovery"][1]),
              "MEMBER_UNUSED_MFA_AND_SCOPE")
        helper = module("combined_disabled_original_http", "test-private-crm-http.py")
        client = helper.Client(); client.login(member, recovery=member["recovery"][1])
        before = row_hashes(ci.sql, BUSINESS_TABLES)
        status, html, _ = owner.capture(client, "/agentes/rascunhos/" + proof["confirmed_ref"])
        check(status == 404 and "data-owner-draft-view=" not in html, "REAL_OWNER_REFERENCE_DEFAULT_OFF")
        monitor_http(client, None, owner)
        monitor_http(client, proof["main_event"], owner)
        monitor_http(client, proof["foreign_event"], owner, expected=404)
        check(row_hashes(ci.sql, BUSINESS_TABLES) == before, "INDEPENDENT_MONITOR_WINDOW_UNCHANGED")
        print(json.dumps({"check": "CRM_OWNER_MONITOR_INDEPENDENT_DISABLE_PASS_NO_GO",
            "owner_default_off": True, "monitor_authenticated_list_and_detail": True,
            "member_recovery_index": 1, "foreign_event_denied": True,
            "business_and_s2s_immutable": True, "operational": False}, sort_keys=True))
    else:
        execute(vault, recovery)


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        reason = str(error) if isinstance(error, ci.Failure) else type(error).__name__.upper()
        if not re.fullmatch(r"[A-Z0-9_]{1,180}", reason):
            reason = "OWNER_MONITOR_UNCLASSIFIED_FAILURE"
        print(json.dumps({"check": "CRM_OWNER_MONITOR_COEXISTENCE_DENIED", "phase": PHASE, "reason": reason}), file=sys.stderr)
        sys.exit(2)
