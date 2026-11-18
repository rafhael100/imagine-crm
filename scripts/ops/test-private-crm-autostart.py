#!/usr/bin/env python3
"""Prove autostart on an otherwise empty GitHub-hosted CI daemon, never a VPS.

This test deliberately restarts the local Docker daemon. It never starts or
recreates a container afterward. Run after Chromium creates clientA's records
and before resilience consumes recovery code [1]; reserve codes [2] and [3].
"""
import argparse
import hashlib
import http.client
import importlib.util
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import time

ROOT = Path(__file__).resolve().parents[2]
PROJECT = "imagine-crm-journey-ci"
SERVICES = {"postgres", "redis", "app", "web"}
DOCKER = ["docker", "--host", "unix:///var/run/docker.sock"]
INSPECT = (
    '{"id":{{json .Id}},"image":{{json .Config.Image}},'
    '"status":{{json .State.Status}},"started":{{json .State.StartedAt}},'
    '"health":{{if .State.Health}}{{json .State.Health.Status}}{{else}}""{{end}},'
    '"policy":{{json .HostConfig.RestartPolicy.Name}},'
    '"project":{{json (index .Config.Labels "com.docker.compose.project")}},'
    '"service":{{json (index .Config.Labels "com.docker.compose.service")}},'
    '"oneoff":{{json (index .Config.Labels "com.docker.compose.oneoff")}},'
    '"mounts":{{json .Mounts}},"ports":{{json .NetworkSettings.Ports}}}'
)


class Refused(RuntimeError):
    """Only static, credential-free descriptions belong in this exception."""


def require(condition, message):
    if not condition:
        raise Refused(message)


def command(args, timeout=10):
    result = subprocess.run(args, capture_output=True, text=True, timeout=timeout)
    require(result.returncode == 0, "local daemon operation failed; output withheld")
    return result.stdout.strip()


def protected_file(path):
    require(path.is_file() and not path.is_symlink(), "regular protected file required")
    require(not path.stat().st_mode & 0o077, "protected file permissions required")


def guard(vault, allow):
    require(allow, "explicit isolated GitHub daemon restart flag required")
    expected = {
        "CI": "true", "GITHUB_ACTIONS": "true", "RUNNER_ENVIRONMENT": "github-hosted",
        "RUNNER_OS": "Linux", "GITHUB_REPOSITORY": "rafhael100/imagine-crm",
        "GITHUB_EVENT_NAME": "pull_request", "GITHUB_JOB": "real-private-http",
        "CRM_PRIVATE_PROJECT": PROJECT,
    }
    require(all(os.environ.get(k) == v for k, v in expected.items()),
            "dedicated GitHub-hosted CRM pull-request job required")
    require(os.geteuid() == 0, "isolated runner operator required")
    require(re.fullmatch(r"[0-9]+", os.environ.get("GITHUB_RUN_ID", "")),
            "GitHub run identity required")
    require(ROOT == Path("/home/runner/work/imagine-crm/imagine-crm")
            and Path(os.environ.get("GITHUB_WORKSPACE", "")).resolve() == ROOT,
            "standard isolated GitHub checkout required; VPS paths refused")
    temp = Path(os.environ.get("RUNNER_TEMP", "")).resolve()
    require(temp == Path("/home/runner/work/_temp"), "isolated runner temp directory required")
    envfile = Path(os.environ.get("CRM_PRIVATE_ENV_FILE", ""))
    require(envfile == temp / "crm-private.env" and vault == temp / "crm-private-data/fixtures.json",
            "exact runner-only environment and fixture paths required")
    protected_file(envfile)
    protected_file(vault)
    require(not vault.parent.is_symlink() and not vault.parent.stat().st_mode & 0o077,
            "private runner fixture directory required")
    require(not os.environ.get("DOCKER_HOST") and not os.environ.get("DOCKER_CONTEXT"),
            "remote or overridden Docker context refused")
    require(os.environ.get("CRM_PRIVATE_TEST_PORT", "18086") == "18086", "fixed loopback port required")
    sha = os.environ.get("CRM_PRIVATE_CANDIDATE_SHA", "")
    require(re.fullmatch(r"[a-f0-9]{40}", sha), "exact candidate SHA required")
    # Read only the two non-secret identities; never source or print runtime.env.
    settings = envfile.read_text()
    require("CRM_PRIVATE_IMAGE_TAG='" + sha + "'" in settings.splitlines()
            and "CRM_PRIVATE_DATA_DIR='" + str(vault.parent) + "'" in settings.splitlines(),
            "runtime image or fixture path does not match this CI run")
    require(command(DOCKER + ["info", "--format", "{{json .LiveRestoreEnabled}}"] ) == "false",
            "live restore would bypass the required container restart")
    require(command(DOCKER + ["info", "--format", "{{.Swarm.LocalNodeState}}"] ) == "inactive",
            "Swarm daemon refused")
    require(command(["systemctl", "is-active", "docker"]) == "active", "local Docker service must be active")
    return sha


