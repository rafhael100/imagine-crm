#!/usr/bin/env python3
"""Encrypted backup + real restore on a separate network-none PostgreSQL16.

Source is read-only and fixed to the fictional CRM stack. No plaintext dump is
written to disk. Run before opening ingress and during quiet maintenance windows.
"""
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path
import re
import secrets
import subprocess
import sys
import time

ROOT=Path(__file__).resolve().parents[2]
PROJECT=os.environ.get('CRM_PRIVATE_PROJECT','imagine-crm-private-journey')
ENVFILE=Path(os.environ['CRM_PRIVATE_ENV_FILE']).resolve()
if PROJECT not in ('imagine-crm-private-journey','imagine-crm-journey-ci') or ENVFILE.stat().st_mode & 0o077:
    raise SystemExit('Protected, isolated private CRM environment required.')
COMPOSE=['docker','compose','--env-file',str(ENVFILE),'-p',PROJECT,'-f',str(ROOT/'infra/homologation/compose.yaml')]
TABLES=re.findall(r'CREATE TABLE\s+(crm_[a-z0-9_]+)\s*\(', (ROOT/'infra/homologation/schema.sql').read_text())
TENANT_TABLES=['crm_memberships','crm_private_trials','crm_private_leads','crm_private_tasks',
    'crm_private_events','crm_private_orders','crm_private_preferences','crm_private_funnels','crm_private_invitations']


def run(command,content=None,environment=None):
    result=subprocess.run(command,input=content,stdout=subprocess.PIPE,stderr=subprocess.PIPE,env=environment)
    if result.returncode:
        safe=re.findall(rb'Private backup crypto failed: [A-Za-z0-9_\\]+ at [A-Za-z0-9_.-]+:[0-9]+',result.stderr)
        if safe: print(safe[-1].decode(),file=sys.stderr)
        raise RuntimeError('Private backup phase failed; no raw data or credentials displayed.')
    return result.stdout


def sql(command,text):
    return run(command+['psql','-X','-qAt','-v','ON_ERROR_STOP=1']+text).decode().strip()


SOURCE=COMPOSE+['exec','-T','postgres']


def source_sql(query):
    return sql(SOURCE,['-U','crm_homologation_owner','-d','imagine_crm_private_homologation','-c',query])


def counts(query):
    return {table:int(query('SELECT count(*) FROM public.'+table)) for table in TABLES}


def scoped(query,tenant):
    # Values come exclusively from server UUIDs or the empty context.
    if tenant and not re.fullmatch(r'[a-f0-9-]{36}',tenant): raise RuntimeError('Invalid private tenant UUID.')
    body=' || \'|\' || '.join('(SELECT count(*) FROM public.'+table+')' for table in TENANT_TABLES)
    text="BEGIN; SET ROLE crm_homologation_app; SELECT set_config('app.tenant_id','"+tenant+"',true); SELECT 'SCOPED|' || "+body+'; ROLLBACK;'
    lines=query(text).splitlines()
    matches=[line for line in lines if line.startswith('SCOPED|')]
    if len(matches)!=1: raise RuntimeError('Missing private RLS evidence.')
    return matches[0]


