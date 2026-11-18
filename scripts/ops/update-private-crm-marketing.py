#!/usr/bin/env python3
"""Views-only maintenance of the existing private CRM; no fixtures, grants or migrations."""
import hashlib
import http.client
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import time
import urllib.request

ROOT=Path(__file__).resolve().parents[2]
STATE=Path('/srv/imagine/private-crm-journey')
ENV=STATE/'runtime.env'
ALLOWED={'resources/views/crm-private-journey.blade.php','resources/views/crm-marketing-home.blade.php',
 'scripts/ops/test-private-crm-browser.cjs','.github/workflows/ci-private-crm-journey.yml',
 'scripts/ops/update-private-crm-marketing.py'}
if os.geteuid()!=0 or ROOT!=Path('/srv/imagine/crm-private-candidate'):
    raise SystemExit('Dedicated private CRM operator checkout required.')
if len(sys.argv)!=2 or not re.fullmatch(r'[0-9]{6,20}',sys.argv[1]): raise SystemExit('Exact green workflow run required.')

def run(command): subprocess.run(command,cwd=ROOT,check=True,stdin=subprocess.DEVNULL)
def github(path):
    request=urllib.request.Request('https://api.github.com/repos/rafhael100/imagine-crm/'+path,
        headers={'Accept':'application/vnd.github+json','User-Agent':'Imagine-private-marketing-maintenance'})
    with urllib.request.urlopen(request,timeout=20) as response: return json.load(response)
def digest(path): return hashlib.sha256(path.read_bytes()).hexdigest()

def protected():
    r=subprocess.run(['curl','--max-time','10','-sS','-o','/dev/null','-w','%{http_code}',
        'https://crm-dev.imaginecomunica.com.br/'],capture_output=True,text=True)
    if r.returncode or r.stdout!='401': raise RuntimeError('Existing TLS and private BasicAuth must remain intact.')

def smoke(marketing):
    for _ in range(30):
        try:
            conn=http.client.HTTPConnection('127.0.0.1',18086,timeout=5)
            conn.request('GET','/',headers={'Host':'crm-dev.imaginecomunica.com.br'})
            response=conn.getresponse();body=response.read().decode();conn.close()
            if response.status==200 and ('data-home-section="faq"' in body if marketing else 'Imagine CRM' in body): break
        except (OSError,http.client.HTTPException): pass
        time.sleep(0.5)
    else: raise RuntimeError('Private CRM landing health check failed.')
    for path in ['/login','/recuperar','/?lang=en','/?lang=es']:
        conn=http.client.HTTPConnection('127.0.0.1',18086,timeout=5)
        conn.request('GET',path,headers={'Host':'crm-dev.imaginecomunica.com.br'})
        response=conn.getresponse();response.read();conn.close()
        if response.status!=200: raise RuntimeError('Private sign-in or locale page unavailable.')

sha=subprocess.check_output(['git','rev-parse','HEAD'],cwd=ROOT,text=True).strip()
marker=json.loads((STATE/'published.json').read_text());previous=marker['sha']
if subprocess.run(['git','diff','--quiet','HEAD'],cwd=ROOT).returncode: raise SystemExit('Tracked source changes not committed.')
changed=set(subprocess.check_output(['git','diff','--name-only',previous,sha],cwd=ROOT,text=True).splitlines())
if not changed or not changed<=ALLOWED: raise SystemExit('Maintenance is limited to the reviewed marketing files.')
workflow=github('actions/runs/'+sys.argv[1])
checks=github('commits/'+sha+'/check-runs?per_page=100')['check_runs']
if workflow.get('head_sha')!=sha or workflow.get('conclusion')!='success' or workflow.get('workflow_id')!=419173788 \
    or len(checks)<19 or any(c['conclusion']!='success' for c in checks):
    raise SystemExit('All exact-SHA integration and marketing browser checks must pass.')
if digest(Path('/etc/nginx/sites-available/imagine-crm-private-journey'))!=marker['crm_config_sha256'] \
    or digest(Path('/etc/nginx/sites-available/imagine-dev'))!=marker['shared_after_sha256']:
    raise SystemExit('Existing ingress differs from verified release; no changes made.')
if ENV.stat().st_mode & 0o077: raise SystemExit('Protected environment permissions required.')
original=ENV.read_text()
expected="CRM_PRIVATE_IMAGE_TAG='"+previous+"'"
if expected not in original: raise SystemExit('Runtime image does not match the recorded release.')
os.umask(0o077)
release=STATE/('marketing-'+sha[:12]);release.mkdir(mode=0o700,exist_ok=False)
(release/'runtime-before.env').write_text(original)
candidate=release/'candidate.env';candidate.write_text(original.replace(expected,"CRM_PRIVATE_IMAGE_TAG='"+sha+"'",1))
base=['docker','compose','-p','imagine-crm-private-journey','-f','infra/homologation/compose.yaml']
compose=lambda file:['docker','compose','--env-file',str(file),*base[2:]]
protected();smoke(False)
os.environ['CRM_PRIVATE_ENV_FILE']=str(ENV)
run(['python3','scripts/ops/backup-private-crm.py',str(STATE/'data/backups')])
run(compose(candidate)+['build','app'])
run(compose(candidate)+['run','--rm','-T','--no-deps','app','sh','-c',
    'mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs bootstrap/cache && php artisan view:cache && for view in storage/framework/views/*.php; do php -l "$view" || exit 1; done'])
try:
    candidate.replace(ENV)
    run(compose(ENV)+['up','-d','--no-build','--no-deps','app'])
    run(compose(ENV)+['up','-d','--no-build','--no-deps','--force-recreate','web'])
    smoke(True);protected()
except BaseException:
    temporary=release/'rollback.env';temporary.write_text(original);temporary.replace(ENV)
    run(compose(ENV)+['up','-d','--no-build','--no-deps','app'])
    run(compose(ENV)+['up','-d','--no-build','--no-deps','--force-recreate','web'])
    smoke(False);protected()
    raise
record={'sha':sha,'previous_sha':previous,'workflow_run':int(sys.argv[1]),'scope':'marketing-views-only',
    'database_and_credentials':'preserved','tls_and_basic_auth':'preserved','other_projects':'unchanged',
    'url':'https://crm-dev.imaginecomunica.com.br/'}
(release/'verified.json').write_text(json.dumps(record,indent=2)+'\n')
marker['base_release_sha']=marker.get('base_release_sha',previous)
marker['sha']=sha;marker['workflow_run']=int(sys.argv[1]);marker['latest_maintenance']='marketing-views-only'
(STATE/'published.json').write_text(json.dumps(marker,indent=2)+'\n')
print('PRIVATE MARKETING UPDATE PASS: validated views published; database, credentials, TLS, BasicAuth and other projects preserved.')
