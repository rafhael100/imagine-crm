#!/usr/bin/env python3
"""Create NEW secrets in an operator-protected path; never overwrite or print them."""
import base64
import json
import os
from pathlib import Path
import secrets
import re
import sys

target=Path(sys.argv[1]).resolve()
data=Path(sys.argv[2]).resolve()
if target.exists():
    raise SystemExit('Refusing to replace existing environment or rotate keys implicitly.')
os.umask(0o077)
target.parent.mkdir(parents=True,exist_ok=True)
data.mkdir(parents=True,exist_ok=True)
values={name:secrets.token_hex(32) for name in ['CRM_PRIVATE_APP_PASSWORD','CRM_PRIVATE_IAM_PASSWORD',
    'CRM_PRIVATE_ENROLLMENT_PASSWORD','CRM_PRIVATE_OPERATOR_PASSWORD','CRM_PRIVATE_OWNER_PASSWORD','CRM_PRIVATE_REDIS_PASSWORD']}
values['CRM_PRIVATE_APP_KEY']='base64:'+base64.b64encode(secrets.token_bytes(32)).decode()
values['CRM_PRIVATE_BACKUP_KEY']=base64.b64encode(secrets.token_bytes(32)).decode()
values['CRM_PRIVATE_TRIAL_KEYS']=json.dumps({'1':secrets.token_hex(32)},separators=(',',':'))
values['CRM_PRIVATE_DATA_DIR']=str(data)
tag=sys.argv[3] if len(sys.argv)>3 else 'candidate'
if not re.fullmatch(r'[a-f0-9]{7,40}|candidate',tag):
    raise SystemExit('Invalid immutable image tag.')
values['CRM_PRIVATE_IMAGE_TAG']=tag
if any('\n' in str(v) or '\r' in str(v) or "'" in str(v) for v in values.values()):
    raise SystemExit('Invalid environment path.')
with target.open('x') as stream:
    stream.write(''.join(f"{key}='{value}'\n" for key,value in values.items()))
target.chmod(0o600)
if os.environ.get('GITHUB_ACTIONS')=='true':
    for value in values.values():
        print('::add-mask::'+str(value))
print('Created private environment with separate secrets; no credential values displayed.')
