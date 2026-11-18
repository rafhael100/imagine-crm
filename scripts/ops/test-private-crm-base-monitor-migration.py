#!/usr/bin/env python3
"""Prove the additive base25 -> monitor33 migration in disposable CI."""
from __future__ import annotations

from concurrent.futures import ThreadPoolExecutor
from contextlib import contextmanager
import hashlib
import json
import os
import re
import secrets
import subprocess
import sys
import time

import private_crm_owner_drafts_ci as ci
import private_crm_base_monitor_migration as migration

DATABASE = "imagine_crm_private_homologation"
# The unmodified Transaction662 owns the inherited container identity contract.
PREFIX = "imagine-owner-monitor-migration-"
SCOPE_LABEL = "imagine.base_monitor_migration.scope"
SCOPE = "base25-monitor33-v1"
SCHEMA = "infra/homologation/schema.sql"
ROLE_SCRIPT = "infra/homologation/20-runtime-roles.sh"
SERVICE_INPUTS = tuple("infra/homologation/s2s/" + name + ".sql" for name in (
    "install-candidate", "outbound-candidate", "execution-feedback-candidate", "execution-monitor-candidate"))
SERVICE_TABLES = {"crm_auto_service." + name for name in (
    "grants", "nonces", "audit", "outbound_grants", "outbox",
    "execution_read_grants", "execution_observations", "execution_projections")}
SEQUENCE = "crm_auto_service.audit_id_seq"
BASE_ROLE_NAMES = {
    "crm_homologation_owner", "crm_homologation_app", "crm_homologation_iam",
    "crm_homologation_enrollment", "crm_homologation_operator",
}
ADDED_ROLE_NAMES = {"crm_auto_reader_owner", "crm_auto_transport", "crm_auto_sender",
                    "crm_auto_execution_reconciler"}
SECRET_ENV = ("POSTGRES_PASSWORD", "CRM_PRIVATE_APP_PASSWORD", "CRM_PRIVATE_IAM_PASSWORD",
              "CRM_PRIVATE_ENROLLMENT_PASSWORD", "CRM_PRIVATE_OPERATOR_PASSWORD")
LABEL = "imagine.owner_monitor_migration."
CHECKS = 0
PHASE = "admission"


def check(condition, label):
    global CHECKS
    CHECKS += 1
    if not condition:
        raise ci.Failure("BASE_MONITOR_MIGRATION_PROOF_" + label)


def run(command, *, data=None, environment=None, timeout=60, required=True):
    result = subprocess.run(command, input=data, env=environment, capture_output=True, timeout=timeout)
    check(not required or result.returncode == 0, "COMMAND_FAILED")
    check(len(result.stdout) <= 8 * 1024 * 1024 and len(result.stderr) <= 1024 * 1024, "COMMAND_BOUND")
    return result


def fingerprint(value):
    return hashlib.sha256(json.dumps(value, sort_keys=True, separators=(",", ":")).encode()).hexdigest()


def inspect_owned(identity, context):
    check(re.fullmatch(r"[a-f0-9]{64}", identity) is not None, "CONTAINER_ID")
    values = json.loads(run(["docker", "inspect", identity]).stdout)
    check(type(values) is list and len(values) == 1, "CONTAINER_INSPECTION")
    value = values[0]
    labels = value["Config"].get("Labels") or {}
    check(value["Id"] == identity
          and value["Name"].lstrip("/").startswith(PREFIX + context["run"] + "-" + context["attempt"] + "-base-monitor-")
          and labels.get(SCOPE_LABEL) == SCOPE
          and all(labels.get(LABEL + key) == context[key] for key in ("head", "run", "attempt")),
          "CONTAINER_OWNERSHIP")
    check(value["Image"] == context["image"] and value["HostConfig"]["NetworkMode"] == "none"
          and not value["HostConfig"].get("PortBindings") and not value["HostConfig"].get("Binds")
          and all(mount["Type"] == "tmpfs" for mount in value.get("Mounts", []))
          and "no-new-privileges:true" in value["HostConfig"].get("SecurityOpt", []),
          "ISOLATED_TEMPORARY_CLUSTER")
    return value


