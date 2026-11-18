#!/usr/bin/env python3
"""Prevent consolidation from silently dropping IAM/OTP or restore hardening.

Read-only source regression guard, NOT runtime authorization or release approval.
"""
from pathlib import Path
import hashlib
import json
import sys

ROOT = Path(__file__).resolve().parents[2]
MANIFEST = ROOT / 'infra/staging/integrated-source-manifest.json'


def inspect(root: Path, manifest: dict) -> list[str]:
    failures = []
    for path, expected in manifest['preserved_sha256'].items():
        target = root / path
        if not target.is_file() or hashlib.sha256(target.read_bytes()).hexdigest() != expected:
            failures.append(path)
    for path in manifest['required_additions']:
        if not (root / path).is_file():
            failures.append(path)
    return sorted(set(failures))


if __name__ == '__main__':
    failures = inspect(ROOT, json.loads(MANIFEST.read_text()))
    if failures:
        print('INTEGRATION FAIL: missing or changed reviewed source: ' + ', '.join(failures))
        sys.exit(1)
    print('INTEGRATION PASS: IAM, OTP, route denial and restore hardening preserved; F399-F401 present.')
    print('Source integrity only: no staging admission, business grant or deployment.')
