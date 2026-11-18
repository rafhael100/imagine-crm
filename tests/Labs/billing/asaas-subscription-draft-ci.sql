-- BILL-ASAAS-08: subscription intent ledger with NO account entitlements.
-- ONLY disposable PostgreSQL CI after SEC-CI-02 preflight; not a migration.
-- Provisional plan references MUST NEVER be interpreted as approved prices.
CREATE TABLE crm_lab_asaas_subscription_drafts (
    organization_ref uuid PRIMARY KEY
        REFERENCES crm_lab_asaas_customer_links(organization_ref) ON DELETE RESTRICT,
    external_reference uuid NOT NULL UNIQUE,
    plan_code varchar(24) NOT NULL,
    monthly_cents integer NOT NULL,
    currency varchar(3) NOT NULL DEFAULT 'BRL',
    cycle varchar(12) NOT NULL DEFAULT 'MONTHLY',
    billing_type varchar(12) NOT NULL,
    trial_started_on date NOT NULL,
    trial_ends_on date NOT NULL,
    first_due_on date NOT NULL,
    state varchar(32) NOT NULL DEFAULT 'blocked_review',
    price_homologated boolean NOT NULL DEFAULT false,
    terms_accepted boolean NOT NULL DEFAULT false,
    company_owner_verified boolean NOT NULL DEFAULT false,
    can_create_subscription boolean NOT NULL DEFAULT false,
    can_charge boolean NOT NULL DEFAULT false,
    can_activate boolean NOT NULL DEFAULT false,
    can_invoice boolean NOT NULL DEFAULT false,
    CONSTRAINT crm_lab_subscription_closed CHECK (
        external_reference <> organization_ref
        AND
        (
            (plan_code = 'basico' AND monthly_cents = 6900)
            OR (plan_code = 'essencial' AND monthly_cents = 8900)
            OR (plan_code = 'profissional' AND monthly_cents = 14900)
            OR (plan_code = 'empresarial' AND monthly_cents = 29900)
        )
        AND currency = 'BRL'
        AND cycle = 'MONTHLY'
        AND billing_type IN ('PIX', 'BOLETO')
        AND trial_ends_on = trial_started_on + 14
        AND first_due_on >= trial_ends_on
        AND state = 'blocked_review'
        AND price_homologated = false
        AND terms_accepted = false
        AND company_owner_verified = false
        AND can_create_subscription = false
        AND can_charge = false
        AND can_activate = false
        AND can_invoice = false
    )
);

-- Only synthetic CI role. The plan price snapshots cannot be updated later.
CREATE ROLE crm_billing_subscription_ci LOGIN PASSWORD 'ephemeral-subscription-draft-only'
    NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;
GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_billing_subscription_ci;
GRANT USAGE ON SCHEMA public TO crm_billing_subscription_ci;
GRANT SELECT (organization_ref, state)
    ON TABLE crm_lab_asaas_customer_links TO crm_billing_subscription_ci;
GRANT SELECT (organization_ref, external_reference, plan_code, monthly_cents, billing_type,
    trial_started_on, trial_ends_on, first_due_on, state)
    ON TABLE crm_lab_asaas_subscription_drafts TO crm_billing_subscription_ci;
GRANT INSERT (organization_ref, external_reference, plan_code, monthly_cents,
    billing_type, trial_started_on, trial_ends_on, first_due_on)
    ON TABLE crm_lab_asaas_subscription_drafts TO crm_billing_subscription_ci;
