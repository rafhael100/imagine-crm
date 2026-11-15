#!/usr/bin/env python3
"""F251: read-only classification of a private CRM update candidate.

This tool has NO side effects, NO deployment credentials, and cannot approve
or perform release. It only checks that the committed diff between a verified
published baseline and candidate is within a narrow reviewed scope. Actual
promotion still needs full CI, server-side backup, TLS/BasicAuth, reviewer
credentials, rollback check and an authorized operator.
"""
from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SHA = re.compile(r"\A[a-f0-9]{40}\Z")

# Positive allowlist for this PARTICULAR private review. Unknown paths,
# renames and deletions always block the candidate: no guesses or wildcards.
SCOPE = {
    ".github/workflows/ci-crm-aia-f248-private.yml": "ci_evidence",
    ".github/workflows/ci-private-crm-journey.yml": "ci_evidence",
    "app/Homologation/Controller.php": "private_runtime_review",
    "app/Homologation/OpportunityScenario.php": "private_runtime_review",
    "app/Homologation/PrivateHeaders.php": "private_runtime_review",
    "app/Support/Synthetic/AgentsSignedIngressCandidate.php": "synthetic_only",
    "docs/operations/private-complete-journey.md": "documentation",
    "resources/views/crm-marketing-home.blade.php": "private_runtime_review",
    "resources/views/crm-private-journey.blade.php": "private_runtime_review",
    "scripts/ops/test-private-crm-browser.cjs": "ci_evidence",
    "scripts/ops/test-private-crm-http.py": "ci_evidence",
    "scripts/ops/update-private-crm-marketing.py": "operator_script_review",
    "scripts/ops/private-crm-release-preflight.py": "operator_script_review",
    "tests/Feature/AgentsPrivateIngressCandidateTest.php": "ci_evidence",
    "tests/Unit/OpportunityScenarioTest.php": "ci_evidence",
}


def classify(changes: list[tuple[str, str]]) -> dict:
    """Pure, fail-closed policy; no JSON response is a deployment approval."""
    blocked = []
    inspected = []
    seen = set()
    for status, path in changes:
        if (status not in ("A", "M") or path not in SCOPE
                or path in seen or not isinstance(path, str)):
            blocked.append(path)
            continue
        seen.add(path)
        inspected.append({
            "file": path, "git_status": status, "class": SCOPE[path],
        })
    runtime = sorted(x["file"] for x in inspected
                     if x["class"] == "private_runtime_review")
    if not changes or not runtime:
        blocked.append("missing_expected_private_application_change")

    return {
        "status": "BLOCKED" if blocked else "MANUAL_REVIEW_REQUIRED",
        "files_changed": len(changes),
        "reviewed_scope": inspected,
        "runtime_review_files": runtime,
        "blocked": sorted(set(blocked)),
        "deploy_approved": False,
        "no_deploy_executed": True,
        "requires": [
            "17/17 exact-head CI checks including F248/F249/F250 and private E2E",
            "independent security and operational review",
            "protected existing VPS revision and rollback snapshot",
            "encrypted tested backup and restore",
            "preserved TLS, BasicAuth, and other project hosts",
            "operator-run promotion with immediate post-release verification",
        ],
    }


def git(*args: str) -> str:
    return subprocess.check_output(
        ["git", *args], cwd=ROOT, stderr=subprocess.DEVNULL, text=True,
    ).strip()


def main(args: list[str]) -> int:
    if len(args) != 2 or not all(SHA.fullmatch(s) for s in args):
        print("Usage: private-crm-release-preflight.py <published-sha> <candidate-sha>",
              file=sys.stderr)
        return 2
    published, candidate = args
    if git("rev-parse", "HEAD") != candidate:
        print("BLOCKED: the checked-out revision is not the candidate.", file=sys.stderr)
        return 1
    if subprocess.run(["git", "merge-base", "--is-ancestor", published, candidate],
                      cwd=ROOT, stdout=subprocess.DEVNULL,
                      stderr=subprocess.DEVNULL).returncode != 0:
        print("BLOCKED: published SHA is not a candidate ancestor.", file=sys.stderr)
        return 1
    if subprocess.run(["git", "diff", "--quiet", "HEAD"], cwd=ROOT,
                      stdout=subprocess.DEVNULL,
                      stderr=subprocess.DEVNULL).returncode != 0:
        print("BLOCKED: uncommitted tracked changes.", file=sys.stderr)
        return 1
    lines = git("diff", "--name-status", "--no-renames",
                published + ".." + candidate).splitlines()
    entries = []
    for line in lines:
        parts = line.split("\t")
        if len(parts) != 2:
            print("BLOCKED: unexpected git status record.", file=sys.stderr)
            return 1
        entries.append((parts[0], parts[1]))
    result = classify(entries)
    result["published_sha"] = published
    result["candidate_sha"] = candidate
    print(json.dumps(result, ensure_ascii=False, sort_keys=True, indent=2))
    return 0 if result["status"] == "MANUAL_REVIEW_REQUIRED" else 1


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
