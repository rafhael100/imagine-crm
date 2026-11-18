#!/usr/bin/env python3
"""Real concurrent writer, exported-snapshot restore and exporter cleanup in CI."""
from __future__ import annotations

from concurrent.futures import ThreadPoolExecutor
from contextlib import contextmanager
import json
import os
from pathlib import Path
import re
import secrets
import selectors
import signal
import subprocess
import sys
import time

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "scripts/ops"))
from private_crm_backup_contract import (  # noqa: E402
    BASE_TENANT_TABLES, DATABASE, S2S_TABLES, detect_profile, isolated_restore_cluster,
    require, scoped_counts, table_counts, verify_restored_security,
)
from private_crm_backup_snapshot import exported_snapshot  # noqa: E402

A = "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
B = "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb"
LEAD_A = "ffffffff-ffff-4fff-8fff-fffffffffff1"
FUNNEL_A = "eeeeeeee-eeee-4eee-8eee-eeeeeeeeeee1"
CONCURRENT_LEAD = "33333333-3333-4333-8333-333333333334"


def run(command, content=None, environment=None):
    result = subprocess.run(command, input=content, capture_output=True, env=environment, timeout=120)
    require(result.returncode == 0, "Synthetic snapshot command failed; raw output suppressed.")
    return result.stdout


