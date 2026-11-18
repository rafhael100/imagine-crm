\set ON_ERROR_STOP on
DO $$ BEGIN
 IF (SELECT count(*) FROM crm_organizations)<>2 OR (SELECT count(*) FROM crm_private_leads)<>1 THEN
 RAISE EXCEPTION 'BASELINE_DATA_LOST'; END IF;
 IF NOT EXISTS(SELECT 1 FROM pg_class WHERE oid='public.crm_private_automation_runs'::regclass AND relrowsecurity AND relforcerowsecurity) THEN
 RAISE EXCEPTION 'LEDGER_RLS_MISSING'; END IF;
 IF has_column_privilege('crm_homologation_app','crm_private_automation_runs','command','UPDATE') THEN
 RAISE EXCEPTION 'COMMAND_MUTATION_ALLOWED'; END IF;
END $$;
BEGIN;
SET ROLE crm_homologation_app;
SELECT set_config('app.tenant_id','aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',true);
INSERT INTO crm_private_automation_runs(id,organization_id,actor_id,authority_session_id,template,settings_version,lead_id,lead_version,execution_key,command,due_at,created_at)
 VALUES('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee','aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','cccccccc-cccc-4ccc-8ccc-cccccccccccc',
 'dddddddd-dddd-4ddd-8ddd-dddddddddddd','followup_qualified',1,'ffffffff-ffff-4fff-8fff-ffffffffffff',2,repeat('a',64),'{}',1800000000,1800000000);
COMMIT;
BEGIN;
SET ROLE crm_homologation_app;
SELECT set_config('app.tenant_id','bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',true);
DO $$ BEGIN IF (SELECT count(*) FROM crm_private_automation_runs)<>0 THEN RAISE EXCEPTION 'CROSS_TENANT_LEDGER'; END IF; END $$;
ROLLBACK;
\echo CRM_INCREMENTAL_RLS_AND_PRESERVATION_PASS
