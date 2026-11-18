#!/usr/bin/env python3
"""Behavioural tests for the non-mutating private-target inspector.

Synthetic local files/processes only. Real PostgreSQL coverage belongs to the
separate disposable CI runner, not to these unit/process tests.
"""
from __future__ import annotations

import builtins
import copy
import importlib.util
import io
import json
import os
from pathlib import Path
import signal
import subprocess
import sys
import tempfile
import time
import unittest
from unittest import mock


ROOT = Path(__file__).resolve().parents[2]
SPEC = importlib.util.spec_from_file_location(
    "private_owner_inspection", ROOT / "scripts/ops/inspect-private-crm-owner-drafts.py"
)
assert SPEC is not None and SPEC.loader is not None
inspection = importlib.util.module_from_spec(SPEC)
sys.modules[SPEC.name] = inspection
SPEC.loader.exec_module(inspection)

SECRET = "PRIVATE_INSPECTION_TEST_SECRET_NEVER_RENDER"


class CommandRunnerTests(unittest.TestCase):
    def setUp(self):
        self.runner = inspection.CommandRunner()

    def command(self, source, **kwargs):
        return self.runner.run([sys.executable, "-c", source], **kwargs)

    def denied(self, source, **kwargs):
        with self.assertRaises(inspection.ProbeError) as raised:
            self.command(source, **kwargs)
        self.assertNotIn(SECRET, str(raised.exception))
        return raised.exception

    def test_success_preserves_exact_bytes(self):
        self.assertEqual(self.command("import sys;sys.stdout.buffer.write(b'known\\x00bytes')"),
                         b"known\x00bytes")

    def test_input_is_stdin_without_shell_interpolation(self):
        value = b"literal $(touch forbidden) `touch forbidden` ; \xff\n"
        result = self.command("import sys;sys.stdout.buffer.write(sys.stdin.buffer.read())",
                              input_bytes=value)
        self.assertEqual(result, value)

    def test_nonzero_exit_does_not_return_partial_stdout_or_stderr(self):
        self.denied("import sys;print(%r);print(%r,file=sys.stderr);sys.exit(7)" % (SECRET, SECRET))

    def test_success_does_not_mix_stderr_into_observed_data(self):
        result = self.command("import sys;sys.stdout.write('known');sys.stderr.write(%r)" % SECRET)
        self.assertEqual(result, b"known")

    def test_missing_command_uses_sanitized_error(self):
        with self.assertRaises(inspection.ProbeError) as raised:
            self.runner.run(["/does-not-exist/" + SECRET])
        self.assertNotIn(SECRET, str(raised.exception))

    def test_stdout_limit_is_enforced_on_actual_process(self):
        self.denied("import sys;sys.stdout.write(%r*4096)" % SECRET,
                    max_output=1024)

    def test_stderr_limit_is_enforced_even_with_success_exit(self):
        self.denied("import sys;sys.stderr.write(%r*4096)" % SECRET,
                    max_output=1024)

    def test_combined_stdout_stderr_limit_is_enforced(self):
        self.denied("import sys;sys.stdout.write('x'*700);sys.stderr.write('y'*700)",
                    max_output=1024)

    def test_timeout_is_bounded_and_does_not_leak_output(self):
        started = time.monotonic()
        self.denied("import time;print(%r,flush=True);time.sleep(30)" % SECRET,
                    timeout=0.15)
        self.assertLess(time.monotonic() - started, 5)

    def test_inherited_remote_docker_and_git_overrides_are_not_forwarded(self):
        keys = ("DOCKER_HOST", "DOCKER_CONTEXT", "DOCKER_CONFIG", "DOCKER_TLS_VERIFY",
                "GIT_CONFIG_COUNT", "GIT_CONFIG_KEY_0", "GIT_CONFIG_VALUE_0", "GIT_DIR")
        with mock.patch.dict(os.environ, {key: SECRET for key in keys}):
            result = self.command("import json,os;print(json.dumps([k for k in %r if k in os.environ]))" % (keys,))
        self.assertEqual(json.loads(result), [])

    def test_descendant_holding_pipe_cannot_escape_deadline(self):
        with tempfile.TemporaryDirectory(prefix="owner-inspect-process-") as directory:
            pidfile = Path(directory) / "child.pid"
            child = "import os,time;open(%r,'w').write(str(os.getpid()));time.sleep(30)" % str(pidfile)
            parent = "import subprocess,sys;subprocess.Popen([sys.executable,'-c',%r])" % child
            try:
                started = time.monotonic()
                self.denied(parent, timeout=0.4)
                self.assertLess(time.monotonic() - started, 5)
                if pidfile.exists():
                    pid = int(pidfile.read_text())
                    for _ in range(50):
                        status = Path(f"/proc/{pid}/stat")
                        if not status.exists() or status.read_text().split()[2] == "Z":
                            break
                        time.sleep(0.02)
                    else:
                        self.fail("A child retaining the pipe survived the inspection deadline")
            finally:
                if pidfile.exists():
                    try:
                        os.kill(int(pidfile.read_text()), signal.SIGKILL)
                    except ProcessLookupError:
                        pass


