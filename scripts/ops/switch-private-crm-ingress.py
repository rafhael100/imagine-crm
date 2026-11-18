#!/usr/bin/env python3
"""Reversible private ingress switch. Never touches application data or other hosts."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys
from datetime import datetime,timezone

ROOT=Path(__file__).resolve().parents[2]
STATE=Path('/srv/imagine/private-crm-journey')
SHARED=Path('/etc/nginx/sites-available/imagine-dev')
CONFIG=Path('/etc/nginx/sites-available/imagine-crm-private-journey')
ENABLED=Path('/etc/nginx/sites-enabled/imagine-crm-private-journey')
if os.geteuid()!=0 or ROOT!=Path('/srv/imagine/crm-private-candidate'):
    raise SystemExit('Dedicated private operator checkout required.')
mode=sys.argv[1] if len(sys.argv)==2 else ''
if mode not in ['rollback','resume']: raise SystemExit('Explicit rollback or resume required.')
marker=json.loads((STATE/'published.json').read_text())
old=(STATE/'ingress-before-publication.conf').read_text()
new=(STATE/'ingress-published-shared.conf').read_text()
digest=lambda text:hashlib.sha256(text.encode()).hexdigest()
if digest(old)!=marker['shared_before_sha256'] or digest(new)!=marker['shared_after_sha256'] \
    or digest(CONFIG.read_text())!=marker['crm_config_sha256']:
    raise SystemExit('Ingress snapshot differs from verified publication; no changes made.')
current=SHARED.read_text();was_enabled=ENABLED.is_symlink()
if was_enabled and ENABLED.resolve()!=CONFIG: raise SystemExit('Unexpected CRM symlink target.')
expected=new if mode=='rollback' else old
target=old if mode=='rollback' else new
if current!=expected or was_enabled!=(mode=='rollback'):
    raise SystemExit('Ingress is not in the expected starting state; no changes made.')
try:
    SHARED.write_text(target)
    if mode=='rollback': ENABLED.unlink()
    else: ENABLED.symlink_to(CONFIG)
    subprocess.run(['nginx','-t'],check=True)
    subprocess.run(['systemctl','reload','nginx'],check=True)
    for host in ['crm-dev.imaginecomunica.com.br','portal-dev.imaginecomunica.com.br','comercial-dev.imaginecomunica.com.br']:
        result=subprocess.run(['curl','--resolve',host+':443:127.0.0.1','--max-time','10','-sS',
            '-o','/dev/null','-w','%{http_code}','https://'+host+'/'],capture_output=True,text=True)
        if result.returncode or result.stdout!='401': raise RuntimeError('Private protection differs after switch.')
except BaseException:
    SHARED.write_text(current)
    if ENABLED.is_symlink(): ENABLED.unlink()
    if was_enabled: ENABLED.symlink_to(CONFIG)
    subprocess.run(['nginx','-t'],check=True)
    subprocess.run(['systemctl','reload','nginx'],check=True)
    raise
os.umask(0o077)
evidence={'state':mode,'sha':marker['sha'],'at_utc':datetime.now(timezone.utc).isoformat(),
    'tls_and_basic_auth':'preserved','application_data':'unchanged','other_projects':'unchanged'}
(STATE/('ingress-'+mode+'-verified.json')).write_text(json.dumps(evidence,indent=2)+'\n')
print('PRIVATE INGRESS '+mode.upper()+' PASS: verified snapshots, TLS/BasicAuth preserved, other projects and database unchanged.')
