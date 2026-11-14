#!/usr/bin/env python3
"""Contrato mínimo do MODELO Nginx da prévia, sem declarar ambiente real seguro.

São checagens estruturais de um arquivo NÃO instalado. A validação nginx -t,
teste 401/200 com TLS real e revisão independente da VPS continuam obrigatórios.
"""
from pathlib import Path
import re

HERE = Path(__file__).resolve().parent
TEMPLATE = HERE / "nginx-crm-preview.conf.example"


def main():
    c = TEMPLATE.read_text("utf-8")
    checks = {
        "loopback only": r"\blisten\s+127\.0\.0\.1:18081\s*;",
        "explicit credential outside repository": r"auth_basic_user_file\s+/etc/nginx/crm-preview\.htpasswd\s*;",
        "private basic auth": r'auth_basic\s+"Imagine CRM',
        "no-store on every response": r'add_header Cache-Control\s+"no-store, private, max-age=0"\s+always\s*;',
        "noindex on every response": r'add_header X-Robots-Tag\s+"noindex, nofollow, noarchive"\s+always\s*;',
        "nosniff": r'add_header X-Content-Type-Options\s+"nosniff"\s+always\s*;',
        "deny embedding": r'add_header X-Frame-Options\s+"DENY"\s+always\s*;',
        "deny external connections": r"connect-src 'none'",
        "deny forms": r"form-action 'none'",
        "deny embedded frames": r"frame-ancestors 'none'",
        "no file index": r"autoindex\s+off\s*;",
    }
    for label, regex in checks.items():
        assert re.search(regex, c), "Ausente: " + label
    assert not re.search(r"(?m)^\s*listen\s+(?:80|443|0\.0\.0\.0|\[::\])", c), "Porta pública não permitida"
    assert "map $uri $crm_preview_csp {" in c, "CSP diferenciada para mock fictício obrigatória"
    assert 'add_header Content-Security-Policy "$crm_preview_csp" always;' in c
    assert "default \"default-src 'none'; img-src 'self'; style-src 'self'; script-src 'self'" in c
    assert "/fluxo-demo/index.html \"default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; script-src 'unsafe-inline'" in c
    assert c.count("script-src 'unsafe-inline'") == 1, "Exceção inline deve se restringir ao mock"
    assert "proxy_pass" not in c, "A página estática não pode fazer proxy para backend"
    assert "fastcgi_pass" not in c, "Sem execução PHP na prévia"
    assert "access_log off" not in c, "Não desabilitar logs operacionais"
    whitelisted = re.findall(r"location\s+=\s+([^\s{]+)\s*\{", c)
    assert whitelisted == ["/", "/index.html", "/assets/logo-imagine.png", "/assets/site.css", "/assets/site.js", "/fluxo-demo/index.html", "/painel-demo/index.html", "/painel-demo/dashboard.css", "/painel-demo/dashboard.js"], whitelisted
    assert re.search(r"location\s+/\s*\{\s*return\s+404\s*;", c), "Demais rotas devem ser negadas"
    assert c.count("limit_except GET HEAD { deny all; }") == len(whitelisted)
    assert "/etc/nginx/crm-preview.htpasswd" not in str(HERE), "Fixture inesperada"
    assert not (HERE / "crm-preview.htpasswd").exists(), "Não armazenar credenciais no repositório"
    print("PASS: modelo Nginx privado com 9 rotas, CSP por caminho, BasicAuth e métodos restritos. SEM deploy.")


if __name__ == "__main__":
    main()
