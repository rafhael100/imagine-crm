"""Exercise P27 receipts through the real CRM consumer and pinned Agents service.

Only the disposable P19 databases are touched. Existing fixtures are temporarily
aged together on both sides; their original bytes are restored before P19's
three-row backup proof. A fourth, never-sent fixture is removed after its denial.
No sleep simulates the 120-second delivery window and no receipt implies dispatch.
"""

import concurrent.futures
import hashlib
import json
import os
import re
import time
import uuid

from outbox_regression import _literal


def run_receipt_reconciliation_regressions(context):
    if (os.getenv('GITHUB_ACTIONS') != 'true'
            or os.getenv('GITHUB_REPOSITORY') != 'rafhael100/imagine-crm'
            or os.getenv('GITHUB_EVENT_NAME') != 'pull_request'):
        raise RuntimeError('RECEIPT_REGRESSION_CI_ONLY')
    sql, agents_sql = context['sql'], context['agents_sql']
    run, dc, deliver = context['run'], context['dc'], context['deliver']
    company, other = (str(uuid.UUID(context[name])) for name in ('A', 'B'))
    event = str(uuid.UUID(context['events'][0]))
    absent = str(uuid.uuid4())
    fingerprint = context['sender_fp']
    if not re.fullmatch('[a-f0-9]{64}', fingerprint):
        raise RuntimeError('RECEIPT_REGRESSION_CERTIFICATE_REQUIRED')
    if sql("SELECT current_database()||':'||mode FROM public.crm_private_installation WHERE id=1") != 'imagine_crm_private_homologation:synthetic-v1':
        raise RuntimeError('RECEIPT_REGRESSION_PRIVATE_LAB_REQUIRED')
    if agents_sql('SELECT current_database()') != 'imagine_agents_ci':
        raise RuntimeError('RECEIPT_REGRESSION_AGENTS_LAB_REQUIRED')

    def event_ref(value):
        return 'SYNTHETIC_' + value.replace('-', '').upper()

    def select_one(execute, table, where):
        value = execute(f'SELECT to_jsonb(t)::text FROM {table} t WHERE {where}')
        return json.loads(value) if value else None

    def update(execute, table, where, changes, allowed):
        if not changes or not set(changes) <= set(allowed):
            raise AssertionError('UNEXPECTED_RECEIPT_FIXTURE_CHANGE')
        assignments = ','.join(f'{key}={_literal(value)}' for key, value in changes.items())
        execute(f'UPDATE {table} SET {assignments} WHERE {where}')

    source_scope = f"company_id='{company}'"
    target_scope = source_scope + f" AND fingerprint='{fingerprint}'"
    outbox_scope = lambda value: source_scope + f" AND event_id='{value}'"
    inbox_scope = f"tenant='SYNTHETIC_TENANT_A' AND product='imagine-crm' AND event_ref='{event_ref(event)}'"
    outbox = lambda value=event: select_one(sql, 'crm_auto_service.outbox', outbox_scope(value))
    source_grant = lambda: select_one(sql, 'crm_auto_service.outbound_grants', source_scope)
    target_grant = lambda: select_one(agents_sql, 'auto_ia_lab.service_company_grants', target_scope)
    company_link = lambda: select_one(agents_sql, 'auto_ia_lab.company_links', source_scope)

    def change_outbox(value=event, **changes):
        update(sql, 'crm_auto_service.outbox', outbox_scope(value), changes,
               ('issued_at', 'expires_at', 'state', 'ack_hash', 'payload'))

    def change_source(**changes):
        update(sql, 'crm_auto_service.outbound_grants', source_scope, changes,
               ('active', 'revision', 'expires_at', 'tenant_ref'))

    def change_target(table, **changes):
        update(agents_sql, 'auto_ia_lab.' + table, target_scope, changes,
               ('active', 'revision', 'expires_at'))

    def digest(execute, table):
        value = execute('SELECT COALESCE(jsonb_agg(to_jsonb(t) ORDER BY to_jsonb(t)::text),'
                        f"'[]'::jsonb)::text FROM {table} t")
        return hashlib.sha256(value.encode()).hexdigest()

    def destination():
        return tuple(digest(agents_sql, table) for table in (
            'auto_ia_lab.event_inbox', 'auto_ia_lab.service_event_audit'))

    def reconcile(value=event, tenant=company, profile=None):
        args = dc + ['exec', '-T']
        if profile is not None:
            args += ['-e', 'CRM_AGENTS_RECEIPT_PROFILE=' + profile]
        return run(args + ['crm-app', 'php', '/reconcile-event.php', tenant, value],
                   ok=False, timeout=45)

    def probe(mode, value=event):
        record = outbox(value)
        result = run(dc + ['exec', '-T', 'crm-app', 'php', '/probe-receipt.php', mode,
                          company, record['tenant_ref'], event_ref(value),
                          hashlib.sha256(record['payload'].encode()).hexdigest()],
                     ok=False, timeout=45)
        if result.returncode != 0:
            raise AssertionError('P22_RECEIPT_HTTP_PROBE_FAILED_' + mode)
        return json.loads(result.stdout.strip())

    def unchanged(before, baseline, value=event):
        if outbox(value) != before or destination() != baseline:
            raise AssertionError('P22_RECEIPT_LOOKUP_MUTATED_STATE')

    def denied(marker, baseline, value=event, tenant=company, profile=None):
        before = outbox(value)
        result = reconcile(value, tenant, profile)
        if result.returncode != 2 or result.stderr.strip() != 'CRM_AGENTS_RECEIPT_UNRESOLVED':
            raise AssertionError(marker + '_NOT_UNRESOLVED')
        unchanged(before, baseline, value)
        print(marker + '_PASS')

    def sql_denial(value, message, tenant=company):
        # Prevent a missing endpoint, key or transport failure from passing a
        # source-authority negative. Demand the exact SQL denial as the sender.
        sql("BEGIN; SET LOCAL ROLE crm_auto_sender; DO $probe$ BEGIN "
            f"PERFORM crm_auto_service.receipt_candidate('{tenant}'::uuid,'{value}'::uuid); "
            "RAISE EXCEPTION 'RECEIPT_CANDIDATE_WAS_ADMITTED'; "
            "EXCEPTION WHEN SQLSTATE 'P0001' THEN "
            f"IF SQLERRM <> '{message}' THEN RAISE; END IF; END $probe$; ROLLBACK;")

    original = outbox()
    original_source = source_grant()
    original_target = target_grant()
    original_link = company_link()
    original_inbox = select_one(agents_sql, 'auto_ia_lab.event_inbox', inbox_scope)
    original_key = agents_sql("SELECT enabled FROM auto_ia_lab.mock_s2s_keys WHERE issuer='imagine-crm' AND key_id='SYNTHETIC_KEY_A'")
    original_destination = destination()
    original_outbox = digest(sql, 'crm_auto_service.outbox')
    original_source_events = digest(sql, 'public.crm_private_events')
    if (original['state'] != 'accepted' or not original['ack_hash']
            or original_inbox is None or original_key != 't'
            or not original_target['active'] or not original_link['active']):
        raise AssertionError('P22_ACCEPTED_SOURCE_AND_DESTINATION_FIXTURES_REQUIRED')
    if agents_sql('SELECT count(*) FROM auto_ia_lab.receipt_read_grants') != '0':
        raise AssertionError('P22_RECEIPT_READ_GRANTS_MUST_START_EMPTY')

    # The new destination login can SET only the restricted read role. Neither
    # destination ingress credentials nor a CRM sender get direct inbox access.
    for role in ('aia_private_receipt', 'aia_private_receipt_login'):
        for table, privilege in (('event_inbox', 'SELECT'), ('event_inbox', 'INSERT'),
                                 ('receipt_read_grants', 'UPDATE')):
            if agents_sql(f"SELECT has_table_privilege('{role}','auto_ia_lab.{table}','{privilege}')") != 'f':
                raise AssertionError('P22_RECEIPT_ROLE_DIRECT_TABLE_PRIVILEGE')
    if agents_sql("SELECT has_function_privilege('aia_private_transport','auto_ia_lab.read_company_event_receipt(uuid,text,jsonb)','EXECUTE')") != 'f':
        raise AssertionError('P22_INGRESS_ROLE_MUST_NOT_READ_RECEIPT')
    if sql("SELECT has_table_privilege('crm_auto_sender','crm_auto_service.outbox','SELECT')") != 'f':
        raise AssertionError('P22_SENDER_DIRECT_OUTBOX_PRIVILEGE')
    for service, forbidden in (
        ('portal', ['agents-receipt-db.password', 'agents-receipt.key', 'agents-signing.key']),
        ('crm-app', ['agents-receipt-db.password', 'agents-db.password']),
        ('agents-app', ['sender.password', 'crm.key']),
    ):
        code = 'foreach(' + json.dumps(['/certs/' + name for name in forbidden])
        code += ' as $path){if(file_exists($path))exit(2);}echo "RECEIPT_SECRETS_ISOLATED";'
        args = (['run', '--rm', '--no-deps', '-T', '--entrypoint', 'php', service]
                if service == 'portal' else ['exec', '-T', service, 'php'])
        if run(dc + args + ['-r', code]).stdout.strip() != 'RECEIPT_SECRETS_ISOLATED':
            raise AssertionError('P22_RECEIPT_SECRET_BUNDLE_ISOLATION')
    print('P22_RECEIPT_LOGIN_FUNCTION_ONLY_AND_SECRET_BUNDLES_PASS')

    now = int(sql('SELECT floor(extract(epoch FROM clock_timestamp()))::bigint'))
    stopped = False

    def stage_expiry_while_waiting(kind):
        # These bounded waits exercise the SQL clock after a real conflicting
        # row lock. They do not stand in for waiting out an event's 120s TTL.
        current = int(sql('SELECT floor(extract(epoch FROM clock_timestamp()))::bigint'))
        cutoff = current + 6
        if kind == 'grant':
            change_source(expires_at=cutoff)
            expected = 'OUTBOUND_GRANT_DENIED'
        else:
            change_source(expires_at=current + 600)
            change_outbox(state='pending', ack_hash=None, issued_at=cutoff - 120,
                          expires_at=cutoff)
            expected = 'OUTBOX_SCOPE_OR_WINDOW_DENIED'
        before, target_before = outbox(), destination()
        blocker_name = 'p22_expiry_blocker_' + kind
        caller_name = 'p22_expiry_caller_' + kind
        blocker_sql = (
            f"BEGIN; SET LOCAL application_name='{blocker_name}'; "
            "SET LOCAL statement_timeout='15000ms'; "
            f"SELECT 1 FROM crm_auto_service.outbox WHERE {outbox_scope(event)} FOR UPDATE; "
            "SELECT pg_sleep(9); COMMIT;"
        )
        caller_sql = (
            f"BEGIN; SET LOCAL application_name='{caller_name}'; SET LOCAL ROLE crm_auto_sender; "
            "SET LOCAL statement_timeout='15000ms'; SET LOCAL lock_timeout='12000ms'; "
            "DO $probe$ BEGIN "
            f"PERFORM crm_auto_service.stage_outbound('{company}'::uuid,'{event}'::uuid); "
            "RAISE EXCEPTION 'EXPIRY_AFTER_LOCK_WAS_ADMITTED'; "
            "EXCEPTION WHEN SQLSTATE 'P0001' THEN "
            f"IF SQLERRM <> '{expected}' THEN RAISE; END IF; END $probe$; ROLLBACK;"
        )
        with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
            blocker = pool.submit(sql, blocker_sql)
            for _ in range(30):
                held = sql("SELECT count(*) FROM pg_stat_activity a "
                           f"WHERE a.application_name='{blocker_name}' AND a.wait_event='PgSleep' "
                           "AND EXISTS(SELECT 1 FROM pg_locks l WHERE l.pid=a.pid "
                           "AND l.relation='crm_auto_service.outbox'::regclass "
                           "AND l.mode='RowShareLock' AND l.granted)")
                if held == '1':
                    break
                if blocker.done():
                    raise AssertionError('P22_EXPIRY_BLOCKER_ENDED_BEFORE_PROBE')
                time.sleep(.05)
            else:
                raise AssertionError('P22_EXPIRY_OUTBOX_ROW_NOT_LOCKED')
            caller = pool.submit(sql, caller_sql)
            for _ in range(30):
                waiting = sql("SELECT count(*) FROM pg_stat_activity a "
                              f"WHERE a.application_name='{caller_name}' AND a.wait_event_type='Lock' "
                              "AND EXISTS(SELECT 1 FROM pg_stat_activity b "
                              f"WHERE b.application_name='{blocker_name}' "
                              "AND b.pid=ANY(pg_blocking_pids(a.pid)))")
                if waiting == '1':
                    break
                if caller.done():
                    raise AssertionError('P22_EXPIRY_CALLER_DID_NOT_WAIT_FOR_OUTBOX')
                time.sleep(.05)
            else:
                raise AssertionError('P22_EXPIRY_CALLER_LOCK_NOT_OBSERVED')
            if int(sql('SELECT floor(extract(epoch FROM clock_timestamp()))::bigint')) >= cutoff:
                raise AssertionError('P22_EXPIRY_FIXTURE_EXPIRED_BEFORE_OBSERVED_WAIT')
            # Timeout errors have different SQLSTATEs and cannot satisfy this
            # probe's exact domain-error assertion after the blocker releases.
            caller.result()
            blocker.result()
        if int(sql('SELECT floor(extract(epoch FROM clock_timestamp()))::bigint')) < cutoff:
            raise AssertionError('P22_EXPIRY_CUTOFF_NOT_REACHED')
        unchanged(before, target_before)
        print('P22_STAGE_' + kind.upper() + '_EXPIRED_DURING_ROW_LOCK_DENIED_PASS')

    try:
        change_source(active=True, revision=original_source['revision'] + 1, expires_at=now + 600)
        stage_expiry_while_waiting('grant')
        stage_expiry_while_waiting('window')
        change_outbox(state='pending', ack_hash=None, issued_at=now - 121, expires_at=now - 1)
        update(agents_sql, 'auto_ia_lab.event_inbox', inbox_scope,
               {'issued_epoch': now - 121, 'expires_epoch': now - 1},
               ('issued_epoch', 'expires_epoch'))
        aged = outbox()
        if (aged['nonce_ref'] != original_inbox['nonce_ref']
                or hashlib.sha256(aged['payload'].encode()).hexdigest() != original_inbox['payload_hash']):
            raise AssertionError('P22_ORIGINAL_EVENT_IDENTITY_MISMATCH')
        baseline = destination()
        # Execute the exposed function as its actual caller: SQL NULL must not
        # bypass the digest check through three-valued boolean evaluation.
        sql("BEGIN; SET LOCAL ROLE crm_auto_sender; DO $probe$ BEGIN "
            f"PERFORM crm_auto_service.ack_outbound('{company}'::uuid,'{event}'::uuid,NULL); "
            "RAISE EXCEPTION 'NULL_RECEIPT_WAS_ACCEPTED'; "
            "EXCEPTION WHEN SQLSTATE 'P0001' THEN "
            "IF SQLERRM <> 'OUTBOUND_ACK_DENIED' THEN RAISE; END IF; END $probe$; ROLLBACK;")
        unchanged(aged, baseline)
        print('P22_SQL_NULL_ACK_DENIED_WITHOUT_MUTATION_PASS')
        # Event transport remains fail-closed even while the receipt endpoint is healthy.
        if deliver(event).returncode != 2:
            raise AssertionError('P22_EXPIRED_EVENT_TRANSPORT_WAS_RENEWED')
        unchanged(aged, baseline)
        denied('P22_RECEIPT_PROFILE_DISABLED', baseline, profile='disabled')
        probe('expect-denial')
        denied('P22_INGEST_GRANT_ALONE_CANNOT_RECONCILE', baseline)

        agents_sql("INSERT INTO auto_ia_lab.receipt_read_grants VALUES(" 
                   f"'{company}','{fingerprint}','SYNTHETIC_TENANT_A',"
                   f"'event.receipt.read',true,1,{now + 600});")
        if probe('valid') != {'status': 200, 'receipt_verified': True}:
            raise AssertionError('P22_REAL_RECEIPT_NOT_VERIFIED')
        unchanged(aged, baseline)
        for mode in ('wrong-signature', 'wrong-payload', 'wrong-company', 'wrong-tenant',
                     'wrong-event', 'wrong-operation', 'expired-query', 'duplicate-key',
                     'unicode-duplicate-key', 'cookie', 'authorization', 'no-cert-forged-header'):
            probe(mode)
            unchanged(aged, baseline)
        print('P22_REAL_RECEIPT_MTLS_CLOSED_QUERY_SIGNATURE_SCOPE_AND_WINDOW_PASS')

        for table in ('receipt_read_grants', 'service_company_grants'):
            before = select_one(agents_sql, 'auto_ia_lab.' + table, target_scope)
            for changes, reason in (({'active': False}, 'REVOKED'),
                                    ({'active': True, 'expires_at': now - 1}, 'EXPIRED')):
                change_target(table, **changes)
                probe('expect-denial')
                denied('P22_' + table.upper() + '_' + reason, baseline)
            change_target(table, active=before['active'], expires_at=before['expires_at'])
        agents_sql(f"UPDATE auto_ia_lab.company_links SET active=false WHERE {source_scope}")
        probe('expect-denial')
        denied('P22_DESTINATION_LINK_REVOKED', baseline)
        agents_sql(f"UPDATE auto_ia_lab.company_links SET active=true WHERE {source_scope}")
        agents_sql("UPDATE auto_ia_lab.mock_s2s_keys SET enabled=false WHERE issuer='imagine-crm' AND key_id='SYNTHETIC_KEY_A'")
        probe('expect-denial')
        denied('P22_DESTINATION_INGRESS_KEY_REVOKED', baseline)
        agents_sql("UPDATE auto_ia_lab.mock_s2s_keys SET enabled=true WHERE issuer='imagine-crm' AND key_id='SYNTHETIC_KEY_A'")

        for changes, message, marker in (
            ({'active': False}, 'OUTBOUND_GRANT_DENIED', 'SOURCE_REVOKED'),
            ({'active': True, 'expires_at': now - 1}, 'OUTBOUND_GRANT_DENIED', 'SOURCE_EXPIRED'),
            ({'expires_at': now + 600, 'tenant_ref': 'SYNTHETIC_P22_CHANGED'},
             'OUTBOX_SCOPE_OR_WINDOW_DENIED', 'SOURCE_TENANT_CHANGED'),
        ):
            change_source(**changes)
            sql_denial(event, message)
            denied('P22_RECEIPT_' + marker, baseline)
        change_source(tenant_ref=original_source['tenant_ref'])
        sql_denial(event, 'OUTBOUND_GRANT_DENIED', other)
        denied('P22_RECEIPT_CROSS_COMPANY', baseline, tenant=other)
        change_outbox(payload=original['payload'] + ' ')
        probe('expect-denial')
        denied('P22_RECEIPT_EXACT_PAYLOAD_BYTES_REQUIRED', baseline)
        change_outbox(payload=original['payload'])

        # A real staged source event that was never sent cannot be inferred to
        # have arrived. The lookup cannot create its row before explicit staging.
        actor, lead = (str(uuid.UUID(context[name])) for name in ('C', 'F'))
        sql(f"INSERT INTO crm_private_events VALUES('{absent}','{company}','{actor}','{lead}','lead.created',{now});")
        sql_denial(absent, 'OUTBOX_RECEIPT_DENIED')
        if outbox(absent) is not None:
            raise AssertionError('P22_RECEIPT_LOOKUP_CREATED_OUTBOX')
        sql("BEGIN; SET LOCAL ROLE crm_auto_sender; "
            f"SELECT crm_auto_service.stage_outbound('{company}','{absent}'); COMMIT;")
        change_outbox(absent, issued_at=now - 121, expires_at=now - 1)
        probe('expect-denial', absent)
        denied('P22_ABSENT_RECEIPT_REMAINS_PENDING_WITHOUT_RESEND', baseline, absent)

        with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
            responses = list(pool.map(lambda _: reconcile(), range(2)))
        for result in responses:
            if result.returncode != 0 or result.stdout.strip() != 'CRM_AGENTS_RECEIPT_VERIFIED_NO_DELIVERY':
                raise AssertionError('P22_EXPIRED_PENDING_RECONCILIATION_FAILED')
        confirmed = outbox()
        immutable = lambda value: {key: val for key, val in value.items() if key not in ('state', 'ack_hash')}
        if (confirmed['state'] != 'accepted'
                or re.fullmatch('[a-f0-9]{64}', confirmed['ack_hash'] or '') is None
                or confirmed['ack_hash'] == original['ack_hash']
                or immutable(confirmed) != immutable(aged)
                or destination() != baseline):
            raise AssertionError('P22_RECEIPT_CHANGED_EVENT_OR_DESTINATION')
        print('P22_LOST_ACK_EXPIRED_PENDING_CONCURRENT_RECEIPT_CONFIRMED_PASS synthetic_fixture_clock=1 event_renewal=0')

        run(dc + ['stop', 'agents-s2s'], timeout=45)
        stopped = True
        if 'agents-s2s' in run(dc + ['ps', '--status', 'running', '--services']).stdout.splitlines():
            raise AssertionError('P22_AGENTS_INGRESS_STILL_RUNNING')
        for _ in range(2):
            if reconcile().returncode != 0 or deliver(event).returncode != 0:
                raise AssertionError('P22_CONFIRMED_RECEIPT_REQUIRED_NETWORK')
        unchanged(confirmed, baseline)
        denied('P22_DESTINATION_UNAVAILABLE_REMAINS_UNRESOLVED', baseline, absent)
        print('P22_CONFIRMED_RECEIPT_DURABLE_REPEAT_WITH_DESTINATION_DOWN_PASS')
    finally:
        try:
            change_outbox(**{key: original[key] for key in (
                'issued_at', 'expires_at', 'state', 'ack_hash', 'payload')})
            change_source(**{key: original_source[key] for key in (
                'active', 'revision', 'expires_at', 'tenant_ref')})
            change_target('service_company_grants', **{key: original_target[key] for key in (
                'active', 'revision', 'expires_at')})
            agents_sql(f"DELETE FROM auto_ia_lab.receipt_read_grants WHERE {target_scope}")
            agents_sql(f"UPDATE auto_ia_lab.company_links SET active={_literal(original_link['active'])} WHERE {source_scope}")
            agents_sql("UPDATE auto_ia_lab.mock_s2s_keys SET enabled=true WHERE issuer='imagine-crm' AND key_id='SYNTHETIC_KEY_A'")
            update(agents_sql, 'auto_ia_lab.event_inbox', inbox_scope,
                   {key: original_inbox[key] for key in ('issued_epoch', 'expires_epoch')},
                   ('issued_epoch', 'expires_epoch'))
            sql(f"DELETE FROM crm_auto_service.outbox WHERE {outbox_scope(absent)}; "
                f"DELETE FROM crm_private_events WHERE id='{absent}' AND organization_id='{company}';")
        finally:
            if stopped:
                run(dc + ['start', 'agents-s2s'], timeout=45)

    if (outbox() != original or source_grant() != original_source
            or target_grant() != original_target or company_link() != original_link
            or destination() != original_destination
            or digest(sql, 'crm_auto_service.outbox') != original_outbox
            or digest(sql, 'public.crm_private_events') != original_source_events
            or outbox(absent) is not None
            or agents_sql('SELECT count(*) FROM auto_ia_lab.receipt_read_grants') != '0'
            or sql('SELECT count(*) FROM crm_auto_service.outbox') != '3'):
        raise AssertionError('P22_RECEIPT_FIXTURES_NOT_RESTORED')
    print('P22_RECEIPT_RECONCILIATION_PASS source_outbox=3 destination_inbox=3 n8n_dispatch=DISABLED event_renewal=0')
