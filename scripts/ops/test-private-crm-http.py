#!/usr/bin/env python3
"""Real HTTP E2E on the isolated loopback stack. Never prints credentials or HTML."""
import base64
import csv
import concurrent.futures
from contextlib import contextmanager
from datetime import datetime, timedelta, timezone
import hashlib
import hmac
import http.client
from http.cookies import SimpleCookie
from html import unescape
from html.parser import HTMLParser
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
        'run','--rm','-T']
    if name in ('automation-deny-enqueue','automation-restore-enqueue'):
        check(project=='imagine-crm-journey-ci','enqueue fault requires exact disposable CI project')
        check(os.environ.get('CRM_PRIVATE_CI_FAULT_PROFILE')=='native-followup-enqueue-v1',
              'explicit CI enqueue fault opt-in required')
        command+=['-e','CRM_PRIVATE_CI_PROJECT='+project,
                  '-e','CRM_PRIVATE_CI_FAULT_PROFILE=native-followup-enqueue-v1']
    if name in ('automation-deny-causal-logout','automation-restore-causal-logout'):
        check(project=='imagine-crm-journey-ci','causal session fault requires exact disposable CI project')
        check(os.environ.get('CRM_PRIVATE_CI_CAUSAL_SESSION_PROFILE')=='native-causal-session-hold-v1',
              'explicit CI causal session fault opt-in required')
        command+=['-e','CRM_PRIVATE_CI_PROJECT='+project,
                  '-e','CRM_PRIVATE_CI_CAUSAL_SESSION_PROFILE=native-causal-session-hold-v1']
    command+=['tools','scripts/ops/seed-private-crm-fixtures.php',name]
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


def native_enqueue_denials():
    """Count sanitized SQL permission errors from the real native engine only."""
    envfile=os.environ.get('CRM_PRIVATE_ENV_FILE')
    project=os.environ.get('CRM_PRIVATE_PROJECT')
    check(project=='imagine-crm-journey-ci','enqueue error witness requires disposable CI stack')
    command=['docker','compose','--env-file',envfile,'-p',project,'-f',str(ROOT/'infra/homologation/compose.yaml'),
        'logs','--no-color','--no-log-prefix','app']
    result=subprocess.run(command,capture_output=True,text=True,timeout=15)
    check(result.returncode==0,'native enqueue error witness unavailable; no log content printed')
    count=0
    for line in (result.stdout+'\n'+result.stderr).splitlines():
        _,marker,context=line.partition('CRM_PRIVATE_REQUEST_UNAVAILABLE ')
        if not marker: continue
        try:
            data,_=json.JSONDecoder().raw_decode(context)
        except (ValueError,TypeError):
            continue
        if not isinstance(data,dict): continue
        if any(cause.get('type')=='Illuminate\\Database\\QueryException'
               and cause.get('code')=='42501'
               and re.fullmatch(r'NativeFollowupEngine\.php:[1-9][0-9]*',cause.get('origin',''))
               for cause in data.get('causes',[]) if isinstance(cause,dict)):
            count+=1
    return count


@contextmanager
def denied_native_enqueue():
    """Temporary INSERT denial in the closed, disposable CI stack only."""
    check(os.environ.get('CRM_PRIVATE_PROJECT')=='imagine-crm-journey-ci'
          and os.environ.get('CRM_PRIVATE_CI_FAULT_PROFILE')=='native-followup-enqueue-v1',
          'disposable CI fault context required')
    check(owner_sql("SELECT has_table_privilege('crm_homologation_app','public.crm_private_automation_runs','INSERT')")=='t',
          'native enqueue privilege baseline missing')
    marker=VAULT.parent/'native-followup-enqueue-ci.json'
    descriptor=os.open(marker,os.O_WRONLY|os.O_CREAT|os.O_EXCL,0o600)
    with os.fdopen(descriptor,'w') as stream:
        json.dump({'profile':'native-followup-enqueue-v1','project':'imagine-crm-journey-ci',
                   'created_at':int(time.time())},stream)
    try:
        scenario('automation-deny-enqueue')
        yield
    finally:
        scenario('automation-restore-enqueue')
        # Keep the witness if restoration failed, so failure is not hidden and
        # the closed CI tools can retry cleanup before the stack is destroyed.
        marker.unlink(missing_ok=True)


@contextmanager
def denied_causal_logout(run):
    """Cause a real IAM hold; restore only this probe's temporary SQL objects."""
    check(os.environ.get('CRM_PRIVATE_PROJECT')=='imagine-crm-journey-ci'
          and os.environ.get('CRM_PRIVATE_CI_CAUSAL_SESSION_PROFILE')=='native-causal-session-hold-v1',
          'disposable causal session fault context required')
    marker=VAULT.parent/'native-causal-session-ci.json'
    descriptor=os.open(marker,os.O_WRONLY|os.O_CREAT|os.O_EXCL,0o600)
    with os.fdopen(descriptor,'w') as stream:
        json.dump({'profile':'native-causal-session-hold-v1','project':'imagine-crm-journey-ci',
                   'created_at':int(time.time()),'probe':str(uuid.uuid4()),
                   'actor':str(uuid.UUID(FIXTURES['clientB']['identity'])),
                   'company':str(uuid.UUID(FIXTURES['clientB']['organization'])),
                   'run':str(uuid.UUID(run['id'])),
                   'source':str(uuid.UUID(run['authority_session_id']))},stream)
    try:
        scenario('automation-deny-causal-logout')
        yield
    finally:
        scenario('automation-restore-causal-logout')
        check(owner_sql("SELECT to_regprocedure('public.crm_private_ci_causal_state_denial()') IS NULL "
                        "AND NOT EXISTS(SELECT 1 FROM pg_trigger WHERE tgrelid='public.crm_auth_sessions'::regclass "
                        "AND tgname='crm_private_ci_causal_state_denial')")=='t',
              'temporary causal fault objects were not removed')
        marker.unlink(missing_ok=True)


def assert_native_superseded(client,peer_company,suffix):
    """Exercise supersession through real authenticated HTTP and persisted rows."""
    company=str(uuid.UUID(FIXTURES['clientB']['organization']))
    peer_company=str(uuid.UUID(peer_company))

    def snapshot(tenant):
        return json.loads(owner_sql("SELECT jsonb_build_object("
            "'leads',(SELECT coalesce(jsonb_agg(to_jsonb(l) ORDER BY l.id),'[]'::jsonb) FROM public.crm_private_leads l WHERE organization_id='"+tenant+"'),"
            "'runs',(SELECT coalesce(jsonb_agg(to_jsonb(r) ORDER BY r.id),'[]'::jsonb) FROM public.crm_private_automation_runs r WHERE organization_id='"+tenant+"'),"
            "'events',(SELECT coalesce(jsonb_agg(to_jsonb(e) ORDER BY e.id),'[]'::jsonb) FROM public.crm_private_events e WHERE organization_id='"+tenant+"'),"
            "'tasks',(SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY t.id),'[]'::jsonb) FROM public.crm_private_tasks t WHERE organization_id='"+tenant+"'))::text"))

    def create_lead(tag):
        before=set(leads(client))
        client.post('/leads',{'title':'LEAD_TESTE_SUPER_'+tag+'_'+suffix.upper(),'value_cents':0})
        created=set(leads(client))-before
        check(len(created)==1,'supersession lead fixture not created once')
        return created.pop()

    def rows_for(model,lead):
        return [row for row in model['runs'] if row['lead_id']==lead]

    def only_state_changed(before,after,state):
        expected=dict(before,state=state)
        check(after==expected,'supersession rewrote causal actor/session/command or another immutable field')

    peer_before=snapshot(peer_company)
    page=client.get('/painel?section=automation')
    settings_version=int(re.search(r'name="automation_version" value="(\d+)"',page).group(1))
    client.post('/preferencias',{'automation_version':settings_version,'delay_seconds':0,'language':'pt',
                                'rules[]':['followup_qualified','followup_proposal']})
    completed_lead=create_lead('DONE')
    client.post('/leads/'+completed_lead,{'version':1,'stage':'qualified'})
    completed_before=rows_for(snapshot(company),completed_lead)
    check(len(completed_before)==1 and completed_before[0]['state']=='completed',
          'completed native control execution missing')
    client.post('/leads/'+completed_lead,{'version':2,'stage':'lost'})
    completed_after=snapshot(company)
    check(rows_for(completed_after,completed_lead)==completed_before,'terminal lead change rewrote completed execution')
    check(len(completed_after['tasks'])==1,'completed task disappeared after terminal lead change')

    client.post('/preferencias',{'automation_version':settings_version+1,'delay_seconds':3600,'language':'pt',
                                'rules[]':['followup_qualified','followup_proposal']})
    target=create_lead('TARGET')
    sibling=create_lead('SIBLING')
    client.post('/leads/'+target,{'version':1,'stage':'qualified'})
    client.post('/leads/'+sibling,{'version':1,'stage':'qualified'})
    before=snapshot(company)
    target_before=rows_for(before,target)
    sibling_before=rows_for(before,sibling)
    check(len(target_before)==len(sibling_before)==1
          and target_before[0]['state']==sibling_before[0]['state']=='pending'
          and target_before[0]['due_at']>int(time.time())+300,
          'future native runs not available for immediate cancellation test')

    # Failure occurs at INSERT of the replacement, after old-run cancellation.
    # The lead, old run, new-run absence, tasks and all native events must roll back.
    errors_before=native_enqueue_denials()
    with denied_native_enqueue():
        error_headers=None
        for locale in LOCALE_COPY:
            client.get('/painel?section=kanban&lang='+locale)
            page,_=client.post('/leads/'+target,{'version':2,'stage':'proposal'},expected=503)
            assert_private_error(client,page,locale,503)
            signature=private_headers(client)
            check(error_headers is None or signature==error_headers,'503 locale changed its existing header contract')
            error_headers=signature
            check(snapshot(company)==before,'localized SQL failure partially changed lead/ledger/audit/tasks')
    check(native_enqueue_denials()==errors_before+len(LOCALE_COPY),
          'expected native enqueue SQL permission failure was not observed')
    check(snapshot(company)==before,'failed replacement partially changed lead/ledger/audit/tasks')
    check(owner_sql("SELECT has_table_privilege('crm_homologation_app','public.crm_private_automation_runs','INSERT')")=='t',
          'native enqueue privilege not restored')
    client.get('/painel?section=kanban&lang=pt')
    client.post('/leads/'+target,{'version':2,'stage':'proposal'})
    advanced=snapshot(company)
    target_advanced=rows_for(advanced,target)
    check(len(target_advanced)==2,'valid stage change did not enqueue exactly one replacement')
    previous=next(row for row in target_advanced if row['id']==target_before[0]['id'])
    replacement=next(row for row in target_advanced if row['id']!=target_before[0]['id'])
    only_state_changed(target_before[0],previous,'cancelled')
    check(replacement['state']=='pending' and replacement['template']=='followup_proposal'
          and replacement['lead_version']==3
          and replacement['actor_id']==target_before[0]['actor_id']
          and replacement['authority_session_id']==target_before[0]['authority_session_id'],
          'replacement execution lost its current actor/session/lead version')
    check(rows_for(advanced,sibling)==sibling_before,'stage change cancelled another lead execution')
    check(advanced['tasks']==before['tasks'],'future replacement produced an early task')

    client.post('/leads/'+target,{'version':3,'stage':'lost'})
    terminal=snapshot(company)
    cancelled=next(row for row in rows_for(terminal,target) if row['id']==replacement['id'])
    only_state_changed(replacement,cancelled,'cancelled')
    check(rows_for(terminal,sibling)==sibling_before,'terminal lead cancelled sibling execution')
    client.post('/leads/'+sibling,{'version':2,'title':'LEAD_TESTE_SUPER_EDITED_'+suffix.upper()})
    edited=snapshot(company)
    only_state_changed(sibling_before[0],rows_for(edited,sibling)[0],'cancelled')
    check(len(edited['runs'])==len(terminal['runs']),'title edit created an unrequested native run')
    check(rows_for(edited,completed_lead)==completed_before,'completed execution changed during later supersession')
    check(edited['tasks']==before['tasks'],'cancelled future execution created or removed a task')
    page=client.get('/painel?section=automation')
    check('data-automation-state="pending"' not in page,'invalid future runs still shown as scheduled')
    client.post('/automacoes/processar',{})
    check(snapshot(company)==edited,'processing superseded executions changed terminal history')
    check(snapshot(peer_company)==peer_before,'supersession crossed the company boundary')
    print('PASS future follow-up supersession: stage/title/terminal changes cancel only stale pending commands; completed tasks preserved')
    print('PASS denied replacement INSERT rolls back lead/run/events/tasks; restored request queues exactly one current-session replacement')


