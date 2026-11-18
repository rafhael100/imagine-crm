#!/usr/bin/env python3
"""Closed, synthetic CI migration from the pinned monitor33 schema to owner35.

This is a disposable PostgreSQL laboratory API, not an operational migration.
The reference databases are independently bootstrapped from PIN by the caller.
No backup detector, restore equivalence or existing bootstrap is changed here.
"""
from __future__ import annotations

from dataclasses import dataclass
import hashlib
import json
import os
from pathlib import Path
import re
import secrets
import selectors
import signal
import subprocess
import time

import private_crm_backup_catalog as catalog
import private_crm_backup_contract as backup
import private_crm_owner_drafts_ci as ci

PIN = "96dbf1aed2d1cf3237bc3631c4594540a0168d2a"
DATABASE = "imagine_crm_private_homologation"
OWNER = "crm_homologation_owner"
SOURCE_BLOBS = {
    "infra/homologation/schema.sql": "9dde1bb86d496083ccd163225d8dd0c79b391cdb",
    "infra/homologation/20-runtime-roles.sh": "ae5fb2f964cc672d681f4108c1aff7fff5ea5682",
    "infra/homologation/s2s/install-candidate.sql": "3cafbf27be3e3968d401dbf98beac0e63fd27bea",
    "infra/homologation/s2s/outbound-candidate.sql": "4166eeb7d8dd2eabec9392e94dff4b392650de52",
    "infra/homologation/s2s/execution-feedback-candidate.sql": "4804870196ad820007f616d260efb8ea9b64dc0c",
    "infra/homologation/s2s/execution-monitor-candidate.sql": "d6dd92bee6ae35ded0aa6655dbdbe858b0d9f181",
    "infra/homologation/s2s/owner-drafts.sql": "a1f93eb85fdca3041f9dd16c21aa58448351a952",
}
HELPERS = {
    "private_crm_backup_catalog.py": (catalog, "d480c3cbaac6dba34dd5558a37af406ada3d6265"),
    "private_crm_backup_contract.py": (backup, "8780863e22006bda309a1071827f96b5d113ddfa"),
}
BOOTSTRAP = "infra/homologation/s2s/owner-drafts.sql"
BASE_TABLES = tuple("public." + name for name in (
    "crm_private_installation", "crm_global_identities", "crm_organizations", "crm_memberships",
    "crm_auth_credentials", "crm_auth_sessions", "crm_auth_recovery_codes", "crm_auth_operations",
    "crm_auth_mandates", "crm_iam_audit_events", "crm_private_accounts", "crm_private_trials",
    "crm_private_trial_claims", "crm_private_key_versions", "crm_private_signup_requests",
    "crm_private_recovery_attempts", "crm_private_funnels", "crm_private_invitations",
    "crm_private_leads", "crm_private_tasks", "crm_private_events", "crm_private_orders",
    "crm_private_preferences", "crm_private_automation_runs", "crm_private_operator_events",
))
OWNER_TABLES = ("public.crm_private_owner_draft_grants", "public.crm_private_owner_draft_intents")
TABLES33 = BASE_TABLES + backup.S2S_TABLES + backup.EXECUTION_TABLES
TABLES35 = TABLES33 + OWNER_TABLES
ROLES = backup.BASE_ROLES + backup.S2S_ROLES + backup.EXECUTION_ROLES
FUNCTIONS = (backup.S2S_FUNCTIONS | {backup.RECEIPT_FUNCTION} |
             backup.EXECUTION_FUNCTIONS | {backup.MONITOR_FUNCTION})
SEQUENCE = "crm_auto_service.audit_id_seq"
ACCOUNT = "public.crm_private_accounts"
MARKER = "owner_draft_lock_marker"
SCHEMAS = ("public", "crm_auto_service")
ADVISORY_KEY = "478600335"


class MigrationDenied(Exception):
    def __init__(self, label, *, sqlstate=None):
        # Callers report only .code, never SQL, stderr, credentials or a traceback.
        label = label if isinstance(label, str) and re.fullmatch(r"[A-Z0-9_]{1,80}", label) else "FAILED"
        self.code = "OWNER_MONITOR_MIGRATION_" + label
        self.sqlstate = sqlstate if isinstance(sqlstate, str) and re.fullmatch(r"[0-9A-Z]{5}", sqlstate) else None
        super().__init__(self.code)


