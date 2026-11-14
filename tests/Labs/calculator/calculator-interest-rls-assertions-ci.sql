-- PERF-CALC-PG01 — disposable PostgreSQL CI acceptance tests. No PII.
-- Requires crm_calc_ci role and calculator-interest-rls-ci.sql.
BEGIN;
SET LOCAL ROLE crm_calc_ci;
SELECT set_config('app.tenant_id','aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',true);
INSERT INTO public.crm_calc_lab_intents
  (organization_id,submission_id,signed_nonce,payload_sha256,media_total_cents,
   report_requested,marketing_requested)
VALUES
  ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
   '11111111-1111-4111-8111-111111111111',
   '22222222-2222-4222-8222-222222222222',
   repeat('a',64),430000,true,false);

-- Same logical delivery with different cents must NOT overwrite the old row.
INSERT INTO public.crm_calc_lab_intents
  (organization_id,submission_id,signed_nonce,payload_sha256,media_total_cents,
   report_requested,marketing_requested)
VALUES
  ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
   '11111111-1111-4111-8111-111111111111',
   '22222222-2222-4222-8222-222222222222',
   repeat('b',64),500000,true,true)
ON CONFLICT DO NOTHING;

DO $$
BEGIN
  IF (SELECT count(*) FROM public.crm_calc_lab_intents)<>1
     OR (SELECT media_total_cents FROM public.crm_calc_lab_intents LIMIT 1)<>430000
     OR (SELECT marketing_requested FROM public.crm_calc_lab_intents LIMIT 1) IS NOT FALSE
  THEN
    RAISE EXCEPTION 'Calculator idempotent replay changed the immutable event';
  END IF;
END $$;

-- Nonce reused under a different submission is forbidden.
DO $$
DECLARE rejected boolean := false;
BEGIN
  BEGIN
    INSERT INTO public.crm_calc_lab_intents
      (organization_id,submission_id,signed_nonce,payload_sha256,media_total_cents,
       report_requested,marketing_requested)
    VALUES
      ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
       '33333333-3333-4333-8333-333333333333',
       '22222222-2222-4222-8222-222222222222',
       repeat('b',64),500000,true,true);
  EXCEPTION WHEN unique_violation THEN rejected := true;
  END;
  IF NOT rejected THEN RAISE EXCEPTION 'Duplicate calculator nonce accepted'; END IF;
END $$;

INSERT INTO public.crm_calc_lab_consent_events
  (organization_id,submission_id,event_id,intent_kind)
VALUES
  ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
   '11111111-1111-4111-8111-111111111111',
   '44444444-4444-4444-8444-444444444444','report_requested');

DO $$
BEGIN
  IF (SELECT count(*) FROM public.crm_calc_lab_consent_events
      WHERE intent_kind='marketing_requested')<>0 THEN
    RAISE EXCEPTION 'Report request implicitly became marketing';
  END IF;
  IF has_table_privilege(current_user,'public.crm_calc_lab_intents','UPDATE')
    OR has_table_privilege(current_user,'public.crm_calc_lab_intents','DELETE')
    OR has_table_privilege(current_user,'public.crm_calc_lab_consent_events','UPDATE')
    OR has_table_privilege(current_user,'public.crm_calc_lab_consent_events','DELETE')
  THEN
    RAISE EXCEPTION 'Runtime role has destructive calculator privileges';
  END IF;
END $$;

SELECT set_config('app.tenant_id','bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',true);
DO $$
BEGIN
  IF (SELECT count(*) FROM public.crm_calc_lab_intents)<>0
     OR (SELECT count(*) FROM public.crm_calc_lab_consent_events)<>0
  THEN
    RAISE EXCEPTION 'Tenant B read tenant A calculator intent';
  END IF;
END $$;

-- The exact same synthetic IDs can be reused under a distinct tenant.
INSERT INTO public.crm_calc_lab_intents
  (organization_id,submission_id,signed_nonce,payload_sha256,media_total_cents,
   report_requested,marketing_requested)
VALUES
  ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
   '11111111-1111-4111-8111-111111111111',
   '22222222-2222-4222-8222-222222222222',
   repeat('c',64),180000,false,true);
DO $$
DECLARE rejected boolean := false;
BEGIN
  BEGIN
    INSERT INTO public.crm_calc_lab_intents
      (organization_id,submission_id,signed_nonce,payload_sha256,media_total_cents,
       report_requested,marketing_requested)
    VALUES
      ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
       '55555555-5555-4555-8555-555555555555',
       '66666666-6666-4666-8666-666666666666',
       repeat('d',64),120000,true,false);
  EXCEPTION WHEN insufficient_privilege THEN rejected := true;
  END;
  IF NOT rejected THEN RAISE EXCEPTION 'Tenant B injected a record for tenant A'; END IF;
END $$;

SELECT set_config('app.tenant_id','',true);
DO $$
BEGIN
  IF (SELECT count(*) FROM public.crm_calc_lab_intents)<>0
     OR (SELECT count(*) FROM public.crm_calc_lab_consent_events)<>0
  THEN
    RAISE EXCEPTION 'Unset tenant marker read private calculator intent';
  END IF;
END $$;

ROLLBACK;
-- No fixture persisted after the CI check. No production migration executed.
SELECT 'PASS: calculator PG16 idempotency, no marketing inherit, FORCE RLS and deny UPDATE/DELETE' AS lab_result;