def assert_native_causal_session(source_client,peer_company,suffix):
    """An independent valid session cannot execute a command from held authority."""
    company=str(uuid.UUID(FIXTURES['clientB']['organization']))
    actor=str(uuid.UUID(FIXTURES['clientB']['identity']))
    peer_company=str(uuid.UUID(peer_company))
    rules=['followup_qualified','followup_proposal']

    def snapshot(tenant):
        return json.loads(owner_sql("SELECT jsonb_build_object("
            "'preferences',(SELECT to_jsonb(p) FROM public.crm_private_preferences p WHERE organization_id='"+tenant+"'),"
            "'leads',(SELECT coalesce(jsonb_agg(to_jsonb(l) ORDER BY l.id),'[]'::jsonb) FROM public.crm_private_leads l WHERE organization_id='"+tenant+"'),"
            "'runs',(SELECT coalesce(jsonb_agg(to_jsonb(r) ORDER BY r.id),'[]'::jsonb) FROM public.crm_private_automation_runs r WHERE organization_id='"+tenant+"'),"
            "'events',(SELECT coalesce(jsonb_agg(to_jsonb(e) ORDER BY e.id),'[]'::jsonb) FROM public.crm_private_events e WHERE organization_id='"+tenant+"'),"
            "'tasks',(SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY t.id),'[]'::jsonb) FROM public.crm_private_tasks t WHERE organization_id='"+tenant+"'))::text"))

    def authority(session):
        session=str(uuid.UUID(session))
        return json.loads(owner_sql("SELECT jsonb_build_object('id',s.id,'identity_id',s.identity_id,"
            "'state',s.state,'security_hold',s.security_hold,'operation_id',s.operation_id,"
            "'expires_at',s.expires_at,'absolute_expires_at',s.absolute_expires_at,"
            "'origin_completed',EXISTS(SELECT 1 FROM public.crm_auth_operations o WHERE o.id=s.operation_id AND o.state='completed'),"
            "'hold_operation',(SELECT to_jsonb(o) FROM public.crm_auth_operations o WHERE o.id=s.security_hold),"
            "'hold_outcomes',(SELECT coalesce(jsonb_agg(a.outcome ORDER BY a.outcome),'[]'::jsonb) "
            "FROM public.crm_iam_audit_events a WHERE a.correlation_id=s.security_hold))::text "
            "FROM public.crm_auth_sessions s WHERE s.id='"+session+"' AND s.identity_id='"+actor+"'"))

    def create_lead(client,tag):
        before=set(leads(client))
        client.post('/leads',{'title':'LEAD_TESTE_CAUSAL_'+tag+'_'+suffix.upper(),'value_cents':0})
        created=set(leads(client))-before
        check(len(created)==1,'causal authority lead fixture not created once')
        return created.pop()

    peer_before=snapshot(peer_company)
    initial=snapshot(company)
    settings=initial['preferences']['automation_rules']
    revision=settings['version']
    check(revision<18 and settings['delay_seconds']==3600
          and sorted(settings['rules'])==sorted(rules),'causal scenario requires the existing native B baseline')
    # Code 0 is reserved for the final HTTP expiry scenario; code 1 for Chromium.
    current=Client()
    current.login(FIXTURES['clientB'],recovery=FIXTURES['clientB']['recovery'][2])
    source_client.post('/preferencias',{'automation_version':revision,'delay_seconds':30,
                                      'language':'pt','rules[]':rules})
    source_lead=create_lead(source_client,'HELD')
    nominal_lead=create_lead(current,'VALID')
    source_client.post('/leads/'+source_lead,{'version':1,'stage':'qualified'})
    current.post('/leads/'+nominal_lead,{'version':1,'stage':'qualified'})
    scheduled=snapshot(company)
    source_runs=[run for run in scheduled['runs'] if run['lead_id']==source_lead]
    nominal_runs=[run for run in scheduled['runs'] if run['lead_id']==nominal_lead]
    check(len(source_runs)==len(nominal_runs)==1,'causal/nominal command fixtures are ambiguous')
    source_run=source_runs[0];nominal_run=nominal_runs[0]
    check(source_run['state']==nominal_run['state']=='pending'
          and source_run['actor_id']==nominal_run['actor_id']==actor
          and source_run['authority_session_id']!=nominal_run['authority_session_id']
          and min(source_run['due_at'],nominal_run['due_at'])>int(time.time()),
          'two future commands must belong to distinct authenticated sessions of the same actor')
    original_authority=authority(source_run['authority_session_id'])
    check(original_authority['state']=='active' and original_authority['security_hold'] is None
          and original_authority['origin_completed'],'original source authority was already inconclusive')

    with denied_causal_logout(source_run):
        _,location=source_client.post('/logout',{})
        check(urllib.parse.urlparse(location).path=='/login','failed logout did not close the browser session')
    held=authority(source_run['authority_session_id'])
    operation=held['hold_operation']
    check(held['state']=='active' and held['security_hold'] is not None
          and held['operation_id']==original_authority['operation_id'] and held['origin_completed']
          and operation is not None and operation['id']==held['security_hold']
          and operation['subject_id']==actor and operation['action']=='logout' and operation['state']=='prepared'
          and held['hold_outcomes']==['attempted'],
          'real IAM failure did not leave an active source behind a prepared audited hold')
    source_client.get('/painel',expected=303)
    current.get('/painel?section=automation')
    valid=authority(nominal_run['authority_session_id'])
    check(valid['state']=='active' and valid['security_hold'] is None and valid['origin_completed'],
          'independent current session was not usable after source failure')
    check(snapshot(company)==scheduled,'fault injection changed business rows before native processing')

    remaining=max(0,max(source_run['due_at'],nominal_run['due_at'])-time.time()+0.2)
    check(remaining<=31,'unexpected long causal session scheduling delay')
    time.sleep(remaining)
    current.post('/automacoes/processar',{})
    processed=snapshot(company)
    expected_runs=[dict(run,state='cancelled') if run['id']==source_run['id'] else
                   dict(run,state='completed',attempts=1) if run['id']==nominal_run['id'] else run
                   for run in scheduled['runs']]
    check(processed['runs']==expected_runs,'held source was executed or unrelated/native command fields changed')
    check(not any(task['id']==source_run['id'] for task in processed['tasks'])
          and sum(task['id']==nominal_run['id'] for task in processed['tasks'])==1
          and len(processed['tasks'])==len(scheduled['tasks'])+1,
          'held source produced a task or nominal command was not applied exactly once')
    check(processed['leads']==scheduled['leads'] and processed['preferences']==scheduled['preferences'],
          'causal rejection changed lead or settings')
    added=[event for event in processed['events'] if event['id'] not in {item['id'] for item in scheduled['events']}]
    check(sorted((event['action'],event['object_id'],event['actor_id']) for event in added)==sorted([
          ('automation.cancelled',source_run['id'],actor),('automation.task_created',nominal_run['id'],actor)]),
          'causal denial/nominal execution audit is missing or misattributed')
    current.post('/automacoes/processar',{})
    check(snapshot(company)==processed,'repeated processing resurrected held work or duplicated nominal work')
    check(authority(source_run['authority_session_id'])==held,'native processing rewrote the IAM hold or operation')
    check(snapshot(peer_company)==peer_before,'causal handling changed another company or actor business data')
    # Restore the requested rule delay through normal CAS; never restore session A.
    current.post('/preferencias',{'automation_version':revision+1,'delay_seconds':3600,
                                 'language':'pt','rules[]':rules})
    print('PASS real failed logout leaves prepared IAM hold; another valid session cancels held causal run without task')
    print('PASS same-actor nominal run completes once; repeat, peer tenant and unresolved IAM evidence preserved')
    return current


def assert_preference_preservation(client,peer_company,suffix):
    """Language/no-op saves preserve native schedules, including at revision 20."""
    company=str(uuid.UUID(FIXTURES['clientB']['organization']))
    actor=str(uuid.UUID(FIXTURES['clientB']['identity']))
    peer_company=str(uuid.UUID(peer_company))
    rules=['followup_qualified','followup_proposal']

    def snapshot(tenant):
        return json.loads(owner_sql("SELECT jsonb_build_object("
            "'preferences',(SELECT to_jsonb(p) FROM public.crm_private_preferences p WHERE organization_id='"+tenant+"'),"
            "'leads',(SELECT coalesce(jsonb_agg(to_jsonb(l) ORDER BY l.id),'[]'::jsonb) FROM public.crm_private_leads l WHERE organization_id='"+tenant+"'),"
            "'runs',(SELECT coalesce(jsonb_agg(to_jsonb(r) ORDER BY r.id),'[]'::jsonb) FROM public.crm_private_automation_runs r WHERE organization_id='"+tenant+"'),"
            "'events',(SELECT coalesce(jsonb_agg(to_jsonb(e) ORDER BY e.id),'[]'::jsonb) FROM public.crm_private_events e WHERE organization_id='"+tenant+"'),"
            "'tasks',(SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY t.id),'[]'::jsonb) FROM public.crm_private_tasks t WHERE organization_id='"+tenant+"'))::text"))

    def save(version,delay,language,selected=rules,expected=303):
        return client.post('/preferencias',{'automation_version':version,'delay_seconds':delay,
                    'language':language,'rules[]':selected},expected=expected)

    def language_change(before,after,language):
        old_ids={event['id'] for event in before['events']}
        added=[event for event in after['events'] if event['id'] not in old_ids]
        check(len(added)==1 and added[0]['action']=='preferences.language_changed'
              and added[0]['actor_id']==actor and added[0]['object_id']==company,
              'language change audit missing or misattributed')
        expected=dict(before,preferences=dict(before['preferences'],language=language),
                      events=sorted(before['events']+added,key=lambda event:event['id']))
        check(after==expected,'language change mutated automation settings/history, lead, run or task')

    peer_before=snapshot(peer_company)
    initial=snapshot(company)
    settings=initial['preferences']['automation_rules']
    revision=settings['version'];delay=settings['delay_seconds']
    check(revision<19 and delay==3600 and sorted(settings['rules'])==sorted(rules),
          'preference preservation requires the existing native tenant B fixtures')
    save(revision,delay,'pt',list(reversed(rules)))
    check(snapshot(company)==initial,'unchanged/reordered preferences consumed a revision or audit event')

    before_ids=set(leads(client))
    client.post('/leads',{'title':'LEAD_TESTE_PREFERENCES_'+suffix.upper(),'value_cents':0})
    created=set(leads(client))-before_ids
    check(len(created)==1,'preference schedule lead not created once')
    lead=created.pop()
    client.post('/leads/'+lead,{'version':1,'stage':'qualified'})
    before=snapshot(company)
    pending=[run for run in before['runs'] if run['lead_id']==lead]
    check(len(pending)==1 and pending[0]['state']=='pending'
          and pending[0]['due_at']>int(time.time())+300,'preference test requires a future native command')
    original_run=pending[0]
    save(revision,delay,'en',list(reversed(rules)))
    localized=snapshot(company)
    language_change(before,localized,'en')
    check('<html lang="en"' in client.get('/painel?section=automation'),'saved language absent from HTTP view')
    save(revision,delay,'en')
    save(revision-1,delay,'en',expected=409)
    save(revision,delay,'en',['followup_qualified','followup_qualified'],expected=422)
    check(snapshot(company)==localized,'no-op, stale or duplicate-rule preference mutated persisted state')

    # A real automation change still advances once and cancels the prior command.
    save(revision,3,'en')
    changed=snapshot(company)
    check(changed['preferences']['automation_rules']['version']==revision+1
          and changed['preferences']['automation_rules']['delay_seconds']==3,
          'changed automation delay did not consume exactly one revision')
    expected_runs=[dict(run,state='cancelled') if run['id']==original_run['id'] else run
                   for run in localized['runs']]
    check(changed['runs']==expected_runs and changed['leads']==localized['leads']
          and changed['tasks']==localized['tasks'],'real preference change failed to cancel only pending commands')
    added=[event for event in changed['events'] if event['id'] not in {item['id'] for item in localized['events']}]
    check(sorted(event['action'] for event in added)==sorted([
          'automation.cancelled','automation.settings.v'+str(revision+1),'preferences.updated']),
          'real preference revision/cancellation audit changed')
    revision+=1;delay=3

    # Reach the existing cap through real HTTP changes; no privileged fixture rewrite.
    while revision<20:
        delay=5 if revision==19 else (2 if delay!=2 else 3)
        save(revision,delay,'en')
        revision+=1
    capped=snapshot(company)
    capped_settings=capped['preferences']['automation_rules']
    check(capped_settings['version']==20 and len(capped_settings['history'])==20
          and capped_settings['delay_seconds']==5,'real configuration revisions did not stop at the existing cap')
    check(capped['runs']==changed['runs'],'later settings revisions rewrote terminal runs')
    client.post('/leads/'+lead,{'version':2,'stage':'proposal'})
    scheduled=snapshot(company)
    replacement=[run for run in scheduled['runs'] if run['lead_id']==lead and run['state']=='pending']
    check(len(replacement)==1 and replacement[0]['settings_version']==20
          and replacement[0]['lead_version']==3 and replacement[0]['due_at']>int(time.time()),
          'revision-20 pending command missing')
    replacement=replacement[0]
    save(20,5,'en',list(reversed(rules)))
    check(snapshot(company)==scheduled,'revision-20 unchanged save mutated the schedule or audit')
    save(20,5,'es')
    translated=snapshot(company)
    language_change(scheduled,translated,'es')
    check('<html lang="es"' in client.get('/painel?section=automation'),'revision-20 language change was rejected')
    save(19,5,'es',expected=409)
    save(20,6,'pt',expected=409)
    save(20,5,'pt',['followup_qualified'],expected=409)
    save(20,5,'es',['followup_proposal','followup_proposal'],expected=422)
    # Existing HTTP field validation redirects with errors; it must not become a no-op.
    for invalid_delay,invalid_rules in ((-1,rules),(5,['billing.charge'])):
        _,location=save(20,invalid_delay,'es',invalid_rules)
        redirected=urllib.parse.urlparse(location)
        check(redirected.path=='/painel' and redirected.query=='',
              'invalid preference field bypassed the existing HTTP validation response')
    check(snapshot(company)==translated,'capped/stale/invalid preference request changed language, history or schedule')

    remaining=max(0,replacement['due_at']-time.time()+0.2)
    check(remaining<=6,'unexpected long native preference test delay')
    time.sleep(remaining)
    client.post('/automacoes/processar',{})
    completed=snapshot(company)
    exact=[task for task in completed['tasks'] if task['id']==replacement['id']]
    check(len(exact)==1 and exact[0]['lead_id']==lead
          and len(completed['tasks'])==len(translated['tasks'])+1,
          'preserved preference command did not create exactly its native task')
    finished=next(run for run in completed['runs'] if run['id']==replacement['id'])
    check(finished==dict(replacement,state='completed',attempts=1),
          'preserved preference command did not complete once without rewriting its causal fields')
    client.post('/automacoes/processar',{})
    check(snapshot(company)==completed,'repeating preserved preference command duplicated task or audit')
    check(snapshot(peer_company)==peer_before,'preference preservation crossed the tenant boundary')
    print('PASS language/no-op preferences preserve exact native schedule and audit; real delay changes invalidate once')
    print('PASS revision 20 accepts language/no-op only; stale 19 and revision 21 denied; preserved task created exactly once')


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
        self.status=None
        self.headers={}

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
        self.status=status
        self.headers={name.lower():value for name,value in response.getheaders() if name.lower()!='set-cookie'}
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


