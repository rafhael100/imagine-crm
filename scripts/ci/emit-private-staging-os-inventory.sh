#!/usr/bin/env bash
# F3-274: OS package inventory of disposable staging images only.
# No SBOM completeness or vulnerability-scan claim; no new network calls.
set -euo pipefail

if [[ "$GITHUB_EVENT_NAME" != pull_request ||
      "$RUNNER_ENVIRONMENT" != github-hosted ||
      "$COMPOSE_PROJECT_NAME" != imagine-crm-stage-review ||
      "$COMPOSE_FILE" != infra/staging/compose.yaml ||
      -z "${RUNNER_TEMP:-}" ]]; then
  echo 'F3-274 NO-GO: GitHub-hosted review runner required.' >&2
  exit 2
fi

out="$RUNNER_TEMP/crm-f3-271-inventory"
if [[ ! -f "$out/manifest.json" || ! -f "$out/SHA256SUMS" ||
      -e "$out/debian-system-packages.tsv" ||
      -e "$out/alpine-system-packages.txt" ]]; then
  echo 'F3-274 NO-GO: missing prior reviewed inventory or unexpected files.' >&2
  exit 2
fi

# Host PHP transforms only public OS package identifiers and versions to
# deterministic text, rejecting all unexpected input rather than logging it.
docker compose exec -T app dpkg-query -W '-f=${binary:Package}\t${Version}\n' |
  php -r '
    $rows = explode("\n", stream_get_contents(STDIN, 524288));
    $packages = [];
    foreach ($rows as $row) {
      if ($row === "") { continue; }
      $fields = explode("\t", $row);
      if (count($fields) !== 2 ||
          !preg_match("/^[a-z0-9][a-z0-9+._:-]{0,127}$/", $fields[0]) ||
          !preg_match("/^[A-Za-z0-9][A-Za-z0-9.+:~_-]{0,127}$/", $fields[1]) ||
          isset($packages[$fields[0]])) {
        fwrite(STDERR, "F3-274 NO-GO: invalid Debian package inventory.\n");
        exit(1);
      }
      $packages[$fields[0]] = $fields[1];
    }
    if (count($packages) < 5) {
      fwrite(STDERR, "F3-274 NO-GO: incomplete Debian package list.\n");
      exit(1);
    }
    ksort($packages, SORT_STRING);
    foreach ($packages as $name => $version) {
      echo $name, "\t", $version, "\n";
    }
  ' > "$out/debian-system-packages.tsv"

docker compose exec -T web apk info -v |
  php -r '
    $lines = explode("\n", stream_get_contents(STDIN, 524288));
    $packages = [];
    foreach ($lines as $line) {
      if ($line === "") { continue; }
      // An installed APK package entry ends in a release like "-r0".
      if (!preg_match("/^[A-Za-z0-9][A-Za-z0-9.+_@:-]{0,190}-r[0-9]{1,8}$/", $line) ||
          isset($packages[$line])) {
        fwrite(STDERR, "F3-274 NO-GO: invalid Alpine package inventory.\n");
        exit(1);
      }
      $packages[$line] = true;
    }
    if (count($packages) < 5) {
      fwrite(STDERR, "F3-274 NO-GO: incomplete Alpine package list.\n");
      exit(1);
    }
    ksort($packages, SORT_STRING);
    foreach (array_keys($packages) as $name) {
      echo $name, "\n";
    }
  ' > "$out/alpine-system-packages.txt"

# Extend the already-reviewed inventory with local OS package lists.
# Never upload raw package manager status files or complete image archives.
(
  cd "$out"
  sha256sum --check SHA256SUMS > /dev/null
  sha256sum debian-system-packages.tsv alpine-system-packages.txt >> SHA256SUMS
  sha256sum --check SHA256SUMS > /dev/null
)
echo 'F3-274 PASS: nonsecret Debian and Alpine OS package snapshots added to verified ephemeral artifact.'
echo 'F3-274 is an OS inventory, not an OS vulnerability scan, attested SBOM, or release approval.'
