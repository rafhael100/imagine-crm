"""Closed backup/restore contracts for the synthetic private CRM database.

Restored roles never receive passwords, LOGIN, SUPERUSER or BYPASSRLS. Object
ownership is preserved; in particular SECURITY DEFINER functions must retain
their restricted owner. This module has no import-time database/container action.
"""
from __future__ import annotations

from contextlib import contextmanager
import hashlib
import json
import os
import re
import secrets
import subprocess
import time

DATABASE = "imagine_crm_private_homologation"
BASE_ROLES = ("crm_homologation_owner", "crm_homologation_app", "crm_homologation_iam",
              "crm_homologation_enrollment", "crm_homologation_operator")
S2S_ROLES = ("crm_auto_reader_owner", "crm_auto_transport", "crm_auto_sender")
S2S_TABLES = tuple("crm_auto_service." + name for name in
                   ("grants", "nonces", "audit", "outbound_grants", "outbox"))
S2S_FUNCTIONS = {
    "crm_auto_service.read_state(uuid,text,text,text,uuid,text,text,bigint)",
    "crm_auto_service.stage_outbound(uuid,uuid)",
    "crm_auto_service.ack_outbound(uuid,uuid,text)",
}
RECEIPT_FUNCTION = "crm_auto_service.receipt_candidate(uuid,uuid)"
EXECUTION_ROLES = ("crm_auto_execution_reconciler",)
EXECUTION_TABLES = tuple("crm_auto_service." + name for name in
                         ("execution_read_grants", "execution_observations", "execution_projections"))
EXECUTION_FUNCTIONS = {
    "crm_auto_service.execution_candidate(uuid,uuid)",
    "crm_auto_service.project_execution(uuid,uuid,jsonb,text)",
    "crm_auto_service.read_execution_projection(uuid,uuid)",
    "crm_auto_service.reject_execution_observation_mutation()",
}
BASE_TENANT_TABLES = tuple("public." + name for name in (
    "crm_memberships", "crm_private_trials", "crm_private_leads", "crm_private_tasks",
    "crm_private_automation_runs", "crm_private_events", "crm_private_orders",
    "crm_private_preferences", "crm_private_funnels", "crm_private_invitations"))


def require(condition: bool, message: str) -> None:
    if not condition:
        raise RuntimeError(message)


def _acl(expression: str) -> str:
    # OIDs differ across clusters. Compare role names and privilege semantics.
    return """(SELECT COALESCE(jsonb_agg(jsonb_build_array(
        pg_get_userbyid(grant_item.grantor),
        CASE WHEN grant_item.grantee=0 THEN 'PUBLIC' ELSE pg_get_userbyid(grant_item.grantee) END,
        grant_item.privilege_type,grant_item.is_grantable)
        ORDER BY grant_item.grantor::regrole::text,
          CASE WHEN grant_item.grantee=0 THEN 'PUBLIC' ELSE pg_get_userbyid(grant_item.grantee) END,
          grant_item.privilege_type,grant_item.is_grantable),'[]'::jsonb)
        FROM aclexplode(""" + expression + ") grant_item)"