def need(condition, label):
    if not condition:
        raise MigrationDenied(label)


def wire(value):
    return json.dumps(value, ensure_ascii=False, sort_keys=True, separators=(",", ":"))


def digest(value):
    return hashlib.sha256(wire(value).encode()).hexdigest()


def blob(raw):
    return hashlib.sha1(b"blob " + str(len(raw)).encode() + b"\0" + raw).hexdigest()


def _sources(source_files):
    need(type(source_files) is dict and set(source_files) == set(SOURCE_BLOBS), "SOURCE_INVENTORY")
    for path, expected in SOURCE_BLOBS.items():
        need(type(source_files[path]) is bytes and blob(source_files[path]) == expected, "SOURCE_BLOB")
    raw = source_files[BOOTSTRAP]
    prefix = b"-- Additive, disposable private homologation only. No production migration.\nBEGIN;\n"
    suffix = b"COMMIT;\n"
    need(raw.startswith(prefix) and raw.endswith(suffix), "BOOTSTRAP_ENVELOPE")
    # The reviewed blob makes this an exact envelope extraction, not a SQL rewrite.
    return raw[len(prefix):-len(suffix)].decode("utf-8")


def pinned_sources():
    """Load the seven reviewed files by immutable commit and verify Git modes/blobs."""
    for filename, (module, expected) in HELPERS.items():
        path = ci.ROOT / "scripts/ops" / filename
        need(Path(module.__file__).resolve() == path.resolve() and path.is_file()
             and not path.is_symlink() and blob(path.read_bytes()) == expected, "HELPER_ORIGIN")
    ci.git(ci.ROOT, "merge-base", "--is-ancestor", PIN, "HEAD")
    result = {}
    for path, expected in SOURCE_BLOBS.items():
        mode = "100644"
        entry = ci.git(ci.ROOT, "ls-tree", PIN, "--", path).decode().strip()
        need(entry == mode + " blob " + expected + "\t" + path, "SOURCE_TREE_ENTRY")
        result[path] = ci.git(ci.ROOT, "show", PIN + ":" + path)
    _sources(result)
    return result


