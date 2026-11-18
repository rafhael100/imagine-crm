#!/bin/bash
set -euo pipefail
export LC_ALL=C
readonly pgdir=/tmp/p20-cluster
readonly pgbin=/usr/lib/postgresql/18/bin
[[ ! -e "$pgdir" ]]
[[ "${DB_HOST:-}" == 127.0.0.1 && "${DB_PORT:-}" == 55432 ]]
[[ "${DB_DATABASE:-}" == portal_automation_p20_ci && "${DB_USERNAME:-}" == postgres ]]
[[ "${APP_ENV:-}" == testing && "${CI:-}" == true ]]
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
mkdir -m 700 "$pgdir"
printf '%s' "$DB_PASSWORD" > /tmp/p20-owner.password
chmod 600 /tmp/p20-owner.password
"$pgbin/initdb" -D "$pgdir" -U postgres --auth-local=trust --auth-host=scram-sha-256 --pwfile=/tmp/p20-owner.password --no-locale --encoding=UTF8 >/tmp/p20-initdb.log
rm /tmp/p20-owner.password
"$pgbin/pg_ctl" -D "$pgdir" -l /tmp/p20-postgres.log -o '-h 127.0.0.1 -p 55432 -k /tmp' -w start
trap '"$pgbin/pg_ctl" -D "$pgdir" -m fast -w stop' EXIT TERM INT
# Uses the pinned Portal helper for local process evidence; one verified bootstrap PDO creates only the dedicated database.
php /p20-code/portal-create-database.php
while "$pgbin/pg_ctl" -D "$pgdir" status >/dev/null; do sleep 1; done