def inventory(sha):
    # Check ALL containers, not just our filter: restarting a shared daemon is forbidden.
    ids = command(DOCKER + ["ps", "-aq"], timeout=3).splitlines()
    require(len(ids) == 4, "daemon must contain exactly the four isolated CRM containers")
    rows = [json.loads(line) for line in
            command(DOCKER + ["inspect", "--format", INSPECT] + ids, timeout=3).splitlines()]
    require(len(rows) == 4 and {row["service"] for row in rows} == SERVICES,
            "unexpected or duplicate service on daemon")
    for row in rows:
        require(row["project"] == PROJECT and str(row["oneoff"]).lower() == "false",
                "foreign or one-off container on daemon")
        require(row["policy"] == "unless-stopped", "runtime restart policy missing")
        for mount in row["mounts"]:
            if mount["Type"] == "bind":
                require(Path(mount["Source"]).resolve().is_relative_to(ROOT),
                        "bind mount outside isolated checkout")
            elif mount["Type"] == "volume":
                require(mount.get("Name") in {PROJECT + "_private_crm_pg", PROJECT + "_private_crm_redis"},
                        "foreign persistent volume")
        if row["service"] == "app":
            require(row["image"] == "imagine-crm-private-journey:" + sha, "unexpected application image")
        if row["service"] == "web":
            require((row["ports"] or {}).get("8080/tcp") == [{"HostIp": "127.0.0.1", "HostPort": "18086"}],
                    "web ingress is not the isolated loopback port")
    return {row["service"]: row for row in rows}


def ready(rows):
    return all(row["status"] == "running" for row in rows.values()) and rows["postgres"]["health"] == "healthy"


def public_ready():
    for path in ("/", "/login"):
        connection = http.client.HTTPConnection("127.0.0.1", 18086, timeout=2)
        try:
            connection.request("GET", path, headers={"Host": "crm-dev.imaginecomunica.com.br"})
            response = connection.getresponse()
            response.read()
            if response.status != 200:
                return False
        finally:
            connection.close()
    return True


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("fixtures", type=Path)
    parser.add_argument("--allow-isolated-github-daemon-restart", action="store_true")
    args = parser.parse_args()
    sha = guard(args.fixtures, args.allow_isolated_github_daemon_restart)
    before = inventory(sha)
    require(ready(before), "stack must already be running and PostgreSQL healthy")
    spec = importlib.util.spec_from_file_location("private_http", ROOT / "scripts/ops/test-private-crm-http.py")
    harness = importlib.util.module_from_spec(spec)
    argv = sys.argv
    try:
        sys.argv = [str(spec.origin), str(args.fixtures)]
        spec.loader.exec_module(harness)
    finally:
        sys.argv = argv
    account = harness.FIXTURES["clientA"]
    require(len(account["recovery"]) >= 4, "reserved fixture recovery factors absent")
    client = harness.Client()
    client.login(account, recovery=account["recovery"][2])
    lead_ids = sorted(harness.leads(client))
    require(lead_ids, "Chromium must have persisted a synthetic lead before this test")
    digest = hashlib.sha256(client.get("/exportar").encode()).digest()
    # Let Docker monitor successful startup and Redis flush its new session to AOF.
    time.sleep(11)
    stable = inventory(sha)
    require(ready(stable) and all(stable[s]["id"] == before[s]["id"]
            and stable[s]["started"] == before[s]["started"] for s in SERVICES),
            "stack changed before daemon restart")
    print("Starting isolated CI daemon restart; no container start command will follow.", flush=True)
    command(["systemctl", "restart", "docker"], timeout=60)
    deadline = time.monotonic() + 120
    recovered = False
    while time.monotonic() < deadline:
        try:
            after = inventory(sha)
            require(all(after[s]["id"] == before[s]["id"] for s in SERVICES),
                    "containers were recreated instead of automatically restarted")
            if ready(after) and all(after[s]["started"] != before[s]["started"] for s in SERVICES) and public_ready():
                recovered = True
                break
        except (Refused, OSError, subprocess.TimeoutExpired, http.client.HTTPException, json.JSONDecodeError):
            pass
        time.sleep(2)
    require(recovered, "automatic recovery timed out; no Compose start was attempted")
    require(sorted(harness.leads(client)) == lead_ids, "Redis session or persistent tenant records lost")
    require(hashlib.sha256(client.get("/exportar").encode()).digest() == digest, "persistent lead fields changed")
    fresh = harness.Client()
    fresh.login(account, recovery=account["recovery"][3])
    require(sorted(harness.leads(fresh)) == lead_ids, "fresh password/MFA login did not recover tenant records")
    for session in (client, fresh):
        session.get("/painel")
        session.post("/logout", {})
    print("PASS automatic daemon recovery: same four containers, HTTP/login, Redis session and synthetic lead records preserved.")


if __name__ == "__main__":
    try:
        main()
    except Refused as error:
        raise SystemExit("AUTOSTART REFUSED/FAILED: " + str(error)) from None
    except Exception:
        raise SystemExit("AUTOSTART FAILED: protected HTTP/runtime verification failed; no credentials or response bodies printed.") from None
