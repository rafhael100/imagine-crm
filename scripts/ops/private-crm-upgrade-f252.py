#!/usr/bin/env python3
"""F252: operator-only, fail-closed upgrade of the *fictional* private CRM.

This is deliberately NOT a production deploy tool. It never edits Nginx,
database schema, secrets, other Docker Compose projects or Git branches.
"plan" is read-only; "apply" needs separately recorded reviews. The script
has no SSH access or unattended deploy mode.
"""
from __future__ import annotations

import fcntl
import hashlib
import http.client
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import urllib.request

ROOT = Path(__file__).resolve().parents[2]
STATE = Path("/srv/imagine/private-crm-journey")
ENV = STATE / "runtime.env"
PUBLISHED = STATE / "published.json"
RELEASES = STATE / "releases"
PROJECT = "imagine-crm-private-journey"
HOST = "crm-dev.imaginecomunica.com.br"
ORIGINAL = "ea5d320addaeb7ab970bdfa3931d55ddea37afac"
RUN_WORKFLOW_ID = 419173788
API = "https://api.github.com/repos/rafhael100/imagine-crm/"
SHA = re.compile(r"[a-f0-9]{40}\Z")
TAG = re.compile(r"^CRM_PRIVATE_IMAGE_TAG='([a-f0-9]{40})'$", re.M)
SHARED_NGINX = Path("/etc/nginx/sites-available/imagine-dev")
CRM_NGINX = Path("/etc/nginx/sites-available/imagine-crm-private-journey")
CRM_ENABLED = Path("/etc/nginx/sites-enabled/imagine-crm-private-journey")


def require(value: bool, message: str) -> None:
    if not value:
        raise RuntimeError(message)


def image_tag(env_text: str) -> str:
    found = TAG.findall(env_text)
    require(len(found) == 1, "Exactly one immutable CRM image tag is required.")
    return found[0]


def switch_tag(env_text: str, current: str, target: str) -> str:
    require(bool(SHA.fullmatch(current) and SHA.fullmatch(target)), "Invalid SHA.")
    require(image_tag(env_text) == current, "Current runtime image does not match the release.")
    return TAG.sub("CRM_PRIVATE_IMAGE_TAG='" + target + "'", env_text)


def validate_approval(approval: dict, current: str, candidate: str) -> None:
    require(approval.get("published_sha") == current, "Approval baseline differs.")
    require(approval.get("candidate_sha") == candidate, "Approval candidate differs.")
    require(approval.get("scope") == "private-synthetic-only", "Unapproved upgrade scope.")
    owner = approval.get("authorized_by")
    require(isinstance(owner, str) and len(owner.strip()) >= 3
            and owner.casefold() not in ("bot", "assistant", "codex", "chatgpt"),
            "Named human owner approval absent.")
    reviews = approval.get("reviews")
    require(isinstance(reviews, dict), "Two explicit reviews required.")
    security = reviews.get("security")
    operations = reviews.get("operations")
    for review in (security, operations):
        require(isinstance(review, dict) and review.get("decision") == "approved",
                "Independent security and operations sign-offs required.")
        require(isinstance(review.get("reviewer"), str)
                and len(review["reviewer"].strip()) >= 3, "Named reviewer required.")
        require(isinstance(review.get("evidence"), str)
                and len(review["evidence"].strip()) >= 8, "Review evidence URL or record required.")
    require(security["reviewer"] != operations["reviewer"], "Distinct reviewers required.")
    # A file can record a review but cannot prove independence by itself.
    require(approval.get("manual_private_release") is True, "Explicit private release approval required.")


def validate_checks(workflow: dict, checks: dict, sha: str) -> None:
    require(workflow.get("head_sha") == sha and workflow.get("status") == "completed"
            and workflow.get("conclusion") == "success"
            and workflow.get("workflow_id") == RUN_WORKFLOW_ID
            and (workflow.get("head_repository") or {}).get("full_name") == "rafhael100/imagine-crm",
            "Exact-SHA complete-journey CI is not successful.")
    items = checks.get("check_runs")
    require(isinstance(items, list) and checks.get("total_count") == len(items),
            "All exact-commit checks must be inspected; pagination not allowed.")
    require(len(items) >= 22 and all(c.get("status") == "completed"
            and c.get("conclusion") == "success" for c in items),
            "All exact-commit checks must be green.")
    names = {c.get("name") for c in items}
    for name in ("real-private-http", "Exact-SHA private maintenance scope / no deploy / manual review",
                 "private-upgrade-static-policy",
                 "private-f253-post-switch-canary"):
        require(name in names, "Missing required private-upgrade check: " + name)


