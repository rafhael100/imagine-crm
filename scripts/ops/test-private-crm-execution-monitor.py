#!/usr/bin/env python3
"""Authenticated monitor HTTP on the disposable private CRM stack.

The fixtures are presentation contracts, not a claim of another n8n run.
Real emitter/n8n/reconciler evidence belongs to Agents PR472. Never prints HTML,
cookies, credentials, SQL payloads or signed receipts; snapshot output is hashes.
"""
from contextlib import contextmanager
from html.parser import HTMLParser
import http.client
import importlib.util
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import time
import urllib.parse
import uuid

sys.dont_write_bytecode = True
ROOT = Path(__file__).resolve().parents[2]
CHECKS = 0
PHASE = 'startup'


def check(condition, message):
    global CHECKS
    CHECKS += 1
    if not condition:
        raise AssertionError(message)


def guard():
    check(os.environ.get('GITHUB_ACTIONS') == 'true'
          and os.environ.get('RUNNER_ENVIRONMENT') == 'github-hosted'
          and os.environ.get('GITHUB_REPOSITORY') == 'rafhael100/imagine-crm'
          and os.environ.get('GITHUB_EVENT_NAME') == 'pull_request',
          'real GitHub-hosted CRM pull request required')
    check(os.environ.get('CRM_PRIVATE_PROJECT') == 'imagine-crm-journey-ci'
          and os.environ.get('CRM_PRIVATE_CI_MONITOR_PROFILE') == 'execution-monitor-v1'
          and os.environ.get('CRM_N8N_EXECUTION_MONITOR') == 'synthetic-private-v1',
          'explicit disposable monitor profile required')
    check(len(sys.argv) in (2, 3), 'protected fixture path required')
    vault = Path(sys.argv[1]).resolve()
    check(vault.is_file() and not vault.is_symlink() and not vault.stat().st_mode & 0o077,
          'protected fixture vault required')
    check(vault.name == 'execution-monitor-fixtures.json', 'monitor fixture vault required')
    envfile = Path(os.environ['CRM_PRIVATE_ENV_FILE']).resolve()
    check(envfile.is_file() and not envfile.stat().st_mode & 0o077,
          'protected environment required')
    return vault, envfile


def run(command, *, timeout=60):
    result = subprocess.run(command, cwd=ROOT, capture_output=True, text=True, timeout=timeout)
    if result.returncode:
        causes = []
        for line in result.stderr.splitlines():
            _, marker, value = line.partition('PRIVATE_EXECUTION_MONITOR_FIXTURE_FAILURE ')
            if not marker:
                continue
            try:
                diagnostic = json.loads(value)
            except (ValueError, TypeError):
                continue
            for cause in diagnostic.get('causes', [])[:3]:
                if (isinstance(cause, dict)
                    and re.fullmatch(r'[A-Za-z0-9_\\]+', cause.get('type', ''))
                    and re.fullmatch(r'[A-Za-z0-9_.-]+', cause.get('source', ''))
                    and isinstance(cause.get('line'), int)):
                    item = {key: cause[key] for key in ('type', 'source', 'line')}
                    if re.fullmatch(r'[A-Z0-9]{5}', cause.get('sqlstate', '')):
                        item['sqlstate'] = cause['sqlstate']
                    causes.append(item)
        check(False, 'isolated monitor command failed: exit=' + str(result.returncode)
              + ' sanitized_causes=' + json.dumps(causes, sort_keys=True))
    return result.stdout.strip()


def compose(*arguments, override=None, timeout=60):
    command = ['docker', 'compose', '--env-file', str(ENVFILE), '-p', 'imagine-crm-journey-ci',
               '-f', str(ROOT / 'infra/homologation/compose.yaml')]
    if override is not None:
        command += ['-f', str(override)]
    return run(command + list(arguments), timeout=timeout)


def fixture(mode):
    return compose('run', '--rm', '-T', '-e', 'CRM_PRIVATE_CI_PROJECT=imagine-crm-journey-ci',
                   '-e', 'CRM_PRIVATE_CI_MONITOR_PROFILE=execution-monitor-v1', 'tools',
                   'scripts/ops/seed-private-crm-execution-monitor.php', mode)


def snapshot():
    value = json.loads(fixture('snapshot'))
    check(len(value) >= 20 and 'crm_auto_service.execution_observations' in value,
          'complete business and S2S snapshot required')
    check(all(set(row) == {'rows', 'sha256'} and isinstance(row['rows'], int)
              and re.fullmatch(r'[a-f0-9]{64}', row['sha256']) for row in value.values()),
          'invalid snapshot metadata')
    return value


