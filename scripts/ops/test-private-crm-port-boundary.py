#!/usr/bin/env python3
"""Reject accidental public exposure of private CRM Compose services.

Static regression only: does not access Docker, credentials, SSH or the VPS.
"""
from pathlib import Path
import re
import sys
import unittest

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "scripts/ops"))
from importlib.machinery import SourceFileLoader

restart = SourceFileLoader("private_restart_contract", str(
    ROOT / "scripts/ops/test-private-crm-restart-contract.py")).load_module()
COMPOSE = ROOT / "infra/homologation/compose.yaml"


def check_network_boundary(source):
    services = restart.service_blocks(source)
    if set(services) != restart.EXPECTED:
        raise ValueError("unexpected or missing service")
    for name, block in services.items():
        top_level_ports = re.findall(r"^    ports:\s*(.*)$", block, re.M)
        if name == "web":
            if top_level_ports != ['["127.0.0.1:18086:8080"]']:
                raise ValueError("web must bind exact fixed loopback endpoint")
        elif top_level_ports:
            raise ValueError("private service has published ports: " + name)
        net = re.findall(r"^    networks:\s*(.*)$", block, re.M)
        expected = "[internal, ingress]" if name == "web" else "[internal]"
        if net != [expected]:
            raise ValueError("unexpected network membership: " + name)
        if re.search(r"^    network_mode:|^    privileged:|^    pid:|^    extra_hosts:", block, re.M):
            raise ValueError("unapproved service escape option: " + name)
    # Require the actual internal network key and block: ingress remains
    # separate and no other network is admitted by the service allowlist.
    networks = re.search(r"(?ms)^networks:\n(.*?)(?=^[^ #\n]|\Z)", source)
    if not networks:
        raise ValueError("networks block absent")
    text = networks.group(1)
    if len(re.findall(r"^  internal:\s*\{internal: true\}\s*$", text, re.M)) != 1:
        raise ValueError("internal network must remain internal")
    if len(re.findall(r"^  ingress:\s*\{driver: bridge\}\s*$", text, re.M)) != 1:
        raise ValueError("ingress bridge absent")
    if set(re.findall(r"^  ([a-z][a-z0-9_-]*):", text, re.M)) != {"internal", "ingress"}:
        raise ValueError("unexpected network")


class BoundaryTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.source = COMPOSE.read_text(encoding="utf-8")

    def test_real_compose(self):
        check_network_boundary(self.source)

    def test_reject_public_web_bind(self):
        altered = self.source.replace("127.0.0.1:18086:8080", "0.0.0.0:18086:8080", 1)
        with self.assertRaises(ValueError):
            check_network_boundary(altered)

    def test_reject_database_port(self):
        altered = self.source.replace("  postgres:\n", "  postgres:\n    ports: [\"5432:5432\"]\n", 1)
        with self.assertRaises(ValueError):
            check_network_boundary(altered)

    def test_reject_redis_port(self):
        altered = self.source.replace("  redis:\n", "  redis:\n    ports: [\"6379:6379\"]\n", 1)
        with self.assertRaises(ValueError):
            check_network_boundary(altered)

    def test_reject_app_ingress(self):
        altered = self.source.replace("    networks: [internal]\n    read_only: true",
                                      "    networks: [internal, ingress]\n    read_only: true", 1)
        with self.assertRaises(ValueError):
            check_network_boundary(altered)

    def test_reject_internal_network_opening(self):
        altered = self.source.replace("internal: {internal: true}", "internal: {driver: bridge}", 1)
        with self.assertRaises(ValueError):
            check_network_boundary(altered)

    def test_reject_host_mode(self):
        altered = self.source.replace("  app:\n", "  app:\n    network_mode: host\n", 1)
        with self.assertRaises(ValueError):
            check_network_boundary(altered)

    def test_reject_added_network(self):
        altered = self.source.replace("  ingress: {driver: bridge}", "  public: {driver: bridge}\n  ingress: {driver: bridge}", 1)
        with self.assertRaises(ValueError):
            check_network_boundary(altered)


if __name__ == "__main__":
    unittest.main()
