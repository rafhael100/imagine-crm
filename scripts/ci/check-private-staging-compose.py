#!/usr/bin/env python3
"""Read-only fail-closed check for isolated CRM staging Compose candidate.

Never starts a container, connects to Asaas, migrates a database or deploys.
GitHub-hosted CI uses dummy inputs solely for docker compose config rendering.
"""
from __future__ import annotations

import json
import os
from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
COMPOSE = ROOT / "infra/staging/compose.yaml"
DOCKERFILE = ROOT / "infra/staging/Dockerfile"
NGINX = ROOT / "infra/staging/nginx.conf"
IGNORE = ROOT / ".dockerignore"
DB_INIT = ROOT / "infra/staging/01-crm-staging-app-role.sh"


def deny(reason: str) -> None:
    raise SystemExit(f"PRIVATE-STAGING NO-GO: {reason}")


def main() -> None:
    files = (COMPOSE, DOCKERFILE, NGINX, IGNORE, DB_INIT)
    if not all(path.is_file() for path in files):
        deny("required infrastructure file missing")
    # Combined build must contain both the guarded Sites-to-lead ingestion
    # path and audited task core while exposing none of those CI-only classes.
    core_components = (
        ROOT / "app/Core/Leads/LaboratorySignedSiteCapture.php",
        ROOT / "app/Core/Leads/LaboratoryTenantLeadLedger.php",
        ROOT / "app/Core/Tasks/LaboratoryTenantTaskLedger.php",
        ROOT / "app/Core/Billing/AsaasSandboxCustomerGateway.php",
        ROOT / "app/Core/Billing/AsaasSandboxPixPaymentGateway.php",
        ROOT / "app/Core/Billing/AsaasSandboxSubscriptionLookup.php",
        ROOT / "app/Core/Billing/AsaasSandboxSubscriptionPaymentsReview.php",
        ROOT / "app/Core/Billing/AsaasWebhookInboxCandidate.php",
        ROOT / "tests/Feature/PostgresAsaasBillingIntegratedLedgerTest.php",
        ROOT / "tests/Feature/PostgresLabLeadsRlsTest.php",
    )
    if not all(component.is_file() for component in core_components):
        deny("integrated leads and tasks core missing")
    for entry in (ROOT / "routes/web.php", ROOT / "bootstrap/app.php"):
        content = entry.read_text(encoding="utf-8")
        if any(name in content for name in (
            "LaboratorySignedSiteCapture", "LaboratoryTenantLeadLedger",
            "LaboratoryTenantTaskLedger",
            "AsaasSandboxCustomerGateway",
            "AsaasSandboxSubscriptionLookup",
            "AsaasSandboxSubscriptionPaymentsReview",
            "LaboratoryAsaasOrderPaymentHandoff",
        )):
            deny("CI-only business modules must not be wired to public routes")

    raw = COMPOSE.read_text(encoding="utf-8")
    dockerfile = DOCKERFILE.read_text(encoding="utf-8")
    nginx = NGINX.read_text(encoding="utf-8")
    ignore = IGNORE.read_text(encoding="utf-8")
    init = DB_INIT.read_text(encoding="utf-8")
    if "127.0.0.1:18080:8080" not in raw:
        deny("web service must only bind localhost")
    if ("CRM_STAGING_DB_PASSWORD:?" not in raw
        or "CRM_STAGING_APP_DB_PASSWORD:?" not in raw
        or "CRM_STAGING_APP_KEY:?" not in raw):
        deny("mandatory non-production secret placeholders missing")
    if not all(x in ignore.splitlines() for x in [".env", ".env.*", ".git", "vendor"]):
        deny("build context secret exclusions missing")
    for source in (raw, dockerfile):
        if re.search(r"(?i)(artisan\s+(migrate|db:wipe)|migrate:fresh|migrate:refresh|db:wipe|asaas-access-token)", source):
            deny("unsafe migration or financial command in runtime definition")
    if not all(token in init for token in (
        "NOSUPERUSER", "NOBYPASSRLS", "NOCREATEROLE", "NOINHERIT",
        "crm_staging_app", "GRANT CONNECT", "GRANT USAGE ON SCHEMA",
    )):
        deny("unprivileged PostgreSQL runtime bootstrap missing")
    if re.search(r"(?i)GRANT\s+(ALL|CREATE|INSERT|UPDATE|DELETE|TRUNCATE)", init):
        deny("runtime app role must not have mutation privileges in bootstrap")
    if "CRM_AUTH_EXPERIMENTAL: \"false\"" not in raw or "CRM_ASAAS_SANDBOX_ENABLED: \"false\"" not in raw:
        deny("staging authentication/payment must start disabled")
    if "internal;" not in nginx or "fastcgi_pass app:9000;" not in nginx:
        deny("nginx must restrict PHP entrypoint and use private FPM")

    env = os.environ.copy()
    # Intentional impossible CI-only placeholders, NOT working credentials.
    env["CRM_STAGING_DB_PASSWORD"] = "CI-only-not-a-real-staging-password"
    env["CRM_STAGING_APP_DB_PASSWORD"] = "CI-only-not-a-real-app-password"
    env["CRM_STAGING_APP_KEY"] = "base64:CI-only-invalid-application-key-do-not-deploy="
    check = subprocess.run(
        ["docker", "compose", "-f", str(COMPOSE), "config", "--format", "json"],
        cwd=ROOT, env=env, capture_output=True, text=True, timeout=30,
        check=False,
    )
    if check.returncode != 0:
        deny("compose configuration is invalid")
    try:
        cfg = json.loads(check.stdout)
        services = cfg["services"]
        assert set(services) == {"app", "web", "postgres"}
        web_ports = services["web"]["ports"]
        assert len(web_ports) == 1
        assert web_ports[0]["host_ip"] == "127.0.0.1"
        assert int(web_ports[0]["published"]) == 18080
        assert int(web_ports[0]["target"]) == 8080
        assert not services["app"].get("ports")
        assert not services["postgres"].get("ports")
        bootstrap_mounts = services["postgres"].get("volumes", [])
        assert any(
            v.get("target") == "/docker-entrypoint-initdb.d/01-crm-staging-app-role.sh"
            and v.get("read_only") is True
            for v in bootstrap_mounts
        )
        assert services["postgres"]["environment"]["POSTGRES_USER"] == "crm_staging"
        assert services["app"]["environment"]["DB_USERNAME"] == "crm_staging_app"
        assert services["app"]["environment"]["DB_PASSWORD"] != services["postgres"]["environment"]["POSTGRES_PASSWORD"]
        assert services["app"]["environment"]["APP_DEBUG"] == "false"
        assert services["app"]["environment"]["CRM_AUTH_EXPERIMENTAL"] == "false"
        assert services["app"]["environment"]["CRM_ASAAS_SANDBOX_ENABLED"] == "false"
        assert services["app"]["environment"]["CRM_ASAAS_SANDBOX_PAYMENTS_ENABLED"] == "false"
        assert services["app"]["environment"]["CRM_ASAAS_SANDBOX_RECONCILIATION_ENABLED"] == "false"
        # Compose namespaced network IDs; find by suffix.
        nets = cfg["networks"]
        assert len(nets) == 2
        assert any((n == "internal" or n.endswith("_internal")) and v.get("internal") is True for n, v in nets.items())
        assert any(n == "ingress" or n.endswith("_ingress") for n in nets)
        # Network attachment matters more than network existence: exposing
        # only PHP to ingress leaves nginx unreachable from host and weakens
        # network isolation. Keep PHP/PG on internal, nginx on both networks.
        def normalized_networks(service: str) -> set[str]:
            return {
                "internal" if n == "internal" or n.endswith("_internal")
                else "ingress" if n == "ingress" or n.endswith("_ingress")
                else n
                for n in services[service].get("networks", {})
            }
        assert normalized_networks("postgres") == {"internal"}
        assert normalized_networks("app") == {"internal"}
        assert normalized_networks("web") == {"internal", "ingress"}
        assert services["app"].get("read_only") is True
        assert services["web"].get("read_only") is True
    except (KeyError, ValueError, TypeError, AssertionError) as exc:
        deny(f"unsafe or inconsistent private staging boundary: {type(exc).__name__}")

    print("PRIVATE-STAGING: compose static checks passed; NO deployment, NO API, NO migrations.")


if __name__ == "__main__":
    main()
