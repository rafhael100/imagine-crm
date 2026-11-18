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
from service_targets import ServiceTargets, validate_compose

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

    def test_random_folder_suffixes_keep_valid_images_and_the_same_project_ownership(self):
        tree=ast.parse(render(CRM))
        def assignment(name):
            nodes=[node for node in ast.walk(tree) if isinstance(node,ast.Assign)
                and len(node.targets)==1 and isinstance(node.targets[0],ast.Name)
                and node.targets[0].id==name]
            self.assertEqual(len(nodes),1)
            return nodes[0]
        guards=[node for node in ast.walk(tree) if isinstance(node,ast.Expr)
            and isinstance(node.value,ast.Call) and isinstance(node.value.func,ast.Name)
            and node.value.func.id=='require_local_docker']
        cleanups=[node.finalbody for node in ast.walk(tree) if isinstance(node,ast.Try) and node.finalbody]
        self.assertEqual(len(guards),1)
        self.assertEqual(len(cleanups),1)
        # Execute the generated derivation/wiring/finally, never its service operations.
        probe=compile(ast.fix_missing_locations(ast.Module(body=[assignment('project'),guards[0],
            assignment('dc'),assignment('targets'),*cleanups[0]],type_ignores=[])),
            '<generated-p20-project-probe>','exec')
        # Docker distribution/reference path-component grammar, not the hash implementation.
        image_name=r'\A[a-z0-9]+(?:(?:[._]|__|-+)[a-z0-9]+)*\Z'
        self.assertNotRegex('p20-central-auto-p19-njrmy51_-crm-app',image_name)
        self.assertNotRegex('p20-central-auto-p19-_yrzfs24-portal',image_name)
        projects=[]
        folders=['/synthetic/runner-a/central-auto-p19-njrmy51_',
            '/synthetic/runner-b/central-auto-p19-njrmy51_',
            '/synthetic/runner-a/central-auto-p19-_yrzfs24',
            '/synthetic/runner-a/central-auto-p19-a___b',
            '/synthetic/runner-a/central-auto-p19-normal123']
        for directory in folders:
            with self.subTest(directory=directory):
                folder=Path(directory);guarded=[];commands=[]
                def record(args,**kwargs):
                    commands.append((args,kwargs))
                scope={'folder':folder,'file':folder/'compose.json','certs':folder/'certs',
                    'hashlib':hashlib,'run':record,'ServiceTargets':ServiceTargets,
                    'require_local_docker':lambda run,project:guarded.append(project)}
                exec(probe,scope)
                project=scope['project'];projects.append(project)
                self.assertTrue(project.startswith('p20-'))
                for service in ('crm-app','portal','agents-app'):
                    self.assertRegex(project+'-'+service,image_name)
                self.assertEqual(guarded,[project])
                self.assertEqual(scope['dc'],['docker','compose','-p',project,'-f',str(folder/'compose.json')])
                self.assertEqual(scope['targets'].project,project)
                self.assertEqual(scope['targets'].dc,scope['dc'])
                self.assertEqual(commands,[(scope['dc']+['down','-v','--remove-orphans'],{'ok':False,'timeout':90})])
        # Equal basenames in distinct runner folders must not share resource ownership.
        self.assertEqual(len(set(projects)),len(folders))

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
