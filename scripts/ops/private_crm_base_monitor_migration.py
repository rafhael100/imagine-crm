#!/usr/bin/env python3
"""Closed synthetic base25 -> monitor33 migration using the unchanged CI core.

References are built by the trusted harness from six immutable source files.
This API does not authenticate externally supplied reference JSON or install a
profile on an operational host. Existing owner33->35 admission stays unchanged.
"""
from __future__ import annotations

from dataclasses import dataclass
import hashlib
import json
import os
from pathlib import Path
import re

import private_crm_owner_monitor_migration as core
import private_crm_owner_drafts_ci as ci

ROOT = Path(__file__).resolve().parents[2]
PIN = "96dbf1aed2d1cf3237bc3631c4594540a0168d2a"
SOURCE_BLOBS = {
    "infra/homologation/schema.sql": "9dde1bb86d496083ccd163225d8dd0c79b391cdb",
    "infra/homologation/20-runtime-roles.sh": "ae5fb2f964cc672d681f4108c1aff7fff5ea5682",
    "infra/homologation/s2s/install-candidate.sql": "3cafbf27be3e3968d401dbf98beac0e63fd27bea",
    "infra/homologation/s2s/outbound-candidate.sql": "4166eeb7d8dd2eabec9392e94dff4b392650de52",
    "infra/homologation/s2s/execution-feedback-candidate.sql": "4804870196ad820007f616d260efb8ea9b64dc0c",
    "infra/homologation/s2s/execution-monitor-candidate.sql": "d6dd92bee6ae35ded0aa6655dbdbe858b0d9f181",
}
SQL_INPUTS = tuple(SOURCE_BLOBS)[2:]
IMPLEMENTATION_BLOBS = {
    "private_crm_owner_monitor_migration.py": "cb6cc4eb0d114cabb47bfe44a685d2ce8d76b250",
    "private_crm_backup_catalog.py": "d480c3cbaac6dba34dd5558a37af406ada3d6265",
    "private_crm_backup_contract.py": "8780863e22006bda309a1071827f96b5d113ddfa",
}
TABLES25 = core.BASE_TABLES
TABLES33 = core.TABLES33
ADDON_TABLES = core.backup.S2S_TABLES + core.backup.EXECUTION_TABLES
BASE_ROLES = core.backup.BASE_ROLES
ADDON_ROLES = core.backup.S2S_ROLES + core.backup.EXECUTION_ROLES
FUNCTIONS = core.FUNCTIONS
SEQUENCE = core.SEQUENCE
READER = "crm_auto_reader_owner"
PUBLIC_READ_TABLES = tuple("public." + name for name in (
    "crm_private_installation", "crm_private_automation_runs", "crm_private_preferences",
    "crm_private_events", "crm_private_trials", "crm_private_leads",
))
SCOPE_LABEL = "imagine.base_monitor_migration.scope"
SCOPE = "base25-monitor33-v1"
Transaction = core.Transaction


class MigrationDenied(Exception):
    def __init__(self, label, *, sqlstate=None):
        label = label if isinstance(label, str) and re.fullmatch(r"[A-Z0-9_]{1,80}", label) else "FAILED"
        self.code = "BASE_MONITOR_MIGRATION_" + label
        self.sqlstate = sqlstate if isinstance(sqlstate, str) and re.fullmatch(r"[0-9A-Z]{5}", sqlstate) else None
        super().__init__(self.code)


def need(condition, label):
    if not condition:
        raise MigrationDenied(label)


def _shared_failure(error):
    prefix = "OWNER_MONITOR_MIGRATION_"
    label = error.code[len(prefix):] if type(error.code) is str and error.code.startswith(prefix) else "SHARED_CORE_DENIED"
    return MigrationDenied(label, sqlstate=error.sqlstate)


def blob(raw):
    return hashlib.sha1(b"blob " + str(len(raw)).encode() + b"\0" + raw).hexdigest()


def _implementation():
    modules = {
        "private_crm_owner_monitor_migration.py": core,
        "private_crm_backup_catalog.py": core.catalog,
        "private_crm_backup_contract.py": core.backup,
    }
    for filename, module in modules.items():
        path = ROOT / "scripts/ops" / filename
        need(Path(module.__file__).resolve() == path.resolve() and path.is_file()
             and not path.is_symlink() and blob(path.read_bytes()) == IMPLEMENTATION_BLOBS[filename],
             "IMPLEMENTATION_ORIGIN")
    guard = ROOT / "scripts/ops/private_crm_owner_drafts_ci.py"
    need(core.ci is ci and ci.ROOT == ROOT and Path(ci.__file__).resolve() == guard.resolve()
         and guard.is_file() and not guard.is_symlink(), "CI_GUARD_ORIGIN")


