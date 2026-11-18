-- Additive, disposable private homologation only. No production migration.
BEGIN;
DO $$ BEGIN
 IF current_database()<>'imagine_crm_private_homologation'
    OR (SELECT mode FROM public.crm_private_installation WHERE id=1) IS DISTINCT FROM 'synthetic-v1'
    OR NOT EXISTS(SELECT 1 FROM pg_roles WHERE rolname='crm_homologation_app' AND NOT rolsuper AND NOT rolbypassrls)
    OR NOT EXISTS(SELECT 1 FROM pg_roles WHERE rolname='crm_homologation_iam' AND NOT rolsuper AND NOT rolbypassrls) THEN
  RAISE EXCEPTION 'Wrong private owner draft installation';
 END IF;
END $$;

-- PostgreSQL row locks require UPDATE privilege on at least one column.
-- This technical marker grants no ability to change company, role or status.
ALTER TABLE public.crm_private_accounts ADD COLUMN owner_draft_lock_marker bigint NOT NULL DEFAULT 0;
GRANT UPDATE(owner_draft_lock_marker) ON public.crm_private_accounts TO crm_homologation_iam;

CREATE TABLE public.crm_private_owner_draft_grants(
 organization_id uuid NOT NULL REFERENCES public.crm_organizations(id),
 operation text NOT NULL CHECK(operation IN ('crm.rule.draft.create','crm.rule.draft.read')),
 key_id text NOT NULL CHECK(key_id ~ '^[a-z][a-z0-9_-]{0,63}$'),
 active boolean NOT NULL DEFAULT false,
 revision bigint NOT NULL CHECK(revision>0),
 expires_at bigint NOT NULL CHECK(expires_at>0 AND expires_at<=4102444800),
 lock_marker bigint NOT NULL DEFAULT 0,
 PRIMARY KEY(organization_id,operation)
);

CREATE TABLE public.crm_private_owner_draft_intents(
 draft_ref uuid PRIMARY KEY,
 organization_id uuid NOT NULL REFERENCES public.crm_organizations(id),
 source_run_id uuid NOT NULL REFERENCES public.crm_private_automation_runs(id),
 creator_id uuid NOT NULL REFERENCES public.crm_global_identities(id),
 business jsonb NOT NULL CHECK(jsonb_typeof(business)='object'),
 business_hash varchar(64) NOT NULL CHECK(business_hash ~ '^[0-9a-f]{64}$'),
 state text NOT NULL DEFAULT 'pending' CHECK(state IN ('pending','confirmed')),
 created_at bigint NOT NULL CHECK(created_at>0),
 confirmed_at bigint,
 UNIQUE(organization_id,source_run_id),
 UNIQUE(organization_id,draft_ref),
 CHECK((state='pending' AND confirmed_at IS NULL) OR (state='confirmed' AND confirmed_at>0)),
 CHECK(business ?& ARRAY['schema','company_id','draft_ref','source_run_id','creator_id']
   AND business->>'schema'='CRM_AIA_OWNER_RULE_DRAFT_V1'
   AND business->>'company_id'=organization_id::text AND business->>'draft_ref'=draft_ref::text
   AND business->>'source_run_id'=source_run_id::text AND business->>'creator_id'=creator_id::text)
);

DO $$ DECLARE name text; BEGIN
 FOREACH name IN ARRAY ARRAY['crm_private_owner_draft_grants','crm_private_owner_draft_intents'] LOOP
  EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY',name);
  EXECUTE format('ALTER TABLE public.%I FORCE ROW LEVEL SECURITY',name);
  EXECUTE format('CREATE POLICY tenant_scope ON public.%I USING (organization_id=NULLIF(current_setting(''app.tenant_id'',true),'''')::uuid) WITH CHECK (organization_id=NULLIF(current_setting(''app.tenant_id'',true),'''')::uuid)',name);
  EXECUTE format('REVOKE ALL ON public.%I FROM PUBLIC',name);
 END LOOP;
END $$;
GRANT SELECT ON public.crm_private_owner_draft_grants TO crm_homologation_app;
GRANT UPDATE(lock_marker) ON public.crm_private_owner_draft_grants TO crm_homologation_app;
GRANT SELECT,INSERT ON public.crm_private_owner_draft_intents TO crm_homologation_app;
GRANT UPDATE(state,confirmed_at) ON public.crm_private_owner_draft_intents TO crm_homologation_app;
COMMIT;
