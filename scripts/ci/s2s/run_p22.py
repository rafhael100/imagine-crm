#!/usr/bin/env python3
"""Compose P27 receipt reconciliation over the checksum-locked P19/P21 lab."""

import hashlib
import json
import os
from pathlib import Path
import sys

from run_p21 import render as render_p21

AGENTS_SHA = '8041c022776bbc1f998224a880726a8a3e69db37'
AGENTS_FILES = {
    'runtime/central-auto/event-status.php': '2329401204ea5170ddd2904d7f07f7889548dd8df070ce9fb9056e28b46308cf',
    'runtime/central-auto/receipt-status.sql': '28f82aa2078e273b8dd66653e5f8cbca3a99f9199c9781bd11d32ba5533a9ed7',
    'runtime/central-auto/nginx.conf': '1c0e63fe85b8f7b116675ca8e22202d73ec20e2d5372d7be7aa24ee21fee85fd',
    'runtime/central-auto/Dockerfile': '050b1804ba8291b3a3511282782ac1e0138ec80d6b8fee68a6b89f7093d732d6',
    'runtime/central-auto/index.php': 'ce41d2e071b4d17d370a92af55dc5ed7e55ba90d8d85a6192f257f7a5a601390',
}


def verify_agents(agents):
    for relative, expected in AGENTS_FILES.items():
        path = agents / relative
        if not path.is_file() or hashlib.sha256(path.read_bytes()).hexdigest() != expected:
            raise RuntimeError('P22_PINNED_AGENTS_RECEIPT_INPUT_CHANGED')


def render(root):
    source = render_p21(root)

    def replace(old, new):
        nonlocal source
        if source.count(old) != 1:
            raise RuntimeError('P22_P21_ANCHOR_CHANGED')
        source = source.replace(old, new)

    replace(
        'from json_boundary_regression import run_json_boundary_regressions',
        'from json_boundary_regression import run_json_boundary_regressions\n'
        'from receipt_reconciliation_regression import run_receipt_reconciliation_regressions',
    )
    replace(
        "'agents-owner.password','agents-signing.key']:",
        "'agents-owner.password','agents-signing.key','agents-receipt-db.password','agents-receipt.key']:",
    )
    replace(
        "crmcert=bundle('crm-app-secrets',['ca.crt','signing.key','db.password','sender.password','agents-signing.key','crm.crt','crm.key'])",
        "crmcert=bundle('crm-app-secrets',['ca.crt','signing.key','db.password','sender.password','agents-signing.key','agents-receipt.key','crm.crt','crm.key'])",
    )
    replace(
        "agentscert=bundle('agents-app-secrets',['ca.crt','agents-db.password','agents-signing.key'])",
        "agentscert=bundle('agents-app-secrets',['ca.crt','agents-db.password','agents-signing.key','agents-receipt-db.password','agents-receipt.key'])",
    )
    replace(
        " file=folder/'compose.json';file.write_text(json.dumps(compose))",
        " compose['services']['crm-app']['environment']['CRM_AGENTS_RECEIPT_PROFILE']='synthetic-private-v1'\n"
        " compose['services']['crm-app']['volumes'].extend([str(ROOT/'scripts/ci/s2s'/name)+':/'+name+':ro' for name in ['reconcile-event.php','probe-receipt.php']])\n"
        " compose['services']['agents-app']['environment'].update({'AGENTS_PRIVATE_RECEIPT_DB_PASSWORD_FILE':'/certs/agents-receipt-db.password','AGENTS_PRIVATE_RECEIPT_KEY_FILE':'/certs/agents-receipt.key','AGENTS_PRIVATE_RECEIPT_KEY_ID':'SYNTHETIC_P27_RECEIPT_KEY'})\n"
        " file=folder/'compose.json';file.write_text(json.dumps(compose))",
    )
    receipt_install = (
        "  agents_sql((AGENTS/'runtime/central-auto/receipt-status.sql').read_text())\n"
        '  agents_sql("CREATE ROLE aia_private_receipt_login LOGIN NOINHERIT NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOREPLICATION PASSWORD \'"+'
        '(certs/\'agents-receipt-db.password\').read_text()+"\'; GRANT aia_private_receipt TO aia_private_receipt_login WITH INHERIT FALSE, SET TRUE;")'
    )
    anchor = "  agents_sql((AGENTS/'runtime/central-auto/receiver-grants.sql').read_text())"
    replace(anchor, anchor + '\n' + receipt_install)
    replace(
        '  run_outbox_regressions(locals())',
        '  run_outbox_regressions(locals())\n  run_receipt_reconciliation_regressions(locals())',
    )
    compile(source, str(root / 'scripts/ci/s2s/run_p19.py'), 'exec')
    return source


def main():
    if (os.environ.get('GITHUB_ACTIONS') != 'true'
            or os.environ.get('GITHUB_REPOSITORY') != 'rafhael100/imagine-crm'
            or os.environ.get('GITHUB_EVENT_NAME') != 'pull_request'
            or os.environ.get('CRM_P22_REPOSITORY_PUBLIC') != 'true'):
        raise SystemExit('P22_DENIED_OUTSIDE_PUBLIC_CRM_PR_CI')
    if len(sys.argv) != 3:
        raise SystemExit('P22_PINNED_PRODUCT_PATHS_REQUIRED')
    root = Path(__file__).resolve().parents[3]
    agents = Path(sys.argv[2]).resolve()
    verify_agents(agents)
    source = render(root)
    paths = sorted((root / 'app/Homologation/S2S').glob('*.php'))
    paths += sorted((root / 'scripts/ci/s2s').glob('*.py'))
    paths += sorted((root / 'scripts/ci/s2s').glob('*.php'))
    paths += [root / 'infra/homologation/s2s/outbound-candidate.sql']
    print(json.dumps({
        'schema': 'CRM_P22_INPUTS_V1',
        'operational_authorization': False,
        'n8n_dispatch': 'DISABLED',
        'agents_sha': AGENTS_SHA,
        'agents_sha256': AGENTS_FILES,
        'sha256': {str(path.relative_to(root)): hashlib.sha256(path.read_bytes()).hexdigest()
                   for path in paths},
    }, sort_keys=True), flush=True)
    original = root / 'scripts/ci/s2s/run_p19.py'
    exec(compile(source, str(original), 'exec'),
         {'__name__': '__main__', '__file__': str(original)})


if __name__ == '__main__':
    main()
