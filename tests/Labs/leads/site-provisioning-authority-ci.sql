-- F399 CI-ONLY: SERVER-OWNED Site/CRM provisioning orders, never migrations.
-- Must be applied only after SEC-CI-02 to the disposable postgres:16 service.
-- No operational grants, external payment/consent, or real tenants.
CREATE TABLE public.crm_lab_site_provision_orders (
  organization_id uuid NOT NULL
    REFERENCES public.crm_organizations(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
  order_reference varchar(128) NOT NULL,
  binding_reference uuid NOT NULL,
  site_reference uuid NOT NULL,
  form_reference uuid NOT NULL,
  destination_reference uuid NOT NULL,
  product_code varchar(24) NOT NULL CHECK (product_code IN ('onepage','landing_page')),
  source_code varchar(12) NOT NULL CHECK (source_code IN ('onepage','landing')),
  contract_revision integer NOT NULL CHECK (contract_revision > 0),
  payment_state varchar(16) NOT NULL CHECK (payment_state IN ('pending','settled')),
  consent_state varchar(16) NOT NULL CHECK (consent_state IN ('missing','recorded')),
  state varchar(16) NOT NULL CHECK (state IN ('draft','approved','revoked')),
  version integer NOT NULL DEFAULT 1 CHECK (version > 0),
  PRIMARY KEY (organization_id,order_reference),
  UNIQUE (organization_id,binding_reference)
);
ALTER TABLE public.crm_lab_site_provision_orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_lab_site_provision_orders FORCE ROW LEVEL SECURITY;
-- No SELECT/UPDATE/INSERT/DELETE policy for crm_app_ci or PUBLIC.

CREATE TABLE public.crm_lab_site_provision_events (
  id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  organization_id uuid NOT NULL,
  order_reference varchar(128) NOT NULL,
  binding_reference uuid NOT NULL,
  event_type varchar(24) NOT NULL CHECK
    (event_type IN ('binding_provisioned','order_revoked')),
  order_version integer NOT NULL CHECK (order_version > 0),
  created_at timestamptz NOT NULL DEFAULT now(),
  FOREIGN KEY (organization_id,order_reference)
    REFERENCES public.crm_lab_site_provision_orders(organization_id,order_reference)
);
ALTER TABLE public.crm_lab_site_provision_events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_lab_site_provision_events FORCE ROW LEVEL SECURITY;
-- Audit ledger is fixture-owner only; no application role grants.

-- Enforce monotonic order state and immutable identifiers in the lab.
CREATE FUNCTION public.crm_lab_provision_order_guard()
RETURNS trigger LANGUAGE plpgsql SET search_path=pg_catalog AS $body$
BEGIN
 IF NEW.organization_id IS DISTINCT FROM OLD.organization_id
  OR NEW.order_reference IS DISTINCT FROM OLD.order_reference
  OR NEW.binding_reference IS DISTINCT FROM OLD.binding_reference
  OR NEW.site_reference IS DISTINCT FROM OLD.site_reference
  OR NEW.form_reference IS DISTINCT FROM OLD.form_reference
  OR NEW.destination_reference IS DISTINCT FROM OLD.destination_reference
  OR NEW.product_code IS DISTINCT FROM OLD.product_code
  OR NEW.source_code IS DISTINCT FROM OLD.source_code
  OR NEW.contract_revision IS DISTINCT FROM OLD.contract_revision
  OR NEW.version <> OLD.version + 1
  OR OLD.state = 'revoked'
  OR (OLD.state='approved' AND NEW.state='draft')
  OR (OLD.state='approved' AND
      (NEW.payment_state IS DISTINCT FROM OLD.payment_state
        OR NEW.consent_state IS DISTINCT FROM OLD.consent_state))
 THEN
   RAISE EXCEPTION 'Immutable/monotonic provisioning order violated'
     USING ERRCODE='23514';
 END IF;
 RETURN NEW;
END $body$;
CREATE TRIGGER crm_lab_provision_order_mutation
BEFORE UPDATE ON public.crm_lab_site_provision_orders
FOR EACH ROW EXECUTE FUNCTION public.crm_lab_provision_order_guard();

-- A revocation of the independent authoritative order must revoke its form
-- in that exact DB transaction. The existing binding PG07 trigger takes the
-- same advisory xact lock as lead intake, so revocation cannot overtake a
-- committed ingestion invisibly.
CREATE FUNCTION public.crm_lab_provision_order_revoke_binding()
RETURNS trigger LANGUAGE plpgsql SET search_path=pg_catalog AS $body$
BEGIN
 IF NEW.state='revoked' AND OLD.state <> 'revoked' THEN
   UPDATE public.crm_lab_site_delivery_bindings
     SET state='revoked', version=version+1
     WHERE organization_id=NEW.organization_id
       AND binding_reference=NEW.binding_reference
       AND state<>'revoked';
   INSERT INTO public.crm_lab_site_provision_events
     (organization_id,order_reference,binding_reference,event_type,order_version)
   VALUES (NEW.organization_id,NEW.order_reference,NEW.binding_reference,
           'order_revoked',NEW.version);
 END IF;
 RETURN NULL;
END $body$;
CREATE TRIGGER crm_lab_provision_order_after_revoke
AFTER UPDATE OF state ON public.crm_lab_site_provision_orders
FOR EACH ROW EXECUTE FUNCTION public.crm_lab_provision_order_revoke_binding();