def call(args: list[str], *, env: dict | None = None, capture: bool = False) -> str:
    result = subprocess.run(args, cwd=ROOT, stdin=subprocess.DEVNULL,
                            stdout=subprocess.PIPE if capture else subprocess.DEVNULL,
                            stderr=subprocess.PIPE, text=True, env=env)
    require(result.returncode == 0, "Private operator command failed; inspect protected VPS logs.")
    return (result.stdout or "").strip()


def gh(path: str) -> dict:
    request = urllib.request.Request(API + path, headers={
        "Accept": "application/vnd.github+json", "User-Agent": "Imagine-F252-private-release"})
    with urllib.request.urlopen(request, timeout=20) as response:
        return json.load(response)


def secret_file(path: Path) -> str:
    require(path.is_file() and not path.is_symlink(), "Protected operator file is absent.")
    metadata = path.stat()
    require(metadata.st_uid == 0 and not metadata.st_mode & 0o077,
            "Protected operator file ownership or mode is unsafe.")
    return path.read_text()


def write_private(path: Path, contents: str) -> None:
    require(not path.exists() and not path.is_symlink(), "Refusing to overwrite release evidence.")
    with path.open("x") as stream:
        stream.write(contents)
    path.chmod(0o600)


def atomic_private(path: Path, contents: str) -> None:
    temporary = path.with_name(path.name + ".next")
    write_private(temporary, contents)
    temporary.replace(path)


