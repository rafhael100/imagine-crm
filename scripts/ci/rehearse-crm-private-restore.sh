#!/usr/bin/env bash
# CRM-RECOVERY-01: GitHub-hosted CI only. Fictional tenants and records.
set -euo pipefail
fail() { echo "CRM RECOVERY NO-GO: $*" >&2; exit 1; }

[[ "$(printenv GITHUB_ACTIONS || true)" == "true" ]] || fail "GitHub Actions required"
[[ "$(printenv GITHUB_REPOSITORY || true)" == "rafhael100/imagine-crm" ]] || fail "wrong repository"
[[ "$(printenv CI || true)" == "true" ]] || fail "CI required"
[[ "$(printenv CRM_RECOVERY_LAB || true)" == "synthetic-only" ]] || fail "synthetic gate absent"
[[ -n "$(printenv RUNNER_TEMP || true)" && -d "$RUNNER_TEMP" ]] || fail "ephemeral directory required"
[[ -n "$(printenv CRM_STAGING_DB_PASSWORD || true)" &&
   -n "$(printenv CRM_STAGING_APP_KEY || true)" ]] || fail "ephemeral credentials required"
[[ -f tests/Labs/recovery/private-postgres-backup-seed-ci.sql ]] || fail "missing fixture"

compose() { docker compose -f infra/staging/compose.yaml "$@"; }
sql() {
  local db="$1"
  shift
  compose exec -T postgres psql -X -qAt -v ON_ERROR_STOP=1 -U crm_staging -d "$db" "$@"
}
python3 scripts/ci/check-private-staging-compose.py >/dev/null

source_db="imagine_crm_staging"
restore_db="imagine_crm_ci_restore"
[[ "$(printenv GITHUB_RUN_ID || true)" =~ ^[0-9]+$ ]] || fail "missing GitHub runner identity"
restorer="crm-ci-pg16-restore-$GITHUB_RUN_ID"
restored_sql() {
  docker exec -i "$restorer" psql -X -qAt -v ON_ERROR_STOP=1 \
    -U crm_staging -d "$restore_db" "$@"
}
[[ "$(sql "$source_db" -c 'SELECT current_database()')" == "$source_db" ]] ||
  fail "unexpected source database"
[[ "$(sql "$source_db" -c 'SHOW server_version_num')" =~ ^16[0-9]{4}$ ]] ||
  fail "PostgreSQL16 required"
existing="$(sql "$source_db" -c "SELECT count(*) FROM pg_class
 WHERE relnamespace='public'::regnamespace AND relkind IN ('r','p')")"
[[ "$existing" == 0 ]] || fail "source must be entirely empty"
[[ "$(sql "$source_db" -c "SELECT count(*) FROM pg_database
 WHERE datname='$restore_db'")" == 0 ]] || fail "restore database already exists"

tmp="$(mktemp -d "$RUNNER_TEMP/crm-recovery-ci-XXXXXXXX")"
cleanup() {
  docker rm --force --volumes "$restorer" >/dev/null 2>&1 || true
  rm -rf -- "$tmp"
}
trap cleanup EXIT
sql "$source_db" < tests/Labs/recovery/private-postgres-backup-seed-ci.sql \
  > "$tmp/fixture.log" || fail "fixture failed"

dataset_query="
SELECT 'LEAD|' || organization_id || '|' || id || '|' || synthetic_title
 FROM public.crm_ci_restore_leads
UNION ALL
SELECT 'TASK|' || organization_id || '|' || id || '|' || lead_id || '|' || status
 FROM public.crm_ci_restore_tasks
UNION ALL
SELECT 'AUDIT|' || organization_id || '|' || id || '|' || task_id || '|' || event_type
 FROM public.crm_ci_restore_events ORDER BY 1"
policy_query="
SELECT relname || '|' || relrowsecurity::int || '|' || relforcerowsecurity::int
FROM pg_class WHERE oid IN (
 'public.crm_ci_restore_leads'::regclass,
 'public.crm_ci_restore_tasks'::regclass,
 'public.crm_ci_restore_events'::regclass) ORDER BY 1"

sql "$source_db" -c "$dataset_query" > "$tmp/source-rows"
sql "$source_db" -c "$policy_query" > "$tmp/source-policy"
[[ "$(wc -l < "$tmp/source-rows" | tr -d ' ')" == 6 ]] || fail "expected six synthetic rows"
[[ "$(grep -Ec '\|1\|1$' "$tmp/source-policy")" == 3 ]] || fail "FORCE RLS missing"

