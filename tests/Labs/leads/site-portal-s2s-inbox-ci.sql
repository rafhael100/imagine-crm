-- F400: CI-only fake Portal S2S authorization registry and durable command inbox.
-- SEC-CI-02 must run BEFORE this file on disposable PostgreSQL16 only.
-- Contains NO production token, credential, API, IAM grant or real client PII.
CREATE TABLE public.crm_lab_site_s2s_keys (
  key_id varchar(64) PRIMARY KEY,
  source_service varchar(32) NOT NULL CHECK (source_service='imagine-portal'),
  audience_service varchar(32) NOT NULL CHECK (audience_service='imagine-crm'),
  allowed_action varchar(16) NOT NULL CHECK (allowed_action IN ('provision','revoke')),
  state varchar(16) NOT NULL CHECK (state IN ('active','revoked')),
  valid_from bigint NOT NULL,
  valid_until bigint NOT NULL CHECK (valid_until > valid_from),
  version integer NOT NULL CHECK (version > 0)
);
ALTER TABLE public.crm_lab_site_s2s_keys ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_lab_site_s2s_keys FORCE ROW LEVEL SECURITY;
-- No policy grants to crm_app_ci, PUBLIC or an operational service role.

CREATE FUNCTION public.crm_lab_s2s_keys_immutable()
RETURNS trigger LANGUAGE plpgsql SET search_path = pg_catalog AS $guard$
BEGIN
  IF NEW.key_id IS DISTINCT FROM OLD.key_id
    OR NEW.source_service IS DISTINCT FROM OLD.source_service
    OR NEW.audience_service IS DISTINCT FROM OLD.audience_service
    OR NEW.allowed_action IS DISTINCT FROM OLD.allowed_action
    OR NEW.valid_from IS DISTINCT FROM OLD.valid_from
    OR NEW.valid_until IS DISTINCT FROM OLD.valid_until
    OR NEW.version <> OLD.version + 1
    OR OLD.state='revoked'
  THEN
    RAISE EXCEPTION 'S2S key identity immutable; revocation terminal' USING ERRCODE='23514';
  END IF;
  RETURN NEW;
END $guard$;
CREATE TRIGGER crm_lab_s2s_key_mutation
BEFORE UPDATE ON public.crm_lab_site_s2s_keys
FOR EACH ROW EXECUTE FUNCTION public.crm_lab_s2s_keys_immutable();

CREATE TABLE public.crm_lab_site_s2s_inbox (
  source_service varchar(32) NOT NULL,
  nonce uuid NOT NULL,
  key_id varchar(64) NOT NULL REFERENCES public.crm_lab_site_s2s_keys(key_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  organization_id uuid NOT NULL,
  order_reference varchar(128) NOT NULL,
  action varchar(16) NOT NULL CHECK (action IN ('provision','revoke')),
  command_digest char(64) NOT NULL CHECK (command_digest ~ '^[0-9a-f]{64}$'),
  outcome varchar(64) NOT NULL,
  created_at timestamptz NOT NULL DEFAULT now(),
  PRIMARY KEY (source_service,nonce),
  FOREIGN KEY (organization_id,order_reference)
    REFERENCES public.crm_lab_site_provision_orders(organization_id,order_reference)
    ON UPDATE RESTRICT ON DELETE RESTRICT
);
ALTER TABLE public.crm_lab_site_s2s_inbox ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_lab_site_s2s_inbox FORCE ROW LEVEL SECURITY;
-- Deliberately no grants to the tenant-facing CRM role. Inserts only from
-- fixture owner inside the same DB transaction as provision/revoke.

CREATE FUNCTION public.crm_lab_s2s_inbox_append_only()
RETURNS trigger LANGUAGE plpgsql AS $guard$
BEGIN
  RAISE EXCEPTION 'S2S inbox receipts are immutable' USING ERRCODE='42501';
END $guard$;
CREATE TRIGGER crm_lab_s2s_inbox_no_mutation
BEFORE UPDATE OR DELETE OR TRUNCATE ON public.crm_lab_site_s2s_inbox
FOR EACH STATEMENT EXECUTE FUNCTION public.crm_lab_s2s_inbox_append_only();

-- The CI fixture-owner must keep this simple lab inbox to demonstrate
-- replay semantics; a production ledger needs its own retention and outbox.