def _sources(source_files):
    need(type(source_files) is dict and set(source_files) == set(SOURCE_BLOBS), "SOURCE_INVENTORY")
    for path, expected in SOURCE_BLOBS.items():
        need(type(source_files[path]) is bytes and blob(source_files[path]) == expected, "SOURCE_BLOB")
    bodies = []
    for path in SQL_INPUTS:
        raw = source_files[path]
        envelope = b"\\set ON_ERROR_STOP on\nBEGIN;\n"
        need(raw.count(envelope) == 1 and raw.endswith(b"COMMIT;\n"), "SOURCE_ENVELOPE")
        # Exact reviewed blobs make this an outer-envelope extraction only.
        body = raw.split(envelope, 1)[1][:-len(b"COMMIT;\n")]
        bodies.append(body.decode("utf-8"))
    return tuple(bodies)


def pinned_sources():
    """Load six reviewed files; the owner bootstrap is not an input to this delta."""
    _implementation()
    ci.git(ROOT, "merge-base", "--is-ancestor", PIN, "HEAD")
    sources = {}
    for path, expected in SOURCE_BLOBS.items():
        entry = ci.git(ROOT, "ls-tree", PIN, "--", path).decode().strip()
        need(entry == "100644 blob " + expected + "\t" + path, "SOURCE_TREE_ENTRY")
        sources[path] = ci.git(ROOT, "show", PIN + ":" + path)
    _sources(sources)
    return sources


def capture_inventory(query):
    _implementation()
    try:
        return core.capture_inventory(query)
    except core.MigrationDenied as error:
        raise _shared_failure(error) from None


def _shape(value, *, installed):
    tables = TABLES33 if installed else TABLES25
    roles = BASE_ROLES + ADDON_ROLES if installed else BASE_ROLES
    schemas = {"public", "crm_auto_service"} if installed else {"public"}
    functions = FUNCTIONS if installed else set()
    relations = {(name, "r") for name in tables} | ({(SEQUENCE, "S")} if installed else set())
    security = value["security"]
    need({(item["name"], item["kind"]) for item in security["relations"]} == relations
         and len(security["relations"]) == len(relations), "REFERENCE_RELATIONS")
    need({item["name"] for item in security["schemas"]} == schemas
         and len(security["schemas"]) == len(schemas), "REFERENCE_SCHEMAS")
    need({item["signature"] for item in security["functions"]} == functions
         and len(security["functions"]) == len(functions)
         and security["default_acls"] == 0, "REFERENCE_FUNCTIONS")
    need({item["name"] for item in value["roles"]} == set(roles)
         and len(value["roles"]) == len(roles) and not value["memberships"], "REFERENCE_ROLES")
    need([item["name"] for item in value["auxiliary"]["sequences"]]
         == ([SEQUENCE] if installed else []), "REFERENCE_SEQUENCE")
    if installed:
        for role in value["roles"]:
            if role["name"] in ADDON_ROLES:
                need(not any(role[key] for key in (
                    "can_login", "superuser", "bypass_rls", "create_role", "create_db", "replication")),
                    "REFERENCE_ACTIVE_SERVICE_ROLE")


def _remove_grant(item, privilege):
    expected = [item["owner"], READER, privilege, False]
    need(item["acl"].count(expected) == 1, "REFERENCE_PUBLIC_GRANT")
    item["acl"].remove(expected)


def _pair_delta(source, target):
    """Project only the eight-table addon and its seven literal public grants."""
    projected = json.loads(core.wire(target))
    for key in ("columns", "constraints"):
        projected["catalog"][key] = [x for x in projected["catalog"][key] if x["table"] not in ADDON_TABLES]
    security = projected["security"]
    security["schemas"] = [x for x in security["schemas"] if x["name"] != "crm_auto_service"]
    security["relations"] = [x for x in security["relations"] if x["name"] not in set(ADDON_TABLES) | {SEQUENCE}]
    for key in ("columns", "constraints", "policies", "triggers"):
        security[key] = [x for x in security[key] if x["table"] not in ADDON_TABLES]
    security["functions"] = [x for x in security["functions"] if x["signature"] not in FUNCTIONS]
    projected["roles"] = [x for x in projected["roles"] if x["name"] not in ADDON_ROLES]
    auxiliary = projected["auxiliary"]
    auxiliary["sequences"] = [x for x in auxiliary["sequences"] if x["name"] != SEQUENCE]
    new_indexes = {x["name"] for x in auxiliary["indexes"] if x["table"] in ADDON_TABLES}
    auxiliary["indexes"] = [x for x in auxiliary["indexes"] if x["table"] not in ADDON_TABLES]
    auxiliary["relation_settings"] = [x for x in auxiliary["relation_settings"]
                                       if x["name"] not in set(ADDON_TABLES) | {SEQUENCE} | new_indexes]
    new_types = set(ADDON_TABLES) | {"crm_auto_service._" + x.split(".")[1] for x in ADDON_TABLES}
    auxiliary["types"] = [x for x in auxiliary["types"] if x["name"] not in new_types]
    public = [x for x in security["schemas"] if x["name"] == "public"]
    need(len(public) == 1, "REFERENCE_PUBLIC_SCHEMA")
    _remove_grant(public[0], "USAGE")
    changed = [x for x in security["relations"] if x["name"] in PUBLIC_READ_TABLES]
    need(len(changed) == 6 and {x["name"] for x in changed} == set(PUBLIC_READ_TABLES), "REFERENCE_PUBLIC_TABLES")
    for item in changed:
        _remove_grant(item, "SELECT")
    need(projected == source, "REFERENCE_DELTA")


