#!/usr/bin/env python3
"""Extend the pinned P19 laboratory with message and durable-receipt regressions."""
import hashlib
import json
import os
from pathlib import Path
import sys

P19_SHA256 = 'beae2aeb731a791c2e36cf9e28b884dcbb8e0c19bc8cd730fff5abf9dac6f9ff'


def render(root):
    source = (root / 'scripts/ci/s2s/run_p19.py').read_text()
    if hashlib.sha256(source.encode()).hexdigest() != P19_SHA256:
        raise RuntimeError('P21_P19_HARNESS_CHANGED')

    def replace(old, new):
        nonlocal source
        if source.count(old) != 1:
            raise RuntimeError('P21_P19_ANCHOR_CHANGED')
        source = source.replace(old, new)

    replace(
        'ROOT=Path(__file__).resolve().parents[3]',
        "ROOT=Path(__file__).resolve().parents[3]\n"
        "sys.path.insert(0,str(ROOT/'scripts/ci/s2s'))\n"
        'from outbox_regression import run_outbox_regressions\n'
        'from json_boundary_regression import run_json_boundary_regressions',
    )
    replace(
        " file=folder/'compose.json';file.write_text(json.dumps(compose))",
        " compose['services']['portal']['volumes'].append("
        "str(ROOT/'scripts/ci/s2s/probe-json.php')+':/probe-json.php:ro')\n"
        " file=folder/'compose.json';file.write_text(json.dumps(compose))",
    )
    for marker, call in (
        ('P19_REAL_PORTAL_LARAVEL_TO_CRM_LARAVEL_STATE_PASS synthetic_business_data=1',
         'run_json_boundary_regressions(locals())'),
        ('P19_EVENT_CONCURRENCY_DESTINATION_SOURCE_REVOCATION_CROSS_TENANT_PASS',
         'run_outbox_regressions(locals())'),
    ):
        old = "  print('" + marker + "')"
        replace(old, old + '\n  ' + call)
    compile(source, str(root / 'scripts/ci/s2s/run_p19.py'), 'exec')
    return source


def main():
    if (os.environ.get('GITHUB_ACTIONS') != 'true'
            or os.environ.get('GITHUB_REPOSITORY') != 'rafhael100/imagine-crm'
            or os.environ.get('GITHUB_EVENT_NAME') != 'pull_request'
            or os.environ.get('CRM_P21_REPOSITORY_PUBLIC') != 'true'):
        raise SystemExit('P21_DENIED_OUTSIDE_PUBLIC_CRM_PR_CI')
    if len(sys.argv) != 3:
        raise SystemExit('P21_PINNED_PRODUCT_PATHS_REQUIRED')
    root = Path(__file__).resolve().parents[3]
    source = render(root)
    paths = sorted((root / 'app/Homologation/S2S').glob('*.php'))
    paths += sorted((root / 'scripts/ci/s2s').glob('*.py'))
    paths += sorted((root / 'scripts/ci/s2s').glob('*.php'))
    paths += [root / 'infra/homologation/s2s/outbound-candidate.sql']
    print(json.dumps({
        'schema': 'CRM_P21_INPUTS_V1',
        'operational_authorization': False,
        'n8n_dispatch': 'DISABLED',
        'sha256': {str(path.relative_to(root)): hashlib.sha256(path.read_bytes()).hexdigest()
                   for path in paths},
    }, sort_keys=True), flush=True)
    # Preserve the immutable harness path used to resolve product build context.
    original = root / 'scripts/ci/s2s/run_p19.py'
    exec(compile(source, str(original), 'exec'),
         {'__name__': '__main__', '__file__': str(original)})


if __name__ == '__main__':
    main()
