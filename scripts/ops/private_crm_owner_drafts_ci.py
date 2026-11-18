#!/usr/bin/env python3
"""Disposable GitHub CI composition for the approved CRM owner draft flow."""
from __future__ import annotations

import argparse
import hashlib
import io
import json
import os
from pathlib import Path
import re
import secrets
import subprocess
import sys
import tarfile
import time

ROOT = Path(__file__).resolve().parents[2]
REPOSITORY = "rafhael100/imagine-crm"
BASE = "d3f06599cddfc244f6089d3dac01f29e5a45b8c0"
BASE_REF = "feat/crm-op01-navigable-summary-2026-11-17"
HEAD_REF = "feat/crm-owner-agents-drafts-2026-11-17"
INTEGRATION_BASE = "d7c7aacc766c7ade4c65dcd211ed4569442144a9"
INTEGRATION_BASE_REF = "dev/fast-track-crm-2026-11-04"
INTEGRATION_HEAD_REF = "integrate/crm-private-journey-and-owner-drafts-2026-11-18"
MIGRATION_HEAD_REF = "feat/crm-owner-monitor-incremental-migration-2026-11-18"
MIGRATION_ANCESTOR = "96dbf1aed2d1cf3237bc3631c4594540a0168d2a"
LEDGER_HEAD_REF = "feat/crm-ledger-reapply-rls-2026-11-18"
LEDGER_ANCESTOR = "d8963313267ccc5b40b7527d6fa24a30fcc12b7e"
BASE_MONITOR_HEAD_REF = "feat/crm-base-monitor-incremental-migration-2026-11-18"
BASE_MONITOR_ANCESTOR = "082a1fda4af9b1871854d0db8cbfe54858894f25"
STRICT_CSV_HEAD_REF = "fix/crm-private-csv-strict-decoding-2026-11-18"
STRICT_CSV_ANCESTOR = "7e1b00a0c173bcfcac3a9051ef6cad3aededcdb8"
TASK_DATE_HEAD_REF = "fix/crm-private-task-date-validation-2026-11-18"
TASK_DATE_ANCESTOR = "c073cfeb8d29fc6cd0a9f33642ccc1275b8b6f59"
NULLABLE_PREFERENCES_HEAD_REF = "fix/crm-nullable-automation-preferences-2026-11-18"
NULLABLE_PREFERENCES_ANCESTOR = "2a6d2950c54873ebbb233b64ef7985e0750c0576"
INTEGRATION_ANCESTORS = (
    "8c62702d02138233404fda73506b779f3ebb3143",
    "fe88dd15d42dbda0bab206dfdbc9f8ce65093212",
)
AGENTS = "0c722eee6bd53a7ee46c4ba68ab70200fc191b78"
CENTRAL = "c08688b16e61d8d1ecc4d8c0ed4da8758a687ee8"
PROJECT = "imagine-crm-journey-ci"
PROFILE = "owner-rule-draft-v1"
CREATE = "crm.rule.draft.create"
READ = "crm.rule.draft.read"
KEY_IDS = {CREATE: "crm_owner_create_1", READ: "crm_owner_read_1"}


class Failure(Exception):
    pass


def need(value, label):
    if not value:
        raise Failure(label)


def run(args, label, *, data=None, cwd=ROOT, timeout=180, check=True):
    result = subprocess.run(args, cwd=cwd, input=data, capture_output=True, timeout=timeout)
    need(not check or result.returncode == 0, label)
    return result


def git(directory, *args):
    # CI uses sudo only for its private fixtures. Trust these three exact
    # checkout paths for this invocation, never a global safe.directory='*'.
    directory = Path(directory).resolve()
    need(directory in {ROOT.resolve(), (ROOT / "_agents_owner_drafts").resolve(),
                       (ROOT / "_central_p30_pinned").resolve()}, "OWNER_DRAFT_CHECKOUT_PATH_DENIED")
    return run(["git", "-c", "safe.directory=" + str(directory), "-C", str(directory), *args],
               "OWNER_DRAFT_SOURCE_DENIED").stdout


