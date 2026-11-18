#!/usr/bin/env python3
"""Exercise the read-only inspector against an owned, disposable PostgreSQL 16.

This runner creates synthetic CI fixtures. The inspected operator module never
creates them, and this runner refuses execution outside its actual PR event.
"""
from __future__ import annotations

import hashlib
import importlib.util
import json
import os
from pathlib import Path
import re
import secrets
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
BASE = "dc601be113b213dfd35fae61d00ff710b4f286e1"
BRANCH = "feat/crm-owner-private-inspection-2026-11-18"
BASE_BRANCH = "docs/crm-owner-drafts-verified-status-2026-11-17"
FILES = frozenset((
    ".github/workflows/ci-crm-owner-private-inspection.yml",
    "scripts/ops/inspect-private-crm-owner-drafts.py",
    "scripts/ci/test_private_crm_owner_inspection.py",
    "scripts/ci/rehearse_private_crm_owner_inspection.py",
    "docs/operations/CRM-OWNER-PRIVATE-INSPECTION.md",
))
DATABASE = "imagine_crm_private_homologation"
OWNER = "crm_homologation_owner"
SECRET = "SYNTHETIC_INSPECTION_SECRET_MUST_NOT_LEAVE_DATABASE"
CHECKS = 0


def require(condition, code):
    global CHECKS
    CHECKS += 1
    if not condition:
        raise RuntimeError(code)


def command(argv, payload=None, *, timeout=30, check=True):
    environment = {"PATH": "/usr/local/bin:/usr/bin:/bin", "LC_ALL": "C",
                   "GIT_CONFIG_NOSYSTEM": "1", "GIT_CONFIG_SYSTEM": "/dev/null",
                   "GIT_CONFIG_GLOBAL": "/dev/null", "GIT_OPTIONAL_LOCKS": "0",
                   "GIT_TERMINAL_PROMPT": "0", "PYTHONDONTWRITEBYTECODE": "1"}
    result = subprocess.run(argv, cwd=ROOT, input=payload, stdout=subprocess.PIPE,
                            stderr=subprocess.PIPE, env=environment, timeout=timeout)
    require(len(result.stdout) + len(result.stderr) <= 1048576, "CI_COMMAND_OUTPUT_LIMIT")
    if check:
        require(result.returncode == 0, "CI_COMMAND_FAILED")
    return result


def git(*args):
    return command(["git", "--no-optional-locks", *args]).stdout.decode().strip()


def preflight():
    require(os.environ.get("GITHUB_ACTIONS") == "true"
            and os.environ.get("GITHUB_REPOSITORY") == "rafhael100/imagine-crm"
            and os.environ.get("GITHUB_EVENT_NAME") == "pull_request"
            and os.environ.get("RUNNER_ENVIRONMENT") == "github-hosted",
            "ACTUAL_DISPOSABLE_PR_JOB_REQUIRED")
    event = json.loads(Path(os.environ["GITHUB_EVENT_PATH"]).read_text())
    pr = event["pull_request"]
    require(pr["head"]["repo"]["full_name"] == "rafhael100/imagine-crm"
            and pr["base"]["repo"]["full_name"] == "rafhael100/imagine-crm"
            and pr["head"]["ref"] == BRANCH and pr["base"]["ref"] == BASE_BRANCH
            and pr["base"]["sha"] == BASE and pr["head"]["sha"] == git("rev-parse", "HEAD"),
            "PR_SOURCE_BINDING_MISMATCH")
    require(not git("diff", "--name-only", "HEAD"), "TRACKED_WORKTREE_CHANGED")
    require(command(["git", "merge-base", "--is-ancestor", BASE, "HEAD"], check=False).returncode == 0,
            "BASE_NOT_ANCESTOR")
    changes = git("diff", "--name-status", "--no-renames", BASE, "HEAD").splitlines()
    require(set(changes) == {"A\t" + path for path in FILES}, "UNREVIEWED_SOURCE_DELTA")
    require(not str(ROOT).startswith("/srv/"), "PERSISTENT_TARGET_FORBIDDEN")
    print("CRM_OWNER_PRIVATE_INSPECTION_PREFLIGHT_PASS", flush=True)
    return pr["head"]["sha"]


def load_inspector():
    spec = importlib.util.spec_from_file_location("owner_private_inspection_ci",
                                                 ROOT / "scripts/ops/inspect-private-crm-owner-drafts.py")
    assert spec and spec.loader
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


