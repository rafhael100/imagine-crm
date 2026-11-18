#!/usr/bin/env python3
"""Real PG16 migration regressions, restricted to the disposable GitHub job.

Each incompatible fixture must fail inside the migration transaction, preserve
its exact pre-attempt metadata/data, and return to the nominal replay afterward.
No VPS, credentials, operational database, or deployment is used.
"""
from __future__ import annotations

import hashlib
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
RLS_BASELINE_SHA = "96dbf1aed2d1cf3237bc3631c4594540a0168d2a"
RLS_BASELINE_BLOB = "2b67bc3b707c552c1508f90045ea7f0718ae668a"
READY = "CENTRAL_AUTO_LEDGER_INCREMENTAL_V1_READY"


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
    require(sql("SELECT current_setting('server_version_num')::integer / 10000") == "16",
            "PostgreSQL 16 required for the real migration regression.")
    head = subprocess.run(["git", "rev-parse", "HEAD"], cwd=ROOT, text=True,
                          capture_output=True, check=True, timeout=10).stdout.strip()
    run_id = os.environ.get("GITHUB_RUN_ID", "")
    run_attempt = os.environ.get("GITHUB_RUN_ATTEMPT", "")
    require(re.fullmatch(r"[a-f0-9]{40}", head) is not None
            and re.fullmatch(r"[1-9][0-9]*", run_id) is not None
            and re.fullmatch(r"[1-9][0-9]*", run_attempt) is not None,
            "Actual checkout, workflow run and attempt required.")
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

    # This later historical version already rejects ACL/policy drift, but silently
    # restores the three RLS prerequisites. Prove that behavior with its real bytes.
    rls_root = ROOT / "previous-rls-migration"
    require(rls_root.is_dir() and not rls_root.is_symlink(),
            "Pinned RLS baseline checkout required.")
    rls_head = subprocess.run(["git", "rev-parse", "HEAD"], cwd=rls_root, text=True,
                              capture_output=True, check=True, timeout=10).stdout.strip()
    require(rls_head == RLS_BASELINE_SHA, "RLS baseline commit mismatch.")
    relative = MIGRATION.relative_to(ROOT).as_posix()
    entry = subprocess.run(["git", "ls-tree", "HEAD", "--", relative], cwd=rls_root,
                           text=True, capture_output=True, check=True,
                           timeout=10).stdout.strip()
    require(entry == "100644 blob " + RLS_BASELINE_BLOB + "\t" + relative,
            "RLS baseline source membership mismatch.")
    historical_path = rls_root / relative
    require(historical_path.is_file() and not historical_path.is_symlink(),
            "Regular RLS baseline source required.")
    historical_bytes = historical_path.read_bytes()
    historical_blob = hashlib.sha1(b"blob " + str(len(historical_bytes)).encode("ascii")
                                  + b"\0" + historical_bytes).hexdigest()
    require(historical_blob == RLS_BASELINE_BLOB, "RLS baseline source bytes mismatch.")
    rls_baseline = historical_bytes.decode("utf-8")
    require(nominal["table"]["rls"] is True and nominal["table"]["force"] is True
            and isinstance(nominal["policies"], list) and len(nominal["policies"]) == 1
            and nominal["policies"][0]["name"] == "tenant_scope",
            "Nominal RLS and tenant policy required.")

    rls_cases = (
        ("rls-disabled", f"ALTER TABLE {TABLE} DISABLE ROW LEVEL SECURITY",
         f"ALTER TABLE {TABLE} ENABLE ROW LEVEL SECURITY",
         "UNEXPECTED_AUTOMATION_RLS_DISABLED"),
        ("rls-not-forced", f"ALTER TABLE {TABLE} NO FORCE ROW LEVEL SECURITY",
         f"ALTER TABLE {TABLE} FORCE ROW LEVEL SECURITY",
         "UNEXPECTED_AUTOMATION_RLS_NOT_FORCED"),
        ("policy-missing", f"DROP POLICY tenant_scope ON {TABLE}",
         f"DROP POLICY IF EXISTS tenant_scope ON {TABLE}; {POLICY}",
         "UNEXPECTED_AUTOMATION_POLICY_MISSING"),
    )

    def isolated_rls_snapshot(name: str) -> dict:
        observed = snapshot()
        expected = json.loads(json.dumps(nominal))
        if name == "rls-disabled":
            expected["table"]["rls"] = False
        elif name == "rls-not-forced":
            expected["table"]["force"] = False
        elif name == "policy-missing":
            expected["policies"] = None
        else:
            raise RuntimeError("Unknown isolated RLS fixture.")
        require(observed == expected, "RLS fixture changed another observed property: " + name)
        return observed

    repaired = []
    for name, setup, cleanup, _ in rls_cases:
        try:
            sql(setup)
            before = isolated_rls_snapshot(name)
            old = execute(rls_baseline)
            require(old.returncode == 0 and READY in old.stdout,
                    "Prior RLS migration did not announce READY: " + name)
            require(snapshot() == nominal and before != nominal,
                    "Prior RLS migration did not restore the nominal snapshot: " + name)
            repaired.append(name)
        finally:
            sql(cleanup)
            replay()
            require(snapshot() == nominal, "Prior RLS fixture cleanup failed: " + name)
        print("REPRODUCED prior migration silently repaired " + name)

    denied = []
    for name, setup, cleanup, marker in rls_cases:
        try:
            sql(setup)
            before = isolated_rls_snapshot(name)
            rejected = execute(migration)
            require(rejected.returncode != 0 and marker in rejected.stderr
                    and READY not in rejected.stdout,
                    "Migration failed to reject isolated RLS drift: " + name)
            require(snapshot() == before, "Rejected RLS migration changed the fixture: " + name)
            denied.append(name)
        finally:
            sql(cleanup)
            replay()
            require(snapshot() == nominal, "Rejected RLS fixture cleanup failed: " + name)
        print("PASS migration rejects isolated " + name)
    require(len(repaired) == 3 and len(denied) == 3,
            "All three historical repairs and candidate denials required.")

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

    require(len(cases) == 28 and len({case[0] for case in cases}) == 28,
            "All 28 distinct ACL/policy regressions required.")
    for name, setup, cleanup, marker in cases:
        sql(setup)
        before = snapshot()
        require(before["table"]["rls"] is True and before["table"]["force"] is True,
                "ACL/policy fixture must retain nominal RLS flags: " + name)
        try:
            rejected = execute(migration)
            require(rejected.returncode != 0 and marker in rejected.stderr,
                    "Migration failed to reject expected drift: " + name)
            require(snapshot() == before, "Rejected migration changed data or metadata: " + name)
        finally:
            sql(cleanup)
        replay()
        require(snapshot() == nominal, "Nominal state not preserved after: " + name)
        print("PASS migration rejects and rolls back " + name)

    # Exercise effective permissions with the actual restricted role, not only catalogs.
    sql(begin + f"UPDATE {TABLE} SET state='completed',attempts=1,due_at=due_at+30; ROLLBACK;")
    permission_denials = 0
    for operation in (
        f"DELETE FROM {TABLE}", f"TRUNCATE {TABLE}",
        *(f"UPDATE {TABLE} SET {column}={column}" for column in (
            "id", "organization_id", "actor_id", "authority_session_id", "template",
            "settings_version", "lead_id", "lead_version", "execution_key", "command", "created_at")),
    ):
        rejected = execute(begin + operation + "; ROLLBACK;")
        require(rejected.returncode != 0 and "permission denied" in rejected.stderr,
                "Unexpected effective ledger privilege.")
        permission_denials += 1
    require(permission_denials == 13, "All 13 effective permission denials required.")
    require(snapshot() == nominal, "Permission probes changed existing records.")
    print(f"CRM_INCREMENTAL_POLICY_ACL_DRIFT_PASS cases={len(cases)} "
          f"real_permission_denials={permission_denials} data_preserved=1 operational_apply=0")
    print(json.dumps({
        "check": "CRM_INCREMENTAL_RLS_REAPPLY_DRIFT_PASS",
        "head": head,
        "run_id": run_id,
        "run_attempt": run_attempt,
        "prior_commit": RLS_BASELINE_SHA,
        "prior_sql_blob": RLS_BASELINE_BLOB,
        "prior_silent_repairs_observed": repaired,
        "candidate_isolated_denials": denied,
        "acl_policy_cases": len(cases),
        "effective_permission_denials": permission_denials,
        "observed_metadata_and_data_preserved": True,
        "operational_apply": False,
    }, sort_keys=True))


if __name__ == "__main__":
    main()