class LocalRunner:
    """Run Git for real; a reachable empty Docker daemon is explicit evidence."""
    def __init__(self, docker_failure=False):
        self.real = inspection.CommandRunner()
        self.commands = []
        self.docker_failure = docker_failure

    def run(self, argv, **kwargs):
        self.commands.append((list(argv), dict(kwargs)))
        program = Path(argv[0]).name
        if program == "git":
            return self.real.run(argv, **kwargs)
        if program == "docker":
            if self.docker_failure:
                raise inspection.ProbeError("COMMAND_FAILED")
            if "ps" in argv:
                return b""
        raise AssertionError("Unexpected command: " + program)


class LocalObservationTests(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="owner-inspect-files-")
        self.root = Path(self.temporary.name)
        self.checkout = self.root / "checkout"
        self.checkout.mkdir()
        self.state = self.root / "state"
        self.nginx = self.root / "nginx"
        self.nginx.mkdir()
        self.target = inspection.Target(
            checkout=self.checkout, state=self.state,
            nginx_crm=self.nginx / "crm", nginx_shared=self.nginx / "shared",
            nginx_enabled=self.nginx / "enabled", scope="ci-fixture",
        )

    def tearDown(self):
        self.temporary.cleanup()

    def git(self, *args):
        command = ["git", "-C", str(self.checkout), "-c", "core.hooksPath=/dev/null",
                   "-c", "commit.gpgsign=false", *args]
        return subprocess.check_output(command, stderr=subprocess.DEVNULL).decode().strip()

    def create_checkout(self):
        self.git("init", "--quiet")
        self.git("config", "user.name", "Inspection fixture")
        self.git("config", "user.email", "inspection@example.invalid")
        (self.checkout / "tracked.txt").write_text("initial\n")
        self.git("add", "tracked.txt")
        self.git("commit", "--quiet", "-m", "synthetic fixture")
        return self.git("rev-parse", "HEAD")

    def collect(self, runner=None):
        report = inspection.Inspector(self.target, runner=runner or LocalRunner()).collect()
        encoded = json.dumps(report, sort_keys=True)
        self.assertNotIn(SECRET, encoded)
        self.assertEqual(report["schema"], "CRM_OWNER_PRIVATE_TARGET_OBSERVATION_V1")
        self.assertEqual(report["scope"], "ci-fixture")
        self.assertIs(report["read_only"], True)
        self.assertIs(report["deploy_approved"], False)
        self.assertIs(report["mutations_executed"], False)
        self.assertIs(report["consistent_snapshot_verified"], False)
        return report

    def test_empty_daemon_is_not_observed_not_an_access_error(self):
        self.create_checkout()
        report = self.collect()
        self.assertEqual(report["sections"]["containers"]["status"], "NOT_OBSERVED")

    def test_daemon_failure_is_unknown_not_absent(self):
        self.create_checkout()
        report = self.collect(LocalRunner(docker_failure=True))
        self.assertEqual(report["sections"]["containers"]["status"], "UNKNOWN")

    def test_missing_protected_paths_are_not_created(self):
        self.create_checkout()
        before = sorted(str(path.relative_to(self.root)) for path in self.root.rglob("*"))
        report = self.collect()
        after = sorted(str(path.relative_to(self.root)) for path in self.root.rglob("*"))
        self.assertEqual(before, after)
        self.assertFalse(self.state.exists())
        paths = report["sections"]["protected_paths"]
        self.assertEqual(paths["status"], "OBSERVED")
        self.assertTrue(all(value["status"] == "NOT_OBSERVED" for value in paths["data"].values()))

    def test_private_file_contents_are_never_opened(self):
        self.create_checkout()
        self.state.mkdir(mode=0o700)
        (self.state / "data").mkdir(mode=0o700)
        private_files = [self.state / "runtime.env", self.state / "data" / "review-access.json",
                         self.state / "data" / "fixtures.json"]
        for path in private_files:
            path.write_text(SECRET)
            path.chmod(0o600)
        original_open, original_io_open, original_os_open = builtins.open, io.open, os.open

        def deny_secret_open(original, file, *args, **kwargs):
            if isinstance(file, (str, bytes, os.PathLike)) and Path(os.fsdecode(file)) in private_files:
                raise AssertionError("Inspector attempted to open protected credential contents")
            return original(file, *args, **kwargs)

        def deny_secret_os_open(file, *args, **kwargs):
            if isinstance(file, (str, bytes, os.PathLike)) and Path(os.fsdecode(file)).name in {
                    path.name for path in private_files}:
                raise AssertionError("Inspector attempted to open a protected credential inode")
            return original_os_open(file, *args, **kwargs)

        with mock.patch("builtins.open", side_effect=lambda f, *a, **k: deny_secret_open(original_open, f, *a, **k)), \
                mock.patch("io.open", side_effect=lambda f, *a, **k: deny_secret_open(original_io_open, f, *a, **k)), \
                mock.patch.object(inspection.os, "open", side_effect=deny_secret_os_open):
            self.collect()
        self.assertTrue(all(path.read_text() == SECRET for path in private_files))

    def test_git_fsmonitor_hook_is_not_executed(self):
        self.create_checkout()
        sentinel = self.root / "unexpected-fsmonitor-side-effect"
        hook = self.root / "fsmonitor"
        hook.write_text("#!/bin/sh\nprintf side-effect > " + str(sentinel) + "\n")
        hook.chmod(0o700)
        self.git("config", "core.fsmonitor", str(hook))
        self.collect()
        self.assertFalse(sentinel.exists(), "Read-only inspection executed a Git fsmonitor hook")

    def test_git_external_diff_and_textconv_are_not_executed(self):
        self.create_checkout()
        sentinel = self.root / "unexpected-git-diff-side-effect"
        hook = self.root / "external-diff"
        hook.write_text("#!/bin/sh\nprintf side-effect > " + str(sentinel) + "\n")
        hook.chmod(0o700)
        self.git("config", "diff.external", str(hook))
        self.git("config", "diff.inspection.textconv", str(hook))
        (self.checkout / ".gitattributes").write_text("tracked.txt diff=inspection\n")
        (self.checkout / "tracked.txt").write_text("changed\n")
        self.collect()
        self.assertFalse(sentinel.exists(), "Read-only inspection executed a Git diff helper")

    def test_git_clean_filter_cannot_run_during_drift_observation(self):
        self.create_checkout()
        sentinel = self.root / "unexpected-git-filter-side-effect"
        hook = self.root / "clean-filter"
        hook.write_text("#!/bin/sh\nprintf side-effect > " + str(sentinel) + "\ncat\n")
        hook.chmod(0o700)
        self.git("config", "filter.inspection.clean", str(hook))
        (self.checkout / ".gitattributes").write_text("tracked.txt filter=inspection\n")
        (self.checkout / "tracked.txt").write_text("changed\n")
        report = self.collect()
        self.assertFalse(sentinel.exists(), "Inspection executed a repository-defined Git clean filter")
        source = report["sections"]["source"]
        self.assertEqual(source["status"], "UNKNOWN", "Configured filters prevent reliable read-only drift observation")

    def test_worktree_git_filter_cannot_bypass_local_config_observation(self):
        self.create_checkout()
        sentinel = self.root / "unexpected-worktree-filter-side-effect"
        hook = self.root / "worktree-clean-filter"
        hook.write_text("#!/bin/sh\nprintf side-effect > " + str(sentinel) + "\ncat\n")
        hook.chmod(0o700)
        self.git("config", "extensions.worktreeConfig", "true")
        self.git("config", "--worktree", "filter.inspection.clean", str(hook))
        (self.checkout / ".gitattributes").write_text("tracked.txt filter=inspection\n")
        (self.checkout / "tracked.txt").write_text("changed\n")
        report = self.collect()
        self.assertFalse(sentinel.exists(), "Inspection executed a worktree-specific Git filter")
        self.assertEqual(report["sections"]["source"]["status"], "UNKNOWN")

    def test_checkout_drift_is_observed_without_refreshing_index(self):
        self.create_checkout()
        index = self.checkout / ".git" / "index"
        original_index = index.read_bytes()
        (self.checkout / "tracked.txt").write_text("changed " + SECRET + "\n")
        report = self.collect()
        self.assertEqual(index.read_bytes(), original_index)
        source = report["sections"]["source"]
        self.assertEqual(source["status"], "OBSERVED")
        self.assertIs(source["data"]["tracked_changes"], True,
                      "Tracked changes must be explicit, without exposing their content")

    def test_git_core_worktree_cannot_redirect_source_observation(self):
        self.create_checkout()
        elsewhere = self.root / "unrelated-worktree"
        elsewhere.mkdir()
        (elsewhere / "tracked.txt").write_text(SECRET)
        self.git("config", "core.worktree", str(elsewhere))
        report = self.collect()
        self.assertEqual(report["sections"]["source"]["status"], "UNKNOWN")
        self.assertEqual(report["sections"]["source"]["code"], "SOURCE_ROOT_MISMATCH")

    def test_matching_image_tag_never_proves_image_provenance_or_clean_worktree(self):
        head = self.create_checkout()

        class MixedRunner(ProjectionRunner):
            def run(inner, argv, **kwargs):
                if Path(argv[0]).name == "git":
                    return inspection.CommandRunner().run(argv, **kwargs)
                return super().run(argv, **kwargs)

        value = container_value(service="app", project=self.target.project)
        value["image_reference"] = "imagine-crm-private-journey:" + head
        report = self.collect(MixedRunner(containers=[value]))
        self.assertIs(report["sections"]["containers"]["data"]["app_tag_matches_checkout"], True)
        self.assertIs(report["sections"]["containers"]["data"]["image_provenance_verified"], False)
        (self.checkout / "tracked.txt").write_text("changed\n")
        report = self.collect(MixedRunner(containers=[value]))
        self.assertIs(report["sections"]["source"]["data"]["tracked_changes"], True)
        self.assertIs(report["sections"]["containers"]["data"]["image_provenance_verified"], False)
        value["image_reference"] = "imagine-crm-private-journey:" + "f" * 40
        report = self.collect(MixedRunner(containers=[value]))
        self.assertIs(report["sections"]["containers"]["data"]["app_tag_matches_checkout"], False)

    def test_permission_failure_is_unknown_and_does_not_expose_exception(self):
        original_stat = os.stat

        def denied_stat(path, *args, **kwargs):
            if path == "runtime.env":
                raise PermissionError(SECRET)
            return original_stat(path, *args, **kwargs)

        self.state.mkdir()
        with mock.patch.object(inspection.os, "stat", side_effect=denied_stat):
            report = inspection.Inspector(self.target, LocalRunner()).protected_paths()
        self.assertNotIn(SECRET, json.dumps(report))
        self.assertEqual(report["data"]["runtime_environment"]["status"], "UNKNOWN")

    def test_symlinked_ingress_is_not_followed_to_private_content(self):
        secret_file = self.root / "private-contents"
        secret_file.write_text(SECRET)
        self.target.nginx_crm.symlink_to(secret_file)
        report = inspection.Inspector(self.target, LocalRunner()).ingress()
        self.assertNotIn(SECRET, json.dumps(report))
        self.assertEqual(report["data"]["crm"]["status"], "UNKNOWN")

    def test_symlink_parent_is_unknown_not_a_readable_protected_directory(self):
        actual = self.root / "actual-state"
        actual.mkdir()
        (actual / "runtime.env").write_text(SECRET)
        self.state.symlink_to(actual)
        report = inspection.Inspector(self.target, LocalRunner()).protected_paths()
        self.assertNotIn(SECRET, json.dumps(report))
        self.assertEqual(report["data"]["state"]["data"]["kind"], "symlink")
        self.assertEqual(report["data"]["runtime_environment"]["status"], "UNKNOWN")


