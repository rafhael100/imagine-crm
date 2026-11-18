-- Disposable private CRM CI only. Controlled verifier fixtures exercise this
-- local SQL boundary; they are not evidence that n8n executed these tasks.
-- Every fixture, tamper and helper below is rolled back.
\set ON_ERROR_STOP on
BEGIN;
DO $$ BEGIN
 IF current_database()<>'imagine_crm_private_homologation' OR current_user<>'crm_homologation_owner'
 OR (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1'
 THEN RAISE EXCEPTION 'PRIVATE_EXECUTION_MONITOR_TEST_REQUIRED'; END IF;
END $$;
CREATE TEMP TABLE monitor_test_sources AS
 SELECT row_number() OVER(ORDER BY company_id,event_id) AS position,chosen.*
 FROM (SELECT DISTINCT ON(company_id) * FROM crm_auto_service.outbox ORDER BY company_id,event_id LIMIT 2) chosen;
DO $$ BEGIN
 IF (SELECT count(*) FROM monitor_test_sources)<>2 THEN RAISE EXCEPTION 'TWO_COMPANIES_REQUIRED'; END IF;
END $$;
INSERT INTO crm_auto_service.execution_read_grants(company_id,tenant_ref,destination,operation,active,revision,expires_at)
 SELECT company_id,tenant_ref,'imagine-ai-agents','event.execution.read',true,1,
 floor(extract(epoch FROM clock_timestamp()))::bigint+900 FROM monitor_test_sources
 ON CONFLICT(company_id) DO UPDATE SET tenant_ref=EXCLUDED.tenant_ref,active=true,expires_at=EXCLUDED.expires_at;

CREATE FUNCTION pg_temp.monitor_test_query(c jsonb) RETURNS jsonb LANGUAGE sql AS $$
 SELECT jsonb_build_object('schema','AUTO_IA_EXECUTION_QUERY_V1','issuer','imagine-crm',
 'audience','imagine-ai-agents','product','imagine-crm','operation','event.execution.read',
 'company_id',c->>'company_id','tenant_ref',c->>'tenant_ref','event_ref',c->>'event_ref','payload_hash',c->>'payload_hash',
 'request_ref','SYNTHETIC_'||upper(replace(gen_random_uuid()::text,'-','')),
 'nonce_ref','SYNTHETIC_'||upper(replace(gen_random_uuid()::text,'-','')),
 'issued_at',floor(extract(epoch FROM clock_timestamp()))::bigint,
 'expires_at',floor(extract(epoch FROM clock_timestamp()))::bigint+30,
 'key_id','SYNTHETIC_KEY_A','signature',repeat('1',64));
$$;
CREATE FUNCTION pg_temp.monitor_test_status(c jsonb,q jsonb,state text,queue text,attempt integer,task text)
RETURNS jsonb LANGUAGE plpgsql AS $$
DECLARE result text:=CASE WHEN state IN ('RESULT','ACKED') THEN 'PREPARED' ELSE 'NONE' END;facts text;observation text;
BEGIN
 facts:='IMAGINE_AGENTS_EXECUTION_OBSERVATION_V1'||chr(10)||concat_ws(chr(10),
  c->>'company_id',c->>'tenant_ref',c->>'event_ref',c->>'payload_hash',c->>'lead_id',task,state,queue,result,attempt::text);
 observation:=encode(sha256(convert_to(facts,'UTF8')),'hex');
 RETURN jsonb_build_object('schema','AUTO_IA_EXECUTION_STATUS_V1','issuer','imagine-ai-agents','audience','imagine-crm',
  'company_id',c->>'company_id','tenant_ref',c->>'tenant_ref','event_ref',c->>'event_ref','payload_hash',c->>'payload_hash',
  'lead_id',c->>'lead_id','task_payload_hash',task,'execution_state',state,'queue_state',queue,'result_code',result,
  'observation_hash',observation,'attempt',attempt,'request_ref',q->>'request_ref','nonce_ref',q->>'nonce_ref',
  'issued_at',(q->>'issued_at')::bigint,'expires_at',(q->>'expires_at')::bigint,
  'key_id','SYNTHETIC_EXECUTION_STATUS_KEY','signature',repeat('2',64));
END $$;
CREATE FUNCTION pg_temp.monitor_expect_denial(statement text,reason text) RETURNS void LANGUAGE plpgsql AS $$
BEGIN
 BEGIN EXECUTE statement;
 EXCEPTION WHEN OTHERS THEN
  IF SQLERRM=reason OR SQLSTATE=reason THEN RETURN; END IF;
  RAISE EXCEPTION 'UNEXPECTED_MONITOR_DENIAL: %, %',SQLSTATE,SQLERRM;
 END;
 RAISE EXCEPTION 'EXPECTED_MONITOR_DENIAL_NOT_OBSERVED: %',reason;
END $$;

DO $$
DECLARE a record;b record;c jsonb;q jsonb;r jsonb;v jsonb;observed jsonb;step record;
 old_pointer text;old_receipt text;old_source text;event_id uuid;counter integer;
 before_state jsonb;after_state jsonb;table_name text;role_name text;original_total bigint;
BEGIN
 SELECT * INTO a FROM monitor_test_sources WHERE position=1;
 SELECT * INTO b FROM monitor_test_sources WHERE position=2;
 IF EXISTS(SELECT 1 FROM crm_auto_service.execution_observations h JOIN monitor_test_sources s USING(company_id,event_id))
 THEN RAISE EXCEPTION 'EMPTY_MONITOR_TEST_HISTORY_REQUIRED'; END IF;

 -- Authorization is the existing application EXECUTE plus the enclosing verified
 -- human context. A GUC alone is never claimed to authenticate that human.
 IF (SELECT provolatile<>'s' OR NOT prosecdef OR pg_get_userbyid(proowner)<>'crm_auto_reader_owner'
  FROM pg_proc WHERE oid='crm_auto_service.read_execution_monitor(uuid)'::regprocedure)
 THEN RAISE EXCEPTION 'MONITOR_FUNCTION_NOT_READ_ONLY'; END IF;
 FOREACH table_name IN ARRAY ARRAY['crm_auto_service.outbox','crm_auto_service.execution_read_grants',
  'crm_auto_service.execution_observations','crm_auto_service.execution_projections'] LOOP
  IF has_table_privilege('crm_homologation_app',table_name,'SELECT,INSERT,UPDATE,DELETE,TRUNCATE')
  THEN RAISE EXCEPTION 'MONITOR_APP_HAS_DIRECT_SERVICE_TABLE_ACCESS'; END IF;
 END LOOP;
 IF has_function_privilege('crm_homologation_app','crm_auto_service.project_execution(uuid,uuid,jsonb,text)','EXECUTE')
 OR has_function_privilege('crm_homologation_app','crm_auto_service.execution_candidate(uuid,uuid)','EXECUTE')
 OR has_function_privilege('crm_homologation_app','crm_auto_service.stage_outbound(uuid,uuid)','EXECUTE')
 OR has_function_privilege('public','crm_auto_service.read_execution_monitor(uuid)','EXECUTE')
 THEN RAISE EXCEPTION 'MONITOR_EXECUTE_SURFACE_DRIFT'; END IF;
 FOREACH role_name IN ARRAY ARRAY['crm_auto_transport','crm_auto_sender','crm_auto_execution_reconciler'] LOOP
  EXECUTE format('SET LOCAL ROLE %I',role_name);
  BEGIN
   PERFORM crm_auto_service.read_execution_monitor(NULL);
   RAISE EXCEPTION 'MONITOR_UNAUTHORIZED_ROLE_ACCEPTED';
  EXCEPTION WHEN insufficient_privilege THEN NULL; END;
  EXECUTE 'RESET ROLE';
 END LOOP;
 EXECUTE 'SET LOCAL ROLE crm_homologation_app';
 PERFORM set_config('app.tenant_id','',true);
 BEGIN
  PERFORM crm_auto_service.read_execution_monitor(NULL);RAISE EXCEPTION 'MONITOR_EMPTY_CONTEXT_ACCEPTED';
 EXCEPTION WHEN OTHERS THEN IF SQLERRM<>'EXECUTION_MONITOR_CONTEXT_DENIED' THEN RAISE; END IF; END;
 PERFORM set_config('app.tenant_id','not-a-company',true);
 BEGIN
  PERFORM crm_auto_service.read_execution_monitor(NULL);RAISE EXCEPTION 'MONITOR_INVALID_CONTEXT_ACCEPTED';
 EXCEPTION WHEN OTHERS THEN IF SQLERRM<>'EXECUTION_MONITOR_CONTEXT_DENIED' THEN RAISE; END IF; END;
 PERFORM set_config('app.tenant_id',a.company_id::text,true);
 v:=crm_auto_service.read_execution_monitor(a.event_id);
 original_total:=(v->>'total_events')::bigint;
 IF v->>'schema' IS DISTINCT FROM 'CRM_N8N_EXECUTION_MONITOR_V1'
 OR v->>'company_id' IS DISTINCT FROM a.company_id::text
 OR v->'selected'->'observation' IS DISTINCT FROM 'null'::jsonb
 OR v->'selected'->'history' IS DISTINCT FROM '[]'::jsonb
 OR (v->'selected'->>'history_count')::integer IS DISTINCT FROM 0
 OR original_total IS NULL OR original_total<1
 THEN RAISE EXCEPTION 'MONITOR_UNOBSERVED_IS_NOT_NOT_STAGED'; END IF;
 v:=crm_auto_service.read_execution_monitor(b.event_id);
 IF v->'selected' IS DISTINCT FROM 'null'::jsonb OR v::text LIKE '%'||b.event_id::text||'%'
 THEN RAISE EXCEPTION 'MONITOR_CROSS_COMPANY_SELECTED_LEAK'; END IF;
 v:=crm_auto_service.read_execution_monitor('00000000-0000-0000-0000-000000000000');
 IF v->'selected' IS DISTINCT FROM 'null'::jsonb THEN RAISE EXCEPTION 'MONITOR_ABSENCE'; END IF;
 EXECUTE 'RESET ROLE';

 c:=crm_auto_service.execution_candidate(a.company_id,a.event_id);
 FOR step IN SELECT * FROM (VALUES ('NOT_STAGED','NONE',0,''),('NOT_STARTED','PENDING',0,repeat('a',64)),
  ('NOT_STARTED','LEASED',0,repeat('a',64)),('STARTED','LEASED',1,repeat('a',64)),
  ('RESULT','LEASED',1,repeat('a',64)),('ACKED','ACKED',1,repeat('a',64))) AS states(state,queue,attempt,task) LOOP
  q:=pg_temp.monitor_test_query(c);r:=pg_temp.monitor_test_status(c,q,step.state,step.queue,step.attempt,step.task);
  PERFORM crm_auto_service.project_execution(a.company_id,a.event_id,q,r::text);
 END LOOP;
 UPDATE crm_auto_service.execution_read_grants SET active=false,expires_at=0 WHERE company_id=a.company_id;
 UPDATE crm_auto_service.outbound_grants SET active=false WHERE company_id=a.company_id;
 SELECT jsonb_build_object(
  'history',(SELECT jsonb_agg(to_jsonb(x) ORDER BY company_id,event_id,sequence) FROM crm_auto_service.execution_observations x),
  'projection',(SELECT jsonb_agg(to_jsonb(x) ORDER BY company_id,event_id) FROM crm_auto_service.execution_projections x),
  'outbox',(SELECT jsonb_agg(to_jsonb(x) ORDER BY company_id,event_id) FROM crm_auto_service.outbox x),
  'grants',(SELECT jsonb_agg(to_jsonb(x) ORDER BY company_id) FROM crm_auto_service.execution_read_grants x)) INTO before_state;
 EXECUTE 'SET LOCAL ROLE crm_homologation_app';
 PERFORM set_config('app.tenant_id',a.company_id::text,true);
 v:=crm_auto_service.read_execution_monitor(a.event_id);
 observed:=v->'selected';
 IF observed->'observation'->>'execution_state' IS DISTINCT FROM 'ACKED'
 OR observed->'observation'->>'result_code' IS DISTINCT FROM 'PREPARED'
 OR (observed->>'history_count')::integer IS DISTINCT FROM 6 OR jsonb_array_length(observed->'history') IS DISTINCT FROM 6
 OR observed->'history'->0 IS DISTINCT FROM observed->'observation'
 OR (observed->'history'->5->>'sequence')::integer IS DISTINCT FROM 1
 OR EXISTS(SELECT 1 FROM jsonb_array_elements(v->'events') item WHERE item->'history' IS DISTINCT FROM '[]'::jsonb)
 OR v::text ~ 'payload_hash|observation_hash|receipt_hash|signature|key_id|tenant_ref|grant_revision|nonce_ref|request_ref|receipt|query'
 THEN RAISE EXCEPTION 'MONITOR_HISTORY_OR_REDACTION'; END IF;
 -- Two local reads leave every transport, observation and projection byte alone.
 PERFORM crm_auto_service.read_execution_monitor(a.event_id);
 EXECUTE 'RESET ROLE';
 SELECT jsonb_build_object(
  'history',(SELECT jsonb_agg(to_jsonb(x) ORDER BY company_id,event_id,sequence) FROM crm_auto_service.execution_observations x),
  'projection',(SELECT jsonb_agg(to_jsonb(x) ORDER BY company_id,event_id) FROM crm_auto_service.execution_projections x),
  'outbox',(SELECT jsonb_agg(to_jsonb(x) ORDER BY company_id,event_id) FROM crm_auto_service.outbox x),
  'grants',(SELECT jsonb_agg(to_jsonb(x) ORDER BY company_id) FROM crm_auto_service.execution_read_grants x)) INTO after_state;
 IF before_state IS DISTINCT FROM after_state THEN RAISE EXCEPTION 'MONITOR_READ_MUTATED_STATE'; END IF;

 SELECT observation_hash INTO old_pointer FROM crm_auto_service.execution_projections WHERE company_id=a.company_id AND event_id=a.event_id;
 UPDATE crm_auto_service.execution_projections SET observation_hash=(SELECT observation_hash FROM crm_auto_service.execution_observations
  WHERE company_id=a.company_id AND event_id=a.event_id AND sequence=1) WHERE company_id=a.company_id AND event_id=a.event_id;
 PERFORM pg_temp.monitor_expect_denial(format('SELECT crm_auto_service.read_execution_monitor(%L)',a.event_id),'EXECUTION_MONITOR_PROJECTION_DRIFT');
 UPDATE crm_auto_service.execution_projections SET observation_hash=old_pointer WHERE company_id=a.company_id AND event_id=a.event_id;
 DELETE FROM crm_auto_service.execution_projections WHERE company_id=a.company_id AND event_id=a.event_id;
 PERFORM pg_temp.monitor_expect_denial(format('SELECT crm_auto_service.read_execution_monitor(%L)',a.event_id),'EXECUTION_MONITOR_PROJECTION_DRIFT');
 INSERT INTO crm_auto_service.execution_projections VALUES(a.company_id,a.event_id,old_pointer);
 SELECT payload INTO old_source FROM crm_auto_service.outbox WHERE company_id=a.company_id AND event_id=a.event_id;
 UPDATE crm_auto_service.outbox SET payload=payload||chr(10) WHERE company_id=a.company_id AND event_id=a.event_id;
 PERFORM pg_temp.monitor_expect_denial(format('SELECT crm_auto_service.read_execution_monitor(%L)',a.event_id),'EXECUTION_MONITOR_HISTORY_DRIFT');
 UPDATE crm_auto_service.outbox SET payload=old_source WHERE company_id=a.company_id AND event_id=a.event_id;
 UPDATE crm_auto_service.outbox SET payload=jsonb_set(payload::jsonb,'{lead_id}',to_jsonb('00000000-0000-0000-0000-000000000000'::text))::text
  WHERE company_id=a.company_id AND event_id=a.event_id;
 PERFORM pg_temp.monitor_expect_denial(format('SELECT crm_auto_service.read_execution_monitor(%L)',a.event_id),'EXECUTION_MONITOR_SOURCE_DRIFT');
 UPDATE crm_auto_service.outbox SET payload=old_source WHERE company_id=a.company_id AND event_id=a.event_id;

 -- Only the disposable test administrator disables the immutable trigger to
 -- inject corruption. The reader must reject it; the exact trigger is restored.
 ALTER TABLE crm_auto_service.execution_observations DISABLE TRIGGER execution_history_immutable;
 SELECT receipt INTO old_receipt FROM crm_auto_service.execution_observations WHERE company_id=a.company_id AND event_id=a.event_id AND sequence=1;
 UPDATE crm_auto_service.execution_observations SET receipt=receipt||chr(10) WHERE company_id=a.company_id AND event_id=a.event_id AND sequence=1;
 PERFORM pg_temp.monitor_expect_denial(format('SELECT crm_auto_service.read_execution_monitor(%L)',a.event_id),'EXECUTION_MONITOR_HISTORY_DRIFT');
 UPDATE crm_auto_service.execution_observations SET receipt=old_receipt WHERE company_id=a.company_id AND event_id=a.event_id AND sequence=1;
 UPDATE crm_auto_service.execution_observations SET sequence=10 WHERE company_id=a.company_id AND event_id=a.event_id AND sequence=1;
 PERFORM pg_temp.monitor_expect_denial(format('SELECT crm_auto_service.read_execution_monitor(%L)',a.event_id),'EXECUTION_MONITOR_HISTORY_DRIFT');
 UPDATE crm_auto_service.execution_observations SET sequence=1 WHERE company_id=a.company_id AND event_id=a.event_id AND sequence=10;
 UPDATE crm_auto_service.execution_observations SET receipt=jsonb_set(receipt::jsonb,'{lead_id}',to_jsonb('00000000-0000-0000-0000-000000000000'::text))::text
  WHERE company_id=a.company_id AND event_id=a.event_id AND sequence=1;
 UPDATE crm_auto_service.execution_observations SET receipt_hash=encode(sha256(convert_to(receipt,'UTF8')),'hex')
  WHERE company_id=a.company_id AND event_id=a.event_id AND sequence=1;
 PERFORM pg_temp.monitor_expect_denial(format('SELECT crm_auto_service.read_execution_monitor(%L)',a.event_id),'EXECUTION_MONITOR_HISTORY_DRIFT');
 UPDATE crm_auto_service.execution_observations SET receipt=old_receipt,receipt_hash=encode(sha256(convert_to(old_receipt,'UTF8')),'hex')
  WHERE company_id=a.company_id AND event_id=a.event_id AND sequence=1;
 ALTER TABLE crm_auto_service.execution_observations ENABLE TRIGGER execution_history_immutable;

 -- The list bound is exercised with independent source events, never with
 -- invented impossible execution transitions to fabricate 21 observations.
 UPDATE crm_auto_service.outbound_grants SET active=true,expires_at=floor(extract(epoch FROM clock_timestamp()))::bigint+900
  WHERE company_id=a.company_id;
 FOR counter IN 1..52 LOOP
  event_id:=gen_random_uuid();
  INSERT INTO public.crm_private_events(id,organization_id,actor_id,object_id,action,occurred_at)
   SELECT event_id,organization_id,actor_id,object_id,'lead.created',occurred_at+counter FROM public.crm_private_events
   WHERE id=a.event_id AND organization_id=a.company_id;
  PERFORM crm_auto_service.stage_outbound(a.company_id,event_id);
 END LOOP;
 EXECUTE 'SET LOCAL ROLE crm_homologation_app';
 PERFORM set_config('app.tenant_id',a.company_id::text,true);
 v:=crm_auto_service.read_execution_monitor(a.event_id);
 IF (v->>'total_events')::bigint IS DISTINCT FROM original_total+52 OR jsonb_array_length(v->'events') IS DISTINCT FROM 50
 OR v->'selected'->>'event_id' IS DISTINCT FROM a.event_id::text
 OR (v->'selected'->>'history_count')::integer IS DISTINCT FROM 6
 OR EXISTS(SELECT 1 FROM jsonb_array_elements(v->'events') item WHERE item->>'event_id'=a.event_id::text)
 THEN RAISE EXCEPTION 'MONITOR_LIST_BOUND_OR_OLDER_SELECTION'; END IF;
 IF EXISTS(SELECT 1 FROM (
  SELECT (item->>'occurred_at')::bigint AS stamp,item->>'event_id' AS id,
   lag((item->>'occurred_at')::bigint) OVER(ORDER BY ordinal) AS previous_stamp,
   lag(item->>'event_id') OVER(ORDER BY ordinal) AS previous_id
  FROM jsonb_array_elements(v->'events') WITH ORDINALITY AS rows(item,ordinal)) ordered
  WHERE previous_stamp<stamp OR (previous_stamp=stamp AND previous_id<id))
 THEN RAISE EXCEPTION 'MONITOR_LIST_ORDER'; END IF;
 EXECUTE 'RESET ROLE';
END $$;
SELECT 'CRM_N8N_MONITOR_SQL_PASS scope=1 source_history_pointer_tamper_denied=1 redacted=1 read_grant_revoked_history=1 list_limit=50 writes=0';
ROLLBACK;

-- The actual reader must also run in a PostgreSQL READ ONLY transaction.
SELECT company_id AS company FROM crm_auto_service.outbox ORDER BY company_id,event_id LIMIT 1 \gset monitor_
BEGIN READ ONLY;
SET LOCAL ROLE crm_homologation_app;
SELECT set_config('app.tenant_id',:'monitor_company',true);
DO $$ BEGIN
 IF crm_auto_service.read_execution_monitor(NULL)->>'schema' IS DISTINCT FROM 'CRM_N8N_EXECUTION_MONITOR_V1'
 THEN RAISE EXCEPTION 'MONITOR_READ_ONLY_FAILED'; END IF;
END $$;
SELECT 'CRM_N8N_MONITOR_READ_ONLY_PASS';
ROLLBACK;
