-- Dedicated synthetic staging ONLY; never apply without schema/grants review.
\set ON_ERROR_STOP on
BEGIN;
SET LOCAL search_path=pg_catalog,public;
DO $$ BEGIN
 IF current_database()<>'imagine_crm_private_homologation'
 OR current_user<>'crm_homologation_owner'
 OR (SELECT mode FROM crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1'
 THEN RAISE EXCEPTION 'PRIVATE_INSTALLATION_REQUIRED'; END IF;
END $$;
CREATE ROLE crm_auto_reader_owner NOLOGIN NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE;
CREATE ROLE crm_auto_transport NOLOGIN NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE;
CREATE SCHEMA crm_auto_service AUTHORIZATION crm_auto_reader_owner;
GRANT USAGE ON SCHEMA public TO crm_auto_reader_owner;
GRANT SELECT ON crm_private_installation,crm_private_automation_runs,crm_private_preferences,crm_private_events,crm_private_trials TO crm_auto_reader_owner;
SET ROLE crm_auto_reader_owner;
CREATE TABLE crm_auto_service.grants(
 company_id uuid NOT NULL, fingerprint varchar(64) NOT NULL CHECK(fingerprint ~ '^[a-f0-9]{64}$'),
 issuer text NOT NULL CHECK(issuer='portal-automation-admin'),
 operation text NOT NULL CHECK(operation='automation.state.read'),
 revision integer NOT NULL CHECK(revision>0), active boolean NOT NULL,
 expires_at bigint NOT NULL, PRIMARY KEY(company_id,fingerprint,operation));
CREATE TABLE crm_auto_service.nonces(
 company_id uuid NOT NULL, fingerprint varchar(64) NOT NULL, request_id uuid NOT NULL,
 nonce varchar(64) NOT NULL, digest varchar(64) NOT NULL, issued_at bigint NOT NULL,
 PRIMARY KEY(company_id,fingerprint,nonce), UNIQUE(company_id,fingerprint,request_id));
CREATE TABLE crm_auto_service.audit(
 id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY, company_id uuid NOT NULL,
 fingerprint varchar(64) NOT NULL, request_id uuid NOT NULL, outcome text NOT NULL,
 observed_at bigint NOT NULL, grant_revision integer);
ALTER TABLE crm_auto_service.grants ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.grants FORCE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.nonces ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.nonces FORCE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.audit ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_auto_service.audit FORCE ROW LEVEL SECURITY;
CREATE POLICY tenant_scope ON crm_auto_service.grants USING(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid) WITH CHECK(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);
CREATE POLICY tenant_scope ON crm_auto_service.nonces USING(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid) WITH CHECK(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);
CREATE POLICY tenant_scope ON crm_auto_service.audit USING(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid) WITH CHECK(company_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);
CREATE FUNCTION crm_auto_service.read_state(company uuid,fp text,issuer_arg text,operation_arg text,req uuid,nonce_arg text,digest_arg text,issued bigint)
RETURNS jsonb LANGUAGE plpgsql SECURITY DEFINER SET search_path=pg_catalog,public AS $$
DECLARE g record; v_now bigint; result jsonb; settings integer; total integer;
BEGIN
 IF current_database()<>'imagine_crm_private_homologation' OR (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1' THEN RAISE EXCEPTION 'PRIVATE_INSTALLATION_REQUIRED'; END IF;
 v_now:=extract(epoch FROM clock_timestamp())::bigint;
 IF issuer_arg<>'portal-automation-admin' OR operation_arg<>'automation.state.read'
 OR fp !~ '^[a-f0-9]{64}$' OR nonce_arg !~ '^[a-f0-9]{64}$' OR digest_arg !~ '^[a-f0-9]{64}$'
 OR issued<v_now-60 OR issued>v_now+5 THEN RETURN jsonb_build_object('decision','DENIED'); END IF;
 PERFORM set_config('app.tenant_id',company::text,true);
 SELECT * INTO g FROM crm_auto_service.grants WHERE company_id=company AND fingerprint=fp AND operation=operation_arg FOR SHARE;
 IF NOT FOUND OR NOT g.active OR g.issuer<>issuer_arg OR g.expires_at<=v_now
 OR NOT EXISTS(SELECT 1 FROM public.crm_private_trials WHERE organization_id=company AND expires_at>v_now)
 THEN
 INSERT INTO crm_auto_service.audit(company_id,fingerprint,request_id,outcome,observed_at) VALUES(company,fp,req,'DENIED',v_now);
 RETURN jsonb_build_object('decision','DENIED'); END IF;
 IF (SELECT count(*) FROM crm_auto_service.nonces WHERE company_id=company AND fingerprint=fp)>=10000 THEN
 RETURN jsonb_build_object('decision','LIMIT_DENIED'); END IF;
 INSERT INTO crm_auto_service.nonces VALUES(company,fp,req,nonce_arg,digest_arg,issued) ON CONFLICT DO NOTHING;
 IF NOT FOUND THEN
 INSERT INTO crm_auto_service.audit(company_id,fingerprint,request_id,outcome,observed_at,grant_revision) VALUES(company,fp,req,'REPLAY_DENIED',v_now,g.revision);
 RETURN jsonb_build_object('decision','REPLAY_DENIED'); END IF;
 SELECT COALESCE((automation_rules::jsonb->>'version')::integer,0) INTO settings FROM public.crm_private_preferences WHERE organization_id=company;
 IF settings IS NULL OR settings<0 OR settings>20 THEN RAISE EXCEPTION 'SOURCE_UNAVAILABLE'; END IF;
 SELECT count(*) INTO total FROM public.crm_private_automation_runs WHERE organization_id=company;
 IF total>200 THEN RAISE EXCEPTION 'SOURCE_LIMIT'; END IF;
 SELECT jsonb_build_object('schema','CRM_AUTOMATION_STATE_V1','source_kind','synthetic_private_database',
 'company_id',company,'observed_at',v_now,'settings_version',settings,
 'counts',jsonb_build_object('pending',count(*) FILTER(WHERE state='pending'),'completed',count(*) FILTER(WHERE state='completed'),
 'cancelled',count(*) FILTER(WHERE state='cancelled'),'failed',count(*) FILTER(WHERE state='failed')),
 'events',COALESCE((SELECT jsonb_agg(jsonb_build_object('action',action,'at',occurred_at) ORDER BY occurred_at DESC,id DESC)
 FROM (SELECT id,action,occurred_at FROM public.crm_private_events WHERE organization_id=company AND action LIKE 'automation.%' ORDER BY occurred_at DESC,id DESC LIMIT 20) e),'[]'::jsonb),
 'ai_provider_calls',0,'operational_authorization',false)
 INTO result FROM public.crm_private_automation_runs WHERE organization_id=company;
 -- Revocation UPDATE takes a conflicting lock and cannot complete before this transaction commits.
 IF g.expires_at<=extract(epoch FROM clock_timestamp())::bigint THEN RAISE EXCEPTION 'GRANT_EXPIRED_DURING_READ'; END IF;
 INSERT INTO crm_auto_service.audit(company_id,fingerprint,request_id,outcome,observed_at,grant_revision) VALUES(company,fp,req,'READ_OK',v_now,g.revision);
 RETURN jsonb_build_object('decision','READ_OK','body',result);
END $$;
REVOKE ALL ON ALL TABLES IN SCHEMA crm_auto_service FROM PUBLIC;
REVOKE ALL ON FUNCTION crm_auto_service.read_state(uuid,text,text,text,uuid,text,text,bigint) FROM PUBLIC;
RESET ROLE;
GRANT USAGE ON SCHEMA crm_auto_service TO crm_auto_transport;
GRANT EXECUTE ON FUNCTION crm_auto_service.read_state(uuid,text,text,text,uuid,text,text,bigint) TO crm_auto_transport;
COMMIT;
