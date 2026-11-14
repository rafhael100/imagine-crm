-- PERF-CALC-PG01 — DISPOSABLE PG16 LAB ONLY. NEVER RUN ON VPS/PRODUCTION.
-- Deliberately metadata-only: NO email/name/phone, no lead table, no sender.
-- GUC is a synthetic scope marker, not authentication. Real authenticated
-- tenant binding, anti-replay authority and LGPD remain separate release gates.

CREATE TABLE public.crm_calc_lab_intents (
  organization_id uuid NOT NULL,
  submission_id uuid NOT NULL,
  signed_nonce uuid NOT NULL,
  payload_sha256 char(64) NOT NULL CHECK(payload_sha256 ~ '^[a-f0-9]{64}$'),
  media_total_cents bigint NOT NULL CHECK(media_total_cents BETWEEN 70000 AND 300000000),
  report_requested boolean NOT NULL DEFAULT false,
  marketing_requested boolean NOT NULL DEFAULT false,
  created_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT calc_scope_pk PRIMARY KEY (organization_id,submission_id),
  CONSTRAINT calc_nonce_once UNIQUE (organization_id,signed_nonce),
  CONSTRAINT calc_intent_minimum_action CHECK(report_requested OR marketing_requested)
);
ALTER TABLE public.crm_calc_lab_intents ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_calc_lab_intents FORCE ROW LEVEL SECURITY;
CREATE POLICY crm_calc_lab_intent_scope ON public.crm_calc_lab_intents
  FOR ALL TO crm_calc_ci
  USING (organization_id = NULLIF(current_setting('app.tenant_id', true),'')::uuid)
  WITH CHECK (organization_id = NULLIF(current_setting('app.tenant_id', true),'')::uuid);
GRANT SELECT (organization_id,submission_id,signed_nonce,payload_sha256,media_total_cents,
  report_requested,marketing_requested) ON public.crm_calc_lab_intents TO crm_calc_ci;
GRANT INSERT (organization_id,submission_id,signed_nonce,payload_sha256,media_total_cents,
  report_requested,marketing_requested) ON public.crm_calc_lab_intents TO crm_calc_ci;

CREATE TABLE public.crm_calc_lab_consent_events (
  organization_id uuid NOT NULL,
  submission_id uuid NOT NULL,
  event_id uuid NOT NULL,
  intent_kind text NOT NULL CHECK(intent_kind IN (
    'report_requested','marketing_requested','marketing_revoked')),
  occurred_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT calc_event_pk PRIMARY KEY (organization_id,event_id),
  CONSTRAINT calc_event_submission_fk FOREIGN KEY(organization_id,submission_id)
    REFERENCES public.crm_calc_lab_intents(organization_id,submission_id)
    ON DELETE RESTRICT
);
ALTER TABLE public.crm_calc_lab_consent_events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_calc_lab_consent_events FORCE ROW LEVEL SECURITY;
CREATE POLICY crm_calc_lab_event_scope ON public.crm_calc_lab_consent_events
  FOR ALL TO crm_calc_ci
  USING (organization_id = NULLIF(current_setting('app.tenant_id', true),'')::uuid)
  WITH CHECK (organization_id = NULLIF(current_setting('app.tenant_id', true),'')::uuid);
GRANT SELECT(organization_id,submission_id,event_id,intent_kind)
  ON public.crm_calc_lab_consent_events TO crm_calc_ci;
GRANT INSERT(organization_id,submission_id,event_id,intent_kind)
  ON public.crm_calc_lab_consent_events TO crm_calc_ci;
-- No UPDATE or DELETE grants. Even the positive test does not imply real
-- consent: all events are synthetic, unsigned business decisions.