LOCALE_COPY={
    'pt':{'session':'Sessão expirada ou revogada. Entre novamente.',
          'password':'Acesso negado. Verifique os dados ou aguarde antes de tentar novamente.',
          'factor':'Fator inválido, já usado ou sessão expirada.','logout':'Sessão encerrada.',
          'error':'Não foi possível concluir esta operação.','unavailable':'Serviço temporariamente indisponível.',
          'code':'Código','terms':'O campo Termos simulados é obrigatório.',
          'title':'Use apenas o identificador fictício indicado no campo.',
          'integer':'O campo Valor em centavos deve ser um número inteiro.'},
    'en':{'session':'Session expired or revoked. Sign in again.',
          'password':'Access denied. Check your details or wait before trying again.',
          'factor':'Invalid or previously used factor, or expired session.','logout':'Signed out.',
          'error':'This operation could not be completed.','unavailable':'Service temporarily unavailable.',
          'code':'Code','terms':'The Simulated terms field is required.',
          'title':'Use only the test identifier indicated in the field.',
          'integer':'The Amount in BRL cents field must be an integer.'},
    'es':{'session':'Sesión expirada o revocada. Vuelve a ingresar.',
          'password':'Acceso denegado. Revisa los datos o espera antes de volver a intentarlo.',
          'factor':'Factor inválido, ya utilizado o sesión expirada.','logout':'Sesión cerrada.',
          'error':'No se pudo completar esta operación.','unavailable':'Servicio temporalmente no disponible.',
          'code':'Código','terms':'El campo Términos simulados es obligatorio.',
          'title':'Usa únicamente el identificador ficticio indicado en el campo.',
          'integer':'El campo Importe en centavos BRL debe ser un número entero.'},
}


def assert_locale(page,locale,copy=None):
    check('<html lang="'+('pt-BR' if locale=='pt' else locale)+'"' in page,'response lost the selected language')
    if copy: check(copy in unescape(page),'localized response copy missing')


def assert_destination(location,path):
    parsed=urllib.parse.urlparse(location)
    check(parsed.path==path and not parsed.query and not parsed.fragment,'locale changed a contracted redirect destination')


def private_headers(client):
    names=('cache-control','x-robots-tag','x-content-type-options','x-frame-options','referrer-policy','content-security-policy')
    return {name:client.headers.get(name) for name in names}


def assert_private_error(client,page,locale,status):
    copy=LOCALE_COPY[locale]
    assert_locale(page,locale)
    check(client.status==status and re.search(re.escape(copy['code'])+r'\s+'+str(status),page),
          'localized error changed its HTTP or displayed status')
    headings=re.findall(r'<h1[^>]*>(.*?)</h1>',page,re.S)
    allowed=[copy['error']]+([copy['unavailable']] if status==503 else [])
    check(len(headings)==1 and unescape(re.sub(r'<[^>]+>','',headings[0])).strip() in allowed,
          'error heading is not a reviewed localized message')
    check('no-store' in client.headers.get('cache-control','') and 'noindex' in client.headers.get('x-robots-tag',''),
          'localized error lost its existing private/no-index headers')
    check(not any(marker in page for marker in ('LOCALE_SENTINEL','SQLSTATE','PDOException','Illuminate\\','Stack trace')),
          'localized error exposed a request or exception sentinel')


def private_business_snapshot(companies):
    scope=','.join("'"+str(uuid.UUID(company))+"'" for company in companies)
    tables=('leads','tasks','events','preferences','orders','automation_runs')
    parts=["'"+table+"',(SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY to_jsonb(t)::text),'[]'::jsonb) "
           "FROM public.crm_private_"+table+" t WHERE organization_id IN ("+scope+"))" for table in tables]
    return json.loads(owner_sql('SELECT jsonb_build_object('+','.join(parts)+')::text'))


def assert_nullable_preferences(client,peer_company):
    """A permitted null rules value is the existing empty list, with a successful no-op."""
    state=json.loads(client.get('/automacoes/estado'))
    company=str(uuid.UUID(state['company_id']));peer_company=str(uuid.UUID(peer_company))
    before=private_business_snapshot([company,peer_company])
    own=[row for row in before['preferences'] if row['organization_id']==company]
    check(len(own)==1,'nullable preferences require exactly the authenticated tenant settings')
    preferences=own[0];settings=preferences['automation_rules']
    check(isinstance(settings,dict) and settings.get('schema')=='CRM_PRIVATE_AUTOMATION_V1'
          and settings.get('rules')==[] and type(settings.get('version')) is int
          and settings['version']==state['settings_version'] and 0<settings['version']<=20
          and type(settings.get('delay_seconds')) is int and 0<=settings['delay_seconds']<=86400
          and preferences['language'] in LOCALE_COPY,
          'nullable preferences require an already paused version with observed language and delay')
    fields={'automation_version':settings['version'],'language':preferences['language'],
            'delay_seconds':settings['delay_seconds']}
    for transport in ('form-empty','json-null'):
        page=client.get('/painel?section=settings')
        form=re.search(r'<form\b[^>]*action="/preferencias"[^>]*>(.*?)</form>',page,re.S)
        check(form is not None,'real preferences form is required for nullable requests')
        token=re.search(r'name="_token" value="([^"]+)"',form.group(1))
        check(token is not None and token.group(1)==client.csrf
              and re.fullmatch(r'[A-Za-z0-9]{40}',token.group(1))
              and 'name="automation_version" value="'+str(settings['version'])+'"' in form.group(1),
              'nullable request lost its real CSRF token or observed current form version')
        if transport=='form-empty':
            # The actual private HTTP kernel converts this empty string to null.
            _,location=client.post('/preferencias',dict(fields,rules=''),expected=303)
        else:
            body=json.dumps(dict(fields,rules=None,_token=token.group(1)),
                            separators=(',',':'),allow_nan=False).encode('utf-8')
            _,location=client.request('/preferencias',expected=303,body=body,
                                      headers={'Content-Type':'application/json'})
        destination=urllib.parse.urlsplit(location)
        check(destination.path=='/painel' and destination.query=='section=settings'
              and not destination.fragment and ((not destination.netloc and not destination.scheme)
                  or (destination.scheme in ('http','https') and destination.netloc=='crm-dev.imaginecomunica.com.br')),
              'nullable preferences did not take the exact successful settings redirect: '+transport)
        check(private_business_snapshot([company,peer_company])==before,
              'nullable preferences changed a revision, rule, delay, language or business/audit row: '+transport)
    print('PASS nullable preferences: real-CSRF form empty and JSON null return 303 to /painel?section=settings; observed paused version/language/delay and both tenant business/audit snapshots unchanged')


def assert_task_date_boundary(client,lead,company,peer_company):
    """Observe the PHP/SQL date mismatch without writing; deny year zero over real HTTP."""
    company=str(uuid.UUID(company));peer_company=str(uuid.UUID(peer_company));lead=str(uuid.UUID(lead))
    before=private_business_snapshot([company,peer_company])
    check(any(row['id']==lead and row['organization_id']==company for row in before['leads']),
          'task date probe requires a real lead of the requesting tenant')
    check(any(row['lead_id']==lead and row['organization_id']==company
              and row['title']=='TASK_TESTE_MANUAL' and row['state']=='done' for row in before['tasks']),
          'the existing nominal task must already be persisted and completed')
    envfile=os.environ.get('CRM_PRIVATE_ENV_FILE');project=os.environ.get('CRM_PRIVATE_PROJECT')
    check(project=='imagine-crm-journey-ci' and envfile and Path(envfile).is_file(),
          'task date witness requires the existing disposable CI stack')
    # Autoload only: no application bootstrap, identity, connection or historical Controller.
    php=r"""
require 'vendor/autoload.php';
$factory=new Illuminate\Validation\Factory(new Illuminate\Translation\Translator(new Illuminate\Translation\ArrayLoader(),'en'));
$date=DateTimeImmutable::createFromFormat('!Y-m-d','0000-01-01');
echo json_encode([
    'php_version'=>PHP_VERSION,'php_version_id'=>PHP_VERSION_ID,
    'date_format_accepts'=>$factory->make(['due'=>'0000-01-01'],['due'=>'required|date_format:Y-m-d'])->passes(),
    'roundtrip_accepts'=>$date!==false && $date->format('Y-m-d')==='0000-01-01',
],JSON_THROW_ON_ERROR);
"""
    command=['docker','compose','--env-file',envfile,'-p',project,'-f',str(ROOT/'infra/homologation/compose.yaml'),
             'exec','-T','app','php','-r',php]
    result=subprocess.run(command,capture_output=True,text=True,timeout=15)
    check(result.returncode==0,'PHP date witness failed; no process output logged')
    observed=json.loads(result.stdout)
    check(set(observed)=={'php_version','php_version_id','date_format_accepts','roundtrip_accepts'}
          and type(observed['php_version_id']) is int and 80400<=observed['php_version_id']<80500
          and isinstance(observed['php_version'],str) and re.fullmatch(r'8\.4\.[0-9]+',observed['php_version'])
          and observed['date_format_accepts'] is True and observed['roundtrip_accepts'] is True,
          'PHP 8.4 must witness the current date-format and roundtrip acceptance of year zero')
    database=json.loads(owner_sql(
        "BEGIN READ ONLY; SELECT jsonb_build_object("
        "'server_version_num',current_setting('server_version_num')::integer,"
        "'read_only',current_setting('transaction_read_only')='on',"
        "'zero_valid',pg_catalog.pg_input_is_valid('0000-01-01','pg_catalog.date'),"
        "'zero_sqlstate',(pg_catalog.pg_input_error_info('0000-01-01','pg_catalog.date')).sql_error_code,"
        "'year_one_valid',pg_catalog.pg_input_is_valid('0001-01-01','pg_catalog.date'))::text; ROLLBACK;"))
    check(set(database)=={'server_version_num','read_only','zero_valid','zero_sqlstate','year_one_valid'}
          and type(database['server_version_num']) is int and 160000<=database['server_version_num']<170000
          and database['read_only'] is True and database['zero_valid'] is False
          and database['zero_sqlstate']=='22008' and database['year_one_valid'] is True,
          'PostgreSQL 16 must reject AD year zero with 22008 and accept year one in a read-only transaction')
    client.post('/tarefas',{'lead':lead,'title':'TASK_TESTE_YEAR_ZERO','due':'0000-01-01'},expected=422)
    check(private_business_snapshot([company,peer_company])==before,
          'year-zero task refusal changed either tenant business/audit rows')
    print('PASS task date: PHP '+observed['php_version']+' accepts year zero; PostgreSQL '
          +str(database['server_version_num'])+' rejects it with 22008 read-only; HTTP 422 preserves both tenant business/audit snapshots and the completed nominal task')


