#!/usr/bin/env python3
"""Negative, Docker-free regression tests for the isolated CRM autostart rehearsal.

Never invokes systemctl, docker, Compose, a database, HTTP or real credentials.
All container inventories are fabricated in memory. This does not test a VPS.
"""

import importlib.util
import json
from pathlib import Path
from unittest import TestCase, main, mock

SOURCE = Path(__file__).with_name("test-private-crm-autostart.py")
spec = importlib.util.spec_from_file_location("crm_private_autostart", SOURCE)
assert spec is not None and spec.loader is not None
sut = importlib.util.module_from_spec(spec)
spec.loader.exec_module(sut)

SHA = "a" * 40
IDS = ["fixture-" + str(i) for i in range(4)]


def good_rows():
    rows = []
    for service in ("postgres", "redis", "app", "web"):
        rows.append({
            "id": "fixture-" + str(len(rows)),
            "image": "imagine-crm-private-journey:" + SHA if service == "app" else "fixture-image",
            "status": "running", "started": "synthetic-start", "health": "healthy",
            "policy": "unless-stopped", "project": sut.PROJECT, "service": service,
            "oneoff": "False", "mounts": [],
            "ports": {"8080/tcp": [{"HostIp": "127.0.0.1", "HostPort": "18086"}]} if service == "web" else {},
        })
    return rows


def inventory_with(rows, ids=None):
    """Replace both Docker read calls, and fail if any other subprocess is attempted."""
    calls = []

    def fake_command(args, timeout=10, *, operation):
        calls.append((operation, args))
        if operation == "Docker container inventory":
            return "\n".join(ids if ids is not None else IDS)
        if operation == "Docker container metadata":
            return "\n".join(json.dumps(row) for row in rows)
        raise AssertionError("Unexpected external operation: " + operation)

    return fake_command, calls


class AutostartDenyOnlyTests(TestCase):
    def test_guard_requires_explicit_opt_in_before_any_io(self):
        with mock.patch.object(sut, "command", side_effect=AssertionError("external call")):
            with self.assertRaises(sut.Refused):
                sut.guard(Path("/nonexistent-fixture"), False)

    def test_four_exact_isolated_containers_pass_read_only_inventory(self):
        fake, calls = inventory_with(good_rows())
        with mock.patch.object(sut, "command", side_effect=fake):
            observed = sut.inventory(SHA)
        self.assertEqual(set(observed), {"postgres", "redis", "app", "web"})
        self.assertEqual(len(calls), 2)
        self.assertEqual([c[0] for c in calls], ["Docker container inventory", "Docker container metadata"])

    def test_fifth_foreign_container_denied_before_inspect(self):
        fake, calls = inventory_with(good_rows(), IDS + ["foreign-container"])
        with mock.patch.object(sut, "command", side_effect=fake):
            with self.assertRaises(sut.Refused):
                sut.inventory(SHA)
        self.assertEqual(len(calls), 1)

    def test_missing_container_denied(self):
        fake, calls = inventory_with(good_rows(), IDS[:3])
        with mock.patch.object(sut, "command", side_effect=fake):
            with self.assertRaises(sut.Refused):
                sut.inventory(SHA)
        self.assertEqual(len(calls), 1)

    def test_duplicate_service_denied(self):
        rows = good_rows()
        rows[-1]["service"] = "redis"
        self.assert_denied(rows)

    def test_oneoff_or_wrong_project_denied(self):
        for field, value in (("oneoff", "True"), ("project", "foreign")):
            with self.subTest(field=field):
                rows = good_rows()
                rows[0][field] = value
                self.assert_denied(rows)

    def test_disabled_restart_policy_denied(self):
        rows = good_rows()
        rows[1]["policy"] = "no"
        self.assert_denied(rows)

    def test_unexpected_app_image_denied(self):
        rows = good_rows()
        rows[2]["image"] = "image:unverified"
        self.assert_denied(rows)

    def test_public_web_binding_denied(self):
        rows = good_rows()
        rows[3]["ports"] = {"8080/tcp": [{"HostIp": "0.0.0.0", "HostPort": "18086"}]}
        self.assert_denied(rows)

    def test_bind_outside_checkout_denied(self):
        rows = good_rows()
        rows[0]["mounts"] = [{"Type": "bind", "Source": "/etc"}]
        self.assert_denied(rows)

    def test_foreign_persistent_volume_denied(self):
        rows = good_rows()
        rows[1]["mounts"] = [{"Type": "volume", "Name": "external_db"}]
        self.assert_denied(rows)

    def test_unhealthy_or_stopped_stack_is_not_ready(self):
        rows = {row["service"]: row for row in good_rows()}
        self.assertTrue(sut.ready(rows))
        rows["postgres"]["health"] = "unhealthy"
        self.assertFalse(sut.ready(rows))
        rows["postgres"]["health"] = "healthy"
        rows["web"]["status"] = "exited"
        self.assertFalse(sut.ready(rows))

    def assert_denied(self, rows):
        fake, calls = inventory_with(rows)
        with mock.patch.object(sut, "command", side_effect=fake):
            with self.assertRaises(sut.Refused):
                sut.inventory(SHA)
        self.assertEqual(len(calls), 2)


if __name__ == "__main__":
    main(verbosity=2)
