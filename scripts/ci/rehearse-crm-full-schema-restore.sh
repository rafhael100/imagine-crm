#!/usr/bin/env bash
# CRM-RECOVERY-03: full already-migrated, fictional CRM schema restore.
# Hosted GitHub CI ONLY. Never touches customer/VPS databases or volumes.
set -euo pipefail
fail() { echo "CRM FULL RESTORE NO-GO: $*" >&2; exit 1; }

[[ "$(printenv GITHUB_ACTIONS || true)" == true ]] || fail "GitHub Actions required"
[[ "$(printenv GITHUB_REPOSITORY || true)" == rafhael100/imagine-crm ]] ||
  fail "unexpected repository"
[[ "$(printenv CI || true)" == true ]] || fail "CI required"
[[ "$(printenv CRM_FULL_RESTORE_LAB || true)" == synthetic-only ]] ||
  fail "synthetic-only marker required"
[[ "$(printenv GITHUB_RUN_ID || true)" =~ ^[0-9]+$ ]] ||
  fail "GitHub runner ID required"
[[ -d "$(printenv RUNNER_TEMP || true)" ]] || fail "ephemeral runner directory required"
[[ -n "$(printenv CRM_STAGING_DB_PASSWORD || true)" ]] || fail "disposable admin password absent"
[[ -n "$(printenv CRM_STAGING_APP_DB_PASSWORD || true)" ]] || fail "disposable app password absent"

compose() { docker compose -f infra/staging/compose.yaml "$@"; }
source_sql() {
  compose exec -T postgres psql -X -qAt -v ON_ERROR_STOP=1 \
    -U crm_staging -d imagine_crm_staging "$@"
}
python3 scripts/ci/check-private-staging-compose.py >/dev/null
[[ "$(source_sql -c 'SELECT current_database()')" == imagine_crm_staging ]] ||
  fail "unexpected source"
[[ "$(source_sql -c 'SHOW server_version_num')" =~ ^16[0-9]{4}$ ]] ||
  fail "PostgreSQL16 required"

# Require exact known CI-only dataset after both preceding migrations and
# fixtures, including the historical recovery fixture and its audit tables.
counts="SELECT
 (SELECT count(*) FROM public.crm_global_identities) || '|' ||
 (SELECT count(*) FROM public.crm_organizations) || '|' ||
 (SELECT count(*) FROM public.crm_memberships) || '|' ||
 (SELECT count(*) FROM public.crm_ci_restore_leads) || '|' ||
 (SELECT count(*) FROM public.crm_ci_restore_tasks) || '|' ||
 (SELECT count(*) FROM public.crm_ci_restore_events) || '|' ||
 (SELECT count(*) FROM public.crm_ci_demo_opportunities) || '|' ||
 (SELECT count(*) FROM public.crm_ci_demo_tasks) || '|' ||
 (SELECT count(*) FROM public.migrations)"
[[ "$(source_sql -c "$counts")" == '2|2|2|2|2|2|7|4|1' ]] ||
  fail "source is not the known fictional CI database"
[[ "$(source_sql -c "SELECT stage FROM crm_ci_demo_opportunities
WHERE reference='DEMO_002'")" == qualified ]] ||
  fail "SQL-backed Kanban HTTP change missing"

# No selected backup path, host, database, tenant or password is accepted
# from a request or operator. Make a random temporary directory in runner.
tmp="$(mktemp -d "$RUNNER_TEMP/crm-full-restore-ci-XXXXXXXX")"
restorer="crm-ci-pg16-full-$GITHUB_RUN_ID"
cleanup() {
  docker rm --force --volumes "$restorer" >/dev/null 2>&1 || true
  rm -rf -- "$tmp"
}
trap cleanup EXIT

data_query="
SELECT 'ID|' || id FROM public.crm_global_identities
UNION ALL
SELECT 'ORG|' || id FROM public.crm_organizations
UNION ALL
SELECT 'MEM|' || organization_id || '|' || id || '|' || status || '|' || version
 FROM public.crm_memberships
UNION ALL
SELECT 'OLDLEAD|' || organization_id || '|' || id || '|' || synthetic_title
 FROM public.crm_ci_restore_leads
