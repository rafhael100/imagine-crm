#!/usr/bin/env bash
# CRM-UX-SCREEN-01. Real Chromium + Laravel private HTTP + fictional data only.
set -euo pipefail
fail() { echo "CRM UX CAPTURE NO-GO: $*" >&2; exit 1; }
[[ "$(printenv CI || true)" == true ]] || fail "GitHub CI only"
[[ "$(printenv GITHUB_ACTIONS || true)" == true ]] || fail "GitHub Actions required"
[[ "$(printenv GITHUB_REPOSITORY || true)" == rafhael100/imagine-crm ]] ||
  fail "unexpected repository"
[[ "$(printenv CRM_UX_SCREEN_LAB || true)" == synthetic-only ]] ||
  fail "missing fictional-only marker"
[[ -d "$(printenv RUNNER_TEMP || true)" ]] || fail "no runner temp"
[[ "$(curl -sS --max-time 5 -o /dev/null -w '%{http_code}' http://127.0.0.1:18080/up)" == 200 ]] ||
  fail "private Laravel health unavailable"

browser=''
for bin in google-chrome google-chrome-stable chromium chromium-browser; do
  if command -v "$bin" >/dev/null 2>&1; then
    browser="$(command -v "$bin")"; break
  fi
done
[[ -n "$browser" ]] || fail "Chrome/Chromium not installed; no mock PASS"

output="$RUNNER_TEMP/crm-visual-ux-ci"
[[ ! -e "$output" ]] || fail "already present"
mkdir -m 0700 "$output"
manifest="$output/manifest.tsv"
printf 'page\tviewport\twidth\theight\tsha256\n' > "$manifest"

for page in marketing kanban-fixed kanban-postgresql-ci; do
  case "$page" in
    marketing) url="http://127.0.0.1:18080/?lang=pt" ;;
    kanban-fixed) url="http://127.0.0.1:18080/demo?lang=pt" ;;
    kanban-postgresql-ci) url="http://127.0.0.1:18080/demo/live?lang=pt" ;;
  esac
  html="$(curl -fsS --max-time 10 "$url")" || fail "GET failed $page"
  [[ "$html" == *'src="/assets/logo-imagine.png"'* ]] || fail "logo absent"
  [[ "$html" != *'EXEMPLO_OMEGA'* ]] || fail "foreign tenant leaked"
  [[ "$html" != *'<form'* ]] || fail "unexpected input form"
  if [[ "$page" == kanban-postgresql-ci ]]; then
    [[ "$html" == *'Dados fictícios carregados do PostgreSQL16 de teste'* ]] ||
      fail "persisted demo was not seeded"
  fi

  for size in 360x900 768x1024 1440x900; do
    width="$(printf '%s' "$size" | cut -dx -f1)"
    height="$(printf '%s' "$size" | cut -dx -f2)"
    file="$output/$page-$size.png"
    profile="$(mktemp -d "$RUNNER_TEMP/crm-ux-chrome-XXXXXXXX")"
    log="$(mktemp "$RUNNER_TEMP/crm-ux-chrome-log-XXXXXXXX")"
    if ! "$browser" --headless --no-sandbox --disable-gpu \
      --disable-extensions --disable-background-networking \
      --disable-dev-shm-usage --no-first-run --no-default-browser-check \
      --force-device-scale-factor=1 --hide-scrollbars --virtual-time-budget=2500 \
      --user-data-dir="$profile" \
      "--window-size=$width,$height" "--screenshot=$file" \
      "$url" >"$log" 2>&1; then
      rm -rf -- "$profile"; rm -f -- "$log"
      fail "real browser capture failed for $page at $size"
    fi
    rm -rf -- "$profile"; rm -f -- "$log"
    [[ -s "$file" ]] || fail "browser produced no PNG"
    python3 - "$file" "$width" "$height" <<'PY'
import os, struct, sys
p, w, h = sys.argv[1], int(sys.argv[2]), int(sys.argv[3])
with open(p, 'rb') as f:
    header = f.read(24)
if (len(header) != 24 or header[:8] != b'\x89PNG\r\n\x1a\n'
        or header[12:16] != b'IHDR'):
    raise SystemExit("CRM UX CAPTURE NO-GO: invalid PNG")
