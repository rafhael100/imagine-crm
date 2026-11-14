-- PERF-CALC-PII-04 — encrypted fixture vault in disposable PostgreSQL16 CI.
-- This is NOT a real contact table, NOT a vault/key management service and
-- NEVER a production migration. The encryption key is random, memory-only CI.
CREATE TABLE public.crm_calc_lab_contact_ciphertext (
  organization_id uuid NOT NULL,
  submission_id uuid NOT NULL,
  key_id varchar(40) NOT NULL CHECK(key_id ~ '^lab-key-[a-z0-9-]{4,32}$'),
  nonce_b64 varchar(24) NOT NULL CHECK(nonce_b64 ~ '^[A-Za-z0-9+/]{16}$'),
  tag_b64 varchar(32) NOT NULL CHECK(tag_b64 ~ '^[A-Za-z0-9+/]{22}==$'),
  ciphertext_b64 varchar(1500) NOT NULL CHECK(
    length(ciphertext_b64) >= 16 AND ciphertext_b64 ~ '^[A-Za-z0-9+/]+={0,2}$'
  ),
  production_authorized boolean NOT NULL DEFAULT false CHECK (production_authorized IS FALSE),
  created_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT calculator_cipher_pk PRIMARY KEY (organization_id,submission_id),
  CONSTRAINT calculator_cipher_metadata_fk FOREIGN KEY(organization_id,submission_id)
    REFERENCES public.crm_calc_lab_contact_metadata(organization_id,submission_id)
    ON DELETE RESTRICT ON UPDATE RESTRICT
);
ALTER TABLE public.crm_calc_lab_contact_ciphertext ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_calc_lab_contact_ciphertext FORCE ROW LEVEL SECURITY;
CREATE POLICY calculator_cipher_tenant
  ON public.crm_calc_lab_contact_ciphertext FOR ALL TO crm_calc_ci
  USING (organization_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid)
  WITH CHECK (organization_id=NULLIF(current_setting('app.tenant_id',true),'')::uuid);
GRANT SELECT (organization_id,submission_id,key_id,nonce_b64,tag_b64,
  ciphertext_b64,production_authorized) ON public.crm_calc_lab_contact_ciphertext TO crm_calc_ci;
GRANT INSERT (organization_id,submission_id,key_id,nonce_b64,tag_b64,
  ciphertext_b64) ON public.crm_calc_lab_contact_ciphertext TO crm_calc_ci;
-- No UPDATE/DELETE, no key storage in table, no SMTP or worker.
