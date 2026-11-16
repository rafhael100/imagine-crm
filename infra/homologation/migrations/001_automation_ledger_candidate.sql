-- CANDIDATE ONLY: upgrade the dedicated synthetic private CRM database.
-- No auto-deploy, no destructive down migration, no identity or business ALLOW.
\set ON_ERROR_STOP on
BEGIN;
SET LOCAL search_path=pg_catalog,public;
SET LOCAL lock_timeout='2s';
SET LOCAL statement_timeout='10s';
SELECT pg_advisory_xact_lock(hashtextextended('central-auto-ledger-upgrade-v1',0));
DO $$ BEGIN
 IF current_database()<>'imagine_crm_private_homologation' OR current_user<>'crm_homologation_owner' THEN
  RAISE EXCEPTION 'UNAPPROVED_DATABASE_OR_OWNER'; END IF;
 IF (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1'
  OR to_regclass('public.crm_private_leads') IS NULL OR to_regclass('public.crm_private_preferences') IS NULL
  OR NOT EXISTS(SELECT 1 FROM pg_roles WHERE rolname='crm_homologation_app' AND NOT rolsuper AND NOT rolbypassrls)
 THEN RAISE EXCEPTION 'PRIVATE_BASELINE_REQUIRED'; END IF;
END $$;
CREATE TABLE IF NOT EXISTS public.crm_private_automation_runs(
 id uuid PRIMARY KEY, organization_id uuid NOT NULL REFERENCES crm_organizations,
 actor_id uuid NOT NULL REFERENCES crm_global_identities, authority_session_id uuid NOT NULL, -- Verified through IAM connection; no cross-connection FK lock inversion.
 template varchar(32) NOT NULL CHECK(template IN ('followup_qualified','followup_proposal')),
 settings_version integer NOT NULL, lead_id uuid NOT NULL, lead_version bigint NOT NULL,
 execution_key varchar(64) NOT NULL, command jsonb NOT NULL,
 state varchar(16) NOT NULL DEFAULT 'pending' CHECK(state IN ('pending','completed','cancelled','failed')),
 due_at bigint NOT NULL, created_at bigint NOT NULL, attempts integer NOT NULL DEFAULT 0 CHECK(attempts BETWEEN 0 AND 3),
 UNIQUE(organization_id,execution_key), FOREIGN KEY(organization_id,lead_id) REFERENCES crm_private_leads(organization_id,id));

-- Reject an incompatible preexisting table instead of pretending IF NOT EXISTS proves compatibility.
DO $$ DECLARE names text[]; types text[]; BEGIN
 SELECT array_agg(attname::text ORDER BY attnum) INTO names FROM pg_attribute
 WHERE attrelid='public.crm_private_automation_runs'::regclass AND attnum>0 AND NOT attisdropped;
 IF names IS DISTINCT FROM ARRAY['id','organization_id','actor_id','authority_session_id','template','settings_version','lead_id','lead_version','execution_key','command','state','due_at','created_at','attempts'] THEN
  RAISE EXCEPTION 'INCOMPATIBLE_AUTOMATION_TABLE'; END IF;
 SELECT array_agg(format_type(atttypid,atttypmod) ORDER BY attnum) INTO types FROM pg_attribute
 WHERE attrelid='public.crm_private_automation_runs'::regclass AND attnum>0 AND NOT attisdropped;
 IF types IS DISTINCT FROM ARRAY['uuid','uuid','uuid','uuid','character varying(32)','integer','uuid','bigint','character varying(64)','jsonb','character varying(16)','bigint','bigint','integer'] THEN
 RAISE EXCEPTION 'INCOMPATIBLE_AUTOMATION_TYPES'; END IF;
 IF (SELECT count(*) FROM pg_constraint WHERE conrelid='public.crm_private_automation_runs'::regclass)<>8 THEN
 RAISE EXCEPTION 'INCOMPATIBLE_AUTOMATION_CONSTRAINTS'; END IF;
 IF (SELECT relowner FROM pg_class WHERE oid='public.crm_private_automation_runs'::regclass)<>(SELECT oid FROM pg_roles WHERE rolname=current_user)
 THEN RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_OWNER'; END IF;
END $$;
ALTER TABLE public.crm_private_automation_runs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_private_automation_runs FORCE ROW LEVEL SECURITY;
DO $$ BEGIN
 IF NOT EXISTS(SELECT 1 FROM pg_policy WHERE polrelid='public.crm_private_automation_runs'::regclass AND polname='tenant_scope') THEN
 CREATE POLICY tenant_scope ON public.crm_private_automation_runs
 USING (organization_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid)
 WITH CHECK (organization_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);
 END IF;
 IF (SELECT count(*) FROM pg_policy WHERE polrelid='public.crm_private_automation_runs'::regclass)<>1 THEN
 RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_POLICY'; END IF;
END $$;
ALTER POLICY tenant_scope ON public.crm_private_automation_runs
 USING (organization_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid)
 WITH CHECK (organization_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);
REVOKE ALL ON public.crm_private_automation_runs FROM PUBLIC;
REVOKE UPDATE ON public.crm_private_automation_runs FROM crm_homologation_app;
GRANT SELECT,INSERT ON public.crm_private_automation_runs TO crm_homologation_app;
GRANT UPDATE(state,attempts,due_at) ON public.crm_private_automation_runs TO crm_homologation_app;
DO $$ BEGIN
 IF has_column_privilege('crm_homologation_app','public.crm_private_automation_runs','command','UPDATE')
  OR has_column_privilege('crm_homologation_app','public.crm_private_automation_runs','actor_id','UPDATE')
  OR has_column_privilege('crm_homologation_app','public.crm_private_automation_runs','authority_session_id','UPDATE')
 THEN RAISE EXCEPTION 'IMMUTABLE_COLUMN_PRIVILEGE_DRIFT'; END IF;
END $$;
COMMIT;
\echo CENTRAL_AUTO_LEDGER_INCREMENTAL_V1_READY