def protected(path):
    need(path.is_file() and not path.is_symlink() and not path.stat().st_mode & 0o077,
         "OWNER_DRAFT_PROTECTED_FILE_REQUIRED")


def preflight():
    need(os.environ.get("GITHUB_ACTIONS") == "true"
         and os.environ.get("GITHUB_REPOSITORY") == REPOSITORY
         and os.environ.get("GITHUB_EVENT_NAME") == "pull_request"
         and os.environ.get("CRM_OWNER_DRAFT_RUNNER_ENVIRONMENT") == "github-hosted"
         and os.environ.get("CRM_PRIVATE_PROJECT") == PROJECT,
         "OWNER_DRAFT_ACTUAL_DISPOSABLE_CI_REQUIRED")
    need(not os.environ.get("DOCKER_HOST") and not os.environ.get("DOCKER_CONTEXT"),
         "OWNER_DRAFT_LOCAL_DOCKER_REQUIRED")
    need(re.fullmatch(r"[a-f0-9]{40}", os.environ.get("GITHUB_SHA", "")), "OWNER_DRAFT_EVENT_SHA_REQUIRED")
    event_path = Path(os.environ.get("GITHUB_EVENT_PATH", ""))
    need(event_path.is_file() and not event_path.is_symlink() and event_path.stat().st_size <= 1048576,
         "OWNER_DRAFT_EVENT_FILE_REQUIRED")
    event = json.loads(event_path.read_text())
    request = event["pull_request"]
    provenance = (request["base"]["sha"], request["base"]["ref"], request["head"]["ref"])
    original = (BASE, BASE_REF, HEAD_REF)
    integration = (INTEGRATION_BASE, INTEGRATION_BASE_REF, INTEGRATION_HEAD_REF)
    migration = (INTEGRATION_BASE, INTEGRATION_BASE_REF, MIGRATION_HEAD_REF)
    ledger = (INTEGRATION_BASE, INTEGRATION_BASE_REF, LEDGER_HEAD_REF)
    base_monitor = (INTEGRATION_BASE, INTEGRATION_BASE_REF, BASE_MONITOR_HEAD_REF)
    strict_csv = (INTEGRATION_BASE, INTEGRATION_BASE_REF, STRICT_CSV_HEAD_REF)
    task_date = (INTEGRATION_BASE, INTEGRATION_BASE_REF, TASK_DATE_HEAD_REF)
    nullable_preferences = (INTEGRATION_BASE, INTEGRATION_BASE_REF, NULLABLE_PREFERENCES_HEAD_REF)
    # Admit only these complete tuples, never combinations of their fields.
    need(event["repository"]["full_name"] == REPOSITORY and event["repository"]["private"] is False
         and request["head"]["repo"]["full_name"] == REPOSITORY
         and request["base"]["repo"]["full_name"] == REPOSITORY
         and provenance in (original, integration, migration, ledger, base_monitor, strict_csv, task_date, nullable_preferences), "OWNER_DRAFT_PR_PROVENANCE_DENIED")
    head = request["head"]["sha"]
    need(re.fullmatch(r"[a-f0-9]{40}", head)
         and git(ROOT, "rev-parse", "HEAD").decode().strip() == head
         and os.environ.get("CRM_PRIVATE_IMAGE_TAG") == head, "OWNER_DRAFT_EXACT_HEAD_REQUIRED")
    git(ROOT, "diff", "--quiet")
    git(ROOT, "diff", "--cached", "--quiet")
    git(ROOT, "merge-base", "--is-ancestor", BASE, head)
    if provenance in (integration, migration, ledger, base_monitor, strict_csv, task_date, nullable_preferences):
        for ancestor in INTEGRATION_ANCESTORS:
            git(ROOT, "merge-base", "--is-ancestor", ancestor, head)
    if provenance in (migration, ledger, base_monitor, strict_csv, task_date, nullable_preferences):
        git(ROOT, "merge-base", "--is-ancestor", MIGRATION_ANCESTOR, head)
    if provenance in (ledger, base_monitor, strict_csv, task_date, nullable_preferences):
        git(ROOT, "merge-base", "--is-ancestor", LEDGER_ANCESTOR, head)
    if provenance in (base_monitor, strict_csv, task_date, nullable_preferences):
        git(ROOT, "merge-base", "--is-ancestor", BASE_MONITOR_ANCESTOR, head)
    if provenance in (strict_csv, task_date, nullable_preferences):
        git(ROOT, "merge-base", "--is-ancestor", STRICT_CSV_ANCESTOR, head)
    if provenance in (task_date, nullable_preferences):
        git(ROOT, "merge-base", "--is-ancestor", TASK_DATE_ANCESTOR, head)
    if provenance == nullable_preferences:
        git(ROOT, "merge-base", "--is-ancestor", NULLABLE_PREFERENCES_ANCESTOR, head)
    temporary = Path(os.environ.get("RUNNER_TEMP", ""))
    need(temporary.is_absolute() and temporary.is_dir() and not temporary.is_symlink(),
         "OWNER_DRAFT_RUNNER_TEMP_REQUIRED")
    return head, temporary.resolve()


