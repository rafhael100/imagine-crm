#!/usr/bin/env bash
# CRM-IAM-REDIS-01: server-side Redis sessions + real PostgreSQL16 IAM/RLS.
# Only existing synthetic PHPUnit routes and fixture users; never production.
set -euo pipefail
fail() { echo "CRM IAM REDIS NO-GO: $*" >&2; exit 1; }
[[ "$(printenv GITHUB_ACTIONS || true)" == true ]] || fail "GitHub Actions required"
[[ "$(printenv GITHUB_REPOSITORY || true)" == rafhael100/imagine-crm ]] ||
  fail "unexpected repository"
[[ "$(printenv CI || true)" == true ]] || fail "CI required"
[[ "$(printenv CRM_IAM_REDIS_LAB || true)" == synthetic-only ]] ||
  fail "no explicit synthetic gate"
[[ "$(printenv APP_ENV || true)" == testing ]] || fail "testing environment only"
[[ "$(printenv DB_DATABASE || true)" == imagine_crm_ci ]] ||
  fail "unexpected PostgreSQL database"
[[ "$(printenv DB_HOST || true)" == 127.0.0.1 ]] || fail "untrusted PostgreSQL host"
[[ "$(printenv REDIS_HOST || true)" == 127.0.0.1 ]] || fail "untrusted Redis host"
[[ -n "$(printenv IAM_TEST_USERNAME || true)" ]] || fail "restricted IAM role absent"
[[ -n "$(printenv RLS_TEST_USERNAME || true)" ]] || fail "restricted tenant role absent"
[[ -d "$(printenv RUNNER_TEMP || true)" ]] || fail "ephemeral runner required"
command -v redis-cli >/dev/null 2>&1 || fail "redis-cli not installed"
[[ "$(redis-cli -h 127.0.0.1 -p 6379 ping)" == PONG ]] ||
  fail "disposable Redis unavailable"
php -m | grep -qi '^redis$' || fail "phpredis required"
php scripts/ci/verify-ephemeral-postgres.php --verify >/dev/null
[[ "$(PGPASSWORD="$(printenv DB_PASSWORD)" psql -X -qAt -h 127.0.0.1 \
  -U postgres -d imagine_crm_ci -c 'SHOW server_version_num')" =~ ^16[0-9]{4}$ ]] ||
  fail "PostgreSQL16 missing"

# Isolated session backing store: real Redis, encrypted server-side
# sessions, secure HttpOnly/SameSite cookie on the same CI-only app.
# Leave the existing full CI suite using SESSION_DRIVER=array unchanged.
export SESSION_DRIVER=redis
export SESSION_STORE=redis
export SESSION_CONNECTION=default
export SESSION_ENCRYPT=true
export SESSION_SECURE_COOKIE=true
export SESSION_SAME_SITE=strict
export CRM_AUTH_EXPERIMENTAL=true
export REDIS_CLIENT=phpredis

php artisan test --filter=CrmIamHttpBoundaryTest --no-ansi
php artisan test --filter=CrmIamPostgresHttpMembershipTest --no-ansi
echo "CRM IAM REDIS PASS: test-only HTTP password/TOTP/session/revocation"
echo "and real PostgreSQL16 membership executed with Redis-backed encrypted sessions."
echo "No login or customer data was exposed by application routes."