def capture_inventory(query):
    """Capture fixed metadata without credentials, statistics, OIDs or sequence state."""
    def fixed(statement):
        return query("SET search_path=pg_catalog,public; SET timezone='UTC'; " + statement)

    identity = json.loads(fixed("SELECT jsonb_build_object('database',current_database(),"
        "'user',current_user,'session_user',session_user,'server_major',current_setting('server_version_num')::int/10000,"
        "'installation',(SELECT jsonb_agg(jsonb_build_array(id,mode) ORDER BY id) FROM public.crm_private_installation))"))
    need(identity == {"database": DATABASE, "user": OWNER, "session_user": OWNER,
                      "server_major": 16, "installation": [[1, "synthetic-v1"]]}, "DATABASE_IDENTITY")
    roles = json.loads(fixed("""SELECT COALESCE(jsonb_agg(jsonb_build_object(
      'name',rolname,'superuser',rolsuper,'inherit',rolinherit,'create_role',rolcreaterole,
      'create_db',rolcreatedb,'can_login',rolcanlogin,'replication',rolreplication,
      'bypass_rls',rolbypassrls,'connection_limit',rolconnlimit,'valid_until',rolvaliduntil,
      'config',rolconfig) ORDER BY rolname),'[]'::jsonb)
      FROM pg_roles WHERE rolname !~ '^pg_'"""))
    memberships = json.loads(fixed("""SELECT COALESCE(jsonb_agg(jsonb_build_object(
      'role',r.rolname,'member',m.rolname,'grantor',g.rolname,'admin',a.admin_option,
      'inherit',a.inherit_option,'set',a.set_option) ORDER BY r.rolname,m.rolname,g.rolname),'[]'::jsonb)
      FROM pg_auth_members a JOIN pg_roles r ON r.oid=a.roleid
      JOIN pg_roles m ON m.oid=a.member JOIN pg_roles g ON g.oid=a.grantor
      WHERE r.rolname !~ '^pg_' OR m.rolname !~ '^pg_'"""))
    database_acl = backup._acl("coalesce(d.datacl,acldefault('d',d.datdba))")
    database = json.loads(fixed("SELECT jsonb_build_object('owner',pg_get_userbyid(d.datdba),"
        "'acl'," + database_acl + ",'allow_connections',d.datallowconn,'connection_limit',d.datconnlimit,"
        "'template',d.datistemplate,'tablespace',s.spcname) FROM pg_database d"
        " JOIN pg_tablespace s ON s.oid=d.dattablespace WHERE d.datname=current_database()"))
    settings = json.loads(fixed("""SELECT COALESCE(jsonb_agg(jsonb_build_object(
      'database',CASE WHEN s.setdatabase=0 THEN 'ALL_DATABASES' ELSE d.datname END,
      'role',CASE WHEN s.setrole=0 THEN 'ALL_ROLES' ELSE r.rolname END,'settings',s.setconfig)
      ORDER BY CASE WHEN s.setdatabase=0 THEN 'ALL_DATABASES' ELSE d.datname END,
      CASE WHEN s.setrole=0 THEN 'ALL_ROLES' ELSE r.rolname END),'[]'::jsonb)
      FROM pg_db_role_setting s LEFT JOIN pg_database d ON d.oid=s.setdatabase
      LEFT JOIN pg_roles r ON r.oid=s.setrole
      WHERE s.setdatabase=0 OR d.datname=current_database()"""))
    auxiliary = json.loads(fixed("""SELECT jsonb_build_object(
      'sequences',COALESCE((SELECT jsonb_agg(jsonb_build_object(
        'name',n.nspname||'.'||c.relname,'type',format_type(s.seqtypid,NULL),
        'start',s.seqstart,'increment',s.seqincrement,'max',s.seqmax,'min',s.seqmin,
        'cache',s.seqcache,'cycle',s.seqcycle) ORDER BY n.nspname,c.relname)
        FROM pg_sequence s JOIN pg_class c ON c.oid=s.seqrelid JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE n.nspname IN ('public','crm_auto_service')),'[]'::jsonb),
      'indexes',COALESCE((SELECT jsonb_agg(jsonb_build_object(
        'name',n.nspname||'.'||i.relname,'table',n.nspname||'.'||t.relname,
        'definition',pg_get_indexdef(x.indexrelid),'valid',x.indisvalid,'ready',x.indisready,
        'live',x.indislive,'clustered',x.indisclustered,'replica_identity',x.indisreplident)
        ORDER BY n.nspname,i.relname)
        FROM pg_index x JOIN pg_class i ON i.oid=x.indexrelid JOIN pg_class t ON t.oid=x.indrelid
        JOIN pg_namespace n ON n.oid=t.relnamespace WHERE n.nspname IN ('public','crm_auto_service')),'[]'::jsonb),
      'relation_settings',COALESCE((SELECT jsonb_agg(jsonb_build_object(
        'name',n.nspname||'.'||c.relname,'kind',c.relkind,'owner',pg_get_userbyid(c.relowner),
        'persistence',c.relpersistence,'options',c.reloptions,'access_method',a.amname,
        'replica_identity',c.relreplident,'partition',c.relispartition,
        'tablespace',CASE WHEN c.reltablespace=0 THEN 'DATABASE_DEFAULT' ELSE s.spcname END)
        ORDER BY n.nspname,c.relname)
        FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace
        LEFT JOIN pg_am a ON a.oid=c.relam LEFT JOIN pg_tablespace s ON s.oid=c.reltablespace
        WHERE n.nspname IN ('public','crm_auto_service')),'[]'::jsonb),
      'types',COALESCE((SELECT jsonb_agg(jsonb_build_object(
        'name',n.nspname||'.'||t.typname,'kind',t.typtype,'category',t.typcategory,
        'owner',pg_get_userbyid(t.typowner),'defined',t.typisdefined,'not_null',t.typnotnull,
        'base',CASE WHEN t.typbasetype=0 THEN NULL ELSE format_type(t.typbasetype,t.typtypmod) END,
        'default',t.typdefault) ORDER BY n.nspname,t.typname)
        FROM pg_type t JOIN pg_namespace n ON n.oid=t.typnamespace
        WHERE n.nspname IN ('public','crm_auto_service')),'[]'::jsonb),
      'rules',COALESCE((SELECT jsonb_agg(jsonb_build_object(
        'table',n.nspname||'.'||c.relname,'name',r.rulename,'definition',pg_get_ruledef(r.oid,false))
        ORDER BY n.nspname,c.relname,r.rulename)
        FROM pg_rewrite r JOIN pg_class c ON c.oid=r.ev_class JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE n.nspname IN ('public','crm_auto_service')),'[]'::jsonb),
      'extensions',COALESCE((SELECT jsonb_agg(jsonb_build_object(
        'name',e.extname,'version',e.extversion,'owner',pg_get_userbyid(e.extowner),
        'schema',n.nspname,'relocatable',e.extrelocatable) ORDER BY e.extname)
        FROM pg_extension e JOIN pg_namespace n ON n.oid=e.extnamespace),'[]'::jsonb));"""))
    value = {"catalog": catalog.catalog_manifest(fixed, schemas=SCHEMAS),
             "security": backup.security_manifest(fixed), "roles": roles,
             "memberships": memberships, "database": database, "settings": settings,
             "auxiliary": auxiliary}
    need(catalog.security_constraints_match(value["security"], value["catalog"]), "CATALOG_BINDING")
    return value