def assert_entry_locales():
    """Errors before Controller::page still keep only the selected UI locale."""
    for locale,copy in LOCALE_COPY.items():
        client=Client()
        _,location=client.request('/painel?lang='+locale,expected=303)
        assert_destination(location,'/login')
        assert_locale(client.get('/login'),locale,copy['session'])
        _,location=client.post('/login',{'login':'lab-locale-missing-'+secrets.token_hex(5),
                                       'password':'LOCALE_SENTINEL_PASSWORD'})
        assert_destination(location,'/login')
        page=client.get('/login')
        assert_locale(page,locale,copy['password'])
        check('LOCALE_SENTINEL' not in page,'denied unknown login reflected a credential: '+locale)
        # An origin-budget refusal may happen before beginCore(). Such denial
        # must not authenticate, but does not promise an IAM/browser rotation.
        _,location=client.request('/painel',expected=303)
        assert_destination(location,'/login')

        client.get('/cadastro')
        visible={'login':'lab-locale-'+locale,'company':'EMPRESA_TESTE_LOCALE_'+locale.upper(),
                 'cnpj':'00000000000191','email':'lab-locale-'+locale+'@example.test',
                 'phone':'+5500000000000','title':'LEAD_TESTE_LOCALE','value_cents':'17'}
        payload={**visible,'password':'LOCALE_SENTINEL_PASSWORD','code':'LOCALE_SENTINEL_CODE',
                 'recovery':'LOCALE_SENTINEL_RECOVERY','invitation':'LOCALE_SENTINEL_INVITATION'}
        _,location=client.post('/cadastro',payload)
        assert_destination(location,'/cadastro')
        page=client.get('/cadastro')
        assert_locale(page,locale,copy['terms'])
        for field in ('login','company','email','phone'):
            check(re.search(r'name="'+field+r'"[^>]*value="'+re.escape(visible[field])+r'"',page),
                  'validation discarded allowed old input')
        check('LOCALE_SENTINEL' not in page,'validation reflected password/code/recovery/invitation old input')

    for query in ('lang=EN','lang=pt_BR','lang%5B%5D=en','lang=%3Cscript%3ELOCALE_SENTINEL%3C%2Fscript%3E'):
        client=Client();client.get('/login?lang=en')
        page=client.get('/login?'+query)
        assert_locale(page,'pt')
        check('LOCALE_SENTINEL' not in page,'invalid locale reflected a request value')
        assert_locale(client.get('/login'),'pt')
    print('PASS localized pre-controller redirect, denied unknown login, validation old-input allowlist and invalid-locale fallback PT/EN/ES')


def assert_old_input_rendering():
    """Malformed form values stay validation errors, with no enrollment effects."""
    # Fixed schema names only. Hash whole rows inside PostgreSQL, including
    # challenge/invitation state, without returning credentials or ciphertext.
    tables=('crm_private_signup_requests','crm_private_invitations','crm_private_accounts',
            'crm_private_trials','crm_private_trial_claims','crm_private_key_versions',
            'crm_global_identities','crm_organizations','crm_memberships','crm_auth_credentials',
            'crm_auth_recovery_codes','crm_auth_sessions','crm_private_recovery_attempts',
            'crm_private_preferences','crm_private_funnels')

    def fingerprints():
        parts=["'"+table+"',(SELECT jsonb_build_object('count',count(*),'digest',"
               "encode(sha256(convert_to(coalesce(jsonb_agg(to_jsonb(t) ORDER BY to_jsonb(t)::text),"
               "'[]'::jsonb)::text,'UTF8')),'hex')) FROM public."+table+" t)" for table in tables]
        result=json.loads(owner_sql('SELECT jsonb_build_object('+','.join(parts)+')::text'))
        check(set(result)==set(tables) and all(isinstance(row['count'],int)
              and re.fullmatch(r'[a-f0-9]{64}',row['digest']) for row in result.values()),
              'enrollment fingerprints are incomplete; no database values logged')
        return result

    class Inputs(HTMLParser):
        def __init__(self,page):
            super().__init__(convert_charrefs=True)
            self.inputs=[]
            self.feed(page)

        def handle_starttag(self,tag,attrs):
            if tag=='input':self.inputs.append((attrs,self.get_starttag_text()))

        def field(self,name):
            matches=[item for item in self.inputs if dict(item[0]).get('name')==name]
            check(len(matches)==1,'rendered old-input control is missing or duplicated')
            attrs,raw=matches[0]
            names=[key for key,_ in attrs]
            check(len(names)==len(set(names)),'rendered old input created duplicate attributes')
            return dict(attrs).get('value',''),raw

    fields={
        'pt':{'login':'Login fictício','company':'Empresa fictícia','email':'E-mail fictício'},
        'en':{'login':'Test username','company':'Test company','email':'Test email'},
        'es':{'login':'Usuario ficticio','company':'Empresa ficticia','email':'Correo ficticio'},
    }
    string_error={'pt':'O campo {field} deve conter texto.',
                  'en':'The {field} field must contain text.',
                  'es':'El campo {field} debe contener texto.'}
    required_error={'pt':'O campo {field} é obrigatório.',
                    'en':'The {field} field is required.',
                    'es':'El campo {field} es obligatorio.'}
    html_text='<b>OLD_INPUT_TEXT</b>&"'
    before=fingerprints()

    def render(locale,source,target,kind,invalid_field=None):
        client=Client()
        initial=client.get(source+'?lang='+locale)
        headers=private_headers(client)
        check(client.csrf is not None,'old-input probe requires a real form CSRF token')
        values={'login':'lab-old-render-'+locale,'company':'EMPRESA_TESTE_OLD_'+locale.upper()}
        values['email']=values['login']+'@example.test'
        if kind=='empty':values={name:'' for name in values}
        elif kind=='html':
            values={'login':'lab-'+html_text,'company':'EMPRESA_TESTE_'+html_text,
                    'email':'lab-'+html_text+'@example.test'}
        payload={**values,'password':'OLD_INPUT_SECRET_PASSWORD','code':'OLD_INPUT_SECRET_CODE',
                 'recovery':'OLD_INPUT_SECRET_RECOVERY','invitation':'OLD_INPUT_SECRET_INVITATION',
                 'terms':'simulated'}
        if source=='/cadastro':
            document=re.search(r'<option selected>([0-9]{14})</option>',initial)
            if not document:document=re.search(r'<option[^>]*>([0-9]{14})</option>',initial)
            check(document is not None,'old-input probe requires a rendered fictional document')
            payload.update(cnpj=document.group(1),phone='+5500000000000',plan='basic')
        else:
            payload.pop('company')
        if invalid_field:
            payload.pop(invalid_field)
            suffix='[]' if kind=='array' else '[nested][value]'
            payload[invalid_field+suffix]='OLD_INPUT_ARRAY_SENTINEL'
            errors=[string_error[locale].format(field=fields[locale][invalid_field])]
        else:
            # Even ordinary strings never issue a challenge: validation stops
            # on the missing terms before Enrollment::issue/issueInvitation.
            payload.pop('terms')
            errors=[LOCALE_COPY[locale]['terms']]
            if kind=='empty':
                required=('login','company','email') if source=='/cadastro' else ('login','email')
                errors.extend(required_error[locale].format(field=fields[locale][field]) for field in required)
        _,location=client.post(source,payload)
        assert_destination(location,source)
        # For /login, consume the signup flash directly on that page, before
        # following its /cadastro redirect. No authentication request is sent.
        page=client.get(target)
        assert_locale(page,locale)
        check(private_headers(client)==headers,'old-input render changed its private header contract')
        check('role="alert"' in page and all(message in unescape(page) for message in errors),
              'old-input render lost its localized validation messages')
        check('<form method="post" action="'+target+'"' in page,
              'old-input validation changed the form destination')
        check(not any(marker in page for marker in ('Array','TypeError','htmlspecialchars',
                  'OLD_INPUT_ARRAY_SENTINEL','OLD_INPUT_SECRET_','data-test="otp-code"',
                  'data-test="setup-secret"','data-test="recovery-codes"')),
              'old-input render exposed an array, exception, credential or enrollment secret')
        controls=Inputs(page)
        check(controls.field('password')[0]=='','old-input render repopulated the password')
        if target=='/convite':
            check(controls.field('invitation')[0]=='','old-input render repopulated the invitation token')
        login=controls.field('login')[0]
        if invalid_field=='login':
            if target=='/login':check(login=='','login array did not use its empty fallback')
            else:
                prefix='lab-cliente-' if target=='/cadastro' else 'lab-equipe-'
                check(re.fullmatch(prefix+r'[a-f0-9]{6}',login) is not None,
                      'login array did not use the contracted fictional fallback')
        else:check(login==values['login'],'old-input render discarded or transformed a scalar login')
        if target=='/cadastro':
            company=controls.field('company')[0]
            if invalid_field=='company':
                check(re.fullmatch(r'EMPRESA_TESTE_[A-F0-9]{6}',company) is not None,
                      'company array did not use the contracted fictional fallback')
            else:check(company==values['company'],'old-input render discarded or transformed a scalar company')
            expected_email=login+'@example.test' if invalid_field=='email' else values['email']
            check(controls.field('email')[0]==expected_email,'old-input render lost its exact email or fallback')
        elif target=='/convite':
            check(controls.field('email')[0]==login+'@example.test',
                  'invitation email was not derived from the displayed safe login')
        if kind=='html':
            names=('login','company','email') if target=='/cadastro' else ('login','email') if target=='/convite' else ('login',)
            for name in names:
                raw=controls.field(name)[1]
                check('<b>OLD_INPUT_TEXT</b>' not in page
                      and '&lt;b&gt;OLD_INPUT_TEXT&lt;/b&gt;&amp;&quot;' in raw,
                      'old-input HTML was not escaped intact at the attribute boundary')

    for locale in LOCALE_COPY:
        for kind in ('array','nested'):
            for field in ('login','company','email'):
                render(locale,'/cadastro','/cadastro',kind,field)
            render(locale,'/convite','/convite',kind,'login')
            render(locale,'/cadastro','/login',kind,'login')
        for kind in ('normal','empty','html'):
            render(locale,'/cadastro','/cadastro',kind)
            render(locale,'/convite','/convite',kind)
            render(locale,'/cadastro','/login',kind)
        check(fingerprints()==before,'old-input validation changed enrollment, trial, identity or invitation state')
    print('PASS old-input PT/EN/ES: real CSRF and 303→200 for flat/nested arrays; safe fictional fallbacks; scalar/empty/HTML values preserved; enrollment fingerprints unchanged')


