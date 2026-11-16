#!/usr/bin/env python3
"""Checklist HTTP somente-leitura para staging REAL do Imagine CRM.

NÃO é executado pela CI e NUNCA faz deploy. O operador informa URL HTTPS
e hostname efetivamente aprovado. Auth opcional por arquivo local protegido.
Nenhuma credencial, corpo HTTP, cookie ou endereço completo é impresso.
"""
from __future__ import annotations

import argparse
import base64
import os
import re
import stat
import sys
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import urlsplit
from urllib.request import HTTPRedirectHandler, Request, build_opener

PATHS = ("/", "/index.html", "/assets/logo-imagine.png",
         "/assets/site.css", "/assets/site.js", "/fluxo-demo/index.html",
         "/painel-demo/index.html", "/painel-demo/dashboard.css", "/painel-demo/dashboard.js")


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def fetch(opener, url, authorization=None):
    headers = {"User-Agent": "Imagine-CRM-Preview-ReadOnly-Audit/1"}
    if authorization:
        headers["Authorization"] = "Basic " + authorization
    try:
        response = opener.open(Request(url, method="GET", headers=headers), timeout=10)
    except HTTPError as error:
        response = error
    with response:
        return response.status, response.headers, response.read(32768)


def verify_headers(headers):
    cache = headers.get("Cache-Control", "")
    robots = headers.get("X-Robots-Tag", "")
    assert "no-store" in cache, "cabeçalho no-store ausente"
    assert "noindex" in robots and "nofollow" in robots, "proibição de indexação ausente"
    assert headers.get("X-Content-Type-Options", "").lower() == "nosniff", "nosniff ausente"


def read_authorization(file_path):
    path = Path(file_path)
    assert path.is_file() and not path.is_symlink(), "arquivo de autenticação inválido"
    if os.name == "posix":
        mode = stat.S_IMODE(path.stat().st_mode)
        assert mode & (stat.S_IRWXG | stat.S_IRWXO) == 0, "arquivo de credenciais deve ter chmod 600"
    value = path.read_text(encoding="utf-8").strip()
    assert value.count(":") >= 1 and len(value) < 300, "credencial inválida"
    assert "\n" not in value and "\r" not in value, "credencial inválida"
    return base64.b64encode(value.encode("utf-8")).decode("ascii")


def main():
    p = argparse.ArgumentParser(description="Smoke HTTP de staging PRIVADO; não instala nem publica nada")
    p.add_argument("--url", required=True, help="origem HTTPS homologada, sem caminho")
    p.add_argument("--expected-host", required=True, help="hostname já aprovado pela operação")
    p.add_argument("--auth-file", help="arquivo local com login:senha, nunca versionado (chmod 600)")
    args = p.parse_args()
    u = urlsplit(args.url)
    assert u.scheme == "https" and u.hostname and u.hostname == args.expected_host, "host ou HTTPS inválido"
    assert not u.username and not u.password and not u.query and not u.fragment
    assert u.path in ("", "/") and u.port in (None, 443), "URL deve ser somente a origem HTTPS"
    # Sem seguir 30x: um redirecionamento de autenticação é falha, não sucesso.
    opener = build_opener(NoRedirect)
    origin = "https://" + u.hostname + (":" + str(u.port) if u.port else "")

    for path in PATHS:
        status, headers, body = fetch(opener, origin + path)
        assert status == 401, f"público {path}: esperado 401, recebido {status}"
        verify_headers(headers)
        assert re.search(r"\bBasic\b", headers.get("WWW-Authenticate", "")), "challenge Basic ausente"
        assert b"Seus clientes mais perto" not in body, "conteúdo comercial vazou sem autenticação"
        print(f"PASS anônimo {path}: 401 + headers privados")

    if args.auth_file:
        auth = read_authorization(args.auth_file)
        for path in PATHS:
            status, headers, body = fetch(opener, origin + path, auth)
            assert status == 200, f"autenticado {path}: esperado 200, recebido {status}"
            verify_headers(headers)
            if path in ("/", "/index.html", "/painel-demo/index.html"):
                assert b"Imagine CRM" in body, "HTML da prévia ausente"
            elif path.endswith(".png"):
                assert body.startswith(b"\x89PNG\r\n\x1a\n"), "PNG da marca inválido"
            elif path == "/painel-demo/dashboard.css":
                assert b".sidebar" in body, "CSS painel ausente"
            elif path == "/painel-demo/dashboard.js":
                assert b"data-view" in body, "JS painel ausente"
            elif path.endswith(".css"):
                assert b".brand-logo" in body, "CSS esperado ausente"
            elif path.endswith(".js"):
                assert b"showDialog" in body, "JS esperado ausente"
            print(f"PASS autenticado {path}: 200 + conteúdo permitido + headers privados")

    print("Contrato HTTP validado. Isto NÃO substitui revisão da VPS, DNS, TLS ou deploy.")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (AssertionError, OSError, URLError) as exc:
        print("FAIL: verificação HTTP não homologada (" + type(exc).__name__ + ")", file=sys.stderr)
        raise SystemExit(1)