UNION ALL
SELECT 'OLDTASK|' || organization_id || '|' || id || '|' || lead_id || '|' || status
 FROM public.crm_ci_restore_tasks
UNION ALL
SELECT 'OLDEVENT|' || organization_id || '|' || id || '|' || task_id || '|' || event_type
 FROM public.crm_ci_restore_events
UNION ALL
SELECT 'LEAD|' || organization_id || '|' || id || '|' || reference || '|' || stage
 FROM public.crm_ci_demo_opportunities
UNION ALL
SELECT 'TASK|' || organization_id || '|' || id || '|' || lead_id || '|' ||
 reference || '|' || subject || '|' || due_day || '|' || state
 FROM public.crm_ci_demo_tasks
UNION ALL
SELECT 'MIG|' || migration FROM public.migrations
ORDER BY 1"
policy_query="
SELECT relname || '|' || relrowsecurity::int || '|' || relforcerowsecurity::int
 FROM pg_class WHERE oid IN (
 'public.crm_memberships'::regclass,
 'public.crm_ci_restore_leads'::regclass,
 'public.crm_ci_restore_tasks'::regclass,
 'public.crm_ci_restore_events'::regclass,
 'public.crm_ci_demo_opportunities'::regclass,
 'public.crm_ci_demo_tasks'::regclass)
ORDER BY 1"

source_sql -c "$data_query" > "$tmp/source-data"
source_sql -c "$policy_query" > "$tmp/source-policy"
[[ "$(wc -l < "$tmp/source-data" | tr -d ' ')" == 24 ]] ||
  fail "wrong synthetic row count"
[[ "$(grep -Ec '\|1\|1$' "$tmp/source-policy")" == 6 ]] ||
  fail "source FORCE RLS not preserved"

compose exec -T postgres pg_dump -U crm_staging -d imagine_crm_staging \
  --format=custom --no-owner > "$tmp/whole-ci.dump"
[[ -s "$tmp/whole-ci.dump" ]] || fail "empty archive"
compose exec -T postgres pg_restore --list < "$tmp/whole-ci.dump" > "$tmp/toc"
for item in crm_memberships crm_ci_demo_opportunities crm_ci_demo_tasks crm_ci_restore_events; do
  grep -q "$item" "$tmp/toc" || fail "archive missing $item"
done

# An entirely separate PG16 process, no network, no ports, no common volumes.
docker run -d --rm --pull=never --network none --security-opt no-new-privileges \
  --name "$restorer" \
  -e POSTGRES_USER=crm_staging -e POSTGRES_DB=imagine_crm_ci_full_restore \
  -e POSTGRES_PASSWORD="$CRM_STAGING_DB_PASSWORD" \
  postgres:16 > "$tmp/restore-container-id"
[[ "$(docker inspect -f '{{.HostConfig.NetworkMode}}' "$restorer")" == none ]] ||
  fail "restore server network is not none"
restored_sql() {
  docker exec -i "$restorer" psql -X -qAt -v ON_ERROR_STOP=1 \
    -U crm_staging -d imagine_crm_ci_full_restore "$@"
}
# The official postgres image starts a *temporary* postmaster during
# initdb, then shuts it down before exec'ing the final PID 1 postgres
# server. pg_isready alone can accept that transient process and race
# the first CREATE ROLE with its shutdown. Verify final PID 1 as well.
ready=0
for attempt in $(seq 1 45); do
  pid1="$(docker exec "$restorer" cat /proc/1/comm 2>/dev/null || true)"
  if [[ "$pid1" == postgres ]] \
    && docker exec "$restorer" pg_isready -q -U crm_staging \
      -d imagine_crm_ci_full_restore \
    && [[ "$(restored_sql -c 'SELECT current_database()' 2>/dev/null || true)" == imagine_crm_ci_full_restore ]]; then
    ready=1
    break
  fi
  sleep 1
done
[[ "$ready" == 1 ]] || fail "isolated final PostgreSQL restore server did not become healthy"