def cleanup_owned(context):
    command = ["docker", "ps", "-aq", "--no-trunc", "--filter", "label=" + SCOPE_LABEL + "=" + SCOPE]
    for key in ("head", "run", "attempt"):
        command += ["--filter", "label=" + LABEL + key + "=" + context[key]]
    identities = run(command).stdout.decode().split()
    for identity in identities:
        inspect_owned(identity, context)
        run(["docker", "rm", "-f", identity])
    check(not run(command).stdout.strip(), "OWNED_CLUSTER_CLEANUP")


class Cluster:
    def __init__(self, context, kind):
        self.context = context
        self.kind = kind
        self.identity = None
        self.command = None

    def start(self):
        name = PREFIX + self.context["run"] + "-" + self.context["attempt"] + "-base-monitor-" + self.kind + "-" + secrets.token_hex(4)
        environment = dict(os.environ, **{key: secrets.token_hex(32) for key in SECRET_ENV})
        arguments = ["docker", "run", "-d", "--name", name, "--network", "none",
                     "--security-opt", "no-new-privileges:true", "--tmpfs", "/var/lib/postgresql/data",
                     "--env", "POSTGRES_DB=" + DATABASE, "--env", "POSTGRES_USER=crm_homologation_owner",
                     "--label", SCOPE_LABEL + "=" + SCOPE]
        for key in ("head", "run", "attempt"):
            arguments += ["--label", LABEL + key + "=" + self.context[key]]
        for key in SECRET_ENV:
            arguments += ["--env", key]
        self.identity = run(arguments + [self.context["image"]], environment=environment).stdout.decode().strip()
        self.command = ["docker", "exec", "-i", self.identity]
        inspect_owned(self.identity, self.context)
        ready = False
        for _ in range(80):
            process = run(["docker", "exec", self.identity, "cat", "/proc/1/comm"], required=False)
            if process.returncode == 0 and process.stdout.strip() == b"postgres":
                process = self.psql(b"SELECT current_database()", required=False)
                if process.returncode == 0 and process.stdout.decode().strip() == DATABASE:
                    ready = True
                    break
            time.sleep(.25)
        check(ready, "POSTGRES_READY")
        check(self.query("SELECT current_setting('server_version_num')::int/10000") == "16", "POSTGRES_16")

    def psql(self, data, *, required=True):
        return run(self.command + ["psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1",
                   "-v", "VERBOSITY=sqlstate", "-U", "crm_homologation_owner", "-d", DATABASE],
                   data=data, timeout=30, required=required)

    def query(self, sql):
        return self.psql(("SET search_path=pg_catalog,public;\n" + sql + "\n").encode()).stdout.decode().strip()

    def bootstrap(self, sources, *, monitor):
        self.psql(sources[SCHEMA])
        run(self.command + ["sh", "-s"], data=sources[ROLE_SCRIPT])
        if monitor:
            for path in SERVICE_INPUTS:
                self.psql(sources[path])
        expected_roles = BASE_ROLE_NAMES | (ADDED_ROLE_NAMES if monitor else set())
        check(set(json.loads(self.query("SELECT jsonb_agg(rolname ORDER BY rolname) FROM pg_roles WHERE rolname !~ '^pg_'")))
              == expected_roles, "EXACT_PROFILE_ROLES")
        check(self.query("SELECT count(*) FROM pg_proc p JOIN pg_namespace n ON n.oid=p.pronamespace"
                         " WHERE n.nspname='crm_auto_service'") == ("9" if monitor else "0"), "EXACT_PROFILE_FUNCTIONS")
        check(self.query("SELECT count(*) FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace"
                         " WHERE n.nspname IN ('public','crm_auto_service') AND c.relkind='S'")
              == ("1" if monitor else "0"), "EXACT_PROFILE_SEQUENCES")
        if monitor:
            check(self.query("SELECT count(*)=4 AND bool_and(NOT rolcanlogin) FROM pg_roles"
                             " WHERE rolname IN ('crm_auto_reader_owner','crm_auto_transport','crm_auto_sender',"
                             "'crm_auto_execution_reconciler')") == "t", "FOUR_ADDITIONAL_NOLOGIN_ROLES")

    def remove(self):
        if self.identity is not None:
            inspect_owned(self.identity, self.context)
            run(["docker", "rm", "-f", self.identity])
            self.identity = None


