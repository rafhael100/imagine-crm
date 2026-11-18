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
import time
import unittest
from unittest import mock

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location(
    "owner_draft_ci_guard", ROOT / "scripts/ops/private_crm_owner_drafts_ci.py"
)
guard = importlib.util.module_from_spec(spec)
spec.loader.exec_module(guard)
sys.path.insert(0, str(ROOT / "scripts/ops"))
try:
    import private_crm_owner_monitor_migration as migration
finally:
    sys.path.pop(0)

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
MIGRATION = (
    INTEGRATION[0], INTEGRATION[1],
    "feat/crm-owner-monitor-incremental-migration-2026-11-18",
)
MIGRATION_ANCESTORS = (*ANCESTORS, "96dbf1aed2d1cf3237bc3631c4594540a0168d2a")
LEDGER = (
    INTEGRATION[0], INTEGRATION[1],
    "feat/crm-ledger-reapply-rls-2026-11-18",
)
LEDGER_ANCESTORS = (*MIGRATION_ANCESTORS, "d8963313267ccc5b40b7527d6fa24a30fcc12b7e")
BASE_MONITOR = (
    INTEGRATION[0], INTEGRATION[1],
    "feat/crm-base-monitor-incremental-migration-2026-11-18",
)
BASE_MONITOR_ANCESTORS = (*LEDGER_ANCESTORS, "082a1fda4af9b1871854d0db8cbfe54858894f25")
STRICT_CSV = (
    INTEGRATION[0], INTEGRATION[1],
    "fix/crm-private-csv-strict-decoding-2026-11-18",
)
STRICT_CSV_ANCESTORS = (*BASE_MONITOR_ANCESTORS, "7e1b00a0c173bcfcac3a9051ef6cad3aededcdb8")
TASK_DATE = (
    INTEGRATION[0], INTEGRATION[1],
    "fix/crm-private-task-date-validation-2026-11-18",
)
TASK_DATE_ANCESTORS = (*STRICT_CSV_ANCESTORS, "c073cfeb8d29fc6cd0a9f33642ccc1275b8b6f59")
NULLABLE_PREFERENCES = (
    INTEGRATION[0], INTEGRATION[1],
    "fix/crm-nullable-automation-preferences-2026-11-18",
)
NULLABLE_PREFERENCES_ANCESTORS = (*TASK_DATE_ANCESTORS, "2a6d2950c54873ebbb233b64ef7985e0750c0576")
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

    def test_migration_tuple_requires_all_preserved_ancestors_and_exact_96db_pin(self):
        self.assertEqual(self.preflight(self.event(MIGRATION), ancestors=MIGRATION_ANCESTORS),
                         (HEAD, self.runner.resolve()))
        self.assertEqual([call for call in self.git_calls if call[0] == "merge-base"],
                         [("merge-base", "--is-ancestor", ancestor, HEAD)
                          for ancestor in MIGRATION_ANCESTORS])
        for missing in MIGRATION_ANCESTORS:
            with self.subTest(missing=missing):
                self.assert_denied(self.event(MIGRATION), "OWNER_DRAFT_SOURCE_DENIED",
                                   ancestors=set(MIGRATION_ANCESTORS) - {missing})
                self.assertIn(("merge-base", "--is-ancestor", missing, HEAD), self.git_calls)

    def test_ledger_tuple_requires_all_preserved_ancestors_and_exact_migration_parent(self):
        self.assertEqual(self.preflight(self.event(LEDGER), ancestors=LEDGER_ANCESTORS),
                         (HEAD, self.runner.resolve()))
        self.assertEqual([call for call in self.git_calls if call[0] == "merge-base"],
                         [("merge-base", "--is-ancestor", ancestor, HEAD)
                          for ancestor in LEDGER_ANCESTORS])
        for missing in LEDGER_ANCESTORS:
            with self.subTest(missing=missing):
                self.assert_denied(self.event(LEDGER), "OWNER_DRAFT_SOURCE_DENIED",
                                   ancestors=set(LEDGER_ANCESTORS) - {missing})
                self.assertIn(("merge-base", "--is-ancestor", missing, HEAD), self.git_calls)

    def test_base_monitor_tuple_requires_every_ancestor_and_exact_ledger_parent(self):
        self.assertEqual(self.preflight(self.event(BASE_MONITOR), ancestors=BASE_MONITOR_ANCESTORS),
                         (HEAD, self.runner.resolve()))
        self.assertEqual([call for call in self.git_calls if call[0] == "merge-base"],
                         [("merge-base", "--is-ancestor", ancestor, HEAD)
                          for ancestor in BASE_MONITOR_ANCESTORS])
        for missing in BASE_MONITOR_ANCESTORS:
            with self.subTest(missing=missing):
                self.assert_denied(self.event(BASE_MONITOR), "OWNER_DRAFT_SOURCE_DENIED",
                                   ancestors=set(BASE_MONITOR_ANCESTORS) - {missing})
                self.assertIn(("merge-base", "--is-ancestor", missing, HEAD), self.git_calls)

    def test_strict_csv_tuple_requires_every_ancestor_and_exact_base_monitor_parent(self):
        self.assertEqual(self.preflight(self.event(STRICT_CSV), ancestors=STRICT_CSV_ANCESTORS),
                         (HEAD, self.runner.resolve()))
        self.assertEqual([call for call in self.git_calls if call[0] == "merge-base"],
                         [("merge-base", "--is-ancestor", ancestor, HEAD)
                          for ancestor in STRICT_CSV_ANCESTORS])
        for missing in STRICT_CSV_ANCESTORS:
            with self.subTest(missing=missing):
                self.assert_denied(self.event(STRICT_CSV), "OWNER_DRAFT_SOURCE_DENIED",
                                   ancestors=set(STRICT_CSV_ANCESTORS) - {missing})
                self.assertIn(("merge-base", "--is-ancestor", missing, HEAD), self.git_calls)

    def test_task_date_tuple_requires_every_ancestor_and_exact_strict_csv_parent(self):
        self.assertEqual(self.preflight(self.event(TASK_DATE), ancestors=TASK_DATE_ANCESTORS),
                         (HEAD, self.runner.resolve()))
        self.assertEqual([call for call in self.git_calls if call[0] == "merge-base"],
                         [("merge-base", "--is-ancestor", ancestor, HEAD)
                          for ancestor in TASK_DATE_ANCESTORS])
        for missing in TASK_DATE_ANCESTORS:
            with self.subTest(missing=missing):
                self.assert_denied(self.event(TASK_DATE), "OWNER_DRAFT_SOURCE_DENIED",
                                   ancestors=set(TASK_DATE_ANCESTORS) - {missing})
                self.assertIn(("merge-base", "--is-ancestor", missing, HEAD), self.git_calls)

    def test_nullable_preferences_tuple_requires_every_ancestor_and_exact_task_date_ancestor(self):
        self.assertEqual(self.preflight(self.event(NULLABLE_PREFERENCES), ancestors=NULLABLE_PREFERENCES_ANCESTORS),
                         (HEAD, self.runner.resolve()))
        self.assertEqual([call for call in self.git_calls if call[0] == "merge-base"],
                         [("merge-base", "--is-ancestor", ancestor, HEAD)
                          for ancestor in NULLABLE_PREFERENCES_ANCESTORS])
        for missing in NULLABLE_PREFERENCES_ANCESTORS:
            with self.subTest(missing=missing):
                self.assert_denied(self.event(NULLABLE_PREFERENCES), "OWNER_DRAFT_SOURCE_DENIED",
                                   ancestors=set(NULLABLE_PREFERENCES_ANCESTORS) - {missing})
                self.assertIn(("merge-base", "--is-ancestor", missing, HEAD), self.git_calls)

    def test_eight_admission_tuples_cannot_mix_fields_or_accept_neighbor_branches(self):
        known = {ORIGINAL, INTEGRATION, MIGRATION, LEDGER, BASE_MONITOR, STRICT_CSV, TASK_DATE, NULLABLE_PREFERENCES}
        choices = [sorted({value[index] for value in known}) for index in range(3)]
        rejected = 0
        for provenance in itertools.product(*choices):
            if provenance in known:
                continue
            with self.subTest(provenance=provenance):
                self.assert_denied(self.event(provenance), "OWNER_DRAFT_PR_PROVENANCE_DENIED")
                self.assertEqual(self.git_calls, [])
                rejected += 1
        self.assertEqual(rejected, 24)
        for candidate in (MIGRATION, LEDGER, BASE_MONITOR, STRICT_CSV, TASK_DATE, NULLABLE_PREFERENCES):
            for index, value in enumerate(("0" * 40, "main", candidate[2] + "-other")):
                provenance = list(candidate)
                provenance[index] = value
                self.assert_denied(self.event(provenance), "OWNER_DRAFT_PR_PROVENANCE_DENIED")
                self.assertEqual(self.git_calls, [])

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