# Custom-format full DB archive. No uploads. Source never overwritten.
compose exec -T postgres pg_dump -U crm_staging -d "$source_db" \
  --format=custom --no-owner > "$tmp/ci-source.dump"
[[ -s "$tmp/ci-source.dump" ]] || fail "empty pg_dump"
compose exec -T postgres pg_restore --list < "$tmp/ci-source.dump" > "$tmp/toc"
grep -q crm_ci_restore_events "$tmp/toc" || fail "audit table absent from archive"

# Restore into a DISTINCT PostgreSQL16 server process, network=none,
# no exposed ports or shared storage, not merely another database.
docker run --detach --rm --pull=never --network none \
  --security-opt no-new-privileges --name "$restorer" \
  --env POSTGRES_USER=crm_staging --env POSTGRES_DB="$restore_db" \
  --env POSTGRES_PASSWORD="$CRM_STAGING_DB_PASSWORD" \
  postgres:16 > "$tmp/restore-container-id"
[[ "$(docker inspect -f '{{.HostConfig.NetworkMode}}' "$restorer")" == none ]] \
  || fail "restored server has external network access"

ready=0
for attempt in $(seq 1 30); do
  if docker exec "$restorer" pg_isready -q -U crm_staging -d "$restore_db"; then
    ready=1
    break
  fi
  sleep 1
done
[[ "$ready" == 1 ]] || fail "separate restore server not ready"

# pg_dump archives objects, grants and policies but not cluster-global roles.
# Provision this synthetic NOLOGIN role on the NEW isolated instance first.
restored_sql -c "CREATE ROLE crm_ci_restore_reader NOLOGIN
 NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS"
docker exec -i "$restorer" pg_restore -U crm_staging --no-owner --exit-on-error \
  --single-transaction -d "$restore_db" < "$tmp/ci-source.dump" > "$tmp/restore.log"

restored_sql -c "$dataset_query" > "$tmp/restored-rows"
restored_sql -c "$policy_query" > "$tmp/restored-policy"
diff -u "$tmp/source-rows" "$tmp/restored-rows" || fail "data changed in recovery"
diff -u "$tmp/source-policy" "$tmp/restored-policy" || fail "RLS changed in recovery"

scope_counts() {
  local tenant="$1"
  restored_sql <<SQL | grep '^SCOPED|' | tail -n 1
BEGIN;
SET ROLE crm_ci_restore_reader;
SELECT set_config('app.tenant_id', '$tenant', true);
SELECT 'SCOPED|' ||
 (SELECT count(*) FROM public.crm_ci_restore_leads) || '|' ||
 (SELECT count(*) FROM public.crm_ci_restore_tasks) || '|' ||
 (SELECT count(*) FROM public.crm_ci_restore_events);
ROLLBACK;
SQL
}
org_a="a1111111-1111-4111-8111-111111111111"
org_b="b2222222-2222-4222-8222-222222222222"
[[ "$(scope_counts "$org_a")" == "SCOPED|1|1|1" ]] || fail "tenant A sees wrong rows"
[[ "$(scope_counts "$org_b")" == "SCOPED|1|1|1" ]] || fail "tenant B sees wrong rows"
[[ "$(scope_counts "")" == "SCOPED|0|0|0" ]] || fail "unscoped rows visible"

privileges="SELECT (NOT r.rolsuper AND NOT r.rolbypassrls)
 AND NOT has_table_privilege('crm_ci_restore_reader',
  'public.crm_ci_restore_leads','INSERT')
 AND NOT has_table_privilege('crm_ci_restore_reader',
  'public.crm_ci_restore_leads','DELETE')
 AND has_table_privilege('crm_ci_restore_reader',
  'public.crm_ci_restore_leads','SELECT')
 FROM pg_roles r WHERE r.rolname='crm_ci_restore_reader'"
[[ "$(restored_sql -c "$privileges")" == t ]] || fail "least-privilege ACL lost"

echo "CRM RECOVERY CI PASS: custom pg_dump and pg_restore in a DIFFERENT server,"
echo "network=none; six fictional records, three FORCE-RLS tables, two tenants."
echo "NO operational backup, external transfer, production database or VPS deploy."
