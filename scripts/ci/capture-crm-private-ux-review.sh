#!/usr/bin/env bash
# CRM-UX-03: screenshot real localhost-only fake Laravel pages in GitHub CI.
# No customer data, browser extension, external site, public deploy or payment.
set -euo pipefail
fail() { echo "CRM UX SCREENSHOTS NO-GO: $*" >&2; exit 1; }
[[ "$(printenv GITHUB_ACTIONS || true)" == true ]] || fail "GitHub-hosted Actions required"
[[ "$(printenv GITHUB_REPOSITORY || true)" == rafhael100/imagine-crm ]] ||
  fail "unexpected repository"
[[ "$(printenv CI || true)" == true ]] || fail "CI required"
[[ "$(printenv CRM_UX_SCREENSHOT_LAB || true)" == synthetic-only ]] ||
  fail "fictional screenshot gate missing"
[[ -d "$(printenv RUNNER_TEMP || true)" ]] || fail "ephemeral runner directory missing"

browser=""
for name in google-chrome google-chrome-stable chromium chromium-browser; do
  if command -v "$name" >/dev/null 2>&1; then
    browser="$(command -v "$name")"
    break
  fi
done
[[ -n "$browser" ]] || fail "real Chrome/Chromium required"

output="$RUNNER_TEMP/crm-ux-private-review"
[[ ! -e "$output" ]] || fail "screenshot output preexists"
mkdir -m 700 "$output"
active_profile=""
cleanup_profile() {
  if [[ -n "$active_profile" && -d "$active_profile" ]]; then
    rm -rf -- "$active_profile"
  fi
}
trap cleanup_profile EXIT

base="http://127.0.0.1:18080"
[[ "$(curl -sS -o /dev/null -w '%{http_code}' --max-time 5 "$base/up")" == 200 ]] ||
  fail "private Nginx unavailable"

# Never screenshot unapproved content: only exact routes of the synthetic
# marketing/Kanban demo. SQL-backed route must contain fake labels only.
fake="$(curl -fsS --max-time 7 "$base/demo/live?lang=pt")"
[[ "$fake" == *'EXEMPLO_ALFA'* && "$fake" != *'EXEMPLO_OMEGA'* ]] ||
  fail "real SQL-backed fixture not isolated"
[[ "$fake" == *'FOLLOW_UP_DEMO_A'* ]] || fail "fake tasks absent"

capture() {
  local name="$1" size="$2" path="$3"
  case "$name" in
    crm-home-pt-desktop|crm-home-pt-mobile|crm-home-en-desktop|crm-home-es-desktop|\
    crm-demo-pt-desktop|crm-demo-pt-mobile|crm-sql-demo-pt-desktop|crm-sql-demo-pt-mobile) ;;
    *) fail "unauthorized screenshot name" ;;
  esac
  active_profile="$(mktemp -d "$RUNNER_TEMP/crm-ux-chrome-XXXXXXXX")"
  local filename="$output/$name.png"
  local url="$base$path"
  # Keep browser profile disposable; disable unrelated background services.
  if ! timeout 50s "$browser" --headless --disable-gpu --disable-extensions \
      --disable-background-networking --disable-dev-shm-usage \
      --no-first-run --no-default-browser-check \
      --user-data-dir="$active_profile" \
      --window-size="$size" --force-device-scale-factor=1 \
      --screenshot="$filename" "$url" >/dev/null 2>&1; then
    fail "headless browser could not capture $name"
  fi
  [[ -s "$filename" ]] || fail "empty browser screenshot $name"
  [[ "$(file -b --mime-type "$filename")" == image/png ]] ||
    fail "browser screenshot $name is not PNG"
  cleanup_profile
  active_profile=""
}

capture crm-home-pt-desktop 1440,900 '/?lang=pt'
capture crm-home-pt-mobile 390,844 '/?lang=pt'
capture crm-home-en-desktop 1440,900 '/?lang=en'
capture crm-home-es-desktop 1440,900 '/?lang=es'
capture crm-demo-pt-desktop 1440,900 '/demo?lang=pt'
capture crm-demo-pt-mobile 390,844 '/demo?lang=pt'
capture crm-sql-demo-pt-desktop 1440,900 '/demo/live?lang=pt'
capture crm-sql-demo-pt-mobile 390,844 '/demo/live?lang=pt'

[[ "$(find "$output" -maxdepth 1 -type f -name '*.png' | wc -l | tr -d ' ')" == 8 ]] ||
  fail "expected eight complete fictional captures"
echo "CRM UX CI PASS: 8 original-logo Laravel screenshots, PT/EN/ES desktop"
echo "and 390px-window mobile review; only synthetic content, no deploy."
echo "Manual contrast, real mobile emulation and independent UX review still required."