def assert_auth_validation_redirects(client,locale,path,login):
    """Structural errors return to their form before any IAM operation starts."""
    check(path in ('/login','/mfa'),'closed auth validation probe path required')
    tables=('crm_auth_sessions','crm_auth_operations','crm_iam_audit_events',
            'crm_auth_credentials','crm_auth_recovery_codes')

    def fingerprint():
        # Values, bindings, credential hashes and factor ciphertext stay in PG.
        # The IAM runner persists intent before admission to the abuse guard.
        parts=["'"+table+"',(SELECT jsonb_build_object('count',count(*),'digest',"
               "encode(sha256(convert_to(coalesce(jsonb_agg(to_jsonb(t) ORDER BY to_jsonb(t)::text),"
               "'[]'::jsonb)::text,'UTF8')),'hex')) FROM public."+table+" t)" for table in tables]
        result=json.loads(owner_sql('SELECT jsonb_build_object('+','.join(parts)+')::text'))
        check(set(result)==set(tables) and all(isinstance(row['count'],int)
              and re.fullmatch(r'[a-f0-9]{64}',row['digest']) for row in result.values()),
              'auth validation fingerprints are incomplete; no database values logged')
        return result

    labels={
        'pt':{'login':'Login fictício','password':'Senha','code':'Código de verificação ou recuperação'},
        'en':{'login':'Test username','password':'Password','code':'Verification or recovery code'},
        'es':{'login':'Usuario ficticio','password':'Contraseña','code':'Código de verificación o recuperación'},
    }
    messages={
        'pt':{'required':'O campo {field} é obrigatório.','string':'O campo {field} deve conter texto.',
              'max':'O campo {field} deve ter no máximo {limit} caracteres.'},
        'en':{'required':'The {field} field is required.','string':'The {field} field must contain text.',
              'max':'The {field} field must have at most {limit} characters.'},
        'es':{'required':'El campo {field} es obligatorio.','string':'El campo {field} debe contener texto.',
              'max':'El campo {field} debe tener como máximo {limit} caracteres.'},
    }
    if path=='/login':
        cases=[('required',{'login':login},'password'),
               ('string',{'login[]':'AUTH_VALIDATION_INPUT','password':'AUTH_VALIDATION_SECRET_PASSWORD'},'login'),
               ('max',{'login':login,'password':'AUTH_VALIDATION_SECRET_'+'X'*73},'password')]
        limit=72
    else:
        cases=[('required',{},'code'),
               ('string',{'code[nested][value]':'AUTH_VALIDATION_SECRET_CODE'},'code'),
               ('max',{'code':'AUTH_VALIDATION_SECRET_'+'X'*33},'code')]
        limit=32
    before=fingerprint()
    csrf=client.csrf
    headers=private_headers(client)
    check(csrf is not None,'auth validation probe requires the current real CSRF token')
    for rule,payload,field in cases:
        context=path+'/'+locale+'/'+rule
        _,location=client.post(path,payload)
        assert_destination(location,path)
        page=client.get(path)
        copy=messages[locale][rule].format(field=labels[locale][field],limit=limit)
        assert_locale(page,locale,copy)
        check('role="alert"' in page and '<form method="post" action="'+path+'"' in page,
              'structural validation lost its original form or error: '+context)
        check(private_headers(client)==headers and client.csrf==csrf,
              'structural validation changed the form headers or current CSRF: '+context)
        check(not any(marker in page for marker in ('AUTH_VALIDATION_INPUT','AUTH_VALIDATION_SECRET_',
                                                   'Array','TypeError','SQLSTATE')),
              'structural validation reflected a credential, array or internal error: '+context)
        if path=='/login':
            controls=re.findall(r'<input\b(?=[^>]*\bname="login")[^>]*>',page)
            check(len(controls)==1,'structural login validation lost its unique username control')
            value=re.search(r'\bvalue="([^"]*)"',controls[0])
            check(value is not None and unescape(value.group(1))==('' if field=='login' else login),
                  'structural login validation discarded its allowed old input: '+context)
        check(fingerprint()==before,
              'structural validation entered IAM or changed authority/credential/recovery state: '+context)


def assert_auth_rotation_locales():
    account=FIXTURES['clientA']
    actor=str(uuid.UUID(account['identity']))
    companies=[account['organization'],FIXTURES['clientB']['organization']]
    before=private_business_snapshot(companies)

    def sessions():
        return json.loads(owner_sql("SELECT coalesce(jsonb_object_agg(id,jsonb_build_object('state',state,'binding',binding)),"
                                    "'{}'::jsonb)::text FROM public.crm_auth_sessions WHERE identity_id='"+actor+"'"))

    signatures={}
    for locale,copy in LOCALE_COPY.items():
        # Independent locale cases use the existing closed-CI cache fixture.
        # This is not a backoff test. Never reset between a failure and its
        # authority/replay assertions; all prior cases already logged out.
        scenario('clear-cache')
        client=Client()
        client.get('/cadastro?plan=enterprise&lang='+locale)
        client.get('/login')
        assert_auth_validation_redirects(client,locale,'/login',account['login'])
        old_csrf=client.csrf
        _,location=client.post('/login',{'login':account['login'],'password':'LOCALE_SENTINEL_WRONG_PASSWORD'})
        assert_destination(location,'/login')
        page=client.get('/login');assert_locale(page,locale,copy['password'])
        check('LOCALE_SENTINEL' not in page,'wrong existing-account password reflected a credential: '+locale)
        check(client.csrf!=old_csrf,'admitted wrong-password operation retained anonymous CSRF: '+locale)
        page,_=client.post('/logout',{'_token':old_csrf},expected=419,csrf=False)
        assert_private_error(client,page,locale,419)
        signature=private_headers(client)
        check(419 not in signatures or signatures[419]==signature,'locale changed the existing CSRF error headers')
        signatures[419]=signature
        _,location=client.request('/painel',expected=303);assert_destination(location,'/login')
        # Fresh origin count 1: preserve the configured 1-second backoff.
        time.sleep(1.2)
        client.get('/login');old_csrf=client.csrf
        known=sessions()
        _,location=client.post('/login',{'login':account['login'],'password':account['password']})
        assert_destination(location,'/mfa')
        assert_locale(client.get('/mfa'),locale)
        check(client.csrf!=old_csrf,'password step retained anonymous CSRF')
        pending=sessions();new=set(pending)-set(known)
        check(len(new)==1,'password step did not create exactly one pending authority')
        rejected_id=new.pop()
        check(pending[rejected_id]['state']=='pending','password step skipped MFA')
        # A failed factor must invalidate pending authority while retaining language.
        _,location=client.post('/mfa',{'code':'LOCALE_SENTINEL_NOT_A_FACTOR'})
        assert_destination(location,'/mfa')
        page=client.get('/mfa');assert_locale(page,locale,copy['factor'])
        check('LOCALE_SENTINEL' not in page and sessions()[rejected_id]['state']=='revoked',
              'failed factor reflected its value or preserved pending authority')
        _,location=client.request('/painel',expected=303);assert_destination(location,'/login')

        # Wrong password, valid password, then wrong MFA are origin counts
        # 1, 2 and 3. Respect the resulting 4-second backoff within this case.
        time.sleep(4.2)
        client.get('/login')
        known=sessions()
        _,location=client.post('/login',{'login':account['login'],'password':account['password']})
        assert_destination(location,'/mfa')
        assert_locale(client.get('/mfa'),locale)
        pending_cookie=dict(client.cookies);pending_csrf=client.csrf
        pending=sessions();new=set(pending)-set(known)
        check(len(new)==1,'fresh password attempt did not create one independent challenge')
        pending_id=new.pop()
        check(pending[pending_id]['binding']!=pending[rejected_id]['binding'],'failed challenge browser binding survived reset')
        # Reuse this real pending authority. These failures are before IAM,
        # so no new login, budget reset, recovery code or extra wait is needed.
        assert_auth_validation_redirects(client,locale,'/mfa',account['login'])
        _,location=client.post('/mfa',{'code':next_factor(account)})
        assert_destination(location,'/painel')
        assert_locale(client.get('/painel'),locale)
        check(client.csrf!=pending_csrf,'MFA retained the pending CSRF token')
        active=sessions();new=set(active)-set(pending)
        check(len(new)==1,'MFA did not issue exactly one replacement authority')
        active_id=new.pop()
        check(active[active_id]['state']=='active' and active[pending_id]['state']=='revoked'
              and active[active_id]['binding']!=pending[pending_id]['binding'],
              'MFA reused the pending authority or session binding')
        stale=Client();stale.cookies=pending_cookie
        _,location=stale.request('/painel',expected=303);assert_destination(location,'/login')
        page,_=client.post('/logout',{'_token':pending_csrf},expected=419,csrf=False)
        assert_private_error(client,page,locale,419)
        assert_locale(client.get('/painel'),locale)
        active_cookie=dict(client.cookies);active_csrf=client.csrf
        _,location=client.post('/logout',{});assert_destination(location,'/login')
        assert_locale(client.get('/login'),locale,copy['logout'])
        check(client.csrf!=active_csrf and sessions()[active_id]['state']=='revoked',
              'logout preserved authenticated CSRF or authority')
        stale=Client();stale.cookies=active_cookie
        _,location=stale.request('/painel',expected=303);assert_destination(location,'/login')
        # The earlier nonvisual plan must not be copied through any auth reset.
        page=client.get('/cadastro');assert_locale(page,locale)
        check('name="plan" value="basic"' in page,'auth rotation restored nonvisual session data')
        _,location=client.request('/painel',expected=303);assert_destination(location,'/login')

    check(private_business_snapshot(companies)==before,'locale/auth probes changed company data or scheduled effects')
    print('PASS auth validation PT/EN/ES: login/MFA return directly to their form; current CSRF and IAM fingerprints unchanged; existing valid MFA still completes')
    print('PASS PT/EN/ES across real password/MFA/logout resets; old sessions, bindings and CSRF rejected; only visual preference retained')


def assert_workspace_error_locales(client,lead):
    lead=str(uuid.UUID(lead))
    company=owner_sql("SELECT organization_id FROM public.crm_private_leads WHERE id='"+lead+"'")
    before=private_business_snapshot([company,FIXTURES['clientB']['organization']])
    csv_before=client.get('/exportar')
    signatures={}
    for locale,copy in LOCALE_COPY.items():
        assert_locale(client.get('/painel?section=leads&lang='+locale),locale)
        requests=[('/admin',None,403),('/leads/'+str(uuid.uuid4()),{'version':1,'stage':'qualified'},404),
                  ('/leads/'+lead,{'version':2,'stage':'qualified'},409),
                  ('/leads/'+lead,{'version':1,'stage':'new'},422),
                  ('/painel?lang='+locale+'&tenant_id='+str(uuid.UUID(FIXTURES['clientB']['organization'])),None,400)]
        for path,payload,status in requests:
            page,_=client.request(path,expected=status) if payload is None else client.post(path,payload,expected=status)
            assert_private_error(client,page,locale,status)
            signature=private_headers(client)
            check(status not in signatures or signatures[status]==signature,'localized denial changed its security header contract')
            signatures[status]=signature
        for payload,message in [({'title':'LOCALE_SENTINEL_INVALID_TITLE','value_cents':0},copy['title']),
                                ({'title':'LEAD_TESTE_VALID','value_cents':'LOCALE_SENTINEL'},copy['integer'])]:
            _,location=client.post('/leads',payload)
            assert_destination(location,'/painel')
            page=client.get('/painel');assert_locale(page,locale,message)
            check('LOCALE_SENTINEL' not in page,'localized validation reflected submitted invalid data')
        check(private_business_snapshot([company,FIXTURES['clientB']['organization']])==before,
              'localized error changed lead, task, schedule, event or peer-tenant data')
        check(client.get('/exportar')==csv_before,'translated UI changed the canonical CSV export')
    client.get('/painel?section=leads&lang=pt')
    print('PASS PT/EN/ES error statuses 400/403/404/409/422 and redirects/headers preserved; canonical CSV and tenant snapshots unchanged')