@contextmanager
def cluster(context, sources, kind, *, monitor=False):
    value = Cluster(context, kind)
    try:
        value.start()
        value.bootstrap(sources, monitor=monitor)
        yield value
    finally:
        value.remove()


def columns(inventory, expected):
    result = {}
    for entry in inventory["catalog"]["columns"]:
        result.setdefault(entry["table"], []).append(entry["name"])
    check(len(result) == expected, "EXACT_TABLE_INVENTORY")
    for table, names in result.items():
        check(re.fullmatch(r"(?:public|crm_auto_service)\.[a-z][a-z0-9_]*", table) is not None
              and names and len(names) == len(set(names))
              and all(re.fullmatch(r"[a-z][a-z0-9_]*", name) for name in names), "CLOSED_COLUMN_IDENTIFIERS")
    return {table: tuple(names) for table, names in result.items()}


def rows(query, specification):
    pieces = []
    for table, names in sorted(specification.items()):
        projection = ",".join('"' + name + '"' for name in names)
        pieces.append("SELECT '" + table + "' AS name,jsonb_build_object('rows',count(*),'sha256',"
                      "encode(sha256(convert_to(COALESCE(jsonb_agg(to_jsonb(r) ORDER BY to_jsonb(r)::text COLLATE \"C\"),"
                      "'[]'::jsonb)::text,'UTF8')),'hex')) AS value"
                      " FROM (SELECT " + projection + " FROM " + table + ") r")
    result = json.loads(query("SELECT jsonb_object_agg(name,value ORDER BY name) FROM (" + " UNION ALL ".join(pieces) + ") inventory"))
    check(set(result) == set(specification) and all(set(value) == {"rows", "sha256"}
          and type(value["rows"]) is int and value["rows"] >= 0 and re.fullmatch(r"[a-f0-9]{64}", value["sha256"])
          for value in result.values()), "ROW_FINGERPRINTS")
    return result


def sequence_state(query):
    exists = query("SELECT to_regclass('" + SEQUENCE + "') IS NOT NULL")
    check(exists in ("t", "f"), "SEQUENCE_EXISTENCE")
    if exists == "f":
        return None
    return json.loads(query("SELECT jsonb_build_object('last_value',last_value,'is_called',is_called) FROM " + SEQUENCE))