def _shape(value, tables):
    security = value["security"]
    need({(item["name"], item["kind"]) for item in security["relations"]}
         == {(name, "r") for name in tables} | {(SEQUENCE, "S")}, "REFERENCE_RELATIONS")
    need(len(security["relations"]) == len(tables) + 1
         and {item["name"] for item in security["schemas"]} == set(SCHEMAS)
         and len(security["schemas"]) == 2, "REFERENCE_SCHEMAS")
    need({item["signature"] for item in security["functions"]} == FUNCTIONS
         and len(security["functions"]) == 9 and security["default_acls"] == 0, "REFERENCE_FUNCTIONS")
    need({item["name"] for item in value["roles"]} == set(ROLES)
         and len(value["roles"]) == 9 and not value["memberships"], "REFERENCE_ROLES")
    need([item["name"] for item in value["auxiliary"]["sequences"]] == [SEQUENCE], "REFERENCE_SEQUENCE")


def _pair_delta(source, target):
    """The independent target may differ only in the literal owner addon footprint."""
    source = json.loads(wire(source))
    projected = json.loads(wire(target))
    def old_column(item):
        return item["table"] not in OWNER_TABLES and not (
            item["table"] == ACCOUNT and item["name"] == MARKER)
    projected["catalog"]["columns"] = list(filter(old_column, projected["catalog"]["columns"]))
    projected["catalog"]["constraints"] = [x for x in projected["catalog"]["constraints"]
                                              if x["table"] not in OWNER_TABLES]
    security = projected["security"]
    security["relations"] = [x for x in security["relations"] if x["name"] not in OWNER_TABLES]
    security["columns"] = list(filter(old_column, security["columns"]))
    for key in ("constraints", "policies"):
        security[key] = [x for x in security[key] if x["table"] not in OWNER_TABLES]
    aux = projected["auxiliary"]
    new_indexes = {x["name"] for x in aux["indexes"] if x["table"] in OWNER_TABLES}
    aux["indexes"] = [x for x in aux["indexes"] if x["table"] not in OWNER_TABLES]
    aux["relation_settings"] = [x for x in aux["relation_settings"]
                                 if x["name"] not in set(OWNER_TABLES) | new_indexes]
    new_types = set(OWNER_TABLES) | {"public._" + x.split(".")[1] for x in OWNER_TABLES}
    aux["types"] = [x for x in aux["types"] if x["name"] not in new_types]
    need(projected == source, "REFERENCE_DELTA")


@dataclass(frozen=True)
class ReferencePair:
    pin: str
    source_json: str
    target_json: str
    delta_sql: str
    source_blobs: tuple


def references(query33, query35, source_files):
    """Seal independently bootstrapped references, never derived from the target DB."""
    delta = _sources(source_files)
    source, target = capture_inventory(query33), capture_inventory(query35)
    _shape(source, TABLES33)
    _shape(target, TABLES35)
    _pair_delta(source, target)
    return ReferencePair(PIN, wire(source), wire(target), delta, tuple(sorted(SOURCE_BLOBS.items())))


def _run(command, *, data=None, timeout=10):
    try:
        result = subprocess.run(command, input=data, capture_output=True, timeout=timeout)
    except Exception:
        raise MigrationDenied("PROCESS_FAILED") from None
    need(result.returncode == 0, "PROCESS_FAILED")
    return result.stdout


