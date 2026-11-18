#!/usr/bin/env python3
"""Actual human CRM session/MFA → native plan → mTLS Agents → reconciliation."""
from __future__ import annotations

from concurrent.futures import ThreadPoolExecutor
import hashlib
import http.client
from http.cookies import SimpleCookie
import importlib.util
import json
import os
from pathlib import Path
import re
import sys
import time
import urllib.parse
import uuid

import private_crm_owner_drafts_ci as ci

PHASE = "preflight"
CHECKS = REQUESTS = 0
HELPER = None
UUID = re.compile(r"[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}")


def check(value, label):
    global CHECKS
    CHECKS += 1
    if not value:
        raise ci.Failure(label)


def capture(client, route, *, method="GET", fields=None, raw=None, headers=None):
    global REQUESTS
    check(route.startswith("/") and not route.startswith("//") and "\n" not in route and "\r" not in route,
          "OWNER_DRAFT_FIXED_HTTP_PATH")
    values = {"Host": "crm-dev.imaginecomunica.com.br", "Accept": "text/html", "Connection": "close"}
    if client is not None and client.cookies:
        values["Cookie"] = "; ".join(k + "=" + v for k, v in client.cookies.items())
    if fields is not None:
        raw = urllib.parse.urlencode(fields, doseq=True)
        values["Content-Type"] = "application/x-www-form-urlencoded"
    values.update(headers or {})
    connection = http.client.HTTPConnection("127.0.0.1", 18086, timeout=20)
    try:
        connection.request(method, route, body=raw, headers=values)
        response = connection.getresponse()
        body = response.read(450001)
        check(len(body) <= 450000, "OWNER_DRAFT_BOUNDED_RESPONSE")
        pairs = response.getheaders()
        response_headers = {k.lower(): v for k, v in pairs}
        if client is not None:
            for name, value in pairs:
                if name.lower() == "set-cookie":
                    cookies = SimpleCookie(); cookies.load(value)
                    for key, morsel in cookies.items():
                        if morsel["max-age"] == "0":
                            client.cookies.pop(key, None)
                        else:
                            client.cookies[key] = morsel.value
            token = re.search(rb'name="_token" value="([A-Za-z0-9]{40})"', body)
            if token:
                client.csrf = token.group(1).decode()
        check("no-store" in response_headers.get("cache-control", "")
              and response_headers.get("x-content-type-options") == "nosniff"
              and not any(k.startswith("access-control-") for k in response_headers),
              "OWNER_DRAFT_PRIVACY_HEADERS")
        REQUESTS += 1
        return response.status, body.decode("utf-8", "strict"), response_headers.get("location", "")
    finally:
        connection.close()


def crm(query):
    return ci.sql(query)


def agents(query):
    return ci.sql(query, agents=True)


def native_snapshot():
    tables = {"leads": "id", "tasks": "id", "automation_runs": "id", "preferences": "organization_id"}
    fields = ["'" + name + "',(SELECT md5(coalesce(jsonb_agg(to_jsonb(t) ORDER BY t." + key
              + "),'[]'::jsonb)::text) FROM public.crm_private_" + name + " t)" for name, key in tables.items()]
    return json.loads(crm("SELECT jsonb_build_object(" + ",".join(fields) + ")::text"))


def counts():
    local = json.loads(crm("SELECT jsonb_build_object('intents',(SELECT count(*) FROM public.crm_private_owner_draft_intents),"
                           "'events',(SELECT count(*) FROM public.crm_private_events))::text"))
    remote = agents("SELECT jsonb_build_object('drafts',(SELECT count(*) FROM aia_p67_owner.drafts),"
                    "'nonces',(SELECT count(*) FROM aia_p67_owner.nonces),'audit',(SELECT count(*) FROM aia_p67_owner.audit))::text")
    return local, json.loads(remote)


