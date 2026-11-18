#!/usr/bin/env python3
"""F251: no deploy, no network, no VPS. Check the upgrade policy fails closed."""
import importlib.util
from pathlib import Path
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


if __name__=='__main__':
    unittest.main(verbosity=2)