def _container(source_command):
    head, _ = ci.preflight()
    run, attempt = os.environ.get("GITHUB_RUN_ID", ""), os.environ.get("GITHUB_RUN_ATTEMPT", "")
    need(re.fullmatch(r"[1-9][0-9]*", run) and re.fullmatch(r"[1-9][0-9]*", attempt), "RUN_IDENTITY")
    need(type(source_command) is list and len(source_command) == 4
         and source_command[:3] == ["docker", "exec", "-i"]
         and re.fullmatch(r"[a-f0-9]{64}", source_command[3]), "CONTAINER_COMMAND")
    need(_run(["docker", "context", "show"]).decode().strip() == "default", "DOCKER_CONTEXT")
    context = json.loads(_run(["docker", "context", "inspect", "default"]))
    need(len(context) == 1 and context[0]["Endpoints"]["docker"]["Host"]
         in ("unix:///var/run/docker.sock", "unix:///run/docker.sock"), "DOCKER_SOCKET")
    inspection = json.loads(_run(["docker", "inspect", source_command[3]]))
    need(len(inspection) == 1, "CONTAINER_INSPECT")
    item = inspection[0]
    image = _run(["docker", "image", "inspect", "postgres:16", "--format", "{{.Id}}"]).decode().strip()
    need(re.fullmatch(r"sha256:[a-f0-9]{64}", image), "POSTGRES_IMAGE")
    labels = item["Config"].get("Labels") or {}
    need(item["Id"] == source_command[3] and item["State"]["Running"] is True
         and item["Name"].startswith("/imagine-owner-monitor-migration-" + run + "-" + attempt + "-")
         and all(labels.get("imagine.owner_monitor_migration." + key) == value
                 for key, value in (("head", head), ("run", run), ("attempt", attempt)))
         and item["Image"] == image and item["Config"]["Image"] == image
         and item["HostConfig"]["NetworkMode"] == "none"
         and not item["HostConfig"].get("PortBindings")
         and all(value in (None, []) for value in (item["NetworkSettings"].get("Ports") or {}).values())
         and not item["HostConfig"].get("Binds")
         and set(item["HostConfig"].get("Tmpfs") or {}) == {"/var/lib/postgresql/data"}
         and all(mount["Type"] == "tmpfs" and mount["Destination"] == "/var/lib/postgresql/data"
                 for mount in item.get("Mounts", []))
         and not item["HostConfig"].get("Privileged")
         and "no-new-privileges:true" in item["HostConfig"].get("SecurityOpt", []), "DISPOSABLE_CONTAINER")


