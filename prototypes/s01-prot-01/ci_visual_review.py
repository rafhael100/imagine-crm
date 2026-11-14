"""CRM UX-02: disposable-browser review of the nine-screen offline CRM.

Only localhost, synthetic fixtures, no application login and no deployment.
"""
from __future__ import annotations

import hashlib
import json
import os
import shutil
from functools import partial
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from threading import Thread
from urllib.parse import urlsplit

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent
PROTOTYPES_ROOT = ROOT.parent
PAGE = ROOT / "index.html"
assert os.environ.get("GITHUB_ACTIONS") == "true", "Disposable CI only"
assert PAGE.is_file() and not PAGE.is_symlink() and PAGE.stat().st_size < 150_000
OUT = Path(os.environ["RUNNER_TEMP"]) / "crm-ux-02-nine-screen-review"
OUT.mkdir(exist_ok=False, parents=True)
IMAGES: list[dict[str, str]] = []
blocked: list[str] = []
errors: list[str] = []
SCREENS = (
    "welcome", "organization", "dashboard", "leads", "pipeline",
    "monitor", "import", "states", "about",
)


class ReadOnly(SimpleHTTPRequestHandler):
    def log_message(self, *_: object) -> None:
        pass

    def do_POST(self) -> None:
        self.send_error(405, "No CRM write endpoint")

    def do_PUT(self) -> None:
        self.send_error(405, "No CRM write endpoint")

    def do_DELETE(self) -> None:
        self.send_error(405, "No CRM write endpoint")


with ThreadingHTTPServer(("127.0.0.1", 0), partial(ReadOnly, directory=str(PROTOTYPES_ROOT))) as server:
    origin = f"http://127.0.0.1:{server.server_port}"
    thread = Thread(target=server.serve_forever, daemon=True)
    thread.start()
    try:
        with sync_playwright() as playwright:
            chrome = shutil.which("google-chrome") or shutil.which("chromium")
            assert chrome, "GitHub runner missing browser"
            browser = playwright.chromium.launch(
                executable_path=chrome,
                headless=True,
                args=["--no-sandbox", "--disable-background-networking"],
            )
            try:
                for width, name in (
                    (1440, "01-desktop.png"),
                    (768, "02-tablet.png"),
                    (390, "03-mobile.png"),
                    (320, "04-small-mobile.png"),
                ):
                    context = browser.new_context(
                        viewport={"width": width, "height": 900},
                        reduced_motion="reduce",
                    )
                    page = context.new_page()
                    page.on("pageerror", lambda e: errors.append(str(e)))

                    def offline_only(route) -> None:
                        u = urlsplit(route.request.url)
                        if u.scheme == "http" and u.hostname == "127.0.0.1" and u.port == server.server_port:
                            route.continue_()
                        else:
                            blocked.append(u.scheme + "://" + (u.hostname or ""))
                            route.abort()

                    page.route("**/*", offline_only)
                    response = page.goto(origin + "/s01-prot-01/", wait_until="domcontentloaded")
                    assert response and response.status == 200
                    assert page.title() == "Imagine CRM — Protótipo offline"
                    assert page.locator('html[lang="pt-BR"]').count() == 1
                    assert page.locator('nav#demo-navigation .nav-button').count() == 9
                    assert page.locator('section[data-screen]').count() == 9
                    logo = page.locator('img[src="../assets/logo-imagine.png"][alt="Imagine"]')
                    assert logo.count() == 1 and logo.is_visible()
                    assert logo.evaluate('(node) => node.naturalWidth') == 200
                    assert logo.evaluate('(node) => node.naturalHeight') == 55
                    assert page.evaluate(
                        'getComputedStyle(document.documentElement).getPropertyValue("--imagine-orange").trim()'
                    ) == "#F9941D"
                    assert page.evaluate(
                        'getComputedStyle(document.documentElement).getPropertyValue("--imagine-graphite").trim()'
                    ) == "#171B19"
                    assert page.locator("form, input, textarea").count() == 0
                    assert page.locator('script[src], link[href^="http"]').count() == 0
                    for screen in SCREENS:
                        control = page.locator(f'button.nav-button[data-page="{screen}"]')
                        assert control.count() == 1, (width, screen)
                        control.click()
                        assert control.get_attribute("aria-current") == "page", (width, screen)
                        assert page.locator(f"section#screen-{screen}").is_visible(), (width, screen)
                    # Restore a useful screenshot of the operating dashboard.
                    page.locator('button.nav-button[data-page="dashboard"]').click()
                    assert page.locator("section#screen-dashboard").is_visible()
                    assert page.evaluate("document.documentElement.scrollWidth <= window.innerWidth")
                    assert len(context.cookies()) == 0
                    target = OUT / name
                    page.screenshot(path=str(target), full_page=True, animations="disabled")
                    raw = target.read_bytes()
                    assert raw.startswith(bytes.fromhex("89504e470d0a1a0a")) and len(raw) > 2048
                    IMAGES.append({"name": name, "sha256": hashlib.sha256(raw).hexdigest()})
                    context.close()
            finally:
                browser.close()
    finally:
        server.shutdown()
        thread.join(timeout=5)
assert not blocked, "External request blocked: preview must be 100% offline"
assert not errors, "Browser script errors"
assert len(IMAGES) == 4
manifest = {
    "schema": "crm-ux-02-static-visual-review-v1",
    "files": IMAGES,
    "live_identity_enabled": False,
    "uses_personal_data": False,
    "production_approval": False,
}
(OUT / "manifest.json").write_text(json.dumps(manifest, indent=2), encoding="utf-8")
print("CRM UX-02 PASS: nine navigable screens on four viewports, no real actions or external requests")