def seed_base(query):
    # SQL fixtures establish real FK relationships; no app, human session, HTTP
    # receipt verification or n8n execution is claimed by these stored samples.
    query("""
BEGIN;
INSERT INTO public.crm_global_identities(id) VALUES
 ('20000000-0000-4000-8000-000000000001'),('20000000-0000-4000-8000-000000000002');
INSERT INTO public.crm_organizations(id) VALUES
 ('10000000-0000-4000-8000-000000000001'),('10000000-0000-4000-8000-000000000002');
INSERT INTO public.crm_memberships(id,organization_id,global_identity_id,status) VALUES
 ('70000000-0000-4000-8000-000000000001','10000000-0000-4000-8000-000000000001','20000000-0000-4000-8000-000000000001','active'),
 ('70000000-0000-4000-8000-000000000002','10000000-0000-4000-8000-000000000002','20000000-0000-4000-8000-000000000002','active');
INSERT INTO public.crm_private_accounts(identity_id,organization_id,label,created_at) VALUES
 ('20000000-0000-4000-8000-000000000001','10000000-0000-4000-8000-000000000001','EMPRESA_TESTE_MIGRATION_ONE',1800000000),
 ('20000000-0000-4000-8000-000000000002','10000000-0000-4000-8000-000000000002','EMPRESA_TESTE_MIGRATION_TWO',1800000000);
INSERT INTO public.crm_private_trials VALUES ('10000000-0000-4000-8000-000000000001',1800000000,1801209600);
INSERT INTO public.crm_private_funnels VALUES
 ('30000000-0000-4000-8000-000000000001','10000000-0000-4000-8000-000000000001','FUNIL_TESTE_MIGRATION_ONE',1800000000),
 ('30000000-0000-4000-8000-000000000002','10000000-0000-4000-8000-000000000002','FUNIL_TESTE_MIGRATION_TWO',1800000000);
INSERT INTO public.crm_private_leads(id,organization_id,funnel_id,title,created_at) VALUES
 ('40000000-0000-4000-8000-000000000001','10000000-0000-4000-8000-000000000001','30000000-0000-4000-8000-000000000001','LEAD_TESTE_MIGRATION_ONE',1800000000),
 ('40000000-0000-4000-8000-000000000002','10000000-0000-4000-8000-000000000002','30000000-0000-4000-8000-000000000002','LEAD_TESTE_MIGRATION_TWO',1800000000);
INSERT INTO public.crm_private_tasks(id,organization_id,lead_id,title,due_on) VALUES
 ('80000000-0000-4000-8000-000000000001','10000000-0000-4000-8000-000000000001','40000000-0000-4000-8000-000000000001','TASK_TESTE_MIGRATION','2026-11-19');
INSERT INTO public.crm_private_preferences(organization_id,language) VALUES ('10000000-0000-4000-8000-000000000001','pt');
INSERT INTO public.crm_private_automation_runs(id,organization_id,actor_id,authority_session_id,template,settings_version,
 lead_id,lead_version,execution_key,command,state,due_at,created_at,attempts) VALUES
 ('50000000-0000-4000-8000-000000000001','10000000-0000-4000-8000-000000000001','20000000-0000-4000-8000-000000000001',
 '90000000-0000-4000-8000-000000000001','followup_qualified',1,'40000000-0000-4000-8000-000000000001',1,repeat('e',64),
 '{"schema":"SYNTHETIC_MIGRATION_FIXTURE"}','pending',1800000060,1800000000,2);
INSERT INTO public.crm_private_events VALUES ('60000000-0000-4000-8000-000000000001',
 '10000000-0000-4000-8000-000000000001','20000000-0000-4000-8000-000000000001',
 '40000000-0000-4000-8000-000000000001','lead.created',1800000000);
COMMIT;
""")


