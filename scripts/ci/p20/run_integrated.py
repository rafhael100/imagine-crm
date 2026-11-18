#!/usr/bin/env python3
"""CI-only functional overlay. Pending guard binding stops before service creation."""
import os
import json
import hashlib
import subprocess
from pathlib import Path
import sys
from inspect_inputs import inventory
from prepare_harness import render
from p20_runtime import load_portal

if __name__ == '__main__':
    if os.environ.get('GITHUB_ACTIONS') != 'true' or os.environ.get('GITHUB_REPOSITORY') != 'rafhael100/imagine-crm':
        raise SystemExit('P20_DENIED_NON_CRM_CI')
    if os.environ.get('P20_REPOSITORY_PUBLIC') != 'true':
        raise SystemExit('P20_DENIED_NON_PUBLIC_REPOSITORY')
    root=Path(__file__).resolve().parents[3]
    # The product and guard are separate pinned checkouts. Never move the #910 candidate to get tooling.
    print(json.dumps(inventory({'crm':root,'portal':root/'pinned-portal','agents':root/'pinned-agents'}),sort_keys=True))
    print(json.dumps({'schema':'P20_HARNESS_INPUTS_V1','harness_commit':subprocess.check_output(['git','-C',str(root),'rev-parse','HEAD'],text=True).strip(),
        'sha256':{str(p.relative_to(root)):hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted((root/'scripts/ci/p20').rglob('*')) if p.is_file() and '__pycache__' not in p.parts}},sort_keys=True))
    load_portal(root)
    source=render(root)
    # Original path preserves ROOT resolution; immutable P19 file remains untouched.
    exec(compile(source,str(root/'scripts/ci/s2s/run_p19.py'),'exec'),{'__name__':'__main__','__file__':str(root/'scripts/ci/s2s/run_p19.py')})
