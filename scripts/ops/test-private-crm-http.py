#!/usr/bin/env python3
"""Real HTTP E2E on the isolated loopback stack. Never prints credentials or HTML."""
import base64
import csv
import concurrent.futures
from datetime import datetime, timedelta, timezone
import hashlib
import hmac
import http.client
from http.cookies import SimpleCookie
from io import StringIO
import json
import os
from pathlib import Path
import re
import secrets
import struct
import subprocess
import sys
import time
import urllib.parse
import uuid

ROOT=Path(__file__).resolve().parents[2]
PORT=int(os.environ.get('CRM_PRIVATE_TEST_PORT','18086'))
VAULT=Path(sys.argv[1])
if VAULT.stat().st_mode & 0o077:
    raise SystemExit('Fixture vault permissions are too broad.')
FIXTURES=json.loads(VAULT.read_text())
STEPS={}
CHECKS=0


def check(condition,label):
    global CHECKS
    CHECKS+=1
    if not condition:
        raise AssertionError(label)


def scenario(name):
    envfile=os.environ.get('CRM_PRIVATE_ENV_FILE')
    project=os.environ.get('CRM_PRIVATE_PROJECT','imagine-crm-private-journey')
    check(project in ('imagine-crm-private-journey','imagine-crm-journey-ci'),'isolated project required')
    check(envfile and Path(envfile).is_file(),'protected environment required')
    command=['docker','compose','--env-file',envfile,'-p',project,'-f',str(ROOT/'infra/homologation/compose.yaml'),
        'run','--rm','-T','tools','scripts/ops/seed-private-crm-fixtures.php',name]
    result=subprocess.run(command,capture_output=True,text=True)
    check(result.returncode==0,'private fixture operation failed: '+name)



def owner_sql(query):
    envfile=os.environ.get('CRM_PRIVATE_ENV_FILE')
    project=os.environ.get('CRM_PRIVATE_PROJECT')
    check(project=='imagine-crm-journey-ci','concurrent native probe requires disposable CI stack')
    command=['docker','compose','--env-file',envfile,'-p',project,'-f',str(ROOT/'infra/homologation/compose.yaml'),
        'exec','-T','postgres','psql','-X','-qAt','-v','ON_ERROR_STOP=1','-U','crm_homologation_owner','-d','imagine_crm_private_homologation','-c',query]
    result=subprocess.run(command,capture_output=True,text=True,timeout=15)
    check(result.returncode==0,'read-only concurrency probe failed; no SQL payload logged')
    return result.stdout.strip()


def wait_for_database_lock(role,event):
    for _ in range(30):
        if owner_sql("SELECT count(*) FROM pg_stat_activity WHERE usename='"+role+"' AND wait_event='"+event+"'")!='0':return
        time.sleep(.05)
    raise AssertionError('expected concurrent database lock was not observed')


def totp(secret,offset=0):
    step=int(time.time())//30+offset
    digest=hmac.new(base64.b32decode(secret),struct.pack('>Q',step),hashlib.sha1).digest()
    start=digest[-1]&15
    value=struct.unpack('>I',digest[start:start+4])[0]&0x7fffffff
    return str(value%1000000).zfill(6)


