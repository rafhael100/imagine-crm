#!/usr/bin/env python3
"""Real CRM session → minimal lead → original Agents consumer, disposable CI only."""
import argparse
import hashlib
import http.client
from http.cookies import SimpleCookie
import importlib.util
import json
import os
from pathlib import Path
import re
import secrets
import subprocess
import sys
import time
import uuid

ROOT = Path(__file__).resolve().parents[2]
P65_SHA = "740abafc813dbde70f5a2ad6e138402ed7945276"
REPO = "rafhael100/imagine-crm"
PHASE = "preflight"
CHECKS = READS = ACCEPTED = DENIED = 0
HELPER = None
SOURCE = None
IMAGE = ""
PORT = 18086


class ProbeFailure(Exception):
    """Only fixed, credential-free labels from this harness may be displayed."""


def check(value, label):
    global CHECKS
    CHECKS += 1
    if not value:
        raise ProbeFailure(label)


def git(directory, *args):
    result = subprocess.run(["git", "-C", str(directory), *args], capture_output=True, text=True, timeout=10)
    check(result.returncode == 0, "source verification failed")
    return result.stdout.strip()


def preflight(disabled):
    global SOURCE, IMAGE, PORT
    check(os.environ.get("GITHUB_ACTIONS") == "true"
          and os.environ.get("GITHUB_REPOSITORY") == REPO
          and os.environ.get("GITHUB_EVENT_NAME") == "pull_request"
          and os.environ.get("CRM_OP01_RUNNER_ENVIRONMENT") == "github-hosted"
          and os.environ.get("CRM_PRIVATE_PROJECT") == "imagine-crm-journey-ci", "disposable CI required")
    event_path = Path(os.environ.get("GITHUB_EVENT_PATH", ""))
    check(event_path.is_file() and not event_path.is_symlink() and event_path.stat().st_size <= 1048576,
          "actual GitHub event required")
    event = json.loads(event_path.read_text())
    head = event.get("pull_request", {}).get("head", {})
    tag = os.environ.get("CRM_PRIVATE_IMAGE_TAG", "")
    check(re.fullmatch(r"[a-f0-9]{40}", tag) and head.get("sha") == tag
          and head.get("repo", {}).get("full_name") == REPO
          and event.get("repository", {}).get("full_name") == REPO
          and git(ROOT, "rev-parse", "HEAD") == tag, "candidate head and image mismatch")
    # GITHUB_SHA remains the real GitHub merge SHA; never substitute a head SHA.
    envfile = Path(os.environ.get("CRM_PRIVATE_ENV_FILE", ""))
    check(envfile.is_file() and not envfile.is_symlink() and not envfile.stat().st_mode & 0o077,
          "protected environment required")
    PORT = int(os.environ.get("CRM_PRIVATE_TEST_PORT", "18086"))
    check(1024 <= PORT <= 65535, "invalid loopback port")
    IMAGE = "imagine-crm-private-journey:" + tag
    if disabled:
        return
    SOURCE = Path(os.environ.get("AIA_P65_SOURCE_DIR", ""))
    check(SOURCE.is_dir() and not SOURCE.is_symlink(), "original Agents checkout required")
    SOURCE = SOURCE.resolve()
    check(git(SOURCE, "rev-parse", "HEAD") == P65_SHA
          and os.environ.get("AIA_P65_SOURCE_SHA", P65_SHA) == P65_SHA, "Agents source pin mismatch")
    git(SOURCE, "diff", "--exit-code", "HEAD", "--", "src/Integration/Crm/MinimalLeadProjection.php",
        "tools/p65-crm-minimal-read-probe.php")
    for path in ["src/Integration/Crm/MinimalLeadProjection.php", "tools/p65-crm-minimal-read-probe.php"]:
        check((SOURCE / path).is_file() and not (SOURCE / path).is_symlink(), "consumer source missing")


def capture(client, path, method="GET", body=None, headers=None):
    check(path.startswith("/") and not path.startswith("//") and "\r" not in path and "\n" not in path,
          "fixed loopback path required")
    fields = {"Host": "crm-dev.imaginecomunica.com.br", "Accept": "application/json", "Connection": "close"}
    if client is not None and client.cookies:
        fields["Cookie"] = "; ".join(key + "=" + value for key, value in client.cookies.items())
    fields.update(headers or {})
    connection = http.client.HTTPConnection("127.0.0.1", PORT, timeout=20)
    try:
        connection.request(method, path, body=body, headers=fields)
        response = connection.getresponse()
        raw = response.read(450001)
        check(len(raw) <= 450000, "bounded HTTP response required")
        pairs = response.getheaders()
        output_headers = {key.lower(): value for key, value in pairs}
        if client is not None:
            for key, value in pairs:
                if key.lower() == "set-cookie":
                    parsed = SimpleCookie(); parsed.load(value)
                    for name, morsel in parsed.items():
                        if morsel["max-age"] == "0": client.cookies.pop(name, None)
                        else: client.cookies[name] = morsel.value
        check("no-store" in output_headers.get("cache-control", "")
              and output_headers.get("x-content-type-options") == "nosniff"
              and not any(key.startswith("access-control-") for key in output_headers), "HTTP privacy headers missing")
        check(response.status != 200 or output_headers.get("content-type", "").split(";", 1)[0] == "application/json",
              "successful read is not a JSON response")
        return response.status, raw
    finally:
        connection.close()


