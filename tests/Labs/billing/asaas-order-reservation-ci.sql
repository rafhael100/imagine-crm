-- BILL-ASAAS-INT-02: CI-only order/payment reservation, never a production migration.
-- Requires verified ephemeral postgres:16 service BEFORE this script.
-- Identifiers and amounts are synthetic. No token, fiscal identity or customer data.
CREATE TABLE crm_lab_asaas_order_reservations (
  order_ref uuid PRIMARY KEY,
  organization_ref uuid NOT NULL,
  provider_customer_id varchar(72) NOT NULL,
  plan_code varchar(32) NOT NULL,
  amount_cents integer NOT NULL,
  due_on date NOT NULL,
  provider_payment_id varchar(72) UNIQUE,
  state varchar(24) NOT NULL DEFAULT 'reserved',
  CONSTRAINT lab_order_shape CHECK (
    order_ref <> organization_ref
    AND provider_customer_id ~ '^cus_[A-Za-z0-9]{5,64}$'
    AND plan_code IN ('basico', 'essencial')
    AND amount_cents > 0 AND amount_cents <= 10000000
    AND (
      (state IN ('reserved','review_required') AND provider_payment_id IS NULL)
      OR (state = 'linked' AND provider_payment_id ~ '^pay_[A-Za-z0-9]{5,64}$')
    )
  ),
  -- The same order cannot be rebound to another tenant or provider payment.
  CONSTRAINT lab_order_identity UNIQUE (order_ref, organization_ref)
);
CREATE INDEX lab_order_organization_lookup ON crm_lab_asaas_order_reservations (organization_ref);
CREATE ROLE crm_billing_order_ci LOGIN PASSWORD 'ephemeral-order-insert-only'
  NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;
GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_billing_order_ci;
GRANT USAGE ON SCHEMA public TO crm_billing_order_ci;
GRANT INSERT (order_ref, organization_ref, provider_customer_id, plan_code, amount_cents, due_on)
  ON crm_lab_asaas_order_reservations TO crm_billing_order_ci;
-- Separate reviewer may link a known payment, but cannot create/delete an order.
CREATE ROLE crm_billing_order_review_ci LOGIN PASSWORD 'ephemeral-order-review-only'
  NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;
GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_billing_order_review_ci;
GRANT USAGE ON SCHEMA public TO crm_billing_order_review_ci;
GRANT SELECT (order_ref, organization_ref, provider_customer_id, plan_code,
              amount_cents, due_on, provider_payment_id, state)
  ON crm_lab_asaas_order_reservations TO crm_billing_order_review_ci;
GRANT UPDATE (provider_payment_id, state)
  ON crm_lab_asaas_order_reservations TO crm_billing_order_review_ci;
