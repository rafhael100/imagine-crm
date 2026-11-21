-- Run only in the disposable private CRM CI, after the optional feedback SQL.
-- Exercises the SQL boundary with controlled verifier fixtures. Real HMAC/HTTP
-- verification is tested separately by PHP and the bilateral Agents harness.
-- Everything, including helper functions and grants, is rolled back.
\set ON_ERROR_STOP on
BEGIN;
DO $$ BEGIN
 IF current_database()<>'imagine_crm_private_homologation' OR current_user<>'crm_homologation_owner'
 OR (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1'
 THEN RAISE EXCEPTION 'PRIVATE_EXECUTION_TEST_REQUIRED'; END IF;
 IF (SELECT count(*) FROM crm_auto_service.outbox)<2 THEN RAISE EXCEPTION 'TWO_EXISTING_OUTBOX_EVENTS_REQUIRED'; END IF;
END $$;
CREATE TEMP TABLE execution_test_sources AS
 SELECT row_number() OVER(ORDER BY company_id,event_id) AS position,*
 FROM crm_auto_service.outbox ORDER BY company_id,event_id LIMIT 2;
CREATE TEMP TABLE execution_business_before AS SELECT jsonb_build_object(
 'outbox',(SELECT jsonb_agg(to_jsonb(business_row) ORDER BY company_id,event_id) FROM crm_auto_service.outbox business_row),
 'leads',(SELECT jsonb_agg(to_jsonb(business_row) ORDER BY id) FROM public.crm_private_leads business_row),
 'tasks',(SELECT jsonb_agg(to_jsonb(business_row) ORDER BY id) FROM public.crm_private_tasks business_row),
 'runs',(SELECT jsonb_agg(to_jsonb(business_row) ORDER BY id) FROM public.crm_private_automation_runs business_row),
 'events',(SELECT jsonb_agg(to_jsonb(business_row) ORDER BY id) FROM public.crm_private_events business_row)) AS value;
INSERT INTO crm_auto_service.execution_read_grants(company_id,tenant_ref,destination,operation,active,revision,expires_at)
 SELECT DISTINCT company_id,tenant_ref,'imagine-ai-agents','event.execution.read',true,1,
 floor(extract(epoch FROM clock_timestamp()))::bigint+900 FROM execution_test_sources
 ON CONFLICT(company_id) DO UPDATE SET tenant_ref=EXCLUDED.tenant_ref,active=true,expires_at=EXCLUDED.expires_at;

CREATE FUNCTION pg_temp.execution_test_query(c jsonb) RETURNS jsonb LANGUAGE sql AS $$
 SELECT jsonb_build_object('schema','AUTO_IA_EXECUTION_QUERY_V1','issuer','imagine-crm',
 'audience','imagine-ai-agents','product','imagine-crm','operation','event.execution.read',
 'company_id',c->>'company_id','tenant_ref',c->>'tenant_ref','event_ref',c->>'event_ref','payload_hash',c->>'payload_hash',
 'request_ref','SYNTHETIC_'||upper(replace(gen_random_uuid()::text,'-','')),
 'nonce_ref','SYNTHETIC_'||upper(replace(gen_random_uuid()::text,'-','')),
 'issued_at',floor(extract(epoch FROM clock_timestamp()))::bigint,
 'expires_at',floor(extract(epoch FROM clock_timestamp()))::bigint+30,
 'key_id','SYNTHETIC_KEY_A','signature',repeat('1',64));
$$;
CREATE FUNCTION pg_temp.execution_test_status(c jsonb,q jsonb,state text,queue text,attempt integer,task text)
RETURNS jsonb LANGUAGE plpgsql AS $$
DECLARE result text:=CASE WHEN state IN ('RESULT','ACKED') THEN 'PREPARED' ELSE 'NONE' END;facts text;observation text;
BEGIN
 -- Independent explicit field order, rather than the implementation's field-list traversal.
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
CREATE FUNCTION pg_temp.execution_expect_denial(statement text,reason text) RETURNS void LANGUAGE plpgsql AS $$
BEGIN
 BEGIN EXECUTE statement;
 EXCEPTION WHEN OTHERS THEN
  IF SQLERRM=reason OR SQLSTATE=reason THEN RETURN; END IF;
  RAISE EXCEPTION 'UNEXPECTED_EXECUTION_DENIAL: %, %',SQLSTATE,SQLERRM;
 END;
 RAISE EXCEPTION 'EXPECTED_EXECUTION_DENIAL_NOT_OBSERVED: %',reason;
END $$;

DO $$
DECLARE source record;c jsonb;q jsonb;r jsonb;v jsonb;step record;before_history jsonb;
 second record;c2 jsonb;task text:=repeat('a',64);statement text;table_name text;before_business jsonb;
BEGIN
 SELECT * INTO source FROM execution_test_sources WHERE position=1;
 SELECT * INTO second FROM execution_test_sources WHERE position=2;
 IF EXISTS(SELECT 1 FROM crm_auto_service.execution_observations h JOIN execution_test_sources s USING(company_id,event_id))
 THEN RAISE EXCEPTION 'EMPTY_TEST_PROJECTIONS_REQUIRED'; END IF;
 c:=crm_auto_service.execution_candidate(source.company_id,source.event_id);
 v:=crm_auto_service.read_execution_projection(source.company_id,source.event_id);
 IF v->>'schema'<>'CRM_AGENTS_EXECUTION_VIEW_V1' OR v->'observation'<>'null'::jsonb
 OR v->'history'<>'[]'::jsonb OR (v->>'history_count')::integer<>0
 THEN RAISE EXCEPTION 'UNOBSERVED_IS_NOT_NOT_STAGED'; END IF;

 FOR step IN SELECT * FROM (VALUES ('NOT_STAGED','NONE',0,''),('NOT_STARTED','PENDING',0,task),
  ('STARTED','LEASED',1,task),('RESULT','LEASED',1,task),('ACKED','ACKED',1,task))
  AS states(state,queue,attempt,task_hash) LOOP
  q:=pg_temp.execution_test_query(c);
  r:=pg_temp.execution_test_status(c,q,step.state,step.queue,step.attempt,step.task_hash);
  v:=crm_auto_service.project_execution(source.company_id,source.event_id,q,r::text);
  IF v->'observation'->>'execution_state'<>step.state THEN RAISE EXCEPTION 'PROJECTION_DID_NOT_ADVANCE'; END IF;
  IF step.state='RESULT' THEN
   r:=pg_temp.execution_test_status(c,q,'UNKNOWN','LEASED',1,task);
   PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
    source.company_id,source.event_id,q,r::text),'EXECUTION_STATE_REGRESSION');
  END IF;
 END LOOP;
 IF (v->>'history_count')::integer<>5 OR jsonb_array_length(v->'history')<>5
 OR (v->'history'->0->>'sequence')::integer<>5 OR (v->'history'->4->>'sequence')::integer<>1
 OR (v->'observation'->>'sequence')::integer<>5
 OR v->'history'->0 ? 'receipt' OR v->'history'->0 ? 'query'
 OR v->>'crm_mutated'<>'false' OR v->>'operational_authorization'<>'false'
 THEN RAISE EXCEPTION 'EXECUTION_HISTORY_READ_MODEL'; END IF;
 SELECT jsonb_agg(to_jsonb(h) ORDER BY sequence) INTO before_history
 FROM crm_auto_service.execution_observations h WHERE company_id=source.company_id AND event_id=source.event_id;
 q:=pg_temp.execution_test_query(c);r:=pg_temp.execution_test_status(c,q,'ACKED','ACKED',1,task);
 v:=crm_auto_service.project_execution(source.company_id,source.event_id,q,r::text);
 IF (SELECT jsonb_agg(to_jsonb(h) ORDER BY sequence) FROM crm_auto_service.execution_observations h
  WHERE company_id=source.company_id AND event_id=source.event_id) IS DISTINCT FROM before_history
 OR (v->>'history_count')::integer<>5 THEN RAISE EXCEPTION 'FRESH_QUERY_CHANGED_STABLE_EVIDENCE'; END IF;

 -- A valid, fresh response for an earlier observation cannot rewind a terminal result.
 r:=pg_temp.execution_test_status(c,q,'NOT_STAGED','NONE',0,'');
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
  source.company_id,source.event_id,q,r::text),'EXECUTION_IDENTITY_DRIFT');
 r:=pg_temp.execution_test_status(c,q,'STARTED','LEASED',1,task);
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
  source.company_id,source.event_id,q,r::text),'EXECUTION_STATE_REGRESSION');
 FOR step IN SELECT * FROM (VALUES (repeat('b',64),1),(task,2)) AS changes(task_hash,attempt) LOOP
  r:=pg_temp.execution_test_status(c,q,'ACKED','ACKED',step.attempt,step.task_hash);
  PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
   source.company_id,source.event_id,q,r::text),'EXECUTION_IDENTITY_DRIFT');
 END LOOP;
 r:=pg_temp.execution_test_status(c,q,'ACKED','ACKED',1,task);
 FOR step IN SELECT * FROM (VALUES ('lead_id','00000000-0000-0000-0000-000000000000'),
  ('payload_hash',repeat('b',64)),('company_id','bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'),
  ('request_ref','SYNTHETIC_OTHER'),('nonce_ref','SYNTHETIC_OTHER'),('queue_state','LEASED')) AS changes(field,value) LOOP
  PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
   source.company_id,source.event_id,q,(r||jsonb_build_object(step.field,step.value))::text),'EXECUTION_OBSERVATION_DENIED');
 END LOOP;
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
  source.company_id,source.event_id,q,(r||jsonb_build_object('observation_hash',repeat('0',64)))::text),'EXECUTION_OBSERVATION_HASH');
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
  source.company_id,source.event_id,q,(r||jsonb_build_object('expires_at',0))::text),'EXECUTION_OBSERVATION_EXPIRED');
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
  source.company_id,source.event_id,q,'{"lead_\u0069d":"ambiguous",'||substr(r::text,2)),'EXECUTION_OBSERVATION_DENIED');
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
  source.company_id,source.event_id,q||jsonb_build_object('operation','event.receipt.read'),r::text),'EXECUTION_OBSERVATION_DENIED');

 c2:=crm_auto_service.execution_candidate(second.company_id,second.event_id);
 q:=pg_temp.execution_test_query(c2);r:=pg_temp.execution_test_status(c2,q,'NOT_STARTED','LEASED',0,task);
 PERFORM crm_auto_service.project_execution(second.company_id,second.event_id,q,r::text);
 r:=pg_temp.execution_test_status(c2,q,'NOT_STARTED','PENDING',0,task);
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
  second.company_id,second.event_id,q,r::text),'EXECUTION_QUEUE_REGRESSION');
 FOR step IN SELECT * FROM (VALUES ('STARTED','LEASED'),('UNKNOWN','LEASED'),('UNKNOWN','DEAD')) AS states(state,queue) LOOP
  q:=pg_temp.execution_test_query(c2);r:=pg_temp.execution_test_status(c2,q,step.state,step.queue,1,task);
  PERFORM crm_auto_service.project_execution(second.company_id,second.event_id,q,r::text);
 END LOOP;
 r:=pg_temp.execution_test_status(c2,q,'RESULT','DEAD',1,task);
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
  second.company_id,second.event_id,q,r::text),'EXECUTION_STATE_REGRESSION');
 r:=pg_temp.execution_test_status(c2,q,'UNKNOWN','LEASED',1,task);
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.project_execution(%L,%L,%L::jsonb,%L)',
  second.company_id,second.event_id,q,r::text),'EXECUTION_QUEUE_REGRESSION');
 v:=crm_auto_service.read_execution_projection(second.company_id,second.event_id);
 IF v->'observation'->>'execution_state'<>'UNKNOWN' OR (v->>'history_count')::integer<>4
 THEN RAISE EXCEPTION 'UNKNOWN_WAS_NOT_PRESERVED'; END IF;

 FOREACH table_name IN ARRAY ARRAY['crm_auto_service.outbox','crm_auto_service.execution_read_grants',
  'crm_auto_service.execution_observations','crm_auto_service.execution_projections',
  'public.crm_private_leads','public.crm_private_tasks','public.crm_private_events'] LOOP
  IF has_table_privilege('crm_auto_execution_reconciler',table_name,'SELECT,INSERT,UPDATE,DELETE,TRUNCATE')
  THEN RAISE EXCEPTION 'EXECUTION_ROLE_HAS_DIRECT_TABLE_ACCESS'; END IF;
 END LOOP;
 IF has_function_privilege('crm_auto_execution_reconciler','crm_auto_service.stage_outbound(uuid,uuid)','EXECUTE')
 OR has_function_privilege('crm_auto_execution_reconciler','crm_auto_service.ack_outbound(uuid,uuid,text)','EXECUTE')
 OR NOT has_function_privilege('crm_auto_execution_reconciler','crm_auto_service.project_execution(uuid,uuid,jsonb,text)','EXECUTE')
 THEN RAISE EXCEPTION 'EXECUTION_ROLE_CAPABILITY_DRIFT'; END IF;
 PERFORM pg_temp.execution_expect_denial('UPDATE crm_auto_service.execution_observations SET observed_at=observed_at','EXECUTION_HISTORY_IMMUTABLE');
 PERFORM pg_temp.execution_expect_denial('DELETE FROM crm_auto_service.execution_observations','EXECUTION_HISTORY_IMMUTABLE');
 PERFORM pg_temp.execution_expect_denial('TRUNCATE crm_auto_service.execution_observations CASCADE','EXECUTION_HISTORY_IMMUTABLE');
 PERFORM pg_temp.execution_expect_denial(format('UPDATE crm_auto_service.execution_projections SET observation_hash=%L WHERE company_id=%L AND event_id=%L',
  repeat('0',64),source.company_id,source.event_id),'23503');

 -- Context is a filter, not authentication: the role still has no direct table access.
 EXECUTE 'SET LOCAL ROLE crm_auto_reader_owner';
 PERFORM set_config('app.tenant_id','00000000-0000-0000-0000-000000000000',true);
 IF EXISTS(SELECT 1 FROM crm_auto_service.execution_observations)
 OR EXISTS(SELECT 1 FROM crm_auto_service.execution_projections)
 OR EXISTS(SELECT 1 FROM crm_auto_service.execution_read_grants) THEN RAISE EXCEPTION 'EXECUTION_RLS_ISOLATION'; END IF;
 EXECUTE 'RESET ROLE';
 UPDATE crm_auto_service.execution_read_grants SET active=false WHERE company_id=source.company_id;
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.execution_candidate(%L,%L)',
  source.company_id,source.event_id),'EXECUTION_READ_GRANT_DENIED');
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.read_execution_projection(%L,%L)',
  source.company_id,source.event_id),'EXECUTION_READ_GRANT_DENIED');
 UPDATE crm_auto_service.execution_read_grants SET active=true,expires_at=floor(extract(epoch FROM clock_timestamp()))::bigint WHERE company_id=source.company_id;
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.execution_candidate(%L,%L)',
  source.company_id,source.event_id),'EXECUTION_READ_GRANT_DENIED');
 UPDATE crm_auto_service.execution_read_grants SET expires_at=floor(extract(epoch FROM clock_timestamp()))::bigint+900 WHERE company_id=source.company_id;
 UPDATE crm_auto_service.outbound_grants SET active=false WHERE company_id=source.company_id;
 PERFORM crm_auto_service.read_execution_projection(source.company_id,source.event_id);
 UPDATE crm_auto_service.outbox SET payload=payload||chr(10) WHERE company_id=source.company_id AND event_id=source.event_id;
 PERFORM pg_temp.execution_expect_denial(format('SELECT crm_auto_service.read_execution_projection(%L,%L)',
  source.company_id,source.event_id),'EXECUTION_PROJECTION_SOURCE_DRIFT');
 UPDATE crm_auto_service.outbox SET payload=source.payload WHERE company_id=source.company_id AND event_id=source.event_id;
 SELECT value INTO before_business FROM execution_business_before;
 IF before_business IS DISTINCT FROM jsonb_build_object(
  'outbox',(SELECT jsonb_agg(to_jsonb(business_row) ORDER BY company_id,event_id) FROM crm_auto_service.outbox business_row),
  'leads',(SELECT jsonb_agg(to_jsonb(business_row) ORDER BY id) FROM public.crm_private_leads business_row),
  'tasks',(SELECT jsonb_agg(to_jsonb(business_row) ORDER BY id) FROM public.crm_private_tasks business_row),
  'runs',(SELECT jsonb_agg(to_jsonb(business_row) ORDER BY id) FROM public.crm_private_automation_runs business_row),
  'events',(SELECT jsonb_agg(to_jsonb(business_row) ORDER BY id) FROM public.crm_private_events business_row))
 THEN RAISE EXCEPTION 'EXECUTION_FEEDBACK_CHANGED_BUSINESS_OR_DELIVERY'; END IF;
END $$;
SELECT 'CRM_AGENTS_EXECUTION_SQL_PASS transitions=9 history=append-only dedup=stable unknown=preserved business_changes=0';
ROLLBACK;