@dataclass(frozen=True)
class ReferencePair:
    pin: str
    source_json: str
    target_json: str
    delta_sql: tuple
    source_blobs: tuple
    implementation_blobs: tuple


def references(query25, query33, source_files):
    """Build an internal pair from independent pinned reference clusters."""
    _implementation()
    delta = _sources(source_files)
    source, target = capture_inventory(query25), capture_inventory(query33)
    _shape(source, installed=False)
    _shape(target, installed=True)
    _pair_delta(source, target)
    return ReferencePair(PIN, core.wire(source), core.wire(target), delta,
                         tuple(sorted(SOURCE_BLOBS.items())), tuple(sorted(IMPLEMENTATION_BLOBS.items())))


def _component_container(source_command):
    # Keep every core check, then narrow resource membership for this rehearsal.
    core._container(source_command)
    value = json.loads(core._run(["docker", "inspect", source_command[3]]))[0]
    prefix = ("/imagine-owner-monitor-migration-" + os.environ["GITHUB_RUN_ID"]
              + "-" + os.environ["GITHUB_RUN_ATTEMPT"] + "-base-monitor-")
    need(value["Name"].startswith(prefix)
         and (value["Config"].get("Labels") or {}).get(SCOPE_LABEL) == SCOPE,
         "COMPONENT_RESOURCE_SCOPE")


def migrate(source_command, reference_pair, *, lock_timeout_ms=2000, statement_timeout_ms=15000):
    """Admit exact25 or exact33; all four literal bodies share one transaction."""
    try:
        return _migrate(source_command, reference_pair, lock_timeout_ms=lock_timeout_ms,
                        statement_timeout_ms=statement_timeout_ms)
    except core.MigrationDenied as error:
        raise _shared_failure(error) from None


def _migrate(source_command, reference_pair, *, lock_timeout_ms, statement_timeout_ms):
    need(type(reference_pair) is ReferencePair and reference_pair.pin == PIN
         and reference_pair.source_blobs == tuple(sorted(SOURCE_BLOBS.items()))
         and reference_pair.implementation_blobs == tuple(sorted(IMPLEMENTATION_BLOBS.items())), "REFERENCE_PIN")
    need(reference_pair.delta_sql == _sources(pinned_sources()), "REFERENCE_BOOTSTRAP")
    source, target = json.loads(reference_pair.source_json), json.loads(reference_pair.target_json)
    _shape(source, installed=False)
    _shape(target, installed=True)
    _pair_delta(source, target)
    _component_container(source_command)
    with Transaction(source_command, lock_timeout_ms=lock_timeout_ms,
                     statement_timeout_ms=statement_timeout_ms) as session:
        session.query("SELECT pg_advisory_xact_lock(" + core.ADVISORY_KEY + ")")
        session.query("LOCK TABLE ONLY public.crm_private_accounts IN ACCESS EXCLUSIVE MODE")
        before = capture_inventory(session.query)
        need(before in (source, target), "SCHEMA_DRIFT")
        apply = before == source
        tables = TABLES25 if apply else TABLES33
        rows_before = core._rows(session.query, tables)
        sequence_before = None if apply else core._sequence_state(session.query)
        if apply:
            for statement in reference_pair.delta_sql:
                session.query(statement)
        after = capture_inventory(session.query)
        need(after == target, "POSTCONDITION")
        rows_after = core._rows(session.query, tables)
        need(rows_after == rows_before, "DATA_CHANGED")
        sequence_after = core._sequence_state(session.query)
        if apply:
            need(sequence_after == {"last_value": 1, "is_called": False}, "NEW_SEQUENCE_STATE")
            need(all(item["rows"] == 0 for item in core._rows(session.query, ADDON_TABLES).values()),
                 "NEW_TABLE_NOT_EMPTY")
        else:
            need(sequence_after == sequence_before, "SEQUENCE_STATE_CHANGED")
        session.commit()
    need(session.closed and session.committed, "TRANSACTION_COMPLETION")
    return {
        "schema": "CRM_BASE_MONITOR_INCREMENTAL_MIGRATION_V1", "result": "PASS",
        "action": "APPLIED" if apply else "ALREADY_PRESENT", "reference_source_sha": PIN,
        "tables_before": len(tables), "tables_after": 33, "preserved_tables": len(tables),
        "roles_before": 5 if apply else 9, "roles_after": 9, "functions_after": 9,
        "sequences_before": 0 if apply else 1, "sequences_after": 1,
        "added_tables": 8 if apply else 0, "added_roles": 4 if apply else 0,
        "added_functions": 9 if apply else 0, "added_columns": 0,
        "sequence_created_initial": apply, "sequence_state_preserved": not apply,
        "catalog_before_sha256": core.digest(before), "catalog_after_sha256": core.digest(after),
        "data_before_sha256": core.digest(rows_before), "data_after_sha256": core.digest(rows_after),
        "exact_reference_comparison": True, "same_transaction_preflight_and_postcondition": True,
        "committed": True, "backend_cleanup_verified": True,
        "runtime_activation": False, "go_for_installation": False,
    }
