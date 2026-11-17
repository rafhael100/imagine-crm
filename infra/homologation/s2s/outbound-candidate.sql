-- CRM P19 CI-only outbox. Never apply lab schema to a running VPS.
\set ON_ERROR_STOP on
BEGIN;
GRANT SELECT ON public.crm_private_leads TO crm_auto_reader_owner;
SET ROLE crm_auto_reader_owner;
CREATE TABLE crm_auto_service.outbound_grants(
 company_id uuid PRIMARY KEY, tenant_ref text NOT NULL UNIQUE CHECK(tenant_ref ~ '^SYNTHETIC_[A-Z0-9_]{1,48}$'),
 destination text NOT NULL CHECK(destination='imagine-ai-agents'), operation text NOT NULL CHECK(operation='lead.ingest'),
 active boolean NOT NULL, revision integer NOT NULL CHECK(revision>0), expires_at bigint NOT NULL);
CREATE TABLE crm_auto_service.outbox(
 company_id uuid NOT NULL,event_id uuid NOT NULL,tenant_ref text NOT NULL,
 nonce_ref text NOT NULL,request_ref text NOT NULL,payload text NOT NULL,
 issued_at bigint NOT NULL,expires_at bigint NOT NULL,state text NOT NULL DEFAULT 'pending' CHECK(state IN ('pending','accepted')),
 ack_hash varchar(64), PRIMARY KEY(company_id,event_id));
