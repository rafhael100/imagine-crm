#!/bin/sh
set -eu
test "$POSTGRES_DB" = imagine_crm_private_homologation
psql -X -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" \
 --set=app_pwd="$CRM_PRIVATE_APP_PASSWORD" --set=iam_pwd="$CRM_PRIVATE_IAM_PASSWORD" \
 --set=enroll_pwd="$CRM_PRIVATE_ENROLLMENT_PASSWORD" --set=operator_pwd="$CRM_PRIVATE_OPERATOR_PASSWORD" <<'SQL'
SELECT format('CREATE ROLE %I LOGIN PASSWORD %L NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOINHERIT',
 'crm_homologation_app', :'app_pwd') \gexec
SELECT format('CREATE ROLE %I LOGIN PASSWORD %L NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOINHERIT',
 'crm_homologation_iam', :'iam_pwd') \gexec
SELECT format('CREATE ROLE %I LOGIN PASSWORD %L NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOINHERIT',
 'crm_homologation_enrollment', :'enroll_pwd') \gexec
SELECT format('CREATE ROLE %I LOGIN PASSWORD %L NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOINHERIT',
 'crm_homologation_operator', :'operator_pwd') \gexec
GRANT CONNECT ON DATABASE imagine_crm_private_homologation TO crm_homologation_app,crm_homologation_iam,crm_homologation_enrollment,crm_homologation_operator;
GRANT USAGE ON SCHEMA public TO crm_homologation_app,crm_homologation_iam,crm_homologation_enrollment,crm_homologation_operator;
GRANT SELECT ON crm_private_installation TO crm_homologation_app,crm_homologation_iam,crm_homologation_enrollment,crm_homologation_operator;
GRANT SELECT ON crm_global_identities,crm_auth_credentials,crm_private_accounts TO crm_homologation_iam;
GRANT UPDATE(last_totp_step) ON crm_auth_credentials TO crm_homologation_iam;
GRANT SELECT,INSERT,UPDATE ON crm_auth_sessions,crm_auth_operations TO crm_homologation_iam;
GRANT SELECT,UPDATE(consumed_at) ON crm_auth_recovery_codes TO crm_homologation_iam;
GRANT INSERT ON crm_iam_audit_events TO crm_homologation_iam;
GRANT SELECT ON crm_memberships,crm_private_trials TO crm_homologation_app;
GRANT UPDATE(lock_marker) ON crm_memberships TO crm_homologation_app;
GRANT SELECT,INSERT,UPDATE ON crm_private_leads,crm_private_tasks,crm_private_orders,crm_private_preferences TO crm_homologation_app;
GRANT SELECT,INSERT ON crm_private_events TO crm_homologation_app;
GRANT SELECT,INSERT ON crm_global_identities,crm_organizations,crm_auth_credentials,crm_auth_recovery_codes TO crm_homologation_enrollment;
GRANT INSERT(identity_id,organization_id,label,created_at,desired_plan) ON crm_private_accounts TO crm_homologation_enrollment;
GRANT SELECT,INSERT,UPDATE ON crm_private_signup_requests TO crm_homologation_enrollment;
GRANT SELECT,INSERT,UPDATE ON crm_private_recovery_attempts TO crm_homologation_enrollment;
GRANT UPDATE(password_hash,version) ON crm_auth_credentials TO crm_homologation_enrollment;
GRANT SELECT,UPDATE(consumed_at) ON crm_auth_recovery_codes TO crm_homologation_enrollment;
GRANT SELECT,UPDATE(state) ON crm_auth_sessions TO crm_homologation_enrollment;
GRANT SELECT,INSERT ON crm_private_trial_claims,crm_private_key_versions TO crm_homologation_enrollment;
GRANT INSERT ON crm_private_trials,crm_memberships,crm_private_preferences TO crm_homologation_enrollment;
-- Enrollment may INSERT only within the tenant fixed for its transaction.
GRANT SELECT ON crm_private_accounts,crm_private_trials TO crm_homologation_operator;
GRANT SELECT ON crm_private_orders,crm_private_operator_events TO crm_homologation_operator;
GRANT UPDATE(status) ON crm_private_accounts TO crm_homologation_operator;
GRANT SELECT(identity_id,lock_marker),UPDATE(lock_marker) ON crm_auth_credentials TO crm_homologation_operator;
GRANT UPDATE(state) ON crm_auth_sessions TO crm_homologation_operator;
GRANT SELECT ON crm_auth_sessions TO crm_homologation_operator;
GRANT INSERT ON crm_private_operator_events TO crm_homologation_operator;
SQL
