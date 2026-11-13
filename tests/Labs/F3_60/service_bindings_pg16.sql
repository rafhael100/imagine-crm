\set ON_ERROR_STOP on
-- CI ONLY: fixed synthetic principals and tenant bindings. NEVER use these roles
-- or passwords outside this disposable PostgreSQL test.
CREATE ROLE f360_worker_a LOGIN PASSWORD 'f360-synthetic-a-only';
CREATE ROLE f360_worker_b LOGIN PASSWORD 'f360-synthetic-b-only';
CREATE TABLE public.crm_service_bindings (
  principal_id uuid PRIMARY KEY,
  organization_reference uuid NOT NULL,
  binding_reference uuid NOT NULL,
  site_reference uuid NOT NULL,
  form_reference uuid NOT NULL,
  destination_reference uuid NOT NULL,
  state text NOT NULL CHECK (state IN ('active','revoked')),
  expires_at_epoch bigint NOT NULL CHECK (expires_at_epoch>0)
);
REVOKE ALL ON TABLE public.crm_service_bindings FROM PUBLIC;
GRANT SELECT ON public.crm_service_bindings TO f360_worker_a,f360_worker_b;
ALTER TABLE public.crm_service_bindings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_service_bindings FORCE ROW LEVEL SECURITY;
CREATE POLICY f360_worker_a_scope ON public.crm_service_bindings
  FOR SELECT TO f360_worker_a
  USING (organization_reference='11111111-1111-4111-8111-111111111111'::uuid);
CREATE POLICY f360_worker_b_scope ON public.crm_service_bindings
  FOR SELECT TO f360_worker_b
  USING (organization_reference='99999999-9999-4999-8999-999999999999'::uuid);
INSERT INTO public.crm_service_bindings VALUES
 ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
  '11111111-1111-4111-8111-111111111111',
  '22222222-2222-4222-8222-222222222222',
  '44444444-4444-4444-8444-444444444444',
  '55555555-5555-4555-8555-555555555555',
  '66666666-6666-4666-8666-666666666666','active',2000000000),
 ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
  '99999999-9999-4999-8999-999999999999',
  '88888888-8888-4888-8888-888888888888',
  'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaab',
  'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaac',
  'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaad','active',2000000000),
 ('cccccccc-cccc-4ccc-8ccc-cccccccccccc',
  '11111111-1111-4111-8111-111111111111',
  '22222222-2222-4222-8222-222222222222',
  '44444444-4444-4444-8444-444444444444',
  '55555555-5555-4555-8555-555555555555',
  '66666666-6666-4666-8666-666666666666','revoked',2000000000),
 ('dddddddd-dddd-4ddd-8ddd-dddddddddddd',
  '11111111-1111-4111-8111-111111111111',
  '22222222-2222-4222-8222-222222222222',
  '44444444-4444-4444-8444-444444444444',
  '55555555-5555-4555-8555-555555555555',
  '66666666-6666-4666-8666-666666666666','active',1762000000);
