#!/usr/bin/env python3
"""CI-only separate Laravel products, HTTPS/mTLS, CRM-only PostgreSQL. No mock server."""
import base64,concurrent.futures,hashlib,json,os,secrets,subprocess,sys,tempfile,time,uuid
from pathlib import Path
if os.getenv('GITHUB_ACTIONS')!='true' or os.getenv('GITHUB_REPOSITORY')!='rafhael100/imagine-crm':sys.exit('CI_ONLY')
ROOT=Path(__file__).resolve().parents[3]
PORTAL=Path(sys.argv[1]).resolve()
A='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';B='bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'
C='cccccccc-cccc-4ccc-8ccc-cccccccccccc';D='dddddddd-dddd-4ddd-8ddd-dddddddddddd';F='ffffffff-ffff-4fff-8fff-ffffffffffff'
def run(args,data=None,ok=True,timeout=180):
 p=subprocess.run(args,input=data,text=True,capture_output=True,timeout=timeout)
 if ok and p.returncode:
  print((p.stdout+'\n'+p.stderr)[-5000:],file=sys.stderr);raise RuntimeError('COMMAND_FAILED_'+str(p.returncode))
 return p
with tempfile.TemporaryDirectory(prefix='central-auto-p19-',dir=os.environ['RUNNER_TEMP']) as folder:
 folder=Path(folder);certs=folder/'certs';pgcerts=folder/'pgcerts';certs.mkdir();pgcerts.mkdir()
 def openssl(*args):return run(['openssl',*args])
 openssl('req','-x509','-newkey','rsa:2048','-nodes','-keyout',str(certs/'ca.key'),'-out',str(certs/'ca.crt'),'-subj','/CN=SYNTHETIC_P19_CA','-days','1')
 for name,san in [('server','DNS:crm-s2s'),('portal','DNS:portal-automation-admin'),('other','DNS:portal-automation-admin'),('pg','DNS:crm-db')]:
  openssl('req','-newkey','rsa:2048','-nodes','-keyout',str(certs/(name+'.key')),'-out',str(certs/(name+'.csr')),'-subj','/CN='+name)
  ext=certs/(name+'.ext');ext.write_text('subjectAltName='+san+'\nextendedKeyUsage='+('clientAuth' if name in ['portal','other'] else 'serverAuth')+'\n')
  openssl('x509','-req','-in',str(certs/(name+'.csr')),'-CA',str(certs/'ca.crt'),'-CAkey',str(certs/'ca.key'),'-CAcreateserial','-out',str(certs/(name+'.crt')),'-days','1','-extfile',str(ext))
 fingerprint=hashlib.sha256(subprocess.run(['openssl','x509','-in',str(certs/'portal.crt'),'-outform','DER'],capture_output=True,check=True).stdout).hexdigest()
 for key in ['signing.key','db.password','owner.password']:
  value=secrets.token_hex(32);print('::add-mask::'+value);(certs/key).write_text(value)
 # No CA private key is ever mounted into an application or database.
 ca_private=(certs/'ca.key').read_bytes();(certs/'ca.key').unlink()
 for name in ['ca.crt','pg.crt','pg.key','owner.password']:
  (pgcerts/name).write_bytes((certs/name).read_bytes())
 for path in pgcerts.iterdir():os.chown(path,999,999);path.chmod(0o400)
 os.chown(pgcerts,999,999);pgcerts.chmod(0o700)
 for name in ['pg.key','pg.crt','owner.password']:(certs/name).unlink()
 for path in certs.iterdir():os.chown(path,33,33);path.chmod(0o400)
 os.chown(certs,33,33);certs.chmod(0o700)
 appkey='base64:'+base64.b64encode(secrets.token_bytes(32)).decode()
 compose={'services':{
 'crm-db':{'image':'postgres:16','environment':{'POSTGRES_DB':'imagine_crm_private_homologation','POSTGRES_USER':'crm_homologation_owner','POSTGRES_PASSWORD_FILE':'/pgcerts/owner.password'},'command':['postgres','-c','ssl=on','-c','ssl_cert_file=/pgcerts/pg.crt','-c','ssl_key_file=/pgcerts/pg.key'], 'volumes':[str(pgcerts)+':/pgcerts:ro','crm_pg:/var/lib/postgresql/data'],'networks':['private'],'mem_limit':'512m'},
 'crm-app':{'build':{'context':str(ROOT),'dockerfile':'infra/homologation/Dockerfile'},'environment':{'APP_ENV':'staging','APP_DEBUG':'false','APP_KEY':appkey,'LOG_CHANNEL':'stderr','CACHE_STORE':'array','SESSION_DRIVER':'array','CRM_AUTO_S2S_PROFILE':'synthetic-private-v1','CRM_AUTO_S2S_KEY_FILE':'/certs/signing.key','CRM_AUTO_S2S_DB_PASSWORD_FILE':'/certs/db.password','CRM_AUTO_S2S_DSN':'pgsql:host=crm-db;dbname=imagine_crm_private_homologation;sslmode=verify-full;sslrootcert=/certs/ca.crt'},'volumes':[str(certs)+':/certs:ro'],'networks':['private'],'read_only':True,'tmpfs':['/tmp','/var/www/html/storage:uid=33,gid=33,mode=0770','/var/www/html/bootstrap/cache:uid=33,gid=33,mode=0770'],'security_opt':['no-new-privileges:true'],'cap_drop':['ALL'],'mem_limit':'512m'},
 'crm-s2s':{'image':'nginx:1.28-alpine','volumes':[str(certs)+':/certs:ro',str(ROOT/'infra/homologation/s2s/nginx.conf')+':/etc/nginx/conf.d/default.conf:ro'],'networks':['private'],'mem_limit':'128m'},
 'portal':{'entrypoint':['php','/p19-read-private.php'],'build':{'context':str(PORTAL/'apps/portal'),'dockerfile':'tests/Integration/AutomationS2S/Dockerfile'},'environment':{'APP_ENV':'staging','APP_DEBUG':'false','APP_KEY':appkey,'CACHE_STORE':'array','SESSION_DRIVER':'array','LOG_CHANNEL':'stderr','AUTOMATION_S2S_CI':'synthetic-isolated'},'volumes':[str(certs)+':/certs:ro',str(ROOT/'scripts/ci/s2s/probe.php')+':/probe.php:ro',str(PORTAL/'apps/portal/tests/Integration/AutomationS2S/read-private.php')+':/p19-read-private.php:ro'],'networks':['private'],'read_only':True,'tmpfs':['/tmp','/var/www/html/storage:uid=33,gid=33,mode=0770','/var/www/html/bootstrap/cache:uid=33,gid=33,mode=0770'],'security_opt':['no-new-privileges:true'],'cap_drop':['ALL'],'mem_limit':'512m'}},'networks':{'private':{'internal':True}},'volumes':{'crm_pg':{}}}
 file=folder/'compose.json';file.write_text(json.dumps(compose))
 dc=['docker','compose','-p','central-auto-p19-ci','-f',str(file)]
 def sql(query):return run(dc+['exec','-T','crm-db','psql','-X','-qAt','-v','ON_ERROR_STOP=1','-U','crm_homologation_owner','-d','imagine_crm_private_homologation'],query).stdout.strip()
 def client(company=A,mode='reader',request=None,nonce=None,env=None):
  args=dc+['run','--rm','--no-deps','-T']
  for k,v in (env or {}).items():args+=['-e',k+'='+v]
  args+=['portal',company,mode]
  if request:args.extend([request,nonce])
  return run(args,ok=False,timeout=45)
 def state():
  p=client();assert p.returncode==0,p.stderr
  return json.loads(p.stdout.strip().splitlines()[-1])['state']
 try:
  run(dc+['build','crm-app','portal'],timeout=900)
  run(dc+['up','-d','crm-db'],timeout=90)
  for _ in range(30):
   p=run(dc+['exec','-T','crm-db','pg_isready','-U','crm_homologation_owner'],ok=False)
   if p.returncode==0:break
   time.sleep(1)
  sql((ROOT/'infra/homologation/schema.sql').read_text())
  sql((ROOT/'infra/homologation/s2s/install-candidate.sql').read_text())
  password=(certs/'db.password').read_text()
  sql("ALTER ROLE crm_auto_transport LOGIN PASSWORD '"+password+"';")
  now=int(time.time())
  sql(f"""INSERT INTO crm_global_identities(id) VALUES('{C}');
INSERT INTO crm_organizations(id) VALUES('{A}'),('{B}');
INSERT INTO crm_private_trials VALUES('{A}',{now-10},{now-10+1209600}),('{B}',{now-10},{now-10+1209600});
INSERT INTO crm_private_preferences(organization_id) VALUES('{A}'),('{B}');
INSERT INTO crm_private_funnels VALUES('{D}','{A}','FUNIL_TESTE_P19',{now});
INSERT INTO crm_private_leads(id,organization_id,funnel_id,title,created_at) VALUES('{F}','{A}','{D}','LEAD_TESTE_P19',{now});
INSERT INTO crm_private_automation_runs(id,organization_id,actor_id,authority_session_id,template,settings_version,lead_id,lead_version,execution_key,command,due_at,created_at)
VALUES('{uuid.uuid4()}','{A}','{C}','{uuid.uuid4()}','followup_qualified',0,'{F}',1,'p19-seeded-ledger','{{}}',{now},{now});
INSERT INTO crm_auto_service.grants VALUES('{A}','{fingerprint}','portal-automation-admin','automation.state.read',1,true,{now+600});""")
  run(dc+['up','-d','crm-app','crm-s2s'],timeout=90)
  time.sleep(3)
  initial=state();assert initial['counts']['pending']==1 and initial['counts']['completed']==0
  sql(f"UPDATE crm_private_automation_runs SET state='completed' WHERE organization_id='{A}'; INSERT INTO crm_private_events VALUES('{uuid.uuid4()}','{A}','{C}','{F}','automation.task_created',{int(time.time())});")
  updated=state();assert updated['counts']['completed']==1 and updated['counts']['pending']==0 and updated['events'][0]['action']=='automation.task_created'
  print('P19_REAL_PORTAL_LARAVEL_TO_CRM_LARAVEL_STATE_PASS synthetic_business_data=1')
  assert client(B,'transport').returncode!=0
  assert client(env={'LAB_CLIENT_CERT':'/certs/other.crt','LAB_CLIENT_KEY':'/certs/other.key'}).returncode!=0
  print('P19_CROSS_COMPANY_SAME_CA_DIFFERENT_CERT_DENIED')
  for mode in ['wrong-mac','wrong-operation','cookie','no-cert-forged-header']:
   response=run(dc+['run','--rm','--no-deps','-T','--entrypoint','php','portal','/probe.php',mode]).stdout.strip()
   assert response in ('403','400','TLS_DENIED'),(mode,response)
  print('P19_INVALID_SIGNATURE_OPERATION_COOKIE_AND_FORGED_MTLS_HEADER_DENIED')
  request=str(uuid.uuid4());nonce=secrets.token_hex(32)
  with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
   values=list(pool.map(lambda _:client(A,'transport',request,nonce).returncode,range(2)))
  assert sorted(values)==[0,2],values
  assert sql(f"SELECT count(*) FROM crm_auto_service.nonces WHERE request_id='{request}'")== '1'
  assert sql(f"SELECT count(*) FROM crm_auto_service.audit WHERE request_id='{request}' AND outcome='READ_OK'")== '1'
  print('P19_CONCURRENT_REPLAY_ONE_READ_ONE_DENIAL_PASS')
  assert sql("SELECT has_table_privilege('crm_auto_transport','crm_private_automation_runs','SELECT')")=='f'
  assert sql("SELECT has_table_privilege('crm_auto_transport','crm_auto_service.grants','UPDATE')")=='f'
  assert sql("SELECT rolbypassrls OR rolsuper FROM pg_roles WHERE rolname='crm_auto_reader_owner'")=='f'
  sql(f"UPDATE crm_auto_service.grants SET active=false,revision=2 WHERE company_id='{A}';")
  assert client(A,'transport',request,nonce).returncode!=0
  assert client().returncode!=0
  sql(f"UPDATE crm_auto_service.grants SET active=true,revision=3 WHERE company_id='{A}';")
  assert state()['counts']['completed']==1
  print('P19_REVOKED_REPLAY_AND_FRESH_DENIED_RECOVERY_PASS')
  # Hold only a CI fixture source table, proving the in-flight read retains the grant lock.
  with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
   blocker=pool.submit(sql,"BEGIN; LOCK public.crm_private_automation_runs IN ACCESS EXCLUSIVE MODE; SELECT pg_sleep(4); COMMIT;")
   for _ in range(30):
    if sql("SELECT count(*) FROM pg_locks WHERE relation='public.crm_private_automation_runs'::regclass AND mode='AccessExclusiveLock' AND granted")=='1':break
    time.sleep(.05)
   else:raise AssertionError('SOURCE_LOCK_NOT_HELD')
   waiting=pool.submit(client)
   for _ in range(30):
    if int(sql("SELECT count(*) FROM pg_stat_activity WHERE usename='crm_auto_transport' AND wait_event_type='Lock'"))>=1:break
    time.sleep(.05)
   else:raise AssertionError('READ_NOT_WAITING_WITH_GRANT_LOCK')
   revoking=pool.submit(sql,f"UPDATE crm_auto_service.grants SET active=false,revision=4 WHERE company_id='{A}';")
   time.sleep(.1)
   assert not revoking.done(),'REVOCATION_DID_NOT_WAIT_FOR_INFLIGHT_READ'
   assert waiting.result().returncode!=0
   revoking.result()
   assert client().returncode!=0
   blocker.result()
  sql(f"UPDATE crm_auto_service.grants SET active=true,revision=5 WHERE company_id='{A}';")
  assert state()['counts']['completed']==1
  print('P19_INFLIGHT_READ_GRANT_LOCK_TIMEOUT_REVOCATION_PASS')
  run(dc+['stop','crm-db'],timeout=45);assert client().returncode!=0
  run(dc+['start','crm-db']);time.sleep(3);assert state()['counts']['completed']==1
  print('P19_DATABASE_OUTAGE_FAIL_CLOSED_RECOVERY_PASS')
  # Restore the actual private CRM database into a separate database on this CI-only cluster.
  dump=run(dc+['exec','-T','crm-db','pg_dump','-U','crm_homologation_owner','imagine_crm_private_homologation']).stdout
  run(dc+['exec','-T','crm-db','createdb','-U','crm_homologation_owner','p19_restore'])
  run(dc+['exec','-T','crm-db','psql','-X','-q','-v','ON_ERROR_STOP=1','-U','crm_homologation_owner','-d','p19_restore'],dump)
  restored=run(dc+['exec','-T','crm-db','psql','-X','-qAt','-U','crm_homologation_owner','-d','p19_restore','-c',"SELECT count(*) FROM crm_auto_service.audit WHERE outcome='READ_OK'"]).stdout.strip()
  assert int(restored)>=4
  restore_owner=run(dc+['exec','-T','crm-db','psql','-X','-qAt','-U','crm_homologation_owner','-d','p19_restore','-c',"SELECT pg_get_userbyid(proowner) FROM pg_proc WHERE oid='crm_auto_service.read_state(uuid,text,text,text,uuid,text,text,bigint)'::regprocedure"]).stdout.strip()
  assert restore_owner=='crm_auto_reader_owner'
  print('P19_CRM_LEDGER_GRANTS_NONCES_AUDIT_RESTORED_PASS external_backup=0')
  print('P19_INTEGRATED_READ_PASS NO_GO operational_dispatch=0 paid_ai_calls=0 human_IAM=NOT_HOMOLOGATED')
 finally:
  run(dc+['down','-v','--remove-orphans'],ok=False,timeout=90)
