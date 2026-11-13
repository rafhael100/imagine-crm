\set ON_ERROR_STOP on
-- Disposable PostgreSQL 16 example for multi-company Portal principal; no prod grants.
CREATE ROLE f364_portal_shared LOGIN PASSWORD 'f364-synthetic-shared-only';
CREATE ROLE f364_portal_other LOGIN PASSWORD 'f364-synthetic-other-only';
CREATE TABLE public.crm_service_site_bindings (
  principal_id uuid NOT NULL,
  organization_reference uuid NOT NULL,
  binding_reference uuid NOT NULL,
  site_reference uuid NOT NULL,
  form_reference uuid NOT NULL,
  destination_reference uuid NOT NULL,
  state text NOT NULL CHECK(state IN ('active','revoked')),
  expires_at_epoch bigint NOT NULL CHECK(expires_at_epoch>0),
  PRIMARY KEY(principal_id,site_reference)
);
REVOKE ALL ON TABLE public.crm_service_site_bindings FROM PUBLIC;
GRANT SELECT ON public.crm_service_site_bindings TO f364_portal_shared,f364_portal_other;
ALTER TABLE public.crm_service_site_bindings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.crm_service_site_bindings FORCE ROW LEVEL SECURITY;
CREATE POLICY f364_shared ON public.crm_service_site_bindings
 FOR SELECT TO f364_portal_shared
 USING(principal_id='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'::uuid);
CREATE POLICY f364_other ON public.crm_service_site_bindings
 FOR SELECT TO f364_portal_other
 USING(principal_id='bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'::uuid);
INSERT INTO public.crm_service_site_bindings VALUES
 ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
  '11111111-1111-4111-8111-111111111111',
  '22222222-2222-4222-8222-222222222222',
  '44444444-4444-4444-8444-444444444444',
  '55555555-5555-4555-8555-555555555555',
  '66666666-6666-4666-8666-666666666666','active',2000000000),
 ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
  '99999999-9999-4999-8999-999999999999',
  '88888888-8888-4888-8888-888888888888',
  '77777777-7777-4777-8777-777777777777',
  'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaac',
  'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaad','active',2000000000),
 ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
  '11111111-1111-4111-8111-111111111111',
  '22222222-2222-4222-8222-222222222222',
  'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaae',
  '55555555-5555-4555-8555-555555555555',
  '66666666-6666-4666-8666-666666666666','revoked',2000000000),
 ('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
  '11111111-1111-4111-8111-111111111111',
  '22222222-2222-4222-8222-222222222222',
  'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaf',
  '55555555-5555-4555-8555-555555555555',
  '66666666-6666-4666-8666-666666666666','active',1762000000),
 ('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
  '33333333-3333-4333-8333-333333333333',
  '22222222-2222-4222-8222-222222222222',
  '88888888-8888-4888-8888-888888888888',
  '55555555-5555-4555-8555-555555555555',
  '66666666-6666-4666-8666-666666666666','active',2000000000);
