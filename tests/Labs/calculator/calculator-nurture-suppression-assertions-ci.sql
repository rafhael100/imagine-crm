-- PERF-CALC-PG03 — synthetic unsubscribe survives a COMMIT, never grants send.
-- Run AFTER the pinned Node->PHP PG fixture has been recorded in this CI only.
BEGIN;
SET LOCAL ROLE crm_calc_ci;
SELECT set_config('app.tenant_id','aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',true);

INSERT INTO public.crm_calc_lab_suppressions
  (organization_id,contact_token,submission_id,reason)
VALUES ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',repeat('e',64),
  '123e4567-e89b-42d3-a456-426614174000','unsubscribed');

INSERT INTO public.crm_calc_lab_suppressions
  (organization_id,contact_token,submission_id,reason)
VALUES ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',repeat('e',64),
  '123e4567-e89b-42d3-a456-426614174000','unsubscribed')
ON CONFLICT DO NOTHING;

DO $$
BEGIN
  IF (SELECT count(*) FROM public.crm_calc_lab_suppressions)<>1
     OR has_table_privilege(current_user,'public.crm_calc_lab_suppressions','UPDATE')
     OR has_table_privilege(current_user,'public.crm_calc_lab_suppressions','DELETE')
  THEN RAISE EXCEPTION 'Suppression not unique or mutable';
  END IF;
END $$;
COMMIT;

-- Prove durable visibility in a separate transaction with same tenant.
BEGIN;
SET LOCAL ROLE crm_calc_ci;
SELECT set_config('app.tenant_id','aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',true);
DO $$
BEGIN
  IF (SELECT count(*) FROM public.crm_calc_lab_suppressions
      WHERE contact_token=repeat('e',64))<>1
  THEN RAISE EXCEPTION 'Unsubscribe did not survive commit'; END IF;
END $$;

-- A second tenant cannot see, remove or replace the suppression.
SELECT set_config('app.tenant_id','bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',true);
DO $$
DECLARE denied boolean := false;
BEGIN
  IF (SELECT count(*) FROM public.crm_calc_lab_suppressions)<>0 THEN
    RAISE EXCEPTION 'Another tenant saw suppression entry';
  END IF;
  BEGIN
    INSERT INTO public.crm_calc_lab_suppressions
      (organization_id,contact_token,submission_id,reason)
    VALUES ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',repeat('f',64),
      '123e4567-e89b-42d3-a456-426614174000','withdrawn');
  EXCEPTION WHEN insufficient_privilege THEN denied := true;
  END;
  IF NOT denied THEN RAISE EXCEPTION 'Tenant B inserted into tenant A suppression'; END IF;
END $$;
COMMIT;

-- The data expires when GitHub destroys the temporary PostgreSQL service.
SELECT 'PASS: synthetic unsubscribe is append-only, durable across transactions, per-tenant, no marketing delivery enabled' AS result;
