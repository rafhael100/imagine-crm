#!/usr/bin/env bash
# F3-267: only inside an ephemeral GitHub PR runner, never a VPS.
# Does not read secrets, open public ports, seed databases or create accounts.
set -euo pipefail

if [[ "$GITHUB_EVENT_NAME" != "pull_request" || "$RUNNER_ENVIRONMENT" != "github-hosted" ]]; then
  echo 'F3-267 NO-GO: ephemeral GitHub pull_request runner required.' >&2
  exit 2
fi
if [[ "$COMPOSE_PROJECT_NAME" != "imagine-crm-stage-review" || "$COMPOSE_FILE" != "infra/staging/compose.yaml" ]]; then
  echo 'F3-267 NO-GO: incorrect private Compose project.' >&2
  exit 2
fi

docker compose exec -T app php -r '
$required = ["mbstring", "pdo_sqlite", "pdo_pgsql", "intl", "zip", "bcmath", "openssl", "fileinfo"];
foreach ($required as $extension) {
    if (!extension_loaded($extension)) {
        fwrite(STDERR, "F3-267 NO-GO: required PHP extension missing.\n");
        exit(1);
    }
}
echo "F3-267 PHP extensions present.\n";
'
docker compose exec -T app composer check-platform-reqs --no-dev --no-interaction > /dev/null
echo 'F3-267 Composer production platform requirements passed.'
docker compose exec -T app php-fpm -t > /dev/null
docker compose exec -T web nginx -t > /dev/null
echo 'F3-267 FPM and Nginx syntax passed.'

# Render only known state labels for the two ephemeral services. No env,
# mount contents, request bodies, user identifiers or logs are printed.
docker compose ps --all --format json | php -r '
$raw = stream_get_contents(STDIN);
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo "F3-267 container state unavailable.\n";
    exit(0);
}
foreach ($data as $entry) {
    if (!is_array($entry)) { continue; }
    $name = $entry["Service"] ?? "";
    $state = $entry["State"] ?? "";
    if (in_array($name, ["app", "web"], true) &&
        in_array($state, ["running", "exited", "restarting", "created", "paused"], true)) {
        echo "F3-267 state ".$name."=".$state."\n";
    }
}
'

origin="http://127.0.0.1:18080"
status() {
  # Never print body, URLs carrying query parameters, auth values or headers.
  curl --noproxy '*' --silent --output /dev/null --max-time 5 \
    --write-out '%{http_code}' "$@" || true
}

ready=false
for _ in $(seq 1 30); do
  code="$(status "$origin/up")"
  if [[ "$code" == 200 ]]; then
    ready=true
    break
  fi
  sleep 2
done
if [[ "$ready" != true ]]; then
  # HTTP status is a three-digit numeric diagnostic only; never echo a body,
  # URL, response header, environment value, host path or service log.
  if [[ "$code" =~ ^[0-9]{3}$ ]]; then
    echo "F3-267 NO-GO: /up HTTP code $code, expected 200." >&2
  else
    echo 'F3-267 NO-GO: /up failed without a safe HTTP status code.' >&2
  fi
  if docker compose exec -T web sh -c 'wget -q -T 5 -O /dev/null http://127.0.0.1:8080/up' > /dev/null 2>&1; then
    echo 'F3-267 diagnostic: nginx container-local /up responds successfully; runner loopback is blocked.' >&2
  else
    echo 'F3-267 diagnostic: nginx container-local /up failed or web process unavailable.' >&2
  fi
  exit 1
fi
echo 'F3-267 /up HTTP 200 on loopback.'

head_code="$(status --head "$origin/up")"
if [[ "$head_code" != 200 ]]; then
  echo 'F3-267 NO-GO: /up HEAD failed.' >&2
  exit 1
fi

for endpoint in / /login /register /admin /api/leads /api/organizations /api/auth /api/v1/leads /api/agents /debug; do
  code="$(status "$origin$endpoint")"
  if [[ "$code" != 404 ]]; then
    echo 'F3-267 NO-GO: unpublished business endpoint was not 404.' >&2
    exit 1
  fi
done
echo 'F3-267 unpublished GET routes denied.'

for endpoint in /login /register /api/leads /api/auth; do
  code="$(status --request POST --header 'Content-Type: application/json' --data '{}' "$origin$endpoint")"
  if [[ "$code" != 404 && "$code" != 405 ]]; then
    echo 'F3-267 NO-GO: business POST endpoint returned unexpected result.' >&2
    exit 1
  fi
done
echo 'F3-267 unpublished POST routes denied.'

for endpoint in /.env /vendor/autoload.php /storage/.env /artisan /composer.lock; do
  code="$(status "$origin$endpoint")"
  if [[ "$code" != 403 && "$code" != 404 ]]; then
    echo 'F3-267 NO-GO: internal filesystem resource may be exposed.' >&2
    exit 1
  fi
done
echo 'F3-267 filesystem/internal paths denied.'

headers="$(curl --noproxy '*' --silent --show-error --max-time 5 --dump-header - \
  --output /dev/null "$origin/up")"
if ! grep -qi '^x-content-type-options: nosniff' <<< "$headers" ||
   ! grep -qi '^x-frame-options: DENY' <<< "$headers" ||
   ! grep -qi '^cache-control: no-store' <<< "$headers"; then
  echo 'F3-267 NO-GO: private web security headers absent.' >&2
  exit 1
fi

echo 'F3-267 PASS: disposable local smoke passed; still DRAFT/NO-DEPLOY.'