def main():
    directory=Path(sys.argv[1]).resolve()
    os.umask(0o077);directory.mkdir(parents=True,exist_ok=True)
    if source_sql('SELECT current_database()')!='imagine_crm_private_homologation':
        raise RuntimeError('Wrong private backup source.')
    before=counts(source_sql)
    tenants=source_sql("SELECT organization_id::text FROM crm_private_accounts WHERE role='client' ORDER BY identity_id LIMIT 2").splitlines()
    tenants=[tenant for tenant in tenants if tenant]
    source_scope={tenant:scoped(source_sql,tenant) for tenant in ['',*tenants]}
    if source_scope['']!='SCOPED|'+'|'.join('0' for _ in TENANT_TABLES):
        raise RuntimeError('Source application role leaks unscoped tenant records.')
    dump=run(SOURCE+['pg_dump','-U','crm_homologation_owner','-d','imagine_crm_private_homologation','-Fc'])
    if len(dump)>67108864: raise RuntimeError('Private dump size limit exceeded.')
    crypt=COMPOSE+['run','--rm','-T','tools','scripts/ops/private-backup-crypt.php']
    encrypted=run(crypt+['encrypt'],dump)
    stamp=datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ')+'-'+secrets.token_hex(3)
    archive=directory/('crm-'+stamp+'.enc')
    with archive.open('xb') as stream: stream.write(encrypted)
    archive.chmod(0o600)
    decrypted=run(crypt+['decrypt'],encrypted)
    if not hashlib.sha256(decrypted).digest()==hashlib.sha256(dump).digest():
        raise RuntimeError('Backup wire mismatch: source bytes='+str(len(dump))+', restored bytes='+str(len(decrypted))+', source PG header='+str(dump.startswith(b'PGDMP'))+', restored PG header='+str(decrypted.startswith(b'PGDMP'))+', PG header offset='+str(decrypted.find(b'PGDMP')))
    tampered=encrypted[:-1]+bytes([encrypted[-1]^1])
    bad=subprocess.run(crypt+['decrypt'],input=tampered,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
    if bad.returncode==0: raise RuntimeError('Tampered backup was accepted.')
    restorer='imagine-crm-restore-check-'+secrets.token_hex(5)
    environment=dict(os.environ,POSTGRES_PASSWORD=secrets.token_hex(32))
    try:
        run(['docker','run','--name',restorer,'--network','none','--security-opt','no-new-privileges:true',
             '--tmpfs','/var/lib/postgresql/data','-d','--env','POSTGRES_PASSWORD',
             '--env','POSTGRES_DB=imagine_crm_private_restore','--env','POSTGRES_USER=crm_homologation_restore','postgres:16'],environment=environment)
        restore_command=['docker','exec','-i',restorer]
        def restored_sql(query):
            return sql(restore_command,['-U','crm_homologation_restore','-d','imagine_crm_private_restore','-c',query])
        ready=False
        for _ in range(40):
            process=subprocess.run(['docker','exec',restorer,'cat','/proc/1/comm'],capture_output=True,text=True)
            if process.returncode==0 and process.stdout.strip()=='postgres':
                try:
                    if restored_sql('SELECT current_database()')=='imagine_crm_private_restore': ready=True;break
                except RuntimeError: pass
            time.sleep(0.5)
        if not ready: raise RuntimeError('Final restore PostgreSQL server unavailable.')
        roles=['crm_homologation_owner','crm_homologation_app','crm_homologation_iam','crm_homologation_enrollment','crm_homologation_operator']
        restored_sql(';'.join('CREATE ROLE '+role+' NOLOGIN NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOINHERIT' for role in roles))
        run(restore_command+['pg_restore','--no-owner','--exit-on-error','-U','crm_homologation_restore','-d','imagine_crm_private_restore'],decrypted)
        if counts(restored_sql)!=before: raise RuntimeError('Restored table counts differ.')
        for tenant,expected in source_scope.items():
            if scoped(restored_sql,tenant)!=expected: raise RuntimeError('Restored tenant visibility differs.')
        for table in TENANT_TABLES:
            result=restored_sql("SELECT relrowsecurity AND relforcerowsecurity FROM pg_class WHERE oid='public."+table+"'::regclass")
            if result!='t': raise RuntimeError('Restored FORCE RLS missing.')
        grants=restored_sql("SELECT NOT has_table_privilege('crm_homologation_app','public.crm_auth_credentials','SELECT') AND NOT has_column_privilege('crm_homologation_app','public.crm_memberships','status','UPDATE') AND has_table_privilege('crm_homologation_app','public.crm_private_leads','INSERT')")
        if grants!='t': raise RuntimeError('Restored application permissions unsafe.')
        manifest={'scope':'fictional-private-crm-only','project':PROJECT,'created_utc':stamp,
            'encrypted_archive':archive.name,'sha256':hashlib.sha256(encrypted).hexdigest(),
            'table_counts':before,'force_rls_tables':TENANT_TABLES,'restore_network':'none',
            'plaintext_dump_on_disk':False,'tamper_rejected':True,'restore_verified':True,
            'tenant_scopes_verified':len(source_scope)}
        (directory/('crm-'+stamp+'.json')).write_text(json.dumps(manifest,indent=2)+'\n')
        print('PRIVATE BACKUP PASS: authenticated encryption, tamper rejection, separate PG16 restore, counts, FORCE RLS and grants.')
    finally:
        subprocess.run(['docker','rm','-f',restorer],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)


if __name__=='__main__': main()
