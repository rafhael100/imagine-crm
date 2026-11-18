#!/usr/bin/env python3
"""F252 guards and local Git/CLI regression; no VPS, network, Docker or credentials."""
from __future__ import annotations

import importlib.util
import json
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest import mock

SCRIPT = Path(__file__).with_name("private-crm-upgrade-f252.py")
spec = importlib.util.spec_from_file_location("private_crm_upgrade_f252", SCRIPT)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
CLASSIFIER = SCRIPT.with_name("private-crm-release-preflight.py")
classifier_spec = importlib.util.spec_from_file_location("f252_original_classifier", CLASSIFIER)
classifier = importlib.util.module_from_spec(classifier_spec)
classifier_spec.loader.exec_module(classifier)
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


def f251_fixture(blocked: bool = True) -> dict:
    changes = [("M", "app/Homologation/Controller.php")]
    if blocked:
        changes.append(("A", "routes/web.php"))
    report = classifier.classify(changes)
    return dict(report, published_sha=OLD, candidate_sha=NEW)


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

    def rejected_preflight_process(self, outcome) -> str:
        """Run the actual caller; replace only external process outcomes."""
        outcomes = [
            subprocess.CompletedProcess([], 0, NEW + "\n", ""),
            subprocess.CompletedProcess([], 0, "", ""),
            subprocess.CompletedProcess([], 0, "", ""),
            outcome,
        ]
        with mock.patch.object(module.subprocess, "run", side_effect=outcomes) as process:
            with self.assertRaises(RuntimeError) as caught:
                module.preflight(OLD, NEW)
        self.assertEqual([
            ["git", "rev-parse", "HEAD"],
            ["git", "diff", "--quiet", "HEAD"],
            ["git", "status", "--porcelain", "--untracked-files=all"],
            ["python3", "scripts/ops/private-crm-release-preflight.py", OLD, NEW],
        ], [call.args[0] for call in process.call_args_list])
        return str(caught.exception)

    def test_f251_blocked_report_preserves_only_validated_diagnostic(self):
        report = f251_fixture()
        report["requires"] = ["UNTRUSTED_REPORT_TEXT_SENTINEL"]
        message = self.rejected_preflight_process(subprocess.CompletedProcess(
            [], 1, json.dumps(report), "PRIVATE_STDERR_SENTINEL"))
        prefix = "F251 private-review scope rejected. Validated report: "
        self.assertTrue(message.startswith(prefix))
        diagnostic = json.loads(message[len(prefix):])
        self.assertEqual({
            "status": "BLOCKED", "published_sha": OLD, "candidate_sha": NEW,
            "files_changed": 2, "blocked": ["routes/web.php"],
            "deploy_approved": False, "no_deploy_executed": True,
        }, diagnostic)
        self.assertNotIn("PRIVATE_STDERR_SENTINEL", message)
        self.assertNotIn("UNTRUSTED_REPORT_TEXT_SENTINEL", message)

    def test_f251_malformed_or_mismatched_output_remains_generic_and_blocked(self):
        cases = [
            ("PRIVATE_STDOUT_SENTINEL", 1),
            (json.dumps([]), 1),
            (json.dumps(f251_fixture()), 0),
            (json.dumps(f251_fixture()), 2),
            (json.dumps(f251_fixture(False)), 1),
            ('{"status":"PRIVATE_STDOUT_SENTINEL",' + json.dumps(f251_fixture())[1:], 1),
            (" " * 65_537, 1),
        ]
        for key, value in (
            ("published_sha", NEW), ("candidate_sha", OLD),
            ("deploy_approved", True), ("deploy_approved", "false"),
            ("no_deploy_executed", False), ("no_deploy_executed", 1),
            ("status", "APPROVED"), ("files_changed", True), ("files_changed", -1),
            ("blocked", []), ("blocked", "PRIVATE_STDOUT_SENTINEL"),
            ("blocked", ["/srv/private/PRIVATE_STDOUT_SENTINEL"]),
            ("blocked", ["../PRIVATE_STDOUT_SENTINEL"]),
            ("blocked", ["routes/web.php\nPRIVATE_STDOUT_SENTINEL"]),
            ("blocked", ["routes/web.php", "routes/web.php"]),
            ("runtime_review_files", []), ("reviewed_scope", ["PRIVATE_STDOUT_SENTINEL"]),
            ("requires", [False]), ("unexpected", "PRIVATE_STDOUT_SENTINEL"),
        ):
            report = f251_fixture()
            report[key] = value
            cases.append((json.dumps(report), 1))
        for index, (output, code) in enumerate(cases):
            with self.subTest(case=index):
                message = self.rejected_preflight_process(subprocess.CompletedProcess(
                    [], code, output, "PRIVATE_STDERR_SENTINEL"))
                self.assertEqual(
                    "F251 report unavailable or invalid; private release remains blocked.", message)

    def test_f251_process_error_does_not_expose_exception_text(self):
        message = self.rejected_preflight_process(OSError("PRIVATE_PROCESS_ERROR_SENTINEL"))
        self.assertEqual("F251 report unavailable or invalid; private release remains blocked.", message)

    def test_actual_f251_cli_refusal_survives_actual_f252_caller(self):
        """Local Git/Python only; no host state, approvals, Docker or network."""
        with tempfile.TemporaryDirectory(prefix="f252-report-") as temporary:
            root = Path(temporary)

            def git(*args):
                result = subprocess.run([
                    "git", "-c", "user.name=F252 synthetic test", "-c", "user.email=f252@example.test",
                    "-c", "commit.gpgsign=false", "-c", "core.hooksPath=/dev/null", *args,
                ], cwd=root, stdin=subprocess.DEVNULL, stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE, text=True)
                self.assertEqual(0, result.returncode, "Synthetic Git fixture failed.")
                return result.stdout.strip()

            git("init", "--quiet", "--template=", "--initial-branch=synthetic")
            target = root / "scripts/ops/private-crm-release-preflight.py"
            target.parent.mkdir(parents=True)
            target.write_bytes(CLASSIFIER.read_bytes())
            controller = root / "app/Homologation/Controller.php"
            controller.parent.mkdir(parents=True)
            controller.write_text("<?php // synthetic baseline\n")
            git("add", "."); git("commit", "--quiet", "-m", "synthetic baseline")
            published = git("rev-parse", "HEAD")
            controller.write_text("<?php // synthetic reviewable change\n")
            git("add", "."); git("commit", "--quiet", "-m", "synthetic allowed diff")
            with mock.patch.object(module, "ROOT", root):
                self.assertIsNone(module.preflight(published, git("rev-parse", "HEAD")))
                route = root / "routes/web.php"
                route.parent.mkdir()
                route.write_text("<?php // PRIVATE_FILE_CONTENT_SENTINEL\n")
                git("add", "."); git("commit", "--quiet", "-m", "synthetic blocked diff")
                candidate = git("rev-parse", "HEAD")
                with self.assertRaises(RuntimeError) as caught:
                    module.preflight(published, candidate)
            message = str(caught.exception)
            prefix = "F251 private-review scope rejected. Validated report: "
            self.assertTrue(message.startswith(prefix))
            self.assertEqual({
                "status": "BLOCKED", "published_sha": published, "candidate_sha": candidate,
                "files_changed": 2, "blocked": ["routes/web.php"],
                "deploy_approved": False, "no_deploy_executed": True,
            }, json.loads(message[len(prefix):]))
            self.assertNotIn("PRIVATE_FILE_CONTENT_SENTINEL", message)

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
