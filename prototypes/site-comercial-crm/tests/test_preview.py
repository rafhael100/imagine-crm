#!/usr/bin/env python3
"""Verificações locais da prévia estática do site comercial Imagine CRM.

Uso: python3 prototypes/site-comercial-crm/tests/test_preview.py
Não usa rede, dados pessoais, backend ou serviços externos.
"""
from __future__ import annotations

import hashlib
import re
from html.parser import HTMLParser
from pathlib import Path

BASE = Path(__file__).resolve().parents[1]
HTML = BASE / "index.html"
LOGO = BASE / "assets" / "logo-imagine.png"
CSS = BASE / "assets" / "site.css"
JS = BASE / "assets" / "site.js"
EXPECTED_LOGO_BLOB = "678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77"
EXPECTED_SECTIONS = {"inicio", "recursos", "como-funciona", "planos", "duvidas", "conteudo"}


class AuditHTML(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.ids = set()
        self.internal_links = []
        self.meta = {}
        self.images = []
        self.buttons = []
        self.headings = []
        self.blocked = []
        self.main_count = 0
        self.dialog_count = 0

    def handle_starttag(self, tag, pairs):
        a = dict(pairs)
        if "id" in a:
            assert a["id"] not in self.ids, f"ID duplicado: {a['id']}"
            self.ids.add(a["id"])
        if tag == "main":
            self.main_count += 1
        if tag == "h1":
            self.headings.append("h1")
        if tag == "meta":
            self.meta[a.get("name") or a.get("http-equiv")] = a.get("content", "")
        if tag == "img":
            self.images.append(a)
        if tag == "a":
            href = a.get("href", "")
            if href.startswith("#"):
                self.internal_links.append(href[1:])
            else:
                self.blocked.append(f"link externo ou não previsto: {href}")
        if tag == "button":
            self.buttons.append(a)
            if a.get("type") != "button":
                self.blocked.append("botão sem type=button")
        if tag == "div" and a.get("role") == "dialog":
            self.dialog_count += 1
        if tag in ("form", "input", "iframe", "object", "embed", "video", "audio"):
            self.blocked.append(f"elemento incompatível com prévia isolada: {tag}")
        for key in a:
            if key.lower().startswith("on"):
                self.blocked.append(f"handler inline proibido: {key}")


def main():
    html = HTML.read_text(encoding="utf-8")
    logo = LOGO.read_bytes()
    stylesheet = CSS.read_text("utf-8")
    script = JS.read_text("utf-8")
    assert html.lower().startswith("<!doctype html>")
    assert html.strip().endswith("</body></html>")
    assert logo[:8] == bytes((137, 80, 78, 71, 13, 10, 26, 10))
    assert (int.from_bytes(logo[16:20], "big"), int.from_bytes(logo[20:24], "big")) == (200, 55)
    actual_blob_sha = hashlib.sha1(b"blob " + str(len(logo)).encode() + bytes((0,)) + logo).hexdigest()
    assert actual_blob_sha == EXPECTED_LOGO_BLOB, "Logomarca diferente do Portal Imagine original"

    parser = AuditHTML()
    parser.feed(html)
    assert not parser.blocked, "; ".join(parser.blocked)
    assert parser.main_count == 1
    assert parser.dialog_count == 1
    assert len(parser.headings) == 1
    assert EXPECTED_SECTIONS.issubset(parser.ids)
    assert all(anchor in parser.ids for anchor in parser.internal_links), "Âncora quebrada"
    assert parser.meta.get("robots") == "noindex,nofollow"
    csp = parser.meta.get("Content-Security-Policy", "")
    for directive in ("default-src 'none'", "connect-src 'none'", "form-action 'none'", "object-src 'none'"):
        assert directive in csp, f"Política CSP ausente: {directive}"
    assert len(parser.images) == 1
    assert parser.images[0].get("src") == "./assets/logo-imagine.png"
    assert parser.images[0].get("alt") == "Imagine Comunicação Digital"
    assert parser.images[0].get("width") == "200"
    assert parser.images[0].get("height") == "55"

    for plan, price in (("Básico", "R$ 69"), ("Essencial", "R$ 89"),
                        ("Profissional", "R$ 149"), ("Empresarial", "R$ 299")):
        assert f"<h3>{plan}</h3>" in html
        assert price in html
        assert f'data-action="Plano {plan}"' in html

    assert len(parser.buttons) >= 6
    assert '<table class="compare-table">' in html
    assert '<caption>Resumo preliminar dos planos mensais</caption>' in html
    assert '<th scope="row">Usuários de referência</th>' in html
    assert '<th scope="row">Anexos e arquivos</th>' in html
    assert '.compare-scroll{' in stylesheet and 'overflow-x:auto' in stylesheet
    assert 'aria-expanded="false"' in html
    assert ".navact>.btn.menu{display:inline-flex" in stylesheet, "Menu mobile deve permanecer visível"
    assert "14 dias" in html and "sem cartão" in html
    assert "Preços" not in html or "homologação" in html.lower()
    assert "cadastro, acesso ao CRM, contratos e pagamentos reais ainda não estão liberados" in script
    assert 'content="noindex,nofollow"' in html
    for prohibited in ("fetch(", "XMLHttpRequest", "navigator.sendBeacon",
                       "localStorage", "sessionStorage", "document.cookie",
                       "window.location=", "location.href="):
        assert prohibited not in (html + script), f"Prévia contém recurso proibido: {prohibited}"
    assert re.search(r"<meta[^>]+Content-Security-Policy", html)
    assert '<link rel="stylesheet" href="./assets/site.css">' in html
    assert '<script src="./assets/site.js" defer></script>' in html
    assert '<style>' not in html and '<script>' not in html
    assert ' style="' not in html and ' onClick=' not in html
    assert "style-src 'self'; script-src 'self'" in html
    assert "'unsafe-inline'" not in html
    assert len(stylesheet) > 4000 and len(script) > 1000
    assert 'document.querySelectorAll(\'[data-action]\')' in script
    print("OK — estrutura, marca original, planos, navegação, isolamento e bloqueio de contratação")


if __name__ == "__main__":
    main()
