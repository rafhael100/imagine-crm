-- CANDIDATE ONLY: upgrade the dedicated synthetic private CRM database.
-- No auto-deploy, no destructive down migration, no identity or business ALLOW.
\set ON_ERROR_STOP on
BEGIN ISOLATION LEVEL READ COMMITTED;
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
-- A new table and a replay have different admission rules. Do not normalize
-- missing RLS or policy on an existing relation, including after a lock wait.
DO $ledger_admission$
DECLARE observed_relation oid;
BEGIN
 observed_relation := to_regclass('public.crm_private_automation_runs');
 IF observed_relation IS NULL THEN
 -- A concurrent external creator must cause an error, not an IF NOT EXISTS skip.
CREATE TABLE public.crm_private_automation_runs(
 id uuid PRIMARY KEY, organization_id uuid NOT NULL REFERENCES crm_organizations,
 actor_id uuid NOT NULL REFERENCES crm_global_identities, authority_session_id uuid NOT NULL, -- Verified through IAM connection; no cross-connection FK lock inversion.
 template varchar(32) NOT NULL CHECK(template IN ('followup_qualified','followup_proposal')),
 settings_version integer NOT NULL, lead_id uuid NOT NULL, lead_version bigint NOT NULL,
 execution_key varchar(64) NOT NULL, command jsonb NOT NULL,
 state varchar(16) NOT NULL DEFAULT 'pending' CHECK(state IN ('pending','completed','cancelled','failed')),
 due_at bigint NOT NULL, created_at bigint NOT NULL, attempts integer NOT NULL DEFAULT 0 CHECK(attempts BETWEEN 0 AND 3),
 UNIQUE(organization_id,execution_key), FOREIGN KEY(organization_id,lead_id) REFERENCES crm_private_leads(organization_id,id));
 ELSE
  IF (SELECT relkind FROM pg_class WHERE oid=observed_relation) IS DISTINCT FROM 'r' THEN
   RAISE EXCEPTION 'INCOMPATIBLE_AUTOMATION_TABLE'; END IF;
  LOCK TABLE ONLY public.crm_private_automation_runs IN ACCESS EXCLUSIVE MODE;
  -- A rename/drop/replacement while waiting must not change the admitted object.
  IF to_regclass('public.crm_private_automation_runs') IS DISTINCT FROM observed_relation
   OR (SELECT relkind FROM pg_class WHERE oid=observed_relation) IS DISTINCT FROM 'r' THEN
   RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_RELATION_CHANGED'; END IF;
  IF (SELECT relrowsecurity FROM pg_class WHERE oid=observed_relation) IS DISTINCT FROM true THEN
   RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_RLS_DISABLED'; END IF;
  IF (SELECT relforcerowsecurity FROM pg_class WHERE oid=observed_relation) IS DISTINCT FROM true THEN
   RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_RLS_NOT_FORCED'; END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_policy WHERE polrelid=observed_relation AND polname='tenant_scope') THEN
   RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_POLICY_MISSING'; END IF;
 END IF;
END $ledger_admission$;

CREATE TEMPORARY TABLE central_auto_expected_ledger(
 id uuid PRIMARY KEY, organization_id uuid NOT NULL,
 actor_id uuid NOT NULL, authority_session_id uuid NOT NULL, -- Verified through IAM connection; no cross-connection FK lock inversion.
 template varchar(32) NOT NULL CHECK(template IN ('followup_qualified','followup_proposal')),
 settings_version integer NOT NULL, lead_id uuid NOT NULL, lead_version bigint NOT NULL,
 execution_key varchar(64) NOT NULL, command jsonb NOT NULL,
 state varchar(16) NOT NULL DEFAULT 'pending' CHECK(state IN ('pending','completed','cancelled','failed')),
 due_at bigint NOT NULL, created_at bigint NOT NULL, attempts integer NOT NULL DEFAULT 0 CHECK(attempts BETWEEN 0 AND 3),
 UNIQUE(organization_id,execution_key) ) ON COMMIT DROP;
CREATE POLICY tenant_scope ON pg_temp.central_auto_expected_ledger
 USING (organization_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid)
 WITH CHECK (organization_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);