def consumer(status, body, expected_ref):
    result = subprocess.run([
        "docker", "run", "--rm", "-i", "--network", "none", "--read-only", "--cap-drop", "ALL",
        "--security-opt", "no-new-privileges", "--pids-limit", "64", "--memory", "128m",
        "--user", "65534:65534", "--mount", "type=bind,src=" + str(SOURCE) + ",target=/agents,readonly",
        "--entrypoint", "php", IMAGE, "/agents/tools/p65-crm-minimal-read-probe.php",
        "--status", str(status), "--expected-ref", expected_ref,
    ], input=body, capture_output=True, timeout=20)
    accepted = status == 200
    marker = b"AIA_P65_CRM_MINIMAL_READ_ACCEPTED\n" if accepted else b"AIA_P65_CRM_MINIMAL_READ_DENIED\n"
    check(result.returncode == (0 if accepted else 2) and result.stdout == marker and result.stderr == b"",
          "original Agents consumer result mismatch")


def snapshot():
    tables = {"leads": "id", "tasks": "id", "automation_runs": "id", "preferences": "organization_id",
              "orders": "id", "trials": "organization_id", "funnels": "id", "invitations": "id", "accounts": "identity_id"}
    parts = ["'" + name + "',(SELECT md5(coalesce(jsonb_agg(to_jsonb(t) ORDER BY t." + key
             + "),'[]'::jsonb)::text) FROM public.crm_private_" + name + " t)" for name, key in tables.items()]
    return json.loads(HELPER.owner_sql("SELECT jsonb_build_object('business',jsonb_build_object(" + ",".join(parts)
        + "),'events',(SELECT coalesce(jsonb_agg(to_jsonb(e) ORDER BY e.id),'[]'::jsonb) FROM public.crm_private_events e))::text"))


def read(client, lead, status, label, *, stage=None, actor=None, company=None, path=None, method="GET", body=None, headers=None):
    global PHASE, READS, ACCEPTED, DENIED
    PHASE = label
    expected = str(uuid.UUID(lead))
    before = snapshot()
    actual, raw = capture(client, path or "/agentes/leads/" + expected + "/minimo", method, body, headers)
    check(actual == status, f"unexpected minimal read status {actual}, expected {status}")
    after = snapshot()
    check(before["business"] == after["business"], "read changed business state")
    previous = {event["id"]: event for event in before["events"]}
    current = {event["id"]: event for event in after["events"]}
    check(all(current.get(key) == value for key, value in previous.items()), "read changed previous audit")
    added = [value for key, value in current.items() if key not in previous]
    if status == 200:
        check(raw == json.dumps({"contract": "CRM_AIA_HOMOLOGATION_LEAD_READ_V1", "operation": "crm.lead.read_minimal",
              "status": "READ", "scope": "SYNTHETIC_PRIVATE_HOMOLOGATION",
              "projection": {"ref": expected, "stage": stage}, "operational": False}, separators=(",", ":")).encode(),
              "minimum response contract mismatch")
        check(len(added) == 1 and added[0]["action"] == "lead.minimal_read"
              and added[0]["object_id"] == expected and added[0]["actor_id"] == actor
              and added[0]["organization_id"] == company, "successful read audit mismatch")
        ACCEPTED += 1
    else:
        check(not added and b'"projection"' not in raw and b"OP01_FORGED_AUTHORITY" not in raw,
              "denial disclosed projection or wrote business audit")
        DENIED += 1
    consumer(actual, raw, expected)
    READS += 1
    return raw


def create_lead(client, suffix):
    before = set(HELPER.leads(client))
    client.post("/leads", {"title": "LEAD_TESTE_OP01_" + suffix, "value_cents": 12345})
    created = set(HELPER.leads(client)) - before
    check(len(created) == 1, "lead must be created once through CRM HTTP")
    return str(uuid.UUID(created.pop()))