def seed_monitor(query):
    # Stored fixtures exercise all eight added tables; they assert no service calls.
    query("""
BEGIN;
INSERT INTO crm_auto_service.grants VALUES ('10000000-0000-4000-8000-000000000001',repeat('a',64),
 'portal-automation-admin','automation.state.read',3,false,4102444800);
INSERT INTO crm_auto_service.nonces VALUES ('10000000-0000-4000-8000-000000000001',repeat('a',64),
 '91000000-0000-4000-8000-000000000001',repeat('b',64),repeat('c',64),1800000000);
INSERT INTO crm_auto_service.audit(company_id,fingerprint,request_id,outcome,observed_at,grant_revision) VALUES
 ('10000000-0000-4000-8000-000000000001',repeat('a',64),'91000000-0000-4000-8000-000000000001','DENIED',1800000000,3);
INSERT INTO crm_auto_service.outbound_grants VALUES ('10000000-0000-4000-8000-000000000001','SYNTHETIC_MIGRATION_ONE',
 'imagine-ai-agents','lead.ingest',false,4,4102444800);
INSERT INTO crm_auto_service.outbox(company_id,event_id,tenant_ref,nonce_ref,request_ref,payload,issued_at,expires_at,state)
 VALUES ('10000000-0000-4000-8000-000000000001','60000000-0000-4000-8000-000000000001','SYNTHETIC_MIGRATION_ONE',
 'SYNTHETIC_MIGRATION_NONCE','SYNTHETIC_MIGRATION_REQUEST',
 '{"schema":"CRM_LEAD_EVENT_V1","action":"lead.created","company_id":"10000000-0000-4000-8000-000000000001","event_id":"60000000-0000-4000-8000-000000000001","lead_id":"40000000-0000-4000-8000-000000000001"}',
 1800000000,4102444800,'pending');
INSERT INTO crm_auto_service.execution_read_grants VALUES ('10000000-0000-4000-8000-000000000001','SYNTHETIC_MIGRATION_ONE',
 'imagine-ai-agents','event.execution.read',false,5,4102444800);
INSERT INTO crm_auto_service.execution_observations(company_id,event_id,observation_hash,sequence,tenant_ref,event_ref,
 payload_hash,lead_id,task_payload_hash,execution_state,queue_state,result_code,attempt,query,receipt,receipt_hash,grant_revision,observed_at)
 VALUES ('10000000-0000-4000-8000-000000000001','60000000-0000-4000-8000-000000000001',repeat('d',64),1,
 'SYNTHETIC_MIGRATION_ONE','SYNTHETIC_MIGRATION_EVENT',repeat('e',64),'40000000-0000-4000-8000-000000000001',
 '','NOT_STAGED','NONE','NONE',0,'{"schema":"SYNTHETIC_MIGRATION_QUERY"}','SYNTHETIC_MIGRATION_RECEIPT',repeat('f',64),5,1800000000);
INSERT INTO crm_auto_service.execution_projections VALUES ('10000000-0000-4000-8000-000000000001',
 '60000000-0000-4000-8000-000000000001',repeat('d',64));
COMMIT;
""")


def snapshot(value, specification):
    return {"inventory": migration.capture_inventory(value.query), "rows": rows(value.query, specification),
            "sequence": sequence_state(value.query)}


def expect_drift(value, references, specification):
    before = snapshot(value, specification)
    try:
        migration.migrate(value.command, references)
    except migration.MigrationDenied as error:
        check(error.code == "BASE_MONITOR_MIGRATION_SCHEMA_DRIFT", "SPECIFIC_DRIFT_DENIAL")
    else:
        check(False, "DRIFT_ACCEPTED")
    check(snapshot(value, specification) == before, "DENIAL_LEFT_SOURCE_UNCHANGED")


def wait_for_role_lock(value, locker, transaction_id, future):
    deadline = time.monotonic() + 12
    while time.monotonic() < deadline and not future.done():
        observed = json.loads(value.query("""
SELECT COALESCE(jsonb_agg(jsonb_build_object(
 'pid',a.pid,'query',a.query,'application',a.application_name,'waiting_mode',l.mode,
 'wait_event_type',a.wait_event_type,'transaction_id',l.transactionid::text,
 'blockers',pg_blocking_pids(a.pid)) ORDER BY a.pid),'[]'::jsonb)
 FROM pg_stat_activity a JOIN pg_locks l ON l.pid=a.pid
 WHERE a.datname=current_database() AND a.pid<>pg_backend_pid()
 AND a.pid<>""" + str(locker.backend_pid) + """
 AND a.usename='crm_homologation_owner' AND a.wait_event_type='Lock' AND NOT l.granted
 AND l.locktype='transactionid' AND l.transactionid::text='""" + transaction_id + """';
"""))
        if observed:
            check(len(observed) == 1, "ONE_REAL_ROLE_WAITING_BACKEND")
            item = observed[0]
            check(type(item["pid"]) is int and item["pid"] > 0
                  and item["wait_event_type"] == "Lock" and item["waiting_mode"] == "ShareLock"
                  and item["transaction_id"] == transaction_id
                  and item["blockers"] == [locker.backend_pid], "EXACT_ROLE_TRANSACTION_BLOCKER")
            check(item["query"].strip() ==
                  "CREATE ROLE crm_auto_sender NOLOGIN NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE;"
                  and item["application"].startswith(PREFIX)
                  and item["application"] != locker.application, "LITERAL_OUTBOUND_CREATE_ROLE_WAIT")
            return {"backend_pid": item["pid"], "blocker_pid": locker.backend_pid,
                    "transaction_id": transaction_id, "waiting_mode": item["waiting_mode"],
                    "query_stage": "OUTBOUND_CREATE_ROLE_AFTER_INSTALL",
                    "blocking_backend_observed": True}
        time.sleep(.05)
    check(False, "REAL_ROLE_LOCK_WAIT_NOT_OBSERVED")