def security_manifest(query) -> dict:
    """Read owners, ACLs and RLS without reading credentials or role passwords."""
    schema_acl = _acl("coalesce(n.nspacl,acldefault('n',n.nspowner))")
    table_acl = _acl("coalesce(c.relacl,acldefault(CASE WHEN c.relkind='S' THEN 'S'::\"char\" ELSE 'r'::\"char\" END,c.relowner))")
    column_acl = _acl("a.attacl")
    function_acl = _acl("coalesce(p.proacl,acldefault('f',p.proowner))")
    return json.loads(query(f"""
      SELECT jsonb_build_object(
        'schemas',COALESCE((SELECT jsonb_agg(jsonb_build_object(
          'name',n.nspname,'owner',pg_get_userbyid(n.nspowner),'acl',{schema_acl}) ORDER BY n.nspname)
          FROM pg_namespace n WHERE n.nspname !~ '^pg_' AND n.nspname<>'information_schema'),'[]'::jsonb),
        'relations',COALESCE((SELECT jsonb_agg(jsonb_build_object(
          'name',n.nspname||'.'||c.relname,'kind',c.relkind,'owner',pg_get_userbyid(c.relowner),
          'rls',c.relrowsecurity,'force_rls',c.relforcerowsecurity,'acl',{table_acl}) ORDER BY n.nspname,c.relname)
          FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace
          WHERE n.nspname !~ '^pg_' AND n.nspname<>'information_schema'
          AND c.relkind IN ('r','p','v','m','f','S')),'[]'::jsonb),
        'columns',COALESCE((SELECT jsonb_agg(jsonb_build_object(
          'table',n.nspname||'.'||c.relname,'name',a.attname,'acl',{column_acl}) ORDER BY n.nspname,c.relname,a.attnum)
          FROM pg_attribute a JOIN pg_class c ON c.oid=a.attrelid JOIN pg_namespace n ON n.oid=c.relnamespace
          WHERE n.nspname !~ '^pg_' AND n.nspname<>'information_schema' AND c.relkind IN ('r','p')
          AND a.attnum>0 AND NOT a.attisdropped),'[]'::jsonb),
        'policies',COALESCE((SELECT jsonb_agg(jsonb_build_object(
          'table',n.nspname||'.'||c.relname,'name',p.polname,'command',p.polcmd,'permissive',p.polpermissive,
          'roles',(SELECT jsonb_agg(role_name ORDER BY role_name) FROM
            (SELECT CASE WHEN role_id=0 THEN 'PUBLIC' ELSE pg_get_userbyid(role_id) END role_name
             FROM unnest(p.polroles) role_id) names),
          'using',pg_get_expr(p.polqual,p.polrelid),'check',pg_get_expr(p.polwithcheck,p.polrelid))
          ORDER BY n.nspname,c.relname,p.polname)
          FROM pg_policy p JOIN pg_class c ON c.oid=p.polrelid JOIN pg_namespace n ON n.oid=c.relnamespace
          WHERE n.nspname !~ '^pg_' AND n.nspname<>'information_schema'),'[]'::jsonb),
        'functions',COALESCE((SELECT jsonb_agg(jsonb_build_object(
          'signature',p.oid::regprocedure::text,'owner',pg_get_userbyid(p.proowner),
          'security_definer',p.prosecdef,'config',p.proconfig,'acl',{function_acl},
          'definition',pg_get_functiondef(p.oid)) ORDER BY p.oid::regprocedure::text)
          FROM pg_proc p JOIN pg_namespace n ON n.oid=p.pronamespace
          WHERE n.nspname !~ '^pg_' AND n.nspname<>'information_schema'),'[]'::jsonb),
        'constraints',COALESCE((SELECT jsonb_agg(jsonb_build_object(
          'table',n.nspname||'.'||c.relname,'name',k.conname,'type',k.contype,
          'validated',k.convalidated,'definition',pg_get_constraintdef(k.oid,true))
          ORDER BY n.nspname,c.relname,k.conname)
          FROM pg_constraint k JOIN pg_class c ON c.oid=k.conrelid
          JOIN pg_namespace n ON n.oid=c.relnamespace
          WHERE n.nspname !~ '^pg_' AND n.nspname<>'information_schema'),'[]'::jsonb),
        'triggers',COALESCE((SELECT jsonb_agg(jsonb_build_object(
          'table',n.nspname||'.'||c.relname,'name',t.tgname,'type',t.tgtype,
          'function',t.tgfoid::regprocedure::text,'enabled',t.tgenabled,
          'definition',pg_get_triggerdef(t.oid,true)) ORDER BY n.nspname,c.relname,t.tgname)
          FROM pg_trigger t JOIN pg_class c ON c.oid=t.tgrelid
          JOIN pg_namespace n ON n.oid=c.relnamespace
          WHERE NOT t.tgisinternal AND n.nspname !~ '^pg_' AND n.nspname<>'information_schema'),'[]'::jsonb),
        'default_acls',(SELECT count(*) FROM pg_default_acl)
      );
    """))


