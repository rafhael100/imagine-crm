-- PERF-CALC-OUTBOX-01: future report dispatch planning, GITHUB CI ONLY.
-- No recipient, SMTP, email, jobs, provider credentials, public route or
-- customer data. A row documents a synthetic *candidate* for manual review;
-- it is never an authorized message or proof of delivery.
CREATE TABLE public.crm_calc_lab_report_outbox (
  organization_id uuid NOT NULL,
  submission_id uuid NOT NULL,
  event_kind varchar(32) NOT NULL DEFAULT 'report_request_review'
    CHECK (event_kind = 'report_request_review'),
  intent_payload_sha256 char(64) NOT NULL
    CHECK(intent_payload_sha256 ~ '^[a-f0-9]{64}$'),
  state varchar(32) NOT NULL DEFAULT 'NO_DISPATCH_REVIEW_ONLY'
    CHECK (state = 'NO_DISPATCH_REVIEW_ONLY'),
  contact_verified boolean NOT NULL DEFAULT false
    CHECK(contact_verified IS FALSE),
  marketing_consent_verified boolean NOT NULL DEFAULT false
    CHECK(marketing_consent_verified IS FALSE),
  send_enabled boolean NOT NULL DEFAULT false
    CHECK(send_enabled IS FALSE),
  created_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT calculator_lab_outbox_pk PRIMARY KEY (
    organization_id,submission_id,event_kind),
  CONSTRAINT calculator_lab_outbox_intent_fk FOREIGN KEY (
    organization_id,submission_id)
    REFERENCES public.crm_calc_lab_intents(organization_id,submission_id)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT calculator_lab_outbox_contact_fk FOREIGN KEY (
    organization_id,submission_id)
    REFERENCES public.crm_calc_lab_contact_metadata(organization_id,submission_id)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT calculator_lab_outbox_cipher_fk FOREIGN KEY (
    organization_id,submission_id)
    REFERENCES public.crm_calc_lab_contact_ciphertext(organization_id,submission_id)
    ON DELETE RESTRICT ON UPDATE RESTRICT
);
ALTER TABLE public.crm_calc_lab_report_outbox ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_calc_lab_report_outbox FORCE ROW LEVEL SECURITY;
CREATE POLICY calculator_lab_outbox_tenant
  ON public.crm_calc_lab_report_outbox FOR ALL TO crm_calc_ci
  USING (
    organization_id = NULLIF(current_setting('app.tenant_id',true),'')::uuid)
  WITH CHECK (
    organization_id = NULLIF(current_setting('app.tenant_id',true),'')::uuid);
GRANT SELECT (
  organization_id,submission_id,event_kind,intent_payload_sha256,state,
  contact_verified,marketing_consent_verified,send_enabled
) ON public.crm_calc_lab_report_outbox TO crm_calc_ci;
GRANT INSERT (
  organization_id,submission_id,event_kind,intent_payload_sha256
) ON public.crm_calc_lab_report_outbox TO crm_calc_ci;
-- Explicitly NO UPDATE/DELETE grant: no worker can mark a message as sent,
-- no row can become send-enabled, and no message transport is present.