def sha256(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def require_unchanged_ingress(
    marker: dict,
    crm_hash: str,
    shared_hash: str,
    *,
    crm_enabled: bool,
    points_to_expected_config: bool,
) -> None:
    """F254 pure policy: status 401 does not prove Nginx bytes are unchanged."""
    require(crm_enabled and points_to_expected_config,
            "Private CRM ingress link changed during maintenance.")
    expected_crm = marker.get("crm_config_sha256")
    expected_shared = marker.get("shared_after_sha256")
    require(isinstance(expected_crm, str) and len(expected_crm) == 64
            and isinstance(expected_shared, str) and len(expected_shared) == 64
            and all(ch in "0123456789abcdef" for ch in expected_crm + expected_shared),
            "Immutable published ingress checksums absent or malformed.")
    require(crm_hash == expected_crm and shared_hash == expected_shared,
            "Protected Nginx configuration changed during the private upgrade.")


def verify_ingress_snapshot(marker: dict) -> None:
    """Read only; checks both hashes and exact link, never rewrites ingress."""
    require_unchanged_ingress(
        marker,
        sha256(CRM_NGINX),
        sha256(SHARED_NGINX),
        crm_enabled=CRM_ENABLED.is_symlink(),
        points_to_expected_config=(
            CRM_ENABLED.is_symlink() and CRM_ENABLED.resolve() == CRM_NGINX
        ),
    )


def protected_https() -> None:
    for host in (HOST, "portal-dev.imaginecomunica.com.br",
                 "comercial-dev.imaginecomunica.com.br"):
        status = call(["curl", "--resolve", host + ":443:127.0.0.1",
                       "--max-time", "12", "-sS", "-o", "/dev/null", "-w", "%{http_code}",
                       "https://" + host + "/"], capture=True)
        require(status == "401", "TLS/BasicAuth boundary differs on an Imagine host.")


def local_smoke() -> None:
    for path in ("/", "/login", "/cadastro", "/recuperar", "/?lang=en", "/?lang=es"):
        connection = http.client.HTTPConnection("127.0.0.1", 18086, timeout=12)
        try:
            connection.request("GET", path, headers={"Host": HOST})
            response = connection.getresponse()
            content = response.read(400_000).decode("utf-8", errors="replace")
            require(response.status == 200 and "Imagine CRM" in content,
                    "Private CRM loopback page unavailable after release.")
        finally:
            connection.close()


def compose(envfile: Path, *args: str) -> None:
    call(["docker", "compose", "--env-file", str(envfile), "-p", PROJECT,
          "-f", str(ROOT / "infra/homologation/compose.yaml"), *args])


def verify_existing(marker: dict, env_text: str) -> str:
    current = marker.get("sha", "")
    require(bool(SHA.fullmatch(current)), "Recorded published SHA is invalid.")
    require(marker.get("base_release_sha", ORIGINAL) == ORIGINAL, "Unknown private release lineage.")
    require(image_tag(env_text) == current, "Recorded release differs from runtime.env.")
    require((STATE / "data/review-access.json").is_file(), "Client/admin reviewer vault absent.")
    require(not (STATE / "data/fixtures.json").exists(), "Unrevoked fixture credentials.")
    verify_ingress_snapshot(marker)
    protected_https()
    return current


def f251_report(current: str, candidate: str) -> dict:
    """Accept the classifier's closed report, including its expected refusal.

    Never return raw process output or stderr. The diagnostic projection below
    contains only revision IDs, repository paths, counts and non-approval flags.
    """
    invalid = "F251 report unavailable or invalid; private release remains blocked."

    def unique_object(pairs: list) -> dict:
        value = {}
        for key, item in pairs:
            if key in value:
                raise ValueError("Duplicate report field.")
            value[key] = item
        return value

    def repo_path(value: object) -> bool:
        return (isinstance(value, str) and len(value) <= 512
                and re.fullmatch(r"[A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)*", value) is not None
                and not any(part in (".", "..") for part in value.split("/")))

    try:
        result = subprocess.run(
            ["python3", "scripts/ops/private-crm-release-preflight.py", current, candidate],
            cwd=ROOT, stdin=subprocess.DEVNULL, stdout=subprocess.PIPE,
            stderr=subprocess.PIPE, text=True,
        )
        require(isinstance(result.stdout, str) and len(result.stdout) <= 65_536, invalid)
        report = json.loads(result.stdout, object_pairs_hook=unique_object)
    except (OSError, UnicodeError, ValueError):
        raise RuntimeError(invalid) from None
    require(isinstance(report, dict) and set(report) == {
        "status", "files_changed", "reviewed_scope", "runtime_review_files", "blocked",
        "deploy_approved", "no_deploy_executed", "requires", "published_sha", "candidate_sha",
    }, invalid)
    require(report["published_sha"] == current and report["candidate_sha"] == candidate
            and SHA.fullmatch(current) is not None and SHA.fullmatch(candidate) is not None
            and report["deploy_approved"] is False and report["no_deploy_executed"] is True,
            invalid)
    require(type(report["files_changed"]) is int and report["files_changed"] >= 0, invalid)
    scope = report["reviewed_scope"]
    require(isinstance(scope, list) and len(scope) <= report["files_changed"], invalid)
    for item in scope:
        require(isinstance(item, dict) and set(item) == {"file", "git_status", "class"}
                and repo_path(item["file"]) and item["git_status"] in ("A", "M")
                and item["class"] in ("ci_evidence", "private_runtime_review", "synthetic_only",
                                      "documentation", "operator_script_review"), invalid)
    for field in ("runtime_review_files", "blocked"):
        values = report[field]
        require(isinstance(values, list) and all(repo_path(value) for value in values)
                and len(values) == len(set(values)), invalid)
    require(set(report["runtime_review_files"]) == {
        item["file"] for item in scope if item["class"] == "private_runtime_review"
    }, invalid)
    require(isinstance(report["requires"], list) and report["requires"]
            and all(isinstance(value, str) for value in report["requires"]), invalid)
    require(type(result.returncode) is int and (
        (result.returncode == 0 and report["status"] == "MANUAL_REVIEW_REQUIRED"
         and not report["blocked"] and report["runtime_review_files"])
        or (result.returncode == 1 and report["status"] == "BLOCKED" and report["blocked"])
    ), invalid)
    return {key: report[key] for key in (
        "status", "published_sha", "candidate_sha", "files_changed", "blocked",
        "deploy_approved", "no_deploy_executed",
    )}


def preflight(current: str, candidate: str) -> None:
    require(call(["git", "rev-parse", "HEAD"], capture=True) == candidate,
            "Checkout must match exact candidate SHA.")
    call(["git", "diff", "--quiet", "HEAD"])
    require(not call(["git", "status", "--porcelain", "--untracked-files=all"], capture=True),
            "Candidate checkout has untracked or modified source files.")
    report = f251_report(current, candidate)
    if report["status"] == "BLOCKED":
        raise RuntimeError("F251 private-review scope rejected. Validated report: "
                           + json.dumps(report, ensure_ascii=True, sort_keys=True))
    require(report.get("status") == "MANUAL_REVIEW_REQUIRED"
            and report.get("deploy_approved") is False
            and not report.get("blocked"), "F251 private-review scope rejected.")


def check_live_image(expected: str, envfile: Path) -> None:
    cmd = ["docker", "compose", "--env-file", str(envfile), "-p", PROJECT,
           "-f", str(ROOT / "infra/homologation/compose.yaml"), "ps", "-q", "app"]
    cid = call(cmd, capture=True)
    require(bool(cid) and len(cid.splitlines()) == 1, "One running private app is required.")
    observed = call(["docker", "inspect", "-f", "{{.Config.Image}}", cid], capture=True)
    require(observed == "imagine-crm-private-journey:" + expected,
            "Running private app image differs from the protected release.")


def backup_and_restore(envfile: Path) -> None:
    environment = dict(os.environ, CRM_PRIVATE_ENV_FILE=str(envfile), CRM_PRIVATE_PROJECT=PROJECT)
    call(["python3", "scripts/ops/backup-private-crm.py", str(STATE / "data/backups")],
         env=environment)


def verify_candidate_canary() -> None:
    """F253: verify real loopback UI, fictional estimate and Agents NO-GO
    after app/web switch, before recording candidate as the published SHA.
    No credentials, migrations, outbound HTTP or new routes.
    """
    call(["python3", "scripts/ops/private-crm-f253-canary.py"])


def review_access(envfile: Path) -> None:
    environment = dict(os.environ, CRM_PRIVATE_ENV_FILE=str(envfile), CRM_PRIVATE_PROJECT=PROJECT)
    call(["python3", "scripts/ops/test-private-crm-http.py",
          str(STATE / "data/review-access.json"), "review-access"], env=environment)


def install(envfile: Path, *, build: bool) -> None:
    if build:
        compose(envfile, "config", "--quiet")
        compose(envfile, "build", "app")
        compose(envfile, "run", "--rm", "-T", "--no-deps", "app", "php", "artisan", "view:cache")
    compose(envfile, "up", "-d", "--no-build", "--no-deps", "app")
    compose(envfile, "up", "-d", "--no-build", "--force-recreate", "--no-deps", "web")


def apply(ci_id: str, marker: dict, old_text: str, current: str, candidate: str) -> None:
    require(ci_id.isdecimal() and 6 <= len(ci_id) <= 20, "Exact CI run ID required.")
    approval_path = STATE / "approvals" / ("private-upgrade-" + candidate + ".json")
    approval = json.loads(secret_file(approval_path))
    validate_approval(approval, current, candidate)
    preflight(current, candidate)
    checks = gh("commits/" + candidate + "/check-runs?per_page=100")
    validate_checks(gh("actions/runs/" + ci_id), checks, candidate)
    check_live_image(current, ENV)
    local_smoke()

    RELEASES.mkdir(mode=0o700, parents=True, exist_ok=True)
    require(RELEASES.stat().st_mode & 0o077 == 0, "Release directory permissions unsafe.")
    directory = RELEASES / candidate
    directory.mkdir(mode=0o700, exist_ok=False)
    old_env = directory / "runtime-before.env"
    new_env = directory / "runtime-candidate.env"
    write_private(old_env, old_text)
    write_private(new_env, switch_tag(old_text, current, candidate))
    write_private(directory / "plan.json", json.dumps({
        "scope": "fictional-private-only", "previous": current, "candidate": candidate,
        "ci_run": int(ci_id), "approval_record": approval_path.name,
        "db_schema_unchanged": True, "ingress_unchanged": True,
        "rollback_image": current}, indent=2) + "\n")

    swapped = False
    try:
        backup_and_restore(ENV)
        swapped = True  # Install can fail midway after app was replaced; rollback is mandatory.
        install(new_env, build=True)
        check_live_image(candidate, new_env)
        local_smoke()
        verify_candidate_canary()
        review_access(new_env)
        protected_https()
        verify_ingress_snapshot(marker)
        backup_and_restore(new_env)
        verify_ingress_snapshot(marker)
        updated = dict(marker, sha=candidate, workflow_run=int(ci_id),
                       base_release_sha=ORIGINAL, latest_maintenance="private-application-f252",
                       previous_sha=current)
        atomic_private(ENV, new_env.read_text())
        atomic_private(PUBLISHED, json.dumps(updated, indent=2) + "\n")
        write_private(directory / "result.json", json.dumps({
            "state": "private-upgrade-verified", "previous": current, "candidate": candidate,
            "client_and_admin": "passed", "backup_restore": "passed",
            "protected_https": "preserved", "other_projects": "unchanged",
            "manual_browser_acceptance": "still_required",
            "commercial_production_go": False}, indent=2) + "\n")
        new_env.unlink()  # Do not retain another redundant copy of all application secrets.
    except BaseException:
        # No migrations are permitted. Recover service and both release markers.
        if swapped:
            install(old_env, build=False)
            check_live_image(current, old_env)
            local_smoke()
            protected_https()
            verify_ingress_snapshot(marker)
        if ENV.exists() and image_tag(ENV.read_text()) == candidate:
            atomic_private(ENV, old_text)
        if PUBLISHED.exists() and json.loads(PUBLISHED.read_text()).get("sha") == candidate:
            atomic_private(PUBLISHED, json.dumps(marker, indent=2) + "\n")
        raise


def rollback(marker: dict, current: str) -> None:
    directory = RELEASES / current
    result = json.loads(secret_file(directory / "result.json"))
    require(result.get("state") == "private-upgrade-verified"
            and result.get("candidate") == current, "Unknown validated F252 release.")
    old_text = secret_file(directory / "runtime-before.env")
    previous = result["previous"]
    require(bool(SHA.fullmatch(previous)) and image_tag(old_text) == previous,
            "No verified previous image available.")
    current_text = secret_file(ENV)
    require(image_tag(current_text) == current, "Runtime changed since verified upgrade.")
    protected_https()
    backup_and_restore(ENV)
    install(directory / "runtime-before.env", build=False)
    check_live_image(previous, directory / "runtime-before.env")
    local_smoke()
    protected_https()
    verify_ingress_snapshot(marker)
    restored = dict(marker, sha=previous, latest_maintenance="f252-rollback",
                    rolled_back_from=current)
    atomic_private(ENV, old_text)
    atomic_private(PUBLISHED, json.dumps(restored, indent=2) + "\n")
    write_private(directory / "rollback.json", json.dumps({
        "from_sha": current, "to_sha": previous, "scope": "app-image-only",
        "database_unchanged": True, "protected_https": "preserved",
        "commercial_production_go": False}, indent=2) + "\n")


def main() -> None:
    require(os.geteuid() == 0 and ROOT == Path("/srv/imagine/crm-private-candidate"),
            "Dedicated Leaseweb private operator checkout and root required.")
    mode = sys.argv[1] if len(sys.argv) >= 2 else ""
    require(mode in ("plan", "apply", "rollback"), "Use plan, apply or rollback.")
    require((len(sys.argv) == 3 if mode in ("plan", "apply") else len(sys.argv) == 2),
            "Exact CI ID required for plan/apply; rollback takes none.")
    os.umask(0o077)
    lock = STATE / "upgrade-f252.lock"
    with lock.open("a+") as fd:
        try:
            fcntl.flock(fd.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError as error:
            raise RuntimeError("Another private CRM maintenance is running.") from error
        marker = json.loads(secret_file(PUBLISHED))
        old_text = secret_file(ENV)
        current = verify_existing(marker, old_text)
        candidate = call(["git", "rev-parse", "HEAD"], capture=True)
        if mode == "rollback":
            rollback(marker, current)
            print("F252 PRIVATE ROLLBACK PASS: previous image, no schema or ingress change.")
            return
        ci_id = sys.argv[2]
        preflight(current, candidate)
        validate_checks(gh("actions/runs/" + ci_id),
                        gh("commits/" + candidate + "/check-runs?per_page=100"), candidate)
        check_live_image(current, ENV)
        if mode == "plan":
            print(json.dumps({"state": "MANUAL_APPROVAL_REQUIRED", "published": current,
                              "candidate": candidate, "ci_run": ci_id,
                              "upgrade_executed": False, "commercial_production_go": False}, indent=2))
            return
        apply(ci_id, marker, old_text, current, candidate)
        print("F252 PRIVATE UPGRADE PASS: protected CRM only. Human visual acceptance still required.")


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        # Never echo protected paths containing secrets or private HTTP response bodies.
        print("F252 BLOCKED: " + str(error), file=sys.stderr)
        raise SystemExit(1)
