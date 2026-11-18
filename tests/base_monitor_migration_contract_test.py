#!/usr/bin/env python3
"""Local source/metadata/admission boundaries; no PostgreSQL or CI execution."""
from __future__ import annotations

import copy
import json
import os
from pathlib import Path
import sys
import unittest
from unittest import mock

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "scripts/ops"))
try:
    import private_crm_base_monitor_migration as migration
finally:
    sys.path.pop(0)


class BaseMonitorMigrationContractTest(unittest.TestCase):
    def test_all_six_actual_source_files_are_bound_before_envelope_extraction(self):
        sources = {path: (ROOT / path).read_bytes() for path in migration.SOURCE_BLOBS}
        bodies = migration._sources(sources)
        self.assertEqual(len(bodies), 4)
        for path, body in zip(migration.SQL_INPUTS, bodies):
            prefix = sources[path].split(b"\\set ON_ERROR_STOP on\nBEGIN;\n", 1)[0]
            self.assertEqual(prefix + b"\\set ON_ERROR_STOP on\nBEGIN;\n" + body.encode() + b"COMMIT;\n",
                             sources[path])
        for path in sources:
            changed = dict(sources, **{path: sources[path] + b"\n"})
            with self.subTest(path=path), self.assertRaisesRegex(
                    migration.MigrationDenied, "^BASE_MONITOR_MIGRATION_SOURCE_BLOB$"):
                migration._sources(changed)
        for changed in ({k: v for k, v in sources.items() if k != migration.SQL_INPUTS[0]},
                        dict(sources, **{"infra/homologation/s2s/owner-drafts.sql": b"unexpected"})):
            with self.assertRaisesRegex(migration.MigrationDenied, "^BASE_MONITOR_MIGRATION_SOURCE_INVENTORY$"):
                migration._sources(changed)

    def test_import_from_another_origin_is_denied_before_any_git_or_database_access(self):
        migration._implementation()
        with mock.patch.object(migration.core, "__file__", str(ROOT / "untrusted-core.py")), \
                mock.patch.object(migration.ci, "git") as git:
            with self.assertRaisesRegex(migration.MigrationDenied, "^BASE_MONITOR_MIGRATION_IMPLEMENTATION_ORIGIN$"):
                migration.pinned_sources()
            git.assert_not_called()

    def inventories(self):
        # Small metadata fixture for the projection contract, not a database oracle.
        source = {
            "catalog": {"columns": [], "constraints": [], "database_collation": {"encoding": "UTF8"}},
            "security": {
                "schemas": [{"name": "public", "owner": "pg_database_owner", "acl": []}],
                "relations": [{"name": name, "kind": "r", "owner": "crm_homologation_owner",
                               "rls": True, "force_rls": True, "acl": []} for name in migration.TABLES25],
                "columns": [], "constraints": [], "policies": [], "triggers": [], "functions": [],
                "default_acls": 0,
            },
            "roles": [{"name": name} for name in migration.BASE_ROLES], "memberships": [],
            "database": {"owner": "crm_homologation_owner"}, "settings": [],
            "auxiliary": {"sequences": [], "indexes": [], "relation_settings": [],
                          "types": [], "rules": [], "extensions": []},
        }
        target = copy.deepcopy(source)
        security = target["security"]
        security["schemas"][0]["acl"].append(["pg_database_owner", migration.READER, "USAGE", False])
        security["schemas"].append({"name": "crm_auto_service", "owner": migration.READER, "acl": []})
        for item in security["relations"]:
            if item["name"] in migration.PUBLIC_READ_TABLES:
                item["acl"].append(["crm_homologation_owner", migration.READER, "SELECT", False])
        security["relations"] += [{"name": name, "kind": "r", "owner": migration.READER, "acl": []}
                                   for name in migration.ADDON_TABLES]
        security["relations"].append({"name": migration.SEQUENCE, "kind": "S", "owner": migration.READER, "acl": []})
        security["functions"] = [{"signature": name} for name in sorted(migration.FUNCTIONS)]
        target["roles"] += [{"name": name} for name in migration.ADDON_ROLES]
        target["auxiliary"]["sequences"] = [{"name": migration.SEQUENCE}]
        return source, target

    def test_projection_accepts_only_the_declared_addon_and_seven_public_grants(self):
        source, target = self.inventories()
        original = copy.deepcopy((source, target))
        migration._pair_delta(source, target)
        self.assertEqual((source, target), original)

    def test_projection_refuses_extra_privilege_grant_option_and_wrong_grantor(self):
        for mutation, label in (("extra", "REFERENCE_DELTA"),
                                ("delegation", "REFERENCE_PUBLIC_GRANT"),
                                ("grantor", "REFERENCE_PUBLIC_GRANT")):
            source, target = self.inventories()
            relation = next(x for x in target["security"]["relations"]
                            if x["name"] == migration.PUBLIC_READ_TABLES[0])
            if mutation == "extra":
                relation["acl"].append(["crm_homologation_owner", migration.READER, "DELETE", False])
            elif mutation == "delegation":
                relation["acl"][0][3] = True
            else:
                relation["acl"][0][0] = "unreviewed_grantor"
            with self.subTest(mutation=mutation), self.assertRaisesRegex(
                    migration.MigrationDenied, "^BASE_MONITOR_MIGRATION_" + label + "$"):
                migration._pair_delta(source, target)

    def test_projection_does_not_normalize_other_public_security_metadata(self):
        source, target = self.inventories()
        target["security"]["relations"][0]["force_rls"] = False
        with self.assertRaisesRegex(migration.MigrationDenied, "^BASE_MONITOR_MIGRATION_REFERENCE_DELTA$"):
            migration._pair_delta(source, target)

    def test_component_scope_keeps_core_admission_and_refuses_neighbor_resources(self):
        identity = "a" * 64
        inspection = {"Name": "/imagine-owner-monitor-migration-123-1-base-monitor-fixture",
                      "Config": {"Labels": {migration.SCOPE_LABEL: migration.SCOPE}}}
        with mock.patch.dict(os.environ, {"GITHUB_RUN_ID": "123", "GITHUB_RUN_ATTEMPT": "1"}, clear=True), \
                mock.patch.object(migration.core, "_container") as admit, \
                mock.patch.object(migration.core, "_run", side_effect=lambda *a, **k: json.dumps([inspection]).encode()) as run:
            command = ["docker", "exec", "-i", identity]
            migration._component_container(command)
            admit.assert_called_once_with(command)
            nominal = copy.deepcopy(inspection)
            for mutate in ("name", "label"):
                inspection.clear(); inspection.update(copy.deepcopy(nominal))
                if mutate == "name":
                    inspection["Name"] = "/imagine-owner-monitor-migration-123-1-owner-fixture"
                else:
                    inspection["Config"]["Labels"][migration.SCOPE_LABEL] = "unreviewed-scope"
                with self.subTest(mutate=mutate), self.assertRaisesRegex(
                        migration.MigrationDenied, "^BASE_MONITOR_MIGRATION_COMPONENT_RESOURCE_SCOPE$"):
                    migration._component_container(command)
            run.reset_mock()
            admit.side_effect = migration.core.MigrationDenied("DISPOSABLE_CONTAINER")
            with self.assertRaises(migration.core.MigrationDenied):
                migration._component_container(command)
            run.assert_not_called()


if __name__ == "__main__":
    unittest.main()
