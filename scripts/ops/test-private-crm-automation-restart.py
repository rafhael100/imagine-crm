#!/usr/bin/env python3
"""CI only: restart only the ephemeral app and verify durable execution ledger contents."""
import os,subprocess,time,urllib.request
from pathlib import Path
assert os.environ.get('GITHUB_ACTIONS')=='true'
assert os.environ.get('CRM_PRIVATE_PROJECT')=='imagine-crm-journey-ci'
env=Path(os.environ['CRM_PRIVATE_ENV_FILE']);assert env.is_file() and not (env.stat().st_mode & 0o077)
compose=['docker','compose','--env-file',str(env),'-p','imagine-crm-journey-ci','-f','infra/homologation/compose.yaml']
def run(args):
 p=subprocess.run(args,capture_output=True,text=True,timeout=45)
 if p.returncode:raise RuntimeError('Isolated restart check failed; raw logs suppressed')
 return p.stdout.strip()
def snapshot():
 return run(compose+['exec','-T','postgres','psql','-X','-qAt','-U','crm_homologation_owner','-d','imagine_crm_private_homologation','-c',"SELECT count(*)::text||':'||md5(coalesce(string_agg(id::text||state||attempts::text||execution_key,',' ORDER BY id),'')) FROM crm_private_automation_runs"])
before=snapshot();assert int(before.split(':')[0])>0
run(compose+['restart','app'])
ready=False
for _ in range(60):
 try:
  with urllib.request.urlopen('http://127.0.0.1:18086/up',timeout=2) as r:ready=r.status==200
 except Exception:pass
 if ready:break
 time.sleep(.2)
assert ready and snapshot()==before
print('CENTRAL_AUTO_P1_RESTART_PASS ledger_persisted=1 only_ephemeral_app_restarted=1')
