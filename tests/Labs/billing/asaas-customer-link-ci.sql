-- BILL-ASAAS-05: synthetic organization -> Asaas customer link experiment.
-- Run ONLY AFTER scripts/ci/verify-ephemeral-postgres.php --verify in
-- dedicated GitHub Actions postgres:16 service. NOT a Laravel migration.
--
-- No CNPJ, name, contact, token, payment, amount, subscription, or live tenant.
-- This fixture does NOT establish ownership or consent.
CREATE TABLE crm_lab_asaas_customer_links (
    organization_ref uuid PRIMARY KEY,
    external_reference uuid NOT NULL UNIQUE,
    provider_customer_id varchar(72) UNIQUE,
    state varchar(24) NOT NULL DEFAULT 'pending_lookup',
    reserved_at bigint NOT NULL,
    CONSTRAINT crm_lab_asaas_link_closed CHECK (
        organization_ref <> external_reference
        AND reserved_at > 0
        AND (
            (state IN ('pending_lookup', 'review_required') AND provider_customer_id IS NULL)
            OR
            (state = 'linked' AND provider_customer_id ~ '^cus_[A-Za-z0-9]{5,64}$')
        )
    )
);

-- This role is separate from the Asaas webhook receiver and reviewer roles.
-- It cannot DELETE/TRUNCATE or replace organization and external references.
-- Its SELECT scope is broad ONLY within this synthetic CI table and
-- absolutely NOT a production least-privilege tenant policy.
CREATE ROLE crm_billing_link_ci LOGIN PASSWORD 'ephemeral-billing-link-only'
    NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;
GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_billing_link_ci;
GRANT USAGE ON SCHEMA public TO crm_billing_link_ci;
GRANT SELECT (organization_ref, external_reference, provider_customer_id, state)
    ON TABLE crm_lab_asaas_customer_links TO crm_billing_link_ci;
GRANT INSERT (organization_ref, external_reference, reserved_at)
    ON TABLE crm_lab_asaas_customer_links TO crm_billing_link_ci;
GRANT UPDATE (provider_customer_id, state)
    ON TABLE crm_lab_asaas_customer_links TO crm_billing_link_ci;


-- Separate GET-only reconciliation reviewer: can inspect synthetic link
-- state and confirm an already discovered sandbox customer ID. NO INSERT,
-- DELETE, TRUNCATE, provider POST or permission on customer/tenant tables.
CREATE ROLE crm_billing_link_review_ci LOGIN PASSWORD 'ephemeral-link-review-only'
    NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;
GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_billing_link_review_ci;
GRANT USAGE ON SCHEMA public TO crm_billing_link_review_ci;
GRANT SELECT (organization_ref, external_reference, provider_customer_id, state)
    ON TABLE crm_lab_asaas_customer_links TO crm_billing_link_review_ci;
GRANT UPDATE (provider_customer_id, state)
    ON TABLE crm_lab_asaas_customer_links TO crm_billing_link_review_ci;
