-- CRM-LEADS-PG01: isolated, synthetic lead/Kanban ledger for GitHub PG16 CI.
-- NO production migration, no PII, no public route or authorization grant.
-- Apply ONLY after SEC-CI-02 and after crm_app_ci is created in the RLS workflow.

CREATE TABLE public.crm_lab_leads (
  id uuid PRIMARY KEY,
  organization_id uuid NOT NULL REFERENCES public.crm_organizations(id) ON DELETE RESTRICT,
  synthetic_title varchar(64) NOT NULL CHECK (synthetic_title ~ '^[A-Z0-9][A-Z0-9_-]{2,63}$'),
  stage varchar(24) NOT NULL DEFAULT 'new'
    CHECK (stage IN ('new', 'qualified', 'proposal', 'won', 'lost')),
  version integer NOT NULL DEFAULT 1 CHECK (version > 0),
  archived boolean NOT NULL DEFAULT false,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT crm_lab_lead_title_org UNIQUE (organization_id, synthetic_title),
  CONSTRAINT crm_lab_lead_identity_org UNIQUE (id, organization_id)
);
CREATE INDEX crm_lab_leads_org_stage ON public.crm_lab_leads (organization_id, stage, id);
ALTER TABLE public.crm_lab_leads ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_lab_leads FORCE ROW LEVEL SECURITY;

