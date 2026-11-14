#!/usr/bin/env bash
# F3-271. Only nonsecret inventory from ephemeral same-repository PR #517.
# No upload of raw logs, env, user files, container filesystem or credentials.
set -euo pipefail

if [[ "$GITHUB_EVENT_NAME" != pull_request ||
      "$RUNNER_ENVIRONMENT" != github-hosted ||
      "$COMPOSE_PROJECT_NAME" != imagine-crm-stage-review ||
      "$COMPOSE_FILE" != infra/staging/compose.yaml ||
      -z "$RUNNER_TEMP" ]]; then
  echo 'F3-271 NO-GO: isolated inventory scope required.' >&2
  exit 2
fi

out="$RUNNER_TEMP/crm-f3-271-inventory"
if [[ -e "$out" ]]; then
  echo 'F3-271 NO-GO: inventory destination already exists.' >&2
  exit 2
fi
mkdir -m 700 "$out"

# Export names and installed versions only. Composer descriptions, URLs,
# support paths, package metadata and any environment are deliberately omitted.
docker compose exec -T app composer show --no-dev --format=json |
  php -r '
    $json = json_decode(stream_get_contents(STDIN, 4194304), true);
    $items = $json["installed"] ?? null;
    if (!is_array($items) || count($items) < 1) {
        fwrite(STDERR, "F3-271 NO-GO: package inventory unavailable.\n");
        exit(1);
    }
    $result = [];
    foreach ($items as $item) {
        $name = $item["name"] ?? null;
        $version = $item["version"] ?? null;
        if (!is_string($name) || !is_string($version) ||
            !preg_match("/^[a-z0-9._\/-]{1,128}$/", $name) ||
            !preg_match("/^[A-Za-z0-9._+@\/:-]{1,128}$/", $version)) {
            fwrite(STDERR, "F3-271 NO-GO: unrecognized package inventory field.\n");
            exit(1);
        }
        if (isset($result[$name])) {
            fwrite(STDERR, "F3-271 NO-GO: duplicate package name.\n");
            exit(1);
        }
        $result[$name] = $version;
    }
    ksort($result, SORT_STRING);
    foreach ($result as $name => $version) {
        echo $name, "\t", $version, "\n";
    }
  ' > "$out/composer-packages.tsv"

docker compose exec -T app php -m |
  php -r '
    $lines = explode("\n", stream_get_contents(STDIN, 8192));
    $result = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "" || $line[0] === "[") {
            continue;
        }
        if (!preg_match("/^[A-Za-z0-9_. -]{1,96}$/", $line)) {
            fwrite(STDERR, "F3-271 NO-GO: unexpected extension inventory field.\n");
            exit(1);
        }
        $result[strtolower($line)] = true;
    }
    ksort($result, SORT_STRING);
    if (!isset($result["mbstring"]) || !isset($result["pdo_sqlite"])) {
        fwrite(STDERR, "F3-271 NO-GO: critical PHP extensions absent.\n");
        exit(1);
    }
    foreach (array_keys($result) as $line) {
        echo $line, "\n";
    }
  ' > "$out/php-modules.txt"

app_cid="$(docker compose ps -q app)"
web_cid="$(docker compose ps -q web)"
app_image="$(docker inspect --format '{{.Image}}' "$app_cid")"
web_image="$(docker inspect --format '{{.Image}}' "$web_cid")"
head="$(git rev-parse HEAD)"
lock_hash="$(sha256sum composer.lock | cut -d ' ' -f1)"
app_hash="$(sha256sum infra/staging/Dockerfile | cut -d ' ' -f1)"
web_hash="$(sha256sum infra/staging/Dockerfile.web | cut -d ' ' -f1)"
compose_hash="$(sha256sum infra/staging/compose.yaml | cut -d ' ' -f1)"

# Only fixed field names + validated SHA-256 values are allowed in JSON.
php -r '
  $values = array_slice($argv, 1);
  if (count($values) !== 7 ||
      !preg_match("/^[0-9a-f]{40}$/", $values[0])) {
    fwrite(STDERR, "F3-271 NO-GO: revision fingerprint invalid.\n");
    exit(1);
  }
  foreach (array_slice($values, 1) as $i => $v) {
    if (!preg_match($i < 2 ? "/^sha256:[0-9a-f]{64}$/" : "/^[0-9a-f]{64}$/", $v)) {
      fwrite(STDERR, "F3-271 NO-GO: image/source digest invalid.\n");
      exit(1);
    }
  }
  $inventory = [
    "schema" => "crm-f3-271-ci-build-inventory-v1",
    "scope" => "disposable-pr-runner-no-deploy",
    "head_sha" => $values[0],
    "app_image_id" => $values[1],
    "web_image_id" => $values[2],
    "composer_lock_sha256" => $values[3],
    "php_dockerfile_sha256" => $values[4],
    "nginx_dockerfile_sha256" => $values[5],
    "compose_sha256" => $values[6],
    "customer_data" => false,
    "production_approval" => false,
  ];
  echo json_encode($inventory,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
' "$head" "$app_image" "$web_image" "$lock_hash" "$app_hash" "$web_hash" "$compose_hash" \
  > "$out/manifest.json"

(
  cd "$out"
  sha256sum manifest.json composer-packages.tsv php-modules.txt > SHA256SUMS
  sha256sum --check SHA256SUMS > /dev/null
)
echo 'F3-271 PASS: sanitized build image/source fingerprints and PHP dependency inventory verified in ephemeral CI.'
echo 'F3-271 is not a registry digest, signed provenance, comprehensive SBOM or a release permission.'