class Transaction:
    """One bounded psql connection; context exit rolls back unless commit succeeds."""
    def __init__(self, source_command, *, lock_timeout_ms=2000, statement_timeout_ms=15000):
        need(type(lock_timeout_ms) is int and 100 <= lock_timeout_ms <= 10000
             and type(statement_timeout_ms) is int and 500 <= statement_timeout_ms <= 30000
             and lock_timeout_ms < statement_timeout_ms, "TIMEOUT_BOUNDS")
        self.source_command = list(source_command)
        self.lock_timeout_ms = lock_timeout_ms
        self.statement_timeout_ms = statement_timeout_ms
        self.application = "imagine-owner-monitor-migration-" + secrets.token_hex(12)
        self.backend_pid = None
        self.process = None
        self.committed = False
        self.closed = False
        self._pending = b""
        self._stderr = b""
        self.deadline = time.monotonic() + 120

    def _command(self, application):
        return self.source_command + ["env", "PGAPPNAME=" + application, "PGCONNECT_TIMEOUT=5",
            "psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-v", "VERBOSITY=sqlstate",
            "-h", "/var/run/postgresql", "-p", "5432", "-U", OWNER, "-d", DATABASE]

    def __enter__(self):
        _container(self.source_command)
        try:
            self.process = subprocess.Popen(self._command(self.application), stdin=subprocess.PIPE,
                stdout=subprocess.PIPE, stderr=subprocess.PIPE, start_new_session=True)
            value = json.loads(self.query("BEGIN ISOLATION LEVEL REPEATABLE READ;"
                "SET LOCAL search_path=pg_catalog,public; SET LOCAL timezone='UTC';"
                "SET LOCAL lock_timeout='" + str(self.lock_timeout_ms) + "ms';"
                "SET LOCAL statement_timeout='" + str(self.statement_timeout_ms) + "ms';"
                "SET LOCAL idle_in_transaction_session_timeout='45s';"
                "SELECT jsonb_build_object('pid',pg_backend_pid(),'user',current_user,"
                "'database',current_database(),'version',current_setting('server_version_num')::int/10000)"))
            need(value["user"] == OWNER and value["database"] == DATABASE and value["version"] == 16
                 and type(value["pid"]) is int and value["pid"] > 0, "SESSION_IDENTITY")
            self.backend_pid = value["pid"]
            return self
        except BaseException:
            self.close()
            raise

    def _sql_error(self):
        states = re.findall(rb"(?:ERROR|FATAL):\s+([0-9A-Z]{5})\b", self._stderr)
        state = states[-1] if states else b""
        raise MigrationDenied({b"55P03": "LOCK_TIMEOUT", b"57014": "STATEMENT_TIMEOUT"}.get(state, "SQL_FAILED"),
                              sqlstate=state.decode() if state else None)

    def query(self, statement):
        need(self.process is not None and not self.closed and not self.committed
             and isinstance(statement, str) and "\x00" not in statement
             and len(statement.encode()) <= 262144, "QUERY_STATE")
        marker = ("OWNER_MIGRATION_FRAME_" + secrets.token_hex(16)).encode()
        frame = statement.rstrip().rstrip(";") + ";\n\\echo " + marker.decode() + "\n"
        try:
            self.process.stdin.write(frame.encode())
            self.process.stdin.flush()
        except (BrokenPipeError, OSError):
            self._sql_error()
        deadline = min(self.deadline, time.monotonic() + self.statement_timeout_ms / 1000 + 5)
        with selectors.DefaultSelector() as selector:
            selector.register(self.process.stdout, selectors.EVENT_READ, "stdout")
            selector.register(self.process.stderr, selectors.EVENT_READ, "stderr")
            while True:
                token = marker + b"\n"
                if token in self._pending:
                    value, self._pending = self._pending.split(token, 1)
                    return value.decode("utf-8").strip()
                remaining = deadline - time.monotonic()
                need(remaining > 0, "CLIENT_TIMEOUT")
                ready = selector.select(remaining)
                need(bool(ready), "CLIENT_TIMEOUT")
                for key, _ in ready:
                    chunk = os.read(key.fileobj.fileno(), 65536)
                    if key.data == "stderr":
                        self._stderr = (self._stderr + chunk)[-8192:]
                        if not chunk:
                            selector.unregister(key.fileobj)
                    else:
                        if not chunk:
                            # Drain available diagnostics before mapping a closed SQLSTATE.
                            for other, _ in selector.select(0):
                                if other.data == "stderr":
                                    self._stderr = (self._stderr + os.read(other.fileobj.fileno(), 8192))[-8192:]
                            self._sql_error()
                        self._pending += chunk
                        need(len(self._pending) <= 8388608, "RESULT_BOUND")

    def commit(self):
        self.query("COMMIT")
        self.committed = True

    def close(self):
        if self.closed:
            return
        try:
            if self.process is not None:
                if self.process.poll() is None:
                    try:
                        self.process.stdin.write(b"\\q\n" if self.committed else b"ROLLBACK;\n\\q\n")
                        self.process.stdin.flush()
                    except (BrokenPipeError, OSError):
                        pass
                    try:
                        self.process.wait(timeout=3)
                    except subprocess.TimeoutExpired:
                        os.killpg(self.process.pid, signal.SIGTERM)
                        try:
                            self.process.wait(timeout=3)
                        except subprocess.TimeoutExpired:
                            os.killpg(self.process.pid, signal.SIGKILL)
                            self.process.wait(timeout=3)
                for pipe in (self.process.stdin, self.process.stdout, self.process.stderr):
                    pipe.close()
                # Killing docker's client alone does not prove its server session ended.
                predicate = ("datname='" + DATABASE + "' AND usename='" + OWNER
                             + "' AND application_name='" + self.application + "'")
                sql = ("SET search_path=pg_catalog; SET statement_timeout='7s';"
                       "SELECT pg_terminate_backend(pid,5000) FROM pg_stat_activity WHERE " + predicate + ";"
                       "SELECT count(*) FROM pg_stat_activity WHERE " + predicate + ";")
                output = _run(self._command(self.application + "-cleanup"), data=sql.encode()).decode().splitlines()
                need(bool(output) and output[-1] == "0", "BACKEND_CLEANUP")
        except Exception:
            raise MigrationDenied("CLEANUP_FAILED") from None
        finally:
            self.closed = True

    def __exit__(self, kind, value, traceback):
        self.close()


