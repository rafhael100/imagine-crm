-- Optional synthetic private UI reader. Install after execution-feedback-candidate.sql.
-- The human session and membership are verified by Workspace before this call.
-- app.tenant_id is only that transaction's scope filter, never authentication.
\set ON_ERROR_STOP on
BEGIN;
SET LOCAL search_path=pg_catalog,public;
DO $$ BEGIN
 IF current_database()<>'imagine_crm_private_homologation'
 OR current_user<>'crm_homologation_owner'
 OR (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1'
 OR to_regprocedure('crm_auto_service.read_execution_projection(uuid,uuid)') IS NULL
 OR to_regclass('crm_auto_service.execution_observations') IS NULL
 OR to_regclass('crm_auto_service.execution_projections') IS NULL
 THEN RAISE EXCEPTION 'PRIVATE_EXECUTION_INSTALLATION_REQUIRED'; END IF;
END $$;
SET ROLE crm_auto_reader_owner;
CREATE FUNCTION crm_auto_service.read_execution_monitor(selected_event uuid DEFAULT NULL) RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path=pg_catalog,public AS $$
DECLARE scope text;company uuid;generated bigint;total bigint;source record;h record;
 p json;r json;j jsonb;facts jsonb;key text;source_hash text;event_ref_value text;
 pointer text;history_total bigint;position integer;latest_hash text;latest jsonb;
 history jsonb;item jsonb;items jsonb:='[]'::jsonb;selected jsonb:=NULL;
 previous_state text;previous_queue text;previous_task text;previous_attempt integer;valid boolean;
BEGIN
 scope:=current_setting('app.tenant_id',true);
 IF scope IS NULL OR scope !~ '^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$'
 OR current_database()<>'imagine_crm_private_homologation'
 OR current_user<>'crm_auto_reader_owner'
 OR current_setting('row_security')<>'on'
 OR (SELECT rolsuper OR rolbypassrls FROM pg_roles WHERE rolname=current_user) IS DISTINCT FROM false
 OR (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1'
 THEN RAISE EXCEPTION 'EXECUTION_MONITOR_CONTEXT_DENIED'; END IF;
 company:=scope::uuid;
 generated:=floor(extract(epoch FROM statement_timestamp()))::bigint;

 -- Every SELECT shares the calling statement's snapshot because this function
 -- is STABLE. No S2S grant, clock renewal, lock, DML or remote call is involved.
 IF EXISTS(SELECT 1 FROM crm_auto_service.outbox o
  LEFT JOIN public.crm_private_events e ON e.id=o.event_id AND e.organization_id=o.company_id
  LEFT JOIN public.crm_private_leads l ON l.id=e.object_id AND l.organization_id=o.company_id
  WHERE o.company_id=company AND (e.id IS NULL OR e.action<>'lead.created' OR l.id IS NULL))
 THEN RAISE EXCEPTION 'EXECUTION_MONITOR_SOURCE_DRIFT'; END IF;
 SELECT count(*) INTO total FROM crm_auto_service.outbox o
 JOIN public.crm_private_events e ON e.id=o.event_id AND e.organization_id=o.company_id AND e.action='lead.created'
 JOIN public.crm_private_leads l ON l.id=e.object_id AND l.organization_id=o.company_id
 WHERE o.company_id=company;
 IF total>2147483647 THEN RAISE EXCEPTION 'EXECUTION_MONITOR_SOURCE_DRIFT'; END IF;

 -- At most 50 list records plus one older explicitly selected own event.
 FOR source IN
  SELECT ranked.* FROM (
   SELECT o.*,e.object_id AS lead_id,e.occurred_at,l.title AS lead_title,
    row_number() OVER(ORDER BY e.occurred_at DESC,e.id DESC) AS ordinal
   FROM crm_auto_service.outbox o
   JOIN public.crm_private_events e ON e.id=o.event_id AND e.organization_id=o.company_id AND e.action='lead.created'
   JOIN public.crm_private_leads l ON l.id=e.object_id AND l.organization_id=o.company_id
   WHERE o.company_id=company
  ) ranked WHERE ranked.ordinal<=50 OR ranked.event_id=selected_event ORDER BY ranked.ordinal
 LOOP
  IF source.tenant_ref !~ '^SYNTHETIC_[A-Z0-9_]{1,48}$'
  OR source.occurred_at<0 OR source.occurred_at>4102444800
  OR source.lead_title !~ '^LEAD_TESTE_[A-Z0-9_]{1,40}$'
  OR octet_length(source.payload)>4096
  OR source.state NOT IN ('pending','accepted')
  OR (source.state='accepted' AND (source.ack_hash IS NULL OR source.ack_hash !~ '^[a-f0-9]{64}$'))
  OR (source.state='pending' AND source.ack_hash IS NOT NULL)
  THEN RAISE EXCEPTION 'EXECUTION_MONITOR_SOURCE_DRIFT'; END IF;
  BEGIN p:=source.payload::json;
  EXCEPTION WHEN invalid_text_representation THEN RAISE EXCEPTION 'EXECUTION_MONITOR_SOURCE_DRIFT'; END;
  IF json_typeof(p) IS DISTINCT FROM 'object' THEN RAISE EXCEPTION 'EXECUTION_MONITOR_SOURCE_DRIFT'; END IF;
  IF (SELECT count(*)<>5 OR count(DISTINCT name)<>5 FROM json_each(p) AS fields(name,value))
  THEN RAISE EXCEPTION 'EXECUTION_MONITOR_SOURCE_DRIFT'; END IF;
  FOREACH key IN ARRAY ARRAY['schema','action','company_id','event_id','lead_id'] LOOP
   IF json_typeof(p->key) IS DISTINCT FROM 'string' THEN RAISE EXCEPTION 'EXECUTION_MONITOR_SOURCE_DRIFT'; END IF;
  END LOOP;
  IF p->>'schema'<>'CRM_LEAD_EVENT_V1' OR p->>'action'<>'lead.created'
  OR p->>'company_id'<>company::text OR p->>'event_id'<>source.event_id::text OR p->>'lead_id'<>source.lead_id::text
  THEN RAISE EXCEPTION 'EXECUTION_MONITOR_SOURCE_DRIFT'; END IF;
  source_hash:=encode(sha256(convert_to(source.payload,'UTF8')),'hex');
  event_ref_value:='SYNTHETIC_'||upper(replace(source.event_id::text,'-',''));
  SELECT count(*) INTO history_total FROM crm_auto_service.execution_observations o
   WHERE o.company_id=company AND o.event_id=source.event_id;
  IF history_total>2147483647 THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
  SELECT projection.observation_hash INTO pointer FROM crm_auto_service.execution_projections projection
   WHERE projection.company_id=company AND projection.event_id=source.event_id;
  position:=0;latest:=NULL;latest_hash:=NULL;history:='[]'::jsonb;
  previous_state:=NULL;previous_queue:=NULL;previous_task:=NULL;previous_attempt:=0;
  FOR h IN SELECT observation.* FROM crm_auto_service.execution_observations observation
   WHERE observation.company_id=company AND observation.event_id=source.event_id ORDER BY observation.sequence
  LOOP
   position:=position+1;
   IF h.sequence<>position OR h.tenant_ref<>source.tenant_ref OR h.event_ref<>event_ref_value
   OR h.payload_hash<>source_hash OR h.lead_id<>source.lead_id
   OR h.observed_at<0 OR h.observed_at>4102444800
   OR h.receipt_hash<>encode(sha256(convert_to(h.receipt,'UTF8')),'hex')
   THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   valid:=CASE h.execution_state
    WHEN 'NOT_STAGED' THEN h.task_payload_hash='' AND h.queue_state='NONE' AND h.attempt=0 AND h.result_code='NONE'
    WHEN 'NOT_STARTED' THEN h.queue_state IN ('PENDING','LEASED','DEAD') AND h.attempt=0 AND h.result_code='NONE'
    WHEN 'STARTED' THEN h.queue_state IN ('LEASED','DEAD') AND h.attempt BETWEEN 1 AND 3 AND h.result_code='NONE'
    WHEN 'UNKNOWN' THEN h.queue_state IN ('LEASED','DEAD') AND h.attempt BETWEEN 1 AND 3 AND h.result_code='NONE'
    WHEN 'RESULT' THEN h.queue_state IN ('LEASED','DEAD') AND h.attempt BETWEEN 1 AND 3 AND h.result_code='PREPARED'
    WHEN 'ACKED' THEN h.queue_state='ACKED' AND h.attempt BETWEEN 1 AND 3 AND h.result_code='PREPARED'
    ELSE false END;
   IF NOT valid OR (h.execution_state<>'NOT_STAGED' AND h.task_payload_hash !~ '^[a-f0-9]{64}$')
   THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   IF previous_state IS NOT NULL THEN
    IF (previous_task<>'' AND h.task_payload_hash<>previous_task)
    OR (previous_attempt>0 AND h.attempt<>previous_attempt)
    THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
    valid:=CASE previous_state
     WHEN 'NOT_STAGED' THEN h.execution_state IN ('NOT_STARTED','STARTED','RESULT','ACKED','UNKNOWN')
     WHEN 'NOT_STARTED' THEN h.execution_state IN ('NOT_STARTED','STARTED','RESULT','ACKED','UNKNOWN')
     WHEN 'STARTED' THEN h.execution_state IN ('STARTED','RESULT','ACKED','UNKNOWN')
     WHEN 'RESULT' THEN h.execution_state IN ('RESULT','ACKED')
     WHEN 'UNKNOWN' THEN h.execution_state='UNKNOWN'
     ELSE false END;
    IF NOT valid THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
    valid:=CASE previous_queue
     WHEN 'NONE' THEN true
     WHEN 'PENDING' THEN h.queue_state IN ('PENDING','LEASED','ACKED','DEAD')
     WHEN 'LEASED' THEN h.queue_state IN ('LEASED','ACKED','DEAD')
     WHEN 'DEAD' THEN h.queue_state='DEAD'
     ELSE false END;
    IF NOT valid THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   END IF;
   facts:=jsonb_build_object('company_id',company,'tenant_ref',h.tenant_ref,'event_ref',h.event_ref,
    'payload_hash',h.payload_hash,'lead_id',h.lead_id,'task_payload_hash',h.task_payload_hash,
    'execution_state',h.execution_state,'queue_state',h.queue_state,'result_code',h.result_code,'attempt',h.attempt);
   IF h.observation_hash<>encode(sha256(convert_to('IMAGINE_AGENTS_EXECUTION_OBSERVATION_V1'||chr(10)||
    (SELECT string_agg(facts->>field,chr(10) ORDER BY ordinal) FROM unnest(ARRAY[
     'company_id','tenant_ref','event_ref','payload_hash','lead_id','task_payload_hash',
     'execution_state','queue_state','result_code','attempt']) WITH ORDINALITY AS fields(field,ordinal)),'UTF8')),'hex')
   THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;

   -- The authenticated reconciler preserved this wire. Verify its integrity and
   -- bindings without requiring a current signing key or a still-active S2S grant.
   BEGIN r:=h.receipt::json;
   EXCEPTION WHEN invalid_text_representation THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END;
   IF json_typeof(r) IS DISTINCT FROM 'object' THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   IF (SELECT count(*)<>20 OR count(DISTINCT name)<>20 FROM json_each(r) AS fields(name,value))
   THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   FOREACH key IN ARRAY ARRAY['schema','issuer','audience','company_id','tenant_ref','event_ref','payload_hash','lead_id',
    'task_payload_hash','execution_state','queue_state','result_code','observation_hash','request_ref','nonce_ref','key_id','signature'] LOOP
    IF json_typeof(r->key) IS DISTINCT FROM 'string' THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   END LOOP;
   FOREACH key IN ARRAY ARRAY['issued_at','expires_at','attempt'] LOOP
    IF json_typeof(r->key) IS DISTINCT FROM 'number' OR r->>key !~ '^(0|[1-9][0-9]{0,9})$'
    THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
    IF (r->>key)::bigint>4102444800 THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   END LOOP;
   j:=r::jsonb;
   FOREACH key IN ARRAY ARRAY['company_id','tenant_ref','event_ref','payload_hash','lead_id','task_payload_hash',
    'execution_state','queue_state','result_code','attempt'] LOOP
    IF j->key IS DISTINCT FROM facts->key THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   END LOOP;
   IF j->>'schema'<>'AUTO_IA_EXECUTION_STATUS_V1' OR j->>'issuer'<>'imagine-ai-agents'
   OR j->>'audience'<>'imagine-crm' OR j->>'key_id'<>'SYNTHETIC_EXECUTION_STATUS_KEY'
   OR j->>'signature' !~ '^[a-f0-9]{64}$' OR j->>'observation_hash'<>h.observation_hash
   OR jsonb_typeof(h.query) IS DISTINCT FROM 'object'
   THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   IF (SELECT count(*) FROM jsonb_object_keys(h.query))<>15 THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   FOREACH key IN ARRAY ARRAY['schema','issuer','audience','product','operation','company_id','tenant_ref','event_ref',
    'payload_hash','request_ref','nonce_ref','key_id','signature'] LOOP
    IF jsonb_typeof(h.query->key) IS DISTINCT FROM 'string' THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   END LOOP;
   FOREACH key IN ARRAY ARRAY['issued_at','expires_at'] LOOP
    IF jsonb_typeof(h.query->key) IS DISTINCT FROM 'number' OR h.query->>key !~ '^(0|[1-9][0-9]{0,9})$'
    THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
    IF (h.query->>key)::bigint>4102444800 THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   END LOOP;
   FOREACH key IN ARRAY ARRAY['company_id','tenant_ref','event_ref','payload_hash','request_ref','nonce_ref'] LOOP
    IF j->>key IS DISTINCT FROM h.query->>key THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   END LOOP;
   IF h.query->>'schema'<>'AUTO_IA_EXECUTION_QUERY_V1' OR h.query->>'issuer'<>'imagine-crm'
   OR h.query->>'audience'<>'imagine-ai-agents' OR h.query->>'product'<>'imagine-crm'
   OR h.query->>'operation'<>'event.execution.read' OR h.query->>'key_id'<>'SYNTHETIC_KEY_A'
   OR h.query->>'signature' !~ '^[a-f0-9]{64}$'
   OR h.query->>'request_ref' !~ '^SYNTHETIC_[A-Z0-9_]{1,48}$' OR h.query->>'nonce_ref' !~ '^SYNTHETIC_[A-Z0-9_]{1,48}$'
   OR (h.query->>'expires_at')::bigint<=(h.query->>'issued_at')::bigint
   OR (h.query->>'expires_at')::bigint>(h.query->>'issued_at')::bigint+60
   OR (j->>'expires_at')::bigint<=(j->>'issued_at')::bigint
   OR (j->>'expires_at')::bigint>(j->>'issued_at')::bigint+30
   OR (j->>'expires_at')::bigint>(h.query->>'expires_at')::bigint
   THEN RAISE EXCEPTION 'EXECUTION_MONITOR_HISTORY_DRIFT'; END IF;
   latest:=jsonb_build_object('sequence',h.sequence,'execution_state',h.execution_state,'queue_state',h.queue_state,
    'result_code',h.result_code,'attempt',h.attempt,'observed_at',h.observed_at);
   latest_hash:=h.observation_hash;
   -- The display window is bounded independently of validation of the whole history.
   IF source.event_id=selected_event AND h.sequence>history_total-20 THEN history:=jsonb_build_array(latest)||history; END IF;
   previous_state:=h.execution_state;previous_queue:=h.queue_state;
   previous_task:=h.task_payload_hash;previous_attempt:=h.attempt;
  END LOOP;
  IF position<>history_total OR pointer IS DISTINCT FROM latest_hash
  THEN RAISE EXCEPTION 'EXECUTION_MONITOR_PROJECTION_DRIFT'; END IF;
  item:=jsonb_build_object('event_id',source.event_id,'lead_id',source.lead_id,'lead_title',source.lead_title,
   'occurred_at',source.occurred_at,'delivery_state',source.state,'observation',latest,
   'history_count',history_total,'history','[]'::jsonb);
  IF source.ordinal<=50 THEN items:=items||jsonb_build_array(item); END IF;
  IF source.event_id=selected_event THEN selected:=item||jsonb_build_object('history',history); END IF;
 END LOOP;
 RETURN jsonb_build_object('schema','CRM_N8N_EXECUTION_MONITOR_V1','company_id',company,
  'generated_at',generated,'total_events',total,'events',items,'selected',selected);
END $$;
REVOKE ALL ON FUNCTION crm_auto_service.read_execution_monitor(uuid) FROM PUBLIC;
GRANT USAGE ON SCHEMA crm_auto_service TO crm_homologation_app;
GRANT EXECUTE ON FUNCTION crm_auto_service.read_execution_monitor(uuid) TO crm_homologation_app;
RESET ROLE;
COMMIT;