-- A mutable session GUC is a *scope marker only* and is NOT authentication.
-- Real callers must use verified GuardPrincipalSource + membership + Authorizer.
CREATE POLICY crm_lab_leads_tenant_scope ON public.crm_lab_leads
 FOR ALL TO crm_app_ci
 USING (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
 WITH CHECK (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid);

-- The test role cannot delete rows, alter the immutable tenant/title/id
-- or mutate billing, IAM, contact or membership data through this table.
GRANT SELECT (id, organization_id, synthetic_title, stage, version, archived, created_at, updated_at)
  ON TABLE public.crm_lab_leads TO crm_app_ci;
GRANT INSERT (id, organization_id, synthetic_title)
  ON TABLE public.crm_lab_leads TO crm_app_ci;
GRANT UPDATE (stage, version, archived, updated_at)
  ON TABLE public.crm_lab_leads TO crm_app_ci;

-- Even direct SQL through the restricted role cannot reset a version, move
-- tenants or unarchive a record after archival.
CREATE FUNCTION public.crm_lab_lead_monotonic_update()
RETURNS trigger LANGUAGE plpgsql AS $crm_lead_guard$
BEGIN
  IF NEW.id IS DISTINCT FROM OLD.id
     OR NEW.organization_id IS DISTINCT FROM OLD.organization_id
     OR NEW.synthetic_title IS DISTINCT FROM OLD.synthetic_title
     OR OLD.archived IS TRUE
     OR NEW.version <> OLD.version + 1
     OR (OLD.stage IN ('won', 'lost') AND NEW.stage <> OLD.stage)
  THEN
    RAISE EXCEPTION 'Synthetic CRM lead transition denied' USING ERRCODE = '23514';
  END IF;
  NEW.updated_at := now();
  RETURN NEW;
END
$crm_lead_guard$;
CREATE TRIGGER crm_lab_lead_monotonic_guard
BEFORE UPDATE ON public.crm_lab_leads
FOR EACH ROW EXECUTE FUNCTION public.crm_lab_lead_monotonic_update();

-- CRM-LEADS-PG02: append-only synthetic stage audit in the SAME transaction
-- as create/move/archive. No direct SELECT/UPDATE/DELETE for runtime role.
CREATE TABLE public.crm_lab_lead_events (
  event_id uuid PRIMARY KEY,
  lead_id uuid NOT NULL,
  organization_id uuid NOT NULL,
  version integer NOT NULL CHECK (version > 0),
  event_type varchar(20) NOT NULL CHECK (event_type IN ('created','stage_moved','archived')),
  from_stage varchar(24),
  to_stage varchar(24) NOT NULL CHECK (to_stage IN ('new','qualified','proposal','won','lost')),
  occurred_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT crm_lab_lead_event_origin
    FOREIGN KEY (lead_id, organization_id)
    REFERENCES public.crm_lab_leads (id, organization_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT crm_lab_lead_event_once UNIQUE (lead_id, version),
  CONSTRAINT crm_lab_lead_event_shape CHECK (
    (event_type='created' AND version=1 AND from_stage IS NULL AND to_stage='new')
    OR (event_type='stage_moved' AND version>1
        AND from_stage IN ('new','qualified','proposal')
        AND from_stage <> to_stage)
    OR (event_type='archived' AND version>1
        AND from_stage IS NOT NULL AND from_stage=to_stage)
  )
);
CREATE INDEX crm_lab_lead_events_org_record ON public.crm_lab_lead_events
  (organization_id, lead_id, version);
ALTER TABLE public.crm_lab_lead_events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_lab_lead_events FORCE ROW LEVEL SECURITY;
CREATE POLICY crm_lab_lead_events_tenant_scope ON public.crm_lab_lead_events
 FOR ALL TO crm_app_ci
 USING (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
 WITH CHECK (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid);
GRANT INSERT (event_id, lead_id, organization_id, version,
  event_type, from_stage, to_stage)
 ON TABLE public.crm_lab_lead_events TO crm_app_ci;

-- CRM-LEADS-PG03: synthetic site-to-CRM capture idempotency claims.
-- DEFERRED composite FK allows reserving the event key first, then creating
-- the lead and audit record inside the SAME transaction (no partial claim).
-- All identifiers are synthetic; digest is NOT a production HMAC protocol.
CREATE TABLE public.crm_lab_lead_capture_keys (
  organization_id uuid NOT NULL,
  source_code varchar(12) NOT NULL
    CHECK (source_code IN ('sites', 'landing', 'onepage')),
  event_sha256 char(64) NOT NULL
    CHECK (event_sha256 ~ '^[0-9a-f]{64}$'),
  lead_id uuid NOT NULL UNIQUE,
  binding_reference uuid NOT NULL,
  submission_reference uuid NOT NULL,
  site_reference uuid NOT NULL,
  form_reference uuid NOT NULL,
  destination_reference uuid NOT NULL,
  signed_nonce uuid NULL,
  recorded_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT crm_lab_capture_unique
    PRIMARY KEY (organization_id, source_code, event_sha256),
  -- A submission may not silently switch its source or destination.
  CONSTRAINT crm_lab_capture_submission_unique
    UNIQUE (organization_id, submission_reference),
  CONSTRAINT crm_lab_capture_signed_nonce_unique
    UNIQUE (organization_id, signed_nonce),
  CONSTRAINT crm_lab_capture_lead_org_fk
    FOREIGN KEY (lead_id, organization_id)
    REFERENCES public.crm_lab_leads(id, organization_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
    DEFERRABLE INITIALLY DEFERRED
);
ALTER TABLE public.crm_lab_lead_capture_keys ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_lab_lead_capture_keys FORCE ROW LEVEL SECURITY;
CREATE POLICY crm_lab_capture_tenant_scope
 ON public.crm_lab_lead_capture_keys FOR ALL TO crm_app_ci
 USING (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
 WITH CHECK (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid);
GRANT SELECT (organization_id, source_code, event_sha256, lead_id,
  binding_reference, submission_reference, site_reference, form_reference,
  destination_reference, signed_nonce)
 ON public.crm_lab_lead_capture_keys TO crm_app_ci;
GRANT INSERT (organization_id, source_code, event_sha256, lead_id,
  binding_reference, submission_reference, site_reference, form_reference,
  destination_reference, signed_nonce)
 ON public.crm_lab_lead_capture_keys TO crm_app_ci;
-- No UPDATE/DELETE: a claimed event cannot be silently reassigned.

-- PG05: (organization_id,signed_nonce) is unique for non-NULL signed
-- submissions; legacy unsigned synthetic calls carry NULL and are NOT
-- authenticated. The signed capture wrapper must verify the HMAC BEFORE
-- invoking the transactional ledger; the row is reserved atomically with
-- its lead and audit event.

-- PG06: CI-authoritative (fixture-owned) organization → site/form/destination
-- registry. Signed lead intake must LOCK and verify an ACTIVE row under the
-- very same membership-scoped transaction. End users cannot write this table.
CREATE TABLE public.crm_lab_site_delivery_bindings (
  organization_id uuid NOT NULL
    REFERENCES public.crm_organizations(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
  binding_reference uuid NOT NULL,
  site_reference uuid NOT NULL,
  form_reference uuid NOT NULL,
  destination_reference uuid NOT NULL,
  source_code varchar(12) NOT NULL
    CHECK (source_code IN ('sites','landing','onepage')),
  state varchar(16) NOT NULL
    CHECK (state IN ('active', 'suspended', 'revoked')),
  version integer NOT NULL DEFAULT 1 CHECK (version > 0),
  created_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT crm_lab_binding_identity
    PRIMARY KEY (organization_id, binding_reference)
);
ALTER TABLE public.crm_lab_site_delivery_bindings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_lab_site_delivery_bindings FORCE ROW LEVEL SECURITY;
CREATE POLICY crm_lab_binding_tenant_scope
 ON public.crm_lab_site_delivery_bindings FOR SELECT TO crm_app_ci
 USING (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid);
GRANT SELECT (organization_id, binding_reference, site_reference,
  form_reference, destination_reference, source_code, state, version)
 ON public.crm_lab_site_delivery_bindings TO crm_app_ci;

-- Only the disposable fixture owner can provision, revoke or edit bindings.
-- No CREATE/INSERT/UPDATE/DELETE permissions for crm_app_ci.