def _sequence_state(query):
    return json.loads(query("SELECT jsonb_build_object('last_value',last_value,'is_called',is_called) FROM " + SEQUENCE))


def _rows(query, tables, *, without_marker=False):
    parts = []
    for name in tables:
        row = "to_jsonb(t)"
        if without_marker and name == ACCOUNT:
            row += "-'" + MARKER + "'"
        parts.append("SELECT '" + name + "' AS name,jsonb_build_object('rows',count(*),'sha256',"
            "encode(sha256(convert_to(COALESCE(jsonb_agg(" + row + " ORDER BY (" + row + ")::text COLLATE \"C\"),"
            "'[]'::jsonb)::text,'UTF8')),'hex')) AS value FROM " + name + " t")
    return json.loads(query("SELECT jsonb_object_agg(name,value ORDER BY name) FROM (" + " UNION ALL ".join(parts) + ") data"))


def migrate(source_command, reference_pair, *, lock_timeout_ms=2000, statement_timeout_ms=15000):
    """Admit exact33 or exact35, apply only exact33, verify and commit one transaction."""
    need(type(reference_pair) is ReferencePair and reference_pair.pin == PIN
         and reference_pair.source_blobs == tuple(sorted(SOURCE_BLOBS.items())), "REFERENCE_PIN")
    need(reference_pair.delta_sql == _sources(pinned_sources()), "REFERENCE_BOOTSTRAP")
    source, target = json.loads(reference_pair.source_json), json.loads(reference_pair.target_json)
    _shape(source, TABLES33)
    _shape(target, TABLES35)
    _pair_delta(source, target)
    with Transaction(source_command, lock_timeout_ms=lock_timeout_ms,
                     statement_timeout_ms=statement_timeout_ms) as session:
        session.query("SELECT pg_advisory_xact_lock(" + ADVISORY_KEY + ")")
        session.query("LOCK TABLE public.crm_private_accounts IN ACCESS EXCLUSIVE MODE")
        before = capture_inventory(session.query)
        need(before in (source, target), "SCHEMA_DRIFT")
        apply = before == source
        tables = TABLES33 if apply else TABLES35
        rows_before = _rows(session.query, tables, without_marker=apply)
        sequence_before = _sequence_state(session.query)
        if apply:
            session.query(reference_pair.delta_sql)
        after = capture_inventory(session.query)
        need(after == target, "POSTCONDITION")
        rows_after = _rows(session.query, tables, without_marker=apply)
        sequence_after = _sequence_state(session.query)
        need(rows_after == rows_before, "DATA_CHANGED")
        need(sequence_after == sequence_before, "SEQUENCE_STATE_CHANGED")
        if apply:
            need(session.query("SELECT count(*) FROM " + ACCOUNT + " WHERE " + MARKER + "<>0") == "0",
                 "MARKER_BACKFILL")
            need(all(item["rows"] == 0 for item in _rows(session.query, OWNER_TABLES).values()), "NEW_TABLE_NOT_EMPTY")
        session.commit()
    need(session.closed and session.committed, "TRANSACTION_COMPLETION")
    return {"schema": "CRM_OWNER_MONITOR_INCREMENTAL_MIGRATION_V1", "result": "PASS",
            "action": "APPLIED" if apply else "ALREADY_PRESENT", "reference_source_sha": PIN,
            "tables_before": len(tables), "tables_after": 35, "roles": 9, "functions": 9, "sequences": 1,
            "preserved_tables": len(tables), "added_tables": 2 if apply else 0,
            "added_columns": 1 if apply else 0, "catalog_before_sha256": digest(before),
            "catalog_after_sha256": digest(after), "data_before_sha256": digest(rows_before),
            "data_after_sha256": digest(rows_after), "sequence_state_preserved": True,
            "exact_reference_comparison": True, "same_transaction_preflight_and_postcondition": True,
            "committed": True, "backend_cleanup_verified": True,
            "runtime_activation": False, "go_for_installation": False}
