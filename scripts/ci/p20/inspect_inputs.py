#!/usr/bin/env python3
"""Read-only preparation inventory. Does not start services, export P18 or migrate."""
import argparse
import hashlib
import json
import subprocess
from pathlib import Path

PINS = {
    'crm': 'cca5055608acff8c767b888b6322df77fe966558',
    'portal': 'c87f39c8c761c9a9c64d13d14bf45de889061a32',
    'agents': '94dcfeabd24f180113d2a378afe6232c67053664',
}
FILES = {
    'crm': ['.github/workflows/central-auto-p19-s2s.yml', 'scripts/ci/s2s/run_p19.py',
            'scripts/ci/s2s/send-event.php', 'infra/homologation/schema.sql',
            'infra/homologation/s2s/install-candidate.sql', 'infra/homologation/s2s/outbound-candidate.sql', 'composer.lock'],
    'portal': ['apps/portal/composer.lock', 'apps/portal/tools/p20/prepare.php', 'apps/portal/tools/p20/DisposablePreparation.php', 'apps/portal/tests/Fixtures/p20/authority-role.php',
               'apps/portal/tests/Feature/AutomationDatabaseAuthorityTest.php',
               'apps/portal/app/Domain/AutomationAdministration/DatabaseAutomationAuthority.php'],
    'agents': ['runtime/central-auto/export-integration-tree.py', 'runtime/central-auto/index.php',
               'runtime/central-auto/receiver-grants.sql',
               'tools/auto_ia/central_transport/rehearse_identity_merge_tree.py',
               'tools/auto_ia/central_transport/check_crosslane_merge_tree.py'],
}

def inventory(roots):
    result = {'status': 'PREPARED_INPUTS_ONLY_NOT_EXECUTED', 'products': {},
              'historical_p19_run': 43927603208,
              'historical_p18_tree_not_reexecuted': '5d700f82c0a92f8b9252e9d9465010123f758954',
              'n8n_dispatch': 'DISABLED', 'operational_authorization': False}
    for name, root in roots.items():
        head = subprocess.check_output(['git', '-C', str(root), 'rev-parse', 'HEAD'], text=True).strip()
        if name == 'crm':
            subprocess.run(['git', '-C', str(root), 'merge-base', '--is-ancestor', PINS[name], head], check=True)
        elif head != PINS[name]:
            raise RuntimeError('PIN_MISMATCH_'+name)
        hashes = {}
        for filename in FILES[name]:
            actual = (root/filename).read_bytes()
            pinned = subprocess.check_output(['git', '-C', str(root), 'show', PINS[name]+':'+filename])
            if actual != pinned:
                raise RuntimeError('INPUT_MODIFIED_'+name+'_'+filename)
            hashes[filename] = hashlib.sha256(actual).hexdigest()
        result['products'][name] = {'commit': PINS[name], 'sha256': hashes}
    return result

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    for name in PINS:
        parser.add_argument('--'+name, type=Path, required=True)
    args = parser.parse_args()
    print(json.dumps(inventory(vars(args)), indent=2, sort_keys=True))