def context():
    _, temporary = preflight()
    folder = temporary / "crm-owner-drafts-ci"
    path = folder / "context.json"
    protected(path)
    value = json.loads(path.read_text())
    need(value["agents_sha"] == AGENTS and value["central_sha"] == CENTRAL
         and value["project"] == PROJECT, "OWNER_DRAFT_CONTEXT_PIN_DENIED")
    return folder, value


def compose(*args, override=True, data=None, check=True, timeout=180):
    envfile = Path(os.environ["CRM_PRIVATE_ENV_FILE"])
    protected(envfile)
    command = ["docker", "compose", "--env-file", str(envfile), "-p", PROJECT,
               "-f", str(ROOT / "infra/homologation/compose.yaml")]
    if override:
        folder, _ = context()
        command.extend(["-f", str(folder / "compose.json")])
    return run(command + list(args), "OWNER_DRAFT_COMPOSE_FAILED", data=data, check=check, timeout=timeout)


def sql(query, *, agents=False):
    return compose("exec", "-T", "agents-db" if agents else "postgres", "psql", "-X", "-qAt",
                   "-v", "ON_ERROR_STOP=1", "-U", "postgres" if agents else "crm_homologation_owner",
                   "-d", "imagine_agents_ci" if agents else "imagine_crm_private_homologation",
                   data=query.encode()).stdout.decode().strip()


def public_file(path, data):
    need(not path.exists(), "OWNER_DRAFT_FRESH_FIXTURE_REQUIRED")
    path.write_bytes(data if isinstance(data, bytes) else data.encode())
    path.chmod(0o644)


def secret_file(path, *, uid=33):
    value = secrets.token_hex(32)
    descriptor = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
    with os.fdopen(descriptor, "w") as stream:
        stream.write(value)
    os.chown(path, uid, uid)
    print("::add-mask::" + value)
    return value


def certificate(folder, name, san, *, client=False):
    run(["openssl", "req", "-new", "-newkey", "rsa:2048", "-nodes", "-subj", "/CN=" + name,
         "-keyout", str(folder / (name + ".key")), "-out", str(folder / (name + ".csr"))],
        "OWNER_DRAFT_CERTIFICATE_FAILED")
    extension = folder / (name + ".ext")
    extension.write_text("basicConstraints=CA:FALSE\nkeyUsage=digitalSignature,keyEncipherment\n"
                         + "extendedKeyUsage=" + ("clientAuth" if client else "serverAuth")
                         + "\nsubjectAltName=" + san + "\n")
    run(["openssl", "x509", "-req", "-in", str(folder / (name + ".csr")), "-CA", str(folder / "ca.crt"),
         "-CAkey", str(folder / "ca.key"), "-CAcreateserial", "-days", "1", "-sha256", "-extfile", str(extension),
         "-out", str(folder / (name + ".crt"))], "OWNER_DRAFT_CERTIFICATE_SIGNING_FAILED")


