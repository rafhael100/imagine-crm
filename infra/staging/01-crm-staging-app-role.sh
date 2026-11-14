#!/bin/bash
# CI/private staging only: separate PostgreSQL app LOGIN from bootstrap admin.
set -euo pipefail
test -n "$POSTGRES_USER"
test -n "$POSTGRES_DB"
test -n "$CRM_STAGING_APP_DB_PASSWORD"

# psql :'app_password' quotes the variable as a SQL literal, not raw SQL.
psql --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" \
  -v ON_ERROR_STOP=1 -v app_password="$CRM_STAGING_APP_DB_PASSWORD" <<'SQL'
SELECT format(
  'CREATE ROLE crm_staging_app LOGIN PASSWORD %L NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS',
  :'app_password'
) \gexec
GRANT CONNECT ON DATABASE imagine_crm_staging TO crm_staging_app;
GRANT USAGE ON SCHEMA public TO crm_staging_app;
-- No CREATE, ownership, membership UPDATE or table INSERT/DELETE grants.
SQL