def assert_funnel_filter_navigation(client,peer,company,peer_company,foreign_funnel,suffix):
    """Real GETs preserve a valid funnel, reject foreign filters and never write business rows."""
    company=str(uuid.UUID(company));peer_company=str(uuid.UUID(peer_company))
    foreign_funnel=str(uuid.UUID(foreign_funnel))
    scope="'"+company+"','"+peer_company+"'"

    def snapshot():
        return {'business':private_business_snapshot([company,peer_company]),
                'funnels':json.loads(owner_sql("SELECT coalesce(jsonb_agg(to_jsonb(f) ORDER BY f.id),'[]'::jsonb)::text "
                                               "FROM public.crm_private_funnels f WHERE organization_id IN ("+scope+")"))}

    initial=snapshot()
    own_funnels=sorted(row['id'] for row in initial['funnels'] if row['organization_id']==company)
    peer_funnels={row['id'] for row in initial['funnels'] if row['organization_id']==peer_company}
    check(len(own_funnels)==2 and foreign_funnel in peer_funnels and foreign_funnel not in own_funnels,
          'funnel navigation requires two own funnels and a real peer funnel')
    empty=[funnel for funnel in own_funnels if not any(row['funnel_id']==funnel for row in initial['business']['leads'])]
    check(len(empty)==1,'funnel navigation fixture requires exactly one empty own funnel')
    # F255's original 0/3 report checks have already completed. Later CSV and
    # quota checks use the actual count, so this one ordinary lead keeps them intact.
    probe_title='LEAD_TESTE_FUNNEL_'+suffix.upper()
    client.get('/painel?section=leads&lang=pt')
    client.post('/leads',{'title':probe_title,'value_cents':32100,'funnel':empty[0]})
    before=snapshot()
    own_rows=[row for row in before['business']['leads'] if row['organization_id']==company]
    peer_rows=[row for row in before['business']['leads'] if row['organization_id']==peer_company]
    check(sum(row['title']==probe_title and row['funnel_id']==empty[0] for row in own_rows)==1,
          'ordinary lead creation did not populate exactly the selected own funnel')
    expected={funnel:sorted(row['id'] for row in own_rows if row['funnel_id']==funnel) for funnel in own_funnels}
    check(all(expected.values()),'both own funnels need distinct persisted leads before navigation')
    all_own=sorted(row['id'] for row in own_rows)
    all_peer=sorted(row['id'] for row in peer_rows)
    notice_copy={
        'pt':'O filtro de funil não está disponível. Escolha um funil da sua empresa.',
        'en':'The funnel filter is unavailable. Choose a funnel from your company.',
        'es':'El filtro de embudo no está disponible. Elige un embudo de tu empresa.',
    }

    def language_links(page,section,linked_funnel):
        block=re.search(r'<div class="languages"[^>]*>(.*?)</div>',page,re.S)
        check(block is not None,'language navigation missing from funnel view')
        hrefs=re.findall(r'<a\b[^>]*href="([^"]+)"',block.group(1))
        check(len(hrefs)==3,'funnel view must expose exactly the three language links')
        links={}
        for raw in hrefs:
            href=unescape(raw);parsed=urllib.parse.urlparse(href)
            values=urllib.parse.parse_qs(parsed.query,keep_blank_values=True)
            language=values.get('lang',[])
            check(not parsed.scheme and not parsed.netloc and not parsed.path and not parsed.fragment
                  and len(language)==1 and language[0] in LOCALE_COPY,
                  'language link changed its local navigation contract')
            wanted={'section':[section],'lang':language}
            if linked_funnel is not None:wanted['funnel']=[linked_funnel]
            check(values==wanted,'language link dropped the selected funnel or propagated untrusted parameters')
            links[language[0]]='/painel'+href
        check(set(links)==set(LOCALE_COPY),'language link locales are duplicated or missing')
        return links

    def assert_cards(page,ids,unavailable,locale,forbidden=()):
        assert_locale(page,locale)
        actual=sorted(re.findall(r'<article\b[^>]*data-lead-id="([a-f0-9-]+)"',page))
        check(actual==ids,'funnel view lost its exact lead scope or exposed peer data')
        check(page.count('data-funnel-filter-notice="unavailable"')==int(unavailable),
              'funnel filter notice does not match the existing filter semantics')
        if unavailable:check(notice_copy[locale] in unescape(page),'unavailable-funnel notice is not localized')
        check(not any(value in page for value in ('FUNNEL_FILTER_SENTINEL','FUNNEL_QUERY_SENTINEL',*forbidden)),
              'funnel view reflected an untrusted filter, query marker or peer record')

    unknown=str(uuid.uuid4())
    check(unknown not in set(own_funnels)|peer_funnels,'unknown funnel fixture unexpectedly exists')
    cases=[('funnel='+funnel,expected[funnel],False,funnel) for funnel in own_funnels]
    cases += [('',all_own,False,None),('funnel=',all_own,False,None),
              # PHP's existing truthiness treats the string '0' as no filter.
              # It is still nonempty, so the new neutral notice is expected.
              ('funnel=0',all_own,True,None),
              ('funnel=FUNNEL_FILTER_SENTINEL',[],True,None),
              ('funnel='+unknown,[],True,None),
              ('funnel='+foreign_funnel,[],True,None),
              ('funnel%5B%5D='+own_funnels[0]+'&funnel%5B%5D=FUNNEL_FILTER_SENTINEL',[],True,None),
              ('funnel%5Bid%5D='+own_funnels[0],[],True,None)]
    for query,ids,unavailable,linked in cases:
        page=client.get('/painel?section=kanban&lang=pt&'+query+'&extra=FUNNEL_QUERY_SENTINEL&return_to=FUNNEL_QUERY_SENTINEL')
        assert_cards(page,ids,unavailable,'pt',all_peer+[row['title'] for row in peer_rows]+[foreign_funnel])
        for locale,href in language_links(page,'kanban',linked).items():
            localized=client.get(href)
            # Invalid, empty and '0' filters are omitted, yielding unfiltered
            # own-company cards after the user follows a language link.
            assert_cards(localized,expected[linked] if linked else all_own,False,locale,
                         all_peer+[row['title'] for row in peer_rows]+[foreign_funnel])
            language_links(localized,'kanban',linked)
        check(snapshot()==before,'funnel/language GETs changed either company business data')

    # A valid filter is meaningful only on Kanban, never on another section.
    other=client.get('/painel?section=leads&lang=pt&funnel='+own_funnels[0]+'&extra=FUNNEL_QUERY_SENTINEL')
    language_links(other,'leads',None)
    check('FUNNEL_QUERY_SENTINEL' not in other,'other section reflected an extra query marker')
    # The peer has an authenticated session already. Its view must not fetch
    # the populated first company's funnel just because a real UUID is supplied.
    forbidden=all_own+[row['title'] for row in own_rows]+[own_funnels[0]]
    for locale in LOCALE_COPY:
        page=peer.get('/painel?section=kanban&lang='+locale+'&funnel='+own_funnels[0]+'&extra=FUNNEL_QUERY_SENTINEL')
        assert_cards(page,[],True,locale,forbidden)
        normalized=peer.get(language_links(page,'kanban',None)[locale])
        assert_cards(normalized,all_peer,False,locale,forbidden)
    client.get('/painel?section=leads&lang=pt');peer.get('/painel?section=kanban&lang=pt')
    check(snapshot()==before,'funnel filter normalization mutated own or peer company rows')
    print('PASS funnel filter PT/EN/ES: two populated own funnels, exact language-link scope, invalid/array/foreign/zero semantics, peer isolation and unchanged navigation snapshots')


def assert_member_billing_read_only(owner,member,peer,company,peer_company,states,pending=None):
    """Existing member reads own orders; only the existing owner may write."""
    company=str(uuid.UUID(company));peer_company=str(uuid.UUID(peer_company))
    pending=str(uuid.UUID(pending)) if pending is not None else None

    def fingerprint():
        organizations=[]
        for label,organization in (('owner',company),('peer',peer_company)):
            parts=["'"+table+"',(SELECT jsonb_build_object('count',count(*),'digest',"
                   "encode(sha256(convert_to(coalesce(jsonb_agg(to_jsonb(t) ORDER BY to_jsonb(t)::text),"
                   "'[]'::jsonb)::text,'UTF8')),'hex')) FROM public.crm_private_"+table+
                   " t WHERE organization_id='"+organization+"')" for table in ('orders','events')]
            organizations.append("'"+label+"',jsonb_build_object("+','.join(parts)+")")
        result=json.loads(owner_sql('SELECT jsonb_build_object('+','.join(organizations)+')::text'))
        check(set(result)=={'owner','peer'} and all(set(value)=={'orders','events'} for value in result.values())
              and all(isinstance(row['count'],int) and re.fullmatch(r'[a-f0-9]{64}',row['digest'])
                      for value in result.values() for row in value.values()),
              'member billing fingerprints are incomplete; no database values logged')
        return result

    class OrderForms(HTMLParser):
        def __init__(self,page,csrf):
            super().__init__(convert_charrefs=True)
            self.forms=[];self.current=None;self.select=None;self.csrf=csrf
            self.feed(page)

        def handle_starttag(self,tag,attributes):
            attrs=dict(attributes)
            if tag=='form':
                action=attrs.get('action','')
                if urllib.parse.urlsplit(action).path.startswith('/pedidos'):
                    self.current={'action':action,'method':attrs.get('method','get').lower(),
                                  'fields':[],'choices':[],'tokens':0}
            elif self.current is not None:
                if tag=='input' and 'name' in attrs:
                    name=attrs['name'];value=attrs.get('value','')
                    if name=='_token':
                        check(value==self.csrf and bool(value),'owner order form lost its current CSRF')
                        self.current['tokens']+=1
                    else:
                        if name=='key':
                            check(re.fullmatch(r'[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}',value),
                                  'owner order form lost its generated idempotency UUID')
                            value='<generated-order-uuid>'
                        self.current['fields'].append((name,value))
                elif tag=='select':self.select={'name':attrs.get('name'),'options':[],'selected':[]}
                elif tag=='option' and self.select is not None:
                    self.select['options'].append(attrs.get('value',''))
                    if 'selected' in attrs:self.select['selected'].append(attrs.get('value',''))

        def handle_endtag(self,tag):
            if tag=='select' and self.select is not None:
                check(self.select['options'] and len(self.select['selected'])<=1,
                      'owner order select has no options or multiple defaults')
                value=(self.select['selected'] or self.select['options'])[0]
                self.current['fields'].append((self.select['name'],value))
                self.current['choices'].append((self.select['name'],self.select['options']))
                self.select=None
            elif tag=='form' and self.current is not None:
                self.current['fields'].sort();self.current['choices'].sort()
                self.forms.append(self.current);self.current=None

    def rows(page):
        result=[]
        for identity,body in re.findall(r'<tr data-order-id="([a-f0-9-]+)">(.*?)</tr>',page,re.S):
            plan=re.search(r'data-plan="([a-z]+)"',body)
            state=re.search(r'data-order-state="([a-z]+)"',body)
            check(plan is not None and state is not None,'billing row lost its canonical plan or state')
            result.append((identity,plan.group(1),state.group(1),body))
        check(len(result)==len({row[0] for row in result}),'billing view duplicated an order row')
        return sorted(result)

    copy={
        'pt':{'notice':'Seu perfil pode consultar os pedidos simulados. Somente o responsável da empresa pode criar pedidos ou simular pagamentos.',
              'read':'Somente consulta','empty':'Ainda não há pedidos simulados nesta empresa.'},
        'en':{'notice':'Your role can view simulated orders. Only the company owner can create orders or simulate payments.',
              'read':'Read only','empty':'There are no simulated orders for this company yet.'},
        'es':{'notice':'Tu perfil puede consultar los pedidos simulados. Solo el responsable de la empresa puede crear pedidos o simular pagos.',
              'read':'Solo consulta','empty':'Todavía no hay pedidos simulados en esta empresa.'},
    }
    before=fingerprint();contracts=None
    check(before['owner']['orders']['count']==len(states) and before['peer']['orders']['count']==0,
          'member billing requires only the original owner orders and an empty peer fixture')
    for locale in LOCALE_COPY:
        owner_page=owner.get('/painel?section=billing&lang='+locale)
        assert_locale(owner_page,locale)
        own_rows=rows(owner_page)
        check(sorted(row[2] for row in own_rows)==sorted(states),'owner billing lost an original payment transition')
        forms=OrderForms(owner_page,owner.csrf).forms
        creation=[form for form in forms if form['action']=='/pedidos']
        check(len(creation)==1 and all(form['method']=='post' and form['tokens']==1 for form in forms),
              'owner billing lost its creation form, POST method or unique CSRF')
        check(sorted(name for name,_ in creation[0]['fields'])==['key','method','plan']
              and creation[0]['choices']==[('method',['pix','card','boleto']),
                                          ('plan',['basic','essential','professional','enterprise'])],
              'owner order form changed its canonical fields or choices')
        transitions=[form for form in forms if form['action']!='/pedidos']
        if pending is not None:
            check(any(row[0]==pending and row[2]=='pending' for row in own_rows)
                  and len(transitions)==3
                  and all(form['action']=='/pedidos/'+pending+'/simular' for form in transitions)
                  and sorted(form['fields'] for form in transitions)==
                      sorted([[('state',state),('version','1')] for state in ('approved','refused','cancelled')]),
                  'owner pending order lost its three versioned simulation actions')
        else:check(transitions==[],'owner final/empty billing unexpectedly offers a simulation action')
        canonical=sorted(forms,key=lambda form:json.dumps(form,sort_keys=True))
        check(contracts is None or canonical==contracts,'locale changed owner order fields, selected values, versions or actions')
        contracts=canonical
        check('data-billing-access="read-only"' not in owner_page,'owner was demoted to billing read-only')

        member_page=member.get('/painel?section=billing&lang='+locale)
        assert_locale(member_page,locale,copy[locale]['notice'])
        check(member_page.count('data-billing-access="read-only"')==1
              and OrderForms(member_page,member.csrf).forms==[],
              'member billing still offers order creation or simulation forms')
        member_rows=rows(member_page)
        check([row[:3] for row in member_rows]==[row[:3] for row in own_rows],
              'member cannot read the exact own-company orders shown to the owner')
        check(all(copy[locale]['read'] in unescape(row[3]) for row in member_rows)
              and (bool(states) or copy[locale]['empty'] in unescape(member_page)),
              'member billing read-only or empty-state copy is missing')
        peer_page=peer.get('/painel?section=billing&lang='+locale)
        assert_locale(peer_page,locale)
        check(rows(peer_page)==[] and not any(row[0] in peer_page for row in own_rows),
              'another company can read the member company orders')
        check(fingerprint()==before,'billing GETs changed own or peer orders/events')

        if pending is not None:
            page,_=member.post('/pedidos',{'plan':'essential','method':'pix','key':str(uuid.uuid4())},expected=403)
            assert_private_error(member,page,locale,403)
            check(fingerprint()==before,'denied member order creation changed orders/events')
            for state in ('approved','refused','cancelled'):
                page,_=member.post('/pedidos/'+pending+'/simular',{'state':state,'version':1},expected=403)
                assert_private_error(member,page,locale,403)
                check(fingerprint()==before,'denied member payment simulation changed orders/events')
    for client in (owner,member,peer):client.get('/painel?section=billing&lang=pt')
    check(fingerprint()==before,'restoring billing language changed either company orders/events')


