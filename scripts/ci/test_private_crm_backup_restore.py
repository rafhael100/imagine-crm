#!/usr/bin/env python3
"""Real encrypted backup and isolated PG16 restore: base and complete S2S.

Only an ephemeral public-repository PR job may install these synthetic fixtures.
The production/operator script is executed unchanged, including crypto checks.
"""
from __future__ import annotations

import json
import os
from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "scripts/ops"))
from private_crm_backup_contract import (  # noqa: E402
    BASE_ROLES, BASE_TENANT_TABLES, DATABASE, S2S_TABLES, detect_profile,
    isolated_restore_cluster, require, scoped_counts, security_manifest, table_counts, verify_restored_security,
)

A = "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
B = "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb"
ACTOR_A = "cccccccc-cccc-4ccc-8ccc-cccccccccccc"
ACTOR_B = "dddddddd-dddd-4ddd-8ddd-dddddddddddd"
FUNNEL_A = "eeeeeeee-eeee-4eee-8eee-eeeeeeeeeee1"
FUNNEL_B = "eeeeeeee-eeee-4eee-8eee-eeeeeeeeeee2"
LEAD_A = "ffffffff-ffff-4fff-8fff-fffffffffff1"
LEAD_B = "ffffffff-ffff-4fff-8fff-fffffffffff2"
EVENT_A = "11111111-1111-4111-8111-111111111111"
EVENT_B = "22222222-2222-4222-8222-222222222222"


def run(command, content=None, environment=None):
    result = subprocess.run(command, input=content, stdout=subprocess.PIPE,
                            stderr=subprocess.PIPE, env=environment, timeout=180)
    if result.returncode and command[:2] == [sys.executable, str(ROOT / "scripts/ops/backup-private-crm.py")]:
        # The operator script's RuntimeError messages are deliberately sanitized.
        # Keep its phase failure visible without echoing subprocess SQL or secrets.
        for line in result.stderr.decode(errors="replace").splitlines():
            if line.startswith("RuntimeError: "):
                print(line, file=sys.stderr)
    require(result.returncode == 0, "Synthetic backup command failed: " + Path(command[0]).name)
    return result.stdout


def verify_sequence_acl_manifest(query) -> None:
    """Exercise NULL/default and explicit sequence ACLs on the actual PG16."""
    sequence = "public.crm_ci_sequence_acl_probe"
    owner = "crm_homologation_owner"
    grantee = "crm_homologation_app"
    require(re.fullmatch(r"16\d{4}", query("SHOW server_version_num")) is not None,
            "Sequence ACL regression requires the disposable PostgreSQL 16.")
    require(query("SELECT to_regclass('" + sequence + "') IS NULL") == "t",
            "Sequence ACL fixture must be new.")
    before = security_manifest(query)
    try:
        query("CREATE SEQUENCE " + sequence)
        require(query("SELECT relacl IS NULL FROM pg_class WHERE oid='" + sequence + "'::regclass") == "t",
                "Sequence fixture must begin with a NULL ACL.")
        implicit = security_manifest(query)
        expected_acl = [[owner, owner, privilege, False] for privilege in ("SELECT", "UPDATE", "USAGE")]
        rows = [item for item in implicit["relations"] if item["name"] == sequence]
        require(rows == [{"name": sequence, "kind": "S", "owner": owner,
                          "rls": False, "force_rls": False, "acl": expected_acl}],
                "Sequence manifest lost the owner's SELECT, UPDATE or USAGE privilege.")

        # Read-only counterproof of the old fallback. pg_class's relkind is 'S',
        # but acldefault('S', ...) describes a foreign server, not a sequence.
        legacy_privileges = json.loads(query(
            "SELECT jsonb_agg(item.privilege_type ORDER BY item.privilege_type) "
            "FROM pg_class c CROSS JOIN LATERAL aclexplode(acldefault('S',c.relowner)) item "
            "WHERE c.oid='" + sequence + "'::regclass"))
        require(legacy_privileges == ["USAGE"]
                and legacy_privileges != [item[2] for item in rows[0]["acl"]],
                "Old foreign-server fallback was not distinguished from sequence privileges.")

        query("REVOKE ALL ON SEQUENCE " + sequence + " FROM PUBLIC")
        require(query("SELECT relacl IS NOT NULL FROM pg_class WHERE oid='" + sequence + "'::regclass") == "t",
                "Public revoke must materialize the equivalent sequence ACL.")
        require(security_manifest(query) == implicit,
                "Equivalent NULL and explicit sequence ACLs changed the security manifest.")

        query("GRANT USAGE ON SEQUENCE " + sequence + " TO " + grantee)
        expanded_acl = sorted(expected_acl + [[owner, grantee, "USAGE", False]])
        expanded_relations = [{**item, "acl": expanded_acl} if item["name"] == sequence else item
                              for item in implicit["relations"]]
        changed = security_manifest(query)
        require(changed != implicit and changed == {**implicit, "relations": expanded_relations},
                "An additional sequence grant must change exactly its manifest ACL.")
    finally:
        # Remove the fixture before any closed-profile detection or backup.
        query("DROP SEQUENCE IF EXISTS " + sequence)
    require(security_manifest(query) == before, "Sequence ACL probe altered the original security profile.")
    print("CRM_BACKUP_SEQUENCE_ACL_PASS null_default=1 explicit_equivalent=1 extra_grant_detected=1 "
          "legacy_type_rejected=1 fixture_removed=1")