def export_agents(temporary):
    source = ROOT / "_agents_owner_drafts"
    central = ROOT / "_central_p30_pinned"
    need(re.fullmatch(r"[a-f0-9]{40}", AGENTS), "OWNER_DRAFT_REVIEWED_AGENTS_PIN_REQUIRED")
    for directory, sha in ((source, AGENTS), (central, CENTRAL)):
        need(directory.is_dir() and not directory.is_symlink()
             and git(directory, "rev-parse", "HEAD").decode().strip() == sha, "OWNER_DRAFT_PROVIDER_PIN_DENIED")
        git(directory, "diff", "--quiet")
        git(directory, "diff", "--cached", "--quiet")
    git(source, "fetch", "--quiet", "--no-tags", "--no-write-fetch-head", str(central), CENTRAL)
    tree = git(source, "merge-tree", "--write-tree", "--name-only", CENTRAL, AGENTS).splitlines()[0].decode()
    need(re.fullmatch(r"[a-f0-9]{40}", tree), "OWNER_DRAFT_PROVIDER_TREE_DENIED")
    directory = temporary / "crm-owner-drafts-agents-source"
    directory.mkdir(mode=0o755)
    with tarfile.open(fileobj=io.BytesIO(git(source, "archive", tree))) as bundle:
        bundle.extractall(directory, filter="data")
    for item in git(source, "ls-tree", "-rz", "-r", tree).split(b"\0"):
        if not item:
            continue
        meta, name = item.split(b"\t", 1)
        mode, kind, expected = meta.split()
        relative = Path(os.fsdecode(name))
        need(kind == b"blob" and mode in (b"100644", b"100755")
             and not relative.is_absolute() and ".." not in relative.parts, "OWNER_DRAFT_PROVIDER_ENTRY_DENIED")
        path = directory / relative
        need(path.is_file() and not path.is_symlink(), "OWNER_DRAFT_PROVIDER_FILE_DENIED")
        body = path.read_bytes()
        actual = hashlib.sha1(b"blob " + str(len(body)).encode() + b"\0" + body).hexdigest()
        need(actual == expected.decode(), "OWNER_DRAFT_PROVIDER_BLOB_DENIED")
        path.chmod(0o755 if mode == b"100755" else 0o644)
    for path in directory.rglob("*"):
        if path.is_dir():
            path.chmod(0o755)
    return directory, tree


