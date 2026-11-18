import ast
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

from run_p21 import render

ROOT = Path(__file__).resolve().parents[3]


class P21HarnessTest(unittest.TestCase):
    def test_pinned_overlay_preserves_original_checks_and_cleanup(self):
        source = render(ROOT)
        ast.parse(source)
        self.assertEqual(source.count('run_outbox_regressions(locals())'), 1)
        self.assertEqual(source.count('run_json_boundary_regressions(locals())'), 1)
        self.assertLess(source.index('run_outbox_regressions(locals())'),
                        source.index('# Restore actual event-state records'))
        self.assertIn("print('P19_EVENT_OUTBOX_INBOX_RECORDS_RESTORED_RLS_FORCE_PASS", source)
        self.assertIn("['down','-v','--remove-orphans']", source)
        self.assertIn("str(ROOT/'scripts/ci/s2s/probe-json.php')+':/probe-json.php:ro'", source)

    def test_unrecognized_harness_cannot_start(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            path = root / 'scripts/ci/s2s/run_p19.py'
            path.parent.mkdir(parents=True)
            path.write_text('raise RuntimeError("must not run")\n')
            with self.assertRaisesRegex(RuntimeError, 'P21_P19_HARNESS_CHANGED'):
                render(root)

    def test_missing_ci_authority_stops_before_product_or_docker_access(self):
        for env in ({}, {'GITHUB_ACTIONS': 'true',
                         'GITHUB_REPOSITORY': 'rafhael100/imagine-crm',
                         'GITHUB_EVENT_NAME': 'pull_request',
                         'CRM_P21_REPOSITORY_PUBLIC': 'false'}):
            result = subprocess.run([sys.executable, str(ROOT / 'scripts/ci/s2s/run_p21.py')],
                                    env=env, capture_output=True, text=True)
            self.assertNotEqual(result.returncode, 0)
            self.assertIn('P21_DENIED_OUTSIDE_PUBLIC_CRM_PR_CI', result.stderr)


if __name__ == '__main__':
    unittest.main()