class MigrationSessionBoundaryTest(unittest.TestCase):
    """Exercise the real pipe reader with a local synthetic process, never a DB."""

    def session(self):
        program = r'''
import os,sys,time
for line in sys.stdin:
    if 'fixture_fragment' in line:
        value = ('{"value":"' + 'x' * 150000 + '"}\n').encode()
        os.write(1,value[:7]);time.sleep(.005);os.write(1,value[7:])
    elif 'fixture_sqlstate' in line:
        os.write(2,b'ERROR:  55P03\nprivate SQL password MAC must not escape\n')
        raise SystemExit(1)
    elif 'fixture_eof' in line:
        raise SystemExit(0)
    elif 'fixture_silent' in line:
        time.sleep(5)
    elif line.startswith('\\echo '):
        value=(line[6:].strip()+'\n').encode()
        os.write(1,value[:5]);time.sleep(.005);os.write(1,value[5:])
'''
        session = migration.Transaction(["docker", "exec", "-i", "a" * 64],
                                        lock_timeout_ms=100, statement_timeout_ms=500)
        process = subprocess.Popen([sys.executable, "-u", "-c", program],
                                   stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        session.process = process

        def stop():
            if process.poll() is None:
                process.terminate()
                process.wait(timeout=2)
            for pipe in (process.stdin, process.stdout, process.stderr):
                pipe.close()
        self.addCleanup(stop)
        return session

    def test_fragmented_large_result_and_multiple_frames_use_the_same_process(self):
        session = self.session()
        identity = session.process.pid
        for _ in range(2):
            self.assertEqual(json.loads(session.query("SELECT fixture_fragment")), {"value": "x" * 150000})
        self.assertEqual(session.process.pid, identity)
        self.assertIsNone(session.process.poll())

    def test_sqlstate_is_observed_and_private_diagnostics_remain_suppressed(self):
        session = self.session()
        with self.assertRaises(migration.MigrationDenied) as caught:
            session.query("SELECT fixture_sqlstate")
        self.assertEqual(caught.exception.code, "OWNER_MONITOR_MIGRATION_LOCK_TIMEOUT")
        self.assertEqual(caught.exception.sqlstate, "55P03")
        self.assertEqual(str(caught.exception), "OWNER_MONITOR_MIGRATION_LOCK_TIMEOUT")

    def test_missing_frame_eof_is_denied_without_inventing_sqlstate(self):
        session = self.session()
        with self.assertRaises(migration.MigrationDenied) as caught:
            session.query("SELECT fixture_eof")
        self.assertEqual(caught.exception.code, "OWNER_MONITOR_MIGRATION_SQL_FAILED")
        self.assertIsNone(caught.exception.sqlstate)

    def test_unresponsive_pipe_has_a_bounded_client_deadline(self):
        session = self.session()
        session.deadline = time.monotonic() + .05
        started = time.monotonic()
        with self.assertRaisesRegex(migration.MigrationDenied, "^OWNER_MONITOR_MIGRATION_CLIENT_TIMEOUT$"):
            session.query("SELECT fixture_silent")
        self.assertLess(time.monotonic() - started, 1)

    def test_bootstrap_extraction_is_bound_to_all_seven_real_file_preimages(self):
        sources = {name: (ROOT / name).read_bytes() for name in migration.SOURCE_BLOBS}
        delta = migration._sources(sources)
        bootstrap = sources[migration.BOOTSTRAP].decode()
        self.assertEqual(bootstrap, "-- Additive, disposable private homologation only. No production migration.\n"
                         "BEGIN;\n" + delta + "COMMIT;\n")
        for path in sources:
            changed = dict(sources, **{path: sources[path] + b"\n"})
            with self.subTest(path=path), self.assertRaisesRegex(
                    migration.MigrationDenied, "^OWNER_MONITOR_MIGRATION_SOURCE_BLOB$"):
                migration._sources(changed)

    def test_resource_admission_binds_run_image_and_disposable_storage_before_sql(self):
        identity, image = "a" * 64, "sha256:" + "b" * 64
        inspection = {
            "Id": identity, "State": {"Running": True},
            "Name": "/imagine-owner-monitor-migration-123-1-fixture",
            "Image": image,
            "Config": {"Image": image, "Labels": {
                "imagine.owner_monitor_migration.head": HEAD,
                "imagine.owner_monitor_migration.run": "123",
                "imagine.owner_monitor_migration.attempt": "1"}},
            "HostConfig": {"NetworkMode": "none", "PortBindings": {}, "Binds": None,
                           "Tmpfs": {"/var/lib/postgresql/data": ""}, "Privileged": False,
                           "SecurityOpt": ["no-new-privileges:true"]},
            "NetworkSettings": {"Ports": {"5432/tcp": None}}, "Mounts": [],
        }

        def run(command, **kwargs):
            responses = {
                ("docker", "context", "show"): b"default\n",
                ("docker", "context", "inspect", "default"): json.dumps(
                    [{"Endpoints": {"docker": {"Host": "unix:///var/run/docker.sock"}}}]).encode(),
                ("docker", "inspect", identity): json.dumps([inspection]).encode(),
                ("docker", "image", "inspect", "postgres:16", "--format", "{{.Id}}"): image.encode(),
            }
            self.assertIn(tuple(command), responses)
            return responses[tuple(command)]

        with mock.patch.dict(os.environ, {"GITHUB_RUN_ID": "123", "GITHUB_RUN_ATTEMPT": "1"}, clear=True), \
                mock.patch.object(migration.ci, "preflight", return_value=(HEAD, ROOT)), \
                mock.patch.object(migration, "_run", side_effect=run), \
                mock.patch.object(migration.subprocess, "Popen", side_effect=AssertionError("No SQL process in admission")):
            migration._container(["docker", "exec", "-i", identity])
            baseline = copy.deepcopy(inspection)
            for path, bad in (
                (("Config", "Labels", "imagine.owner_monitor_migration.head"), "0" * 40),
                (("Config", "Labels", "imagine.owner_monitor_migration.run"), "122"),
                (("Config", "Labels", "imagine.owner_monitor_migration.attempt"), "2"),
                (("Config", "Image"), "postgres:16"), (("Image",), "sha256:" + "c" * 64),
                (("Name",), "/imagine-owner-monitor-migration-122-1-fixture"),
                (("HostConfig", "NetworkMode"), "bridge"),
                (("HostConfig", "Binds"), ["/tmp:/var/lib/postgresql/data"]),
                (("HostConfig", "Tmpfs"), {}),
                (("Mounts",), [{"Type": "volume", "Destination": "/var/lib/postgresql/data"}]),
                (("NetworkSettings", "Ports"), {"5432/tcp": [{"HostPort": "5432"}]}),
            ):
                inspection.clear(); inspection.update(copy.deepcopy(baseline))
                node = inspection
                for key in path[:-1]:
                    node = node[key]
                node[path[-1]] = bad
                with self.subTest(path=path), self.assertRaisesRegex(
                        migration.MigrationDenied, "^OWNER_MONITOR_MIGRATION_DISPOSABLE_CONTAINER$"):
                    migration._container(["docker", "exec", "-i", identity])


if __name__ == "__main__":
    unittest.main()
