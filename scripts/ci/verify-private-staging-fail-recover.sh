#!/usr/bin/env bash
# F3-269: disposable CI failure-and-recovery proof. NEVER run on the VPS.
# Only the disposable PHP-FPM process is frozen/unfrozen; no real services.
set -euo pipefail

if [[ "$GITHUB_EVENT_NAME" != pull_request || "$RUNNER_ENVIRONMENT" != github-hosted ||
      "$COMPOSE_PROJECT_NAME" != imagine-crm-stage-review ||
      "$COMPOSE_FILE" != infra/staging/compose.yaml ]]; then
  echo 'F3-269 NO-GO: unapproved environment.' >&2
  exit 2
fi

origin='http://127.0.0.1:18080'
code() {
  curl --noproxy '*' --silent --output /dev/null --max-time 10 \
    --write-out '%{http_code}' "$origin/up" || true
}

if [[ "$(code)" != 200 ]]; then
  echo 'F3-269 NO-GO: baseline /up unhealthy.' >&2
  exit 1
fi

# Both services must be declared healthy by Docker (not just by one curl).
for svc in app web; do
  cid="$(docker compose ps -q "$svc")"
  if [[ -z "$cid" || "$(docker inspect --format '{{.State.Health.Status}}' "$cid")" != healthy ]]; then
    echo 'F3-270 NO-GO: baseline Docker service health was not healthy.' >&2
    exit 1
  fi
done
echo 'F3-270 app and Nginx start healthy.'

# Suspend the disposable PHP process without removing its container IP/DNS
# from crm_private. This tests an *unresponsive* FastCGI backend.
app_cid="$(docker compose ps -q app)"
if [[ -z "$app_cid" ]]; then
  echo 'F3-269 NO-GO: missing disposable PHP-FPM container.' >&2
  exit 1
fi
paused=false
restore_backend() {
  if [[ "$paused" == true ]]; then
    docker unpause "$app_cid" > /dev/null 2>&1 || true
  fi
}
trap restore_backend EXIT
docker pause "$app_cid" > /dev/null
paused=true
failure="$(code)"
if [[ "$failure" != 502 && "$failure" != 503 && "$failure" != 504 ]]; then
  if [[ "$failure" =~ ^[0-9]{3}$ ]]; then
    echo "F3-269 NO-GO: unexpected three-digit HTTP status $failure while mock backend is down." >&2
  else
    echo 'F3-269 NO-GO: failure produced no valid HTTP status.' >&2
  fi
  web_cid="$(docker compose ps -q web)"
  if [[ -n "$web_cid" && "$(docker inspect --format '{{.State.Running}}' "$web_cid")" == true ]]; then
    echo 'F3-269 diagnostic: Nginx container remains running.' >&2
  else
    echo 'F3-269 diagnostic: Nginx container is not running.' >&2
  fi
  exit 1
fi
headers="$(curl --noproxy '*' --silent --max-time 10 --dump-header - \
  --output /dev/null "$origin/up")"
if ! grep -qi '^x-content-type-options: nosniff' <<< "$headers" ||
   ! grep -qi '^x-frame-options: DENY' <<< "$headers" ||
   ! grep -qi '^cache-control: no-store' <<< "$headers" ||
   grep -qi '^set-cookie:' <<< "$headers"; then
  echo 'F3-269 NO-GO: failure response has missing protections or a cookie.' >&2
  exit 1
fi
echo 'F3-269 unresponsive PHP-FPM returns HTTP 502/503/504 with protective headers, no cookie.'

# The Nginx /up healthcheck must react to the frozen backend.
web_cid="$(docker compose ps -q web)"
became_unhealthy=false
for _ in $(seq 1 12); do
  if [[ "$(docker inspect --format '{{.State.Health.Status}}' "$web_cid")" == unhealthy ]]; then
    became_unhealthy=true
    break
  fi
  sleep 2
done
if [[ "$became_unhealthy" != true ]]; then
  echo 'F3-270 NO-GO: Nginx health did not report unavailable upstream.' >&2
  exit 1
fi
echo 'F3-270 Nginx Docker health detected frozen backend.'

docker unpause "$app_cid" > /dev/null
paused=false
recovered=false
for _ in $(seq 1 30); do
  if [[ "$(code)" == 200 ]]; then
    recovered=true
    break
  fi
  sleep 2
done
if [[ "$recovered" != true ]]; then
  echo 'F3-269 NO-GO: /up did not recover after temporary backend unpause.' >&2
  exit 1
fi
# A recovering web must return to Docker healthy, not just one good response.
became_healthy=false
for _ in $(seq 1 20); do
  if [[ "$(docker inspect --format '{{.State.Health.Status}}' "$web_cid")" == healthy ]]; then
    became_healthy=true
    break
  fi
  sleep 2
done
if [[ "$became_healthy" != true ]]; then
  echo 'F3-270 NO-GO: Docker health did not recover.' >&2
  exit 1
fi
echo 'F3-269 PASS: backend freeze is contained and /up recovers on loopback; DRAFT/NO-DEPLOY.'
echo 'F3-270 PASS: Docker readiness transitions healthy to unhealthy to healthy in isolated CI.'
