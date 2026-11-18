#!/usr/bin/env python3
"""Real PG16 migration regressions, restricted to the disposable GitHub job.

Each incompatible fixture must fail inside the migration transaction, preserve
its exact pre-attempt metadata/data, and return to the nominal replay afterward.
No VPS, credentials, operational database, or deployment is used.
"""
from __future__ import annotations

import json
import os
from pathlib import Path
import re
import subprocess

ROOT = Path(__file__).resolve().parents[2]
TABLE = "public.crm_private_automation_runs"
APP = "crm_homologation_app"
TENANT = "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
SCOPE = "organization_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid"
POLICY = f"CREATE POLICY tenant_scope ON {TABLE} USING ({SCOPE}) WITH CHECK ({SCOPE})"
MIGRATION = ROOT / "infra/homologation/migrations/001_automation_ledger_candidate.sql"
BASELINE_SHA = "403a2b735e77bd72b529e296b20e63eab1c00859"


def require(condition: bool, message: str) -> None:
    if not condition:
        raise RuntimeError(message)


def main() -> None:
    require(os.environ.get("GITHUB_ACTIONS") == "true"
            and os.environ.get("GITHUB_REPOSITORY") == "rafhael100/imagine-crm"
            and os.environ.get("GITHUB_EVENT_NAME") == "pull_request",
            "Disposable public-repository PR CI required.")
    container = os.environ.get("PG_CONTAINER", "")
    require(re.fullmatch(r"[a-f0-9]{12,64}", container) is not None,
            "Exact disposable PostgreSQL service required.")
    command = ["docker", "exec", "-i", container, "psql", "-X", "-qAt",
               "-v", "ON_ERROR_STOP=1", "-U", "crm_homologation_owner",
               "-d", "imagine_crm_private_homologation"]

    def execute(sql: str) -> subprocess.CompletedProcess:
        return subprocess.run(command, input=sql, text=True, capture_output=True,
                              check=False, timeout=35)

    def sql(statement: str) -> str:
        result = execute(statement)
        require(result.returncode == 0, "Synthetic PostgreSQL fixture failed.")
        return result.stdout.strip()

    require(sql("SELECT current_database() || '|' || current_user || '|' || mode "
                "FROM crm_private_installation WHERE id=1")
            == "imagine_crm_private_homologation|crm_homologation_owner|synthetic-v1",
            "Private synthetic baseline required.")
    require(sql(f"SELECT count(*) FROM {TABLE}") == "1",
            "Existing ledger row required for preservation proof.")
    migration = MIGRATION.read_text()

    def replay() -> None:
        result = execute(migration)
        require(result.returncode == 0 and "CENTRAL_AUTO_LEDGER_INCREMENTAL_V1_READY"
                in result.stdout, "Nominal incremental replay failed.")

    def snapshot() -> dict:
        return json.loads(sql(f"""
          SELECT jsonb_build_object(
            'table', (SELECT jsonb_build_object('owner',relowner,'rls',relrowsecurity,
                       'force',relforcerowsecurity,'acl',coalesce(relacl,acldefault('r',relowner))::text)
                      FROM pg_class WHERE oid='{TABLE}'::regclass),
            'columns', (SELECT jsonb_agg(jsonb_build_object('name',attname,'acl',coalesce(attacl,'{{}}'::aclitem[])::text)
                        ORDER BY attnum) FROM pg_attribute
                        WHERE attrelid='{TABLE}'::regclass AND attnum>0 AND NOT attisdropped),
            'policies', (SELECT jsonb_agg(jsonb_build_object('name',polname,'command',polcmd,
                         'permissive',polpermissive,'roles',polroles,
                         'using',pg_get_expr(polqual,polrelid),
                         'check',pg_get_expr(polwithcheck,polrelid)) ORDER BY polname)
                         FROM pg_policy WHERE polrelid='{TABLE}'::regclass),
            'rows', (SELECT jsonb_agg(to_jsonb(r) ORDER BY id) FROM {TABLE} r),
            'leads', (SELECT jsonb_agg(to_jsonb(l) ORDER BY id) FROM public.crm_private_leads l)
          );
        """))

    replay()
    nominal = snapshot()
    begin = f"BEGIN; SET ROLE {APP}; SELECT set_config('app.tenant_id','{TENANT}',true); "

    # Counterexamples execute the real prior candidate, pinned in the workflow.
    baseline_root = ROOT / "previous-migration"
    baseline_head = subprocess.run(["git", "rev-parse", "HEAD"], cwd=baseline_root,
                                   text=True, capture_output=True, check=True).stdout.strip()
    require(baseline_head == BASELINE_SHA, "Pinned prior migration checkout required.")
    baseline = (baseline_root / MIGRATION.relative_to(ROOT)).read_text()
    try:
        sql(f"GRANT DELETE ON {TABLE} TO {APP}")
        old = execute(baseline)
        require(old.returncode == 0, "Prior migration counterexample failed unexpectedly.")
        result = sql(begin + f"DELETE FROM {TABLE}; SELECT 'REMAINING|' || count(*) "
                     f"FROM {TABLE}; ROLLBACK;")
        require("REMAINING|0" in result.splitlines(), "Prior DELETE counterexample not reproduced.")
    finally:
        sql(f"REVOKE DELETE ON {TABLE} FROM {APP}")
    replay()
    require(snapshot() == nominal, "Counterexample changed existing records.")
    print("REPRODUCED prior migration announced READY while application DELETE remained allowed")
    try:
        sql(f"ALTER POLICY tenant_scope ON {TABLE} TO crm_homologation_owner")
        old = execute(baseline)
        require(old.returncode == 0, "Prior policy counterexample failed unexpectedly.")
        result = sql(begin + f"SELECT 'VISIBLE|' || count(*) FROM {TABLE}; ROLLBACK;")
        require("VISIBLE|0" in result.splitlines(), "Prior policy-role counterexample not reproduced.")
    finally:
        sql(f"DROP POLICY tenant_scope ON {TABLE}; {POLICY}")
    replay()
    require(snapshot() == nominal, "Policy counterexample changed existing records.")
    print("REPRODUCED prior migration announced READY while tenant policy denied the application")

    cases = []
    for privilege in ("DELETE", "TRUNCATE", "UPDATE", "REFERENCES", "TRIGGER"):
        cases.append(("table-" + privilege.lower(),
                      f"GRANT {privilege} ON {TABLE} TO {APP}",
                      f"REVOKE {privilege} ON {TABLE} FROM {APP}",
                      "UNEXPECTED_AUTOMATION_TABLE_PRIVILEGES"))
    for column in ("organization_id", "lead_version", "execution_key", "command",
                   "actor_id", "authority_session_id", "template", "settings_version",
                   "lead_id", "id", "created_at"):
        cases.append(("immutable-" + column,
                      f"GRANT UPDATE({column}) ON {TABLE} TO {APP}",
                      f"REVOKE UPDATE({column}) ON {TABLE} FROM {APP}",
                      "UNEXPECTED_AUTOMATION_COLUMN_PRIVILEGES"))
    cases.extend([
        ("public-select", f"GRANT SELECT ON {TABLE} TO PUBLIC",
         f"REVOKE SELECT ON {TABLE} FROM PUBLIC", "UNEXPECTED_AUTOMATION_TABLE_PRIVILEGES"),
        ("table-grant-option", f"GRANT SELECT ON {TABLE} TO {APP} WITH GRANT OPTION",
         f"REVOKE GRANT OPTION FOR SELECT ON {TABLE} FROM {APP}",
         "UNEXPECTED_AUTOMATION_TABLE_PRIVILEGES"),
        ("column-grant-option", f"GRANT UPDATE(state) ON {TABLE} TO {APP} WITH GRANT OPTION",
         f"REVOKE GRANT OPTION FOR UPDATE(state) ON {TABLE} FROM {APP}",
         "UNEXPECTED_AUTOMATION_COLUMN_PRIVILEGES"),
        ("public-column", f"GRANT SELECT(command) ON {TABLE} TO PUBLIC",
         f"REVOKE SELECT(command) ON {TABLE} FROM PUBLIC",
         "UNEXPECTED_AUTOMATION_COLUMN_PRIVILEGES"),
        ("inherited-delete", "CREATE ROLE crm_migration_drift_reader NOLOGIN; "
         f"GRANT crm_migration_drift_reader TO {APP}; "
         f"GRANT DELETE ON {TABLE} TO crm_migration_drift_reader",
         f"REVOKE DELETE ON {TABLE} FROM crm_migration_drift_reader; "
         f"REVOKE crm_migration_drift_reader FROM {APP}; DROP ROLE crm_migration_drift_reader",
         "UNEXPECTED_AUTOMATION_TABLE_PRIVILEGES"),
        ("inherited-column", "CREATE ROLE crm_migration_drift_reader NOLOGIN; "
         f"GRANT crm_migration_drift_reader TO {APP}; "
         f"GRANT UPDATE(execution_key) ON {TABLE} TO crm_migration_drift_reader",
         f"REVOKE UPDATE(execution_key) ON {TABLE} FROM crm_migration_drift_reader; "
         f"REVOKE crm_migration_drift_reader FROM {APP}; DROP ROLE crm_migration_drift_reader",
         "UNEXPECTED_AUTOMATION_COLUMN_PRIVILEGES"),
    ])
    restore_policy = f"DROP POLICY tenant_scope ON {TABLE}; {POLICY}"
    for name, definition in (
        ("restrictive", f"AS RESTRICTIVE USING ({SCOPE}) WITH CHECK ({SCOPE})"),
        ("select-only", f"FOR SELECT USING ({SCOPE})"),
        ("different-role", f"TO {APP} USING ({SCOPE}) WITH CHECK ({SCOPE})"),
        ("using-true", f"USING (true) WITH CHECK ({SCOPE})"),
        ("check-true", f"USING ({SCOPE}) WITH CHECK (true)"),
    ):
        cases.append(("policy-" + name,
                      f"DROP POLICY tenant_scope ON {TABLE}; "
                      f"CREATE POLICY tenant_scope ON {TABLE} {definition}",
                      restore_policy, "UNEXPECTED_AUTOMATION_POLICY_DEFINITION"))
    cases.append(("additional-policy", f"CREATE POLICY extra_scope ON {TABLE} USING (true)",
                  f"DROP POLICY extra_scope ON {TABLE}", "UNEXPECTED_AUTOMATION_POLICY"))

    for name, setup, cleanup, marker in cases:
        sql(setup + f"; ALTER TABLE {TABLE} DISABLE ROW LEVEL SECURITY; "
            f"ALTER TABLE {TABLE} NO FORCE ROW LEVEL SECURITY")
        before = snapshot()
        try:
            rejected = execute(migration)
            require(rejected.returncode != 0 and marker in rejected.stderr,
                    "Migration failed to reject expected drift: " + name)
            require(snapshot() == before, "Rejected migration changed data or metadata: " + name)
        finally:
            sql(cleanup + f"; ALTER TABLE {TABLE} ENABLE ROW LEVEL SECURITY; "
                f"ALTER TABLE {TABLE} FORCE ROW LEVEL SECURITY")
        replay()
        require(snapshot() == nominal, "Nominal state not preserved after: " + name)
        print("PASS migration rejects and rolls back " + name)

    # Exercise effective permissions with the actual restricted role, not only catalogs.
    sql(begin + f"UPDATE {TABLE} SET state='completed',attempts=1,due_at=due_at+30; ROLLBACK;")
    for operation in (
        f"DELETE FROM {TABLE}", f"TRUNCATE {TABLE}",
        *(f"UPDATE {TABLE} SET {column}={column}" for column in (
            "id", "organization_id", "actor_id", "authority_session_id", "template",
            "settings_version", "lead_id", "lead_version", "execution_key", "command", "created_at")),
    ):
        rejected = execute(begin + operation + "; ROLLBACK;")
        require(rejected.returncode != 0 and "permission denied" in rejected.stderr,
                "Unexpected effective ledger privilege.")
    require(snapshot() == nominal, "Permission probes changed existing records.")
    print(f"CRM_INCREMENTAL_POLICY_ACL_DRIFT_PASS cases={len(cases)} "
          "real_permission_denials=13 data_preserved=1 operational_apply=0")


if __name__ == "__main__":
    main()