def detect_profile(query, base_tables: list[str]) -> tuple[dict, dict]:
    require(query("SELECT current_database() || '|' || mode FROM public.crm_private_installation WHERE id=1")
            == DATABASE + "|synthetic-v1", "Unsupported private backup source.")
    require(base_tables and len(set(base_tables)) == len(base_tables)
            and all(re.fullmatch(r"crm_[a-z0-9_]+", table) for table in base_tables),
            "Invalid private schema inventory.")
    manifest = security_manifest(query)
    schemas = {item["name"] for item in manifest["schemas"]}
    require(schemas in ({"public"}, {"public", "crm_auto_service"}),
            "Unknown database schema in private backup.")
    s2s = "crm_auto_service" in schemas
    functions = {item["signature"] for item in manifest["functions"]}
    relations = {item["name"] for item in manifest["relations"]}
    execution = bool(functions & EXECUTION_FUNCTIONS or relations & set(EXECUTION_TABLES))
    service_tables = (S2S_TABLES if s2s else ()) + (EXECUTION_TABLES if execution else ())
    tables = tuple("public." + table for table in base_tables) + service_tables
    expected_relations = {(table, "r") for table in tables}
    if s2s:
        expected_relations.add(("crm_auto_service.audit_id_seq", "S"))
    require({(item["name"], item["kind"]) for item in manifest["relations"]} == expected_relations,
            "Incomplete or unknown private backup relation profile.")
    accepted_functions = ((S2S_FUNCTIONS | {RECEIPT_FUNCTION} | EXECUTION_FUNCTIONS,)
                          if execution else (S2S_FUNCTIONS, S2S_FUNCTIONS | {RECEIPT_FUNCTION}))
    require((not s2s and not execution and not functions) or (s2s and functions in accepted_functions),
            "Incomplete or unknown private S2S function profile.")
    require(manifest["default_acls"] == 0, "Unreviewed default grants in private backup.")
    if execution:
        guards = {(item["name"], item["type"], item["enabled"], item["function"])
                  for item in manifest["triggers"]
                  if item["table"] == "crm_auto_service.execution_observations"}
        guard_function = "crm_auto_service.reject_execution_observation_mutation()"
        require(guards == {("execution_history_immutable", 27, "O", guard_function),
                           ("execution_history_no_truncate", 34, "O", guard_function)},
                "Execution history immutability trigger profile differs.")
    roles = BASE_ROLES + (S2S_ROLES if s2s else ()) + (EXECUTION_ROLES if execution else ())
    for schema in manifest["schemas"]:
        expected_owner = "crm_auto_reader_owner" if schema["name"] == "crm_auto_service" else "pg_database_owner"
        require(schema["owner"] == expected_owner, "Private schema ownership drift.")
    for relation in manifest["relations"]:
        owner = "crm_auto_reader_owner" if relation["name"].startswith("crm_auto_service.") else "crm_homologation_owner"
        require(relation["owner"] == owner, "Private relation ownership drift.")
    rls_tables = BASE_TENANT_TABLES + service_tables
    require(all(item["rls"] and item["force_rls"] for item in manifest["relations"]
                if item["name"] in rls_tables), "Private FORCE RLS profile missing.")
    for function in manifest["functions"]:
        require(function["owner"] == "crm_auto_reader_owner" and function["security_definer"] is True
                and function["config"] in (["search_path=pg_catalog, public"], ["search_path=pg_catalog,public"]),
                "Restricted S2S function ownership or configuration drift.")
    for kind in ("schemas", "relations", "columns", "functions"):
        for item in manifest[kind]:
            for grantor, grantee, _, _ in item["acl"]:
                require(grantor in (*roles, "pg_database_owner")
                        and grantee in (*roles, "PUBLIC", "pg_database_owner"),
                        "Unknown role referenced by private object grants.")
    require(all(set(policy["roles"]) <= set((*roles, "PUBLIC")) for policy in manifest["policies"]),
            "Unknown role referenced by private RLS policy.")
    role_names = ",".join("'" + role + "'" for role in roles)
    role_state = json.loads(query("SELECT json_agg(json_build_object('name',rolname,"
        "'elevated',rolsuper OR rolbypassrls OR rolcreatedb OR rolcreaterole)) "
        "FROM pg_roles WHERE rolname IN (" + role_names + ")"))
    require(role_state is not None and {item["name"] for item in role_state} == set(roles)
            and all(not item["elevated"] for item in role_state if item["name"] != "crm_homologation_owner"),
            "Private restore role profile is incomplete or elevated.")
    require(query("SELECT count(*) FROM pg_auth_members WHERE member IN "
                  "(SELECT oid FROM pg_roles WHERE rolname IN (" + role_names + "))") == "0",
            "Unreviewed role inheritance in private backup.")
    return {"name": "synthetic-private-s2s-execution-v1" if execution else
            ("synthetic-private-s2s-v1" if s2s else "synthetic-private-base-v1"),
            "tables": tables, "roles": roles, "rls_tables": rls_tables,
            "s2s": s2s, "receipt_reconciliation": RECEIPT_FUNCTION in functions,
            "execution_feedback": execution}, manifest


def table_counts(query, profile: dict) -> dict:
    # Keep historical base-table manifest keys; qualify added S2S tables.
    return {table.removeprefix("public."): int(query("SELECT count(*) FROM " + table))
            for table in profile["tables"]}


def scoped_counts(query, tenant: str, *, role: str, tables: tuple[str, ...], manage_transaction: bool = True) -> str:
    require(not tenant or re.fullmatch(r"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}", tenant),
            "Invalid private tenant UUID.")
    require(role in ("crm_homologation_app", "crm_auto_reader_owner")
            and set(tables) <= set(BASE_TENANT_TABLES + S2S_TABLES + EXECUTION_TABLES), "Unsupported RLS probe.")
    body = " || '|' || ".join("(SELECT count(*) FROM " + table + ")" for table in tables)
    statement = "SET ROLE " + role + "; SELECT set_config('app.tenant_id','" + tenant
    statement += "',true); SELECT 'SCOPED|' || " + body + ";"
    # Snapshot importers already own a read-only transaction. Restoration callers
    # keep the original explicit BEGIN/ROLLBACK; never nest these implicitly.
    if manage_transaction:
        statement = "BEGIN; " + statement + " ROLLBACK;"
    result = query(statement)
    lines = [line for line in result.splitlines() if line.startswith("SCOPED|")]
    require(len(lines) == 1, "Missing private RLS evidence.")
    return lines[0]


