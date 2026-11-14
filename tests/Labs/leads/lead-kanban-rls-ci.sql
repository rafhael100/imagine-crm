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
  CONSTRAINT crm_lab_lead_title_org UNIQUE (organization_id, synthetic_title)
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