# The SQL archive does not contain cluster-global roles. Recreate only
# harmless NOLOGIN ACL targets. No source passwords are transferred.
restored_sql -c "CREATE ROLE crm_staging_app NOLOGIN
  NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOINHERIT"
restored_sql -c "CREATE ROLE crm_ci_restore_reader NOLOGIN
  NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOINHERIT"

docker exec -i "$restorer" pg_restore -U crm_staging -d imagine_crm_ci_full_restore \
  --no-owner --exit-on-error --single-transaction \
  < "$tmp/whole-ci.dump" > "$tmp/restore.log"

restored_sql -c "$data_query" > "$tmp/restored-data"
restored_sql -c "$policy_query" > "$tmp/restored-policy"
diff -u "$tmp/source-data" "$tmp/restored-data" ||
  fail "data diverged after fully migrated database restore"
diff -u "$tmp/source-policy" "$tmp/restored-policy" ||
  fail "RLS policies lost FORCE setting after restore"
[[ "$(restored_sql -c "$counts")" == '2|2|2|2|2|2|7|4|1' ]] ||
  fail "restored schema count differs"

scope_counts() {
  local role="$1" tenant="$2" tables="$3"
  if [[ "$tables" == modern ]]; then
    restored_sql <<SQL | grep '^SCOPED|' | tail -n 1
BEGIN;
SET ROLE crm_staging_app;
SELECT set_config('app.tenant_id', '$tenant', true);
SELECT 'SCOPED|' ||
 (SELECT count(*) FROM public.crm_memberships) || '|' ||
 (SELECT count(*) FROM public.crm_ci_demo_opportunities) || '|' ||
 (SELECT count(*) FROM public.crm_ci_demo_tasks);
ROLLBACK;
SQL
  else
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
  fi
}
a="a1111111-1111-4111-8111-111111111111"
b="b2222222-2222-4222-8222-222222222222"
[[ "$(scope_counts crm_staging_app "$a" modern)" == 'SCOPED|1|6|3' ]] ||
  fail "restored app tenant A RLS failed"
[[ "$(scope_counts crm_staging_app "$b" modern)" == 'SCOPED|1|1|1' ]] ||
  fail "restored app tenant B RLS failed"
[[ "$(scope_counts crm_staging_app "" modern)" == 'SCOPED|0|0|0' ]] ||
  fail "restored app unscoped RLS failed"
for org in "$a" "$b"; do
  [[ "$(scope_counts crm_ci_restore_reader "$org" previous)" == 'SCOPED|1|1|1' ]] ||
    fail "restored historical audit tenant scope failed"
done
[[ "$(scope_counts crm_ci_restore_reader "" previous)" == 'SCOPED|0|0|0' ]] ||
  fail "restored historical audit unscoped visibility failed"

# Verify restored principal runtime role cannot own tables or mutate them.
role_safety="SELECT CASE WHEN
 NOT r.rolsuper AND NOT r.rolbypassrls AND NOT r.rolcanlogin
 AND NOT pg_has_role('crm_staging_app', c.relowner, 'MEMBER')
 AND has_table_privilege('crm_staging_app',
     'public.crm_ci_demo_opportunities','SELECT')
 AND NOT has_table_privilege('crm_staging_app',
     'public.crm_ci_demo_opportunities','INSERT')
 AND NOT has_table_privilege('crm_staging_app',
     'public.crm_ci_demo_opportunities','UPDATE')
 AND NOT has_table_privilege('crm_staging_app',
     'public.crm_ci_demo_opportunities','DELETE')
 THEN 'safe' ELSE 'unsafe' END
 FROM pg_roles r CROSS JOIN pg_class c
 WHERE r.rolname='crm_staging_app'
 AND c.oid='public.crm_ci_demo_opportunities'::regclass"
[[ "$(restored_sql -c "$role_safety")" == safe ]] ||
  fail "restored app role has unsafe ownership or privileges"

echo "CRM FULL RESTORE CI PASS: actual Laravel migration + fictional leads/tasks/audit,"
echo "24 records, six FORCE-RLS tables, two roles, independent network-none PG16."
echo "No VPS, real customer data, external artifact, domain, payments or access grant."
