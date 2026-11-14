#!/usr/bin/env bash
# CRM WEB-P3: ONLY on ephemeral GitHub-hosted CI. NOT for Leaseweb/VPS.
set -euo pipefail

if [[ "$GITHUB_ACTIONS" != "true" || "$RUNNER_ENVIRONMENT" != "github-hosted" || "$GITHUB_EVENT_NAME" == "workflow_dispatch" ]]; then
  echo "NO-GO: disposable GitHub runner required" >&2
  exit 2
fi
if [[ "$CI_CRM_PREVIEW_DOCKER" != "synthetic-only" ]]; then
  echo "NO-GO: explicit synthetic CI scope required" >&2
  exit 2
fi

prototype="prototypes/site-comercial-crm"
image="imagine-crm-preview-ci:$GITHUB_SHA"
name="imagine-crm-preview-ci-$GITHUB_RUN_ID"
stage="$(mktemp -d)"
trap 'docker rm -f "$name" >/dev/null 2>&1 || true; docker image rm "$image" >/dev/null 2>&1 || true; rm -rf "$stage"' EXIT

# A throwaway credential; hashed file only on the disposable runner.
user="ci-review"
password="$(openssl rand -hex 18)"
printf '%s:%s\n' "$user" "$(printf '%s' "$password" | openssl passwd -apr1 -stdin)" > "$stage/htpasswd"
chmod 0644 "$stage/htpasswd"

# Context strictly restricted with .dockerignore (not the repository root).
docker build --quiet --file "$prototype/staging/Dockerfile-preview-ci" --tag "$image" "$prototype" >/dev/null

docker run --detach --name "$name" \
  --user 101:101 --read-only --cap-drop ALL \
  --security-opt no-new-privileges \
  --tmpfs /tmp:rw,nosuid,size=16m,uid=101,gid=101 \
  --mount "type=bind,src=$stage/htpasswd,dst=/etc/nginx/crm-preview.htpasswd,readonly" \
  --publish 127.0.0.1:18081:8080 \
  "$image" >/dev/null

docker exec "$name" nginx -t >/dev/null 2>&1
docker inspect "$name" --format '{{json .HostConfig}}' | python3 -c '
import json,sys
d=json.load(sys.stdin)
assert d["ReadonlyRootfs"] is True
assert d["Privileged"] is False
assert d["SecurityOpt"] == ["no-new-privileges"]
assert "ALL" in d["CapDrop"]
assert d["PortBindings"] == {"8080/tcp":[{"HostIp":"127.0.0.1","HostPort":"18081"}]}
print("PASS: non-root, rootfs read-only, cap-drop ALL, port 127.0.0.1")
'
test "$(docker inspect "$name" --format '{{.Config.User}}')" = "101:101"

origin="http://127.0.0.1:18081"
code() {
  curl --noproxy '*' --silent --max-time 5 --output /dev/null \
    --write-out '%{http_code}' "$@" || true
}
ready="false"
for i in $(seq 1 25); do
  if [[ "$(code "$origin/index.html")" == "401" ]]; then
    ready="true"
    break
  fi
  sleep 1
done
if [[ "$ready" != true ]]; then
  echo "FAIL: protected Nginx did not answer 401 on ephemeral localhost" >&2
  exit 1
fi

for asset in / /index.html /assets/logo-imagine.png /assets/site.css /assets/site.js /fluxo-demo/index.html; do
  [[ "$(code "$origin$asset")" == "401" ]] || { echo "FAIL: anonymous resource not protected"; exit 1; }
  [[ "$(code --user "$user:wrong" "$origin$asset")" == "401" ]] || { echo "FAIL: wrong password was accepted"; exit 1; }
  [[ "$(code --user "$user:$password" "$origin$asset")" == "200" ]] || { echo "FAIL: approved fixture inaccessible"; exit 1; }
  echo "PASS: anonymous 401 / authenticated 200 for one known static asset"
done

