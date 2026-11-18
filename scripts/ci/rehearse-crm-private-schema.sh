#!/usr/bin/env bash
# CRM-STG-SCHEMA-01: real Laravel migration rehearsal in disposable Compose.
# This script must NEVER be used with live CRM or Leaseweb databases.
set -euo pipefail
fail() { echo "CRM SCHEMA CI NO-GO: $*" >&2; exit 1; }

[[ "$(printenv GITHUB_ACTIONS || true)" == true ]] || fail "GitHub Actions required"
[[ "$(printenv GITHUB_REPOSITORY || true)" == rafhael100/imagine-crm ]] ||
  fail "wrong repository"
[[ "$(printenv CI || true)" == true ]] || fail "CI required"
[[ "$(printenv CRM_SCHEMA_LAB || true)" == synthetic-only ]] ||
  fail "synthetic lab marker required"
[[ -d "$(printenv RUNNER_TEMP || true)" ]] || fail "ephemeral runner required"
[[ -n "$(printenv CRM_STAGING_DB_PASSWORD || true)" ]] ||
  fail "admin CI credential missing"
[[ -n "$(printenv CRM_STAGING_APP_DB_PASSWORD || true)" ]] ||
  fail "runtime CI credential missing"
[[ "$CRM_STAGING_DB_PASSWORD" != "$CRM_STAGING_APP_DB_PASSWORD" ]] ||
  fail "administrator and runtime credentials must differ"

compose() { docker compose -f infra/staging/compose.yaml "$@"; }
sql() {
  compose exec -T postgres psql -X -qAt -v ON_ERROR_STOP=1 \
    -U crm_staging -d imagine_crm_staging "$@"
}
python3 scripts/ci/check-private-staging-compose.py >/dev/null
[[ "$(sql -c 'SELECT current_database()')" == imagine_crm_staging ]] ||
  fail "wrong database"
[[ "$(sql -c 'SHOW server_version_num')" =~ ^16[0-9]{4}$ ]] ||
  fail "PostgreSQL16 required"

# The recovery CI previously verified blank-source import and independent
# restore; these tenant schema tables must still be absent before migrating.
[[ "$(sql -c "SELECT count(*) FROM information_schema.tables
 WHERE table_schema='public' AND table_name IN
 ('crm_memberships','crm_organizations','crm_global_identities','migrations')")" == 0 ]] ||
  fail "schema is not pristine"


role_check="SELECT CASE WHEN
 r.rolcanlogin AND NOT r.rolsuper AND NOT r.rolbypassrls
 AND NOT r.rolcreatedb AND NOT r.rolcreaterole
 AND NOT pg_has_role('crm_staging_app','crm_staging','MEMBER')
 THEN 'safe' ELSE 'unsafe' END
 FROM pg_roles r WHERE r.rolname='crm_staging_app'"
[[ "$(sql -c "$role_check")" == safe ]] ||
  fail "app database role is missing or elevated"

# Administrator access exists ONLY for CI migration; the app container normally
# starts as crm_staging_app. Forward password via environment without argv.
migrate_admin() {
  DB_PASSWORD="$CRM_STAGING_DB_PASSWORD" compose exec -T \
    -e DB_USERNAME=crm_staging -e DB_PASSWORD app \
    php artisan migrate --force --no-interaction
}
migrate_admin
migrate_admin # no-op/idempotent; this must not recreate or destroy data

migration_check="SELECT count(*) FROM public.migrations
 WHERE migration='2026_11_04_000001_create_crm_identity_organizations_memberships'"
[[ "$(sql -c "$migration_check")" == 1 ]] ||
  fail "Laravel migration is not idempotent"

schema_check="SELECT CASE WHEN c.relrowsecurity AND c.relforcerowsecurity
 AND (SELECT count(*) FROM pg_policies WHERE schemaname='public'
      AND tablename='crm_memberships'
      AND policyname='crm_memberships_tenant_scope')=1
 THEN 'protected' ELSE 'unsafe' END FROM pg_class c
 WHERE c.oid='public.crm_memberships'::regclass"
[[ "$(sql -c "$schema_check")" == protected ]] ||
  fail "membership RLS not FORCE enabled"

# The runtime has no mutation privileges. SELECT is a disposable CI grant
# solely for testing the RLS membrane; the real IAM policy remains NO-GO.
sql <<'SQL' >/dev/null
GRANT SELECT ON public.crm_memberships TO crm_staging_app;

INSERT INTO public.crm_global_identities (id, created_at, updated_at)
VALUES ('a3333333-3333-4333-8333-333333333333', now(), now()),
       ('b4444444-4444-4444-8444-444444444444', now(), now());

INSERT INTO public.crm_organizations (id, created_at, updated_at)
VALUES ('a1111111-1111-4111-8111-111111111111', now(), now()),
       ('b2222222-2222-4222-8222-222222222222', now(), now());

INSERT INTO public.crm_memberships
 (id, organization_id, global_identity_id, status, version, created_at, updated_at)
VALUES
 ('a7777777-7777-4777-8777-777777777777',
  'a1111111-1111-4111-8111-111111111111',
  'a3333333-3333-4333-8333-333333333333', 'active', 1, now(), now()),
 ('b8888888-8888-4888-8888-888888888888',
  'b2222222-2222-4222-8222-222222222222',
  'b4444444-4444-4444-8444-444444444444', 'active', 1, now(), now());
SQL

# Executes real Laravel bootstrap + PDO using the Compose runtime role.
# Without migration-admin overrides it MUST be non-owner and NO-BYPASSRLS.
compose exec -T -e CI=true -e GITHUB_ACTIONS=true \
  -e GITHUB_REPOSITORY=rafhael100/imagine-crm \
  -e CRM_SCHEMA_LAB=synthetic-only \
  app php scripts/ci/check-staging-runtime-schema.php

# Scope marker is not trusted identity; test does not create user sessions.
# Another CI-only migration pass must remain a no-op after fictional seeding.
migrate_admin
[[ "$(sql -c 'SELECT count(*) FROM public.crm_memberships')" == 2 ]] ||
  fail "repeating migration changed fictional membership data"
echo "CRM SCHEMA CI PASS: Laravel migrated once and stayed idempotent;"
echo "runtime DB role is non-owner, non-superuser and membership FORCE RLS scoped."
echo "No real login, CRM business rights, checkout, Asaas or VPS touched."
