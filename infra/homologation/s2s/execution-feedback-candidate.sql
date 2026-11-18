-- Optional, synthetic private CRM only. No worker, resend or business mutation is installed.
\set ON_ERROR_STOP on
BEGIN;
SET LOCAL search_path=pg_catalog,public;
DO $$ BEGIN
 IF current_database()<>'imagine_crm_private_homologation'
 OR current_user<>'crm_homologation_owner'
 OR (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1'
 THEN RAISE EXCEPTION 'PRIVATE_INSTALLATION_REQUIRED'; END IF;
END $$;
SET ROLE crm_auto_reader_owner;
CREATE TABLE crm_auto_service.execution_read_grants(
 company_id uuid PRIMARY KEY,
 tenant_ref text NOT NULL UNIQUE CHECK(tenant_ref ~ '^SYNTHETIC_[A-Z0-9_]{1,48}$'),
 destination text NOT NULL CHECK(destination='imagine-ai-agents'),
 operation text NOT NULL CHECK(operation='event.execution.read'),
 active boolean NOT NULL,revision integer NOT NULL CHECK(revision>0),expires_at bigint NOT NULL);
CREATE TABLE crm_auto_service.execution_observations(
 company_id uuid NOT NULL,event_id uuid NOT NULL,
 observation_hash text NOT NULL CHECK(observation_hash ~ '^[a-f0-9]{64}$'),
 sequence integer NOT NULL CHECK(sequence>0),
 tenant_ref text NOT NULL CHECK(tenant_ref ~ '^SYNTHETIC_[A-Z0-9_]{1,48}$'),
 event_ref text NOT NULL CHECK(event_ref ~ '^SYNTHETIC_[A-Z0-9_]{1,48}$'),
 payload_hash text NOT NULL CHECK(payload_hash ~ '^[a-f0-9]{64}$'),lead_id uuid NOT NULL,
 task_payload_hash text NOT NULL,
 execution_state text NOT NULL CHECK(execution_state IN ('NOT_STAGED','NOT_STARTED','STARTED','RESULT','ACKED','UNKNOWN')),
 queue_state text NOT NULL CHECK(queue_state IN ('NONE','PENDING','LEASED','ACKED','DEAD')),
 result_code text NOT NULL CHECK(result_code IN ('NONE','PREPARED')),
 attempt smallint NOT NULL CHECK(attempt BETWEEN 0 AND 3),
 query jsonb NOT NULL,receipt text NOT NULL CHECK(octet_length(receipt)<=4096),
 receipt_hash text NOT NULL CHECK(receipt_hash ~ '^[a-f0-9]{64}$'),
 grant_revision integer NOT NULL CHECK(grant_revision>0),observed_at bigint NOT NULL,
 PRIMARY KEY(company_id,event_id,observation_hash),
 UNIQUE(company_id,event_id,sequence),
 FOREIGN KEY(company_id,event_id) REFERENCES crm_auto_service.outbox(company_id,event_id),
 CHECK((execution_state='NOT_STAGED' AND task_payload_hash='' AND queue_state='NONE' AND attempt=0 AND result_code='NONE')
 OR (task_payload_hash ~ '^[a-f0-9]{64}$' AND (
   (execution_state='NOT_STARTED' AND queue_state IN ('PENDING','LEASED','DEAD') AND attempt=0 AND result_code='NONE')
   OR (execution_state IN ('STARTED','UNKNOWN') AND queue_state IN ('LEASED','DEAD') AND attempt BETWEEN 1 AND 3 AND result_code='NONE')
   OR (execution_state='RESULT' AND queue_state IN ('LEASED','DEAD') AND attempt BETWEEN 1 AND 3 AND result_code='PREPARED')
   OR (execution_state='ACKED' AND queue_state='ACKED' AND attempt BETWEEN 1 AND 3 AND result_code='PREPARED')))));
CREATE TABLE crm_auto_service.execution_projections(
 company_id uuid NOT NULL,event_id uuid NOT NULL,observation_hash text NOT NULL,
 PRIMARY KEY(company_id,event_id),
 FOREIGN KEY(company_id,event_id) REFERENCES crm_auto_service.outbox(company_id,event_id),
 FOREIGN KEY(company_id,event_id,observation_hash)
 REFERENCES crm_auto_service.execution_observations(company_id,event_id,observation_hash));
ALTER TABLE crm_auto_service.execution_read_grants ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.execution_read_grants FORCE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.execution_observations ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.execution_observations FORCE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.execution_projections ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.execution_projections FORCE ROW LEVEL SECURITY;
CREATE POLICY tenant_scope ON crm_auto_service.execution_read_grants USING(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid) WITH CHECK(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);
CREATE POLICY tenant_scope ON crm_auto_service.execution_observations USING(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid) WITH CHECK(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);
CREATE POLICY tenant_scope ON crm_auto_service.execution_projections USING(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid) WITH CHECK(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);

CREATE FUNCTION crm_auto_service.reject_execution_observation_mutation() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path=pg_catalog,public AS $$
BEGIN RAISE EXCEPTION 'EXECUTION_HISTORY_IMMUTABLE'; END $$;
CREATE TRIGGER execution_history_immutable BEFORE UPDATE OR DELETE ON crm_auto_service.execution_observations
FOR EACH ROW EXECUTE FUNCTION crm_auto_service.reject_execution_observation_mutation();
CREATE TRIGGER execution_history_no_truncate BEFORE TRUNCATE ON crm_auto_service.execution_observations
FOR EACH STATEMENT EXECUTE FUNCTION crm_auto_service.reject_execution_observation_mutation();

CREATE FUNCTION crm_auto_service.execution_candidate(company uuid,event uuid) RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER SET search_path=pg_catalog,public AS $$
DECLARE g record;o record;e record;p json;k text;n bigint;
BEGIN
 IF company IS NULL OR event IS NULL OR current_database()<>'imagine_crm_private_homologation'
 OR (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1'
 THEN RAISE EXCEPTION 'PRIVATE_INSTALLATION_REQUIRED'; END IF;
 PERFORM set_config('app.tenant_id',company::text,true);
 SELECT * INTO g FROM crm_auto_service.execution_read_grants WHERE company_id=company FOR SHARE;
 n:=floor(extract(epoch FROM clock_timestamp()))::bigint;
 IF NOT FOUND OR NOT g.active OR g.expires_at<=n THEN RAISE EXCEPTION 'EXECUTION_READ_GRANT_DENIED'; END IF;
 -- A row lock serializes the complete HTTP observation, not just its final INSERT.
 -- Both pending and accepted admissions may be observed; neither is acknowledged here.
 SELECT * INTO o FROM crm_auto_service.outbox WHERE company_id=company AND event_id=event FOR UPDATE;
 IF NOT FOUND OR o.tenant_ref<>g.tenant_ref THEN RAISE EXCEPTION 'EXECUTION_SOURCE_DENIED'; END IF;
 SELECT * INTO e FROM public.crm_private_events WHERE id=event AND organization_id=company AND action='lead.created';
 IF NOT FOUND OR NOT EXISTS(SELECT 1 FROM public.crm_private_leads WHERE id=e.object_id AND organization_id=company)
 THEN RAISE EXCEPTION 'EXECUTION_SOURCE_DENIED'; END IF;
 BEGIN p:=o.payload::json;
 EXCEPTION WHEN invalid_text_representation THEN RAISE EXCEPTION 'EXECUTION_SOURCE_DENIED'; END;
 IF json_typeof(p) IS DISTINCT FROM 'object' THEN RAISE EXCEPTION 'EXECUTION_SOURCE_DENIED'; END IF;
 IF (SELECT count(*)<>5 OR count(DISTINCT key)<>5 FROM json_each(p)) THEN RAISE EXCEPTION 'EXECUTION_SOURCE_DENIED'; END IF;
 FOREACH k IN ARRAY ARRAY['schema','action','company_id','event_id','lead_id'] LOOP
  IF json_typeof(p->k) IS DISTINCT FROM 'string' THEN RAISE EXCEPTION 'EXECUTION_SOURCE_DENIED'; END IF;
 END LOOP;
 IF p->>'schema'<>'CRM_LEAD_EVENT_V1' OR p->>'action'<>'lead.created'
 OR p->>'company_id'<>company::text OR p->>'event_id'<>event::text OR p->>'lead_id'<>e.object_id::text
 OR (o.state='accepted' AND (o.ack_hash IS NULL OR o.ack_hash !~ '^[a-f0-9]{64}$'))
 OR (o.state='pending' AND o.ack_hash IS NOT NULL)
 THEN RAISE EXCEPTION 'EXECUTION_SOURCE_DENIED'; END IF;
 IF g.expires_at<=floor(extract(epoch FROM clock_timestamp()))::bigint THEN RAISE EXCEPTION 'EXECUTION_READ_GRANT_DENIED'; END IF;
 RETURN jsonb_build_object('company_id',company,'event_id',event,'tenant_ref',o.tenant_ref,
  'event_ref','SYNTHETIC_'||upper(replace(event::text,'-','')),
  'payload_hash',encode(sha256(convert_to(o.payload,'UTF8')),'hex'),'lead_id',e.object_id,
  'delivery_state',o.state,'grant_revision',g.revision);
END $$;

CREATE FUNCTION crm_auto_service.read_execution_projection(company uuid,event uuid) RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER SET search_path=pg_catalog,public AS $$
DECLARE c jsonb;h record;observation jsonb:=NULL;history jsonb;total bigint;
BEGIN
 c:=crm_auto_service.execution_candidate(company,event);
 SELECT observation.* INTO h FROM crm_auto_service.execution_projections p
 JOIN crm_auto_service.execution_observations observation USING(company_id,event_id,observation_hash)
 WHERE p.company_id=company AND p.event_id=event;
 IF FOUND THEN
  IF h.tenant_ref<>c->>'tenant_ref' OR h.event_ref<>c->>'event_ref'
  OR h.payload_hash<>c->>'payload_hash' OR h.lead_id::text<>c->>'lead_id'
  THEN RAISE EXCEPTION 'EXECUTION_PROJECTION_SOURCE_DRIFT'; END IF;
  observation:=jsonb_build_object('sequence',h.sequence,'execution_state',h.execution_state,'queue_state',h.queue_state,
   'result_code',h.result_code,'task_payload_hash',h.task_payload_hash,'attempt',h.attempt,
   'observation_hash',h.observation_hash,'receipt_hash',h.receipt_hash,
   'observed_at',h.observed_at,'grant_revision',h.grant_revision);
 END IF;
 SELECT count(*) INTO total FROM crm_auto_service.execution_observations WHERE company_id=company AND event_id=event;
 SELECT COALESCE(jsonb_agg(jsonb_build_object('sequence',item.sequence,
  'execution_state',item.execution_state,'queue_state',item.queue_state,'result_code',item.result_code,
  'task_payload_hash',item.task_payload_hash,'attempt',item.attempt,'observation_hash',item.observation_hash,
  'receipt_hash',item.receipt_hash,'observed_at',item.observed_at,'grant_revision',item.grant_revision)
  ORDER BY item.sequence DESC),'[]'::jsonb) INTO history
 FROM (SELECT * FROM crm_auto_service.execution_observations WHERE company_id=company AND event_id=event
  ORDER BY sequence DESC LIMIT 20) item;
 RETURN (c-'grant_revision')||jsonb_build_object('schema','CRM_AGENTS_EXECUTION_VIEW_V1',
  'observation',observation,'history',history,'history_count',total,'crm_mutated',false,'operational_authorization',false);
END $$;

-- EXECUTE belongs only to the authenticated PHP verifier. SQL independently enforces
-- source identity, closed shape, stable facts, freshness, idempotence and transition order.
CREATE FUNCTION crm_auto_service.project_execution(company uuid,event uuid,request jsonb,wire text) RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER SET search_path=pg_catalog,public AS $$
DECLARE c jsonb;r json;j jsonb;k text;n bigint;h record;previous boolean;computed text;valid boolean;next_sequence integer;
BEGIN
 c:=crm_auto_service.execution_candidate(company,event);
 IF request IS NULL OR jsonb_typeof(request) IS DISTINCT FROM 'object'
 OR (SELECT count(*) FROM jsonb_object_keys(request))<>15
 OR wire IS NULL OR octet_length(wire)>4096 THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 FOREACH k IN ARRAY ARRAY['schema','issuer','audience','product','operation','company_id','tenant_ref','event_ref',
  'payload_hash','request_ref','nonce_ref','key_id','signature'] LOOP
  IF jsonb_typeof(request->k) IS DISTINCT FROM 'string' THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 END LOOP;
 FOREACH k IN ARRAY ARRAY['issued_at','expires_at'] LOOP
  IF jsonb_typeof(request->k) IS DISTINCT FROM 'number' OR request->>k !~ '^(0|[1-9][0-9]{0,9})$'
  THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
  IF (request->>k)::bigint>4102444800 THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 END LOOP;
 IF request->>'schema'<>'AUTO_IA_EXECUTION_QUERY_V1' OR request->>'issuer'<>'imagine-crm'
 OR request->>'audience'<>'imagine-ai-agents' OR request->>'product'<>'imagine-crm'
 OR request->>'operation'<>'event.execution.read' OR request->>'key_id'<>'SYNTHETIC_KEY_A'
 OR request->>'signature' !~ '^[a-f0-9]{64}$'
 OR request->>'request_ref' !~ '^SYNTHETIC_[A-Z0-9_]{1,48}$'
 OR request->>'nonce_ref' !~ '^SYNTHETIC_[A-Z0-9_]{1,48}$'
 THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 BEGIN r:=wire::json;
 EXCEPTION WHEN invalid_text_representation THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END;
 IF json_typeof(r) IS DISTINCT FROM 'object' THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 IF (SELECT count(*)<>20 OR count(DISTINCT key)<>20 FROM json_each(r)) THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 FOREACH k IN ARRAY ARRAY['schema','issuer','audience','company_id','tenant_ref','event_ref','payload_hash','lead_id',
  'task_payload_hash','execution_state','queue_state','result_code','observation_hash','request_ref','nonce_ref','key_id','signature'] LOOP
  IF json_typeof(r->k) IS DISTINCT FROM 'string' THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 END LOOP;
 FOREACH k IN ARRAY ARRAY['issued_at','expires_at','attempt'] LOOP
  IF json_typeof(r->k) IS DISTINCT FROM 'number' OR r->>k !~ '^(0|[1-9][0-9]{0,9})$'
  THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
  IF (r->>k)::bigint>4102444800 THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 END LOOP;
 j:=r::jsonb;
 IF j->>'schema'<>'AUTO_IA_EXECUTION_STATUS_V1' OR j->>'issuer'<>'imagine-ai-agents'
 OR j->>'audience'<>'imagine-crm' OR j->>'key_id'<>'SYNTHETIC_EXECUTION_STATUS_KEY'
 OR j->>'signature' !~ '^[a-f0-9]{64}$' OR j->>'observation_hash' !~ '^[a-f0-9]{64}$'
 OR j->>'lead_id'<>c->>'lead_id'
 THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 FOREACH k IN ARRAY ARRAY['company_id','tenant_ref','event_ref','payload_hash'] LOOP
  IF request->>k<>c->>k OR j->>k<>c->>k THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 END LOOP;
 FOREACH k IN ARRAY ARRAY['request_ref','nonce_ref'] LOOP
  IF request->>k<>j->>k THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 END LOOP;
 n:=floor(extract(epoch FROM clock_timestamp()))::bigint;
 IF (request->>'issued_at')::bigint<n-30 OR (request->>'issued_at')::bigint>n+5
 OR (request->>'expires_at')::bigint<=n OR (request->>'expires_at')::bigint<=(request->>'issued_at')::bigint
 OR (request->>'expires_at')::bigint>(request->>'issued_at')::bigint+60
 OR (j->>'issued_at')::bigint<n-10 OR (j->>'issued_at')::bigint>n+5
 OR (j->>'expires_at')::bigint<=n OR (j->>'expires_at')::bigint<=(j->>'issued_at')::bigint
 OR (j->>'expires_at')::bigint>(j->>'issued_at')::bigint+30
 OR (j->>'expires_at')::bigint>(request->>'expires_at')::bigint
 THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_EXPIRED'; END IF;
 valid:=CASE j->>'execution_state'
  WHEN 'NOT_STAGED' THEN j->>'task_payload_hash'='' AND j->>'queue_state'='NONE' AND (j->>'attempt')::bigint=0 AND j->>'result_code'='NONE'
  WHEN 'NOT_STARTED' THEN j->>'queue_state' IN ('PENDING','LEASED','DEAD') AND (j->>'attempt')::bigint=0 AND j->>'result_code'='NONE'
  WHEN 'STARTED' THEN j->>'queue_state' IN ('LEASED','DEAD') AND (j->>'attempt')::bigint BETWEEN 1 AND 3 AND j->>'result_code'='NONE'
  WHEN 'UNKNOWN' THEN j->>'queue_state' IN ('LEASED','DEAD') AND (j->>'attempt')::bigint BETWEEN 1 AND 3 AND j->>'result_code'='NONE'
  WHEN 'RESULT' THEN j->>'queue_state' IN ('LEASED','DEAD') AND (j->>'attempt')::bigint BETWEEN 1 AND 3 AND j->>'result_code'='PREPARED'
  WHEN 'ACKED' THEN j->>'queue_state'='ACKED' AND (j->>'attempt')::bigint BETWEEN 1 AND 3 AND j->>'result_code'='PREPARED'
  ELSE false END;
 IF NOT valid OR (j->>'execution_state'<>'NOT_STAGED' AND j->>'task_payload_hash' !~ '^[a-f0-9]{64}$')
 THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_DENIED'; END IF;
 computed:=encode(sha256(convert_to('IMAGINE_AGENTS_EXECUTION_OBSERVATION_V1'||chr(10)||
  (SELECT string_agg(j->>field,chr(10) ORDER BY ordinal) FROM unnest(ARRAY[
   'company_id','tenant_ref','event_ref','payload_hash','lead_id','task_payload_hash',
   'execution_state','queue_state','result_code','attempt']) WITH ORDINALITY AS facts(field,ordinal)),'UTF8')),'hex');
 IF j->>'observation_hash'<>computed THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_HASH'; END IF;
 SELECT observation.* INTO h FROM crm_auto_service.execution_projections p
 JOIN crm_auto_service.execution_observations observation USING(company_id,event_id,observation_hash)
 WHERE p.company_id=company AND p.event_id=event;
 previous:=FOUND;
 IF previous THEN
  IF h.tenant_ref<>c->>'tenant_ref' OR h.event_ref<>c->>'event_ref'
  OR h.payload_hash<>c->>'payload_hash' OR h.lead_id::text<>c->>'lead_id'
  THEN RAISE EXCEPTION 'EXECUTION_PROJECTION_SOURCE_DRIFT'; END IF;
  IF h.observation_hash=computed THEN
   -- Fresh transport metadata is not a new fact. Preserve the original signed evidence.
   RETURN crm_auto_service.read_execution_projection(company,event);
  END IF;
  IF (h.task_payload_hash<>'' AND h.task_payload_hash<>j->>'task_payload_hash')
  OR (h.attempt>0 AND h.attempt<>(j->>'attempt')::bigint)
  THEN RAISE EXCEPTION 'EXECUTION_IDENTITY_DRIFT'; END IF;
  valid:=CASE h.execution_state
   WHEN 'NOT_STAGED' THEN j->>'execution_state' IN ('NOT_STARTED','STARTED','RESULT','ACKED','UNKNOWN')
   WHEN 'NOT_STARTED' THEN j->>'execution_state' IN ('NOT_STARTED','STARTED','RESULT','ACKED','UNKNOWN')
   WHEN 'STARTED' THEN j->>'execution_state' IN ('STARTED','RESULT','ACKED','UNKNOWN')
   WHEN 'RESULT' THEN j->>'execution_state' IN ('RESULT','ACKED')
   WHEN 'UNKNOWN' THEN j->>'execution_state'='UNKNOWN'
   ELSE false END;
  IF NOT valid THEN RAISE EXCEPTION 'EXECUTION_STATE_REGRESSION'; END IF;
  valid:=CASE h.queue_state
   WHEN 'NONE' THEN true
   WHEN 'PENDING' THEN j->>'queue_state' IN ('PENDING','LEASED','ACKED','DEAD')
   WHEN 'LEASED' THEN j->>'queue_state' IN ('LEASED','ACKED','DEAD')
   WHEN 'DEAD' THEN j->>'queue_state'='DEAD'
   ELSE false END;
  IF NOT valid THEN RAISE EXCEPTION 'EXECUTION_QUEUE_REGRESSION'; END IF;
 END IF;
 IF EXISTS(SELECT 1 FROM crm_auto_service.execution_observations
  WHERE company_id=company AND event_id=event AND observation_hash=computed)
 THEN RAISE EXCEPTION 'EXECUTION_STATE_REGRESSION'; END IF;
 SELECT count(*)+1 INTO next_sequence FROM crm_auto_service.execution_observations WHERE company_id=company AND event_id=event;
 INSERT INTO crm_auto_service.execution_observations(company_id,event_id,observation_hash,sequence,
  tenant_ref,event_ref,payload_hash,lead_id,task_payload_hash,execution_state,queue_state,result_code,
  attempt,query,receipt,receipt_hash,grant_revision,observed_at)
 VALUES(company,event,computed,next_sequence,j->>'tenant_ref',j->>'event_ref',j->>'payload_hash',(j->>'lead_id')::uuid,
  j->>'task_payload_hash',j->>'execution_state',j->>'queue_state',j->>'result_code',(j->>'attempt')::smallint,
  request,wire,encode(sha256(convert_to(wire,'UTF8')),'hex'),(c->>'grant_revision')::integer,n);
 INSERT INTO crm_auto_service.execution_projections VALUES(company,event,computed)
 ON CONFLICT(company_id,event_id) DO UPDATE SET observation_hash=EXCLUDED.observation_hash;
 -- Test hooks or database contention must not extend the response or grant lifetime.
 n:=floor(extract(epoch FROM clock_timestamp()))::bigint;
 IF (j->>'expires_at')::bigint<=n OR (request->>'expires_at')::bigint<=n
 OR (j->>'issued_at')::bigint<n-10 OR (request->>'issued_at')::bigint<n-30
 THEN RAISE EXCEPTION 'EXECUTION_OBSERVATION_EXPIRED'; END IF;
 RETURN crm_auto_service.read_execution_projection(company,event);
END $$;
REVOKE ALL ON crm_auto_service.execution_read_grants,crm_auto_service.execution_observations,
 crm_auto_service.execution_projections FROM PUBLIC;
REVOKE ALL ON FUNCTION crm_auto_service.execution_candidate(uuid,uuid),
 crm_auto_service.project_execution(uuid,uuid,jsonb,text),crm_auto_service.read_execution_projection(uuid,uuid),
 crm_auto_service.reject_execution_observation_mutation() FROM PUBLIC;
RESET ROLE;
CREATE ROLE crm_auto_execution_reconciler NOLOGIN NOINHERIT NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE;
GRANT USAGE ON SCHEMA crm_auto_service TO crm_auto_execution_reconciler;
GRANT EXECUTE ON FUNCTION crm_auto_service.execution_candidate(uuid,uuid),
 crm_auto_service.project_execution(uuid,uuid,jsonb,text),crm_auto_service.read_execution_projection(uuid,uuid)
 TO crm_auto_execution_reconciler;
COMMIT;
