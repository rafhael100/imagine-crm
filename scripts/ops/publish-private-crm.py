#!/usr/bin/env python3
"""Promote only the verified fictional CRM host, preserving TLS and BasicAuth."""
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import urllib.request

ROOT=Path(__file__).resolve().parents[2]
STATE=Path('/srv/imagine/private-crm-journey')
HOST='crm-dev.imaginecomunica.com.br'
SHARED=Path('/etc/nginx/sites-available/imagine-dev')
CONFIG=Path('/etc/nginx/sites-available/imagine-crm-private-journey')
ENABLED=Path('/etc/nginx/sites-enabled/imagine-crm-private-journey')
if os.geteuid()!=0 or ROOT!=Path('/srv/imagine/crm-private-candidate'):
    raise SystemExit('Dedicated private Leaseweb checkout and operator required.')
sha=subprocess.check_output(['git','rev-parse','HEAD'],cwd=ROOT,text=True).strip()
if not re.fullmatch('[a-f0-9]{40}',sha): raise SystemExit('Invalid source revision.')
if subprocess.run(['git','diff','--quiet','HEAD'],cwd=ROOT).returncode: raise SystemExit('Uncommitted source changes.')
run_id=sys.argv[1] if len(sys.argv)==2 else ''
if not re.fullmatch('[0-9]{6,20}',run_id): raise SystemExit('Exact successful private journey CI run required.')
request=urllib.request.Request('https://api.github.com/repos/rafhael100/imagine-crm/actions/runs/'+run_id,
    headers={'Accept':'application/vnd.github+json','User-Agent':'Imagine-private-staging-verification'})
with urllib.request.urlopen(request,timeout=20) as response: workflow=json.load(response)
if workflow.get('head_sha')!=sha or workflow.get('conclusion')!='success' or workflow.get('status')!='completed' \
    or workflow.get('workflow_id')!=419173788 or workflow.get('head_repository',{}).get('full_name')!='rafhael100/imagine-crm':
    raise SystemExit('Private HTTP, Chromium, restore and resilience CI must pass for this exact SHA.')
for filename,expected in [
    ('prepare-verified.json',{'sha':sha,'restore':'passed','scope':'synthetic-v1'}),
    ('http-verified.json',{'sha':sha,'private_http':'passed','restore':'passed'}),
    ('access-verified.json',{'sha':sha,'reviewer_access':'passed','automated_fixture_credentials':'revoked'}),
]:
    file=STATE/filename
    if not file.is_file() or json.loads(file.read_text())!=expected: raise SystemExit('Missing exact-revision VPS verification.')
if (STATE/'data/fixtures.json').exists(): raise SystemExit('Revoke automated test credentials before opening ingress.')
review=STATE/'data/review-access.json'
if not review.is_file() or review.stat().st_mode & 0o077: raise SystemExit('Protected reviewer access must be prepared first.')
if CONFIG.exists() or ENABLED.exists() or ENABLED.is_symlink(): raise SystemExit('Existing CRM ingress requires explicit review; no implicit overwrite.')
original=SHARED.read_text()
match=re.match(r'(server\s*\{\s*server_name\s+)([^;]+)(;)',original)
if not match: raise SystemExit('Shared ingress shape changed.')
hosts=match[2].split()
if hosts.count(HOST)!=1 or len(hosts)<2: raise SystemExit('Private CRM host binding is ambiguous.')
remaining=[host for host in hosts if host!=HOST]
changed=original[:match.start(2)]+' '.join(remaining)+original[match.end(2):]
text='''server {
    listen 443 ssl;
    server_name crm-dev.imaginecomunica.com.br;
    server_tokens off;
    if ($imagine_preview = 0) { return 503; }
    auth_basic "Imagine Desenvolvimento";
    auth_basic_user_file /etc/nginx/dev.htpasswd;
    ssl_certificate /etc/letsencrypt/live/imagine-dev/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/imagine-dev/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    add_header X-Robots-Tag "noindex, nofollow, noarchive" always;
    add_header Cache-Control "private, no-store" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    client_max_body_size 64k;
    location / {
        limit_except GET HEAD POST { deny all; }
        proxy_pass http://127.0.0.1:18086;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $remote_addr;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header Authorization "";
        proxy_redirect off;
        proxy_read_timeout 30s;
        proxy_connect_timeout 5s;
    }
}
'''

def protected_status(host,path='/'):
    result=subprocess.run(['curl','--resolve',host+':443:127.0.0.1','--max-time','10','-sS',
        '-o','/dev/null','-w','%{http_code}','https://'+host+path],capture_output=True,text=True)
    if result.returncode: raise RuntimeError('Private HTTPS verification failed; certificate checks remain enabled.')
    return result.stdout

others=['portal-dev.imaginecomunica.com.br','comercial-dev.imaginecomunica.com.br']
before={host:protected_status(host) for host in others}
if any(code!='401' for code in before.values()) or protected_status(HOST)!='401':
    raise SystemExit('Existing private BasicAuth protection must be confirmed before promotion.')
os.umask(0o077)
backup=STATE/'ingress-before-publication.conf'
if backup.exists(): raise SystemExit('Existing ingress rollback copy; inspect before another promotion.')
shutil.copy2(SHARED,backup);backup.chmod(0o600)
try:
    CONFIG.write_text(text);CONFIG.chmod(0o644)
    SHARED.write_text(changed)
    ENABLED.symlink_to(CONFIG)
    subprocess.run(['nginx','-t'],check=True)
    subprocess.run(['systemctl','reload','nginx'],check=True)
    for endpoint in ['/','/login','/admin','/cadastro','/assets/logo-imagine.png']:
        if protected_status(HOST,endpoint)!='401': raise RuntimeError('Private authentication boundary changed.')
    for host,status in before.items():
        if protected_status(host)!=status: raise RuntimeError('Another project ingress changed.')
except BaseException:
    SHARED.write_text(original)
    if ENABLED.is_symlink(): ENABLED.unlink()
    if CONFIG.exists(): CONFIG.unlink()
    subprocess.run(['nginx','-t'],check=True)
    subprocess.run(['systemctl','reload','nginx'],check=True)
    raise
marker={'sha':sha,'workflow_run':int(run_id),'url':'https://'+HOST+'/',
    'basic_auth':'preserved','tls':'verified','other_projects':'unchanged',
    'shared_before_sha256':hashlib.sha256(original.encode()).hexdigest(),
    'shared_after_sha256':hashlib.sha256(changed.encode()).hexdigest()}
(STATE/'ingress-published-shared.conf').write_text(changed)
marker['crm_config_sha256']=hashlib.sha256(text.encode()).hexdigest()
(STATE/'published.json').write_text(json.dumps(marker,indent=2)+'\n')
(STATE/'data/publication-opened.json').write_text(json.dumps({'sha':sha,'scope':'private-review'})+'\n')
print('PRIVATE PUBLICATION PASS: exact tested SHA, CRM host only, TLS and BasicAuth preserved; other projects unchanged.')