def container_value(identifier="a" * 64, service="postgres", project=inspection.PROJECT):
    return {"id": identifier, "image_id": "sha256:" + "b" * 64,
            "image_reference": "postgres:16" if service == "postgres" else "imagine-crm-private-journey:" + "c" * 40,
            "project": project, "service": service, "state": "running", "ports": {},
            "networks": {}, "mounts": []}


def catalog_value(owner=False):
    names = inspection.BASE_TABLES | (inspection.OWNER_TABLES if owner else set())
    return {"database_matches": True, "server_major": 16, "read_only": True,
            "repeatable_read": True, "schemas": ["public"],
            "relations": [{"name": name, "kind": "r", "owner": "crm_homologation_owner",
                           "rls": name in inspection.OWNER_TABLES,
                           "force_rls": name in inspection.OWNER_TABLES} for name in sorted(names)],
            "roles": [{"name": role, "login": role == "crm_homologation_owner",
                       "superuser": role == "crm_homologation_owner", "bypass_rls": False,
                       "create_db": False, "create_role": False, "inherit": False,
                       "replication": False} for role in inspection.ROLES[:5]],
            "membership_count": 0, "default_acl_count": 0, "user_function_count": 0,
            "owner_lock_marker": owner}


class ProjectionRunner:
    def __init__(self, *, containers=None, catalog=None, installation=None):
        self.values = [container_value()] if containers is None else containers
        self.catalog = catalog_value() if catalog is None else catalog
        self.installation = {"structure_matches": True, "mode": "NOT_READ"} if installation is None else installation
        self.commands = []
        self.raw_database = None

    def run(self, argv, **kwargs):
        self.commands.append((list(argv), dict(kwargs)))
        if Path(argv[0]).name == "docker":
            if "ps" in argv:
                return ("\n".join(value["id"] for value in self.values) + ("\n" if self.values else "")).encode()
            if "inspect" in argv:
                value = next((value for value in self.values if value["id"] == argv[-1]), self.values[0])
                return json.dumps(value).encode()
            if "exec" in argv:
                return self.raw_database if self.raw_database is not None else (
                    json.dumps(self.catalog).encode() + b"\n" + json.dumps(self.installation).encode() + b"\n")
        raise AssertionError("Unexpected fixture command")


