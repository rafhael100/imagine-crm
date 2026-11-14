-- CRM-TASKS-PG01: synthetic follow-up tasks bound to an existing tenant
-- lead. GitHub hosted PostgreSQL16 CI ONLY; not a production migration.
-- Requires SEC-CI-02, crm_app_ci and crm_lab_leads/lead events fixtures.

CREATE TABLE public.crm_lab_followup_tasks (
  id uuid PRIMARY KEY,
  organization_id uuid NOT NULL,
  lead_id uuid NOT NULL,
  synthetic_subject varchar(48) NOT NULL
    CHECK (synthetic_subject ~ '^TASK_[A-Z0-9_]{3,40}$'),
  due_on date NOT NULL,
  status varchar(12) NOT NULL DEFAULT 'todo'
    CHECK (status IN ('todo','done','cancelled')),
  version integer NOT NULL DEFAULT 1 CHECK (version > 0),
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT crm_lab_task_lead_tenant_fk
    FOREIGN KEY (lead_id, organization_id)
    REFERENCES public.crm_lab_leads(id, organization_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT crm_lab_task_tenant_unique UNIQUE (id, organization_id),
  CONSTRAINT crm_lab_task_repeat_guard UNIQUE
    (organization_id, lead_id, synthetic_subject)
);
CREATE INDEX crm_lab_task_worklist
  ON public.crm_lab_followup_tasks (organization_id, status, due_on, id);
ALTER TABLE public.crm_lab_followup_tasks ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_lab_followup_tasks FORCE ROW LEVEL SECURITY;
CREATE POLICY crm_lab_task_tenant_scope ON public.crm_lab_followup_tasks
 FOR ALL TO crm_app_ci
 USING (organization_id = NULLIF(current_setting('app.tenant_id',true),'')::uuid)
 WITH CHECK (organization_id = NULLIF(current_setting('app.tenant_id',true),'')::uuid);

GRANT SELECT (id, organization_id, lead_id, synthetic_subject, due_on,
  status, version, created_at, updated_at)
 ON public.crm_lab_followup_tasks TO crm_app_ci;
GRANT INSERT (id, organization_id, lead_id, synthetic_subject, due_on)
 ON public.crm_lab_followup_tasks TO crm_app_ci;
GRANT UPDATE (status, version)
 ON public.crm_lab_followup_tasks TO crm_app_ci;
-- No DELETE, no changing the tenant/lead/subject/due date at runtime.

CREATE FUNCTION public.crm_lab_task_transition_guard()
RETURNS trigger LANGUAGE plpgsql AS $crm_task_guard$
BEGIN
  IF NEW.id IS DISTINCT FROM OLD.id
     OR NEW.organization_id IS DISTINCT FROM OLD.organization_id
     OR NEW.lead_id IS DISTINCT FROM OLD.lead_id
     OR NEW.synthetic_subject IS DISTINCT FROM OLD.synthetic_subject
     OR NEW.due_on IS DISTINCT FROM OLD.due_on
     OR OLD.status <> 'todo'
     OR NEW.status NOT IN ('done', 'cancelled')
     OR NEW.version <> OLD.version + 1
  THEN
    RAISE EXCEPTION 'Synthetic task transition denied'
      USING ERRCODE = '23514';
  END IF;
  NEW.updated_at := now();
  RETURN NEW;
END
$crm_task_guard$;
CREATE TRIGGER crm_lab_task_monotonic_guard
BEFORE UPDATE ON public.crm_lab_followup_tasks
FOR EACH ROW EXECUTE FUNCTION public.crm_lab_task_transition_guard();

-- Append-only audit, same membership-bound PostgreSQL transaction as task.
CREATE TABLE public.crm_lab_task_events (
  event_id uuid PRIMARY KEY,
  task_id uuid NOT NULL,
  organization_id uuid NOT NULL,
  version integer NOT NULL CHECK (version > 0),
  event_type varchar(16) NOT NULL
    CHECK (event_type IN ('created','completed','cancelled')),
  occurred_at timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT crm_lab_task_event_origin_fk
    FOREIGN KEY (task_id, organization_id)
    REFERENCES public.crm_lab_followup_tasks(id, organization_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT crm_lab_task_event_once UNIQUE (task_id, version),
  CONSTRAINT crm_lab_task_event_shape CHECK (
    (event_type = 'created' AND version = 1)
    OR (event_type IN ('completed','cancelled') AND version > 1)
  )
);
ALTER TABLE public.crm_lab_task_events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_lab_task_events FORCE ROW LEVEL SECURITY;
CREATE POLICY crm_lab_task_event_tenant_scope ON public.crm_lab_task_events
 FOR ALL TO crm_app_ci
 USING (organization_id = NULLIF(current_setting('app.tenant_id',true),'')::uuid)
 WITH CHECK (organization_id = NULLIF(current_setting('app.tenant_id',true),'')::uuid);
GRANT INSERT (event_id, task_id, organization_id, version, event_type)
 ON public.crm_lab_task_events TO crm_app_ci;
-- Neither SELECT nor UPDATE/DELETE/TRUNCATE of events for app role.