for asset in /index.html /assets/logo-imagine.png /assets/site.css /assets/site.js /fluxo-demo/index.html; do
  curl --noproxy '*' --fail --silent --show-error --max-time 10 \
    --user "$user:$password" --output "$stage/response.bin" "$origin$asset"
  cmp "$prototype$asset" "$stage/response.bin" >/dev/null || {
    echo "FAIL: contents changed during HTTP delivery" >&2
    exit 1
  }
done
echo "PASS: all five delivered assets match the approved source bytes"

for forbidden in /api/leads /api/checkout /login /register /admin /.env /composer.lock /manifest.sha256; do
  [[ "$(code --user "$user:$password" "$origin$forbidden")" == "404" ]] || {
    echo "FAIL: forbidden route/path returned unexpected status" >&2
    exit 1
  }
done
echo "PASS: unpublished routes and internal files deny 404"

# No directories or hidden runtime endpoints may be served.
for forbidden in /assets/ /fluxo-demo/ /index.php /wp-admin /robots.txt; do
  [[ "$(code --user "$user:$password" "$origin$forbidden")" == "404" ]] || {
    echo "FAIL: unintended directory or runtime endpoint exposed" >&2
    exit 1
  }
done
[[ "$(code --head "$origin/index.html")" == "401" ]] || {
  echo "FAIL: unauthenticated HEAD was not denied" >&2
  exit 1
}
echo "PASS: directory listings, PHP and anonymous HEAD denied"

[[ "$(code --request POST --user "$user:$password" "$origin/")" == "403" ]] || {
  echo "FAIL: POST not denied" >&2; exit 1
}
[[ "$(code --head --user "$user:$password" "$origin/")" == "200" ]] || {
  echo "FAIL: HEAD unavailable" >&2; exit 1
}

curl --noproxy '*' --silent --max-time 5 --head \
  --user "$user:$password" "$origin/index.html" > "$stage/headers-main.txt"
curl --noproxy '*' --silent --max-time 5 --head \
  --user "$user:$password" "$origin/fluxo-demo/index.html" > "$stage/headers-trial.txt"
for file in "$stage/headers-main.txt" "$stage/headers-trial.txt"; do
  grep -Eiq '^Cache-Control: .*no-store' "$file"
  grep -Eiq '^X-Robots-Tag: noindex, nofollow' "$file"
  grep -Eiq '^X-Content-Type-Options: nosniff' "$file"
  grep -Eiq '^X-Frame-Options: DENY' "$file"
  grep -Eiq '^Content-Security-Policy: .*connect-src .none.' "$file"
  grep -Eiq '^Content-Security-Policy: .*form-action .none.' "$file"
done
grep -Eq "script-src 'self'" "$stage/headers-main.txt"
if grep -Eq "script-src 'unsafe-inline'" "$stage/headers-main.txt"; then
  echo "FAIL: main page unexpectedly allows inline scripts" >&2
  exit 1
fi
grep -Eq "script-src 'unsafe-inline'" "$stage/headers-trial.txt"
if grep -Eiq '^Set-Cookie:' "$stage/headers-main.txt" || grep -Eiq '^Set-Cookie:' "$stage/headers-trial.txt"; then
  echo "FAIL: preview unexpectedly sets a session cookie" >&2
  exit 1
fi
for entry in \
  '/index.html:text/html' \
  '/fluxo-demo/index.html:text/html' \
  '/assets/site.css:text/css' \
  '/assets/site.js:javascript' \
  '/assets/logo-imagine.png:image/png'; do
  route="$(printf '%s' "$entry" | cut -d: -f1)"
  mime="$(printf '%s' "$entry" | cut -d: -f2)"
  curl --noproxy '*' --silent --max-time 5 --head \
    --user "$user:$password" "$origin$route" > "$stage/type-headers.txt"
  grep -Ei "^Content-Type: .*$mime" "$stage/type-headers.txt" >/dev/null || {
    echo "FAIL: wrong Content-Type for a static asset" >&2
    exit 1
  }
done
echo "PASS: CSP, no-store, robots, nosniff, no cookies, MIME and scoped inline exception"

echo "PASS: isolated synthetic HTTP CI smoke. Nothing installed on the VPS."
