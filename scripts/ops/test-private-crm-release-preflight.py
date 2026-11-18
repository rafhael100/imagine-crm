#!/usr/bin/env python3
"""F251: no deploy, no network, no VPS. Check the upgrade policy fails closed."""
import importlib.util
import contextlib
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile
import unittest

path=Path(__file__).with_name('private-crm-release-preflight.py')
spec=importlib.util.spec_from_file_location('private_crm_release_preflight',path)
module=importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class PrivateReleasePreflightTests(unittest.TestCase):
    def test_exact_known_change_requires_human_operator_review(self):
        report=module.classify([
            ('M','app/Homologation/Controller.php'),
            ('A','app/Homologation/OpportunityScenario.php'),
            ('M','app/Homologation/PrivateHeaders.php'),
            ('A','app/Support/Synthetic/AgentsSignedIngressCandidate.php'),
            ('M','resources/views/crm-private-journey.blade.php'),
            ('M','scripts/ops/test-private-crm-http.py'),
        ])
        self.assertEqual('MANUAL_REVIEW_REQUIRED',report['status'])
        self.assertFalse(report['deploy_approved'])
        self.assertTrue(report['no_deploy_executed'])
        self.assertEqual(6,report['files_changed'])
        self.assertEqual(4,len(report['runtime_review_files']))

    def test_any_schema_route_auth_provider_or_infrastructure_change_blocks(self):
        base=[('M','app/Homologation/Controller.php')]
        for unexpected in (
            'database/migrations/2026_new_table.php',
            'routes/web.php',
            'bootstrap/app.php',
            'app/Providers/AppServiceProvider.php',
            'infra/homologation/compose.yaml',
            '.env',
            'composer.lock',
            'scripts/ops/publish-private-crm.py',
            'app/Homologation/ExperimentalServiceAuthorization.php',
            'app/Support/Synthetic/UnreviewedFixture.php',
        ):
            result=module.classify(base+[('M',unexpected)])
            self.assertEqual('BLOCKED',result['status'])
            self.assertIn(unexpected,result['blocked'])
            self.assertFalse(result['deploy_approved'])

    def test_deletions_renames_and_duplicates_are_never_silent(self):
        for changes in (
            [('M','app/Homologation/Controller.php'),
             ('D','resources/views/crm-private-journey.blade.php')],
            [('M','app/Homologation/Controller.php'),
             ('R100','resources/views/crm-marketing-home.blade.php')],
            [('M','app/Homologation/Controller.php'),
             ('M','app/Homologation/Controller.php')],
            [('M','app/Homologation/Controller.php'),('A',None)],
        ):
            self.assertEqual('BLOCKED',module.classify(changes)['status'])

    def test_docs_only_and_empty_diff_are_not_a_release(self):
        for changes in (
            [],
            [('M','docs/operations/private-complete-journey.md')],
            [('A','tests/Feature/AgentsPrivateIngressCandidateTest.php')],
        ):
            result=module.classify(changes)
            self.assertEqual('BLOCKED',result['status'])
            self.assertFalse(result['deploy_approved'])


