"""Exercise the actual owner-draft preflight without Docker or a GitHub runner.

Events and runner files are synthetic. Only the command boundary is replaced:
the recorder permits Git reads and controls their results, so these tests prove
admission/denial and required ancestry checks, not remote GitHub provenance.
"""
import copy
import importlib.util
import itertools
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest import mock

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location(
    "owner_draft_ci_guard", ROOT / "scripts/ops/private_crm_owner_drafts_ci.py"
)
guard = importlib.util.module_from_spec(spec)
spec.loader.exec_module(guard)

REPOSITORY = "rafhael100/imagine-crm"
ORIGINAL = (
    "d3f06599cddfc244f6089d3dac01f29e5a45b8c0",
    "feat/crm-op01-navigable-summary-2026-11-17",
    "feat/crm-owner-agents-drafts-2026-11-17",
)
INTEGRATION = (
    "d7c7aacc766c7ade4c65dcd211ed4569442144a9",
    "dev/fast-track-crm-2026-11-04",
    "integrate/crm-private-journey-and-owner-drafts-2026-11-18",
)
ANCESTORS = (
    ORIGINAL[0],
    "8c62702d02138233404fda73506b779f3ebb3143",
    "fe88dd15d42dbda0bab206dfdbc9f8ce65093212",
)
HEAD = "1" * 40


class OwnerDraftCiAdmissionTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="crm-owner-ci-admission-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name) / "checkout"
        self.root.mkdir()
        self.runner = Path(self.temporary.name) / "runner"
        self.runner.mkdir()
        self.event_path = Path(self.temporary.name) / "event.json"
        self.git_calls = []

    @staticmethod
    def event(provenance):
        base, base_ref, head_ref = provenance
        return {
            "repository": {"full_name": REPOSITORY, "private": False},
            "pull_request": {
                "base": {"sha": base, "ref": base_ref, "repo": {"full_name": REPOSITORY}},
                "head": {"sha": HEAD, "ref": head_ref, "repo": {"full_name": REPOSITORY}},
            },
        }

    def preflight(self, event, *, ancestors=ANCESTORS, environment=None,
                  checkout_head=HEAD, dirty=False, staged=False):
        self.event_path.write_text(json.dumps(event))
        self.git_calls = []
        env = {
            "GITHUB_ACTIONS": "true", "GITHUB_REPOSITORY": REPOSITORY,
            "GITHUB_EVENT_NAME": "pull_request", "GITHUB_SHA": "2" * 40,
            "CRM_OWNER_DRAFT_RUNNER_ENVIRONMENT": "github-hosted",
            "CRM_PRIVATE_PROJECT": "imagine-crm-journey-ci",
            "GITHUB_EVENT_PATH": str(self.event_path), "RUNNER_TEMP": str(self.runner),
            "CRM_PRIVATE_IMAGE_TAG": HEAD,
        }
        env.update(environment or {})

        def command(args, label, **kwargs):
            self.assertEqual(label, "OWNER_DRAFT_SOURCE_DENIED")
            self.assertEqual(args[:5], ["git", "-c", "safe.directory=" + str(self.root),
                                        "-C", str(self.root)])
            action = tuple(args[5:])
            self.git_calls.append(action)
            if action == ("rev-parse", "HEAD"):
                output = (checkout_head + "\n").encode()
            elif action in (("diff", "--quiet"), ("diff", "--cached", "--quiet")):
                if (dirty and action == ("diff", "--quiet")) or (staged and "--cached" in action):
                    raise guard.Failure(label)
                output = b""
            elif action[:2] == ("merge-base", "--is-ancestor"):
                self.assertEqual(action[3], HEAD)
                if action[2] not in ancestors:
                    raise guard.Failure(label)
                output = b""
            else:
                self.fail("Unexpected command in admission preflight")
            return subprocess.CompletedProcess(args, 0, stdout=output, stderr=b"")

        with mock.patch.dict(os.environ, env, clear=True), \
                mock.patch.object(guard, "ROOT", self.root), \
                mock.patch.object(guard, "run", side_effect=command):
            return guard.preflight()

    def assert_denied(self, event, label, **options):
        with self.assertRaisesRegex(guard.Failure, "^" + label + "$"):
            self.preflight(event, **options)

    def test_original_tuple_remains_admitted_with_only_its_original_ancestor(self):
        self.assertEqual(self.preflight(self.event(ORIGINAL), ancestors=(ORIGINAL[0],)),
                         (HEAD, self.runner.resolve()))
        self.assertEqual([call for call in self.git_calls if call[0] == "merge-base"],
                         [("merge-base", "--is-ancestor", ORIGINAL[0], HEAD)])

    def test_integration_tuple_requires_each_of_the_three_exact_ancestors(self):
        self.assertEqual(self.preflight(self.event(INTEGRATION)), (HEAD, self.runner.resolve()))
        self.assertEqual([call for call in self.git_calls if call[0] == "merge-base"],
                         [("merge-base", "--is-ancestor", ancestor, HEAD) for ancestor in ANCESTORS])

    def test_all_six_crossed_tuples_are_denied_before_any_git_read(self):
        denied = 0
        for choice in itertools.product((0, 1), repeat=3):
            provenance = tuple((ORIGINAL, INTEGRATION)[side][index]
                               for index, side in enumerate(choice))
            if provenance in (ORIGINAL, INTEGRATION):
                continue
            with self.subTest(choice=choice):
                self.assert_denied(self.event(provenance), "OWNER_DRAFT_PR_PROVENANCE_DENIED")
                self.assertEqual(self.git_calls, [])
                denied += 1
        self.assertEqual(denied, 6)

    def test_wrong_base_sha_or_branch_is_denied_for_both_admissions(self):
        for original in (ORIGINAL, INTEGRATION):
            for index, value in enumerate(("0" * 40, "main", original[2] + "-other")):
                provenance = list(original)
                provenance[index] = value
                with self.subTest(provenance=provenance):
                    self.assert_denied(self.event(provenance), "OWNER_DRAFT_PR_PROVENANCE_DENIED")
                    self.assertEqual(self.git_calls, [])

    def test_missing_ancestry_rejects_original_and_each_integration_parent(self):
        for provenance, required in ((ORIGINAL, (ORIGINAL[0],)), (INTEGRATION, ANCESTORS)):
            for missing in required:
                with self.subTest(provenance=provenance, missing=missing):
                    self.assert_denied(self.event(provenance), "OWNER_DRAFT_SOURCE_DENIED",
                                       ancestors=set(required) - {missing})
                    self.assertIn(("merge-base", "--is-ancestor", missing, HEAD), self.git_calls)

    def test_real_runner_environment_and_local_docker_guards_remain_required(self):
        for name, value, label in (
            ("GITHUB_ACTIONS", "false", "OWNER_DRAFT_ACTUAL_DISPOSABLE_CI_REQUIRED"),
            ("GITHUB_REPOSITORY", "other/repository", "OWNER_DRAFT_ACTUAL_DISPOSABLE_CI_REQUIRED"),
            ("GITHUB_EVENT_NAME", "pull_request_target", "OWNER_DRAFT_ACTUAL_DISPOSABLE_CI_REQUIRED"),
            ("CRM_OWNER_DRAFT_RUNNER_ENVIRONMENT", "self-hosted", "OWNER_DRAFT_ACTUAL_DISPOSABLE_CI_REQUIRED"),
            ("CRM_PRIVATE_PROJECT", "another-project", "OWNER_DRAFT_ACTUAL_DISPOSABLE_CI_REQUIRED"),
            ("DOCKER_HOST", "tcp://example.invalid:2375", "OWNER_DRAFT_LOCAL_DOCKER_REQUIRED"),
            ("DOCKER_CONTEXT", "remote", "OWNER_DRAFT_LOCAL_DOCKER_REQUIRED"),
            ("GITHUB_SHA", "invalid", "OWNER_DRAFT_EVENT_SHA_REQUIRED"),
        ):
            with self.subTest(environment=name):
                self.assert_denied(self.event(INTEGRATION), label, environment={name: value})
                self.assertEqual(self.git_calls, [])

    def test_event_repository_and_forks_are_still_denied(self):
        baseline = self.event(INTEGRATION)
        for path, value in (
            (("repository", "private"), True),
            (("repository", "full_name"), "other/repository"),
            (("pull_request", "head", "repo", "full_name"), "other/repository"),
            (("pull_request", "base", "repo", "full_name"), "other/repository"),
        ):
            event = copy.deepcopy(baseline)
            node = event
            for key in path[:-1]:
                node = node[key]
            node[path[-1]] = value
            with self.subTest(path=path):
                self.assert_denied(event, "OWNER_DRAFT_PR_PROVENANCE_DENIED")
                self.assertEqual(self.git_calls, [])

    def test_exact_head_image_and_clean_checkout_are_still_required(self):
        event = self.event(INTEGRATION)
        invalid = copy.deepcopy(event)
        invalid["pull_request"]["head"]["sha"] = "invalid"
        self.assert_denied(invalid, "OWNER_DRAFT_EXACT_HEAD_REQUIRED")
        for options in ({"checkout_head": "3" * 40},
                        {"environment": {"CRM_PRIVATE_IMAGE_TAG": "3" * 40}}):
            with self.subTest(options=options):
                self.assert_denied(event, "OWNER_DRAFT_EXACT_HEAD_REQUIRED", **options)
        for options in ({"dirty": True}, {"staged": True}):
            with self.subTest(options=options):
                self.assert_denied(event, "OWNER_DRAFT_SOURCE_DENIED", **options)


if __name__ == "__main__":
    unittest.main()
