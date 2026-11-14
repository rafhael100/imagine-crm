"""CI-only browser review of the approved static CRM Imagine commercial prototype.

No CRM backend, sign-up, payments, VPS, public hosting or customer data.
Network is restricted to a disposable 127.0.0.1 HTTP server.
"""
from __future__ import annotations

import hashlib
import json
import os
from pathlib import Path
import shutil
import sys
from functools import partial
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from threading import Thread
from urllib.parse import urlsplit

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent
PAGE = ROOT / "index.html"
assert os.environ.get("GITHUB_ACTIONS") == "true", "Only disposable GitHub CI may run this review"
assert PAGE.is_file() and PAGE.stat().st_size < 100_000
assert not PAGE.is_symlink()
assert not any(p.is_symlink() for p in ROOT.iterdir())
OUT = Path(os.environ["RUNNER_TEMP"]).resolve() / "imagine-crm-web-preview-shots"
OUT.mkdir(parents=True, exist_ok=False)
CHROME = shutil.which("google-chrome") or shutil.which("chromium")
assert CHROME, "Require the GitHub runner's Chromium or Chrome"
shots: list[dict[str, str]] = []
blocked: list[str] = []
errors: list[str] = []


class StaticOnly(SimpleHTTPRequestHandler):
    def log_message(self, *_: object) -> None:
        pass

    def do_POST(self) -> None:
        self.send_error(405, "This is a static visual review; no registration or billing")

    def do_PUT(self) -> None:
        self.send_error(405, "No write endpoint")

    def do_DELETE(self) -> None:
        self.send_error(405, "No delete endpoint")


def capture(page, name: str) -> None:
    assert name.endswith(".png") and "/" not in name and "\\" not in name
    path = OUT / name
    assert not path.exists()
    page.screenshot(path=str(path), full_page=True, animations="disabled")
    raw = path.read_bytes()
    assert raw.startswith(bytes.fromhex("89504e470d0a1a0a"))
    assert len(raw) > 2048
    shots.append({"name": name, "sha256": hashlib.sha256(raw).hexdigest()})


with ThreadingHTTPServer(("127.0.0.1", 0), partial(StaticOnly, directory=str(ROOT))) as server:
    origin = f"http://127.0.0.1:{server.server_port}"
    thread = Thread(target=server.serve_forever, daemon=True)
    thread.start()
    try:
        with sync_playwright() as pw:
            browser = pw.chromium.launch(
                executable_path=CHROME,
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
                    )
                    page = context.new_page()
                    page.on("pageerror", lambda error: errors.append(str(error)))
                    sent: list[str] = []
                    page.on("request", lambda request: sent.append(request.url))

                    def only_private(route) -> None:
                        target = urlsplit(route.request.url)
                        if target.scheme == "http" and target.hostname == "127.0.0.1" and target.port == server.server_port:
                            route.continue_()
                        else:
                            blocked.append(target.scheme + "://" + (target.hostname or ""))
                            route.abort()

                    page.route("**/*", only_private)
                    response = page.goto(origin + "/", wait_until="domcontentloaded")
                    assert response is not None and response.status == 200
                    assert page.title() == "Imagine CRM — Prévia da página comercial"
                    assert page.locator('html[lang="pt-BR"]').count() == 1
                    assert page.locator('meta[name="robots"]').get_attribute("content") == "noindex,nofollow"
                    assert page.locator('form, input[type="email"], input[type="tel"], input[type="password"]').count() == 0
                    assert page.locator('a[href^="http"], a[href^="//"]').count() == 0
                    assert page.locator("#recursos, #como-funciona, #planos, #duvidas").count() == 4
                    assert page.locator("#planos .plan").count() == 4
                    assert page.get_by_text("R$ 69", exact=False).count() == 1
                    assert page.get_by_text("R$ 89", exact=False).count() == 1
                    assert page.get_by_text("14 dias para conhecer o Imagine CRM").count() == 1

                    # Validate the visible hamburger menu rather than only HTML.
                    menu = page.get_by_role("button", name="Abrir menu")
                    if width <= 800:
                        assert menu.is_visible()
                        assert menu.get_attribute("aria-expanded") == "false"
                        menu.click()
                        assert menu.get_attribute("aria-expanded") == "true"
                        assert page.locator("#mobilelinks a:visible").count() == 4
                        page.locator('#mobilelinks a[href="#planos"]').click()
                        assert menu.get_attribute("aria-expanded") == "false"
                    else:
                        assert not menu.is_visible()

                    # All signup/checkout/login CTAs must remain inert; only
                    # the explicit information-only dialog is allowed.
                    for action in [
                        "Acessar o CRM", "Teste gratuito de 14 dias",
                        "Plano Básico", "Plano Essencial", "Plano Profissional",
                        "Plano Empresarial",
                    ]:
                        if action == "Acessar o CRM" and width <= 800:
                            # Desktop login hides on mobile; the alternative
                            # is an actual visible action inside the menu.
                            menu.click()
                            assert menu.get_attribute("aria-expanded") == "true"
                        trigger = page.locator('[data-action="' + action + '"]:visible').first
                        assert trigger.count() == 1, ("No visible CTA", width, action)
                        trigger.click()
                        dialog = page.get_by_role("dialog")
                        assert dialog.is_visible(), ("Missing preview-only dialog", action)
                        assert page.locator("#dialogmsg").inner_text().find(action) != -1
                        assert "ainda não estão liberados" in page.locator("#dialogmsg").inner_text()
                        assert page.url.startswith(origin + "/")
                        assert len(context.cookies()) == 0
                        page.keyboard.press("Escape")
                        assert not dialog.is_visible()
                        if action == "Acessar o CRM" and width <= 800:
                            menu.click()
                            assert menu.get_attribute("aria-expanded") == "false"

                    faq = page.locator("#duvidas details").first
                    assert faq.count() == 1 and not faq.get_attribute("open")
                    faq.locator("summary").click()
                    assert faq.get_attribute("open") is not None
                    faq.locator("summary").click()
                    assert faq.get_attribute("open") is None

                    assert page.evaluate("document.documentElement.scrollWidth <= innerWidth"), (
                        "Horizontal overflow", width,
                        page.evaluate("document.documentElement.scrollWidth"),
                    )
                    assert errors == [], errors
                    assert blocked == [], ("No request may leave loopback", blocked)
                    assert all(urlsplit(u).hostname == "127.0.0.1" for u in sent), sent
                    assert len(sent) <= 2, ("No telemetry or remote resources", sent)
                    capture(page, name)
                    page.close()
                    context.close()
                    print(f"PASS CRM commercial static preview: {width}px, plans, menu, modal and privacy")
            finally:
                browser.close()
    finally:
        server.shutdown()
        thread.join(timeout=4)

expected = [
    "01-desktop.png", "02-tablet.png",
    "03-mobile.png", "04-small-mobile.png",
]
assert [item["name"] for item in shots] == expected
(OUT / "manifest.json").write_text(
    json.dumps(
        {
            "project": "Imagine CRM commercial static visual review",
            "source_sha_hint": os.environ.get("GITHUB_SHA", "unknown"),
            "files": shots,
            "public_release_authorized": False,
            "customer_data_used": False,
            "registration_enabled": False,
            "billing_enabled": False,
        },
        sort_keys=True,
        indent=2,
    ) + "\n",
    encoding="utf-8",
)
print("PASS CRM preview: four images + SHA256 manifest; DRAFT/NO-GO, no external requests")