def main(vault):
    global HELPER, PHASE
    check(vault.is_file() and not vault.is_symlink() and not vault.stat().st_mode & 0o077
          and vault.stat().st_size <= 65536, "protected fixture vault required")
    # The existing module is imported with its expected vault argument. Its main
    # journey is never called; this stack must contain only the three seed users.
    prior = sys.argv
    try:
        sys.argv = [str(ROOT / "scripts/ops/test-private-crm-http.py"), str(vault)]
        spec = importlib.util.spec_from_file_location("crm_op01_http_helpers", sys.argv[0])
        HELPER = importlib.util.module_from_spec(spec); spec.loader.exec_module(HELPER)
    finally:
        sys.argv = prior
    check(set(HELPER.FIXTURES) == {"admin", "clientA", "clientB"}, "fresh seed users required")
    check(HELPER.owner_sql("SELECT (SELECT count(*) FROM crm_private_leads)+(SELECT count(*) FROM crm_private_tasks)"
                          "+(SELECT count(*) FROM crm_private_automation_runs)+(SELECT count(*) FROM crm_private_events)") == "0",
          "fresh business stack required")
    HELPER.scenario("clear-cache")
    accounts = HELPER.FIXTURES
    a, b, admin = HELPER.Client(), HELPER.Client(), HELPER.Client()
    absent = str(uuid.uuid4())
    read(HELPER.Client(), absent, 303, "anonymous")
    pending = HELPER.Client(); pending.get("/login")
    _, location = pending.post("/login", {"login": accounts["clientA"]["login"], "password": accounts["clientA"]["password"]})
    check(location.endswith("/mfa"), "password-only session missing")
    read(pending, absent, 303, "pending-mfa")
    PHASE = "password-mfa-login"
    a.login(accounts["clientA"]); b.login(accounts["clientB"]); admin.login(dict(accounts["admin"], admin=True))
    lead = create_lead(a, "A"); lost = create_lead(a, "LOST"); peer = create_lead(b, "B")
    own = {"actor": accounts["clientA"]["identity"], "company": accounts["clientA"]["organization"]}
    peer_actor = {"actor": accounts["clientB"]["identity"], "company": accounts["clientB"]["organization"]}
    read(a, lead, 200, "new-stage", stage="new", **own)
    for version, stage in enumerate(["qualified", "proposal", "won"], 1):
        a.get("/painel?section=kanban"); a.post("/leads/" + lead, {"version": version, "stage": stage})
        read(a, lead, 200, stage + "-stage", stage=stage, **own)
    a.get("/painel?section=kanban"); a.post("/leads/" + lost, {"version": 1, "stage": "lost"})
    read(a, lost, 200, "lost-stage", stage="lost", **own)
    read(b, peer, 200, "independent-owner", stage="new", **peer_actor)
    foreign = read(a, peer, 404, "foreign-lead")
    missing = read(a, absent, 404, "missing-lead")
    check(foreign == missing, "foreign and missing resources differ publicly")
    read(b, lead, 404, "reverse-tenant-isolation")
    read(a, lead, 404, "malformed-resource", path="/agentes/leads/not-a-uuid/minimo")
    read(a, lead, 404, "noncanonical-resource", path="/agentes/leads/AAAAAAAA-AAAA-4AAA-8AAA-AAAAAAAAAAAA/minimo")
    read(admin, lead, 403, "admin-has-no-lead-authority")
    route = "/agentes/leads/" + lead + "/minimo"
    for query, status in [("?fields=ref", 422), ("?x=&x=1", 422), ("?tenant_id=OP01_FORGED_AUTHORITY", 400)]:
        read(a, lead, status, "query-refused", path=route + query)
    read(a, lead, 422, "get-body-refused", body=b"OP01_FORGED_AUTHORITY")
    for method in ["HEAD", "POST", "PUT", "DELETE"]:
        read(a, lead, 405, "method-" + method, method=method)
    for header in ["Authorization", "X-Tenant-Ref", "X-Principal-Ref", "X-Company-Id", "X-Aia-Claims", "X-Role"]:
        read(a, lead, 400, "authority-header-" + header, headers={header: "OP01_FORGED_AUTHORITY"})
    read(a, lead, 405, "method-override", method="POST", headers={"X-HTTP-Method-Override": "GET"})
    read(a, lead, 200, "control-after-rejections", stage="won",
         headers={"Origin": "https://untrusted.example.invalid"}, **own)

    PHASE = "member-invitation-otp-mfa"
    a.get("/painel?section=team"); page, _ = a.post("/equipe/convidar", {}, expected=200)
    invitation = re.search(r'data-test="invitation-code">([^<]+)', page).group(1).strip()
    member = HELPER.Client(); member.get("/convite")
    teammate = {"login": "lab-op01-member-" + secrets.token_hex(4), "password": secrets.token_hex(16)}
    member.post("/convite", dict(teammate, invitation=invitation, email=teammate["login"] + "@example.test", terms="simulated"))
    page = member.get("/cadastro/codigo")
    member.post("/cadastro/verificar", {"code": re.search(r'data-test="otp-code">\s*([0-9]{6})', page).group(1)})
    page = member.get("/cadastro/mfa")
    teammate["secret"] = re.search(r'data-test="setup-secret">\s*([A-Z2-7]{32})', page).group(1)
    HELPER.STEPS[teammate["login"]] = int(time.time()) // 30
    member.post("/cadastro/concluir", {"code": HELPER.totp(teammate["secret"])}, expected=200)
    member.login(teammate)
    member_id = HELPER.owner_sql("SELECT identity_id FROM crm_auth_credentials WHERE login_key='"
                                + hashlib.sha256(teammate["login"].encode()).hexdigest() + "'")
    read(member, lead, 200, "member-view", stage="won", actor=str(uuid.UUID(member_id)), company=own["company"])
    read(member, peer, 404, "member-isolation")

    PHASE = "membership-revocation"
    try:
        HELPER.scenario("revoke-membershipB")
        read(b, peer, 403, "revoked-membership")
    finally:
        HELPER.scenario("restore-membershipB")
    read(b, peer, 200, "restored-membership", stage="new", **peer_actor)
    identity = str(uuid.UUID(peer_actor["actor"]))
    try:
        HELPER.owner_sql("UPDATE crm_auth_credentials SET status='revoked',version=version+1 WHERE identity_id='" + identity + "'")
        read(b, peer, 303, "revoked-credential")
    finally:
        HELPER.owner_sql("UPDATE crm_auth_credentials SET status='active' WHERE identity_id='" + identity + "'")
    read(b, peer, 303, "old-credential-version")
    PHASE = "fresh-login-after-credential-revocation"
    b.login(accounts["clientB"])
    read(b, peer, 200, "fresh-credential-control", stage="new", **peer_actor)
    admin.get("/admin"); admin.post("/admin/contas/" + identity, {"status": "suspended"})
    read(b, peer, 303, "suspended-account")
    admin.get("/admin"); admin.post("/admin/contas/" + identity, {"status": "active"})
    read(b, peer, 303, "resume-does-not-revive-session")
    PHASE = "fresh-login-after-resume"
    b.login(accounts["clientB"])
    read(b, peer, 200, "resumed-account-control", stage="new", **peer_actor)

    PHASE = "audit-fault"
    privilege = "SELECT has_table_privilege('crm_homologation_app','public.crm_private_events','INSERT')"
    check(HELPER.owner_sql(privilege) == "t", "audit privilege baseline missing")
    try:
        HELPER.owner_sql("REVOKE INSERT ON public.crm_private_events FROM crm_homologation_app")
        check(HELPER.owner_sql(privilege) == "f", "audit fault did not apply")
        read(a, lead, 503, "audit-failure-no-projection")
    finally:
        HELPER.owner_sql("GRANT INSERT ON public.crm_private_events TO crm_homologation_app")
    check(HELPER.owner_sql(privilege) == "t", "audit privilege not restored")
    read(a, lead, 200, "audit-restored-control", stage="won", **own)
    stale = HELPER.Client(); stale.cookies = dict(a.cookies)
    a.get("/painel"); a.post("/logout", {})
    read(stale, lead, 303, "logout-stale-cookie")
    print(json.dumps({"schema": "CRM_OP01_SESSION_HTTP_VERIFIED_V1", "checks": CHECKS, "reads": READS,
          "accepted": ACCEPTED, "denied": DENIED, "stages": 5, "member_onboarded": True,
          "business_reads_unchanged": True, "success_audit_exact": True, "audit_fault_rolled_back": True,
          "agents_source_sha": P65_SHA, "service_delegation_verified": False, "operational": False}, separators=(",", ":")))
    print("CRM_OP01_REAL_SESSION_MINIMAL_READ_PASS")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("vault", nargs="?"); parser.add_argument("--disabled", action="store_true")
    arguments = parser.parse_args()
    try:
        check((arguments.disabled and arguments.vault is None) or (not arguments.disabled and arguments.vault is not None),
              "use either --disabled or the protected seed vault")
        preflight(arguments.disabled)
        if arguments.disabled:
            PHASE = "feature-disabled"
            status, raw = capture(None, "/agentes/leads/10000000-0000-4000-8000-000000000001/minimo")
            check(status == 404 and b'"projection"' not in raw, "disabled feature exposed a route")
            print("CRM_OP01_DEFAULT_OFF_PASS")
        else:
            main(Path(arguments.vault))
    except ProbeFailure as error:
        print("CRM_OP01_HTTP_FAILED phase=" + PHASE + " reason=" + str(error), file=sys.stderr)
        sys.exit(1)
    except Exception:
        # Never dump HTTP bodies, credentials, SQL, fixture paths or helper tracebacks.
        print("CRM_OP01_HTTP_FAILED phase=" + PHASE, file=sys.stderr)
        sys.exit(1)
