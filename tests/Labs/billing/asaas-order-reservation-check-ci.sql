-- Run after fixture under ephemeral PG. Fail loudly on constraint regression.
DO $$
DECLARE
  first_order uuid := '11111111-2222-4333-8444-555555555551';
  second_order uuid := '11111111-2222-4333-8444-555555555552';
  third_order uuid := '11111111-2222-4333-8444-555555555553';
  tenant_a uuid := 'aaaaaaaa-2222-4333-8444-555555555551';
  tenant_b uuid := 'bbbbbbbb-2222-4333-8444-555555555552';
BEGIN
  INSERT INTO crm_lab_asaas_customer_links
    (organization_ref,external_reference,provider_customer_id,state,reserved_at)
  VALUES
    (tenant_a,'cccccccc-2222-4333-8444-555555555551','cus_SYNTHETIC1234','linked',1700000000),
    (tenant_b,'cccccccc-2222-4333-8444-555555555552','cus_SYNTHETIC5678','linked',1700000000);

  INSERT INTO crm_lab_asaas_order_reservations
    (order_ref,organization_ref,provider_customer_id,plan_code,amount_cents,due_on)
  VALUES (first_order,tenant_a,'cus_SYNTHETIC1234','basico',6900,'2026-12-15');

  -- Customer ID from tenant B must not attach to an otherwise new tenant-A order.
  BEGIN
    INSERT INTO crm_lab_asaas_order_reservations
      (order_ref,organization_ref,provider_customer_id,plan_code,amount_cents,due_on)
    VALUES (third_order,tenant_a,'cus_SYNTHETIC5678','essencial',8900,'2026-12-15');
    RAISE EXCEPTION 'foreign customer was accepted under another tenant';
  EXCEPTION WHEN foreign_key_violation THEN NULL;
  END;

  -- No new order or mutated tenant/plan may reuse an existing order_ref.
  BEGIN
    INSERT INTO crm_lab_asaas_order_reservations
      (order_ref,organization_ref,provider_customer_id,plan_code,amount_cents,due_on)
    VALUES (first_order,tenant_b,'cus_SYNTHETIC5678','essencial',8900,'2026-12-15');
    RAISE EXCEPTION 'duplicate order was accepted';
  EXCEPTION WHEN unique_violation THEN NULL;
  END;

  INSERT INTO crm_lab_asaas_order_reservations
    (order_ref,organization_ref,provider_customer_id,plan_code,amount_cents,due_on)
  VALUES (second_order,tenant_b,'cus_SYNTHETIC5678','essencial',8900,'2026-12-15');

  UPDATE crm_lab_asaas_order_reservations
    SET provider_payment_id='pay_SYNTHETIC1234',state='linked'
    WHERE order_ref=first_order;

  BEGIN
    UPDATE crm_lab_asaas_order_reservations
      SET provider_payment_id='pay_SYNTHETIC1234',state='linked'
      WHERE order_ref=second_order;
    RAISE EXCEPTION 'duplicate provider payment was accepted';
  EXCEPTION WHEN unique_violation THEN NULL;
  END;

  BEGIN
    UPDATE crm_lab_asaas_order_reservations
      SET state='linked' WHERE order_ref=second_order;
    RAISE EXCEPTION 'linked state without payment was accepted';
  EXCEPTION WHEN check_violation THEN NULL;
  END;

  -- A reviewer with UPDATE grants still cannot reset/rebind a linked row.
  BEGIN
    UPDATE crm_lab_asaas_order_reservations
      SET provider_payment_id = NULL, state = 'reserved'
      WHERE order_ref = first_order;
    RAISE EXCEPTION 'linked payment was reset';
  EXCEPTION WHEN check_violation THEN NULL;
  END;

  UPDATE crm_lab_asaas_order_reservations
    SET state='review_required' WHERE order_ref=second_order;
  BEGIN
    UPDATE crm_lab_asaas_order_reservations
      SET provider_payment_id='pay_SYNTHETIC5678', state='linked'
      WHERE order_ref=second_order;
    RAISE EXCEPTION 'review-only order was reactivated';
  EXCEPTION WHEN check_violation THEN NULL;
  END;

  IF (SELECT count(*) FROM crm_lab_asaas_order_reservations) <> 2
     OR (SELECT count(*) FROM crm_lab_asaas_order_reservations WHERE state='linked') <> 1
     OR has_table_privilege('crm_billing_order_ci','crm_lab_asaas_order_reservations','SELECT')
     OR has_table_privilege('crm_billing_order_ci','crm_lab_asaas_order_reservations','DELETE')
     OR has_table_privilege('crm_billing_order_review_ci','crm_lab_asaas_order_reservations','INSERT')
     OR has_table_privilege('crm_billing_order_review_ci','crm_lab_asaas_order_reservations','DELETE')
     OR has_table_privilege('crm_billing_order_review_ci','crm_lab_asaas_order_reservations','TRUNCATE')
     OR has_column_privilege('crm_billing_order_review_ci','crm_lab_asaas_order_reservations','organization_ref','UPDATE')
  THEN
    RAISE EXCEPTION 'order reservation isolation/role contract failed';
  END IF;
END
$$;
-- No rights, subscriptions or webhook effects are created by this laboratory.
