#!/usr/bin/env python3
"""F3-52: deterministic, fail-closed review-only evidence of the 4-repo lab."""
from __future__ import annotations

import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import stat
import subprocess
import sys

EXPECTED: dict[str, tuple[str, str]] = {
    "comercial": ("comercial-source", "fe42b6c0683ff66d836dc58677f25a8fed2d793d"),
    "portal_intake": ("portal-source", "af414ed7aece433e65b4f813be12ba3546cc5c8a"),
    "portal_sites": ("portal-sites-candidate", "8d1e20bc5c39dc4106b549c5e979351b5c766615"),
    "sites": ("_synthetic_sites_fixture", "057b6f859df5050c842a86d18676e6332a79d1f9"),
    "crm_core": ("crm-core-source", "881ebbf0ddf486f8a85c4848f85719a2f4207f6c"),
}
HEX = re.compile(r"\A[a-f0-9]{40}\Z")
SEAMS = (
    "comercial_portal_crm_disposable_pg",
    "portal_sites_signed_synthetic_contract",
    "sites_crm_metadata_and_denial",
)


def canonical(value: object) -> bytes:
    return json.dumps(value, sort_keys=True, separators=(",", ":"), ensure_ascii=True).encode("ascii")


def evidence(repo_head: str, pins: dict[str, str]) -> dict:
    if not HEX.fullmatch(repo_head) or set(pins) != set(EXPECTED):
        raise ValueError("Invalid or incomplete reviewed Git source set")
    if any(pins[k] != sha for k, (_, sha) in EXPECTED.items()):
        raise ValueError("A pinned source diverged from the approved synthetic lab")
    return {
        "schema": "imagine.f352.four-project-boundary-evidence.v1",
        "status": "NO_GO",
        "synthetic_only": True,
        "external_service_tested": False,
        "production_authorized": False,
        "staging_authorized": False,
        "customer_access_authorized": False,
        "live_crm_acknowledgment_proven": False,
        "single_end_to_end_business_transaction_proven": False,
        "appsec_and_dba_approved": False,
        "output_contains_pii": False,
        "tested_boundary_count": 3,
        "independent_contract_boundaries": list(SEAMS),
        "crm_review_head": repo_head,
        "pinned_sources": pins,
        "portal_source_versions_are_distinct": pins["portal_intake"] != pins["portal_sites"],
        "unresolved_gates": [
            "single_approved_portal_release",
            "operator_review_and_branch_protection",
            "tenant_iam_mfa_and_revocation",
            "authenticated_transport_and_ratelimit",
            "customer_consent_and_privacy",
            "postgresql_rls_and_receipt_durability",
            "appsec_dba_and_private_staging",
        ],
    }


def envelope(data: dict) -> dict:
    payload = canonical(data)
    return {"sha256": hashlib.sha256(payload).hexdigest(), "evidence": data}


def check(document: object) -> dict:
    if type(document) is not dict or set(document) != {"sha256", "evidence"}:
        raise ValueError("Noncanonical review evidence wrapper")
    blob = document["evidence"]
    if type(blob) is not dict or not re.fullmatch(r"\A[a-f0-9]{64}\Z", str(document["sha256"])):
        raise ValueError("Invalid review evidence")
    if document["sha256"] != hashlib.sha256(canonical(blob)).hexdigest():
        raise ValueError("Review evidence has changed")
    expected = evidence(blob.get("crm_review_head", ""), blob.get("pinned_sources", {}))
    if blob != expected:
        raise ValueError("Unknown or misleading review evidence field")
    return expected


def git_head(directory: str) -> str:
    path = Path(directory)
    if not path.is_dir() or path.is_symlink():
        raise ValueError("Unexpected pinned checkout directory")
    cp = subprocess.run(
        ["git", "-C", directory, "rev-parse", "--verify", "HEAD^{commit}"],
        stdout=subprocess.PIPE, stderr=subprocess.DEVNULL, check=True, timeout=6,
    )
    actual = cp.stdout.decode("ascii", errors="strict").strip()
    if not HEX.fullmatch(actual):
        raise ValueError("Invalid Git commit in review fixture")
    return actual


def self_test() -> None:
    pins = {name: sha for name, (_, sha) in EXPECTED.items()}
    valid = evidence("a" * 40, pins)
    assert check(envelope(valid)) == valid
    tampered = envelope(valid)
    tampered["evidence"]["production_authorized"] = True
    try:
        check(tampered)
    except ValueError:
        pass
    else:
        raise AssertionError("Tampered authorization was accepted")
    try:
        evidence("a" * 40, {**pins, "portal_sites": "0" * 40})
    except ValueError:
        pass
    else:
        raise AssertionError("Changed source pin accepted")
    forged = envelope(valid)
    forged["evidence"]["unresolved_gates"] = []
    forged["sha256"] = hashlib.sha256(canonical(forged["evidence"])).hexdigest()
    try:
        check(forged)
    except ValueError:
        pass
    else:
        raise AssertionError("Re-signed misleading review status accepted")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    options = parser.add_mutually_exclusive_group(required=True)
    options.add_argument("--out", type=Path)
    options.add_argument("--verify", type=Path)
    options.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("F3-52 negative evidence checks passed")
        return 0
    if args.verify:
        raw = args.verify.read_bytes()
        if len(raw) > 4096:
            raise ValueError("Oversized F3-52 evidence artifact")
        check(json.loads(raw.decode("ascii", errors="strict")))
        print("F3-52 review evidence integrity and NO-GO verified")
        return 0
    if (os.environ.get("GITHUB_ACTIONS") != "true"
        or os.environ.get("CRM_F350_COMERCIAL_CI_LAB") != "1"
        or os.environ.get("CRM_F342_CROSS_REPO_LAB") != "1"):
        raise ValueError("F3-52 CI-only review emission refused outside ephemeral lab")
    out: Path = args.out
    if out.is_symlink() or out.exists() or not out.parent.is_dir():
        raise ValueError("Must provide a new review artifact filename")
    pins = {name: git_head(directory) for name, (directory, _) in EXPECTED.items()}
    proof = envelope(evidence(git_head("."), pins))
    check(proof)
    with out.open("x", encoding="ascii") as destination:
        os.chmod(out, stat.S_IRUSR | stat.S_IWUSR)
        destination.write(canonical(proof).decode("ascii") + "\n")
    print("F3-52 source-revision evidence created (5 pins, 3 boundaries, NO-GO)")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, ValueError, UnicodeError, AssertionError, subprocess.SubprocessError) as exc:
        print("F3-52 evidence refused: " + type(exc).__name__, file=sys.stderr)
        raise SystemExit(2)