def prepare():
    head, temporary = preflight()
    need(os.geteuid() == 0, "OWNER_DRAFT_PROTECTED_FIXTURE_OWNER_REQUIRED")
    folder = temporary / "crm-owner-drafts-ci"
    folder.mkdir(mode=0o700)
    source, tree = export_agents(temporary)
    runtime = source / "runtime/central-auto/crm-owner-drafts"
    image_tag = "imagine-crm-owner-draft-agents:" + AGENTS
    run(["docker", "build", "--quiet", "-f", str(runtime / "Dockerfile"), "-t", image_tag, str(source)],
        "OWNER_DRAFT_PROVIDER_IMAGE_BUILD_FAILED", timeout=600)
    php_image = run(["docker", "image", "inspect", image_tag, "--format", "{{.Id}}"],
                    "OWNER_DRAFT_PROVIDER_IMAGE_REQUIRED").stdout.decode().strip()
    need(re.fullmatch(r"sha256:[a-f0-9]{64}", php_image), "OWNER_DRAFT_IMMUTABLE_IMAGE_REQUIRED")
    images = {}
    for label, tag in (("pg", "postgres:16"), ("nginx", "nginx:stable-alpine")):
        run(["docker", "pull", tag], "OWNER_DRAFT_RUNTIME_PULL_FAILED", timeout=300)
        images[label] = run(["docker", "image", "inspect", tag, "--format", "{{index .RepoDigests 0}}"],
                            "OWNER_DRAFT_RUNTIME_DIGEST_REQUIRED").stdout.decode().strip()
        need(re.fullmatch(r"[^\s]+@sha256:[a-f0-9]{64}", images[label]), "OWNER_DRAFT_RUNTIME_DIGEST_DENIED")
    certs = folder / "certificates"
    certs.mkdir(mode=0o700)
    run(["openssl", "req", "-x509", "-newkey", "rsa:2048", "-nodes", "-days", "1", "-sha256",
         "-subj", "/CN=IMAGINE_OWNER_DRAFT_CI", "-keyout", str(certs / "ca.key"), "-out", str(certs / "ca.crt")],
        "OWNER_DRAFT_CA_FAILED")
    certificate(certs, "agents-db", "DNS:agents-db")
    certificate(certs, "agents-s2s", "DNS:agents-s2s")
    certificate(certs, "crm", "DNS:imagine-crm-owner-ci", client=True)
    bundles = {}
    for label, names, uid in (("crm-certs", ("ca.crt", "crm.crt", "crm.key"), 33),
                               ("gateway-certs", ("ca.crt", "agents-s2s.crt", "agents-s2s.key"), 0),
                               ("pg-certs", ("ca.crt", "agents-db.crt", "agents-db.key"), 999),
                               ("fpm-certs", ("ca.crt",), 33)):
        target = folder / label
        target.mkdir(mode=0o700)
        os.chown(target, uid, uid)
        for name in names:
            item = target / (name.replace("agents-s2s", "agents") if label == "gateway-certs" else name)
            item.write_bytes((certs / name).read_bytes())
            item.chmod(0o600 if name.endswith(".key") else 0o644)
            os.chown(item, uid, uid)
        bundles[label] = target
    keys = folder / "crm-keys"
    keys.mkdir(mode=0o700)
    os.chown(keys, 33, 33)
    create_key = secret_file(keys / "crm_owner_draft_create_key")
    read_key = secret_file(keys / "crm_owner_draft_read_key")
    passwords = {}
    for label in ("create", "read"):
        target = folder / (label + "-secrets")
        target.mkdir(mode=0o700)
        os.chown(target, 33, 33)
        passwords[label] = secret_file(target / "owner-draft-db-password")
    pg_password = secret_file(folder / "pg-bootstrap-password", uid=0)
    gateway = folder / "gateway.conf"
    public_file(gateway, (runtime / "nginx.conf").read_bytes())
    faults = folder / "http-faults"
    faults.mkdir(mode=0o755)
    for mode, handler in (("delay", "usleep(4500000); return $body;"),
                          ("tamper", "$value=json_decode($body,true); if(is_array($value)){$value['nonce']='00000000-0000-4000-8000-000000000000'; return json_encode($value);} return $body;")):
        public_file(faults / (mode + ".php"), "<?php\nob_start(static function(string $body): string {" + handler
                    + "});\nrequire '/var/www/html/runtime/central-auto/crm-owner-drafts/create.php';\n")
    database = {"image": images["pg"], "environment": {"POSTGRES_DB": "imagine_agents_ci",
                "POSTGRES_USER": "postgres", "POSTGRES_PASSWORD": pg_password},
                "command": ["postgres", "-c", "ssl=on", "-c", "ssl_cert_file=/certs/agents-db.crt", "-c",
                            "ssl_key_file=/certs/agents-db.key", "-c", "log_statement=none", "-c", "log_min_error_statement=panic",
                            "-c", "log_parameter_max_length_on_error=0"],
                "volumes": [str(bundles["pg-certs"]) + ":/certs:ro", "owner_drafts_agents_pg:/var/lib/postgresql/data"],
                "networks": ["internal"], "security_opt": ["no-new-privileges:true"],
                "healthcheck": {"test": ["CMD-SHELL", "pg_isready -h 127.0.0.1 -U postgres -d imagine_agents_ci"],
                                "interval": "2s", "timeout": "2s", "retries": 30}}
    services = {"agents-db": database}
    for label, port in (("create", 9000), ("read", 9001)):
        services["agents-" + label] = {"image": php_image, "command": ["php-fpm", "--nodaemonize"],
            "environment": {"AGENTS_PRIVATE_CRM_OWNER_DRAFTS": PROFILE,
                            "AGENTS_PRIVATE_CRM_DRAFT_DSN": "pgsql:host=agents-db;port=5432;dbname=imagine_agents_ci;sslmode=verify-full;sslrootcert=/certs/ca.crt",
                            "AGENTS_PRIVATE_CRM_DRAFT_DB_PASSWORD_FILE": "/run/secrets/owner-draft-db-password"},
            "volumes": [str(bundles["fpm-certs"]) + ":/certs:ro", str(folder / (label + "-secrets")) + ":/run/secrets:ro",
                        str(runtime / (label + "-fpm.conf")) + ":/usr/local/etc/php-fpm.d/zz-owner-draft.conf:ro",
                        str(faults) + ":/test-fixtures:ro"],
            "depends_on": {"agents-db": {"condition": "service_healthy"}}, "networks": ["internal"],
            "read_only": True, "tmpfs": ["/tmp"], "cap_drop": ["ALL"], "security_opt": ["no-new-privileges:true"]}
    services["agents-s2s"] = {"image": images["nginx"], "volumes": [str(gateway) + ":/etc/nginx/conf.d/default.conf:ro",
        str(bundles["gateway-certs"]) + ":/certs:ro"], "depends_on": ["agents-create", "agents-read"],
        "networks": ["internal"], "read_only": True, "tmpfs": ["/var/cache/nginx", "/var/run", "/tmp"],
        "security_opt": ["no-new-privileges:true"]}
    services["app"] = {"environment": {"CRM_PRIVATE_OWNER_DRAFTS": PROFILE},
                       "volumes": [str(bundles["crm-certs"]) + ":/certs:ro", str(keys) + ":/run/secrets:ro"],
                       "depends_on": {"agents-s2s": {"condition": "service_started"}}}
    document = {"services": services, "volumes": {"owner_drafts_agents_pg": {}}}
    (folder / "compose.json").write_text(json.dumps(document))
    (folder / "compose.json").chmod(0o600)
    fingerprint = hashlib.sha256(run(["openssl", "x509", "-in", str(certs / "crm.crt"), "-outform", "DER"],
                                    "OWNER_DRAFT_CLIENT_FINGERPRINT_REQUIRED").stdout).hexdigest()
    value = {"crm_sha": head, "agents_sha": AGENTS, "central_sha": CENTRAL, "agents_tree": tree,
             "agents_source": str(source), "project": PROJECT, "fingerprint": fingerprint,
             "runtime_image": php_image, "evidence": str(temporary / "crm-owner-drafts-evidence")}
    (folder / "context.json").write_text(json.dumps(value))
    (folder / "context.json").chmod(0o600)
    compose("config", "--quiet")
    compose("up", "-d", "--no-build", "agents-db")
    for _ in range(45):
        ready = compose("exec", "-T", "agents-db", "pg_isready", "-h", "127.0.0.1", "-U", "postgres", "-d", "imagine_agents_ci", check=False)
        if ready.returncode == 0:
            break
        time.sleep(1)
    else:
        raise Failure("OWNER_DRAFT_DATABASE_NOT_READY")
    sql((runtime / "schema.sql").read_text(), agents=True)
    bootstrap = ""
    for label in ("create", "read"):
        bootstrap += "CREATE ROLE aia_p67_" + label + "_login LOGIN NOINHERIT NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE PASSWORD '" + passwords[label] + "';\n"
        bootstrap += "GRANT aia_p67_" + label + " TO aia_p67_" + label + "_login WITH INHERIT FALSE, SET TRUE;\n"
        bootstrap += "GRANT CONNECT ON DATABASE imagine_agents_ci TO aia_p67_" + label + "_login;\n"
    sql(bootstrap, agents=True)
    sql((ROOT / "infra/homologation/s2s/owner-drafts.sql").read_text())
    compose("up", "-d", "--no-build")
    compose("restart", "web")
    print(json.dumps({"check": "CRM_OWNER_DRAFT_COMPOSITION_PREPARED", **{name: value[name] for name in
                      ("crm_sha", "agents_sha", "central_sha", "agents_tree", "runtime_image")}}))


