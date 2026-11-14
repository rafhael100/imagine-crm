#!/usr/bin/env bash
# F3-269: disposable CI failure-and-recovery proof. NEVER run on the VPS.
# Only the mock PHP-FPM container is stopped/restarted, never business services.
set -euo pipefail

if [[ "$GITHUB_EVENT_NAME" != pull_request || "$RUNNER_ENVIRONMENT" != github-hosted ||
      "$COMPOSE_PROJECT_NAME" != imagine-crm-stage-review ||
      "$COMPOSE_FILE" != infra/staging/compose.yaml ]]; then
  echo 'F3-269 NO-GO: unapproved environment.' >&2
  exit 2
fi

origin='http://127.0.0.1:18080'
code() {
  curl --noproxy '*' --silent --output /dev/null --max-time 5 \
    --write-out '%{http_code}' "$origin/up" || true
}

if [[ "$(code)" != 200 ]]; then
  echo 'F3-269 NO-GO: baseline /up unhealthy.' >&2
  exit 1
fi

# Explicit fault injection in ephemeral runner only; this cannot touch the VPS.
docker compose stop -t 5 app > /dev/null
failure="$(code)"
if [[ "$failure" != 502 && "$failure" != 503 && "$failure" != 504 ]]; then
  if [[ "$failure" =~ ^[0-9]{3}$ ]]; then
    echo "F3-269 NO-GO: unexpected three-digit HTTP status $failure while mock backend is down." >&2
  else
    echo 'F3-269 NO-GO: failure produced no valid HTTP status.' >&2
  fi
  if docker compose exec -T web nginx -t > /dev/null 2>&1; then
    echo 'F3-269 diagnostic: nginx remains running.' >&2
  else
    echo 'F3-269 diagnostic: nginx was not running.' >&2
  fi
  exit 1
fi
headers="$(curl --noproxy '*' --silent --max-time 5 --dump-header - \
  --output /dev/null "$origin/up")"
if ! grep -qi '^x-content-type-options: nosniff' <<< "$headers" ||
   ! grep -qi '^x-frame-options: DENY' <<< "$headers" ||
   ! grep -qi '^cache-control: no-store' <<< "$headers" ||
   grep -qi '^set-cookie:' <<< "$headers"; then
  echo 'F3-269 NO-GO: failure response has missing protections or a cookie.' >&2
  exit 1
fi
echo 'F3-269 unavailable PHP-FPM returns HTTP 502/503/504 with protective headers, no cookie.'

docker compose start app > /dev/null
recovered=false
for _ in $(seq 1 30); do
  if [[ "$(code)" == 200 ]]; then
    recovered=true
    break
  fi
  sleep 2
done
if [[ "$recovered" != true ]]; then
  echo 'F3-269 NO-GO: /up did not recover after temporary backend restart.' >&2
  exit 1
fi
echo 'F3-269 PASS: backend failure is contained and /up recovers on loopback; DRAFT/NO-DEPLOY.'