def main():
    head = preflight()
    inspector_module = load_inspector()
    nonce = secrets.token_hex(12)
    project = "crm-owner-inspect-ci-" + nonce
    container = None
    docker = ["docker", "--host", "unix:///var/run/docker.sock"]
    with tempfile.TemporaryDirectory(prefix="crm-owner-inspection-ci-") as directory:
        root = Path(directory)
        target = inspector_module.Target(ROOT, root / "state", root / "nginx-crm",
                                         root / "nginx-shared", root / "nginx-enabled",
                                         project=project, scope="ci-fixture")
        inspector = inspector_module.Inspector(target)
        try:
            result = command(docker + ["run", "--detach", "--name", project,
                "--network", "none", "--tmpfs", "/var/lib/postgresql/data:rw",
                "--label", "com.docker.compose.project=" + project,
                "--label", "com.docker.compose.service=postgres",
                "--label", "imagine.inspection.ci-owner=" + nonce,
                "--env", "POSTGRES_USER=" + OWNER, "--env", "POSTGRES_DB=" + DATABASE,
                "--env", "POSTGRES_HOST_AUTH_METHOD=trust", "postgres:16"], timeout=120)
            container = result.stdout.decode().strip()
            require(re.fullmatch(r"[a-f0-9]{64}", container) is not None, "FIXTURE_CONTAINER_ID_INVALID")
            client = command(docker + ["exec", container, "env", "-i",
                "PATH=/usr/local/bin:/usr/bin:/bin", "LC_ALL=C", "PGPASSFILE=/dev/null",
                "PGSERVICEFILE=/dev/null", "psql", "--version"], check=False, timeout=5)
            require(client.returncode == 0, "POSTGRES_CLIENT_UNAVAILABLE_IN_FIXED_PATH")
            require(client.stdout.startswith(b"psql (PostgreSQL) 16."), "POSTGRES_CLIENT_16_REQUIRED")
            print("CRM_OWNER_PRIVATE_INSPECTION_PG16_CLIENT_PATH_PASS", flush=True)
            ready = False
            for _ in range(80):
                # The image's temporary initdb server accepts only Unix sockets.
                # TCP readiness waits for the final server after database creation.
                result = command(docker + ["exec", container, "pg_isready", "-h", "127.0.0.1",
                    "-p", "5432", "-U", OWNER, "-d", DATABASE],
                                 check=False, timeout=5)
                if result.returncode == 0:
                    ready = True
                    break
                time.sleep(0.25)
            require(ready, "POSTGRES_NOT_READY")

            def psql(statement, *, user=OWNER, database=DATABASE, check=True):
                return command(docker + ["exec", "-i", container, "env", "-i",
                    "PATH=/usr/local/bin:/usr/bin:/bin", "LC_ALL=C", "PGPASSFILE=/dev/null",
                    "PGSERVICEFILE=/dev/null", "psql", "-X", "--no-password", "-qAt",
                    "-h", "/var/run/postgresql", "-U", user, "-d", database,
                    "-v", "ON_ERROR_STOP=1", "-f", "-"], statement.encode(), check=check)

            version_result = psql("SHOW server_version_num;", check=False)
            require(version_result.returncode == 0, "POSTGRES_VERSION_QUERY_FAILED_EXIT_" + str(version_result.returncode))
            version = version_result.stdout.decode().strip()
            require(version.isdigit() and int(version) // 10000 == 16, "POSTGRES_16_REQUIRED")
            psql((ROOT / "infra/homologation/schema.sql").read_text())
            roles_environment = ["POSTGRES_DB=" + DATABASE, "POSTGRES_USER=" + OWNER,
                "CRM_PRIVATE_APP_PASSWORD=synthetic_ci_app", "CRM_PRIVATE_IAM_PASSWORD=synthetic_ci_iam",
                "CRM_PRIVATE_ENROLLMENT_PASSWORD=synthetic_ci_enroll", "CRM_PRIVATE_OPERATOR_PASSWORD=synthetic_ci_operator"]
            command(docker + ["exec", "-i", container, "env", "-i", "PATH=/usr/local/bin:/usr/bin:/bin",
                "LC_ALL=C", "PGPASSFILE=/dev/null", "PGSERVICEFILE=/dev/null", *roles_environment, "sh", "-s"],
                (ROOT / "infra/homologation/20-runtime-roles.sh").read_bytes())
            psql("INSERT INTO crm_global_identities(id) VALUES ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');"
                 "INSERT INTO crm_organizations(id) VALUES ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');"
                 "INSERT INTO crm_auth_credentials(identity_id,login_key,password_hash,status) VALUES"
                 "('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',repeat('a',64),'" + SECRET + "','active');")
            fixture_admin = OWNER

            def fingerprint():
                # Only fixture-owned synthetic rows are read by this TEST helper.
                # The inspected module never performs these row queries.
                statements = []
                for relation in ("crm_global_identities", "crm_organizations", "crm_auth_credentials"):
                    statements.append("SELECT md5(coalesce(jsonb_agg(to_jsonb(t) ORDER BY to_jsonb(t)::text),'[]'::jsonb)::text) FROM public." + relation + " t;")
                statements.append("SELECT md5(coalesce(jsonb_agg(jsonb_build_array(n.nspname,c.relname,c.relkind,c.relowner,c.relrowsecurity,c.relforcerowsecurity) ORDER BY n.nspname,c.relname),'[]'::jsonb)::text) FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace WHERE n.nspname IN ('public','crm_auto_service');")
                return hashlib.sha256(psql("\n".join(statements), user=fixture_admin).stdout).hexdigest()

            def observe(expected_inventory):
                before = fingerprint()
                observation = inspector.database(container)
                require(observation["status"] == "OBSERVED", "DATABASE_NOT_OBSERVED")
                data = observation["data"]
                require(data["relation_inventory"] == expected_inventory, "WRONG_INVENTORY_OBSERVATION")
                require(data["transaction_read_only"] is True and data["transaction_repeatable_read"] is True,
                        "CATALOG_TRANSACTION_NOT_READ_ONLY")
                require(data["profile_verified"] is False, "INVENTORY_MUST_NOT_APPROVE_PROFILE")
                require(SECRET not in json.dumps(observation), "SYNTHETIC_SECRET_DISCLOSED")
                require(fingerprint() == before, "INSPECTION_CHANGED_FIXTURE")
                return data

            data = observe("BASE_25")
            require(len(data["relations"]) == 25 and len(data["roles"]) == 5, "BASE_CATALOG_INCOMPLETE")
            original_owner = next(role for role in data["roles"] if role["name"] == OWNER)
            psql((ROOT / "infra/homologation/s2s/owner-drafts.sql").read_text())
            data = observe("OWNER_27")
            require(len(data["relations"]) == 27 and data["owner_lock_marker"] is True, "OWNER_CATALOG_INCOMPLETE")
            require(all(item["rls"] and item["force_rls"] for item in data["relations"]
                        if item["name"] in inspector_module.OWNER_TABLES), "OWNER_RLS_NOT_OBSERVED")
            print("CRM_OWNER_PRIVATE_INSPECTION_PG16_BASE_OWNER_PASS", flush=True)

            before = fingerprint()
            attempted_write = inspector_module.SQL.rsplit("ROLLBACK;", 1)[0] + "CREATE TABLE public.inspector_forbidden_write(id integer);ROLLBACK;"
            write_result = psql(attempted_write, check=False)
            require(write_result.returncode != 0 and b"read-only transaction" in write_result.stderr,
                    "COLLECTOR_SQL_DID_NOT_DENY_WRITE")
            require(fingerprint() == before, "READ_ONLY_COUNTERPROOF_CHANGED_FIXTURE")

            psql('CREATE SCHEMA "' + SECRET + '"; CREATE TABLE "' + SECRET + '".hidden(id integer);')
            data = observe("UNRECOGNIZED")
            require(data["unknown_schema_count"] == 1 and data["unknown_relation_count"] == 1,
                    "UNKNOWN_OBJECT_NOT_COUNTED")
            psql('DROP SCHEMA "' + SECRET + '" CASCADE;')
            psql("DELETE FROM public.crm_private_installation;")
            data = observe("OWNER_27")
            require(data["installation"] == {"structure_matches": True, "mode": "NOT_READ"},
                    "MARKER_VALUE_MUST_REMAIN_UNOBSERVED")
            psql("INSERT INTO public.crm_private_installation VALUES (1,'synthetic-v1');")
            psql("ALTER TABLE public.crm_private_installation DROP CONSTRAINT crm_private_installation_mode_check;"
                 "UPDATE public.crm_private_installation SET mode='" + SECRET + "';")
            data = observe("OWNER_27")
            require(data["installation"] == {"structure_matches": True, "mode": "NOT_READ"},
                    "CHANGED_MARKER_VALUE_MUST_REMAIN_UNOBSERVED")
            require(psql("SELECT mode FROM public.crm_private_installation;").stdout.decode().strip() == SECRET,
                    "INSPECTOR_CHANGED_MARKER_VALUE")
            psql("UPDATE public.crm_private_installation SET mode='synthetic-v1';"
                 "ALTER TABLE public.crm_private_installation ADD CONSTRAINT crm_private_installation_mode_check CHECK(mode='synthetic-v1');")

            # A malicious RLS expression must never be called by the marker probe.
            psql("CREATE ROLE ci_inspection_admin LOGIN SUPERUSER;"
                 "CREATE FUNCTION public.inspection_policy_canary() RETURNS boolean LANGUAGE plpgsql AS $$BEGIN RAISE EXCEPTION 'INSPECTION_POLICY_CALLED';END$$;"
                 "ALTER TABLE public.crm_private_installation ENABLE ROW LEVEL SECURITY;"
                 "ALTER TABLE public.crm_private_installation FORCE ROW LEVEL SECURITY;"
                 "CREATE POLICY inspection_canary ON public.crm_private_installation USING (public.inspection_policy_canary());"
                 "ALTER ROLE crm_homologation_owner NOSUPERUSER NOBYPASSRLS;")
            fixture_admin = "ci_inspection_admin"
            called = psql("SELECT * FROM public.crm_private_installation;", check=False)
            require(called.returncode != 0 and b"INSPECTION_POLICY_CALLED" in called.stderr,
                    "POLICY_COUNTERPROOF_NOT_REACHABLE")
            data = observe("OWNER_27")
            require(data["installation"] == {"structure_matches": False, "mode": "NOT_READ"},
                    "INSPECTOR_READ_INSTALLATION_ROWS_OR_POLICY")
            owner_flags = ("SUPERUSER" if original_owner["superuser"] else "NOSUPERUSER") + " " + (
                "BYPASSRLS" if original_owner["bypass_rls"] else "NOBYPASSRLS")
            psql("ALTER ROLE crm_homologation_owner " + owner_flags + ";"
                 "DROP POLICY inspection_canary ON public.crm_private_installation;"
                 "ALTER TABLE public.crm_private_installation DISABLE ROW LEVEL SECURITY;"
                 "ALTER TABLE public.crm_private_installation NO FORCE ROW LEVEL SECURITY;"
                 "DROP FUNCTION public.inspection_policy_canary();", user="ci_inspection_admin")
            print("CRM_OWNER_PRIVATE_INSPECTION_PG16_NEGATIVES_PASS", flush=True)

            psql("ALTER DATABASE " + DATABASE + " RENAME TO ci_unavailable_database;", database="postgres")
            started = time.monotonic()
            unavailable = inspector.database(container)
            require(unavailable["status"] == "UNKNOWN" and time.monotonic() - started < 8,
                    "UNAVAILABLE_DATABASE_NOT_BOUNDED_UNKNOWN")
            psql("ALTER DATABASE ci_unavailable_database RENAME TO " + DATABASE + ";", database="postgres")
            data = observe("OWNER_27")
            require(data["installation"] == {"structure_matches": True, "mode": "NOT_READ"},
                    "POST_FAILURE_OBSERVATION_NOT_RECOVERED")

            before = fingerprint()
            report = inspector.collect()
            require(report["scope"] == "ci-fixture" and report["deploy_approved"] is False
                    and report["mutations_executed"] is False and report["consistent_snapshot_verified"] is False,
                    "FIXTURE_CANNOT_CLAIM_OPERATIONAL_AUTHORITY")
            require(report["sections"]["containers"]["status"] == "OBSERVED"
                    and report["sections"]["database"]["status"] == "OBSERVED", "FULL_OBSERVATION_INCOMPLETE")
            require(report["sections"]["containers"]["data"]["image_provenance_verified"] is False,
                    "IMAGE_TAG_CANNOT_PROVE_PROVENANCE")
            require(SECRET not in json.dumps(report) and fingerprint() == before, "FULL_OBSERVATION_LEAKED_OR_MUTATED")
            require(not (root / "state").exists(), "INSPECTION_CREATED_TARGET_PATH")
            print("CRM_OWNER_PRIVATE_INSPECTION_REAL_DOCKER_READ_ONLY_PASS", flush=True)
        finally:
            if container:
                ownership = command(docker + ["inspect", "--type", "container", "--format",
                    '{{index .Config.Labels "imagine.inspection.ci-owner"}}', container]).stdout.decode().strip()
                require(ownership == nonce, "CLEANUP_OWNERSHIP_MISMATCH")
                command(docker + ["rm", "--force", container])
    print(json.dumps({"check": "CRM_OWNER_PRIVATE_INSPECTION_CI_PASS_NO_GO", "head": head,
        "checks": CHECKS, "postgres_major": 16, "base_tables": 25, "owner_tables": 27,
        "inspector_mutations": False, "read_only_write_denied": True, "rls_policy_not_executed": True,
        "unavailable_database_unknown": True, "protected_contents_not_exported": True,
        "live_vps_inspected": False, "deploy_approved": False, "operational": False,
        "artifacts": 0, "owned_fixture_removed": True}, sort_keys=True))


if __name__ == "__main__":
    main()