actual = struct.unpack('>II', header[16:24])
if actual != (w, h):
    raise SystemExit(f"CRM UX CAPTURE NO-GO: expected {w}x{h}, got {actual}")
if not 2000 <= os.path.getsize(p) <= 4_000_000:
    raise SystemExit("CRM UX CAPTURE NO-GO: abnormal PNG byte count")
PY
    checksum="$(sha256sum "$file" | cut -d' ' -f1)"
    printf '%s\t%s\t%s\t%s\t%s\n' "$page" "$size" \
      "$width" "$height" "$checksum" >> "$manifest"
  done
done

# A local, script-free contact sheet makes manual review practical after ZIP download.
# Its filenames are constants, and it is safe to open offline.
python3 - "$output" <<'PY'
from html import escape
from pathlib import Path
import sys
folder = Path(sys.argv[1])
pages = [
    ('marketing', 'Página comercial'),
    ('kanban-fixed', 'Kanban visual fictício'),
    ('kanban-postgresql-ci', 'Kanban fictício persistido no PostgreSQL16'),
]
sizes = ['360x900', '768x1024', '1440x900']
items = []
for slug, title in pages:
    items.append('<section><h2>' + escape(title) + '</h2><div class="grid">')
    for size in sizes:
        filename = f'{slug}-{size}.png'
        if not (folder / filename).is_file():
            raise SystemExit(f'CRM UX CAPTURE NO-GO: missing {filename}')
        items.append(
            '<figure><a href="' + filename + '"><img loading="lazy" '
            'src="' + filename + '" alt="' + escape(title + ', viewport ' + size)
            + '"></a><figcaption>' + escape(size) + '</figcaption></figure>'
        )
    items.append('</div></section>')
html = '''<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="Content-Security-Policy"
 content="default-src 'none'; img-src 'self' file:; style-src 'unsafe-inline';
 base-uri 'none'; form-action 'none'">
<title>Imagine CRM | Revisão visual privada CI</title>
<style>*{box-sizing:border-box}body{margin:0;font:16px Arial,Helvetica,sans-serif;
background:#f7f7f8;color:#171b19}main{max-width:1380px;margin:auto;padding:32px 20px}
h1{margin:0 0 12px;font-size:30px}h2{font-size:21px;margin:38px 0 16px}
p{line-height:1.6;max-width:850px}.warn{background:#fff1e0;
border-left:4px solid #f9941d;padding:12px 16px}.grid{display:grid;
grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));gap:18px}
figure{margin:0;border:1px solid #e8e8e8;background:white;
border-radius:12px;padding:10px}img{display:block;width:100%;height:auto;
border-radius:6px;border:1px solid #e8e8e8}figcaption{padding:10px 2px;
font-size:14px;font-weight:bold}a:focus-visible{outline:3px solid #b75c00}
</style></head><body><main>
<h1>Imagine CRM — comparação visual do Laravel</h1>
<p class="warn">Todas as oportunidades e tarefas são fictícias. Capturas Chrome em
localhost de CI descartável; não são produção, certificação WCAG ou aprovação
de uso comercial. Para ampliar, clique na imagem.</p>
''' + '\n'.join(items) + '''
<p>Consulte manifest.tsv para os hashes SHA-256 e dimensões de cada PNG.
As imagens devem ser inspecionadas por responsável UX.</p></main></body></html>'''
(folder / 'index.html').write_text(html, encoding='utf-8')
if html.count('<img ') != 9 or '<script' in html or '<form' in html:
    raise SystemExit('CRM UX CAPTURE NO-GO: invalid visual contact sheet')
PY

[[ "$(wc -l < "$manifest" | tr -d ' ')" == 10 ]] ||
  fail "missing screenshots"
[[ "$(find "$output" -maxdepth 1 -name '*.png' | wc -l | tr -d ' ')" == 9 ]] ||
  fail "wrong screenshot count"
echo "CRM UX SCREEN CI PASS: 9 Chromium screenshots, 3 private Laravel pages,"
echo "viewports 360/768/1440; synthetic data and SHA-256 manifest only."
echo "Images are review aids, NOT automated WCAG or visual design approval."