class ProjectionTests(unittest.TestCase):
    def setUp(self):
        self.target = inspection.default_target()

    def database(self, runner):
        result = inspection.Inspector(self.target, runner).database("a" * 64)
        self.assertNotIn(SECRET, json.dumps(result))
        if result["status"] == "OBSERVED":
            self.assertIs(result["data"]["profile_verified"], False)
        return result

    def test_base_and_owner_catalogs_are_inventory_observations_only(self):
        for owner, name, count in ((False, "BASE_25", 25), (True, "OWNER_27", 27)):
            with self.subTest(owner=owner):
                result = self.database(ProjectionRunner(catalog=catalog_value(owner)))
                self.assertEqual(result["status"], "OBSERVED")
                self.assertEqual(result["data"]["relation_inventory"], name)
                self.assertEqual(len(result["data"]["relations"]), count)
                self.assertIs(result["data"]["transaction_read_only"], True)

    def test_unknown_catalog_names_are_counted_without_disclosure(self):
        catalog = catalog_value(True)
        catalog["schemas"].append(SECRET)
        catalog["relations"].append({"name": SECRET + ".hidden", "kind": "r", "owner": SECRET,
                                     "rls": False, "force_rls": False})
        catalog["relations"][0]["owner"] = SECRET
        result = self.database(ProjectionRunner(catalog=catalog))
        self.assertEqual(result["status"], "OBSERVED")
        self.assertEqual(result["data"]["relation_inventory"], "UNRECOGNIZED")
        self.assertEqual(result["data"]["unknown_schema_count"], 1)
        self.assertEqual(result["data"]["unknown_relation_count"], 1)

    def test_database_identity_transaction_and_version_fail_closed(self):
        for field, invalid in (("database_matches", False), ("read_only", False),
                               ("repeatable_read", False), ("server_major", 15),
                               ("server_major", True)):
            with self.subTest(field=field, invalid=invalid):
                catalog = catalog_value()
                catalog[field] = invalid
                self.assertEqual(self.database(ProjectionRunner(catalog=catalog))["status"], "UNKNOWN")

    def test_invalid_and_duplicate_catalog_objects_are_unknown(self):
        mutations = [
            lambda c: c["relations"].append(copy.deepcopy(c["relations"][0])),
            lambda c: c["roles"].append(copy.deepcopy(c["roles"][0])),
            lambda c: c["roles"][0].update(superuser="false"),
            lambda c: c["relations"][0].update(force_rls=0),
            lambda c: c.update(membership_count=True),
            lambda c: c.update(relations=c["relations"] * 20),
            lambda c: c["relations"][0].update(kind=SECRET),
            lambda c: c.update(extra=SECRET),
        ]
        for index, mutate in enumerate(mutations):
            with self.subTest(mutation=index):
                catalog = catalog_value()
                mutate(catalog)
                self.assertEqual(self.database(ProjectionRunner(catalog=catalog))["status"], "UNKNOWN")

    def test_partial_database_output_is_not_empty_profile(self):
        for raw in (b"", b"{}\n", b"{}\n{}\n{}\n", b"{bad\n{}\n",
                    b'{"x":1,"x":2}\n{}\n', b'{"x":NaN}\n{}\n', b"\xff\n{}\n"):
            with self.subTest(raw=raw):
                runner = ProjectionRunner()
                runner.raw_database = raw
                self.assertEqual(self.database(runner)["status"], "UNKNOWN")

    def test_marker_output_cannot_claim_a_read_value(self):
        for installation in ({"structure_matches": True, "mode": SECRET},
                             {"structure_matches": True, "mode": "synthetic-v1"},
                             {"structure_matches": True, "mode": "NOT_READ", "secret": SECRET}):
            with self.subTest(fields=tuple(installation)):
                self.assertEqual(self.database(ProjectionRunner(installation=installation))["status"], "UNKNOWN")

    def test_unreviewed_marker_structure_remains_visible_without_reading_mode(self):
        result = self.database(ProjectionRunner(installation={"structure_matches": False,
                                                             "mode": "NOT_READ"}))
        self.assertEqual(result["status"], "OBSERVED")
        self.assertIs(result["data"]["installation"]["structure_matches"], False)
        self.assertEqual(result["data"]["installation"]["mode"], "NOT_READ")
        self.assertIs(result["data"]["profile_verified"], False)

    def test_catalog_request_is_fixed_read_only_and_bounded(self):
        runner = ProjectionRunner()
        self.database(runner)
        requests = [(argv, opts) for argv, opts in runner.commands if "exec" in argv]
        self.assertEqual(len(requests), 1)
        argv, options = requests[0]
        sql = options["input_bytes"].decode("ascii")
        self.assertIn("REPEATABLE READ READ ONLY", sql)
        self.assertIn("statement_timeout", sql)
        self.assertIn("lock_timeout", sql)
        self.assertIn("idle_in_transaction_session_timeout", sql)
        self.assertIn("search_path=pg_catalog", sql)
        self.assertNotRegex(sql, r"(?i)\bFROM\s+public\.")
        self.assertNotRegex(sql, r"(?i)\b(?:pg_authid|rolpassword|pg_read_file|pg_read_binary_file)\b")
        self.assertTrue(sql.rstrip().endswith("ROLLBACK;"))
        self.assertIn("--no-password", argv)
        self.assertIn("env", argv)
        self.assertIn("-i", argv)
        self.assertIn("PGPASSFILE=/dev/null", argv)
        self.assertIn("PGSERVICEFILE=/dev/null", argv)
        self.assertLessEqual(options["timeout"], 15)
        self.assertLessEqual(options["max_output"], 262144)

    def test_wrong_container_binding_is_denied_before_database_command(self):
        mutations = [{"project": "unrelated-project"}, {"service": "redis"},
                     {"state": "exited"}, {"image_reference": "unknown:" + SECRET}]
        for mutation in mutations:
            with self.subTest(mutation=tuple(mutation)):
                container = container_value()
                container.update(mutation)
                runner = ProjectionRunner(containers=[container])
                self.assertEqual(self.database(runner)["status"], "UNKNOWN")
                self.assertFalse(any("exec" in args for args, _ in runner.commands))

    def test_unknown_docker_metadata_is_projected_without_secrets(self):
        value = container_value(service="app")
        value["image_reference"] = "unrecognized:" + SECRET
        value["networks"] = {SECRET: {"NetworkID": "d" * 64, "IPAddress": SECRET,
                                      "Aliases": [SECRET]}}
        value["mounts"] = [{"Type": "bind", "RW": True, "Source": "/" + SECRET,
                             "Destination": "/" + SECRET}]
        value["ports"] = {SECRET: [{"HostIp": SECRET, "HostPort": "19876"}]}
        result = inspection.Inspector(self.target, ProjectionRunner(containers=[value])).containers()
        self.assertEqual(result["status"], "OBSERVED")
        self.assertNotIn(SECRET, json.dumps(result))
        self.assertIs(result["data"]["image_provenance_verified"], False)
        service = result["data"]["services"][0]
        self.assertIsNone(service["image_reference"])
        self.assertEqual(service["unknown_network_count"], 1)
        self.assertEqual(service["mounts"][0]["source"], "UNRECOGNIZED")

    def test_duplicate_or_wrong_project_container_is_not_a_complete_observation(self):
        cases = [[container_value(), container_value("e" * 64)],
                 [container_value(project="unrelated-project")],
                 [container_value(), container_value()]]
        for values in cases:
            with self.subTest(containers=len(values)):
                result = inspection.Inspector(self.target, ProjectionRunner(containers=values)).containers()
                self.assertEqual(result["status"], "UNKNOWN")

    def test_unrequested_docker_environment_is_rejected_without_rendering(self):
        value = container_value()
        value["environment"] = ["SECRET=" + SECRET]
        result = inspection.Inspector(self.target, ProjectionRunner(containers=[value])).containers()
        self.assertEqual(result["status"], "UNKNOWN")
        self.assertNotIn(SECRET, json.dumps(result))

    def test_docker_endpoint_is_explicit_local_socket(self):
        runner = ProjectionRunner()
        with mock.patch.dict(os.environ, {"DOCKER_HOST": "tcp://unrelated.invalid:2375",
                                          "DOCKER_CONTEXT": SECRET}):
            inspection.Inspector(self.target, runner).containers()
        for argv, _ in runner.commands:
            self.assertEqual(argv[:5], ["docker", "--config", "/proc/self/fd", "--host", "unix:///var/run/docker.sock"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