def manifest_digest(manifest: dict) -> str:
    return hashlib.sha256(json.dumps(manifest, sort_keys=True, separators=(",", ":")).encode()).hexdigest()


@contextmanager
def isolated_restore_cluster(profile: dict, run):
    """Create only a new network-none PG16; never connect to an existing host."""
    restorer = "imagine-crm-restore-check-" + secrets.token_hex(5)
    environment = dict(os.environ, POSTGRES_PASSWORD=secrets.token_hex(32))
    command = ["docker", "exec", "-i", restorer]

    def query(statement: str) -> str:
        return run(command + ["psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-U",
                   "crm_homologation_restore", "-d", DATABASE, "-c", statement]).decode().strip()

    try:
        run(["docker", "run", "--name", restorer, "--network", "none", "--security-opt", "no-new-privileges:true",
             "--tmpfs", "/var/lib/postgresql/data", "-d", "--env", "POSTGRES_PASSWORD",
             "--env", "POSTGRES_DB=" + DATABASE, "--env", "POSTGRES_USER=crm_homologation_restore", "postgres:16"],
            environment=environment)
        ready = False
        for _ in range(40):
            process = subprocess.run(["docker", "exec", restorer, "cat", "/proc/1/comm"], capture_output=True, text=True)
            if process.returncode == 0 and process.stdout.strip() == "postgres":
                try:
                    if query("SELECT current_database()") == DATABASE:
                        ready = True
                        break
                except RuntimeError:
                    pass
            time.sleep(0.5)
        require(ready, "Final restore PostgreSQL server unavailable.")
        require(tuple(profile["roles"]) in (BASE_ROLES, BASE_ROLES + S2S_ROLES,
                BASE_ROLES + S2S_ROLES + EXECUTION_ROLES), "Unsupported restore roles.")
        query(";".join("CREATE ROLE " + role + " NOLOGIN NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOINHERIT"
                       for role in profile["roles"]))
        yield query, command
    finally:
        subprocess.run(["docker", "rm", "-f", restorer], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)


def verify_restored_security(query, profile: dict, expected: dict, base_tables: list[str]) -> None:
    restored_profile, actual = detect_profile(query, base_tables)
    require(restored_profile == profile, "Restored private backup profile differs.")
    def comparable(manifest):
        # pg_dump/restore can distribute varchar[] -> text[] coercions into
        # each literal of legacy CHECK arrays. pg_get_constraintdef decompiles
        # those equivalent trees differently. This extension does not attempt
        # a general SQL-equivalence rewrite. Keep EVERY constraint's identity,
        # kind and validation state; compare exact definitions for PK/FK/UNIQUE
        # and for every CHECK in the three newly introduced execution tables.
        constraints = [({key: value for key, value in item.items() if key != "definition"}
                        if item["type"] == "c" and item["table"] not in EXECUTION_TABLES else item)
                       for item in manifest["constraints"]]
        return {**manifest, "constraints": constraints}

    expected_comparable, actual_comparable = comparable(expected), comparable(actual)
    changed = [key for key in expected_comparable
               if actual_comparable.get(key) != expected_comparable[key]]
    diagnostic = ""
    if "constraints" in changed:
        # Schema-only diagnostics: never print rows, wire receipts, queries,
        # role credentials or a backup payload when identifying a restore drift.
        before = {(item["table"], item["name"]): item for item in expected_comparable["constraints"]}
        after = {(item["table"], item["name"]): item for item in actual_comparable["constraints"]}
        differences = [{"source": before.get(key), "restored": after.get(key)}
                       for key in sorted(set(before) | set(after)) if before.get(key) != after.get(key)]
        diagnostic = "; constraint_schema_diff=" + json.dumps(differences[:3], sort_keys=True)
    require(not changed, "Restored security manifest differs in: " + ", ".join(changed) + diagnostic)
    role_names = ",".join("'" + role + "'" for role in profile["roles"])
    require(query("SELECT count(*) FROM pg_roles WHERE rolname IN (" + role_names + ") "
                  "AND (rolcanlogin OR rolsuper OR rolbypassrls OR rolcreatedb OR rolcreaterole OR rolinherit)") == "0",
            "Restored application/definer roles must remain inert and unprivileged.")