def intent(draft):
    check(UUID.fullmatch(draft), "OWNER_DRAFT_INTENT_REFERENCE")
    raw = crm("SELECT to_jsonb(i)::text FROM public.crm_private_owner_draft_intents i WHERE draft_ref='" + draft + "'")
    check(raw, "OWNER_DRAFT_DURABLE_INTENT_MISSING")
    return json.loads(raw)


def remote_draft(draft):
    check(UUID.fullmatch(draft), "OWNER_DRAFT_REMOTE_REFERENCE")
    raw = agents("SELECT to_jsonb(d)::text FROM aia_p67_owner.drafts d WHERE draft_ref='" + draft + "'")
    check(raw, "OWNER_DRAFT_REMOTE_DRAFT_MISSING")
    return json.loads(raw)


def post(client, route):
    return capture(client, route, method="POST", fields={"_token": client.csrf})


def create(client, source, *, expected=303):
    before = native_snapshot()
    status, _, location = post(client, "/agentes/planejamentos/" + source + "/rascunho")
    check(status == expected, "OWNER_DRAFT_CREATE_STATUS_" + str(status))
    check(native_snapshot() == before, "OWNER_DRAFT_CREATE_CHANGED_NATIVE_BUSINESS")
    if expected == 303:
        path = urllib.parse.urlparse(location).path
        check(path.startswith("/agentes/rascunhos/") and UUID.fullmatch(path.rsplit("/", 1)[-1]),
              "OWNER_DRAFT_CREATE_REDIRECT")
        return path.rsplit("/", 1)[-1]
    return None


def read(client, draft, *, expected=200, confirmed=True, locale=None):
    before = native_snapshot()
    route = "/agentes/rascunhos/" + draft + ("?lang=" + locale if locale else "")
    status, body, _ = capture(client, route)
    check(status == expected, "OWNER_DRAFT_READ_STATUS_" + str(status))
    check(native_snapshot() == before, "OWNER_DRAFT_READ_CHANGED_NATIVE_BUSINESS")
    if expected in (200, 503):
        check('data-owner-draft-view="imagine"' in body
              and 'data-owner-draft-operational="false"' in body
              and 'data-owner-draft-executed="false"' in body,
              "OWNER_DRAFT_HONEST_PRESENTATION")
        if confirmed:
            row = intent(draft)
            check('data-owner-draft-state="confirmed"' in body and row["state"] == "confirmed"
                  and row["business"]["creator_id"] in body and row["business"]["source_run_id"] in body
                  and row["business"]["lead_id"] in body, "OWNER_DRAFT_HISTORICAL_PROJECTION")
        else:
            check('data-owner-draft-state="pending"' in body and "data-owner-draft-pending" in body
                  and "data-owner-draft-creator" not in body, "OWNER_DRAFT_UNKNOWN_NOT_DISCLOSED_AS_CONFIRMED")
    else:
        check("data-owner-draft-view" not in body and "CRM_AUTHENTICATED_OWNER" not in body,
              "OWNER_DRAFT_DENIAL_DISCLOSED_CONTENT")
    return body


def deny(client, route, expected, *, expected_location=None, **kwargs):
    before = counts(), native_snapshot()
    status, body, location = capture(client, route, **kwargs)
    check(status == expected, "OWNER_DRAFT_DENIAL_STATUS_" + str(status))
    if expected_location is not None:
        check(urllib.parse.urlparse(location).path == expected_location, "OWNER_DRAFT_DENIAL_REDIRECT")
    check((counts(), native_snapshot()) == before, "OWNER_DRAFT_DENIAL_CHANGED_DURABLE_STATE")
    check("data-owner-draft-view" not in body, "OWNER_DRAFT_DENIAL_DISCLOSED_PROJECTION")


