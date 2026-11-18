"""Prove malformed/ambiguous signed requests fail before CRM SQL admission."""
import json
import uuid


def run_json_boundary_regressions(context):
    run, dc, sql = context['run'], context['dc'], context['sql']
    cases = [
        ('valid-whitespace', 200),
        ('duplicate-company', 403),
        ('duplicate-escaped-company', 403),
        ('duplicate-mac', 403),
        ('duplicate-issued-at', 403),
        ('malformed', 403),
        ('array-root', 403),
        ('over-limit', 413),
    ]
    for mode, expected_status in cases:
        before = sql('SELECT count(*) FROM crm_auto_service.audit')
        response = run(dc + [
            'run', '--rm', '--no-deps', '-T', '--entrypoint', 'php',
            'portal', '/probe-json.php', mode,
        ])
        result = json.loads(response.stdout.strip().splitlines()[-1])
        request = str(uuid.UUID(result['request_id']))
        if result['status'] != expected_status:
            raise AssertionError('P21_JSON_BOUNDARY_STATUS_' + mode)
        nonces = sql("SELECT count(*) FROM crm_auto_service.nonces "
                     f"WHERE request_id='{request}'")
        audits = sql("SELECT count(*) FROM crm_auto_service.audit "
                     f"WHERE request_id='{request}'")
        expected_rows = '1' if expected_status == 200 else '0'
        if nonces != expected_rows or audits != expected_rows:
            raise AssertionError('P21_JSON_BOUNDARY_SQL_EFFECT_' + mode)
        after = sql('SELECT count(*) FROM crm_auto_service.audit')
        if int(after) - int(before) != int(expected_rows):
            raise AssertionError('P21_JSON_BOUNDARY_UNEXPECTED_AUDIT_' + mode)
    print('P21_JSON_MTLS_BOUNDARY_PASS cases=8 ambiguous_request_sql_effects=0')
