#!/usr/bin/env python3
"""F252 pure negative-contract tests; no VPS, network, Docker or credentials."""
from __future__ import annotations

import importlib.util
from pathlib import Path
import unittest

SCRIPT = Path(__file__).with_name("private-crm-upgrade-f252.py")
spec = importlib.util.spec_from_file_location("private_crm_upgrade_f252", SCRIPT)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
OLD = "a" * 40
NEW = "b" * 40


def approved() -> dict:
    return {
        "published_sha": OLD,
        "candidate_sha": NEW,
        "scope": "private-synthetic-only",
        "authorized_by": "responsavel-imagine",
        "manual_private_release": True,
        "reviews": {
            "security": {"reviewer": "revisor-seg", "decision": "approved",
                         "evidence": "ISSUE-SEG-APPROVED"},
            "operations": {"reviewer": "revisor-ops", "decision": "approved",
                           "evidence": "ISSUE-OPS-APPROVED"},
        },
    }


def checks() -> dict:
    runs = [
        {"name": "real-private-http", "status": "completed", "conclusion": "success"},
        {"name": "Exact-SHA private maintenance scope / no deploy / manual review",
         "status": "completed", "conclusion": "success"},
        {"name": "private-upgrade-static-policy", "status": "completed", "conclusion": "success"},
        {"name": "private-f253-post-switch-canary", "status": "completed", "conclusion": "success"},
    ] + [{"name": "other-required-check-" + str(n), "status": "completed",
          "conclusion": "success"} for n in range(19)]
    return {"total_count": 23, "check_runs": runs}


def workflow() -> dict:
    return {"head_sha": NEW, "status": "completed", "conclusion": "success",
            "workflow_id": module.RUN_WORKFLOW_ID,
            "head_repository": {"full_name": "rafhael100/imagine-crm"}}


