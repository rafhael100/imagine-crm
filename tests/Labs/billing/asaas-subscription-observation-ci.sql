-- BILL-ASAAS-10. CI-only, disposable PostgreSQL after SEC-CI-02 preflight.
-- A found Asaas SANDBOX subscription is NOT a payment or entitlement.
-- No operational migrations or customers are affected.
CREATE TABLE crm_lab_asaas_subscription_observations (
    organization_ref uuid PRIMARY KEY
        REFERENCES crm_lab_asaas_subscription_drafts(organization_ref) ON DELETE RESTRICT,
    external_reference uuid NOT NULL UNIQUE,
    provider_subscription_id varchar(72) NOT NULL UNIQUE,
    observed_at bigint NOT NULL,
    state varchar(28) NOT NULL DEFAULT 'observed_no_rights',
    can_charge boolean NOT NULL DEFAULT false,
    can_activate boolean NOT NULL DEFAULT false,
    payment_verified boolean NOT NULL DEFAULT false,
    terms_accepted boolean NOT NULL DEFAULT false,
    CONSTRAINT crm_lab_sub_observation_closed CHECK (
        organization_ref <> external_reference
        AND provider_subscription_id ~ '^sub_[A-Za-z0-9]{5,64}$'
        AND observed_at > 0
        AND state = 'observed_no_rights'
        AND can_charge = false
        AND can_activate = false
        AND payment_verified = false
        AND terms_accepted = false
    )
);

-- Strictly a lab-only reviewer, NOT tenant-scoped RLS authorization.
-- Allows looking up the synthetic payer ID to validate Asaas GET response.
-- This visibility is NOT acceptable for a customer-facing runtime role.
CREATE ROLE crm_billing_subscription_review_ci
    LOGIN PASSWORD 'ephemeral-subscription-review-only'
    NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;
GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_billing_subscription_review_ci;
GRANT USAGE ON SCHEMA public TO crm_billing_subscription_review_ci;
GRANT SELECT (organization_ref, external_reference, state)
    ON TABLE crm_lab_asaas_subscription_drafts TO crm_billing_subscription_review_ci;
GRANT SELECT (organization_ref, state, provider_customer_id)
    ON TABLE crm_lab_asaas_customer_links TO crm_billing_subscription_review_ci;
GRANT INSERT (organization_ref, external_reference, provider_subscription_id, observed_at)
    ON TABLE crm_lab_asaas_subscription_observations TO crm_billing_subscription_review_ci;
GRANT SELECT (organization_ref, external_reference, provider_subscription_id, state)
    ON TABLE crm_lab_asaas_subscription_observations TO crm_billing_subscription_review_ci;
