#!/usr/bin/env python3
"""F253: private CRM candidate canary; loopback-only, no credentials.

Used only AFTER an operator has switched the app container to a reviewed
synthetic candidate, BEFORE F252 records the new release. This does not
publish, modify state, start services, authenticate users, or inspect
private CRM records. All requests are loopback with a fixed Host.
"""
from __future__ import annotations

import http.client
import json
from urllib.parse import urlencode

HOST = "crm-dev.imaginecomunica.com.br"
PORT = 18086
MAX_BYTES = 450_000
METHOD_PATHS = (
    "/__synthetic_f248_not_registered",
    "/api/aia/lead/read",
    "/api/agents/op01/leads",
)


def require(value: bool, reason: str) -> None:
    if not value:
        raise RuntimeError("F253 private canary denied: " + reason)


def check_page(status: int, headers: dict[str, str], body: str,
               *, section: str | None = None) -> None:
    require(status == 200, "HTTP page unavailable")
    require("no-store" in headers.get("cache-control", "").lower(), "Cache-Control missing")
    require("noindex" in headers.get("x-robots-tag", "").lower(), "robots protection missing")
    require("Imagine CRM" in body, "CRM identity missing")
    if section is not None:
        require('data-home-section="' + section + '"' in body,
                "expected commercial section absent")


def check_denial(status: int, body: str) -> None:
    require(status in (400, 404), "rejected route is accessible")
    for secret in ("SYNTHETIC_F253_KEY", "SYNTHETIC_F253_RESOURCE",
                   "SYNTHETIC_F253_HEADER", "access_token"):
        require(secret not in body, "service request material leaked")


def check_scenario(status: int, headers: dict[str, str], body: str,
                   language: str) -> None:
    check_page(status, headers, body, section="imagine-scenario")
    require(language in ("pt", "en", "es"), "unsupported locale")
    for key, value in (
        ("visits", 2500), ("leads", 200), ("clients", 26)
    ):
        require('data-scenario-' + key + '="' + str(value) + '"' in body,
                "wrong anonymous funnel arithmetic")
    require('<form method="get" action="/" data-scenario-form="imagine"' in body,
            "read-only scenario form missing")
    require('name="visits"' in body and 'name="lead_rate"' in body
            and 'name="client_rate"' in body,
            "calculator inputs absent")
    require('name="email"' not in body and 'name="cnpj"' not in body,
            "personal data requested on anonymous calculator")
    expected = "Visits → Leads → Customers" if language == "en" \
        else "Visitas → Leads → Clientes"
    require(expected in body, "three-pillar language text missing")


def query(path: str, *, method: str = "GET",
          headers: dict[str, str] | None = None,
          body: str | None = None) -> tuple[int, dict[str, str], str]:
    require(path.startswith("/") and not path.startswith("//")
            and "\r" not in path and "\n" not in path,
            "nonlocal or unsafe HTTP path")
    require(method in ("GET", "POST"), "unsupported method")
    connection = http.client.HTTPConnection("127.0.0.1", PORT, timeout=10)
    safe_headers = {"Host": HOST, "Accept": "text/html,application/json",
                    "Connection": "close"}
    if headers:
        safe_headers.update(headers)
    try:
        connection.request(method, path, body=body, headers=safe_headers)
        response = connection.getresponse()
        read = response.read(MAX_BYTES + 1)
        require(len(read) <= MAX_BYTES, "HTTP response exceeds canary limit")
        return (response.status,
                {k.lower(): v for k, v in response.getheaders()},
                read.decode("utf-8", errors="replace"))
    finally:
        connection.close()


def main() -> None:
    status, headers, body = query("/")
    check_page(status, headers, body, section="imagine-methodology")
    for lang in ("pt", "en", "es"):
        qs = urlencode({"lang": lang, "visits": 2500,
                        "lead_rate": 8, "client_rate": 13})
        check_scenario(*query("/?" + qs), language=lang)
    check_page(*query("/login"))
    for path in METHOD_PATHS:
        status, _, body = query(path)
        check_denial(status, body)
        signed = json.dumps({
            "contract": "SYNTHETIC_CRM_AIA_READ_V1",
            "request_ref": "SYNTHETIC_F253_PROBE",
            "operation": "crm.lead.read_minimal",
            "tenant_ref": "SYNTHETIC_TENANT_A",
            "principal_ref": "SYNTHETIC_F253_PRINCIPAL",
            "resource_ref": "SYNTHETIC_F253_RESOURCE",
            "purpose_ref": "SYNTHETIC_F253_PURPOSE",
            "fields": ["ref", "stage"],
        }, separators=(",", ":"))
        status, _, body = query(path, method="POST", body=signed,
            headers={"Content-Type": "application/json",
                     "X-Aia-Claims": "SYNTHETIC_F253_HEADER",
                     "X-Aia-Signature": "SYNTHETIC_F253_HEADER"})
        check_denial(status, body)
    for route in ("/login", "/cadastro", "/recuperar"):
        status, _, body = query(route, headers={
            "X-Aia-Claims": "SYNTHETIC_F253_HEADER"})
        require(status == 400, "human/service identity separation regressed")
        check_denial(status, body)
    print("F253 PASS: localhost private CRM/3 languages/3 pillars/Agents NO-GO;"
          " no authenticated business reads or publication.")


if __name__ == "__main__":
    main()
