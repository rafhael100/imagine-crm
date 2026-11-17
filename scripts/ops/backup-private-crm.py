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
from private_crm_backup_contract import (
    BASE_TENANT_TABLES, DATABASE, S2S_TABLES, detect_profile, isolated_restore_cluster,
    manifest_digest, scoped_counts, table_counts, verify_restored_security,
)

ROOT=Path(__file__).resolve().parents[2]
PROJECT=os.environ.get('CRM_PRIVATE_PROJECT','imagine-crm-private-journey')
ENVFILE=Path(os.environ['CRM_PRIVATE_ENV_FILE']).resolve()
if PROJECT not in ('imagine-crm-private-journey','imagine-crm-journey-ci') or ENVFILE.stat().st_mode & 0o077:
    raise SystemExit('Protected, isolated private CRM environment required.')
COMPOSE=['docker','compose','--env-file',str(ENVFILE),'-p',PROJECT,'-f',str(ROOT/'infra/homologation/compose.yaml')]
TABLES=re.findall(r'CREATE TABLE\s+(crm_[a-z0-9_]+)\s*\(', (ROOT/'infra/homologation/schema.sql').read_text())


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


def scope_evidence(query, profile, tenants):
    roles={'crm_homologation_app':BASE_TENANT_TABLES}
    if profile['s2s']: roles['crm_auto_reader_owner']=S2S_TABLES
    evidence={}
    for role,tables in roles.items():
        evidence[role]={tenant:scoped_counts(query,tenant,role=role,tables=tables)
                        for tenant in ['',*tenants]}
        if evidence[role]['']!='SCOPED|'+'|'.join('0' for _ in tables):
            raise RuntimeError('Source or restored role leaks unscoped tenant records.')
    return evidence


def main():
    directory=Path(sys.argv[1]).resolve()
    os.umask(0o077);directory.mkdir(parents=True,exist_ok=True)
    profile,security=detect_profile(source_sql,TABLES)
    before=table_counts(source_sql,profile)
    tenants=source_sql("SELECT DISTINCT organization_id::text FROM crm_private_accounts WHERE role='client' ORDER BY organization_id::text LIMIT 2").splitlines()
    tenants=[tenant for tenant in tenants if tenant]
    source_scope=scope_evidence(source_sql,profile,tenants)
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
    with isolated_restore_cluster(profile,run) as (restored_sql,restore_command):
        # Preserve the restricted owners, especially those of SECURITY DEFINER functions.
        # A fresh isolated cluster uses the contract's DB name; it has no network or ingress.
        run(restore_command+['pg_restore','--exit-on-error','-U','crm_homologation_restore','-d',DATABASE],decrypted)
        verify_restored_security(restored_sql,profile,security,TABLES)
        if table_counts(restored_sql,profile)!=before: raise RuntimeError('Restored table counts differ.')
        if scope_evidence(restored_sql,profile,tenants)!=source_scope:
            raise RuntimeError('Restored tenant visibility differs.')
        grants=restored_sql("SELECT NOT has_table_privilege('crm_homologation_app','public.crm_auth_credentials','SELECT') AND NOT has_column_privilege('crm_homologation_app','public.crm_memberships','status','UPDATE') AND has_table_privilege('crm_homologation_app','public.crm_private_leads','INSERT')")
        if grants!='t': raise RuntimeError('Restored application permissions unsafe.')
        manifest={'manifest_version':2,'scope':'fictional-private-crm-only','project':PROJECT,'created_utc':stamp,
            'encrypted_archive':archive.name,'sha256':hashlib.sha256(encrypted).hexdigest(),
            'database_profile':profile['name'],'s2s_receipt_reconciliation':profile['receipt_reconciliation'],
            'table_counts':before,'force_rls_tables':[table.removeprefix('public.') for table in profile['rls_tables']],
            'security_manifest_sha256':manifest_digest(security),'object_owners_preserved':True,
            's2s_function_owners_verified':len(security['functions']),'restore_network':'none',
            'plaintext_dump_on_disk':False,'tamper_rejected':True,'restore_verified':True,
            'tenant_scopes_verified':len(source_scope['crm_homologation_app']),
            'scope_roles_verified':sorted(source_scope)}
        (directory/('crm-'+stamp+'.json')).write_text(json.dumps(manifest,indent=2)+'\n')
        print('PRIVATE BACKUP PASS: authenticated encryption, tamper rejection, separate PG16 restore, counts, FORCE RLS, grants and restricted ownership; profile='+profile['name'])


if __name__=='__main__': main()