ALTER TABLE crm_auto_service.outbound_grants ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.outbound_grants FORCE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.outbox ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.outbox FORCE ROW LEVEL SECURITY;
CREATE POLICY tenant_scope ON crm_auto_service.outbound_grants USING(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid) WITH CHECK(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);
CREATE POLICY tenant_scope ON crm_auto_service.outbox USING(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid) WITH CHECK(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);
CREATE FUNCTION crm_auto_service.stage_outbound(company uuid,event uuid) RETURNS jsonb LANGUAGE plpgsql SECURITY DEFINER SET search_path=pg_catalog,public AS $$
DECLARE g record;e record;o record;n bigint;
BEGIN
 IF current_database()<>'imagine_crm_private_homologation' OR (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1' THEN RAISE EXCEPTION 'PRIVATE_INSTALLATION_REQUIRED'; END IF;
 n:=floor(extract(epoch FROM clock_timestamp()))::bigint;PERFORM set_config('app.tenant_id',company::text,true);
 SELECT * INTO g FROM crm_auto_service.outbound_grants WHERE company_id=company FOR SHARE;
 IF NOT FOUND OR NOT g.active OR g.expires_at<=n THEN RAISE EXCEPTION 'OUTBOUND_GRANT_DENIED'; END IF;
 SELECT * INTO e FROM public.crm_private_events WHERE id=event AND organization_id=company AND action='lead.created';
 IF NOT FOUND OR NOT EXISTS(SELECT 1 FROM public.crm_private_leads WHERE id=e.object_id AND organization_id=company) THEN RAISE EXCEPTION 'SOURCE_EVENT_DENIED'; END IF;
 INSERT INTO crm_auto_service.outbox(company_id,event_id,tenant_ref,nonce_ref,request_ref,payload,issued_at,expires_at)
 VALUES(company,event,g.tenant_ref,'SYNTHETIC_'||upper(replace(gen_random_uuid()::text,'-','')),'SYNTHETIC_'||upper(replace(gen_random_uuid()::text,'-','')),
 jsonb_build_object('schema','CRM_LEAD_EVENT_V1','action','lead.created','company_id',company,'event_id',event,'lead_id',e.object_id)::text,n,n+120) ON CONFLICT DO NOTHING;
 SELECT * INTO o FROM crm_auto_service.outbox WHERE company_id=company AND event_id=event FOR UPDATE;
 -- Waiting for this row must not preserve authority or a transport window
 -- that expired while the caller was blocked. Keep the original envelope.
 n:=floor(extract(epoch FROM clock_timestamp()))::bigint;
 IF g.expires_at<=n THEN RAISE EXCEPTION 'OUTBOUND_GRANT_DENIED'; END IF;
 IF o.tenant_ref<>g.tenant_ref THEN RAISE EXCEPTION 'OUTBOX_SCOPE_OR_WINDOW_DENIED'; END IF;
 -- A verified receipt is durable. Its original transport window only limits pending sends.
 -- Current source grant, event and tenant checks above still apply to accepted replays.
 IF o.state='accepted' THEN
  IF o.ack_hash IS NULL OR o.ack_hash !~ '^[a-f0-9]{64}$' THEN RAISE EXCEPTION 'OUTBOX_RECEIPT_DENIED'; END IF;
 ELSIF o.expires_at<=n THEN
  -- A missing ACK does not prove non-delivery: never renew the envelope automatically.
  RAISE EXCEPTION 'OUTBOX_SCOPE_OR_WINDOW_DENIED';
 END IF;
 RETURN to_jsonb(o);
END $$;
-- Separate, explicit read path for a pending envelope whose delivery window expired.
-- Expiry never authorizes a resend. This function does not create or renew anything.
CREATE FUNCTION crm_auto_service.receipt_candidate(company uuid,event uuid) RETURNS jsonb LANGUAGE plpgsql SECURITY DEFINER SET search_path=pg_catalog,public AS $$
DECLARE g record;e record;o record;n bigint;
BEGIN
 IF current_database()<>'imagine_crm_private_homologation' OR (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1' THEN RAISE EXCEPTION 'PRIVATE_INSTALLATION_REQUIRED'; END IF;
 PERFORM set_config('app.tenant_id',company::text,true);
 SELECT * INTO g FROM crm_auto_service.outbound_grants WHERE company_id=company FOR SHARE;
 n:=floor(extract(epoch FROM clock_timestamp()))::bigint;
 IF NOT FOUND OR NOT g.active OR g.expires_at<=n THEN RAISE EXCEPTION 'OUTBOUND_GRANT_DENIED'; END IF;
 SELECT * INTO e FROM public.crm_private_events WHERE id=event AND organization_id=company AND action='lead.created';
 IF NOT FOUND OR NOT EXISTS(SELECT 1 FROM public.crm_private_leads WHERE id=e.object_id AND organization_id=company) THEN RAISE EXCEPTION 'SOURCE_EVENT_DENIED'; END IF;
 SELECT * INTO o FROM crm_auto_service.outbox WHERE company_id=company AND event_id=event FOR UPDATE;
 IF NOT FOUND THEN RAISE EXCEPTION 'OUTBOX_RECEIPT_DENIED'; END IF;
 IF o.tenant_ref<>g.tenant_ref THEN RAISE EXCEPTION 'OUTBOX_SCOPE_OR_WINDOW_DENIED'; END IF;
 IF (o.state='accepted' AND (o.ack_hash IS NULL OR o.ack_hash !~ '^[a-f0-9]{64}$'))
 OR (o.state='pending' AND o.ack_hash IS NOT NULL) THEN RAISE EXCEPTION 'OUTBOX_RECEIPT_DENIED'; END IF;
 -- Recheck the grant after waiting for the row, not only before the lock.
 IF g.expires_at<=floor(extract(epoch FROM clock_timestamp()))::bigint THEN RAISE EXCEPTION 'OUTBOUND_GRANT_DENIED'; END IF;
 RETURN to_jsonb(o);
END $$;
CREATE FUNCTION crm_auto_service.ack_outbound(company uuid,event uuid,ack text) RETURNS void LANGUAGE plpgsql SECURITY DEFINER SET search_path=pg_catalog,public AS $$
BEGIN
 PERFORM set_config('app.tenant_id',company::text,true);
 IF ack IS NULL OR ack !~ '^[a-f0-9]{64}$' OR NOT EXISTS(SELECT 1 FROM crm_auto_service.outbound_grants WHERE company_id=company AND active AND expires_at>floor(extract(epoch FROM clock_timestamp()))::bigint) THEN RAISE EXCEPTION 'OUTBOUND_ACK_DENIED'; END IF;
 UPDATE crm_auto_service.outbox SET state='accepted',ack_hash=ack WHERE company_id=company AND event_id=event AND state='pending';
END $$;
RESET ROLE;
CREATE ROLE crm_auto_sender NOLOGIN NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE;
REVOKE ALL ON FUNCTION crm_auto_service.stage_outbound(uuid,uuid),crm_auto_service.receipt_candidate(uuid,uuid),crm_auto_service.ack_outbound(uuid,uuid,text) FROM PUBLIC;
GRANT USAGE ON SCHEMA crm_auto_service TO crm_auto_sender;
GRANT EXECUTE ON FUNCTION crm_auto_service.stage_outbound(uuid,uuid),crm_auto_service.receipt_candidate(uuid,uuid),crm_auto_service.ack_outbound(uuid,uuid,text) TO crm_auto_sender;
COMMIT;
