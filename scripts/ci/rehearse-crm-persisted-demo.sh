#!/usr/bin/env bash
# CRM-STG-DATA-01: isolate fictional Kanban/task storage in hosted CI only.
# Never seed or change a customer database or operational CRM table.
set -euo pipefail
fail() { echo "CRM PERSISTED DEMO NO-GO: $*" >&2; exit 1; }
[[ "$(printenv GITHUB_ACTIONS || true)" == true ]] || fail "GitHub Actions required"
[[ "$(printenv GITHUB_REPOSITORY || true)" == rafhael100/imagine-crm ]] ||
  fail "wrong repository"
[[ "$(printenv CI || true)" == true ]] || fail "CI required"
[[ "$(printenv CRM_DEMO_LAB || true)" == synthetic-only ]] || fail "synthetic gate absent"
[[ -d "$(printenv RUNNER_TEMP || true)" ]] || fail "ephemeral GitHub runner required"
[[ -n "$(printenv CRM_STAGING_DB_PASSWORD || true)" ]] || fail "CI-only administrator missing"
[[ -n "$(printenv CRM_STAGING_APP_DB_PASSWORD || true)" ]] || fail "CI app account missing"
[[ -f tests/Labs/demo/ci-persisted-kanban-fixture.sql ]] || fail "synthetic fixture missing"

compose() { docker compose -f infra/staging/compose.yaml "$@"; }
sql() {
  compose exec -T postgres psql -X -qAt -v ON_ERROR_STOP=1 \
    -U crm_staging -d imagine_crm_staging "$@"
}
python3 scripts/ci/check-private-staging-compose.py >/dev/null
[[ "$(sql -c 'SELECT current_database()')" == imagine_crm_staging ]] ||
  fail "unexpected PostgreSQL database"
[[ "$(sql -c 'SHOW server_version_num')" =~ ^16[0-9]{4}$ ]] ||
  fail "requires disposable PostgreSQL16"
[[ "$(sql -c "SELECT count(*) FROM information_schema.tables
WHERE table_schema='public' AND table_name IN
('crm_ci_demo_opportunities','crm_ci_demo_tasks')")" == 0 ]] ||
  fail "synthetic demo tables already exist"
[[ "$(sql -c "SELECT count(*) FROM public.crm_memberships")" == 2 ]] ||
  fail "private Laravel tenant migration/fixture not proved first"

sql < tests/Labs/demo/ci-persisted-kanban-fixture.sql >/dev/null
[[ "$(sql -c 'SELECT count(*) FROM crm_ci_demo_opportunities')" == 7 ]] ||
  fail "wrong number of synthetic opportunities"
[[ "$(sql -c 'SELECT count(*) FROM crm_ci_demo_tasks')" == 4 ]] ||
  fail "wrong number of synthetic tasks"

role_safety="SELECT CASE WHEN
  NOT r.rolsuper AND NOT r.rolbypassrls
  AND NOT pg_has_role('crm_staging_app',a.relowner,'MEMBER')
  AND NOT pg_has_role('crm_staging_app',t.relowner,'MEMBER')
  AND a.relrowsecurity AND a.relforcerowsecurity
  AND t.relrowsecurity AND t.relforcerowsecurity
  AND has_table_privilege('crm_staging_app','crm_ci_demo_opportunities','SELECT')
  AND has_table_privilege('crm_staging_app','crm_ci_demo_tasks','SELECT')
  AND NOT has_table_privilege('crm_staging_app','crm_ci_demo_opportunities','INSERT')
  AND NOT has_table_privilege('crm_staging_app','crm_ci_demo_opportunities','UPDATE')
  AND NOT has_table_privilege('crm_staging_app','crm_ci_demo_opportunities','DELETE')
  AND NOT has_table_privilege('crm_staging_app','crm_ci_demo_tasks','INSERT')
  AND NOT has_table_privilege('crm_staging_app','crm_ci_demo_tasks','UPDATE')
  AND NOT has_table_privilege('crm_staging_app','crm_ci_demo_tasks','DELETE')
  THEN 'safe' ELSE 'unsafe' END
FROM pg_roles r CROSS JOIN pg_class a CROSS JOIN pg_class t
WHERE r.rolname='crm_staging_app'
  AND a.oid='public.crm_ci_demo_opportunities'::regclass
  AND t.oid='public.crm_ci_demo_tasks'::regclass"
[[ "$(sql -c "$role_safety")" == safe ]] ||
  fail "restricted app role or FORCE RLS is unsafe"

scope_counts() {
  local tenant="$1"
  sql <<SQL | grep '^SCOPED|' | tail -n 1
BEGIN;
SET ROLE crm_staging_app;
SELECT set_config('app.tenant_id', '$tenant', true);
SELECT 'SCOPED|' ||
  (SELECT count(*) FROM public.crm_ci_demo_opportunities) || '|' ||
  (SELECT count(*) FROM public.crm_ci_demo_tasks);
ROLLBACK;
SQL
}
org_a="a1111111-1111-4111-8111-111111111111"
org_b="b2222222-2222-4222-8222-222222222222"
[[ "$(scope_counts "$org_a")" == "SCOPED|6|3" ]] || fail "tenant A visibility violated"
[[ "$(scope_counts "$org_b")" == "SCOPED|1|1" ]] || fail "tenant B visibility violated"
[[ "$(scope_counts "")" == "SCOPED|0|0" ]] || fail "unscoped visibility violated"

echo "CRM-STG-DATA-01 PASS: seven synthetic opportunities, four tasks in actual PG16;"
echo "app role SELECT-only, FORCE RLS; A 6/3, B 1/1 and unscoped 0/0."
echo "Only fixed synthetic A is readable by the private live-demo controller."
