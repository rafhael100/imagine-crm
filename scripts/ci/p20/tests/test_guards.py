import copy
import importlib.util
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))
from service_targets import validate_compose, validate_container, require_local_docker

class ProcessGuards(unittest.TestCase):
    def invoke(self, case):
        with tempfile.TemporaryDirectory() as folder:
            sentinel = Path(folder)/'write'
            p = subprocess.run([os.environ.get('P20_TEST_PHP','php'),str(ROOT/'tests/guard-process.php'),case,str(sentinel)],capture_output=True,text=True,timeout=5)
            self.assertNotIn('SYNTHETIC_SECRET_MUST_NOT_LEAK', p.stdout+p.stderr)
            return p, sentinel.exists(), Path(str(sentinel)+'.query').exists()

    def test_valid_profiles_reach_sentinel(self):
        for case in ('valid','agents-valid','runtime-valid'):
            with self.subTest(case=case):
                p, written, queried = self.invoke(case)
                self.assertEqual(p.returncode,0,p.stderr)
                self.assertTrue(written and queried)
                self.assertIn('P20_SERVICE_TARGET_V1',p.stderr)

    def test_configuration_and_dns_fail_before_query_and_write(self):
        for case in ('wrong-dsn','agents-wrong-dsn','wrong-profile','production','wrong-role','wrong-password-file','dns-empty','dns-multiple','dns-wrong','libpq-override'):
            with self.subTest(case=case):
                p,written,queried=self.invoke(case)
                self.assertNotEqual(p.returncode,0)
                self.assertFalse(written or queried)

    def test_sql_identity_failures_never_reach_write(self):
        for case in ('sql-database','agents-sql-database','sql-address','sql-port','sql-start','sql-oid','sql-system-id','sql-directory','sql-role','sql-no-tls','runtime-unsafe','sql-exception','sql-missing'):
            with self.subTest(case=case):
                p,written,queried=self.invoke(case)
                self.assertNotEqual(p.returncode,0)
                self.assertTrue(queried)
                self.assertFalse(written)

    def test_abnormal_process_exit_is_nonzero_without_write(self):
        for case in ('exit-zero','exception','fatal','missing-proof'):
            with self.subTest(case=case):
                p,written,queried=self.invoke(case)
                self.assertNotEqual(p.returncode,0)
                self.assertFalse(written or queried)

class TopologyGuards(unittest.TestCase):
    def setUp(self):
        self.compose={'networks':{'private':{'internal':True}},'services':{'crm-db':{'networks':['private']}},'volumes':{'crm_pg':{},'agents_pg':{}}}
        self.container={'Config':{'Labels':{'com.docker.compose.project':'test','com.docker.compose.service':'crm-db'}},
            'State':{'Running':True},'HostConfig':{'NetworkMode':'test_private'},
            'NetworkSettings':{'Networks':{'test_private':{'NetworkID':'network-id','IPAddress':'172.28.0.2'}},'Ports':{'5432/tcp':None}},
            'Mounts':[{'Destination':'/var/lib/postgresql/data','Type':'volume','Name':'test_crm_pg'}]}
        self.network={'Id':'network-id','Internal':True,'Labels':{'com.docker.compose.project':'test'}}
        self.volume={'Name':'test_crm_pg','Driver':'local','Options':None,'Labels':{'com.docker.compose.project':'test'}}

    def test_valid_private_topology(self):
        validate_compose(self.compose)
        self.assertEqual(validate_container(self.container,self.network,self.volume,'test','crm-db','test_crm_pg'),'172.28.0.2')

    def test_invalid_compose_never_reaches_sentinel(self):
        cases=[lambda c:c['networks']['private'].update(internal=False),
            lambda c:c['services']['crm-db'].update(ports=['5432:5432']),
            lambda c:c['services']['crm-db'].update(network_mode='host'),
            lambda c:c['services']['crm-db'].update(privileged=True),
            lambda c:c['services']['crm-db'].update(extra_hosts=['crm-db:192.0.2.1']),
            lambda c:c['volumes'].update(crm_pg={'external':True})]
        for change in cases:
            c=copy.deepcopy(self.compose);change(c);sentinel=[]
            with self.assertRaises(RuntimeError):
                validate_compose(c);sentinel.append('write')
            self.assertEqual(sentinel,[])

    def test_invalid_live_evidence_never_reaches_sentinel(self):
        cases=[lambda c,n,v:c['HostConfig'].update(PortBindings={'5432/tcp':[{'HostIp':'0.0.0.0'}]}),
            lambda c,n,v:c['HostConfig'].update(NetworkMode='host'),
            lambda c,n,v:c['State'].update(Running=False),
            lambda c,n,v:c['Mounts'][0].update(Type='bind'),
            lambda c,n,v:n.update(Internal=False),
            lambda c,n,v:v.update(Driver='nfs'),
            lambda c,n,v:v['Labels'].update({'com.docker.compose.project':'shared'}),
            lambda c,n,v:c['NetworkSettings']['Networks']['test_private'].update(NetworkID='other')]
        for change in cases:
            c,n,v=copy.deepcopy((self.container,self.network,self.volume));change(c,n,v);sentinel=[]
            with self.assertRaises(RuntimeError):
                validate_container(c,n,v,'test','crm-db','test_crm_pg');sentinel.append('write')
            self.assertEqual(sentinel,[])

if __name__ == '__main__': unittest.main()
