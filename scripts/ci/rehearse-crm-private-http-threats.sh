#!/usr/bin/env bash
# CRM-STG-THREAT-01: hostile HTTP regression on disposable fictional data.
# Never uses production routes, secrets, real customers or a VPS.
set -euo pipefail
fail() { echo "CRM STAGING THREAT CI NO-GO: $*" >&2; exit 1; }
[[ "$(printenv GITHUB_ACTIONS || true)" == true ]] || fail "GitHub Actions required"
[[ "$(printenv GITHUB_REPOSITORY || true)" == rafhael100/imagine-crm ]] ||
  fail "wrong repository"
[[ "$(printenv CI || true)" == true ]] || fail "CI required"
[[ "$(printenv CRM_THREAT_LAB || true)" == synthetic-only ]] ||
  fail "missing fictional-only marker"
[[ -d "$(printenv RUNNER_TEMP || true)" ]] || fail "runner directory absent"
[[ -n "$(printenv CRM_STAGING_DB_PASSWORD || true)" ]] ||
  fail "no disposable DB admin credential"
python3 scripts/ci/check-private-staging-compose.py >/dev/null

compose() { docker compose -f infra/staging/compose.yaml "$@"; }
sql() {
  compose exec -T postgres psql -X -qAt -v ON_ERROR_STOP=1 \
    -U crm_staging -d imagine_crm_staging "$@"
}
[[ "$(sql -c 'SELECT current_database()')" == imagine_crm_staging ]] ||
  fail "unexpected database"
[[ "$(sql -c 'SHOW server_version_num')" =~ ^16[0-9]{4}$ ]] ||
  fail "only PostgreSQL16"
expected_sql="
SELECT (SELECT count(*) FROM public.crm_ci_demo_opportunities)::text || '|' ||
       (SELECT count(*) FROM public.crm_ci_demo_tasks)::text || '|' ||
       (SELECT count(*) FROM public.crm_memberships)::text || '|' ||
       (SELECT stage FROM public.crm_ci_demo_opportunities
        WHERE reference='DEMO_002')"
before="$(sql -c "$expected_sql")"
[[ "$before" == '7|4|2|qualified' ]] ||
  fail "not the previously tested fake dataset"

base="http://127.0.0.1:18080"
org_b="b2222222-2222-4222-8222-222222222222"
for iteration in $(seq 1 24); do
  case "$((iteration % 4))" in
    0) locale=pt; expected='Visão geral do seu processo comercial' ;;
    1) locale=en; expected='An overview of your sales workflow' ;;
    2) locale=es; expected='Una visión general de tu proceso comercial' ;;
    3) locale='%3Cscript%3E'; expected='Visão geral do seu processo comercial' ;;
  esac
  result="$(curl --fail --silent --show-error --max-time 7 \
    --header 'Authorization: Bearer SYNTHETIC_CANNOT_AUTHORIZE' \
    --header 'X-Service-Identity: SYNTHETIC_AGENT' \
    --header "X-Tenant-Ref: $org_b" \
    --header "X-Organization: $org_b" \
    --header 'X-Principal-Ref: SYNTHETIC_USER' \
    --header 'X-Forwarded-User: IS_ADMIN_FAKE' \
    --header 'X-Forwarded-Host: evil.example.test' \
    --cookie "tenant=$org_b; is_admin=true" \
    "$base/demo/live?lang=$locale&tenant=$org_b&organization_id=$org_b&is_admin=true&can_checkout=true")"
  [[ "$result" == *"$expected"* ]] || fail "locale $locale failed"
  [[ "$result" == *'EXEMPLO_ALFA'* ]] || fail "fictional tenant A missing"
  [[ "$result" != *'EXEMPLO_OMEGA'* ]] || fail "tenant B data leaked"
  [[ "$result" != *'SYNTHETIC_CANNOT_AUTHORIZE'* ]] ||
    fail "untrusted bearer reflected"
  [[ "$result" != *'SYNTHETIC_AGENT'* ]] ||
    fail "untrusted service claim reflected"
  [[ "$result" == *'data-stage="new" data-count="1"'* ]] ||
    fail "real SQL change not maintained"
  [[ "$result" == *'data-stage="qualified" data-count="2"'* ]] ||
    fail "real SQL stage count changed unexpectedly"
  [[ "$result" != *'<form'* ]] || fail "new form escaped the NO-GO gate"
done

for method in POST PUT PATCH DELETE; do
  result="$(curl --silent --show-error --max-time 5 \
    -o /dev/null -w '%{http_code}' \
    -X "$method" -H 'Content-Type: application/json' \
    -d '{"tenant":"b2222222-2222-4222-8222-222222222222","stage":"won","is_admin":true}' \
    "$base/demo/live")"
  [[ "$result" == 404 || "$result" == 405 || "$result" == 419 ]] ||
    fail "unsafe HTTP method $method returned $result"
done

after="$(sql -c "$expected_sql")"
[[ "$after" == "$before" ]] ||
  fail "hostile requests changed fictional DB state"

# The correct use of DB transactions must not leave a non-local tenant
# marker in the app process. This test verifies repeatability, not pooled
# connection authentication; future production pool QA remains required.
echo "CRM STAGING THREAT CI PASS: 24 cross-request tenant/header attacks,"
echo "four rejected writes, zero foreign tenant rows and unchanged PG16 state."
echo "Only a private fake dashboard was exercised. No actual IAM proven."
