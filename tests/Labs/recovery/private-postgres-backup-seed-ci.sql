-- CRM-RECOVERY-01. GitHub Actions disposable Postgres16 ONLY.
-- These are fictional records. NEVER import into VPS/real CRM databases.
-- The pipeline validates database identity before applying this fixture.
BEGIN;

CREATE ROLE crm_ci_restore_reader NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE
  NOINHERIT NOBYPASSRLS;

CREATE TABLE public.crm_ci_restore_leads (
  id uuid PRIMARY KEY,
  organization_id uuid NOT NULL,
  synthetic_title varchar(32) NOT NULL CHECK (synthetic_title LIKE 'SYNTHETIC_%'),
  CONSTRAINT crm_ci_restore_leads_org_id UNIQUE (id, organization_id)
);

CREATE TABLE public.crm_ci_restore_tasks (
  id uuid PRIMARY KEY,
  organization_id uuid NOT NULL,
  lead_id uuid NOT NULL,
  status varchar(16) NOT NULL DEFAULT 'todo'
    CHECK (status IN ('todo','done','cancelled')),
  CONSTRAINT crm_ci_restore_tasks_org_id UNIQUE (id, organization_id),
  CONSTRAINT crm_ci_restore_task_lead_fk FOREIGN KEY (lead_id, organization_id)
    REFERENCES public.crm_ci_restore_leads (id, organization_id)
);

CREATE TABLE public.crm_ci_restore_events (
  id uuid PRIMARY KEY,
  organization_id uuid NOT NULL,
  task_id uuid NOT NULL,
  event_type varchar(16) NOT NULL CHECK (event_type IN ('created','completed')),
  CONSTRAINT crm_ci_restore_event_task_fk FOREIGN KEY (task_id, organization_id)
    REFERENCES public.crm_ci_restore_tasks (id, organization_id)
);

INSERT INTO public.crm_ci_restore_leads (id, organization_id, synthetic_title)
 VALUES
 ('a5555555-5555-4555-8555-555555555555',
  'a1111111-1111-4111-8111-111111111111', 'SYNTHETIC_A'),
 ('b6666666-6666-4666-8666-666666666666',
  'b2222222-2222-4222-8222-222222222222', 'SYNTHETIC_B');

INSERT INTO public.crm_ci_restore_tasks (id, organization_id, lead_id, status)
 VALUES
 ('a9999999-9999-4999-8999-999999999999',
  'a1111111-1111-4111-8111-111111111111',
  'a5555555-5555-4555-8555-555555555555', 'done'),
 ('b9999999-9999-4999-8999-999999999999',
  'b2222222-2222-4222-8222-222222222222',
  'b6666666-6666-4666-8666-666666666666', 'todo');

INSERT INTO public.crm_ci_restore_events (id, organization_id, task_id, event_type)
 VALUES
 ('a7777777-7777-4777-8777-777777777777',
  'a1111111-1111-4111-8111-111111111111',
  'a9999999-9999-4999-8999-999999999999', 'completed'),
 ('b8888888-8888-4888-8888-888888888888',
  'b2222222-2222-4222-8222-222222222222',
  'b9999999-9999-4999-8999-999999999999', 'created');

ALTER TABLE public.crm_ci_restore_leads ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_ci_restore_leads FORCE ROW LEVEL SECURITY;
ALTER TABLE public.crm_ci_restore_tasks ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_ci_restore_tasks FORCE ROW LEVEL SECURITY;
ALTER TABLE public.crm_ci_restore_events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_ci_restore_events FORCE ROW LEVEL SECURITY;

CREATE POLICY crm_ci_restore_lead_reader ON public.crm_ci_restore_leads
  FOR SELECT TO crm_ci_restore_reader
  USING (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid);
CREATE POLICY crm_ci_restore_task_reader ON public.crm_ci_restore_tasks
  FOR SELECT TO crm_ci_restore_reader
  USING (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid);
CREATE POLICY crm_ci_restore_event_reader ON public.crm_ci_restore_events
  FOR SELECT TO crm_ci_restore_reader
  USING (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid);

GRANT USAGE ON SCHEMA public TO crm_ci_restore_reader;
GRANT SELECT ON public.crm_ci_restore_leads,
  public.crm_ci_restore_tasks,
  public.crm_ci_restore_events TO crm_ci_restore_reader;

COMMIT;
