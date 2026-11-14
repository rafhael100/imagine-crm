-- PERF-CALC-PG03. CI-only durable unsubscribe/suppression metadata.
-- No email/phone/name, no marketing outbox or send authorization.
-- contact_token is a synthetic fixture; operational use requires keyed HMAC
-- from a verified contact identity, NOT a plain email hash.
CREATE TABLE public.crm_calc_lab_suppressions(
  organization_id uuid NOT NULL,
  contact_token char(64) NOT NULL CHECK (contact_token ~ '^[a-f0-9]{64}$'),
  submission_id uuid NOT NULL,
  reason varchar(24) NOT NULL CHECK(reason IN ('unsubscribed','withdrawn')),
  recorded_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT calc_lab_suppression_pk PRIMARY KEY(organization_id,contact_token),
  CONSTRAINT calc_lab_suppression_origin_fk
    FOREIGN KEY (organization_id,submission_id)
    REFERENCES public.crm_calc_lab_intents(organization_id,submission_id)
    ON DELETE RESTRICT ON UPDATE RESTRICT
);
ALTER TABLE public.crm_calc_lab_suppressions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_calc_lab_suppressions FORCE ROW LEVEL SECURITY;
CREATE POLICY calc_lab_suppressions_scope ON public.crm_calc_lab_suppressions
  FOR ALL TO crm_calc_ci
  USING (organization_id = NULLIF(current_setting('app.tenant_id',true),'')::uuid)
  WITH CHECK (organization_id = NULLIF(current_setting('app.tenant_id',true),'')::uuid);
GRANT SELECT (organization_id,contact_token,submission_id,reason,recorded_at)
  ON public.crm_calc_lab_suppressions TO crm_calc_ci;
GRANT INSERT (organization_id,contact_token,submission_id,reason)
  ON public.crm_calc_lab_suppressions TO crm_calc_ci;
-- No UPDATE/DELETE, no "resubscribe" transition. Future authorized
-- production engine must implement and audit a separate re-consent flow.
