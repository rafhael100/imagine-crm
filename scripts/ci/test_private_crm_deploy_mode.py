#!/usr/bin/env python3
"""Invalid operator arguments must not reach the protected deployment target."""
import contextlib
import io
from pathlib import Path
import runpy
import subprocess
import sys
import unittest
from unittest.mock import patch


SCRIPT = Path(__file__).resolve().parents[1] / 'ops' / 'deploy-private-crm.py'
EXPECTED = 'Explicit prepare, verify or access required; no change performed.'
AUTHORIZED_SCRIPT = Path('/srv/imagine/crm-private-candidate/scripts/ops/deploy-private-crm.py')


def invoke(arguments, *, uid=0, script_path=AUTHORIZED_SCRIPT):
    """Model an otherwise admissible operator, but intercept every target effect."""
    output = io.StringIO()
    with contextlib.ExitStack() as stack:
        stack.enter_context(patch.object(sys, 'argv', [str(SCRIPT), *arguments]))
        calls = {
            'uid': stack.enter_context(patch('os.geteuid', return_value=uid)),
            'resolve': stack.enter_context(patch.object(Path, 'resolve', return_value=script_path)),
            'exists': stack.enter_context(patch.object(Path, 'exists', return_value=False)),
            'umask': stack.enter_context(patch('os.umask')),
            'mkdir': stack.enter_context(patch.object(Path, 'mkdir')),
            'chmod': stack.enter_context(patch.object(Path, 'chmod')),
            'write': stack.enter_context(patch.object(Path, 'write_text')),
            'replace': stack.enter_context(patch.object(Path, 'replace')),
            'read': stack.enter_context(patch.object(Path, 'read_text')),
            'stat': stack.enter_context(patch.object(Path, 'stat')),
            'run': stack.enter_context(patch('subprocess.run', return_value=subprocess.CompletedProcess([], 0))),
            'capture': stack.enter_context(patch('subprocess.check_output', return_value='a' * 40 + '\n')),
        }
        stack.enter_context(contextlib.redirect_stdout(output))
        stack.enter_context(contextlib.redirect_stderr(output))
        try:
            runpy.run_path(str(SCRIPT), run_name='__main__')
        except SystemExit as exc:
            message = str(exc)
        else:
            raise AssertionError('Operator did not terminate at admission.')
    return message, output.getvalue(), {name: mock.call_count for name, mock in calls.items()}


class DeployModeAdmissionTest(unittest.TestCase):
    def test_invalid_arguments_do_not_probe_or_change_an_otherwise_authorized_target(self):
        cases = [[], ['--help'], ['invalid'], ['PREPARE'], ['prepare', 'extra'],
                 ['verify', 'extra'], ['access', 'extra'], ['prepare', '--help']]
        for arguments in cases:
            with self.subTest(arguments=arguments):
                message, output, calls = invoke(arguments)
                self.assertEqual(EXPECTED, message)
                self.assertEqual('', output)
                self.assertTrue(all(count == 0 for count in calls.values()), calls)

    def test_error_does_not_echo_untrusted_argument(self):
        message, output, calls = invoke(['secret-sentinel\n$(do-not-execute)'])
        self.assertEqual(EXPECTED, message)
        self.assertNotIn('secret-sentinel', message + output)
        self.assertTrue(all(count == 0 for count in calls.values()), calls)

    def test_all_valid_modes_still_require_the_authorized_operator(self):
        for mode in ('prepare', 'verify', 'access'):
            with self.subTest(mode=mode):
                message, output, calls = invoke([mode], uid=1000)
                self.assertEqual('Run only as operator on the dedicated Leaseweb private CRM checkout.', message)
                self.assertEqual('', output)
                self.assertEqual(1, calls.pop('uid'))
                self.assertEqual(1, calls.pop('resolve'))
                self.assertTrue(all(count == 0 for count in calls.values()), calls)

    def test_root_on_another_checkout_remains_denied(self):
        for mode in ('prepare', 'verify', 'access'):
            with self.subTest(mode=mode):
                message, output, calls = invoke([mode], script_path=Path('/tmp/other/scripts/ops/deploy-private-crm.py'))
                self.assertEqual('Run only as operator on the dedicated Leaseweb private CRM checkout.', message)
                self.assertEqual('', output)
                self.assertEqual(1, calls.pop('uid'))
                self.assertEqual(1, calls.pop('resolve'))
                self.assertTrue(all(count == 0 for count in calls.values()), calls)


if __name__ == '__main__':
    unittest.main(verbosity=2)