def late_role_rollback(value, references, specification):
    before = snapshot(value, specification)
    with migration.Transaction(value.command, lock_timeout_ms=2000, statement_timeout_ms=15000) as locker:
        locker.query("CREATE ROLE crm_auto_sender NOLOGIN NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE;")
        transaction_id = locker.query("SELECT (pg_current_xact_id()::text::bigint % 4294967296)::text")
        check(re.fullmatch(r"[1-9][0-9]{0,9}", transaction_id) is not None, "REAL_BLOCKER_TRANSACTION_ID")
        check(snapshot(value, specification) == before, "UNCOMMITTED_ROLE_INVISIBLE_TO_ADMISSION")
        with ThreadPoolExecutor(max_workers=1) as executor:
            future = executor.submit(migration.migrate, value.command, references,
                                     lock_timeout_ms=8000, statement_timeout_ms=15000)
            observation = wait_for_role_lock(value, locker, transaction_id, future)
            try:
                future.result(timeout=20)
            except migration.MigrationDenied as error:
                check(error.code == "BASE_MONITOR_MIGRATION_LOCK_TIMEOUT"
                      and error.sqlstate == "55P03", "REAL_ROLE_LOCK_TIMEOUT_SQLSTATE")
            else:
                check(False, "ROLE_LOCK_TIMEOUT_ACCEPTED")
        check(value.query("SELECT count(*) FROM pg_stat_activity WHERE pid=" + str(observation["backend_pid"])) == "0",
              "FAILED_MIGRATION_BACKEND_CLOSED")
        check(snapshot(value, specification) == before, "MIGRATION_ROLLBACK_BEFORE_BLOCKER_CLOSE")
    check(value.query("SELECT count(*) FROM pg_stat_activity WHERE pid=" + str(locker.backend_pid)) == "0",
          "BLOCKER_BACKEND_CLOSED")
    check(snapshot(value, specification) == before, "BOTH_TRANSACTIONS_ROLLED_BACK")
    check(value.query("SELECT to_regnamespace('crm_auto_service') IS NULL") == "t", "SERVICE_SCHEMA_ROLLED_BACK")
    check(value.query("SELECT count(*) FROM pg_roles WHERE rolname LIKE 'crm_auto_%'") == "0", "ADDED_ROLES_ROLLED_BACK")
    check(sequence_state(value.query) is None, "ADDED_SEQUENCE_ROLLED_BACK")
    return {**observation, "sqlstate": "55P03", "catalog_data_sequence_unchanged": True,
            "added_schema_roles_sequence_rolled_back": True, "both_backends_closed": True}


