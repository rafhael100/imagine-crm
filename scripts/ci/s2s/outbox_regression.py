"""Real PostgreSQL/HTTP regressions for P19, restricted to its disposable CI.

The harness calls ``run_outbox_regressions(locals())`` after its three events
have been accepted and before backup/restore. Only these existing synthetic
rows are temporarily aged/corrupted; the original rows and grant are restored.
No envelope renewal or operational recovery policy is implemented here.
"""

import hashlib
import json
import os
import uuid


def _literal(value):
    if value is None:
        return "NULL"
    if isinstance(value, bool):
        return "true" if value else "false"
    if isinstance(value, int):
        return str(value)
    if isinstance(value, str):
        return "'" + value.replace("'", "''") + "'"
    raise TypeError("UNSUPPORTED_SYNTHETIC_SQL_VALUE")


def run_outbox_regressions(context):
    """Exercise the actual sender while preserving the original P19 fixtures."""
    if (
        os.getenv("GITHUB_ACTIONS") != "true"
        or os.getenv("GITHUB_REPOSITORY") != "rafhael100/imagine-crm"
        or os.getenv("GITHUB_EVENT_NAME") != "pull_request"
    ):
        raise RuntimeError("OUTBOX_REGRESSION_CI_ONLY")

    sql, agents_sql = context["sql"], context["agents_sql"]
    deliver, run, dc = context["deliver"], context["run"], context["dc"]
    company = str(uuid.UUID(context["A"]))
    accepted_event, pending_event = (
        str(uuid.UUID(event)) for event in context["events"][:2]
    )
    if accepted_event == pending_event:
        raise AssertionError("DISTINCT_SYNTHETIC_EVENTS_REQUIRED")
    if sql(
        "SELECT current_database() || ':' || mode "
        "FROM public.crm_private_installation WHERE id=1"
    ) != "imagine_crm_private_homologation:synthetic-v1":
        raise RuntimeError("OUTBOX_REGRESSION_PRIVATE_LAB_REQUIRED")
    if agents_sql("SELECT current_database()") != "imagine_agents_ci":
        raise RuntimeError("OUTBOX_REGRESSION_AGENTS_LAB_REQUIRED")

    def row(event):
        value = sql(
            "SELECT to_jsonb(o)::text FROM crm_auto_service.outbox o "
            f"WHERE company_id='{company}' AND event_id='{event}'"
        )
        return json.loads(value)

    def grant():
        return json.loads(sql(
            "SELECT to_jsonb(g)::text FROM crm_auto_service.outbound_grants g "
            f"WHERE company_id='{company}'"
        ))

    def update_outbox(event, **changes):
        if not changes or not set(changes) <= {
            "issued_at", "expires_at", "state", "ack_hash"
        }:
            raise AssertionError("UNEXPECTED_OUTBOX_FIXTURE_CHANGE")
        assignments = ",".join(f"{key}={_literal(value)}" for key, value in changes.items())
        sql(
            f"UPDATE crm_auto_service.outbox SET {assignments} "
            f"WHERE company_id='{company}' AND event_id='{event}'"
        )

    def update_grant(**changes):
        if not changes or not set(changes) <= {
            "active", "revision", "expires_at", "tenant_ref"
        }:
            raise AssertionError("UNEXPECTED_GRANT_FIXTURE_CHANGE")
        assignments = ",".join(f"{key}={_literal(value)}" for key, value in changes.items())
        sql(
            f"UPDATE crm_auto_service.outbound_grants SET {assignments} "
            f"WHERE company_id='{company}'"
        )

    def destination_snapshot():
        # Include the audit as well as the inbox: duplicate transport must also
        # be detected even if the destination correctly deduplicates its inbox.
        result = []
        for table in ("auto_ia_lab.event_inbox", "auto_ia_lab.service_event_audit"):
            data = agents_sql(
                "SELECT COALESCE(jsonb_agg(to_jsonb(t) "
                f"ORDER BY to_jsonb(t)::text),'[]'::jsonb)::text FROM {table} t"
            )
            result.append(hashlib.sha256(data.encode("utf-8")).hexdigest())
        return tuple(result)

    originals = {event: row(event) for event in (accepted_event, pending_event)}
    original_grant = grant()
    for original in originals.values():
        if original["state"] != "accepted" or not original["ack_hash"]:
            raise AssertionError("ACCEPTED_FIXTURES_REQUIRED")
    destination_before = destination_snapshot()
    now = int(sql("SELECT floor(extract(epoch FROM clock_timestamp()))::bigint"))
    stopped = False

    def denied_unchanged(event, marker):
        before = row(event)
        response = deliver(event, company)
        if response.returncode != 2 or "CRM_AGENTS_EVENT_DENIED" not in response.stderr:
            raise AssertionError(marker + "_NOT_DENIED")
        if row(event) != before or destination_snapshot() != destination_before:
            raise AssertionError(marker + "_MUTATED_STATE")
        print(marker + "_PASS")

    try:
        update_grant(active=True, revision=original_grant["revision"] + 1,
                     expires_at=now + 600)
        valid_grant = grant()
        update_outbox(accepted_event, issued_at=now - 121, expires_at=now - 1)
        accepted_before = row(accepted_event)

        # A successful repeat while ingress is stopped proves no network
        # acknowledgement is needed for this already confirmed local event.
        run(dc + ["stop", "agents-s2s"], timeout=45)
        stopped = True
        running = run(dc + ["ps", "--status", "running", "--services"]).stdout.splitlines()
        if "agents-s2s" in running:
            raise AssertionError("AGENTS_INGRESS_STILL_RUNNING")
        for _ in range(2):
            response = deliver(accepted_event, company)
            if response.returncode != 0 or "CRM_AGENTS_ACK_VERIFIED_NO_DELIVERY" not in response.stdout:
                raise AssertionError("ACCEPTED_EXPIRED_RETRY_FAILED")
        if row(accepted_event) != accepted_before or destination_snapshot() != destination_before:
            raise AssertionError("ACCEPTED_EXPIRED_RETRY_CHANGED_STATE")
        print("P21_ACCEPTED_EXPIRED_REPEAT_WITH_DESTINATION_DOWN_NO_IO_PASS")

        update_grant(active=False, revision=valid_grant["revision"] + 1)
        denied_unchanged(accepted_event, "P21_ACCEPTED_EXPIRED_REVOKED_GRANT_DENIED")
        update_grant(active=True, revision=valid_grant["revision"], expires_at=now - 1)
        denied_unchanged(accepted_event, "P21_ACCEPTED_EXPIRED_EXPIRED_GRANT_DENIED")
        update_grant(expires_at=valid_grant["expires_at"], tenant_ref="SYNTHETIC_TENANT_P21_CHANGED")
        denied_unchanged(accepted_event, "P21_ACCEPTED_EXPIRED_CHANGED_TENANT_DENIED")
        update_grant(tenant_ref=valid_grant["tenant_ref"])

        for invalid_ack in (None, "", "g" * 64, "a" * 63):
            update_outbox(accepted_event, ack_hash=invalid_ack)
            denied_unchanged(accepted_event, "P21_ACCEPTED_EXPIRED_INVALID_ACK_DENIED")
        update_outbox(accepted_event, ack_hash=accepted_before["ack_hash"])

        # Existing accepted fixture is temporarily changed to the shape of a
        # lost-ACK pending row. Its persisted nonce/request/payload stay exact.
        update_outbox(pending_event, state="pending", ack_hash=None,
                      issued_at=now - 121, expires_at=now - 1)
        # A failed HTTP send alone is insufficient while ingress is stopped.
        # Assert rejection by the actual SQL authority, using its restricted
        # runtime role, before the adapter could attempt any network operation.
        sql(
            "BEGIN; SET LOCAL ROLE crm_auto_sender; DO $probe$ BEGIN "
            f"PERFORM crm_auto_service.stage_outbound('{company}'::uuid,"
            f"'{pending_event}'::uuid); "
            "RAISE EXCEPTION 'EXPIRED_PENDING_WAS_ADMITTED'; "
            "EXCEPTION WHEN SQLSTATE 'P0001' THEN "
            "IF SQLERRM <> 'OUTBOX_SCOPE_OR_WINDOW_DENIED' THEN RAISE; END IF; "
            "END $probe$; ROLLBACK;"
        )
        denied_unchanged(pending_event, "P21_PENDING_EXPIRED_NO_RENEWAL_DENIED")
    finally:
        # Preserve the three-row restore proof in P19 and the original revoked
        # source grant. These operations touch only the isolated lab fixtures.
        try:
            for event, original in originals.items():
                update_outbox(event, **{key: original[key] for key in (
                    "issued_at", "expires_at", "state", "ack_hash"
                )})
            update_grant(**{key: original_grant[key] for key in (
                "active", "revision", "expires_at", "tenant_ref"
            )})
        finally:
            if stopped:
                run(dc + ["start", "agents-s2s"], timeout=45)

    if any(row(event) != original for event, original in originals.items()):
        raise AssertionError("OUTBOX_FIXTURES_NOT_RESTORED")
    if grant() != original_grant or destination_snapshot() != destination_before:
        raise AssertionError("OUTBOX_REGRESSION_CHANGED_BASELINE")
    print("P21_OUTBOX_EXPIRY_REGRESSIONS_PASS synthetic_fixtures_only=1 envelope_renewal=0")
