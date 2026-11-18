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
    assert_workspace_error_locales(a,lead)
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
