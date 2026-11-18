import ast
import contextlib
import io
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

ROOT=Path(__file__).resolve().parents[1]
CRM=ROOT.parents[2]
sys.path.insert(0,str(ROOT))
from prepare_harness import render
from p20_runtime import load_portal, check_suite
from service_targets import validate_compose

class Harness(unittest.TestCase):
    def test_overlay_compiles_and_guards_precede_application_sql(self):
        source=render(CRM)
        ast.parse(source)
        self.assertLess(source.index('targets.preflight()'),source.index("sql((ROOT/'infra/homologation/schema.sql')"))
        self.assertLess(source.index('portal.check(dc,run)'),source.index('portal.prepare(dc,run)'))
        self.assertLess(source.index('portal.prepare(dc,run)'),source.index("sql((ROOT/'infra/homologation/schema.sql')"))
        self.assertIn("return targets.sql('crm',query)",source)
        self.assertIn("return targets.sql('agents',query)",source)
        self.assertIn("args+=['portal','php','/p19-read-private.php',company,mode]",source)
        self.assertIn('targets.runtime()',source)
        self.assertIn('P20_EXPECTED_TWO_REAL_PRIVATE_READS',source)
        self.assertIn('P19_CRM_TO_AGENTS_SIGNED_ACK_LOST_ACK_IDEMPOTENCY_PASS',source)
        self.assertNotIn("['wrong-mac'",source)
        self.assertNotIn('pg_dump',source)
        self.assertNotIn("['exec','-T','crm-db','psql'",source)
        self.assertEqual(source.count('for _ in range(1):'),1)
        self.assertIn("['down','-v','--remove-orphans']",source)

    def test_incomplete_suite_is_not_success(self):
        check_suite({'tests':4,'failures':0,'skipped':0})
        for result in ({'tests':0,'failures':0,'skipped':0},{'tests':4,'failures':0,'skipped':1},None):
            with self.assertRaises(RuntimeError): check_suite(result)

    def test_missing_guard_stops_before_adapter_or_services(self):
        with tempfile.TemporaryDirectory() as directory:
            root=Path(directory); here=root/'scripts/ci/p20'; here.mkdir(parents=True)
            (here/'portal_guard_binding.json').write_text('{"status":"PENDING"}')
            with self.assertRaisesRegex(RuntimeError,'PORTAL_GUARD_UNBOUND'): load_portal(root)

    def test_changed_p19_source_is_refused(self):
        with tempfile.TemporaryDirectory() as directory:
            root=Path(directory); target=root/'scripts/ci/s2s'; target.mkdir(parents=True)
            (target/'run_p19.py').write_text('# synthetic modified input')
            with self.assertRaisesRegex(RuntimeError,'P19_HARNESS_CHANGED'): render(root)

    def test_pinned_portal_binding_and_isolated_configuration(self):
        portal=Path(os.environ.get('P20_PORTAL_CHECKOUT',str(CRM/'pinned-portal')))
        self.assertTrue(portal.is_dir(),'Set P20_PORTAL_CHECKOUT to the pinned Portal checkout')
        with tempfile.TemporaryDirectory() as directory:
            root=Path(directory); here=root/'scripts/ci';here.mkdir(parents=True)
            (here/'p20').symlink_to(ROOT,target_is_directory=True)
            (root/'pinned-portal').symlink_to(portal.resolve(),target_is_directory=True)
            adapter=load_portal(root)
            compose={'networks':{'private':{'internal':True}},'volumes':{'crm_pg':{},'agents_pg':{}},
                'services':{'portal':{'environment':{},'volumes':[],'networks':['private']}}}
            with contextlib.redirect_stdout(io.StringIO()):
                adapter.configure(compose,root,root/'pinned-portal')
            validate_compose(compose)
            service=compose['services']['portal']
            self.assertEqual(service['environment']['DB_HOST'],'127.0.0.1')
            self.assertEqual(service['environment']['DB_PORT'],'55432')
            self.assertNotIn('ports',service)
            self.assertTrue((root/'portal-build/tests/Feature/PortalPrivateAuthorityTest.php').is_file())
            self.assertEqual((root/'portal-build/tools/p20/prepare.php').read_bytes(),(portal/'apps/portal/tools/p20/prepare.php').read_bytes())
            self.assertEqual((root/'portal-build/tools/p20/DisposablePreparation.php').read_bytes(),(portal/'apps/portal/tools/p20/DisposablePreparation.php').read_bytes())

if __name__=='__main__': unittest.main()
