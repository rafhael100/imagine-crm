-- CRM P0: consent + binding + idempotent lead receipt, disposable PG18 only.
-- ONLY fake .invalid addresses; no HTTP, real person, CRM SaaS or production.
\set ON_ERROR_STOP on
BEGIN;
DO $$
BEGIN
 IF current_database()<>'crm_p0_consent_lab'
    OR current_user<>'postgres'
    OR current_setting('server_version_num')::int<180000 THEN
   RAISE EXCEPTION 'Refusing non-disposable CRM lead laboratory';
 END IF;
END $$;
CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE SCHEMA crm_p0;
REVOKE ALL ON SCHEMA crm_p0 FROM PUBLIC;
CREATE TABLE crm_p0.key_material (
 id int PRIMARY KEY CHECK(id=1),
 secret text NOT NULL CHECK(length(secret)>=32)
);
INSERT INTO crm_p0.key_material VALUES
 (1,'ci-fake-sites-crm-lead-intake-hmac-2026-11-only');
REVOKE ALL ON crm_p0.key_material FROM PUBLIC;
CREATE TABLE crm_p0.bindings (
 organization uuid NOT NULL,
 binding uuid NOT NULL,
 site uuid NOT NULL,
 form uuid NOT NULL,
 destination uuid NOT NULL,
 revision int NOT NULL CHECK(revision>=1),
 active boolean NOT NULL DEFAULT false,
 PRIMARY KEY(organization,binding)
);
CREATE TABLE crm_p0.leads (
 organization uuid NOT NULL,
 submission uuid NOT NULL,
 binding uuid NOT NULL,
 site uuid NOT NULL,
 form uuid NOT NULL,
 destination uuid NOT NULL,
 revision int NOT NULL CHECK(revision>=1),
 notice_revision text NOT NULL CHECK(notice_revision='ci-notice-1'),
 email text NOT NULL CHECK(email ~ '^[a-z0-9._+-]+@example[.]invalid$'),
 consented_at timestamptz NOT NULL DEFAULT clock_timestamp(),
 PRIMARY KEY(organization,submission)
);
ALTER TABLE crm_p0.bindings ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_p0.bindings FORCE ROW LEVEL SECURITY;
ALTER TABLE crm_p0.leads ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_p0.leads FORCE ROW LEVEL SECURITY;
REVOKE ALL ON crm_p0.bindings,crm_p0.leads FROM PUBLIC;
CREATE ROLE crm_p0_intake_ci LOGIN PASSWORD 'ci-fake-intake-sql-only'
 NOSUPERUSER NOBYPASSRLS NOINHERIT NOCREATEDB NOCREATEROLE NOREPLICATION;
GRANT CONNECT ON DATABASE crm_p0_consent_lab TO crm_p0_intake_ci;
GRANT USAGE ON SCHEMA crm_p0 TO crm_p0_intake_ci;

CREATE FUNCTION crm_p0.ingest_fake_consented_lead(
 p_org uuid,p_binding uuid,p_submission uuid,p_site uuid,
 p_form uuid,p_destination uuid,p_revision int,p_consent boolean,
 p_notice text,p_email text,p_expiry bigint,p_mac text
) RETURNS text LANGUAGE plpgsql SECURITY DEFINER
SET search_path=pg_catalog,pg_temp
AS $$
DECLARE
  current_secret text;
  expected_mac text;
  scope text;
  active_binding crm_p0.bindings%ROWTYPE;
  saved crm_p0.leads%ROWTYPE;
  inserted int;
