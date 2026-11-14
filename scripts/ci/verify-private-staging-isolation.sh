#!/usr/bin/env bash
# F3-268: runtime isolation checks in GitHub's disposable runner ONLY.
# Does not test VPS firewalls or infer an independent security approval.
set -euo pipefail

if [[ "$GITHUB_EVENT_NAME" != pull_request || "$RUNNER_ENVIRONMENT" != github-hosted ||
      "$COMPOSE_PROJECT_NAME" != imagine-crm-stage-review ||
      "$COMPOSE_FILE" != infra/staging/compose.yaml ]]; then
  echo 'F3-268 NO-GO: runner or Compose scope mismatch.' >&2
  exit 2
fi

for svc in app web; do
  cid="$(docker compose ps -q "$svc")"
  if [[ -z "$cid" ]]; then
    echo 'F3-268 NO-GO: missing service.' >&2
    exit 1
  fi
  if [[ "$(docker inspect --format '{{.HostConfig.ReadonlyRootfs}}' "$cid")" != true ]]; then
    echo 'F3-268 NO-GO: root filesystem is writable.' >&2
    exit 1
  fi
  if [[ "$(docker inspect --format '{{json .HostConfig.CapDrop}}' "$cid")" != '["ALL"]' ]]; then
    echo 'F3-268 NO-GO: capabilities were not dropped.' >&2
    exit 1
  fi
  if [[ "$(docker inspect --format '{{json .HostConfig.SecurityOpt}}' "$cid")" != '["no-new-privileges:true"]' ]]; then
    echo 'F3-268 NO-GO: privilege escalation gate missing.' >&2
    exit 1
  fi
  docker inspect --format '{{json .NetworkSettings.Networks}}' "$cid" |
    php -r '
      $networks = json_decode(stream_get_contents(STDIN), true);
      if (!is_array($networks)) { exit(1); }
      $actual = array_keys($networks);
      sort($actual, SORT_STRING);
      $prefix = "imagine-crm-stage-review_";
      $expected = $argv[1] === "app"
        ? [$prefix."crm_private"]
        : [$prefix."crm_loopback", $prefix."crm_private"];
      if ($actual !== $expected) { exit(1); }
    ' "$svc" || { echo 'F3-268 NO-GO: unexpected runtime network.' >&2; exit 1; }
done
echo 'F3-268 read-only, capabilities, privilege and network separation passed.'

if [[ "$(docker compose exec -T app id -u)" != 33 ||
      "$(docker compose exec -T web id -u)" != 101 ]]; then
  echo 'F3-268 NO-GO: service UID mismatch.' >&2
  exit 1
fi

docker compose exec -T app php -r '
$paths = [
    "/var/www/html/.env", "/var/www/html/.git",
    "/var/www/html/tests", "/var/www/html/scripts",
    "/var/www/html/database/experimental-migrations",
    "/var/www/html/vendor/bin/phpunit",
];
foreach ($paths as $path) {
    if (file_exists($path)) {
        fwrite(STDERR, "F3-268 NO-GO: unapproved app image source.\n");
        exit(1);
    }
}
$lines = file("/proc/net/route", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!is_array($lines)) { exit(1); }
foreach ($lines as $line) {
    $fields = preg_split("/\s+/", trim($line));
    if (($fields[1] ?? "") === "00000000") {
        fwrite(STDERR, "F3-268 NO-GO: app has a default route.\n");
        exit(1);
    }
}
echo "F3-268 app image excludes dev artifacts and has no default route.\n";
'

docker compose exec -T web sh -c '
  test ! -e /var/www/html/app &&
  test ! -e /var/www/html/vendor &&
  test ! -e /var/www/html/.env &&
  test ! -e /var/www/html/composer.lock &&
  test ! -e /var/www/html/tests &&
  test -f /var/www/html/public/index.php
' || { echo 'F3-268 NO-GO: web image includes unexpected backend or lacks front controller.' >&2; exit 1; }

if [[ "$(docker compose port web 8080)" != '127.0.0.1:18080' ]]; then
  echo 'F3-268 NO-GO: published port is not loopback-only.' >&2
  exit 1
fi
php_public_port="$(docker compose port app 9000 2>/dev/null || true)"
if [[ -n "$php_public_port" ]]; then
  echo 'F3-268 NO-GO: PHP-FPM port published on the host.' >&2
  exit 1
fi
docker network inspect imagine-crm-stage-review_crm_loopback \
  --format '{{json .Options}}' |
  php -r '
    $opts = json_decode(stream_get_contents(STDIN), true);
    if (!is_array($opts) ||
        ($opts["com.docker.network.bridge.enable_ip_masquerade"] ?? null) !== "false" ||
        ($opts["com.docker.network.bridge.host_binding_ipv4"] ?? null) !== "127.0.0.1") {
        exit(1);
    }
  ' || { echo 'F3-268 NO-GO: ingress NAT/host binding mismatch.' >&2; exit 1; }

echo 'F3-268 PASS: ephemeral non-root images, no app default route, no exposed FPM, and Nginx loopback only.'
echo 'F3-268 does not validate host firewall, cross-network routing, or deployment security.'