def context():
    head, _ = ci.preflight()
    run_id, attempt = os.environ.get("GITHUB_RUN_ID", ""), os.environ.get("GITHUB_RUN_ATTEMPT", "")
    check(all(re.fullmatch(r"[1-9][0-9]{0,19}", text) for text in (run_id, attempt)), "REAL_RUN_IDENTITY")
    check(run(["docker", "context", "show"]).stdout.decode().strip() == "default", "LOCAL_DOCKER_CONTEXT")
    contexts = json.loads(run(["docker", "context", "inspect", "default"]).stdout)
    check(type(contexts) is list and len(contexts) == 1
          and contexts[0]["Endpoints"]["docker"]["Host"] in
          ("unix:///var/run/docker.sock", "unix:///run/docker.sock"), "LOCAL_DOCKER_SOCKET")
    image = run(["docker", "image", "inspect", "postgres:16", "--format", "{{.Id}}"]).stdout.decode().strip()
    check(re.fullmatch(r"sha256:[a-f0-9]{64}", image) is not None, "OBSERVED_POSTGRES_IMAGE")
    return {"head": head, "run": run_id, "attempt": attempt, "image": image}


def exercise(context, sources):
    global PHASE
    PHASE = "independent-pinned-reference-clusters"
    with cluster(context, sources, "ref25") as reference25, cluster(context, sources, "ref33", monitor=True) as reference33:
        inventory25 = migration.capture_inventory(reference25.query)
        inventory33 = migration.capture_inventory(reference33.query)
        specification25, specification33 = columns(inventory25, 25), columns(inventory33, 33)
        check(set(specification33) - set(specification25) == SERVICE_TABLES, "ONLY_EIGHT_ADDITIONAL_TABLES")
        check({name: specification33[name] for name in specification25} == specification25,
              "ALL_BASE_COLUMNS_UNCHANGED")
        check(sequence_state(reference25.query) is None
              and sequence_state(reference33.query) == {"last_value": 1, "is_called": False}, "REFERENCE_SEQUENCE_SHAPES")
        references = migration.references(reference25.query, reference33.query, sources)
        PHASE = "first-apply-and-populated-reapply"
        with cluster(context, sources, "positive") as source:
            seed_base(source.query)
            before = snapshot(source, specification25)
            check(sum(value["rows"] > 0 for value in before["rows"].values()) == 12
                  and sum(value["rows"] == 0 for value in before["rows"].values()) == 13, "BASE_DATA_COUNTS")
            applied = migration.migrate(source.command, references)
            check(applied["action"] == "APPLIED", "FIRST_APPLY")
            check(migration.capture_inventory(source.query) == inventory33, "EXACT_TARGET_REFERENCE")
            check(rows(source.query, specification25) == before["rows"], "ALL_25_TABLE_DATA_PRESERVED")
            added = {name: specification33[name] for name in SERVICE_TABLES}
            check(all(value["rows"] == 0 for value in rows(source.query, added).values()), "EIGHT_NEW_TABLES_EMPTY")
            initialized_sequence = sequence_state(source.query)
            check(before["sequence"] is None and initialized_sequence == {"last_value": 1, "is_called": False},
                  "NEW_SEQUENCE_INITIALIZED")
            seed_monitor(source.query)
            populated = snapshot(source, specification33)
            check(sum(value["rows"] > 0 for value in populated["rows"].values()) == 20
                  and all(populated["rows"][name]["rows"] == 1 for name in SERVICE_TABLES), "ALL_EIGHT_ADDED_TABLES_POPULATED")
            check(populated["sequence"] == {"last_value": 1, "is_called": True}, "AUDIT_IDENTITY_USED")
            reapplied = migration.migrate(source.command, references)
            check(reapplied["action"] == "ALREADY_PRESENT", "REAPPLY_NOOP")
            check(snapshot(source, specification33) == populated, "REAPPLY_PRESERVES_ALL_33_AND_SEQUENCE")
            PHASE = "target-grant-drift-denial"
            source.query("GRANT DELETE ON crm_auto_service.audit TO crm_homologation_app;")
            expect_drift(source, references, specification33)

        PHASE = "partial-installation-denial"
        with cluster(context, sources, "partial") as source:
            seed_base(source.query)
            source.psql(sources[SERVICE_INPUTS[0]])
            partial = migration.capture_inventory(source.query)
            check(set(columns(partial, 28)) - set(specification25)
                  == {"crm_auto_service.grants", "crm_auto_service.nonces", "crm_auto_service.audit"},
                  "WHOLE_INSTALL_BOOTSTRAP_PARTIAL_28")
            expect_drift(source, references, columns(partial, 28))

        PHASE = "source-role-attribute-drift-denial"
        with cluster(context, sources, "role") as source:
            seed_base(source.query)
            source.query("ALTER ROLE crm_homologation_app INHERIT;")
            expect_drift(source, references, specification25)

        PHASE = "late-create-role-lock-rollback"
        with cluster(context, sources, "rollback") as source:
            seed_base(source.query)
            late_rollback = late_role_rollback(source, references, specification25)

        PHASE = "reference-preservation"
        check(migration.capture_inventory(reference25.query) == inventory25
              and migration.capture_inventory(reference33.query) == inventory33, "REFERENCES_UNCHANGED")
        check(sequence_state(reference25.query) is None
              and sequence_state(reference33.query) == {"last_value": 1, "is_called": False}, "REFERENCE_SEQUENCES_UNCHANGED")
        return {"first_apply": applied, "populated_reapply": reapplied,
                "reference25_sha256": fingerprint(inventory25), "reference33_sha256": fingerprint(inventory33),
                "source25_data_sha256": fingerprint(before["rows"]), "populated33_data_sha256": fingerprint(populated["rows"]),
                "new_sequence_state": initialized_sequence, "populated_sequence_state": populated["sequence"],
                "late_role_rollback": late_rollback}


