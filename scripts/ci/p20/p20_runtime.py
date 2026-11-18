"""Integration hooks. A missing Portal guard binding is a hard stop before any service."""
import hashlib
import importlib.util
import json
import re
import subprocess
from pathlib import Path
from service_targets import require, validate_compose


def load_portal(root):
    here = root/'scripts/ci/p20'
    binding = json.loads((here/'portal_guard_binding.json').read_text())
    require(binding.get('status') == 'BOUND_SOURCE_VERIFIED', 'PORTAL_GUARD_UNBOUND')
    require(bool(re.fullmatch('[0-9a-f]{40}', binding.get('commit') or '')), 'PORTAL_GUARD_PIN')
    guard_root = root/'pinned-portal'
    head = subprocess.check_output(['git','-C',str(guard_root),'rev-parse','HEAD'],text=True).strip()
    require(head == binding['commit'], 'PORTAL_GUARD_HEAD')
    require(bool(binding['guard_files_sha256']), 'PORTAL_GUARD_FILES')
    for name, digest in binding['guard_files_sha256'].items():
        path = (guard_root/name).resolve()
        require(path.is_relative_to(guard_root.resolve()), 'PORTAL_GUARD_PATH')
        require(hashlib.sha256(path.read_bytes()).hexdigest() == digest, 'PORTAL_GUARD_HASH')
    adapter = here/'portal_adapter.py'
    require(adapter.is_file() and hashlib.sha256(adapter.read_bytes()).hexdigest() == binding['adapter_sha256'], 'PORTAL_ADAPTER_HASH')
    spec = importlib.util.spec_from_file_location('p20_portal_adapter', adapter)
    module = importlib.util.module_from_spec(spec); spec.loader.exec_module(module)
    # Adapter only invokes the separate, pinned Portal guard; it must not duplicate its implementation.
    return module.Adapter(guard_root)


def configure(compose, folder, portal_root, portal):
    portal.configure(compose, folder, portal_root)
    validate_compose(compose)


def check_suite(result):
    require(result == {'tests':4, 'failures':0, 'skipped':0}, 'PORTAL_SUITE_INCOMPLETE')
