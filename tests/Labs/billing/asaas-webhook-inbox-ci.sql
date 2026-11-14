-- BILL-ASAAS-02. Only run AFTER scripts/ci/verify-ephemeral-postgres.php
-- --verify has matched the disposable postgres:16 GitHub CI service.
-- This is a test fixture, not a production schema/migration.
--
-- No raw payload, client information, tenant identifier or card details.
CREATE TABLE crm_lab_asaas_events (
    event_key varchar(64) PRIMARY KEY,
    resource_key varchar(64) NOT NULL,
    event_kind varchar(64) NOT NULL,
    body_digest varchar(64) NOT NULL,
    state varchar(24) NOT NULL DEFAULT 'pending_review',
    observed_at bigint NOT NULL,
    CONSTRAINT crm_lab_asaas_event_closed CHECK (
        event_key ~ '^[0-9a-f]{64}$'
        AND resource_key ~ '^[0-9a-f]{64}$'
        AND body_digest ~ '^[0-9a-f]{64}$'
        AND event_kind IN (
            'PAYMENT_CREATED', 'PAYMENT_UPDATED', 'PAYMENT_CONFIRMED',
            'PAYMENT_RECEIVED', 'PAYMENT_OVERDUE', 'PAYMENT_DELETED',
            'PAYMENT_REFUNDED', 'PAYMENT_PARTIALLY_REFUNDED',
            'PAYMENT_CHARGEBACK_REQUESTED',
            'SUBSCRIPTION_CREATED', 'SUBSCRIPTION_UPDATED',
            'SUBSCRIPTION_INACTIVATED', 'SUBSCRIPTION_DELETED'
        )
        AND state IN ('pending_review', 'reviewed_no_effect')
        AND observed_at > 0
    )
);

-- Runtime role cannot inspect, mutate, truncate, settle or grant.
-- The DB owner seeds the role ONLY inside the disposable CI service.
CREATE ROLE crm_billing_ci LOGIN PASSWORD 'ephemeral-billing-insert-only'
    NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;
GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_billing_ci;
GRANT USAGE ON SCHEMA public TO crm_billing_ci;
GRANT INSERT (event_key, resource_key, event_kind, body_digest, observed_at)
    ON TABLE crm_lab_asaas_events TO crm_billing_ci;

-- A physically separate reviewer can inspect only pseudonymous markers and
-- acknowledge them as reviewed WITHOUT ANY business mutation capability.
CREATE ROLE crm_billing_review_ci LOGIN PASSWORD 'ephemeral-billing-review-only'
    NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;
GRANT CONNECT ON DATABASE imagine_crm_ci TO crm_billing_review_ci;
GRANT USAGE ON SCHEMA public TO crm_billing_review_ci;
GRANT SELECT (event_key, event_kind, state, observed_at)
    ON TABLE crm_lab_asaas_events TO crm_billing_review_ci;
GRANT UPDATE (state) ON TABLE crm_lab_asaas_events TO crm_billing_review_ci;