def main():
    global PHASE
    check(sys.argv[1:] in ([], ["--cleanup"]), "ARGUMENTS")
    current = context()
    if sys.argv[1:]:
        cleanup_owned(current)
        print("CRM_BASE_MONITOR_MIGRATION_OWNED_CLEANUP_PASS")
        return
    try:
        sources = migration.pinned_sources()
        check(set(sources) == {SCHEMA, ROLE_SCRIPT, *SERVICE_INPUTS}, "SIX_PINNED_SOURCE_FILES")
        result = exercise(current, sources)
    finally:
        cleanup_owned(current)
    PHASE = "complete"
    print(json.dumps({"check": "CRM_BASE_MONITOR_INCREMENTAL_MIGRATION_PASS_NO_GO",
                      "head": current["head"], "source_commit": migration.PIN,
                      "run_id": current["run"], "run_attempt": current["attempt"], "postgres_image": current["image"],
                      "checks": CHECKS, "source_tables": 25, "target_tables": 33,
                      "populated_source_tables": 12, "empty_source_tables": 13,
                      "populated_reapply_tables": 20, "empty_reapply_tables": 13,
                      "source_roles": 5, "target_roles": 9, "added_nologin_roles": 4,
                      "source_functions": 0, "target_functions": 9,
                      "source_sequences": 0, "target_sequences": 1,
                      "independent_reference_clusters": 2, "migration_targets": 4,
                      "drift_and_partial_denials": 3, "real_lock_timeout_probes": 1,
                      "source_data_preserved": True, "populated_reapply_is_noop": True,
                      "same_transaction_catalog_and_security": True, "owned_cleanup": True,
                      "human_crm_session_verified": False, "owner_execution": False,
                      "new_http_or_n8n_execution": False, "distributed_recovery": False,
                      "go_for_installation": False, **result}, sort_keys=True), flush=True)


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        label = (error.code if isinstance(error, migration.MigrationDenied)
                 else error.args[0] if isinstance(error, ci.Failure) and len(error.args) == 1 else "")
        if type(label) is not str or not re.fullmatch(r"BASE_MONITOR_MIGRATION_[A-Z0-9_]{1,160}", label):
            label = "BASE_MONITOR_MIGRATION_UNEXPECTED_FAILURE"
        print(json.dumps({"check": "CRM_BASE_MONITOR_INCREMENTAL_MIGRATION_DENIED",
                          "phase": PHASE, "reason": label, "go_for_installation": False}), file=sys.stderr, flush=True)
        raise SystemExit(1)
