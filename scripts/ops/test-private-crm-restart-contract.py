#!/usr/bin/env python3
"""Static fail-closed regression for private Compose restart scope.

Only Python stdlib; never contacts Docker, a database, or the VPS.
The full Docker-daemon recovery remains covered by the separate isolated CI.
"""
from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[2]
COMPOSE = ROOT / "infra/homologation/compose.yaml"
PERSISTENT = frozenset({"postgres", "redis", "app", "web"})
EXPECTED = PERSISTENT | {"tools"}


def service_blocks(source: str) -> dict[str, str]:
    """Read literal service blocks; reject ambiguous YAML instead of guessing."""
    lines = source.splitlines()
    starts = [i for i, line in enumerate(lines) if line == "services:"]
    if len(starts) != 1:
        raise ValueError("exactly one top-level services block required")
    first = starts[0] + 1
    end = next((i for i in range(first, len(lines))
                if lines[i] and not lines[i].startswith((" ", "#"))), len(lines))
    blocks = {}
    current = None
    for line in lines[first:end]:
        if not line.strip() or line.lstrip().startswith("#"):
            continue
        if re.fullmatch(r"  [a-z][a-z0-9_-]*:", line):
            current = line.strip()[:-1]
            if current in blocks:
                raise ValueError("duplicate service " + current)
            blocks[current] = []
        elif line.startswith("    "):
            if current is None:
                raise ValueError("service property without service")
            blocks[current].append(line)
        else:
            raise ValueError("unexpected services indentation")
    return {key: "\n".join(value) for key, value in blocks.items()}


def assert_restart_contract(source: str) -> None:
    blocks = service_blocks(source)
    if set(blocks) != EXPECTED:
        raise ValueError("unexpected/missing service")
    for service, block in blocks.items():
        # Only literal, top-level service keys count. Do not accept YAML anchors,
        # substitutions, duplicate restart keys, or commented-out policies.
        policies = re.findall(r"^    restart:\s*(.*?)\s*$", block, re.M)
        if service in PERSISTENT:
            if policies != ["unless-stopped"]:
                raise ValueError("persistent service restart policy: " + service)
            if re.search(r"^    profiles:", block, re.M):
                raise ValueError("persistent service must start by default: " + service)
        elif policies:
            raise ValueError("tools must not have restart policy")
    if not re.search(r"^    profiles:\s*\[tools\]\s*$", blocks["tools"], re.M):
        raise ValueError("tools must remain opt-in profile")


class RestartScopeTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.source = COMPOSE.read_text(encoding="utf-8")

    def test_real_compose(self):
        assert_restart_contract(self.source)

    def test_reject_missing_restart(self):
        altered = self.source.replace("    restart: unless-stopped", "", 1)
        with self.assertRaises(ValueError):
            assert_restart_contract(altered)

    def test_reject_always_restart(self):
        altered = self.source.replace("    restart: unless-stopped", "    restart: always", 1)
        with self.assertRaises(ValueError):
            assert_restart_contract(altered)

    def test_reject_duplicate_restart(self):
        altered = self.source.replace("    restart: unless-stopped",
                                      "    restart: unless-stopped\n    restart: always", 1)
        with self.assertRaises(ValueError):
            assert_restart_contract(altered)

    def test_reject_tools_autostart(self):
        altered = self.source.replace("  tools:\n", "  tools:\n    restart: unless-stopped\n", 1)
        with self.assertRaises(ValueError):
            assert_restart_contract(altered)

    def test_reject_tools_without_profile(self):
        altered = self.source.replace("    profiles: [tools]\n", "", 1)
        with self.assertRaises(ValueError):
            assert_restart_contract(altered)

    def test_reject_added_foreign_service(self):
        altered = self.source.replace("  tools:\n", "  other:\n    image: busybox\n  tools:\n", 1)
        with self.assertRaises(ValueError):
            assert_restart_contract(altered)

    def test_reject_persistent_service_profile(self):
        altered = self.source.replace("  redis:\n", "  redis:\n    profiles: [tools]\n", 1)
        with self.assertRaises(ValueError):
            assert_restart_contract(altered)

    def test_reject_duplicate_service(self):
        altered = self.source.replace("  redis:\n", "  redis:\n  redis:\n", 1)
        with self.assertRaises(ValueError):
            assert_restart_contract(altered)


if __name__ == "__main__":
    unittest.main()