def assert_confirmation_result(client,page,locale,kind,values):
    """Inspect an existing POST result in memory; never print its one-time values."""
    check(kind in ('registered','invitation','recovered') and client.status==200,
          'confirmation probe requires the existing successful POST response')
    assert_locale(page,locale)
    header=re.search(r'<header\b[^>]*>(.*?)</header>',page,re.S)
    main=re.search(r'<main\b[^>]*>(.*?)</main>',page,re.S)
    check(header is not None and main is not None,'confirmation response lost its header or main content')

    class Markup(HTMLParser):
        void={'area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'}

        def __init__(self,fragment):
            super().__init__(convert_charrefs=True)
            self.tags=[];self.links=[];self.indicators=[];self.blocks=[]
            self.link=None;self.indicator=None;self.block=None
            self.feed(fragment)

        def handle_starttag(self,tag,attributes):
            attrs=dict(attributes);self.tags.append((tag,attrs))
            if tag=='a':
                self.link={'href':attrs.get('href',''),'text':[]};self.links.append(self.link)
            if 'data-confirmation-language' in attrs:
                self.indicator={'language':attrs['data-confirmation-language'],'text':[],'tags':[],'depth':0}
                self.indicators.append(self.indicator)
            if attrs.get('data-test') in ('recovery-codes','invitation-code','otp-code','setup-secret'):
                self.block={'marker':attrs['data-test'],'text':[],'tags':[],'depth':0}
                self.blocks.append(self.block)
            for active in (self.indicator,self.block):
                if active is not None:
                    active['tags'].append((tag,attrs))
                    if tag not in self.void:active['depth']+=1

        def handle_endtag(self,tag):
            if tag=='a':self.link=None
            for name in ('indicator','block'):
                active=getattr(self,name)
                if active is not None and tag not in self.void:
                    active['depth']-=1
                    if active['depth']==0:setattr(self,name,None)

        def handle_data(self,text):
            for active in (self.link,self.indicator,self.block):
                if active is not None:active['text'].append(text)

    head=Markup(header.group(1));body=Markup(main.group(1))
    copy={
        'pt':{'label':'Idioma: PT-BR','notice':'Idioma definido antes da confirmação.',
              'login':'Entrar','team':'Voltar à equipe'},
        'en':{'label':'Language: EN','notice':'Language set before confirmation.',
              'login':'Sign in','team':'Back to team'},
        'es':{'label':'Idioma: ES','notice':'Idioma definido antes de la confirmación.',
              'login':'Ingresar','team':'Volver al equipo'},
    }[locale]
    check(len(head.indicators)==1 and head.indicators[0]['language']==locale,
          'one-time confirmation lost its selected language indicator')
    indicator=head.indicators[0]
    text=' '.join(' '.join(indicator['text']).split())
    check(copy['label'] in text and copy['notice'] in text,
          'confirmation language indicator or explanation is not localized')
    check(all(tag not in ('a','button','form','input','select','textarea','summary')
              and 'tabindex' not in attrs and 'contenteditable' not in attrs
              and attrs.get('role') not in ('button','link','combobox','listbox','textbox')
              and not any(name.startswith('on') for name in attrs)
              for tag,attrs in indicator['tags']),
          'confirmation language indicator still offers an interactive action')
    check(not any('languages' in attrs.get('class','').split() or tag=='form' for tag,attrs in head.tags)
          and sorted(link['href'] for link in head.links)==
              sorted(['/','https://comercial-dev.imaginecomunica.com.br/','/login']),
          'confirmation header retains language navigation or lost an explicit existing link')

    marker='invitation-code' if kind=='invitation' else 'recovery-codes'
    expected_count=1 if kind=='invitation' else 5
    check(len(values)==expected_count and len(set(values))==expected_count
          and head.blocks==[] and len(body.blocks)==1 and body.blocks[0]['marker']==marker,
          'confirmation lost or duplicated its expected one-time result marker')
    visible=''.join(body.blocks[0]['text']).split()
    check(visible==values and all(value and page.count(value)==1 and value not in header.group(1) for value in values),
          'confirmation moved, duplicated or changed a one-time result value')
    # These baseline results display each value once, only in the secret block;
    # their existing navigation never carries a code or invitation in a URL.
    urls=[attrs[name] for _,attrs in head.tags+body.tags
          for name in ('href','src','action','formaction') if name in attrs]
    check(not any(value in urllib.parse.unquote(url) for value in values for url in urls),
          'confirmation exposed a one-time value through a navigation URL')
    destination='/painel?section=team' if kind=='invitation' else '/login'
    label=copy['team'] if kind=='invitation' else copy['login']
    actions=[link for link in body.links if link['href']==destination]
    check(len(actions)==1 and label in ' '.join(actions[0]['text']),
          'confirmation lost its explicit return-to-team or sign-in action')


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
    prefix=f'--{boundary}\r\nContent-Disposition: form-data; name="_token"\r\n\r\n{client.csrf}\r\n--{boundary}\r\nContent-Disposition: form-data; name="csv"; filename="fictional.csv"\r\nContent-Type: text/csv\r\n\r\n'
    payload=csv_text.encode('utf-8') if isinstance(csv_text,str) else csv_text
    check(isinstance(payload,bytes),'CSV fixture must be text or exact bytes')
    body=prefix.encode('ascii')+payload+f'\r\n--{boundary}--\r\n'.encode('ascii')
    client.request('/importar',expected=expected,body=body,headers={'Content-Type':'multipart/form-data; boundary='+boundary})


def assert_strict_csv_import(client,peer,company,peer_company):
    """Malformed uploads cannot change either tenant's business rows or audit events."""
    companies=[str(uuid.UUID(company)),str(uuid.UUID(peer_company))]
    before=private_business_snapshot(companies)
    own_export=client.get('/exportar');peer_export=peer.get('/exportar')
    check(own_export.startswith('title,stage,value_cents\n') and '\r' not in own_export,
          'canonical export must remain the existing three-column LF CSV')
    # The first payload is also passed to the byte-pinned historical Controller
    # in the separate PHP 8.4 parser/forwarding witness. No historical DB write is claimed.
    malformed='title,value_cents\nLEAD_TESTE_CSV_PREFIX,1\nLEAD_TESTE_CSV_UNCLOSED,"100'
    rows20='title,value_cents\n'+''.join(f'LEAD_TESTE_CSV_BOUND_{i},1\n' for i in range(20))
    cases=[
        ('unclosed quote after a valid row',malformed,422),
        ('bytes after a closing quote','title,value_cents\nLEAD_TESTE_CSV_SUFFIX,"1"00',422),
        ('mixed record terminators','title,value_cents\r\nLEAD_TESTE_CSV_MIXED,1\n',422),
        ('invalid UTF-8',b'title,value_cents\nLEAD_TESTE_CSV_UTF8,1\xff',422),
        ('UTF-8 BOM',b'\xef\xbb\xbftitle,value_cents\nLEAD_TESTE_CSV_BOM,1\n',422),
        ('NUL byte',b'title,value_cents\nLEAD_TESTE_CSV_NUL,1\x00',422),
        ('21 data rows',rows20+'LEAD_TESTE_CSV_BOUND_20,1\n',422),
        # Twenty syntactically valid rows reach the unchanged trial quota. This
        # populated account cannot accept twenty more; a parser refusal would be 422.
        ('20 data rows exceed remaining trial quota',rows20,409),
        ('upload above 20 KiB','title,value_cents\nLEAD_TESTE_CSV_SIZE,'+'1'*20480,303),
    ]
    for label,payload,status in cases:
        upload_csv(client,payload,expected=status)
        check(private_business_snapshot(companies)==before,'CSV refusal changed business/audit rows: '+label)
        check(client.get('/exportar')==own_export and peer.get('/exportar')==peer_export,
              'CSV refusal changed an own or peer export: '+label)
    print('PASS strict CSV: malformed quote/suffix, mixed EOL, UTF-8/BOM/NUL, 21-row and 20-KiB denials; 20-row trial boundary; both tenant business/audit snapshots unchanged')



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


def assert_scenario_locale_navigation(client):
    """Follow rendered locale links without recalculating or saving a scenario."""
    companies=[FIXTURES['clientA']['organization'],FIXTURES['clientB']['organization']]
    before=private_business_snapshot(companies)
    defaults={'visits':1000,'lead_rate':4,'client_rate':15}
    cases=[
        ('visits=2500&lead_rate=8&client_rate=13',
         {'visits':2500,'lead_rate':8,'client_rate':13},(200,26)),
        ('',defaults,(40,6)),
        ('visits=0&lead_rate=0&client_rate=0',
         {'visits':0,'lead_rate':0,'client_rate':0},(0,0)),
        ('visits=1000000&lead_rate=100&client_rate=100',
         {'visits':1000000,'lead_rate':100,'client_rate':100},(1000000,1000000)),
        ('visits=-1&lead_rate=10000&client_rate=1e2',defaults,(40,6)),
        ('visits%5B%5D=SCENARIO_INPUT_SENTINEL&lead_rate%5Bnested%5D=100&client_rate%5B%5D=50',
         defaults,(40,6)),
        ('visits=0001&lead_rate=1.5&client_rate=101',defaults,(40,6)),
        ('visits=99&lead_rate=13&client_rate=17',
         {'visits':99,'lead_rate':13,'client_rate':17},(13,2)),
    ]

    def links(page,inputs):
        block=re.search(r'<div class="languages"[^>]*>(.*?)</div>',page,re.S)
        check(block is not None,'scenario page lost its language navigation')
        hrefs=re.findall(r'<a\b[^>]*href="([^"]+)"',block.group(1))
        check(len(hrefs)==3,'scenario page must retain three language links')
        targets={}
        for raw in hrefs:
            href=unescape(raw)
            parsed=urllib.parse.urlparse(href)
            query=urllib.parse.parse_qs(parsed.query,keep_blank_values=True)
            language=query.get('lang',[])
            check(not parsed.scheme and not parsed.netloc and not parsed.path and not parsed.fragment
                  and len(language)==1 and language[0] in LOCALE_COPY,
                  'scenario language navigation changed its local URL contract')
            expected={name:[str(value)] for name,value in inputs.items()}
            expected['lang']=language
            check(query==expected,'scenario language link discarded normalized values or copied unrelated parameters')
            targets[language[0]]='/'+href
        check(set(targets)==set(LOCALE_COPY),'scenario languages are missing or duplicated')
        return targets

    def rendered(page,locale,inputs,results):
        assert_locale(page,locale)
        for name,value in inputs.items():
            controls=re.findall(r'<input\b(?=[^>]*\bname="'+name+r'")[^>]*>',page)
            check(len(controls)==1,'scenario lost a unique numeric control')
            actual=re.search(r'\bvalue="([^"]*)"',controls[0])
            check(actual is not None and actual.group(1)==str(value),
                  'locale navigation changed a normalized scenario input')
        for name,value in zip(('visits','leads','clients'),(inputs['visits'],*results)):
            check('data-scenario-'+name+'="'+str(value)+'"' in page,
                  'locale navigation changed a scenario result')
        check(not any(marker in page for marker in ('SCENARIO_INPUT_SENTINEL','SCENARIO_QUERY_SENTINEL')),
              'scenario navigation reflected an untrusted input or unrelated parameter')

    for query,inputs,results in cases:
        page=client.get('/?lang=pt&'+query
                        +'&section=kanban&funnel=SCENARIO_QUERY_SENTINEL&extra=SCENARIO_QUERY_SENTINEL')
        rendered(page,'pt',inputs,results)
        targets=links(page,inputs)
        # Follow what the user sees. Do not reconstruct the values in each GET.
        for locale in ('en','es','pt'):
            page=client.get(targets[locale])
            rendered(page,locale,inputs,results)
            targets=links(page,inputs)
        check(private_business_snapshot(companies)==before,
              'anonymous scenario locale navigation changed CRM business data')
    print('PASS scenario locale navigation: one calculation retained through real PT/EN/ES links; normalized defaults/zero/bounds/arrays; no unrelated query or CRM effects')


