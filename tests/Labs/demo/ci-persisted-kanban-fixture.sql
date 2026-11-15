-- CRM-STG-DATA-01. ONLY a fresh disposable private GitHub Actions PG16.
-- This separate demonstration namespace is NOT the operational CRM ledger.
-- All labels are synthetic uppercase tokens. No people, CNPJ, email or phone.
BEGIN;

CREATE TABLE public.crm_ci_demo_opportunities (
    id uuid PRIMARY KEY,
    organization_id uuid NOT NULL CHECK (
      organization_id IN (
        'a1111111-1111-4111-8111-111111111111'::uuid,
        'b2222222-2222-4222-8222-222222222222'::uuid
      )
    ),
    reference varchar(16) NOT NULL CHECK (reference ~ '^DEMO_[0-9]{3}$'),
    label varchar(32) NOT NULL CHECK (label ~ '^EXEMPLO_[A-Z]+$'),
    detail varchar(32) NOT NULL CHECK (detail ~ '^REGISTRO_FICTICIO_[A-Z]+$'),
    stage varchar(12) NOT NULL CHECK (stage IN ('new', 'qualified', 'proposal', 'won')),
    UNIQUE (id, organization_id),
    UNIQUE (organization_id, reference)
);

CREATE TABLE public.crm_ci_demo_tasks (
    id uuid PRIMARY KEY,
    organization_id uuid NOT NULL,
    lead_id uuid NOT NULL,
    reference varchar(20) NOT NULL CHECK (reference ~ '^TASK_DEMO_[0-9]{3}$'),
    subject varchar(36) NOT NULL CHECK (subject ~ '^FOLLOW_UP_DEMO_[A-Z]+$'),
    due_day date NOT NULL CHECK (due_day BETWEEN '2026-12-01' AND '2026-12-31'),
    state varchar(9) NOT NULL DEFAULT 'todo' CHECK (state IN ('todo','done')),
    UNIQUE (organization_id, reference),
    FOREIGN KEY (lead_id, organization_id)
      REFERENCES public.crm_ci_demo_opportunities (id, organization_id)
);

ALTER TABLE public.crm_ci_demo_opportunities ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_ci_demo_opportunities FORCE ROW LEVEL SECURITY;
ALTER TABLE public.crm_ci_demo_tasks ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_ci_demo_tasks FORCE ROW LEVEL SECURITY;

CREATE POLICY crm_ci_demo_opportunity_select
  ON public.crm_ci_demo_opportunities FOR SELECT TO crm_staging_app
  USING (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid);
CREATE POLICY crm_ci_demo_task_select
  ON public.crm_ci_demo_tasks FOR SELECT TO crm_staging_app
  USING (organization_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid);

GRANT SELECT ON public.crm_ci_demo_opportunities, public.crm_ci_demo_tasks
  TO crm_staging_app;
-- The role has no INSERT, UPDATE, DELETE, TRUNCATE, CREATE or ownership.

INSERT INTO public.crm_ci_demo_opportunities
(id, organization_id, reference, label, detail, stage) VALUES
('a0000000-0000-4000-8000-000000000001','a1111111-1111-4111-8111-111111111111','DEMO_001','EXEMPLO_ALFA','REGISTRO_FICTICIO_A','new'),
('a0000000-0000-4000-8000-000000000002','a1111111-1111-4111-8111-111111111111','DEMO_002','EXEMPLO_BETA','REGISTRO_FICTICIO_B','new'),
('a0000000-0000-4000-8000-000000000003','a1111111-1111-4111-8111-111111111111','DEMO_003','EXEMPLO_GAMA','REGISTRO_FICTICIO_C','qualified'),
('a0000000-0000-4000-8000-000000000004','a1111111-1111-4111-8111-111111111111','DEMO_004','EXEMPLO_DELTA','REGISTRO_FICTICIO_D','proposal'),
('a0000000-0000-4000-8000-000000000005','a1111111-1111-4111-8111-111111111111','DEMO_005','EXEMPLO_EPSILON','REGISTRO_FICTICIO_E','proposal'),
('a0000000-0000-4000-8000-000000000006','a1111111-1111-4111-8111-111111111111','DEMO_006','EXEMPLO_ZETA','REGISTRO_FICTICIO_F','won'),
('b0000000-0000-4000-8000-000000000001','b2222222-2222-4222-8222-222222222222','DEMO_101','EXEMPLO_OMEGA','REGISTRO_FICTICIO_Z','new');

INSERT INTO public.crm_ci_demo_tasks
(id, organization_id, lead_id, reference, subject, due_day, state) VALUES
('a1000000-0000-4000-8000-000000000001','a1111111-1111-4111-8111-111111111111','a0000000-0000-4000-8000-000000000001','TASK_DEMO_001','FOLLOW_UP_DEMO_A','2026-12-08','todo'),
('a1000000-0000-4000-8000-000000000002','a1111111-1111-4111-8111-111111111111','a0000000-0000-4000-8000-000000000003','TASK_DEMO_002','FOLLOW_UP_DEMO_B','2026-12-10','todo'),
('a1000000-0000-4000-8000-000000000003','a1111111-1111-4111-8111-111111111111','a0000000-0000-4000-8000-000000000004','TASK_DEMO_003','FOLLOW_UP_DEMO_C','2026-12-12','todo'),
('b1000000-0000-4000-8000-000000000001','b2222222-2222-4222-8222-222222222222','b0000000-0000-4000-8000-000000000001','TASK_DEMO_101','FOLLOW_UP_DEMO_Z','2026-12-14','todo');

COMMIT;