def seed_grants(vault):
    folder, value = context()
    protected(vault)
    fixtures = json.loads(vault.read_text())
    expiry = int(time.time()) + 3600
    keys = {CREATE: (folder / "crm-keys/crm_owner_draft_create_key").read_text(),
            READ: (folder / "crm-keys/crm_owner_draft_read_key").read_text()}
    for operation, key in keys.items():
        need(re.fullmatch(r"[a-f0-9]{64}", key), "OWNER_DRAFT_KEY_SHAPE_REQUIRED")
        sql("INSERT INTO aia_p67_owner.mac_keys(key_id,operation,secret,active,revision,expires_at) VALUES ('"
            + KEY_IDS[operation] + "','" + operation + "','" + key + "',true,1," + str(expiry) + ");", agents=True)
    for label in ("clientA", "clientB"):
        company = fixtures[label]["organization"]
        need(re.fullmatch(r"[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}", company), "OWNER_DRAFT_FIXTURE_COMPANY_REQUIRED")
        sql("INSERT INTO aia_p67_owner.companies(company_id,active,revision) VALUES ('" + company + "',true,1);", agents=True)
        for operation in (CREATE, READ):
            sql("INSERT INTO aia_p67_owner.service_grants(company_id,fingerprint,operation,key_id,active,revision,expires_at) VALUES ('"
                + company + "','" + value["fingerprint"] + "','" + operation + "','" + KEY_IDS[operation]
                + "',true,1," + str(expiry) + ");", agents=True)
            sql("INSERT INTO public.crm_private_owner_draft_grants(organization_id,operation,key_id,active,revision,expires_at) VALUES ('"
                + company + "','" + operation + "','" + KEY_IDS[operation] + "',true,1," + str(expiry) + ");")
    print("CRM_OWNER_DRAFT_FICTIONAL_GRANTS_READY")


