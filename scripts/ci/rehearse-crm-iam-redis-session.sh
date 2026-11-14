#!/usr/bin/env bash
# CRM-IAM-REDIS-01. Only existing synthetic PHPUnit routes + CI identities.
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
php -m | grep -Eqi '^redis$' || fail "phpredis extension required"
# GitHub runner does not guarantee redis-cli: probe with the PHP client under test.
php -r '
  if (!class_exists("Redis")) { exit(1); }
  $r = new Redis();
  if (!$r->connect("127.0.0.1", 6379, 2.0)
      || !in_array($r->ping(), [true, "+PONG", "PONG"], true)) {
    exit(1);
  }
  $r->close();
' || fail "disposable Redis unavailable"
php scripts/ci/verify-ephemeral-postgres.php --verify >/dev/null
version="$(PGPASSWORD="$(printenv DB_PASSWORD)" psql -X -qAt -h 127.0.0.1 \
  -U postgres -d imagine_crm_ci -c 'SHOW server_version_num')"
[[ "$version" =~ ^16[0-9]{4}$ ]] || fail "PostgreSQL16 required"

# Separate process env: the normal full suite continues with memory sessions.
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
php artisan test --filter=CrmIamRedisConfidentialityTest --no-ansi

echo "CRM IAM REDIS PASS: real Redis-backed session for fake login/TOTP,"
echo "PostgreSQL16 tenant authorization and revocation. No customer login routes."