def main():
    scenario('clear-cache')
    assert_old_input_rendering()
    assert_entry_locales()
    assert_auth_rotation_locales()
    # The preexisting journey starts with its original independent budgets.
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
    assert_scenario_locale_navigation(a)

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
    assert_confirmation_result(a,page,'pt','registered',primary['recovery'])
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
    assert_workspace_error_locales(a,lead)
    a.get('/painel?section=automation')
    a.post('/preferencias',{'automation_version':0,'language':'pt','rules[]':['followup_qualified','followup_proposal']})
    page=a.get('/painel?section=automation')
    check('value="1"' in page and 'Central de automações' in page,'versioned automation settings missing')
    a.post('/preferencias',{'automation_version':0,'language':'pt'},expected=409)
    a.post('/preferencias',{'automation_version':1,'language':'pt'})
    check('Pausada' in a.get('/painel?section=automation'),'pause not visible')
    assert_nullable_preferences(a,FIXTURES['clientB']['organization'])
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
    csv_text='"title","value_cents"\r\n"LEAD_TESTE_CSV_A","100"\r\n"LEAD_TESTE_CSV_B","200"\r\n'
    upload_csv(a,csv_text)
    check(len(leads(a))==3,'CSV import not persisted')
    canonical=a.get('/exportar')
    check('LEAD_TESTE_CSV_A,new,100\n' in canonical and 'LEAD_TESTE_CSV_B,new,200\n' in canonical,
          'quoted two-column CRLF import changed values or the default stage')
    # UI configuration is interpreted by the native planner and persisted as a durable run.
    a.get('/painel?section=automation')
    a.post('/preferencias',{'automation_version':3,'delay_seconds':3,'language':'pt','rules[]':['followup_qualified','followup_proposal']})
    a.get('/painel?section=kanban');a.post('/leads/'+lead,{'version':2,'stage':'proposal'})
    page=a.get('/painel?section=automation')
    proposal_runs=[identity for identity,body in re.findall(r'<tr data-automation-id="([^"]+)">(.*?)</tr>',page,re.S)
                   if 'data-automation-state="pending"' in body]
    check(len(proposal_runs)==1,'scheduled native run missing or ambiguous')
    proposal_run=proposal_runs[0]
    lead_ref=str(uuid.UUID(lead))
    audit_query="SELECT count(*) FROM public.crm_private_events WHERE object_id='"+lead_ref+"'"
    lead_audit_before=owner_sql(audit_query)
    # The ordinary title form submits its current value even without edits.
    # This must preserve the causal lead version and its scheduled native run.
    a.get('/painel?section=leads')
    a.post('/leads/'+lead,{'version':3,'title':title})
    a.post('/leads/'+lead,{'version':3,'title':title})
    a.post('/leads/'+lead,{'version':2,'title':title},expected=409)
    a.post('/leads/'+lead,{'version':3,'title':title,'stage':'proposal'},expected=422)
    check(version(a,lead)=='3','unchanged save or rejected command mutated lead version')
    check(owner_sql(audit_query)==lead_audit_before,'unchanged save or rejected command wrote a lead event')
    after_save=a.get('/painel?section=automation')
    preserved_runs=[identity for identity,body in re.findall(r'<tr data-automation-id="([^"]+)">(.*?)</tr>',after_save,re.S)
                    if 'data-automation-state="pending"' in body]
    check(preserved_runs==proposal_runs,'unchanged title save changed the scheduled native run')
    time.sleep(3.1)
    a.post('/automacoes/processar',{})
    proposal_tasks=a.get('/painel?section=tasks')
    check('TASK_TESTE_AUTO_PROPOSAL' in proposal_tasks,'native scheduled effect missing')
    check(proposal_tasks.count('data-task-id="'+proposal_run+'"')==1,
          'unchanged save prevented the exact scheduled task or produced a duplicate')
    check(owner_sql("SELECT state FROM public.crm_private_automation_runs WHERE id='"+str(uuid.UUID(proposal_run))+"'")=='completed',
          'scheduled run did not complete after unchanged save')
    before_tasks=len(re.findall(r'data-task-id=',proposal_tasks))
    a.post('/automacoes/processar',{})
    check(len(re.findall(r'data-task-id=',a.get('/painel?section=tasks')))==before_tasks,'duplicate native effect')
    print('PASS unchanged lead save preserves version/audit/schedule → exact native task once; stale and invalid transitions denied')
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
    check(owner_sql("SELECT NOT has_column_privilege('crm_homologation_app','crm_private_automation_runs','command','UPDATE') AND NOT has_column_privilege('crm_homologation_app','crm_private_automation_runs','actor_id','UPDATE') AND NOT has_column_privilege('crm_homologation_app','crm_private_automation_runs','execution_key','UPDATE') AND NOT has_column_privilege('crm_homologation_app','crm_private_automation_runs','authority_session_id','UPDATE') AND has_column_privilege('crm_homologation_app','crm_private_automation_runs','state','UPDATE')")=='t','native runtime may rewrite immutable authority or command')
    a.get('/automacoes/estado?company_id=forged',expected=422)
    print('PASS CENTRAL-AUTO-P1 UI → version → planner → PostgreSQL schedule → native task → dedupe → cancel → pause; AI calls zero by closed action catalog')
    print('PASS lead create/edit protections → Kanban versioning → automation → tasks → reports → CSV')
    assert_task_date_boundary(a,lead,automation_state['company_id'],FIXTURES['clientB']['organization'])

    a.get('/painel?section=funnels')
    a.post('/funis',{'title':'FUNIL_TESTE_SEGUNDO'})
    check(len(re.findall('data-funnel-id=',a.get('/painel?section=funnels')))==2,'two funnels not persisted')
    a.post('/funis',{'title':'FUNIL_TESTE_TERCEIRO'},expected=409)
    a.get('/painel?section=team&lang=en')
    page,_=a.post('/equipe/convidar',{},expected=200)
    invitation=re.search(r'data-test="invitation-code">([^<]+)',page).group(1).strip()
    assert_confirmation_result(a,page,'en','invitation',[invitation])
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
    assert_confirmation_result(member,page,'pt','registered',teammate['recovery'])
    check(primary['label'] in member.login(teammate),'invited member cannot see same company')
    check(title in member.get('/painel?section=leads'),'invited member cannot see same company leads')
    member.get('/automacoes/estado',expected=403)
    member.post('/preferencias',{'automation_version':8,'language':'pt'},expected=403)
    member.post('/funis',{'title':'FUNIL_TESTE_DENIED'},expected=403)
    member.post('/equipe/convidar',{},expected=403)
    member.get('/admin',expected=403)
    a.get('/painel?section=team&lang=pt');a.post('/equipe/convidar',{},expected=409)
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
    assert_funnel_filter_navigation(a,b,automation_state['company_id'],FIXTURES['clientB']['organization'],bPipelines[0][0],suffix)
    exported=a.get('/exportar')
    check(',won,' in exported and ',lost,' in exported,'exported sales stages missing')
    before_import=len(leads(a))
    upload_csv(a,exported)
    check(len(leads(a))==before_import*2,'exported CSV cannot be reimported')
    before_invalid_csv=private_business_snapshot([automation_state['company_id'],FIXTURES['clientB']['organization']])
    upload_csv(a,'title,stage,value_cents\nLEAD_TESTE_ATOMIC,new,1\n=INVALID,new,1\n')
    check(len(leads(a))==before_import*2,'invalid CSV partially committed')
    check(private_business_snapshot([automation_state['company_id'],FIXTURES['clientB']['organization']])==before_invalid_csv,
          'invalid domain row changed either tenant business/audit rows')
    print('PASS exported CSV round trip preserves stages → invalid-row import is atomic')
    assert_strict_csv_import(a,b,automation_state['company_id'],FIXTURES['clientB']['organization'])

    # A real title edit retains its original versioning and audit semantics.
    edited_title='LEAD_TESTE_EDITED_'+suffix.upper()
    prior_version=int(version(a,lead))
    prior_audit=int(owner_sql(audit_query))
    a.post('/leads/'+lead,{'version':prior_version,'title':edited_title})
    check(int(version(a,lead))==prior_version+1,'real title edit did not advance version once')
    check(owner_sql("SELECT title FROM public.crm_private_leads WHERE id='"+lead_ref+"'")==edited_title,
          'real title edit was discarded as unchanged')
    check(int(owner_sql(audit_query))==prior_audit+1,'real title edit did not append exactly one event')
    print('PASS real lead title change persists with one version and one audit event')

    a.get('/painel?section=billing')
    assert_member_billing_read_only(a,member,b,automation_state['company_id'],FIXTURES['clientB']['organization'],[])
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
        if state=='approved':
            assert_member_billing_read_only(a,member,b,automation_state['company_id'],
                                           FIXTURES['clientB']['organization'],['pending'],order)
        a.post('/pedidos/'+order+'/simular',{'state':state,'version':1})
        a.post('/pedidos/'+order+'/simular',{'state':state,'version':1})
        b.get('/painel?section=billing')
        b.post('/pedidos/'+order+'/simular',{'state':'approved','version':1},expected=404)
    billing=a.get('/painel?section=billing')
    check(all(state in billing for state in ('approved','refused','cancelled')),'payment state missing')
    assert_member_billing_read_only(a,member,b,automation_state['company_id'],
                                   FIXTURES['clientB']['organization'],['approved','refused','cancelled'])
    print('PASS member billing PT/EN/ES: exact own-order read-only views; direct creation/simulation denied without order/event effects; original owner forms and three payment transitions preserved')
    print('PASS simulated pending/approved/refused/cancelled payments → order/event idempotency → tenant binding')

    assert_native_superseded(b,automation_state['company_id'],suffix)
    b=assert_native_causal_session(b,automation_state['company_id'],suffix)
    assert_preference_preservation(b,automation_state['company_id'],suffix)

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
    recovery_client=Client();recovery_client.get('/recuperar?lang=es')
    page,_=recovery_client.post('/recuperar',{'login':primary['login'],'code':primary['recovery'][1],'password':new_password},expected=200)
    fresh_codes=re.findall(r'<div>([a-f0-9]{24})</div>',page)
    check(len(fresh_codes)==5,'recovery rotation missing')
    assert_confirmation_result(recovery_client,page,'es','recovered',fresh_codes)
    print('PASS existing registered/invitation/recovered POST results retain a passive PT/EN/ES indicator, exact one-time values and explicit actions; no extra enrollment or recovery')
    a.get('/painel',expected=303)
    primary['password']=new_password
    a.login(primary,recovery=fresh_codes[0])
    check(len(leads(a))==20,'data lost after recovery/session revocation')
    recovery_client.get('/recuperar?lang=pt')
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
    race_page=a.get('/painel?section=leads')
    race_lead=next(value for value,body in re.findall(r'<tr data-lead-id="([^"]+)">(.*?)</tr>',race_page,re.S) if 'LEAD_TESTE_QUOTA_' in body)
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