def plan(client, account, suffix):
    before = set(HELPER.leads(client))
    client.post("/leads", {"title": "LEAD_TESTE_OWNER_" + suffix, "value_cents": 0})
    added = set(HELPER.leads(client)) - before
    check(len(added) == 1, "OWNER_DRAFT_REAL_LEAD_CREATION")
    lead = added.pop()
    client.post("/leads/" + lead, {"version": 1, "stage": "qualified"})
    rows = json.loads(crm("SELECT coalesce(jsonb_agg(to_jsonb(r)),'[]'::jsonb)::text FROM public.crm_private_automation_runs r "
                          "WHERE lead_id='" + lead + "' AND organization_id='" + account["organization"] + "'"))
    check(len(rows) == 1 and rows[0]["state"] == "pending" and rows[0]["actor_id"] == account["identity"],
          "OWNER_DRAFT_ORIGINAL_NATIVE_PENDING_SOURCE")
    return rows[0]


def main():
    global HELPER, PHASE
    ci.preflight()
    if len(sys.argv) == 2 and sys.argv[1] == "--disabled":
        for method, route in (("GET", "/agentes/rascunhos/" + str(uuid.uuid4())),
                              ("POST", "/agentes/planejamentos/" + str(uuid.uuid4()) + "/rascunho"),
                              ("POST", "/agentes/rascunhos/" + str(uuid.uuid4()) + "/reenviar")):
            status, _, _ = capture(None, route, method=method)
            check(status == 404, "OWNER_DRAFT_DEFAULT_OFF_ROUTE_PRESENT")
        print(json.dumps({"check": "CRM_OWNER_DRAFT_DEFAULT_OFF_PASS", "checks": CHECKS}))
        return
    check(len(sys.argv) == 2, "OWNER_DRAFT_VAULT_ARGUMENT")
    vault = Path(sys.argv[1]); ci.protected(vault)
    specification = importlib.util.spec_from_file_location("owner_draft_original_http", ci.ROOT / "scripts/ops/test-private-crm-http.py")
    HELPER = importlib.util.module_from_spec(specification)
    specification.loader.exec_module(HELPER)
    fixtures = HELPER.FIXTURES
    a = HELPER.Client(); b = HELPER.Client(); admin = HELPER.Client()
    PHASE = "original-human-login-and-mfa"
    a.login(fixtures["clientA"]); b.login(fixtures["clientB"]); admin.login(dict(fixtures["admin"], admin=True))
    account = fixtures["clientA"]; company = account["organization"]; actor = account["identity"]
    check(crm("SELECT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','owner_draft_lock_marker','UPDATE')"
              " AND NOT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','status','UPDATE')"
              " AND NOT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','team_role','UPDATE')"
              " AND NOT has_column_privilege('crm_homologation_iam','public.crm_private_accounts','organization_id','UPDATE')"
              " AND NOT has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_grants','active','UPDATE')"
              " AND NOT has_column_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','business','UPDATE')"
              " AND NOT has_table_privilege('crm_homologation_app','public.crm_private_owner_draft_intents','DELETE')") == "t",
          "OWNER_DRAFT_MARKER_OR_LEDGER_PRIVILEGE_OVERREACH")
    for client in (a, b):
        client.get("/painel?section=automation")
        client.post("/preferencias", {"automation_version": 0, "delay_seconds": 3600, "language": "pt",
                                     "rules[]": ["followup_qualified", "followup_proposal"]})
    first = plan(a, account, "FIRST")
    second = plan(a, account, "SECOND")
    route = "/agentes/planejamentos/" + first["id"] + "/rascunho"
    PHASE = "closed-human-input-and-forged-authority"
    deny(a, route + "?company_id=" + company, 422, method="POST", fields={"_token": a.csrf})
    for field in ("company_id", "creator_id", "rule_id", "business", "draft_ref"):
        deny(a, route, 422, method="POST", fields={"_token": a.csrf, field: "FORGED_OWNER_DRAFT"})
    deny(a, route, 422, method="POST", raw="_token=" + a.csrf + "&_token=" + a.csrf,
         headers={"Content-Type": "application/x-www-form-urlencoded"})
    deny(a, route, 419, method="POST", fields={"_token": "0" * 40})
    deny(a, route, 400, method="POST", fields={"_token": a.csrf}, headers={"X-Company-Id": company})
    deny(admin, route, 403, method="POST", fields={"_token": admin.csrf})
    deny(b, route, 404, method="POST", fields={"_token": b.csrf})
    crm("UPDATE crm_private_owner_draft_grants SET active=false WHERE organization_id='" + company + "' AND operation='" + ci.CREATE + "'")
    try:
        deny(a, route, 403, method="POST", fields={"_token": a.csrf})
    finally:
        crm("UPDATE crm_private_owner_draft_grants SET active=true WHERE organization_id='" + company + "' AND operation='" + ci.CREATE + "'")
    PHASE = "owner-creates-and-reads-persisted-draft"
    first_ref = create(a, first["id"])
    local = intent(first_ref); remote = remote_draft(first_ref)
    check(local["state"] == "confirmed" and remote["state"] == "DRAFT"
          and remote["authorship_source"] == "CRM_AUTHENTICATED_OWNER"
          and remote["creator_id"] == actor and remote["business"] == local["business"],
          "OWNER_DRAFT_ATTESTED_AUTHOR_AND_BUSINESS")
    business = local["business"]
    source_command = json.dumps(first["command"], ensure_ascii=False, sort_keys=True, separators=(",", ":")).encode()
    check(business["source_run_id"] == first["id"] and business["command_hash"] == hashlib.sha256(source_command).hexdigest()
          and business["source_created_at"] == first["created_at"]
          and business["projection"]["due_at"] == first["command"]["due_at"], "OWNER_DRAFT_NATIVE_COMMAND_PROVENANCE")
    for locale in ("pt", "en", "es"):
        body = read(a, first_ref, locale=locale)
        check('<html lang="' + ("pt-BR" if locale == "pt" else locale) + '"' in body, "OWNER_DRAFT_LOCALE")
        check(all(value not in body for value in (company, account["login"], account["password"], account["secret"],
              first["authority_session_id"], business["command_hash"], "LEAD_TESTE_OWNER_FIRST")),
              "OWNER_DRAFT_UNRELATED_DATA_DISCLOSURE")
    check(create(a, first["id"]) == first_ref and remote_draft(first_ref) == remote,
          "OWNER_DRAFT_DUPLICATE_CHANGED_ORIGINAL")
    detail = "/agentes/rascunhos/" + first_ref
    PHASE = "current-owner-reads-historical-author-without-claiming-authorship"
    # Controlled fixture change, not an owner-transfer feature. Both people
    # retain their actual CRM login/MFA sessions; the backend must re-read the
    # current account and membership and preserve the original author.
    successor = fixtures["clientB"]["identity"]
    successor_company = fixtures["clientB"]["organization"]
    successor_membership = str(uuid.uuid4())
    crm("BEGIN; UPDATE crm_private_accounts SET team_role='member' WHERE identity_id='" + actor + "';"
        " UPDATE crm_private_accounts SET organization_id='" + company + "' WHERE identity_id='" + successor + "';"
        " INSERT INTO crm_memberships(id,organization_id,global_identity_id,status,version) VALUES ('"
        + successor_membership + "','" + company + "','" + successor + "','active',1); COMMIT;")
    try:
        read(b, first_ref)
        check(remote_draft(first_ref) == remote and intent(first_ref)["business"]["creator_id"] == actor,
              "OWNER_DRAFT_SUCCESSOR_REWROTE_HISTORICAL_AUTHOR")
        deny(a, detail, 403)
        deny(b, "/agentes/planejamentos/" + second["id"] + "/rascunho", 409,
             method="POST", fields={"_token": b.csrf})
    finally:
        crm("BEGIN; UPDATE crm_private_accounts SET organization_id='" + successor_company + "' WHERE identity_id='" + successor + "';"
            " UPDATE crm_private_accounts SET team_role='owner' WHERE identity_id='" + actor + "';"
            " DELETE FROM crm_memberships WHERE id='" + successor_membership + "'; COMMIT;")
    PHASE = "current-owner-required-for-create-and-read"
    for query, restore in (("UPDATE crm_private_accounts SET team_role='member' WHERE identity_id='" + actor + "'",
                            "UPDATE crm_private_accounts SET team_role='owner' WHERE identity_id='" + actor + "'"),
                           ("UPDATE crm_private_accounts SET status='suspended' WHERE identity_id='" + actor + "'",
                            "UPDATE crm_private_accounts SET status='active' WHERE identity_id='" + actor + "'"),
                           ("UPDATE crm_memberships SET status='revoked' WHERE global_identity_id='" + actor + "'",
                            "UPDATE crm_memberships SET status='active' WHERE global_identity_id='" + actor + "'")):
        crm(query)
        try:
            deny(a, detail, 403)
            deny(a, "/agentes/planejamentos/" + second["id"] + "/rascunho", 403,
                 method="POST", fields={"_token": a.csrf})
        finally:
            crm(restore)
    deny(admin, detail, 403)
    deny(b, detail, 404)
    deny(a, "/agentes/rascunhos/" + str(uuid.uuid4()), 404)
    deny(a, detail, 405, method="HEAD")
    deny(a, detail + "?lang=pt&company_id=" + company, 422)
    deny(a, detail, 400, headers={"Authorization": "Bearer FORGED_OWNER_DRAFT"})
    PHASE = "independent-current-technical-read-grants"
    crm("UPDATE crm_private_owner_draft_grants SET active=false WHERE organization_id='" + company + "' AND operation='" + ci.READ + "'")
    try:
        deny(a, detail, 403)
    finally:
        crm("UPDATE crm_private_owner_draft_grants SET active=true WHERE organization_id='" + company + "' AND operation='" + ci.READ + "'")
    agents("UPDATE aia_p67_owner.service_grants SET active=false WHERE company_id='" + company + "' AND operation='" + ci.READ + "'")
    try:
        read(a, first_ref, expected=503, confirmed=False)
        check(intent(first_ref)["state"] == "confirmed", "OWNER_DRAFT_UNAVAILABLE_ERASED_HISTORICAL_CONFIRMATION")
    finally:
        agents("UPDATE aia_p67_owner.service_grants SET active=true WHERE company_id='" + company + "' AND operation='" + ci.READ + "'")
    read(a, first_ref)
    PHASE = "historical-read-after-source-invalidation"
    a.post("/leads/" + first["lead_id"], {"version": 2, "stage": "lost"})
    check(crm("SELECT state FROM crm_private_automation_runs WHERE id='" + first["id"] + "'") == "cancelled",
          "OWNER_DRAFT_ORIGINAL_ENGINE_INVALIDATION")
    read(a, first_ref)
    before_remote = remote_draft(first_ref)
    status, _, location = post(a, detail + "/reenviar")
    check(status == 303 and location.endswith(detail) and remote_draft(first_ref) == before_remote,
          "OWNER_DRAFT_RETRY_DID_NOT_RECONCILE_FIRST")
    PHASE = "trial-denies-create-but-preserves-authorized-history"
    trial = json.loads(crm("SELECT to_jsonb(t)::text FROM crm_private_trials t WHERE organization_id='" + company + "'"))
    now = int(time.time())
    crm("UPDATE crm_private_trials SET started_at=" + str(now - 1209601) + ",expires_at=" + str(now - 1)
        + " WHERE organization_id='" + company + "'")
    try:
        deny(a, "/agentes/planejamentos/" + second["id"] + "/rascunho", 403, method="POST", fields={"_token": a.csrf})
        read(a, first_ref)
    finally:
        crm("UPDATE crm_private_trials SET started_at=" + str(trial["started_at"]) + ",expires_at=" + str(trial["expires_at"])
            + " WHERE organization_id='" + company + "'")
    PHASE = "concurrent-native-source-idempotency"
    with ThreadPoolExecutor(max_workers=2) as executor:
        # Independent HTTP objects preserve the same verified human authority.
        copies = [HELPER.Client(), HELPER.Client()]
        for client in copies:
            client.cookies = dict(a.cookies); client.csrf = a.csrf
        futures = [executor.submit(create, client, second["id"]) for client in copies]
        references = [future.result(timeout=25) for future in futures]
    check(references[0] == references[1], "OWNER_DRAFT_CONCURRENT_DUPLICATION")
    PHASE = "response-loss-after-remote-commit-and-local-revocation-order"
    loss = plan(a, account, "LOSS")
    ci.gateway_fault("delay")
    try:
        with ThreadPoolExecutor(max_workers=1) as executor:
            future = executor.submit(create, a, loss["id"])
            loss_ref = None
            for _ in range(40):
                candidate = crm("SELECT draft_ref::text FROM crm_private_owner_draft_intents WHERE source_run_id='" + loss["id"] + "'")
                if candidate and agents("SELECT count(*) FROM aia_p67_owner.drafts WHERE draft_ref='" + candidate + "'") == "1":
                    loss_ref = candidate
                    break
                time.sleep(0.05)
            check(loss_ref is not None and not future.done(), "OWNER_DRAFT_INTENT_AND_REMOTE_COMMIT_BEFORE_ACK")
            # The held CRM membership serializes its revocation during nominal I/O.
            blocked = ci.compose("exec", "-T", "postgres", "psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1",
                "-U", "crm_homologation_owner", "-d", "imagine_crm_private_homologation", check=False,
                data=("BEGIN; SET LOCAL lock_timeout='100ms'; UPDATE crm_memberships SET status='revoked' WHERE global_identity_id='"
                      + actor + "'; COMMIT;").encode())
            check(blocked.returncode != 0 and b"lock timeout" in blocked.stderr, "OWNER_DRAFT_MEMBERSHIP_NOT_HELD_DURING_IO")
            check(future.result(timeout=20) == loss_ref, "OWNER_DRAFT_LOSS_REFERENCE_CHANGED")
        check(intent(loss_ref)["state"] == "pending", "OWNER_DRAFT_TIMEOUT_MISREPORTED_CONFIRMATION")
        crm("UPDATE crm_memberships SET status='revoked' WHERE global_identity_id='" + actor + "'")
        try:
            deny(a, "/agentes/rascunhos/" + loss_ref, 403)
        finally:
            crm("UPDATE crm_memberships SET status='active' WHERE global_identity_id='" + actor + "'")
    finally:
        ci.gateway_fault(None)
    immutable_loss = remote_draft(loss_ref)
    read(a, loss_ref)
    check(remote_draft(loss_ref) == immutable_loss, "OWNER_DRAFT_READ_RECREATED_REMOTE_AFTER_TIMEOUT")
    PHASE = "response-correlation-tamper-and-recovery"
    tampered = plan(a, account, "TAMPER")
    ci.gateway_fault("tamper")
    try:
        tampered_ref = create(a, tampered["id"])
        check(intent(tampered_ref)["state"] == "pending", "OWNER_DRAFT_TAMPERED_ACK_ACCEPTED")
    finally:
        ci.gateway_fault(None)
    read(a, tampered_ref)
    PHASE = "intent-audit-failure-before-any-remote-call"
    rejected_plan = plan(a, account, "PREAUDIT")
    crm("CREATE FUNCTION public.owner_draft_ci_pre_audit_fault() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN "
        "RAISE EXCEPTION 'OWNER_DRAFT_CI_PRE_AUDIT_FAULT'; END $$; REVOKE ALL ON FUNCTION public.owner_draft_ci_pre_audit_fault() FROM PUBLIC; "
        "CREATE TRIGGER owner_draft_ci_pre_audit_fault BEFORE INSERT ON crm_private_events FOR EACH ROW "
        "WHEN (NEW.action='agents.draft.intent_created') EXECUTE FUNCTION public.owner_draft_ci_pre_audit_fault();")
    try:
        deny(a, "/agentes/planejamentos/" + rejected_plan["id"] + "/rascunho", 503,
             method="POST", fields={"_token": a.csrf})
    finally:
        crm("DROP TRIGGER owner_draft_ci_pre_audit_fault ON crm_private_events; DROP FUNCTION public.owner_draft_ci_pre_audit_fault();")
    PHASE = "audit-failure-after-remote-commit-and-recovery"
    audit_plan = plan(a, account, "AUDIT")
    crm("CREATE FUNCTION public.owner_draft_ci_audit_fault() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN "
        "RAISE EXCEPTION 'OWNER_DRAFT_CI_AUDIT_FAULT'; END $$; REVOKE ALL ON FUNCTION public.owner_draft_ci_audit_fault() FROM PUBLIC; "
        "CREATE TRIGGER owner_draft_ci_audit_fault BEFORE INSERT ON crm_private_events FOR EACH ROW "
        "WHEN (NEW.action='agents.draft.confirmed') EXECUTE FUNCTION public.owner_draft_ci_audit_fault();")
    try:
        before = native_snapshot()
        status, _, _ = post(a, "/agentes/planejamentos/" + audit_plan["id"] + "/rascunho")
        check(status == 503, "OWNER_DRAFT_AUDIT_FAILURE_MISREPORTED_SUCCESS")
        audit_ref = crm("SELECT draft_ref::text FROM crm_private_owner_draft_intents WHERE source_run_id='" + audit_plan["id"] + "'")
        check(UUID.fullmatch(audit_ref) and intent(audit_ref)["state"] == "pending"
              and remote_draft(audit_ref)["state"] == "DRAFT" and native_snapshot() == before,
              "OWNER_DRAFT_DURABLE_INTENT_LOST_AFTER_AUDIT_FAILURE")
    finally:
        crm("DROP TRIGGER owner_draft_ci_audit_fault ON crm_private_events; DROP FUNCTION public.owner_draft_ci_audit_fault();")
    read(a, audit_ref)
    PHASE = "current-session-and-causal-session-revocation"
    causal_plan = plan(a, account, "CAUSAL")
    fresh = HELPER.Client(); fresh.login(account, recovery=account["recovery"][0])
    crm("UPDATE crm_auth_sessions SET state='revoked' WHERE id='" + causal_plan["authority_session_id"] + "'")
    # SessionRevoked extends AuthenticationException. The existing private IAM
    # contract redirects to login; this is never a successful draft response.
    deny(a, "/agentes/rascunhos/" + first_ref, 303, expected_location="/login")
    deny(fresh, "/agentes/planejamentos/" + causal_plan["id"] + "/rascunho", 409,
         method="POST", fields={"_token": fresh.csrf})
    read(fresh, first_ref)
    PHASE = "real-browser-source-preparation"
    # Chromium creates a NEW source in its own session, avoiding a causal-session fixture.
    evidence = {"first_ref": first_ref, "foreign_company": fixtures["clientB"]["organization"],
                "http_checks": CHECKS, "http_requests": REQUESTS}
    (vault.parent / "owner-drafts-browser.json").write_text(json.dumps(evidence))
    (vault.parent / "owner-drafts-browser.json").chmod(0o600)
    print(json.dumps({"check": "CRM_OWNER_DRAFT_REAL_HTTP_PASS", "checks": CHECKS, "requests": REQUESTS,
                      "human_session_mfa_executed": True, "mtls_agents_executed": True,
                      "remote_ack_loss_reconciled": True, "audit_failure_reconciled": True,
                      "native_source_unchanged_by_draft_flow": True, "operational": False}))


if __name__ == "__main__":
    try:
        main()
    except (ci.Failure, AssertionError, OSError, KeyError, ValueError, TimeoutError) as error:
        print(json.dumps({"check": "CRM_OWNER_DRAFT_REAL_HTTP_FAILED", "phase": PHASE,
                          "reason": str(error) if isinstance(error, (ci.Failure, AssertionError)) else type(error).__name__}),
              file=sys.stderr)
        raise SystemExit(2)