-- Reject an incompatible preexisting table instead of pretending IF NOT EXISTS proves compatibility.
DO $$ DECLARE names text[]; types text[]; actual text[]; expected text[]; BEGIN
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
 -- Constraint counts alone cannot detect a weakened CHECK or a retargeted FK.
 SELECT array_agg(pg_get_constraintdef(oid) ORDER BY pg_get_constraintdef(oid)) INTO actual
 FROM pg_constraint WHERE conrelid='public.crm_private_automation_runs'::regclass AND contype<>'f';
 SELECT array_agg(pg_get_constraintdef(oid) ORDER BY pg_get_constraintdef(oid)) INTO expected
 FROM pg_constraint WHERE conrelid='pg_temp.central_auto_expected_ledger'::regclass;
 IF actual IS DISTINCT FROM expected THEN RAISE EXCEPTION 'INCOMPATIBLE_AUTOMATION_CONSTRAINT_DEFINITIONS'; END IF;
 SELECT array_agg(pg_get_constraintdef(oid) ORDER BY pg_get_constraintdef(oid)) INTO actual
 FROM pg_constraint WHERE conrelid='public.crm_private_automation_runs'::regclass AND contype='f';
 SELECT array_agg(definition ORDER BY definition) INTO expected FROM (VALUES
 ('FOREIGN KEY (organization_id) REFERENCES crm_organizations(id)'),
 ('FOREIGN KEY (actor_id) REFERENCES crm_global_identities(id)'),
 ('FOREIGN KEY (organization_id, lead_id) REFERENCES crm_private_leads(organization_id, id)')
 ) AS approved(definition);
 IF actual IS DISTINCT FROM expected OR EXISTS (
 SELECT 1 FROM pg_constraint WHERE conrelid='public.crm_private_automation_runs'::regclass
 AND (NOT convalidated OR condeferrable OR condeferred)
 ) THEN RAISE EXCEPTION 'INCOMPATIBLE_AUTOMATION_FOREIGN_KEYS'; END IF;
 SELECT array_agg(attname::text||':'||attnotnull::text ORDER BY attnum) INTO actual
 FROM pg_attribute WHERE attrelid='public.crm_private_automation_runs'::regclass AND attnum>0 AND NOT attisdropped;
 SELECT array_agg(attname::text||':'||attnotnull::text ORDER BY attnum) INTO expected
 FROM pg_attribute WHERE attrelid='pg_temp.central_auto_expected_ledger'::regclass AND attnum>0 AND NOT attisdropped;
 IF actual IS DISTINCT FROM expected THEN RAISE EXCEPTION 'INCOMPATIBLE_AUTOMATION_NULLABILITY'; END IF;
 SELECT array_agg(adnum::text||':'||pg_get_expr(adbin,adrelid) ORDER BY adnum) INTO actual
 FROM pg_attrdef WHERE adrelid='public.crm_private_automation_runs'::regclass;
 SELECT array_agg(adnum::text||':'||pg_get_expr(adbin,adrelid) ORDER BY adnum) INTO expected
 FROM pg_attrdef WHERE adrelid='pg_temp.central_auto_expected_ledger'::regclass;
 IF actual IS DISTINCT FROM expected THEN RAISE EXCEPTION 'INCOMPATIBLE_AUTOMATION_DEFAULTS'; END IF;
 IF (SELECT relowner FROM pg_class WHERE oid='public.crm_private_automation_runs'::regclass)<>(SELECT oid FROM pg_roles WHERE rolname=current_user)
 THEN RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_OWNER'; END IF;
END $$;
-- A replay must not preserve an unreviewed right or silently repair policy drift.
-- REVOKE UPDATE does not remove DELETE/TRUNCATE or rights held through another role.
DO $$ BEGIN
 IF EXISTS (
  SELECT 1 FROM pg_class c CROSS JOIN LATERAL aclexplode(c.relacl) acl
  WHERE c.oid='public.crm_private_automation_runs'::regclass AND acl.grantee<>c.relowner
   AND (acl.grantee<>(SELECT oid FROM pg_roles WHERE rolname='crm_homologation_app')
    OR acl.privilege_type NOT IN ('SELECT','INSERT') OR acl.is_grantable)
 ) THEN RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_TABLE_PRIVILEGES'; END IF;
 IF EXISTS (
  SELECT 1 FROM pg_attribute a JOIN pg_class c ON c.oid=a.attrelid
   CROSS JOIN LATERAL aclexplode(a.attacl) acl
  WHERE a.attrelid='public.crm_private_automation_runs'::regclass AND a.attnum>0
   AND NOT a.attisdropped AND acl.grantee<>c.relowner
   AND (acl.grantee<>(SELECT oid FROM pg_roles WHERE rolname='crm_homologation_app')
    OR acl.privilege_type<>'UPDATE' OR a.attname NOT IN ('state','attempts','due_at')
    OR acl.is_grantable)
 ) THEN RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_COLUMN_PRIVILEGES'; END IF;
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
 IF EXISTS (
  SELECT 1 FROM pg_policy actual CROSS JOIN pg_policy expected
  WHERE actual.polrelid='public.crm_private_automation_runs'::regclass
   AND expected.polrelid='pg_temp.central_auto_expected_ledger'::regclass
   AND (actual.polcmd IS DISTINCT FROM expected.polcmd
    OR actual.polpermissive IS DISTINCT FROM expected.polpermissive
    OR actual.polroles IS DISTINCT FROM expected.polroles
    OR pg_get_expr(actual.polqual,actual.polrelid) IS DISTINCT FROM pg_get_expr(expected.polqual,expected.polrelid)
    OR pg_get_expr(actual.polwithcheck,actual.polrelid) IS DISTINCT FROM pg_get_expr(expected.polwithcheck,expected.polrelid))
 ) THEN RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_POLICY_DEFINITION'; END IF;
END $$;
REVOKE ALL ON public.crm_private_automation_runs FROM PUBLIC;
REVOKE UPDATE ON public.crm_private_automation_runs FROM crm_homologation_app;
GRANT SELECT,INSERT ON public.crm_private_automation_runs TO crm_homologation_app;
GRANT UPDATE(state,attempts,due_at) ON public.crm_private_automation_runs TO crm_homologation_app;
DO $$ BEGIN
 IF EXISTS (
  SELECT 1 FROM pg_attribute WHERE attrelid='public.crm_private_automation_runs'::regclass
   AND attnum>0 AND NOT attisdropped AND attname NOT IN ('state','attempts','due_at')
   AND has_column_privilege('crm_homologation_app',attrelid,attname,'UPDATE')
 ) OR has_table_privilege('crm_homologation_app','public.crm_private_automation_runs','DELETE,TRUNCATE,REFERENCES,TRIGGER')
 THEN RAISE EXCEPTION 'UNEXPECTED_AUTOMATION_EFFECTIVE_PRIVILEGES'; END IF;
END $$;
COMMIT;
\echo CENTRAL_AUTO_LEDGER_INCREMENTAL_V1_READY

