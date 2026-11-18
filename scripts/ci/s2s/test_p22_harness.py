import ast
import hashlib
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

from run_p22 import render, verify_agents

ROOT = Path(__file__).resolve().parents[3]


class P22HarnessTest(unittest.TestCase):
    def test_overlay_preserves_prior_regressions_and_restore(self):
        source = render(ROOT)
        ast.parse(source)
        calls = ('run_json_boundary_regressions(locals())',
                 'run_outbox_regressions(locals())',
                 'run_receipt_reconciliation_regressions(locals())')
        for call in calls:
            self.assertEqual(source.count(call), 1)
        self.assertLess(source.index(calls[1]), source.index(calls[2]))
        self.assertLess(source.index(calls[2]), source.index('# Restore actual event-state records'))
        self.assertIn("assert count=='3'", source)
        self.assertIn("['down','-v','--remove-orphans']", source)
        self.assertIn('aia_private_receipt_login LOGIN NOINHERIT NOSUPERUSER NOBYPASSRLS', source)
        self.assertIn('WITH INHERIT FALSE, SET TRUE', source)

    def test_unknown_overlay_fails_before_execution(self):
        with patch('run_p22.render_p21', return_value='raise RuntimeError("must not run")\n'):
            with self.assertRaisesRegex(RuntimeError, 'P22_P21_ANCHOR_CHANGED'):
                render(ROOT)

    def test_pinned_dependency_bytes_required(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            relative = 'runtime/central-auto/event-status.php'
            expected = b'<?php /* synthetic input fixture */\n'
            with patch('run_p22.AGENTS_FILES', {relative: hashlib.sha256(expected).hexdigest()}):
                with self.assertRaisesRegex(RuntimeError, 'P22_PINNED_AGENTS_RECEIPT_INPUT_CHANGED'):
                    verify_agents(root)
                path = root / relative
                path.parent.mkdir(parents=True)
                path.write_bytes(expected)
                verify_agents(root)
                path.write_bytes(expected + b'// altered\n')
                with self.assertRaisesRegex(RuntimeError, 'P22_PINNED_AGENTS_RECEIPT_INPUT_CHANGED'):
                    verify_agents(root)

    def test_missing_authority_stops_before_paths_or_docker(self):
        for env in ({}, {'GITHUB_ACTIONS': 'true',
                         'GITHUB_REPOSITORY': 'rafhael100/imagine-crm',
                         'GITHUB_EVENT_NAME': 'pull_request',
                         'CRM_P22_REPOSITORY_PUBLIC': 'false'}):
            result = subprocess.run([sys.executable, str(ROOT / 'scripts/ci/s2s/run_p22.py'),
                                     '/must/not/be/read', '/must/not/be/read'],
                                    env=env, capture_output=True, text=True)
            self.assertNotEqual(result.returncode, 0)
            self.assertIn('P22_DENIED_OUTSIDE_PUBLIC_CRM_PR_CI', result.stderr)


if __name__ == '__main__':
    unittest.main()
