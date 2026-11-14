"""CRM UX-01: four viewport screenshots of the offline prototype hub.

Runs on a disposable same-repository GitHub PR, never a public site.
No customer data, credentials, signup, authentication or external network.
"""
from __future__ import annotations

import hashlib
import json
import os
from functools import partial
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from threading import Thread
from urllib.parse import urlsplit

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent
PAGE = ROOT / "index.html"
assert os.environ.get("GITHUB_ACTIONS") == "true"
assert PAGE.is_file() and not PAGE.is_symlink() and PAGE.stat().st_size < 50000
assert all(not p.is_symlink() for p in ROOT.iterdir())
OUT = Path(os.environ["RUNNER_TEMP"]) / "imagine-crm-ux-01-hub-review"
OUT.mkdir(parents=True, exist_ok=False)
CAPTURES: list[dict[str, str]] = []
blocked: list[str] = []
errors: list[str] = []


class ReadOnly(SimpleHTTPRequestHandler):
    def log_message(self, *_: object) -> None:
        pass

    def do_POST(self) -> None:
        self.send_error(405, "This is a static visual preview")

    def do_PUT(self) -> None:
        self.send_error(405, "Write operations are not allowed")

    def do_DELETE(self) -> None:
        self.send_error(405, "Write operations are not allowed")


with ThreadingHTTPServer(("127.0.0.1", 0), partial(ReadOnly, directory=str(ROOT))) as server:
    origin = f"http://127.0.0.1:{server.server_port}"
    thread = Thread(target=server.serve_forever, daemon=True)
    thread.start()
    try:
        with sync_playwright() as pw:
            import shutil
            executable = shutil.which("google-chrome") or shutil.which("chromium")
            assert executable, "The disposable runner requires a local Chromium browser"
            browser = pw.chromium.launch(
                executable_path=executable,
                headless=True,
                args=["--no-sandbox", "--disable-background-networking"],
            )
            try:
                for width, name in [
                    (1440, "01-desktop.png"),
                    (768, "02-tablet.png"),
                    (390, "03-mobile.png"),
                    (320, "04-small-mobile.png"),
                ]:
                    context = browser.new_context(
                        viewport={"width": width, "height": 900},
                        reduced_motion="reduce",
                        java_script_enabled=False,
                    )
                    page = context.new_page()
                    page.on("pageerror", lambda err: errors.append(str(err)))

                    def localhost_only(route) -> None:
                        url = urlsplit(route.request.url)
                        if (url.scheme == "http" and url.hostname == "127.0.0.1"
                                and url.port == server.server_port):
                            route.continue_()
                        else:
                            blocked.append(url.scheme + "://" + (url.hostname or ""))
                            route.abort()

                    page.route("**/*", localhost_only)
                    response = page.goto(origin + "/", wait_until="domcontentloaded")
                    assert response is not None and response.status == 200
                    assert page.title() == "Imagine CRM — Laboratório navegável offline"
                    assert page.locator('html[lang="pt-BR"]').count() == 1
                    assert page.locator('article.card').count() == 12
                    logo = page.locator('img[src="assets/logo-imagine.png"][alt="Imagine"]')
                    assert logo.count() == 1 and logo.is_visible()
                    assert logo.evaluate("(node) => node.naturalWidth") == 200
                    assert logo.evaluate("(node) => node.naturalHeight") == 55
                    assert page.get_by_text("PROTÓTIPO — DADOS FICTÍCIOS.", exact=False).count() == 1
                    assert page.locator('form, script, iframe, input, textarea').count() == 0
                    assert page.locator('a[href^="https"], a[href^="http"], a[href^="//"]').count() == 0
                    assert page.evaluate("document.documentElement.scrollWidth <= window.innerWidth")
                    assert page.evaluate(
                        'getComputedStyle(document.documentElement).getPropertyValue("--imagine-orange").trim()'
                    ) == "#F9941D"
                    assert page.evaluate(
                        'getComputedStyle(document.documentElement).getPropertyValue("--imagine-graphite").trim()'
                    ) == "#171B19"

                    links = page.locator('article.card a')
                    assert links.count() == 12
                    for index in range(12):
                        anchor = links.nth(index)
                        assert anchor.is_visible()
                        href = anchor.get_attribute("href")
                        assert href and href.endswith("/index.html")
                        assert not href.startswith("/") and ".." not in href
                        assert (ROOT / href).is_file(), (width, href)
                        assert anchor.evaluate(
                            "(node) => getComputedStyle(node).backgroundColor"
                        ) == "rgb(249, 148, 29)"
                    path = OUT / name
                    page.screenshot(path=str(path), full_page=True, animations="disabled")
                    png = path.read_bytes()
                    assert png.startswith(bytes.fromhex("89504e470d0a1a0a")) and len(png) > 2048
                    CAPTURES.append({"name": name, "sha256": hashlib.sha256(png).hexdigest()})
                    assert len(context.cookies()) == 0
                    context.close()
            finally:
                browser.close()
    finally:
        server.shutdown()
        thread.join(timeout=5)

assert not blocked, "Outbound browser requests attempted"
assert not errors, "Browser page errors"
manifest = {
    "schema": "imagine-crm-ux-01-visual-review-v1",
    "head_sha": os.environ.get("GITHUB_SHA", ""),
    "files": CAPTURES,
    "uses_real_customer_data": False,
    "login_enabled": False,
    "production_release_authorized": False,
}
assert len(manifest["files"]) == 4
(OUT / "manifest.json").write_text(json.dumps(manifest, indent=2), encoding="utf-8")
print("CRM UX-01 PASS: 12 offline flows, original Imagine palette, four real browser viewports")