def gateway_fault(mode):
    need(mode in (None, "delay", "tamper"), "OWNER_DRAFT_FAULT_MODE_DENIED")
    folder, value = context()
    original = (Path(value["agents_source"]) / "runtime/central-auto/crm-owner-drafts/nginx.conf").read_text()
    if mode:
        marker = "/var/www/html/runtime/central-auto/crm-owner-drafts/create.php"
        need(original.count(marker) == 1, "OWNER_DRAFT_ORIGINAL_HANDLER_REQUIRED")
        original = original.replace(marker, "/test-fixtures/" + mode + ".php")
    (folder / "gateway.conf").write_text(original)
    compose("exec", "-T", "agents-s2s", "nginx", "-t")
    compose("exec", "-T", "agents-s2s", "nginx", "-s", "reload")
    time.sleep(0.25)


def disable():
    folder, _ = context()
    path = folder / "compose.json"
    document = json.loads(path.read_text())
    document["services"]["app"]["environment"]["CRM_PRIVATE_OWNER_DRAFTS"] = ""
    path.write_text(json.dumps(document))
    compose("up", "-d", "--no-build", "--no-deps", "--force-recreate", "app")
    compose("restart", "web")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("mode", choices=("preflight", "prepare", "seed-grants", "disable", "cleanup"))
    parser.add_argument("vault", nargs="?")
    arguments = parser.parse_args()
    preflight()
    if arguments.mode == "preflight":
        print("CRM_OWNER_DRAFT_PREFLIGHT_PASS")
    elif arguments.mode == "prepare":
        prepare()
    elif arguments.mode == "seed-grants":
        need(arguments.vault, "OWNER_DRAFT_FIXTURE_VAULT_REQUIRED")
        seed_grants(Path(arguments.vault))
    elif arguments.mode == "disable":
        disable()
    else:
        folder = Path(os.environ["RUNNER_TEMP"]) / "crm-owner-drafts-ci"
        compose("down", "--volumes", "--remove-orphans", override=(folder / "context.json").is_file())


if __name__ == "__main__":
    try:
        main()
    except (Failure, OSError, ValueError, KeyError, subprocess.SubprocessError, tarfile.TarError) as error:
        print(str(error) if isinstance(error, Failure) else "CRM_OWNER_DRAFT_CI_FAILED", file=sys.stderr)
        raise SystemExit(2)