def unchanged(before):
    after = snapshot()
    different = [table for table in sorted(set(before) | set(after)) if before.get(table) != after.get(table)]
    check(not different, 'business/S2S mutation: ' + ','.join(different))


@contextmanager
def temporary_fixture(change, restore):
    fixture(change)
    try:
        yield
    finally:
        fixture(restore)


class Monitor(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.attributes = []
        self.rows = []
        self.row_states = {}
        self.article = []
        self.feed(html)

    def handle_starttag(self, tag, attributes):
        fields = dict(attributes)
        self.attributes.append((tag, fields))
        if tag == 'article':
            self.article.append(fields.get('data-execution-event'))
        if 'data-execution-event' in fields:
            self.rows.append(fields['data-execution-event'])
        if 'data-execution-state' in fields and self.article and self.article[-1]:
            self.row_states.setdefault(self.article[-1], []).append(fields['data-execution-state'])

    def handle_endtag(self, tag):
        if tag == 'article' and self.article:
            self.article.pop()

    def values(self, attribute):
        return [fields[attribute] for _, fields in self.attributes if attribute in fields]


def safe_page(content):
    check('SQLSTATE' not in content and 'crm_auto_service' not in content
          and 'Stack trace' not in content and 'PDOException' not in content,
          'internal database details exposed')
    for actor in ('clientA', 'clientB', 'member', 'clientEmpty', 'admin'):
        account = FIXTURES[actor]
        for secret in [account['password'], account['secret'], *account['recovery']]:
            check(secret not in content, 'credential exposed in rendered page')
    for field in ('payload_hash', 'task_payload_hash', 'receipt_hash', 'nonce_ref',
                  'SYNTHETIC_EXECUTION_STATUS_KEY', 'AUTO_IA_EXECUTION_STATUS_V1'):
        check(field not in content, 'transport evidence exposed in monitor page')


def list_page(client, actor='clientA', language='pt'):
    html = client.get('/painel?section=n8n&lang=' + language)
    safe_page(html)
    parsed = Monitor(html)
    check(parsed.values('data-execution-monitor') == [language], 'localized monitor wrapper absent')
    records = sorted(EVENTS.get(actor, []), key=lambda row: (row['occurred_at'], row['event_id']), reverse=True)[:50]
    check(parsed.rows == [row['event_id'] for row in records], 'event window or deterministic ordering differs')
    check(parsed.row_states == {row['event_id']: [row['state']] for row in records},
          'observed state differs from absent projection')
    check(not parsed.values('data-execution-selected') and not parsed.values('data-execution-sequence'),
          'list leaked detail history')
    check(bool(parsed.values('data-execution-empty')) == (not records), 'empty-state semantics differ')
    return parsed, html


def detail_page(client, event, language='pt'):
    html = client.get('/painel?section=n8n&event=' + event['event_id'] + '&lang=' + language)
    safe_page(html)
    parsed = Monitor(html)
    check(parsed.values('data-execution-monitor') == [language]
          and parsed.values('data-execution-selected') == [event['event_id']], 'selected event missing')
    check(not parsed.rows, 'detail unexpectedly rendered list payload')
    sequences = [int(item) for item in parsed.values('data-execution-sequence')]
    check(sequences == list(range(event['history_count'], 0, -1))[:20], 'history order/count differs')
    check(len(sequences) <= 20, 'history display exceeded its structural window')
    check(parsed.values('data-execution-state') == [event['state'], *event['history_states']],
          'history state differs from persisted observation')
    check(bool(parsed.values('data-execution-unobserved')) == (event['history_count'] == 0),
          'unobserved projection was confused with a signed NOT_STAGED fact')
    return parsed, html


def reject(client, path, status):
    html = client.get(path, expected=status)
    safe_page(html)
    check('data-execution-monitor=' not in html, 'denied request exposed monitor content')
    return html


def method_denied(client, method):
    connection = http.client.HTTPConnection('127.0.0.1', HTTP.PORT, timeout=20)
    body = urllib.parse.urlencode({'_token': client.csrf})
    headers = {'Host': 'crm-dev.imaginecomunica.com.br', 'Accept': 'text/html',
               'Cookie': '; '.join(key + '=' + value for key, value in client.cookies.items()),
               'Content-Type': 'application/x-www-form-urlencoded'}
    connection.request(method, '/painel?section=n8n', body=body, headers=headers)
    response = connection.getresponse()
    html = response.read().decode('utf-8', errors='replace')
    connection.close()
    check(response.status == 405, 'monitor accepted unsupported method ' + method)
    check('data-execution-monitor=' not in html, 'unsupported method exposed monitor content')
    safe_page(html)


def runtime_separation():
    identity = compose('ps', '-q', 'app')
    inspection = json.loads(run(['docker', 'inspect', identity]))[0]
    names = {value.partition('=')[0] for value in inspection['Config']['Env']}
    check(not any(name.startswith(('CRM_AUTO_S2S_', 'CRM_AGENTS_', 'AGENTS_PRIVATE_')) for name in names),
          'web application received S2S configuration')
    check('CRM_PRIVATE_OWNER_PASSWORD' not in names and 'CRM_PRIVATE_BACKUP_KEY' not in names,
          'web application received operator credentials')
    check(not any(mount['Destination'] in ('/private', '/certs') for mount in inspection['Mounts']),
          'web application received protected operator/key mounts')


def restart(override=None):
    compose('up', '-d', '--no-deps', '--no-build', '--force-recreate', 'app', 'web', override=override, timeout=120)
    for _ in range(40):
        connection = http.client.HTTPConnection('127.0.0.1', HTTP.PORT, timeout=2)
        try:
            connection.request('GET', '/up', headers={'Host': 'crm-dev.imaginecomunica.com.br'})
            result = connection.getresponse(); result.read()
            if result.status == 200:
                return
        except (OSError, http.client.HTTPException):
            pass
        finally:
            connection.close()
        time.sleep(.5)
    raise AssertionError('private application restart did not become healthy')


def profiles(client):
    global PHASE
    override = VAULT.parent / 'execution-monitor-app-profile.json'
    check(not override.exists(), 'unexpected profile override file')
    before = snapshot()
    try:
        for name, environment in (
            ('disabled', {'CRM_N8N_EXECUTION_MONITOR': ''}),
            ('invalid', {'CRM_N8N_EXECUTION_MONITOR': 'unrecognized-profile'}),
            ('production', {'APP_ENV': 'production', 'CRM_N8N_EXECUTION_MONITOR': 'synthetic-private-v1'}),
        ):
            PHASE = 'profile-' + name
            descriptor = os.open(override, os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600)
            with os.fdopen(descriptor, 'w') as stream:
                json.dump({'services': {'app': {'environment': environment}}}, stream)
            restart(override)
            reject(client, '/painel?section=n8n', 404)
            if name == 'production':
                reject(client, '/painel', 404)
            else:
                content = client.get('/painel')
                check('data-execution-monitor=' not in content, 'disabled profile changed base workspace')
    finally:
        restart()
        override.unlink(missing_ok=True)
    list_page(client)
    unchanged(before)


def main():
    global PHASE
    runtime_separation()
    PHASE = 'real-password-and-mfa'
    anonymous = HTTP.Client()
    reject(anonymous, '/painel?section=n8n', 303)
    password_only = HTTP.Client()
    password_only.get('/login')
    _, location = password_only.post('/login', {'login': FIXTURES['clientA']['login'],
                                                'password': FIXTURES['clientA']['password']})
    check(location.endswith('/mfa'), 'password did not require MFA')
    reject(password_only, '/painel?section=n8n', 303)
    clients = {}
    for actor in ('clientA', 'clientB', 'member', 'clientEmpty', 'admin'):
        client = HTTP.Client()
        client.login(dict(FIXTURES[actor], admin=actor == 'admin'))
        clients[actor] = client
    a, b = clients['clientA'], clients['clientB']
    PHASE = 'readonly-list-detail-and-languages'
    before = snapshot()
    for language in ('pt', 'en', 'es'):
        parsed, html = list_page(a, language=language)
        check(EVENTS['clientB'][0]['event_id'] not in html
              and EVENTS['clientB'][0]['lead_title'] not in html, 'foreign event exposed')
        for index in (55, 54, 53, 52, 51, 50, 49, 0):
            detail_page(a, EVENTS['clientA'][index], language)
        check(len(parsed.rows) == 50 and EVENTS['clientA'][0]['event_id'] not in parsed.rows,
              'list window must exclude older own event that remains selectable')
    list_page(clients['member'])
    list_page(b, 'clientB')
    list_page(clients['clientEmpty'], 'clientEmpty')
    reject(clients['admin'], '/painel?section=n8n', 403)
    reject(a, '/painel?section=n8n&event=' + EVENTS['clientB'][0]['event_id'], 404)
    reject(b, '/painel?section=n8n&event=' + EVENTS['clientA'][55]['event_id'], 404)
    reject(a, '/painel?section=n8n&event=' + str(uuid.uuid4()), 404)
    for suffix in ('&extra=1', '&action=execute', '&page=2', '&event=not-a-uuid', '&event=',
                   '&event[]=' + EVENTS['clientA'][55]['event_id'],
                   '&event=' + EVENTS['clientA'][55]['event_id'].upper(), '&lang=fr', '&lang[]=pt'):
        reject(a, '/painel?section=n8n' + suffix, 422)
    for suffix in ('&tenant_id=' + FIXTURES['clientB']['organization'], '&role=admin'):
        reject(a, '/painel?section=n8n' + suffix, 400)
    for header in ('X-Tenant-Id', 'X-Identity-Id', 'X-Role', 'X-Aia-Claims', 'X-Aia-Signature'):
        html, _ = a.request('/painel?section=n8n', expected=400, headers={header: 'SYNTHETIC_REJECT'})
        safe_page(html)
        check('data-execution-monitor=' not in html, 'spoofed identity header admitted')
    method_denied(a, 'POST'); method_denied(a, 'PUT')
    fixture('readonly-context')
    unchanged(before)

    PHASE = 'historical-read-after-read-grant-revocation'
    fixture('revoke-read-grant')
    before = snapshot()
    detail_page(a, EVENTS['clientA'][55])
    list_page(a); fixture('readonly-context')
    unchanged(before)

    PHASE = 'source-and-projection-drift-denied'
    before = snapshot()
    for change, restore in (('corrupt-source', 'restore-source'), ('corrupt-pointer', 'restore-pointer')):
        with temporary_fixture(change, restore):
            corrupt = snapshot()
            reject(a, '/painel?section=n8n', 503)
            reject(a, '/painel?section=n8n&event=' + EVENTS['clientA'][55]['event_id'], 503)
            list_page(b, 'clientB')
            unchanged(corrupt)
        detail_page(a, EVENTS['clientA'][55])
        unchanged(before)

    PHASE = 'account-membership-and-session-revocation'
    for change, restore in (('suspend-account', 'restore-account'), ('revoke-membership', 'restore-membership')):
        with temporary_fixture(change, restore):
            denied = snapshot()
            reject(b, '/painel?section=n8n', 403)
            list_page(a)
            unchanged(denied)
        list_page(b, 'clientB')
    fixture('expire-session')
    before = snapshot()
    reject(b, '/painel?section=n8n', 303)
    unchanged(before)
    profiles(a)
    PHASE = 'complete'
    print(json.dumps({'schema': 'CRM_N8N_MONITOR_HTTP_EVIDENCE_V1', 'checks': CHECKS + HTTP.CHECKS,
        'synthetic_presentation_fixtures': True, 'new_n8n_executions': 0,
        'event_window': 50, 'own_events': 56, 'nominal_history': 6,
        'history_window_structural_maximum': 20, 'languages': ['pt', 'en', 'es'],
        'real_password_mfa': True, 'readonly_business_and_s2s': True,
        's2s_revocation_preserves_history': True, 'production_route_absent': True}, sort_keys=True))
    print('PRIVATE_CRM_EXECUTION_MONITOR_HTTP_PASS NO_GO')


if __name__ == '__main__':
    try:
        VAULT, ENVFILE = guard()
        FIXTURES = json.loads(VAULT.read_text())
        check(FIXTURES['monitor']['synthetic_presentation_only'] is True
              and FIXTURES['monitor']['n8n_executions_in_this_fixture'] == 0,
              'presentation fixture provenance required')
        EVENTS = FIXTURES['monitor']['events']
        spec = importlib.util.spec_from_file_location('private_crm_http', ROOT / 'scripts/ops/test-private-crm-http.py')
        HTTP = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(HTTP)
        mode = sys.argv[2] if len(sys.argv) == 3 else 'http'
        if mode == 'before-browser':
            marker = VAULT.parent / 'execution-monitor-browser-snapshot.json'
            descriptor = os.open(marker, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
            with os.fdopen(descriptor, 'w') as stream:
                json.dump(snapshot(), stream, sort_keys=True)
            print('PRIVATE_CRM_EXECUTION_MONITOR_BROWSER_SNAPSHOT_CREATED')
        elif mode == 'after-browser':
            marker = VAULT.parent / 'execution-monitor-browser-snapshot.json'
            check(marker.is_file() and not marker.stat().st_mode & 0o077, 'protected browser snapshot absent')
            unchanged(json.loads(marker.read_text()))
            marker.unlink()
            print('PRIVATE_CRM_EXECUTION_MONITOR_BROWSER_READONLY_PASS')
        else:
            check(mode == 'http', 'unknown monitor test mode')
            main()
    except Exception as error:
        print('Private execution monitor failed at ' + PHASE + ': ' + type(error).__name__
              + '; no credential, SQL, receipt or page payload logged.', file=sys.stderr)
        # Assertion labels are locally defined; subprocess/PDO/request data are never echoed.
        if isinstance(error, AssertionError):
            print(str(error), file=sys.stderr)
        raise SystemExit(1)
