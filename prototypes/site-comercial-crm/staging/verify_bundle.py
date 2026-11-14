#!/usr/bin/env python3
"""Verifica pacote estático de prévia ANTES de qualquer publicação na VPS.

Aceita apenas caminhos conhecidos, sem symlinks, e exige hash SHA256 explícito
para cada recurso. Não faz rede, instalação, merge nem deploy.
"""
from __future__ import annotations

import argparse
import hashlib
from pathlib import Path

FILES = ("index.html", "assets/logo-imagine.png", "assets/site.css", "assets/site.js",
         "fluxo-demo/index.html", "painel-demo/index.html",
         "painel-demo/dashboard.css", "painel-demo/dashboard.js")
OPTIONAL = ("manifest.sha256", "LEIA-ME.txt")
MAX_SIZE = {"index.html": 100_000, "assets/logo-imagine.png": 300_000,
            "assets/site.css": 100_000, "assets/site.js": 100_000,
            "fluxo-demo/index.html": 100_000, "painel-demo/index.html": 100_000,
            "painel-demo/dashboard.css": 100_000, "painel-demo/dashboard.js": 100_000}


def verify(directory: Path) -> None:
    assert directory.is_dir() and not directory.is_symlink(), "diretório inválido"
    allowed = set(FILES + OPTIONAL)
    actual = set()
    for item in directory.rglob("*"):
        assert not item.is_symlink(), "pacote não pode conter symlinks"
        if item.is_file():
            name = item.relative_to(directory).as_posix()
            actual.add(name)
            assert name in allowed, "recurso não permitido no pacote"
        elif item.is_dir():
            assert item.relative_to(directory).as_posix() in ("assets", "fluxo-demo", "painel-demo"), "diretório inesperado"
        else:
            raise AssertionError("tipo de arquivo não permitido")
    assert set(FILES + ("manifest.sha256",)).issubset(actual), "recurso obrigatório ausente"
    manifest = (directory / "manifest.sha256").read_text("utf-8").splitlines()
    assert len(manifest) == len(FILES), "manifesto incompleto"
    seen = set()
    for line in manifest:
        hash_value, sep, name = line.partition("  ")
        assert sep and len(hash_value) == 64 and all(c in "0123456789abcdef" for c in hash_value)
        assert name in FILES and name not in seen, "nome inesperado/repetido no manifesto"
        seen.add(name)
        f = directory / name
        assert f.stat().st_size <= MAX_SIZE[name], "arquivo excede limite"
        assert hashlib.sha256(f.read_bytes()).hexdigest() == hash_value, "hash divergente"
    assert seen == set(FILES), "recurso sem hash"
    page = (directory / "index.html").read_text("utf-8")
    assert 'src="./assets/logo-imagine.png"' in page
    assert 'href="./assets/site.css"' in page
    assert 'src="./assets/site.js"' in page
    assert "connect-src 'none'" in page and "form-action 'none'" in page
    assert "<form" not in page and "<input" not in page
    assert 'href="./fluxo-demo/index.html"' in page
    mock = (directory / "fluxo-demo/index.html").read_text("utf-8")
    assert 'src="../assets/logo-imagine.png"' in mock
    assert "PROTÓTIPO — DADOS FICTÍCIOS." in mock
    assert "form-action 'none'" in mock and "connect-src 'none'" in mock
    panel = (directory / "painel-demo/index.html").read_text("utf-8")
    css = (directory / "painel-demo/dashboard.css").read_text("utf-8")
    script = (directory / "painel-demo/dashboard.js").read_text("utf-8")
    assert 'href="./painel-demo/index.html"' in page
    assert 'src="../assets/logo-imagine.png"' in panel
    assert 'src="./dashboard.js"' in panel and 'href="./dashboard.css"' in panel
    assert 'href="../painel-demo/index.html"' in mock
    assert 'data-enter="cliente"' in panel and 'data-enter="administrador"' in panel
    assert "form-action 'none'" in panel and "connect-src 'none'" in panel
    assert "<form" not in panel and "<input" not in panel
    assert len(css) > 6000 and len(script) > 9000
    assert all(word not in script for word in ("fetch(", "XMLHttpRequest", "localStorage", "sessionStorage", "document.cookie"))
    print("PASS: oito ativos permitidos e SHA256 conferidos. Nenhum deploy realizado.")


def main():
    parser = argparse.ArgumentParser(description="Auditar pacote CRM WEB-P0 (offline)")
    parser.add_argument("directory", type=Path)
    args = parser.parse_args()
    verify(args.directory)


if __name__ == "__main__":
    main()
