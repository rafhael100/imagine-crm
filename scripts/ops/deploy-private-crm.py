#!/usr/bin/env python3
"""Leaseweb private CRM only. Does not change ingress or any other Compose project."""
import json
import os
from pathlib import Path
import re
import subprocess
import sys

ROOT=Path(__file__).resolve().parents[2]
PROTECTED=Path('/srv/imagine/private-crm-journey')
ENVFILE=PROTECTED/'runtime.env'
DATA=PROTECTED/'data'
PROJECT='imagine-crm-private-journey'
if os.geteuid()!=0 or ROOT!=Path('/srv/imagine/crm-private-candidate'):
    raise SystemExit('Run only as operator on the dedicated Leaseweb private CRM checkout.')
if (DATA/'publication-opened.json').exists():
    raise SystemExit('Private ingress has opened. Fixture preparation requires a separately reviewed maintenance release.')

def run(command):
    subprocess.run(command,cwd=ROOT,check=True,stdin=subprocess.DEVNULL)

sha=subprocess.check_output(['git','rev-parse','HEAD'],cwd=ROOT,text=True).strip()
if not re.fullmatch('[a-f0-9]{40}',sha): raise SystemExit('Invalid source revision.')
if subprocess.run(['git','diff','--quiet','HEAD'],cwd=ROOT).returncode:
    raise SystemExit('Private checkout has uncommitted tracked changes.')
os.umask(0o077)
PROTECTED.mkdir(mode=0o700,parents=True,exist_ok=True)
PROTECTED.chmod(0o700)
if not ENVFILE.exists():
    run(['python3','scripts/ops/create-private-crm-environment.py',str(ENVFILE),str(DATA),sha])
else:
    if ENVFILE.stat().st_mode & 0o077: raise SystemExit('Runtime environment permissions unsafe.')
    text=ENVFILE.read_text()
    text,count=re.subn(r"^CRM_PRIVATE_IMAGE_TAG='[a-f0-9]{40}'$","CRM_PRIVATE_IMAGE_TAG='"+sha+"'",text,flags=re.M)
    if count!=1: raise SystemExit('Unknown image tag configuration; keys were not changed.')
    temporary=PROTECTED/'runtime.env.next'
    temporary.write_text(text);temporary.chmod(0o600);temporary.replace(ENVFILE)
compose=['docker','compose','--env-file',str(ENVFILE),'-p',PROJECT,'-f','infra/homologation/compose.yaml']
os.environ['CRM_PRIVATE_ENV_FILE']=str(ENVFILE)
os.environ['CRM_PRIVATE_PROJECT']=PROJECT
mode=sys.argv[1] if len(sys.argv)>1 else ''
if mode=='prepare':
    run(compose+['config','--quiet'])
    run(compose+['build','app'])
    run(compose+['up','-d','--no-build'])
    # Git replaces the bind-mounted config inode; reload alone can retain the old file.
    run(compose+['up','-d','--no-build','--force-recreate','--no-deps','web'])
    run(compose+['exec','-T','web','nginx','-t'])
    run(compose+['exec','-T','web','nginx','-s','reload'])
    run(compose+['exec','-T','app','php','artisan','view:cache'])
    run(['python3','scripts/ops/backup-private-crm.py',str(DATA/'backups')])
    (PROTECTED/'prepare-verified.json').write_text(json.dumps({'sha':sha,'restore':'passed','scope':'synthetic-v1'})+'\n')
    print('PRIVATE PREPARE PASS: isolated loopback application and verified encrypted restore; ingress unchanged.')
elif mode=='verify':
    marker=PROTECTED/'prepare-verified.json'
    if not marker.is_file() or json.loads(marker.read_text())!={'sha':sha,'restore':'passed','scope':'synthetic-v1'}:
        raise SystemExit('Exact revision needs a verified initial restore before persistent fixtures.')
    if (DATA/'fixtures.json').exists():
        run(compose+['run','--rm','-T','tools','scripts/ops/seed-private-crm-fixtures.php','revoke'])
    run(compose+['run','--rm','-T','tools','scripts/ops/seed-private-crm-fixtures.php','seed'])
    run(['python3','scripts/ops/test-private-crm-http.py',str(DATA/'fixtures.json')])
    run(['python3','scripts/ops/backup-private-crm.py',str(DATA/'backups')])
    (PROTECTED/'http-verified.json').write_text(json.dumps({'sha':sha,'private_http':'passed','restore':'passed'})+'\n')
    print('PRIVATE HTTP VERIFY PASS: private client/admin journey and filled backup; ingress unchanged.')
elif mode=='access':
    marker=PROTECTED/'http-verified.json'
    if not marker.is_file() or json.loads(marker.read_text())!={'sha':sha,'private_http':'passed','restore':'passed'}:
        raise SystemExit('Exact revision needs completed private HTTP and restore verification.')
    if (DATA/'fixtures.json').exists():
        run(compose+['run','--rm','-T','tools','scripts/ops/seed-private-crm-fixtures.php','revoke'])
    if not (DATA/'review-access.json').exists():
        run(compose+['run','--rm','-T','tools','scripts/ops/seed-private-crm-review-access.php'])
    run(['python3','scripts/ops/test-private-crm-http.py',str(DATA/'review-access.json'),'review-access'])
    (PROTECTED/'access-verified.json').write_text(json.dumps({'sha':sha,'reviewer_access':'passed','automated_fixture_credentials':'revoked'})+'\n')
    print('PRIVATE REVIEWER ACCESS PASS: client/admin tested, automated fixture credentials revoked; ingress unchanged.')
else:
    raise SystemExit('Explicit prepare, verify or access required; no ingress change performed.')