BEGIN
 IF p_consent IS DISTINCT FROM true
    OR p_notice<>'ci-notice-1'
    OR p_email !~ '^[a-z0-9._+-]+@example[.]invalid$'
    OR length(p_email)>120
    OR p_revision<1 OR p_revision>100
    OR p_expiry <= extract(epoch from clock_timestamp())::bigint
    OR p_expiry > extract(epoch from clock_timestamp())::bigint+120
    OR p_mac !~ '^[0-9a-f]{64}$'
    OR p_submission='00000000-0000-0000-0000-000000000000'::uuid THEN
  RAISE EXCEPTION 'Invalid synthetic consent or request' USING ERRCODE='42501';
 END IF;
 SELECT secret INTO current_secret FROM crm_p0.key_material WHERE id=1;
 IF current_secret IS NULL THEN
  RAISE EXCEPTION 'Synthetic intake unavailable' USING ERRCODE='42501';
 END IF;
 scope:=concat_ws('|','v1',p_org::text,p_binding::text,p_submission::text,
  p_site::text,p_form::text,p_destination::text,p_revision::text,
  'consent-true',p_notice,p_email,p_expiry::text);
 expected_mac:=encode(public.hmac(
  convert_to(scope,'UTF8'),convert_to(current_secret,'UTF8'),'sha256'),'hex');
 IF expected_mac IS DISTINCT FROM p_mac THEN
  RAISE EXCEPTION 'Unauthenticated synthetic intake' USING ERRCODE='42501';
 END IF;

 SELECT * INTO active_binding FROM crm_p0.bindings
 WHERE organization=p_org AND binding=p_binding FOR SHARE;
 IF NOT FOUND OR active_binding.active IS DISTINCT FROM true
    OR active_binding.site<>p_site OR active_binding.form<>p_form
    OR active_binding.destination<>p_destination
    OR active_binding.revision<>p_revision THEN
  RAISE EXCEPTION 'Synthetic CRM binding inactive or mismatched' USING ERRCODE='42501';
 END IF;
 INSERT INTO crm_p0.leads(organization,submission,binding,site,
  form,destination,revision,notice_revision,email)
 VALUES(p_org,p_submission,p_binding,p_site,p_form,p_destination,
  p_revision,p_notice,p_email)
 ON CONFLICT(organization,submission) DO NOTHING;
 GET DIAGNOSTICS inserted=ROW_COUNT;
 IF inserted=1 THEN RETURN 'created'; END IF;

 SELECT * INTO saved FROM crm_p0.leads
 WHERE organization=p_org AND submission=p_submission;
 IF NOT FOUND OR saved.binding<>p_binding OR saved.site<>p_site
    OR saved.form<>p_form OR saved.destination<>p_destination
    OR saved.revision<>p_revision OR saved.notice_revision<>p_notice
    OR saved.email<>p_email THEN
   RAISE EXCEPTION 'Synthetic idempotency collision' USING ERRCODE='23505';
 END IF;
 RETURN 'duplicate';
END;
$$;
REVOKE ALL ON FUNCTION crm_p0.ingest_fake_consented_lead(
 uuid,uuid,uuid,uuid,uuid,uuid,int,boolean,text,text,bigint,text) FROM PUBLIC;
GRANT EXECUTE ON FUNCTION crm_p0.ingest_fake_consented_lead(
 uuid,uuid,uuid,uuid,uuid,uuid,int,boolean,text,text,bigint,text)
 TO crm_p0_intake_ci;

-- Only the disposable superuser inserts authoritatively configured bindings.
INSERT INTO crm_p0.bindings(organization,binding,site,form,destination,revision,active)
VALUES
('11111111-1111-4111-8111-111111111111','22222222-2222-4222-8222-222222222222',
 '44444444-4444-4444-8444-444444444444','55555555-5555-4555-8555-555555555555',
 '66666666-6666-4666-8666-666666666666',1,true),
('11111111-1111-4111-8111-111111111111','23232323-2323-4232-8232-232323232323',
 '45454545-4545-4454-8454-454545454545','56565656-5656-4565-8565-565656565656',
 '67676767-6767-4676-8676-676767676767',1,true),
('88888888-8888-4888-8888-888888888888','99999999-9999-4999-8999-999999999999',
 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',1,false);
COMMIT;