class UpgradeGuardTests(unittest.TestCase):
    def reject(self, callback):
        with self.assertRaises(RuntimeError):
            callback()

    def test_image_tag_exact_single_and_immutable(self):
        text = "OTHER='x'\nCRM_PRIVATE_IMAGE_TAG='" + OLD + "'\n"
        self.assertEqual(OLD, module.image_tag(text))
        self.assertEqual("OTHER='x'\nCRM_PRIVATE_IMAGE_TAG='" + NEW + "'\n",
                         module.switch_tag(text, OLD, NEW))
        self.reject(lambda: module.image_tag(text * 2))
        self.reject(lambda: module.image_tag("CRM_PRIVATE_IMAGE_TAG='candidate'"))
        self.reject(lambda: module.switch_tag(text, NEW, OLD))
        self.reject(lambda: module.switch_tag(text, OLD, "main"))

    def test_approval_requires_exact_release_two_distinct_reviews(self):
        module.validate_approval(approved(), OLD, NEW)
        changes = [
            ("candidate_sha", OLD),
            ("published_sha", NEW),
            ("scope", "production"),
            ("authorized_by", ""),
            ("manual_private_release", False),
        ]
        for key, value in changes:
            candidate = approved()
            candidate[key] = value
            with self.subTest(key=key):
                self.reject(lambda: module.validate_approval(candidate, OLD, NEW))
        for field in ("security", "operations"):
            candidate = approved()
            del candidate["reviews"][field]
            self.reject(lambda: module.validate_approval(candidate, OLD, NEW))
            for key, value in (("decision", "pending"), ("reviewer", ""),
                               ("evidence", "")):
                candidate = approved()
                candidate["reviews"][field][key] = value
                with self.subTest(field=field, key=key):
                    self.reject(lambda: module.validate_approval(candidate, OLD, NEW))
        candidate = approved()
        candidate["reviews"]["operations"]["reviewer"] = "revisor-seg"
        self.reject(lambda: module.validate_approval(candidate, OLD, NEW))

    def test_ci_exact_sha_and_all_required_checks(self):
        module.validate_checks(workflow(), checks(), NEW)
        for key, value in (("head_sha", OLD), ("status", "queued"),
                           ("conclusion", "failure"), ("workflow_id", 10)):
            run = workflow()
            run[key] = value
            with self.subTest(key=key):
                self.reject(lambda: module.validate_checks(run, checks(), NEW))
        changed = workflow()
        changed["head_repository"]["full_name"] = "some-other/repo"
        self.reject(lambda: module.validate_checks(changed, checks(), NEW))
        for key, value in (("conclusion", "failure"), ("status", "in_progress"),
                           ("name", "unknown")):
            status = checks()
            status["check_runs"][2][key] = value
            with self.subTest(check=key):
                self.reject(lambda: module.validate_checks(workflow(), status, NEW))
        status = checks()
        status["total_count"] = 101
        self.reject(lambda: module.validate_checks(workflow(), status, NEW))
        status = checks()
        status["check_runs"].pop()
        status["total_count"] = 21
        self.reject(lambda: module.validate_checks(workflow(), status, NEW))

    def test_f254_immutable_nginx_snapshot_under_https_401(self):
        """A protected HTTPS 401 is insufficient when Nginx bytes change."""
        marker={
            "crm_config_sha256": "a"*64,
            "shared_after_sha256": "b"*64,
        }
        module.require_unchanged_ingress(
            marker,"a"*64,"b"*64,
            crm_enabled=True,points_to_expected_config=True,
        )
        for bad_crm,bad_shared,enabled,target in [
            ("c"*64,"b"*64,True,True),
            ("a"*64,"c"*64,True,True),
            ("a"*64,"b"*64,False,True),
            ("a"*64,"b"*64,True,False),
        ]:
            with self.subTest(hash_or_link=(bad_crm,bad_shared,enabled,target)):
                self.reject(lambda:module.require_unchanged_ingress(
                    marker,bad_crm,bad_shared,crm_enabled=enabled,
                    points_to_expected_config=target))
        for defective in [
            {},
            {"crm_config_sha256": "a"*64},
            {"crm_config_sha256": "not-a-digest", "shared_after_sha256": "b"*64},
            {"crm_config_sha256": "A"*64, "shared_after_sha256": "b"*64},
        ]:
            self.reject(lambda item=defective:module.require_unchanged_ingress(
                item,"a"*64,"b"*64,crm_enabled=True,
                points_to_expected_config=True))

    def test_f254_release_requires_nginx_snapshot_before_marking_success(self):
        source=SCRIPT.read_text()
        apply_region=source[source.index("def apply("):source.index("def rollback(")]
        rollback_region=source[source.index("def rollback("):source.index("def main(")]
        self.assertIn("verify_ingress_snapshot(marker)",apply_region)
        self.assertLess(
            apply_region.index("verify_ingress_snapshot(marker)"),
            apply_region.index("updated = dict(marker, sha=candidate"))
        self.assertIn("verify_ingress_snapshot(marker)",rollback_region)
        self.assertLess(
            rollback_region.index("verify_ingress_snapshot(marker)"),
            rollback_region.index("restored = dict(marker, sha=previous"))
        self.assertGreaterEqual(
            source.count("verify_ingress_snapshot(marker)"),5)

    def test_operator_only_no_automated_ssh_or_production(self):
        source = SCRIPT.read_text()
        for forbidden in ("paramiko", "subprocess.Popen(['ssh'", "docker compose down",
                          "crm-production", "force=True", "os.system("):
            self.assertNotIn(forbidden, source)
        self.assertIn("commercial_production_go", source)
        self.assertIn("MANUAL_APPROVAL_REQUIRED", source)
        self.assertIn("backup_and_restore", source)
        self.assertIn("--untracked-files=all", source)
        self.assertIn("review_access", source)
        self.assertIn("verify_candidate_canary()", source)
        self.assertIn("private-crm-f253-canary.py", source)


if __name__ == "__main__":
    unittest.main(verbosity=2)