def main() -> None:
    require(os.environ.get("GITHUB_ACTIONS") == "true"
            and os.environ.get("GITHUB_REPOSITORY") == "rafhael100/imagine-crm"
            and os.environ.get("GITHUB_EVENT_NAME") == "pull_request"
            and os.environ.get("CRM_PRIVATE_PROJECT") == "imagine-crm-journey-ci",
            "Disposable public-repository PR backup job required.")
    envfile = Path(os.environ["CRM_PRIVATE_ENV_FILE"]).resolve()
    directory = Path(sys.argv[1]).resolve()
    require(not directory.is_relative_to(ROOT) and not envfile.is_relative_to(ROOT),
            "Runner-only protected files must remain outside the checkout.")
    compose = ["docker", "compose", "--env-file", str(envfile), "-p", "imagine-crm-journey-ci",
               "-f", str(ROOT / "infra/homologation/compose.yaml")]
    source = compose + ["exec", "-T", "postgres"]

    def sql(statement: str) -> str:
        return run(source + ["psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-U",
                   "crm_homologation_owner", "-d", DATABASE], statement.encode()).decode().strip()

    verify_sequence_acl_manifest(sql)
    base_tables = re.findall(r"CREATE TABLE\s+(crm_[a-z0-9_]+)\s*\(",
                             (ROOT / "infra/homologation/schema.sql").read_text())
    sql(f"""
      INSERT INTO crm_global_identities(id) VALUES ('{ACTOR_A}'),('{ACTOR_B}');
      INSERT INTO crm_organizations(id) VALUES ('{A}'),('{B}');
      INSERT INTO crm_private_accounts(identity_id,organization_id,label,created_at) VALUES
        ('{ACTOR_A}','{A}','EMPRESA_TESTE_BACKUP_A',floor(extract(epoch FROM now()))::bigint),
        ('{ACTOR_B}','{B}','EMPRESA_TESTE_BACKUP_B',floor(extract(epoch FROM now()))::bigint);
      INSERT INTO crm_private_trials SELECT id,floor(extract(epoch FROM now()))::bigint,
        floor(extract(epoch FROM now()))::bigint+1209600 FROM crm_organizations;
      INSERT INTO crm_private_preferences(organization_id,automation_rules) VALUES
        ('{A}','{{"version":1}}'),('{B}','{{"version":1}}');
      INSERT INTO crm_private_funnels(id,organization_id,title,created_at) VALUES
        ('{FUNNEL_A}','{A}','FUNIL_TESTE_BACKUP_A',1800000000),
        ('{FUNNEL_B}','{B}','FUNIL_TESTE_BACKUP_B',1800000000);
      INSERT INTO crm_private_leads(id,organization_id,funnel_id,title,created_at) VALUES
        ('{LEAD_A}','{A}','{FUNNEL_A}','LEAD_TESTE_BACKUP_A',1800000000),
        ('{LEAD_B}','{B}','{FUNNEL_B}','LEAD_TESTE_BACKUP_B',1800000000);
      INSERT INTO crm_private_events(id,organization_id,actor_id,object_id,action,occurred_at) VALUES
        ('{EVENT_A}','{A}','{ACTOR_A}','{LEAD_A}','lead.created',1800000000),
        ('{EVENT_B}','{B}','{ACTOR_B}','{LEAD_B}','lead.created',1800000000);
      INSERT INTO crm_private_automation_runs(id,organization_id,actor_id,authority_session_id,template,
        settings_version,lead_id,lead_version,execution_key,command,state,due_at,created_at) VALUES
        (gen_random_uuid(),'{A}','{ACTOR_A}',gen_random_uuid(),'followup_qualified',1,'{LEAD_A}',1,
          repeat('a',64),'{{}}','completed',1800000000,1800000000),
        (gen_random_uuid(),'{B}','{ACTOR_B}',gen_random_uuid(),'followup_qualified',1,'{LEAD_B}',1,
          repeat('b',64),'{{}}','pending',1800000000,1800000000);
    """)

    def encrypted_backup(expected_profile: str) -> dict:
        before = set(directory.glob("*.json")) if directory.exists() else set()
        output = run([sys.executable, str(ROOT / "scripts/ops/backup-private-crm.py"), str(directory)])
        print(output.decode().strip())
        created = set(directory.glob("*.json")) - before
        require(len(created) == 1, "One verified encrypted archive manifest required.")
        manifest = json.loads(created.pop().read_text())
        require(manifest["database_profile"] == expected_profile and manifest["restore_verified"]
                and manifest["tamper_rejected"] and manifest["object_owners_preserved"]
                and manifest["consistent_source_snapshot"] is True
                and manifest["snapshot_protocol"] == "postgresql-export-v1"
                and manifest["restore_network"] == "none" and not manifest["plaintext_dump_on_disk"],
                "Encrypted backup did not verify its complete profile.")
        require((directory / manifest["encrypted_archive"]).is_file(), "Encrypted archive missing.")
        return manifest

    base_manifest = encrypted_backup("synthetic-private-base-v1")
    require(len(base_manifest["table_counts"]) == len(base_tables), "Incomplete base counts.")
    for path in ("install-candidate.sql", "outbound-candidate.sql"):
        sql((ROOT / "infra/homologation/s2s" / path).read_text())
    for company, fingerprint, tenant in ((A, "a", "SYNTHETIC_BACKUP_A"), (B, "b", "SYNTHETIC_BACKUP_B")):
        sql(f"""
          INSERT INTO crm_auto_service.grants VALUES ('{company}',repeat('{fingerprint}',64),
            'portal-automation-admin','automation.state.read',1,true,
            floor(extract(epoch FROM now()))::bigint+7200);
          INSERT INTO crm_auto_service.outbound_grants VALUES ('{company}','{tenant}',
            'imagine-ai-agents','lead.ingest',true,1,floor(extract(epoch FROM now()))::bigint+7200);
        """)
    for company, event, fingerprint in ((A, EVENT_A, "a"), (B, EVENT_B, "b")):
        sql(f"""
          SET ROLE crm_auto_sender;
          SELECT crm_auto_service.stage_outbound('{company}','{event}');
          RESET ROLE;
          SET ROLE crm_auto_transport;
          SELECT crm_auto_service.read_state('{company}',repeat('{fingerprint}',64),'portal-automation-admin',
            'automation.state.read',gen_random_uuid(),repeat('{fingerprint}',64),repeat('c',64),
            floor(extract(epoch FROM now()))::bigint);
          RESET ROLE;
        """)
    sql(f"SET ROLE crm_auto_sender; SELECT crm_auto_service.ack_outbound('{A}','{EVENT_A}',repeat('d',64)); RESET ROLE;")
    s2s_manifest = encrypted_backup("synthetic-private-s2s-v1")
    require(all(s2s_manifest["table_counts"][table] == 2 for table in S2S_TABLES),
            "S2S grants/nonces/audit/outbox not completely covered by the manifest.")
    require(s2s_manifest["s2s_function_owners_verified"] == 4
            and s2s_manifest["s2s_receipt_reconciliation"] is True
            and s2s_manifest["scope_roles_verified"] == ["crm_auto_reader_owner", "crm_homologation_app"],
            "Current P22 fixture must verify all four restricted functions, including receipt_candidate.")

    # Unknown or partial profiles cannot receive a verified-backup marker.
    for setup, cleanup in (
        ("CREATE SCHEMA backup_unreviewed", "DROP SCHEMA backup_unreviewed"),
        ("ALTER TABLE crm_auto_service.outbox RENAME TO outbox_unknown",
         "ALTER TABLE crm_auto_service.outbox_unknown RENAME TO outbox"),
    ):
        sql(setup)
        try:
            try:
                detect_profile(sql, base_tables)
            except RuntimeError:
                pass
            else:
                raise AssertionError("Unreviewed source profile was accepted.")
        finally:
            sql(cleanup)
    profile, security = detect_profile(sql, base_tables)
    counts = table_counts(sql, profile)
    dump = run(source + ["pg_dump", "-U", "crm_homologation_owner", "-d", DATABASE, "-Fc"])

    # Reproduce both legacy failures, each in its own disposable cluster.
    with isolated_restore_cluster({"roles": BASE_ROLES}, run) as (_, command):
        missing = subprocess.run(command + ["pg_restore", "--no-owner", "--exit-on-error", "-U",
                        "crm_homologation_restore", "-d", DATABASE], input=dump, capture_output=True)
        require(missing.returncode != 0 and re.search(rb'role "crm_auto_[a-z_]+" does not exist', missing.stderr),
                "Missing-role legacy restore failure not reproduced.")
    print("REPRODUCED legacy base-only role list cannot restore the S2S archive in another cluster")
    with isolated_restore_cluster(profile, run) as (query, command):
        run(command + ["pg_restore", "--no-owner", "--exit-on-error", "-U", "crm_homologation_restore", "-d", DATABASE], dump)
        require(query("SELECT bool_and(pg_get_userbyid(p.proowner)='crm_homologation_restore' AND r.rolsuper) "
                      "FROM pg_proc p JOIN pg_roles r ON r.oid=p.proowner JOIN pg_namespace n ON n.oid=p.pronamespace "
                      "WHERE n.nspname='crm_auto_service' AND p.prosecdef") == "t",
                "Legacy SECURITY DEFINER owner failure not reproduced.")
        try:
            verify_restored_security(query, profile, security, base_tables)
        except RuntimeError:
            pass
        else:
            raise AssertionError("Legacy privileged-owner restore was accepted.")
    print("REPRODUCED --no-owner transfers SECURITY DEFINER functions to the restore superuser; verifier rejects")

    with isolated_restore_cluster(profile, run) as (query, command):
        run(command + ["pg_restore", "--exit-on-error", "-U", "crm_homologation_restore", "-d", DATABASE], dump)
        verify_restored_security(query, profile, security, base_tables)
        require(table_counts(query, profile) == counts, "Separate-cluster table counts differ.")
        require(run(["docker", "inspect", "--format", "{{.HostConfig.NetworkMode}}", command[-1]]).strip() == b"none",
                "Restore cluster acquired a network.")
        for role, tables in (("crm_homologation_app", BASE_TENANT_TABLES), ("crm_auto_reader_owner", S2S_TABLES)):
            for tenant in ("", A, B):
                require(scoped_counts(query, tenant, role=role, tables=tables)
                        == scoped_counts(sql, tenant, role=role, tables=tables), "Restored RLS scope differs.")

        def result(role: str, expression: str) -> dict:
            output = query("BEGIN; SET ROLE " + role + "; SELECT 'RESULT|' || (" + expression + ")::text; ROLLBACK;")
            lines = [line[7:] for line in output.splitlines() if line.startswith("RESULT|")]
            require(len(lines) == 1, "Function result missing.")
            return json.loads(lines[0])

        read = (f"crm_auto_service.read_state('{A}',repeat('a',64),'portal-automation-admin','automation.state.read',"
                "'33333333-3333-4333-8333-333333333333',repeat('e',64),repeat('f',64),"
                "floor(extract(epoch FROM now()))::bigint)")
        state = result("crm_auto_transport", read)
        require(state["decision"] == "READ_OK" and state["body"]["company_id"] == A
                and state["body"]["counts"]["completed"] == 1
                and state["body"]["counts"]["pending"] == 0, "Restored read_state functional scope failed.")
        query(f"UPDATE crm_auto_service.grants SET active=false WHERE company_id='{A}'")
        require(result("crm_auto_transport", read)["decision"] == "DENIED", "Restored revoked grant accepted.")
        query(f"UPDATE crm_auto_service.grants SET active=true WHERE company_id='{A}'")
        receipt = result("crm_auto_sender", f"crm_auto_service.stage_outbound('{A}','{EVENT_A}')")
        require(receipt["state"] == "accepted" and receipt["ack_hash"] == "d" * 64,
                "Restored durable accepted receipt differs.")
        require(profile["receipt_reconciliation"], "Current P22 receipt function missing after restore.")
        pending = result("crm_auto_sender", f"crm_auto_service.receipt_candidate('{B}','{EVENT_B}')")
        require(pending["state"] == "pending" and pending["ack_hash"] is None,
                "Restored receipt candidate differs.")
        acknowledged = query("BEGIN; SET ROLE crm_auto_sender; "
            f"SELECT crm_auto_service.ack_outbound('{B}','{EVENT_B}',repeat('e',64)); "
            f"SELECT 'RESULT|' || crm_auto_service.receipt_candidate('{B}','{EVENT_B}')::text; ROLLBACK;")
        receipts = [json.loads(line[7:]) for line in acknowledged.splitlines() if line.startswith("RESULT|")]
        require(len(receipts) == 1 and receipts[0]["state"] == "accepted"
                and receipts[0]["ack_hash"] == "e" * 64, "Restored ack_outbound did not confirm the pending event.")
        after_rollback = result("crm_auto_sender", f"crm_auto_service.receipt_candidate('{B}','{EVENT_B}')")
        require(after_rollback == pending, "Acknowledgement probe did not roll back its complete receipt state.")
        for role, table in (("crm_auto_transport", "crm_auto_service.grants"),
                            ("crm_auto_sender", "crm_auto_service.outbox"),
                            ("crm_homologation_app", "crm_auto_service.outbox")):
            denied = subprocess.run(command + ["psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-U",
                       "crm_homologation_restore", "-d", DATABASE, "-c", f"SET ROLE {role}; SELECT * FROM {table}"],
                       capture_output=True)
            require(denied.returncode != 0 and b"permission denied" in denied.stderr,
                    "Restored transport/application acquired raw service-table access.")
        require(table_counts(query, profile) == counts, "Rolled-back function probes altered restored records.")
    require(table_counts(sql, profile) == counts, "Restore tests altered the source database.")
    print("CRM_BACKUP_BASE_S2S_SEPARATE_CLUSTER_PASS encrypted=1 tamper_rejected=1 "
          "owners_preserved=1 force_rls=1 functions_executed=1 source_preserved=1 operational_apply=0")


if __name__ == "__main__":
    main()
