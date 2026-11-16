"""Invoke Portal #914 unchanged inside its own disposable PHP+PostgreSQL process namespace."""
import json
import os
from pathlib import Path
import secrets
import shutil
import xml.etree.ElementTree as ET
from service_targets import require

class Adapter:
    def __init__(self, portal_root):
        self.root=portal_root
        self.here=Path(__file__).resolve().parent

    def configure(self, compose, folder, portal_root):
        require(portal_root.resolve() == self.root.resolve(),'PORTAL_ROOT')
        context=folder/'portal-build'
        shutil.copytree(portal_root/'apps/portal',context,ignore=shutil.ignore_patterns('vendor','node_modules','.env','.env.*','.git'))
        # Isolated build context only: retain tests/tooling excluded by the product's deployment .dockerignore.
        (context/'.dockerignore').write_text('vendor/\nnode_modules/\n.env\n.env.*\n')
        shutil.copyfile(self.here/'Portal.Dockerfile',context/'Dockerfile')
        shutil.copyfile(self.here/'PortalPrivateAuthorityTest.php',context/'tests/Feature/PortalPrivateAuthorityTest.php')
        service=compose['services']['portal']
        service['build']={'context':str(context),'dockerfile':'Dockerfile'}
        service['entrypoint']=['bash','/p20-code/portal-start.sh']
        service['volumes'].append(str(self.here)+':/p20-code:ro')
        password=secrets.token_hex(32)
        print('::add-mask::'+password)
        service['environment'].update({'APP_ENV':'testing','CI':'true','P20_AUTHORITY_PG_CI':'1',
            'P20_INTEGRATED_PRIVATE_CI':'1','PORTAL_ADMIN_AUTH_ENABLED':'true',
            'IMAGINE_AUTOMATION_ADMIN_ENABLED':'true','IMAGINE_AUTOMATION_ADMIN_MODE':'isolated_ci',
            'DB_CONNECTION':'pgsql','DB_HOST':'127.0.0.1','DB_PORT':'55432','DB_DATABASE':'portal_automation_p20_ci',
            'DB_USERNAME':'postgres','DB_PASSWORD':password,'DB_SSLMODE':'disable',
            'AUTOMATION_AUTHORITY_DB_USERNAME':'portal_automation_runtime',
            'AUTOMATION_AUTHORITY_DB_PASSWORD':'SYNTHETIC_P20_CI_ONLY','AUTOMATION_AUTHORITY_SSLMODE':'disable',
            'QUEUE_CONNECTION':'sync','MAIL_MAILER':'array'})
        service['mem_limit']='1024m'
        # PostgreSQL and PHP share /tmp, UID, PID visibility and loopback, not another application's DB.
        service['tmpfs']=['/tmp:uid=33,gid=33,mode=0700','/var/www/html/storage:uid=33,gid=33,mode=0770','/var/www/html/bootstrap/cache:uid=33,gid=33,mode=0770']
        service['healthcheck']={'test':['CMD','/usr/lib/postgresql/18/bin/pg_isready','-h','127.0.0.1','-p','55432','-U','postgres','-d','portal_automation_p20_ci'],'interval':'2s','timeout':'2s','retries':30}

    def guard(self, dc, run, mode):
        p=run(dc+['exec','-T','portal','php','tools/p20/prepare.php',mode,'/tmp/p20-cluster','/usr/lib/postgresql/18/bin/pg_controldata','55432'])
        results=[json.loads(line) for line in p.stdout.splitlines() if line.startswith('{')]
        require(len(results)==2 and results[-1].get('schema')=='P20_PREPARATION_RESULT_V1' and results[-1].get('completed') is True,'PORTAL_PROOF')
        target=results[-1]['target']
        require(target==results[0] and target['database']=='portal_automation_p20_ci' and target['address']=='127.0.0.1','PORTAL_TARGET')
        print(json.dumps(results[-1],sort_keys=True))

    def check(self, dc, run):
        run(dc+['up','-d','--wait','portal'],timeout=90)
        self.guard(dc,run,'--check')

    def prepare(self, dc, run):
        self.guard(dc,run,'--migrate')
        # Exact fixture from #914; fixed local connection remains verified. No application migration is changed.
        run(dc+['exec','-T','portal','php','/p20-code/portal-role.php'])

    def run_suite(self, dc, run):
        # Custom test config prevents product phpunit.xml from forcing its nonconnected DB fixture.
        run(dc+['exec','-T','portal','php','vendor/bin/phpunit','--configuration','/p20-code/phpunit-private.xml',
                '--log-junit','/tmp/p20-private-junit.xml','--no-progress'])
        xml=run(dc+['exec','-T','portal','cat','/tmp/p20-private-junit.xml']).stdout
        cases=ET.fromstring(xml).findall('.//testcase')
        return {'tests':len(cases),'failures':sum(len(c.findall('failure'))+len(c.findall('error')) for c in cases),
                'skipped':sum(len(c.findall('skipped')) for c in cases)}