class PrivateReleasePreflightCiDiagnosticsTests(unittest.TestCase):
    """Run each real CI classification command against disposable Git history."""

    workflows = (
        '.github/workflows/ci-private-crm-release-preflight.yml',
        '.github/workflows/ci-private-crm-upgrade-f252.yml',
    )
    marker = 'AFTER_CLASSIFICATION\n'

    @contextlib.contextmanager
    def candidate(self, blocked):
        with tempfile.TemporaryDirectory(prefix='f251-ci-diagnostics-') as folder:
            root = Path(folder) / 'repo'
            runner = Path(folder) / 'runner'
            root.mkdir()
            runner.mkdir()
            classifier = root / 'scripts/ops/private-crm-release-preflight.py'
            classifier.parent.mkdir(parents=True)
            classifier.write_bytes(path.read_bytes())
            runtime = root / 'app/Homologation/Controller.php'
            runtime.parent.mkdir(parents=True)
            runtime.write_text('synthetic baseline\n')
            env = dict(os.environ, GIT_CONFIG_GLOBAL=os.devnull,
                       GIT_CONFIG_NOSYSTEM='1')

            def git(*args):
                return subprocess.run(
                    ['git', '-c', 'core.hooksPath=/dev/null',
                     '-c', 'commit.gpgSign=false', '-c', 'user.name=F251 CI',
                     '-c', 'user.email=f251@example.invalid', *args],
                    cwd=root, env=env, check=True, capture_output=True,
                    text=True, timeout=10,
                ).stdout.strip()

            git('init', '--quiet')
            git('add', '.')
            git('commit', '--quiet', '-m', 'synthetic baseline')
            published = git('rev-parse', 'HEAD')
            runtime.write_text('synthetic candidate\n')
            changes = [('M', 'app/Homologation/Controller.php')]
            if blocked:
                route = root / 'routes/web.php'
                route.parent.mkdir()
                route.write_text('synthetic blocked path\n')
                changes.append(('A', 'routes/web.php'))
            git('add', '.')
            git('commit', '--quiet', '-m', 'synthetic candidate')
            yield root, runner, published, git('rev-parse', 'HEAD'), changes

    def invoke(self, workflow, fixture, legacy=False, invalid_args=False):
        root, runner, published, candidate, _ = fixture
        source = (path.resolve().parents[2] / workflow).read_text()
        invocation = re.search(
            r'(?m)^          python3 scripts/ops/private-crm-release-preflight\.py \\\n'
            r'            (?P<published>[a-f0-9]{40}) \\\n'
            r'            "\$CANDIDATE_SHA" (?P<output>>|\| tee) '
            r'"\$RUNNER_TEMP/(?P<report>[\w.-]+\.json)"$', source,
        )
        self.assertIsNotNone(invocation, workflow)
        run_block = source[:invocation.start()].rsplit('        run: |\n', 1)[1]
        options = run_block.splitlines()[0].strip()
        self.assertEqual('set -euo pipefail', options)
        # Only substitute the fixture's ancestor; execute the actual command,
        # output destination and shell options from each checked-in workflow.
        command = invocation.group(0).replace(invocation['published'], published, 1)
        if legacy:
            command = command.replace(' | tee "$RUNNER_TEMP/', ' > "$RUNNER_TEMP/', 1)
        result = subprocess.run(
            ['bash', '-c', options + '\n' + command
             + '\nprintf "%s\\n" AFTER_CLASSIFICATION\n'],
            cwd=root, env=dict(os.environ, RUNNER_TEMP=str(runner),
                               CANDIDATE_SHA='invalid' if invalid_args else candidate),
            capture_output=True, text=True, timeout=10,
        )
        return result, (runner / invocation['report']).read_text()

    def test_workflow_report_and_exit_are_preserved_without_advancing_a_refusal(self):
        for case, expected_rc in (('blocked', 1), ('manual', 0), ('usage', 2)):
            with self.candidate(case == 'blocked') as fixture:
                expected = module.classify(fixture[4])
                expected.update(published_sha=fixture[2], candidate_sha=fixture[3])
                for workflow in self.workflows:
                    with self.subTest(case=case, workflow=workflow):
                        before, old_report = self.invoke(
                            workflow, fixture, legacy=True, invalid_args=case == 'usage')
                        after, report = self.invoke(
                            workflow, fixture, invalid_args=case == 'usage')
                        self.assertEqual(expected_rc, before.returncode)
                        self.assertEqual(expected_rc, after.returncode)
                        self.assertEqual(before.stderr, after.stderr)
                        self.assertEqual(old_report, report)
                        continuation = self.marker if case == 'manual' else ''
                        self.assertEqual(continuation, before.stdout)
                        self.assertEqual(report + continuation, after.stdout)
                        if case == 'usage':
                            self.assertEqual('', report)
                            self.assertIn('Usage:', after.stderr)
                        else:
                            self.assertEqual(expected, json.loads(report))
                            self.assertFalse(expected['deploy_approved'])
                            self.assertTrue(expected['no_deploy_executed'])


if __name__=='__main__':
    unittest.main(verbosity=2)
