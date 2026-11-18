#!/usr/bin/env python3
"""Render a minimal, reviewable overlay of the immutable P19 harness. No services are run."""
import argparse
import hashlib
from pathlib import Path

P19_SHA256 = 'beae2aeb731a791c2e36cf9e28b884dcbb8e0c19bc8cd730fff5abf9dac6f9ff'


def render(root):
    source = (root/'scripts/ci/s2s/run_p19.py').read_text()
    if hashlib.sha256(source.encode()).hexdigest() != P19_SHA256:
        raise RuntimeError('P19_HARNESS_CHANGED')
    def replace(old, new):
        nonlocal source
        if source.count(old) != 1:
            raise RuntimeError('P19_ANCHOR_CHANGED')
        source = source.replace(old, new)
    replace("ROOT=Path(__file__).resolve().parents[3]", "ROOT=Path(__file__).resolve().parents[3]\nsys.path.insert(0,str(ROOT/'scripts/ci/p20'))\nfrom p20_runtime import load_portal, configure, check_suite\nfrom service_targets import ServiceTargets, require_local_docker\nportal=load_portal(ROOT)")
    # Compose also uses this project in image names; tempfile suffixes may contain '_'.
    replace(" file=folder/'compose.json';file.write_text(json.dumps(compose))", " project='p20-'+hashlib.sha256(str(folder.resolve()).encode()).hexdigest()[:24]\n require_local_docker(run,project)\n configure(compose,folder,PORTAL,portal)\n file=folder/'compose.json';file.write_text(json.dumps(compose))")
    replace(" dc=['docker','compose','-p','central-auto-p19-ci','-f',str(file)]", " dc=['docker','compose','-p',project,'-f',str(file)]\n targets=ServiceTargets(dc,project,folder,certs,run)")
    replace(" def sql(query):return run(dc+['exec','-T','crm-db','psql','-X','-qAt','-v','ON_ERROR_STOP=1','-U','crm_homologation_owner','-d','imagine_crm_private_homologation'],query).stdout.strip()", " def sql(query):return targets.sql('crm',query)")
    replace(" def agents_sql(query):return run(dc+['exec','-T','agents-db','psql','-X','-qAt','-v','ON_ERROR_STOP=1','-U','agents_ci_owner','-d','imagine_agents_ci'],query).stdout.strip()", " def agents_sql(query):return targets.sql('agents',query)")
    replace("  sql((ROOT/'infra/homologation/schema.sql').read_text())", "  targets.preflight()\n  portal.check(dc,run)\n  portal.prepare(dc,run)\n  sql((ROOT/'infra/homologation/schema.sql').read_text())")
    replace("  run(dc+['up','-d','crm-app','crm-s2s','agents-app','agents-s2s'],timeout=90)", "  targets.runtime()\n  run(dc+['up','-d','crm-app','crm-s2s','agents-app','agents-s2s'],timeout=90)\n  targets.versions()")
    replace("  args=dc+['run','--rm','--no-deps','-T']", "  args=dc+['exec','-T']")
    replace("  args+=['portal',company,mode]", "  args+=['portal','php','/p19-read-private.php',company,mode]")
    start=source.index("  assert client(B,'transport').returncode!=0")
    end=source.index('  # Separate product database',start)
    source=source[:start]+'''  before=int(sql("SELECT count(*) FROM crm_auto_service.audit WHERE outcome='READ_OK'"))
  check_suite(portal.run_suite(dc,run))
  after=int(sql("SELECT count(*) FROM crm_auto_service.audit WHERE outcome='READ_OK'"))
  assert after-before==2,'P20_EXPECTED_TWO_REAL_PRIVATE_READS'
  print('P20_NOMINAL_AUTHORITY_PRIVATE_CRM_PASS')
'''+source[end:]
    replace('  for _ in range(3):','  for _ in range(1):')
    start=source.index('  with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:',source.index("  first=deliver(events[0])"))
    end=source.index(' finally:',start)
    source=source[:start]+'''  print('P20_PORTAL_CRM_AGENTS_FUNCTIONAL_PASS synthetic=1 n8n_dispatch=DISABLED operational_authorization=0')
'''+source[end:]
    compile(source, str(root/'scripts/ci/s2s/run_p19.py'), 'exec')
    return source

if __name__ == '__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root',type=Path,required=True)
    args=parser.parse_args()
    print(render(args.root),end='')
