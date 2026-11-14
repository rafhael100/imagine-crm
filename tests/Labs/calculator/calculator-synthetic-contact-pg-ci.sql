-- PERF-CALC-PII-02: ephemeral PostgreSQL CI metadata for synthetic email test.
-- NOTHING here accepts/stores names, addresses, phone, real contact details,
-- provider account IDs or legal consent. NEVER a production migration.
CREATE TABLE public.crm_calc_lab_contact_metadata (
  organization_id uuid NOT NULL,
  submission_id uuid NOT NULL,
  contact_fingerprint char(64) NOT NULL CHECK(contact_fingerprint ~ '^[a-f0-9]{64}$'),
  report_request_claimed boolean NOT NULL,
  marketing_interest_claimed boolean NOT NULL,
  contact_identity_verified boolean NOT NULL DEFAULT false
    CHECK(contact_identity_verified IS FALSE),
  marketing_consent_proven boolean NOT NULL DEFAULT false
    CHECK(marketing_consent_proven IS FALSE),
  created_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT calculator_contact_lab_pk PRIMARY KEY(organization_id,submission_id),
  CONSTRAINT calculator_contact_lab_origin_fk FOREIGN KEY(organization_id,submission_id)
    REFERENCES public.crm_calc_lab_intents(organization_id,submission_id)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT calculator_contact_lab_interest CHECK (
    report_request_claimed OR marketing_interest_claimed
  )
);
ALTER TABLE public.crm_calc_lab_contact_metadata ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_calc_lab_contact_metadata FORCE ROW LEVEL SECURITY;
CREATE POLICY calculator_contact_lab_tenant_scope
  ON public.crm_calc_lab_contact_metadata FOR ALL TO crm_calc_ci
  USING (organization_id = NULLIF(current_setting('app.tenant_id',true),'')::uuid)
  WITH CHECK (organization_id = NULLIF(current_setting('app.tenant_id',true),'')::uuid);
GRANT SELECT (
  organization_id,submission_id,contact_fingerprint,
  report_request_claimed,marketing_interest_claimed,
  contact_identity_verified,marketing_consent_proven
) ON public.crm_calc_lab_contact_metadata TO crm_calc_ci;
GRANT INSERT (
  organization_id,submission_id,contact_fingerprint,
  report_request_claimed,marketing_interest_claimed
) ON public.crm_calc_lab_contact_metadata TO crm_calc_ci;
-- Any outbox, CRM leads, real consent or send permission is outside this lab.