def main() -> None:
    require(os.environ.get("GITHUB_ACTIONS") == "true"
            and os.environ.get("GITHUB_REPOSITORY") == "rafhael100/imagine-crm"
            and os.environ.get("GITHUB_EVENT_NAME") == "pull_request"
            and os.environ.get("CRM_PRIVATE_PROJECT") == "imagine-crm-journey-ci",
            "Disposable public-repository PR snapshot job required.")
    envfile = Path(os.environ["CRM_PRIVATE_ENV_FILE"]).resolve()
    require(not envfile.is_relative_to(ROOT), "Runner-owned private environment required.")
    source = ["docker", "compose", "--env-file", str(envfile), "-p", "imagine-crm-journey-ci",
              "-f", str(ROOT / "infra/homologation/compose.yaml"), "exec", "-T", "postgres"]
    psql = source + ["psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-U",
                     "crm_homologation_owner", "-d", DATABASE]

    def sql(statement: str) -> str:
        return run(psql, statement.encode()).decode().strip()

    def ended(pid: int) -> None:
        require(sql(f"SELECT count(*) FROM pg_stat_activity WHERE pid={pid}") == "0",
                "Private snapshot exporter session leaked.")

    @contextmanager
    def witness(*, lock_table: bool):
        marker = "crm-backup-ci-witness-" + secrets.token_hex(8)
        command = source + ["env", "PGAPPNAME=" + marker, "PGCONNECT_TIMEOUT=3",
            "psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-U", "crm_homologation_owner", "-d", DATABASE]
        process = subprocess.Popen(command, stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                                   stderr=subprocess.PIPE, start_new_session=True)
        witness_pid = None
        try:
            setup = "SET statement_timeout='5s'; SET idle_in_transaction_session_timeout='30s'; BEGIN; "
            if lock_table:
                setup += "LOCK TABLE public.crm_private_leads IN ACCESS EXCLUSIVE MODE; "
            setup += "SELECT pg_backend_pid();\n"
            process.stdin.write(setup.encode())
            process.stdin.flush()
            data = b""
            end = time.monotonic() + 8
            with selectors.DefaultSelector() as selector:
                selector.register(process.stdout, selectors.EVENT_READ)
                while b"\n" not in data:
                    wait = end - time.monotonic()
                    require(wait > 0 and selector.select(wait), "Synthetic witness did not become ready.")
                    chunk = os.read(process.stdout.fileno(), 64 - len(data))
                    require(chunk and len(data) + len(chunk) < 64, "Synthetic witness identity invalid.")
                    data += chunk
            require(data.strip().isdigit(), "Synthetic witness PID invalid.")
            witness_pid = int(data.strip())
            yield witness_pid, marker
        finally:
            if process.poll() is None:
                try:
                    process.stdin.write(b"ROLLBACK;\n\\q\n")
                    process.stdin.flush()
                except (BrokenPipeError, OSError):
                    pass
            try:
                process.communicate(timeout=5)
            except subprocess.TimeoutExpired:
                try:
                    os.killpg(process.pid, signal.SIGKILL)
                except ProcessLookupError:
                    pass
                process.communicate(timeout=3)
            if witness_pid is not None:
                sql(f"SELECT pg_terminate_backend(pid,3000) FROM pg_stat_activity WHERE pid={witness_pid} "
                    f"AND application_name='{marker}' AND datname='{DATABASE}' AND usename='crm_homologation_owner'")

    base_tables = re.findall(r"CREATE TABLE\s+(crm_[a-z0-9_]+)\s*\(",
                             (ROOT / "infra/homologation/schema.sql").read_text())
    live_profile, _ = detect_profile(sql, base_tables)
    require(live_profile["receipt_reconciliation"], "Complete current S2S fixture required.")
    before = table_counts(sql, live_profile)
    old_title = sql(f"SELECT title FROM public.crm_private_leads WHERE id='{LEAD_A}'")
    require(old_title == "LEAD_TESTE_BACKUP_A", "Expected controlled backup fixture missing.")
    require(sql(f"SELECT count(*) FROM public.crm_private_leads WHERE id='{CONCURRENT_LEAD}'") == "0",
            "Concurrent-writer fixture already exists.")
    try:
        with exported_snapshot(source) as snapshot:
            pid, identifier = snapshot.backend_pid, snapshot.identifier
            # A different connection commits real writes AFTER the snapshot was exported.
            # No table lock or production test hook synchronizes this deterministic proof.
            sql(f"""
              BEGIN;
              INSERT INTO public.crm_private_leads(id,organization_id,funnel_id,title,created_at)
                VALUES ('{CONCURRENT_LEAD}','{A}','{FUNNEL_A}','LEAD_TESTE_BACKUP_AFTER_EXPORT',1800000000);
              UPDATE public.crm_private_leads SET title='LEAD_TESTE_BACKUP_WRITTEN_AFTER_EXPORT' WHERE id='{LEAD_A}';
              COMMIT;
            """)
            require(int(sql("SELECT count(*) FROM public.crm_private_leads"))
                    == before["crm_private_leads"] + 1, "Concurrent writer did not commit.")
            require(sql(f"SELECT title FROM public.crm_private_leads WHERE id='{LEAD_A}'")
                    == "LEAD_TESTE_BACKUP_WRITTEN_AFTER_EXPORT", "Concurrent update did not commit.")
            profile, security = detect_profile(snapshot.query, base_tables)
            frozen = table_counts(snapshot.query, profile)
            require(frozen == before, "Snapshot inventory observed a later committed row.")
            require(snapshot.query(f"SELECT title FROM public.crm_private_leads WHERE id='{LEAD_A}'") == old_title,
                    "Snapshot observed a later committed update.")
            scopes = {}
            for role, tables in (("crm_homologation_app", BASE_TENANT_TABLES), ("crm_auto_reader_owner", S2S_TABLES)):
                scopes[role] = {tenant: scoped_counts(snapshot.query, tenant, role=role, tables=tables,
                                                     manage_transaction=False) for tenant in ("", A, B)}
            dump = snapshot.dump()
            require(dump.startswith(b"PGDMP"), "Snapshot did not produce a PostgreSQL archive.")
        ended(pid)
        expired = subprocess.run(psql, input=("BEGIN ISOLATION LEVEL REPEATABLE READ READ ONLY; "
                      "SET TRANSACTION SNAPSHOT '" + identifier + "'; ROLLBACK;").encode(), capture_output=True)
        require(expired.returncode != 0 and b"invalid snapshot identifier" in expired.stderr,
                "Exported snapshot survived exporter cleanup.")
        # Reproduce the old race with an actual pg_dump that imports no snapshot.
        # Its rows are newer than the already collected manifest, despite both reads succeeding.
        unshared_dump = run(source + ["pg_dump", "-U", "crm_homologation_owner", "-d", DATABASE, "-Fc"])
        with isolated_restore_cluster(profile, run) as (query, command):
            run(command + ["pg_restore", "--exit-on-error", "-U", "crm_homologation_restore", "-d", DATABASE], unshared_dump)
            require(table_counts(query, profile) != frozen
                    and int(query("SELECT count(*) FROM public.crm_private_leads")) == before["crm_private_leads"] + 1
                    and query(f"SELECT count(*) FROM public.crm_private_leads WHERE id='{CONCURRENT_LEAD}'") == "1",
                    "Prior unshared-manifest/dump inconsistency not reproduced.")
        print("REPRODUCED prior unshared pg_dump captures a committed row absent from the earlier manifest")
        with isolated_restore_cluster(profile, run) as (query, command):
            run(command + ["pg_restore", "--exit-on-error", "-U", "crm_homologation_restore", "-d", DATABASE], dump)
            verify_restored_security(query, profile, security, base_tables)
            require(table_counts(query, profile) == frozen, "Restored dump and snapshot manifest disagree.")
            require(query(f"SELECT count(*) FROM public.crm_private_leads WHERE id='{CONCURRENT_LEAD}'") == "0"
                    and query(f"SELECT title FROM public.crm_private_leads WHERE id='{LEAD_A}'") == old_title,
                    "pg_dump imported a different snapshot from the manifest.")
            for role, tables in (("crm_homologation_app", BASE_TENANT_TABLES), ("crm_auto_reader_owner", S2S_TABLES)):
                for tenant in ("", A, B):
                    require(scoped_counts(query, tenant, role=role, tables=tables) == scopes[role][tenant],
                            "Restored scopes differ from the exported source snapshot.")
        require(int(sql("SELECT count(*) FROM public.crm_private_leads")) == before["crm_private_leads"] + 1,
                "Backup snapshot or restore modified the live writer's data.")
        print("PASS concurrent committed insert/update excluded from both snapshot manifest and separate-cluster dump")
    finally:
        sql(f"DELETE FROM public.crm_private_leads WHERE id='{CONCURRENT_LEAD}'; "
            f"UPDATE public.crm_private_leads SET title='LEAD_TESTE_BACKUP_A' WHERE id='{LEAD_A}'")
    require(table_counts(sql, live_profile) == before, "Concurrent fixtures were not restored.")

    for failure in ("caller", "metadata-lost", "read-only", "exporter-lost"):
        failed_pid = None
        expected_fault = False
        try:
            with exported_snapshot(source) as snapshot:
                failed_pid = snapshot.backend_pid
                if failure == "caller":
                    expected_fault = True
                    raise RuntimeError("Synthetic caller failure.")
                if failure == "metadata-lost":
                    # A real exporter is running, but its metadata did not reach
                    # the caller; cleanup must use the pre-connection marker.
                    snapshot.backend_pid = None
                    snapshot.backend_started = None
                    expected_fault = True
                    raise RuntimeError("Synthetic exporter metadata interruption.")
                if failure == "read-only":
                    try:
                        snapshot.query(f"UPDATE public.crm_private_leads SET title='LEAD_TESTE_FORBIDDEN' WHERE id='{LEAD_A}'")
                    except RuntimeError as error:
                        require("SQLSTATE=25006" in str(error), "Write failed without proving read-only enforcement.")
                        expected_fault = True
                        raise
                else:
                    # Target only this CI-created exporter, then require fail-closed capture.
                    require(sql(f"SELECT pg_terminate_backend({failed_pid},5000)") == "t",
                            "Synthetic exporter interruption failed.")
                    expected_fault = True
                    snapshot.dump()
        except RuntimeError:
            if not expected_fault:
                raise
        else:
            raise AssertionError("Snapshot failure path did not fail closed: " + failure)
        require(failed_pid is not None, "Exporter did not start for cleanup proof.")
        ended(failed_pid)
        require(sql(f"SELECT title FROM public.crm_private_leads WHERE id='{LEAD_A}'") == old_title,
                "Snapshot failure path mutated the source.")
        print("PASS exporter cleanup after " + failure)
    with exported_snapshot(source) as snapshot:
        nested_pid = snapshot.backend_pid
        try:
            snapshot.query("BEGIN; SELECT 1; ROLLBACK;")
        except RuntimeError as error:
            require("own transaction" in str(error), "Unexpected transaction-control failure.")
        else:
            raise AssertionError("Nested transaction controls were silently accepted.")
        require(snapshot.query("SELECT current_setting('transaction_read_only')") == "on",
                "Importer did not retain read-only protection.")
    ended(nested_pid)
    for kind in ("dump", "SQL"):
        with witness(lock_table=kind == "dump") as (witness_pid, witness_marker):
            with exported_snapshot(source) as snapshot:
                exporter_pid = snapshot.backend_pid
                suffix = "-d-" if kind == "dump" else "-q-"
                with ThreadPoolExecutor(max_workers=1) as pool:
                    future = pool.submit(snapshot.dump) if kind == "dump" else pool.submit(snapshot.query, "SELECT pg_sleep(10)")
                    observed = []
                    until = time.monotonic() + 8
                    while time.monotonic() < until and not future.done():
                        observed = json.loads(sql("SELECT COALESCE(json_agg(json_build_object('pid',pid,"
                            "'start',backend_start,'wait_type',wait_event_type,'wait',wait_event)), '[]'::json) "
                            "FROM pg_stat_activity WHERE application_name LIKE '" + snapshot.application + suffix
                            + f"%' AND datname='{DATABASE}' AND usename='crm_homologation_owner' AND state='active' "
                            + ("AND wait_event_type='Lock' AND clock_timestamp()-query_start < interval '1 second'"
                               if kind == "dump" else "AND wait_event='PgSleep'")))
                        if observed:
                            break
                        time.sleep(0.02)
                    if len(observed) != 1:
                        snapshot.deadline = time.monotonic()
                        raise RuntimeError("Real blocked operation was not observed before client timeout: " + kind)
                    operation_pid = observed[0]["pid"]
                    # The backend is known to be alive. Shorten only the local CI
                    # object's budget so the client deadline precedes native 2s/15s timeouts.
                    snapshot.deadline = time.monotonic() + 0.15
                    try:
                        future.result(timeout=40)
                    except RuntimeError as error:
                        require("Private snapshot " + kind + " client deadline exceeded." in str(error),
                                "Operation failed without proving the client timeout was first: " + kind)
                    else:
                        raise AssertionError("Real blocked operation did not hit its client deadline.")
                ended(operation_pid)
                require(sql(f"SELECT count(*) FROM pg_stat_activity WHERE pid={witness_pid} "
                            f"AND application_name='{witness_marker}'") == "1", "Cleanup terminated the unrelated witness.")
                require(sql(f"SELECT count(*) FROM pg_stat_activity WHERE pid={exporter_pid}") == "1",
                        "Operation cleanup terminated its still-owned exporter prematurely.")
            ended(exporter_pid)
            require(sql(f"SELECT count(*) FROM pg_stat_activity WHERE pid={witness_pid} "
                        f"AND application_name='{witness_marker}'") == "1", "Exporter cleanup terminated the unrelated witness.")
        ended(witness_pid)
        print("PASS real " + kind + " client timeout; observed backend removed and unrelated witness preserved")
    require(sql("SELECT count(*) FROM pg_stat_activity WHERE application_name LIKE 'imagine-crm-backup-snapshot-%'") == "0",
            "Exporter sessions remain after the complete fixture.")
    require(table_counts(sql, live_profile) == before, "Snapshot regressions changed source counts.")
    print("CRM_BACKUP_EXPORTED_SNAPSHOT_PASS concurrent_writer=1 manifest_dump_same_snapshot=1 "
          "read_only=1 normal_and_failure_cleanup=1 real_client_timeouts=2 witness_preserved=1 source_preserved=1 operational_apply=0")


if __name__ == "__main__":
    main()
