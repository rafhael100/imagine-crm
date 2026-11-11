-- F3-45 ONLY: PostgreSQL 16 disposable GitHub Actions database.
-- Never migrate to an operational CRM. Fixed passwords are harmless TEST
-- fixtures, NOT any real service credential or recommended KMS practice.
-- The F3-40 roles are NOLOGIN and already carry fixed-tenant RLS policies.
BEGIN;
CREATE ROLE crm_f345_service_a LOGIN INHERIT PASSWORD 'f345-synthetic-service-a'
  NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS;
CREATE ROLE crm_f345_service_b LOGIN INHERIT PASSWORD 'f345-synthetic-service-b'
  NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS;
GRANT CONNECT ON DATABASE imagine_crm_handoff_ci
  TO crm_f345_service_a,crm_f345_service_b;
-- PostgreSQL 16 membership options: permissions INHERIT, but neither
-- identity may SET ROLE to its parent or administrator.
GRANT crm_f340_receiver_a TO crm_f345_service_a
  WITH INHERIT TRUE, SET FALSE, ADMIN FALSE;
GRANT crm_f340_receiver_b TO crm_f345_service_b
  WITH INHERIT TRUE, SET FALSE, ADMIN FALSE;
COMMIT;