def next_factor(account):
    key=account['login']
    current=int(time.time())//30
    step=max(current,STEPS.get(key,-1)+1)
    if step>current+1:
        delay=max(0,(step-1)*30-time.time()+0.2)
        time.sleep(delay)
    STEPS[key]=step
    return totp(account['secret'],step-int(time.time())//30)


class Client:
    def __init__(self):
        self.cookies={}
        self.csrf=None
        self.last=''

    def request(self,path,data=None,expected=200,headers=None,body=None):
        check(path.startswith('/') and not path.startswith('//'),'local path only')
        conn=http.client.HTTPConnection('127.0.0.1',PORT,timeout=20)
        fields={'Host':'crm-dev.imaginecomunica.com.br','Accept':'text/html'}
        if self.cookies:
            fields['Cookie']='; '.join(k+'='+v for k,v in self.cookies.items())
        if data is not None:
            body=urllib.parse.urlencode(data,doseq=True)
            fields['Content-Type']='application/x-www-form-urlencoded'
        if headers: fields.update(headers)
        method='POST' if data is not None or body is not None else 'GET'
        conn.request(method,path,body=body,headers=fields)
        response=conn.getresponse()
        content=response.read().decode('utf-8',errors='replace')
        status=response.status
        location=response.getheader('Location','')
        for name,value in response.getheaders():
            if name.lower()=='set-cookie':
                parsed=SimpleCookie();parsed.load(value)
                for key,morsel in parsed.items():
                    if morsel['max-age']=='0': self.cookies.pop(key,None)
                    else: self.cookies[key]=morsel.value
        conn.close()
        self.last=content
        token=re.search(r'name="_token" value="([^"]+)"',content)
        if token: self.csrf=token.group(1)
        allowed=(expected,) if isinstance(expected,int) else expected
        check(status in allowed,f'HTTP {status}, expected {allowed}, at {method} {path}')
        if status==200 and path!='/exportar':
            check('noindex' in response.getheader('X-Robots-Tag',''),'noindex missing')
            check('no-store' in response.getheader('Cache-Control',''),'no-store missing')
        return content,location

    def get(self,path,expected=200):
        return self.request(path,expected=expected)[0]

    def post(self,path,data,expected=303,csrf=True):
        payload=dict(data)
        if csrf: payload['_token']=self.csrf
        return self.request(path,payload,expected)

    def login(self,account,recovery=None,dashboard_expected=200):
        self.get('/login')
        _,location=self.post('/login',{'login':account['login'],'password':account['password']})
        check(location.endswith('/mfa'),'password did not lead to MFA')
        self.get('/mfa')
        values={'code':recovery if recovery else next_factor(account)}
        if recovery: values['recovery']='1'
        _,location=self.post('/mfa',values)
        check(location.endswith('/admin' if account.get('admin') else '/painel'),'MFA route mismatch')
        return self.get('/admin' if account.get('admin') else '/painel',expected=dashboard_expected)


def leads(client):
    page=client.get('/painel?section=leads')
    return re.findall(r'data-lead-id="([a-f0-9-]+)"',page)


def version(client,identity):
    page=client.get('/painel?section=leads')
    found=re.search(r'data-lead-id="'+re.escape(identity)+r'".*?name="version" value="(\d+)"',page,re.S)
    check(found is not None,'lead/version absent')
    return found.group(1)


def signup(client,login,label,document,password):
    client.get('/cadastro?plan=essential')
    return client.post('/cadastro',{'login':login,'password':password,'company':label,'cnpj':document,
        'email':login+'@example.test','phone':'+5500000000000','terms':'simulated','plan':'essential'})


def upload_csv(client,csv_text,expected=303):
    boundary='crm-private-'+secrets.token_hex(8)
    body=f'--{boundary}\r\nContent-Disposition: form-data; name="_token"\r\n\r\n{client.csrf}\r\n--{boundary}\r\nContent-Disposition: form-data; name="csv"; filename="fictional.csv"\r\nContent-Type: text/csv\r\n\r\n{csv_text}\r\n--{boundary}--\r\n'
    client.request('/importar',expected=expected,body=body,headers={'Content-Type':'multipart/form-data; boundary='+boundary})



def assert_agents_no_go(client, audience):
    """F249: real private HTTP kernel has no synthetic or operational Agents API."""
    body=json.dumps({
        'contract':'SYNTHETIC_CRM_AIA_READ_V1',
        'request_ref':'SYNTHETIC_F249_REJECT',
        'operation':'crm.lead.read_minimal',
        'tenant_ref':'SYNTHETIC_TENANT_A',
        'principal_ref':'SYNTHETIC_F249_PRINCIPAL',
        'resource_ref':'SYNTHETIC_F249_LEAD',
        'purpose_ref':'SYNTHETIC_F249_PURPOSE',
        'fields':['ref','stage'],
    },separators=(',',':'))
    paths=(
        '/api/aia/lead/read',
        '/api/agents/op01/leads',
        '/api/crm/agents/leads/SYNTHETIC_F249_LEAD',
        '/internal/aia/op01/leads',
        '/__synthetic_f248_not_registered',
    )
    for path in paths:
        # Even an authenticated customer/admin with a fake service proof
        # must never gain access to an unapproved S2S API.
        html,_=client.request(path,body=body,expected=404,headers={
            'Content-Type':'application/json',
            'X-Aia-Claims':'{}',
            'X-Aia-Signature':'SYNTHETIC_NOT_A_SIGNATURE',
        })
        for forbidden in (
            'SYNTHETIC_F249_LEAD',
            'SYNTHETIC_F249_PRINCIPAL',
            'SYNTHETIC_NOT_A_SIGNATURE',
            'access_token',
            'READ_SYNTHETIC_ONLY',
        ):
            check(forbidden not in html,
                  'F249 forbidden service/lead content in denied HTTP response')
        client.get(path,expected=404)
    print('PASS F249 private Agents HTTP NO-GO audience='+audience)



def assert_no_agents_impersonation_of_human(client, route, actor):
    """F250: service claims/key hints cannot turn into human identity/MFA."""
    for name,value in (
        ('X-Aia-Claims','SYNTHETIC_FORGED_CLAIMS'),
        ('X-Aia-Signature','SYNTHETIC_FORGED_SIGNATURE'),
        ('X-Aia-Key-Id','SYNTHETIC_F250_KEY'),
        ('X-Service-Identity','SYNTHETIC_AGENT_SERVICE'),
    ):
        body,_=client.request(route,expected=400,headers={name:value})
        check('SYNTHETIC_F250_KEY' not in body
              and 'SYNTHETIC_FORGED_SIGNATURE' not in body
              and 'access_token' not in body,
              'F250 service identity marker leaked to human route')
    print('PASS F250 human/service header separation actor='+actor)


def main():
    scenario('clear-cache')
    a=Client(); b=Client(); admin=Client()
    home=a.get('/')
    assert_agents_no_go(a,'guest')
    assert_no_agents_impersonation_of_human(a,'/login','guest')
    check('R$ 69,00' in home and 'R$ 89,00' in home,'commercial plan references missing')
    for language,headline,method,pillar in [
        ('en','More control.','Visits → Leads → Customers','ATTRACT'),
        ('es','Más control.','Visitas → Leads → Clientes','ATRACCIÓN'),
        ('pt','Mais controle.','Visitas → Leads → Clientes','ATRAÇÃO'),
    ]:
        localized=a.get('/?lang='+language)
        check(headline in localized,'locale missing')
        check('data-home-section="imagine-methodology"' in localized,
              'Imagine methodology section missing')
        check(method in localized and pillar in localized,
              'Imagine methodology translations/pillars missing')
        check('href="#metodologia"' in localized,
              'Imagine methodology navigation missing')

    # Anonymous Imagine 3-pillar calculator: no contact capture, session
    # provisioning, outbound providers or probability promise.
    scenario_html=a.get('/?lang=pt&visits=2500&lead_rate=8&client_rate=13')
    check('data-home-section="imagine-scenario"' in scenario_html,
          'Imagine commercial calculator missing')
    check('data-scenario-visits="2500"' in scenario_html,
          'weekly visit scenario not reflected')
    check('data-scenario-leads="200"' in scenario_html,
          'visit-to-lead scenario calculation incorrect')
    check('data-scenario-clients="26"' in scenario_html,
          'lead-to-customer scenario calculation incorrect')
    check('data-scenario-form="imagine"' in scenario_html,
          'anonymous estimate form missing')
    for lang in ('pt','en','es'):
        localized=a.get('/?lang='+lang+'&visits=1000&lead_rate=4&client_rate=15')
        check('data-scenario-leads="40"' in localized and
              'data-scenario-clients="6"' in localized,
              'localized scenario result missing')
    malformed=a.get('/?lang=pt&visits=-1&lead_rate=10000&client_rate=1e2')
    check('data-scenario-visits="1000"' in malformed and
          'data-scenario-leads="40"' in malformed and
          'data-scenario-clients="6"' in malformed,
          'invalid scenario input must use safe defaults')
    print('PASS Imagine Visits → Leads → Customers PII-free scenario PT/EN/ES')

    initial=a.get('/cadastro')
    document=re.search(r'<option selected>([0-9]{14})</option>',initial)
    if not document: document=re.search(r'<option[^>]*>([0-9]{14})</option>',initial)
    check(document is not None,'fictional document absent')
    suffix=secrets.token_hex(4)
    primary={'login':'lab-journey-'+suffix,'password':secrets.token_hex(16),'label':'EMPRESA_TESTE_JOURNEY_'+suffix.upper()}
    signup(a,primary['login'],primary['label'],document.group(1),primary['password'])
    page=a.get('/cadastro/codigo')
    code=re.search(r'data-test="otp-code">\s*([0-9]{6})',page).group(1)
    wrong='000000' if code!='000000' else '111111'
    a.post('/cadastro/verificar',{'code':wrong})
    a.get('/cadastro/codigo')
    a.post('/cadastro/verificar',{'code':code})
    page=a.get('/cadastro/mfa')
    primary['secret']=re.search(r'data-test="setup-secret">\s*([A-Z2-7]{32})',page).group(1)
    setup_code=totp(primary['secret']); STEPS[primary['login']]=int(time.time())//30
    page,_=a.post('/cadastro/concluir',{'code':setup_code},expected=200)
    primary['recovery']=re.findall(r'<div>([a-f0-9]{24})</div>',page)
    check(len(primary['recovery'])==5,'single-display recovery codes missing')
    a.get('/login')
    a.post('/login',{'login':primary['login'],'password':'incorrect-password'})
    time.sleep(1.2)
    check(primary['label'] in a.login(primary),'new account dashboard missing')
    print('PASS commercial PT/EN/ES → signup → private OTP → MFA → persisted 14-day trial → login')

    a.post('/leads',{'title':'LEAD_TESTE_UNSAFE','value_cents':1},expected=419,csrf=False)
    a.get('/painel?section=leads')
    title='LEAD_TESTE_JOURNEY_'+suffix.upper()
    a.post('/leads',{'title':title,'value_cents':150000})
    ids=leads(a);check(len(ids)==1,'lead not persisted')
    lead=ids[0]
    a.get('/painel?section=automation')
    a.post('/preferencias',{'automation_version':0,'language':'pt','rules[]':['followup_qualified','followup_proposal']})
    page=a.get('/painel?section=automation')
    check('value="1"' in page and 'Central de automações' in page,'versioned automation settings missing')
    a.post('/preferencias',{'automation_version':0,'language':'pt'},expected=409)
    a.post('/preferencias',{'automation_version':1,'language':'pt'})
    check('Pausada' in a.get('/painel?section=automation'),'pause not visible')
    a.post('/preferencias',{'automation_version':2,'language':'pt','rules[]':['followup_qualified','followup_proposal']})
    a.post('/preferencias',{'automation_version':3,'language':'pt','rules[]':['billing.charge']},expected=303)
    rejected=a.get('/painel?section=automation')
    check('name="automation_version" value="3"' in rejected,'rejected sensitive rule changed stored version')
    a.get('/painel?section=kanban')
    a.post('/leads/'+lead,{'version':1,'stage':'qualified'})
    a.post('/leads/'+lead,{'version':1,'stage':'proposal'},expected=409)
    check(version(a,lead)=='2','stale write mutated version')
    tasks=a.get('/painel?section=tasks')
    check('TASK_TESTE_AUTO_QUALIFIED' in tasks,'automation not persisted')
    a.post('/tarefas',{'lead':lead,'title':'TASK_TESTE_MANUAL','due':(datetime.now(timezone.utc)+timedelta(days=1)).strftime('%Y-%m-%d')})
    tasks=a.get('/painel?section=tasks')
    task_rows=re.findall(r'<tr data-task-id="([^"]+)">(.*?)</tr>',tasks,re.S)
    manual=[identity for identity,body in task_rows if 'TASK_TESTE_MANUAL' in body][0]
    a.post('/tarefas/'+manual,{'version':1})
    check('done' in a.get('/painel?section=tasks'),'task completion missing')
    check('R$ 1.500,00' in a.get('/painel?section=reports'),'report total missing')
    exported=a.get('/exportar');check(title in exported and 'value_cents' in exported,'CSV export missing')
    boundary='crm-private-'+secrets.token_hex(8)
    csv_text='title,value_cents\nLEAD_TESTE_CSV_A,100\nLEAD_TESTE_CSV_B,200\n'
    body=f'--{boundary}\r\nContent-Disposition: form-data; name="_token"\r\n\r\n{a.csrf}\r\n--{boundary}\r\nContent-Disposition: form-data; name="csv"; filename="fictional.csv"\r\nContent-Type: text/csv\r\n\r\n{csv_text}\r\n--{boundary}--\r\n'
    a.request('/importar',expected=303,body=body,headers={'Content-Type':'multipart/form-data; boundary='+boundary})
    check(len(leads(a))==3,'CSV import not persisted')
    # UI configuration is interpreted by the native planner and persisted as a durable run.
    a.get('/painel?section=automation')
    a.post('/preferencias',{'automation_version':3,'delay_seconds':1,'language':'pt','rules[]':['followup_qualified','followup_proposal']})
    a.get('/painel?section=kanban');a.post('/leads/'+lead,{'version':2,'stage':'proposal'})
    page=a.get('/painel?section=automation')
    check('pending' in page,'scheduled native run missing')
    time.sleep(1.1)
    a.post('/automacoes/processar',{})
    check('TASK_TESTE_AUTO_PROPOSAL' in a.get('/painel?section=tasks'),'native scheduled effect missing')
    before_tasks=len(re.findall(r'data-task-id=',a.get('/painel?section=tasks')))
    a.post('/automacoes/processar',{})
    check(len(re.findall(r'data-task-id=',a.get('/painel?section=tasks')))==before_tasks,'duplicate native effect')
    a.post('/preferencias',{'automation_version':4,'delay_seconds':3600,'language':'pt','rules[]':['followup_qualified','followup_proposal']})
    other=[value for value in leads(a) if value!=lead][0]
    a.get('/painel?section=kanban');a.post('/leads/'+other,{'version':1,'stage':'qualified'})
    page=a.get('/painel?section=automation')
    scheduled=[identity for identity,body in re.findall(r'<tr data-automation-id="([^"]+)">(.*?)</tr>',page,re.S) if 'pending' in body]
    check(len(scheduled)==1,'pending cancellation target missing')
    a.post('/automacoes/'+scheduled[0]+'/cancelar',{})
    a.post('/automacoes/'+scheduled[0]+'/cancelar',{})
    check('cancelled' in a.get('/painel?section=automation'),'cancel not persisted')
    a.get('/painel?section=kanban');a.post('/leads/'+other,{'version':2,'stage':'proposal'})
    a.post('/preferencias',{'automation_version':5,'delay_seconds':0,'language':'pt'})
    page=a.get('/painel?section=automation')
    check(not any('pending' in body for _,body in re.findall(r'<tr data-automation-id="([^"]+)">(.*?)</tr>',page,re.S)),'pause did not cancel pending runs')
    # Real resource failure: fill the disposable native task table, then recover after server backoff.
    a.post('/preferencias',{'automation_version':6,'delay_seconds':0,'language':'pt','rules[]':['followup_qualified','followup_proposal']})
    final_lead=[value for value in leads(a) if value not in [lead,other]][0]
    scenario('automation-fill-capacity')
    a.get('/painel?section=kanban');a.post('/leads/'+final_lead,{'version':1,'stage':'qualified'})
    pending=a.get('/painel?section=automation')
    capacity_runs=[identity for identity,body in re.findall(r'<tr data-automation-id="([^"]+)">(.*?)</tr>',pending,re.S) if 'pending' in body]
    check(len(capacity_runs)==1,'capacity failure not retained for retry')
    scenario('automation-free-capacity')
    time.sleep(30.2)
    a.post('/automacoes/processar',{})
    recovered_tasks=a.get('/painel?section=tasks')
    check(recovered_tasks.count('data-task-id="'+capacity_runs[0]+'"')==1,'retry recovery did not create exactly the expected native task')
    # A new authenticated session cannot resurrect the revoked causal session of a scheduled run.
    a.post('/preferencias',{'automation_version':7,'delay_seconds':1,'language':'pt','rules[]':['followup_qualified','followup_proposal']})
    a.get('/painel?section=kanban');a.post('/leads/'+final_lead,{'version':2,'stage':'proposal'})
    before_revoke_tasks=len(re.findall(r'data-task-id=',a.get('/painel?section=tasks')))
    a.post('/logout',{})
    a.login(primary,recovery=primary['recovery'][4])
    time.sleep(1.1)
    a.post('/automacoes/processar',{})
    check(len(re.findall(r'data-task-id=',a.get('/painel?section=tasks')))==before_revoke_tasks,'revoked causal session produced an effect')
    page=a.get('/painel?section=automation')
    check(not any('pending' in body for _,body in re.findall(r'<tr data-automation-id="([^"]+)">(.*?)</tr>',page,re.S)),'revoked source run not cancelled')
    automation_state=json.loads(a.get('/automacoes/estado'))
    check(automation_state['schema']=='CRM_AUTOMATION_STATE_V1' and automation_state['source_kind']=='synthetic_private_database','native read contract missing')
    check(automation_state['counts']['completed']>=3 and automation_state['counts']['cancelled']>=3,'read contract not backed by persisted execution results')
    check(automation_state['ai_provider_calls']==0 and automation_state['operational_authorization'] is False,'read contract invented spend or authority')
    check(all(set(item)=={'action','at'} for item in automation_state['events']),'read contract exposes private identifiers')
    a.get('/automacoes/estado?company_id=forged',expected=422)
    print('PASS CENTRAL-AUTO-P1 UI → version → planner → PostgreSQL schedule → native task → dedupe → cancel → pause; AI calls zero by closed action catalog')
    print('PASS lead create/edit protections → Kanban versioning → automation → tasks → reports → CSV')

    a.get('/painel?section=funnels')
    a.post('/funis',{'title':'FUNIL_TESTE_SEGUNDO'})
    check(len(re.findall('data-funnel-id=',a.get('/painel?section=funnels')))==2,'two funnels not persisted')
    a.post('/funis',{'title':'FUNIL_TESTE_TERCEIRO'},expected=409)
    a.get('/painel?section=team')
    page,_=a.post('/equipe/convidar',{},expected=200)
    invitation=re.search(r'data-test="invitation-code">([^<]+)',page).group(1).strip()
    member=Client()
    member.get('/convite')
    teammate={'login':'lab-member-'+suffix,'password':secrets.token_hex(16)}
    member.post('/convite',dict(teammate,invitation=invitation,email=teammate['login']+'@example.test',terms='simulated'))
    page=member.get('/cadastro/codigo')
    code=re.search(r'data-test="otp-code">\s*([0-9]{6})',page).group(1)
    member.post('/cadastro/verificar',{'code':code})
    page=member.get('/cadastro/mfa')
    teammate['secret']=re.search(r'data-test="setup-secret">\s*([A-Z2-7]{32})',page).group(1)
    STEPS[teammate['login']]=int(time.time())//30
    page,_=member.post('/cadastro/concluir',{'code':totp(teammate['secret'])},expected=200)
    teammate['recovery']=re.findall(r'<div>([a-f0-9]{24})</div>',page)
    check(primary['label'] in member.login(teammate),'invited member cannot see same company')
    check(title in member.get('/painel?section=leads'),'invited member cannot see same company leads')
    member.get('/automacoes/estado',expected=403)
    member.post('/preferencias',{'automation_version':8,'language':'pt'},expected=403)
    member.post('/funis',{'title':'FUNIL_TESTE_DENIED'},expected=403)
    member.post('/equipe/convidar',{},expected=403)
    member.get('/admin',expected=403)
    a.get('/painel?section=team');a.post('/equipe/convidar',{},expected=409)
    FIXTURES['member']=teammate
    VAULT.write_text(json.dumps(FIXTURES));VAULT.chmod(0o600)
    print('PASS two-funnel quota → scoped single-use invitation → second user → owner/member permission boundaries')

    for target in ['/painel?tenant_id=aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','/painel?role=admin']:
        a.get(target,expected=400)
    a.request('/painel',expected=400,headers={'X-Role':'admin'})
    a.get('/admin',expected=403)
    b.login(FIXTURES['clientB'])
    check(title not in b.get('/painel?section=leads'),'cross-tenant read leak')
    bSummary=b.get('/painel?section=reports')
    check('data-report-methodology="imagine"' in bSummary,
          'F255 second tenant cannot open its own funnel report')
    check('data-report-leads="0"' in bSummary and
          'data-report-clients="0"' in bSummary,
          'F255 first tenant lead or won aggregates leaked into second tenant')
    check('data-report-visits="unavailable"' in bSummary,
          'F255 second tenant has invented visitor analytics')
    bPipelines=re.findall(
        r'<tr data-report-funnel-id="([^"]+)" data-report-funnel-leads="(\d+)" data-report-funnel-won="(\d+)" data-report-funnel-rate="([^"]+)">',
        bSummary)
    check(len(bPipelines)>=1 and
          all(int(row[1])==0 and int(row[2])==0 for row in bPipelines),
          'F255 foreign leads or won status visible in other tenant pipeline')
    b.post('/leads/'+lead,{'version':2,'stage':'proposal'},expected=404)
    other_state=json.loads(b.get('/automacoes/estado'))
    check(sum(other_state['counts'].values())==0 and other_state['company_id']!=automation_state['company_id'],'read contract crosses tenant boundaries')
    b.post('/automacoes/'+scheduled[0]+'/cancelar',{},expected=404)
    check(scheduled[0] not in b.get('/painel?section=automation'),'foreign native run exposed')
    b.post('/automacoes/processar',{})
    b.post('/tarefas',{'lead':lead,'title':'TASK_TESTE_CROSS','due':'2026-12-01'},expected=404)
    print('PASS CSRF → client/admin role denial → forged tenant/role rejection → two-company isolation')
    assert_agents_no_go(a,'authenticated-client')
    assert_no_agents_impersonation_of_human(a,'/painel','client')

    a.get('/painel?section=kanban')
    # Proposal was already reached by the integrated native-engine scenario above.
    a.post('/leads/'+lead,{'version':3,'stage':'won'})
    a.post('/leads/'+lead,{'version':4,'stage':'lost'},expected=422)
    other=next(identity for identity in leads(a) if identity!=lead)
    other_version=int(version(a,other))
    a.post('/leads/'+other,{'version':other_version,'stage':'lost'})
    a.post('/leads/'+other,{'version':other_version+1,'stage':'qualified'},expected=422)
    check('TASK_TESTE_AUTO_PROPOSAL' in a.get('/painel?section=tasks'),'proposal automation missing')
    report=a.get('/painel?section=reports')
    check('data-report-methodology="imagine"' in report,
          'Imagine 3-pillar report missing')
    check('data-report-visits="unavailable"' in report,
          'traffic visits must never be fabricated')
    check('data-report-leads="3"' in report and
          'data-report-clients="1"' in report,
          'report must reflect tenant-scoped persisted leads and won deals')
    check('data-report-conversion="33.3"' in report,
          'won-per-lead ratio must match tenant-scoped won leads')
    check('não contratos, pagamentos ou clientes reais' in report,
          'won deals must not be presented as real paying customers')
    pipeline_rows=re.findall(
        r'<tr data-report-funnel-id="([^"]+)" data-report-funnel-leads="(\d+)" data-report-funnel-won="(\d+)" data-report-funnel-rate="([^"]+)">',
        report)
    check(len(pipeline_rows)==2,'two scoped pipeline report rows expected')
    check(sorted(int(row[1]) for row in pipeline_rows)==[0,3],
          'empty and populated pipeline counts wrong')
    check(sorted(int(row[2]) for row in pipeline_rows)==[0,1],
          'won count across pipelines wrong')
    check(sorted(float(row[3]) for row in pipeline_rows)==[0.0,33.3],
          'pipeline conversion must be scoped and avoid zero division')
    bSummaryAfterWon=b.get('/painel?section=reports')
    check('data-report-leads="0"' in bSummaryAfterWon and
          'data-report-clients="0"' in bSummaryAfterWon,
          'F255 foreign won deal leaked across tenants after stage transition')
    check('data-report-conversion="0"' in bSummaryAfterWon,
          'F255 other tenant conversion inflated by foreign won deals')
    check(title not in b.get('/exportar'),
          'F255 cross-tenant lead appeared in another company CSV export')
    print('PASS F255 independent-tenant funnel, conversion and CSV isolation before/after won')
    print('PASS proposal → won/lost → terminal state protection → proposal follow-up task')
    exported=a.get('/exportar')
    check(',won,' in exported and ',lost,' in exported,'exported sales stages missing')
    before_import=len(leads(a))
    upload_csv(a,exported)
    check(len(leads(a))==before_import*2,'exported CSV cannot be reimported')
    upload_csv(a,'title,stage,value_cents\nLEAD_TESTE_ATOMIC,new,1\n=INVALID,new,1\n')
    check(len(leads(a))==before_import*2,'invalid CSV partially committed')
    print('PASS exported CSV round trip preserves stages → invalid-row import is atomic')

    a.get('/painel?section=billing')
    for state in ['approved','refused','cancelled']:
        key=str(uuid.uuid4())
        a.post('/pedidos',{'plan':'essential','method':'pix','key':key})
        page=a.get('/painel?section=billing')
        before=len(re.findall(r'data-order-id=',page))
        a.post('/pedidos',{'plan':'essential','method':'pix','key':key})
        page=a.get('/painel?section=billing')
        check(len(re.findall(r'data-order-id=',page))==before,'duplicate order created')
        pending=[identity for identity,row in re.findall(r'<tr data-order-id="([^"]+)">(.*?)</tr>',page,re.S) if 'name="state" value="approved"' in row]
        check(len(pending)==1,'pending order absent or ambiguous')
        order=pending[0]
        a.post('/pedidos/'+order+'/simular',{'state':state,'version':1})
        a.post('/pedidos/'+order+'/simular',{'state':state,'version':1})
        b.get('/painel?section=billing')
        b.post('/pedidos/'+order+'/simular',{'state':'approved','version':1},expected=404)
    billing=a.get('/painel?section=billing')
    check(all(state in billing for state in ('approved','refused','cancelled')),'payment state missing')
    print('PASS simulated pending/approved/refused/cancelled payments → order/event idempotency → tenant binding')

    scenario('clear-cache')
    for i in range(20-len(leads(a))):
        a.get('/painel?section=leads')
        a.post('/leads',{'title':f'LEAD_TESTE_QUOTA_{i}','value_cents':0})
    check(len(leads(a))==20,'20-lead quota setup failed')
    a.post('/leads',{'title':'LEAD_TESTE_OVER_LIMIT','value_cents':0},expected=409)
    old_cookie=dict(a.cookies)
    a.get('/painel');a.post('/logout',{})
    stale=Client();stale.cookies=old_cookie
    _,secure_location=stale.request('/painel',expected=303,headers={'X-Forwarded-Proto':'https'})
    check(secure_location=='https://crm-dev.imaginecomunica.com.br/login','TLS proxy redirect downgraded to HTTP')
    a.login(primary,recovery=primary['recovery'][0])
    check(len(leads(a))==20,'data did not survive logout/login')
    new_password=secrets.token_hex(16)
    recovery_client=Client();recovery_client.get('/recuperar')
    page,_=recovery_client.post('/recuperar',{'login':primary['login'],'code':primary['recovery'][1],'password':new_password},expected=200)
    fresh_codes=re.findall(r'<div>([a-f0-9]{24})</div>',page)
    check(len(fresh_codes)==5,'recovery rotation missing')
    a.get('/painel',expected=303)
    primary['password']=new_password
    a.login(primary,recovery=fresh_codes[0])
    check(len(leads(a))==20,'data lost after recovery/session revocation')
    recovery_client.get('/recuperar')
    recovery_client.post('/recuperar',{'login':primary['login'],'code':primary['recovery'][1],'password':new_password})
    print('PASS quota 20/21 → logout/relogin persistence → one-use recovery → credential version/session revocation')

    scenario('clear-cache')
    administrator=dict(FIXTURES['admin'],admin=True)
    page=admin.login(administrator)
    assert_agents_no_go(admin,'authenticated-global-admin')
    assert_no_agents_impersonation_of_human(admin,'/admin','administrator')
    rows=re.findall(r'<tr data-account-id="([^"]+)">(.*?)</tr>',page,re.S)
    identity=[identity for identity,body in rows if primary['label'] in body and 'data-team-role="owner"' in body][0]
    FIXTURES['member']['identity']=[identity for identity,body in rows if primary['label'] in body and 'data-team-role="member"' in body][0]
    FIXTURES['journey']=dict(primary,identity=identity)
    VAULT.write_text(json.dumps(FIXTURES));VAULT.chmod(0o600)
    # Real nominal client/admin race. A DB barrier holds the tenant lock while IAM locks are live.
    a.get('/painel?section=automation')
    settings_version=int(re.search(r'name="automation_version" value="(\d+)"',a.last).group(1))
    a.post('/preferencias',{'automation_version':settings_version,'delay_seconds':1,'language':'pt','rules[]':['followup_qualified']})
    race_lead=next(value for value in leads(a) if value not in [lead,other,final_lead])
    a.get('/painel?section=kanban');a.post('/leads/'+race_lead,{'version':1,'stage':'qualified'})
    page=a.get('/painel?section=automation')
    race_runs=[rid for rid,body in re.findall(r'<tr data-automation-id="([^"]+)">(.*?)</tr>',page,re.S) if 'pending' in body]
    check(len(race_runs)==1,'race requires one scheduled native effect')
    time.sleep(1.1)
    company=str(uuid.UUID(automation_state['company_id']))
    envfile=os.environ['CRM_PRIVATE_ENV_FILE'];project=os.environ['CRM_PRIVATE_PROJECT']
    check(project=='imagine-crm-journey-ci','race barrier is CI-only')
    command=['docker','compose','--env-file',envfile,'-p',project,'-f',str(ROOT/'infra/homologation/compose.yaml'),
        'exec','-T','postgres','psql','-X','-qAt','-v','ON_ERROR_STOP=1','-U','crm_homologation_owner','-d','imagine_crm_private_homologation','-c',
        "BEGIN; SELECT pg_advisory_xact_lock(hashtextextended('crm_private_quota|"+company+"',0)); SELECT pg_sleep(8); COMMIT;"]
    barrier=subprocess.Popen(command,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    try:
        wait_for_database_lock('crm_homologation_owner','PgSleep')
        with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
            processing=pool.submit(a.post,'/automacoes/processar',{})
            wait_for_database_lock('crm_homologation_app','advisory')
            revocation=pool.submit(admin.post,'/admin/contas/'+identity,{'status':'suspended'})
            wait_for_database_lock('crm_homologation_operator','transactionid')
            check(barrier.wait(timeout=12)==0,'native transaction barrier failed')
            processing.result(timeout=15);revocation.result(timeout=15)
        check(owner_sql("SELECT state FROM crm_private_automation_runs WHERE id='"+str(uuid.UUID(race_runs[0]))+"'")=='completed','admitted native operation was cut by revocation')
        check(owner_sql("SELECT count(*) FROM crm_private_tasks WHERE id='"+str(uuid.UUID(race_runs[0]))+"'")=='1','concurrent operation duplicated native task')
        print('CENTRAL_AUTO_P1_CONCURRENT_REVOKE_PASS nominal_client_admin=1 admission_commits_before_revocation=1 effect_count=1')
    finally:
        if barrier.poll() is None:barrier.terminate();barrier.wait(timeout=5)

    a.get('/painel',expected=303)
    admin.get('/admin');admin.post('/admin/contas/'+identity,{'status':'active'})
    a.get('/painel',expected=303)
    a.login(primary,recovery=fresh_codes[1])
    check(len(leads(a))==20,'resume lost data or did not require fresh authentication')
    scenario('expire-clientB');b.get('/painel',expected=303)
    scenario('revoke-membershipB')
    b.login(FIXTURES['clientB'],recovery=FIXTURES['clientB']['recovery'][0],dashboard_expected=403)
    scenario('restore-membershipB')
    b.get('/painel')
    print('PASS administrator metadata → suspend/revoke → resume/fresh login → session expiry')
    print(f'PRIVATE CRM HTTP PASS: {CHECKS} checks; loopback staging, fictional data, no real messages/payments.')


if __name__=='__main__':
    if len(sys.argv)>2 and sys.argv[2]=='review-access':
        scenario('clear-cache')
        for key in ['client','admin']:
            reviewer=Client();account=dict(FIXTURES[key],admin=key=='admin')
            reviewer.login(account)
            if key=='client': reviewer.get('/admin',expected=403)
            reviewer.post('/logout',{})
            reviewer.get('/admin' if key=='admin' else '/painel',expected=303)
        print(f'PRIVATE REVIEW ACCESS PASS: {CHECKS} checks; client/admin password+MFA and logout, no credentials displayed.')
    else:
        main()
