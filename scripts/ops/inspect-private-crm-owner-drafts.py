#!/usr/bin/env python3
"""Observe the existing private CRM target without activating or changing it.

Standalone stdlib module: the operator may execute its verified bytes through
stdin. It imports no repository module and creates no files, locks or caches.
The Python API accepts explicit CI fixtures; the CLI has no target override.
"""
from __future__ import annotations

import hashlib
import json
import math
import os
from pathlib import Path
import re
import selectors
import signal
import stat
import subprocess
import sys
import time
from dataclasses import dataclass


SCHEMA = "CRM_OWNER_PRIVATE_TARGET_OBSERVATION_V1"
DATABASE = "imagine_crm_private_homologation"
PROJECT = "imagine-crm-private-journey"
SOCKET = "unix:///var/run/docker.sock"
SHA = re.compile(r"[a-f0-9]{40}\Z")
ID = re.compile(r"[a-f0-9]{64}\Z")
IMAGE = re.compile(r"sha256:[a-f0-9]{64}\Z")
SERVICES = frozenset(("postgres", "redis", "app", "web", "tools", "agents-db",
                      "agents-create", "agents-read", "agents-s2s"))
ROLES = ("crm_homologation_owner", "crm_homologation_app", "crm_homologation_iam",
         "crm_homologation_enrollment", "crm_homologation_operator",
         "crm_auto_reader_owner", "crm_auto_transport", "crm_auto_sender")
BASE_TABLES = frozenset("public." + name for name in (
    "crm_private_installation", "crm_global_identities", "crm_organizations", "crm_memberships",
    "crm_auth_credentials", "crm_auth_sessions", "crm_auth_recovery_codes", "crm_auth_operations",
    "crm_auth_mandates", "crm_iam_audit_events", "crm_private_accounts", "crm_private_trials",
    "crm_private_trial_claims", "crm_private_key_versions", "crm_private_signup_requests",
    "crm_private_recovery_attempts", "crm_private_funnels", "crm_private_invitations",
    "crm_private_leads", "crm_private_tasks", "crm_private_events", "crm_private_orders",
    "crm_private_preferences", "crm_private_automation_runs", "crm_private_operator_events"))
OWNER_TABLES = frozenset(("public.crm_private_owner_draft_grants", "public.crm_private_owner_draft_intents"))
S2S_TABLES = frozenset("crm_auto_service." + name for name in
                       ("grants", "nonces", "audit", "outbound_grants", "outbox"))
S2S_SEQUENCE = "crm_auto_service.audit_id_seq"
KNOWN_RELATIONS = BASE_TABLES | OWNER_TABLES | S2S_TABLES | {S2S_SEQUENCE}
ERROR_CODES = frozenset((
    "INVALID_OUTPUT", "INVALID_COMMAND", "COMMAND_START_FAILED", "COMMAND_TIMEOUT", "COMMAND_OUTPUT_LIMIT",
    "COMMAND_FAILED", "INVALID_TARGET", "PATH_ABSENT", "PATH_UNREADABLE", "FILE_UNSAFE", "FILE_LIMIT",
    "CHECKOUT_UNSAFE", "SOURCE_REVISION_INVALID", "SOURCE_ROOT_MISMATCH", "SOURCE_FILTER_UNREVIEWED", "SOURCE_UNREADABLE",
    "INGRESS_UNREADABLE", "INGRESS_LINK_UNEXPECTED", "INGRESS_LINK_UNREADABLE", "CONTAINER_SHAPE",
    "CONTAINER_ID_INVALID", "CONTAINER_LABEL_MISMATCH", "CONTAINER_STATE_INVALID", "CONTAINER_IMAGE_INVALID",
    "CONTAINER_PORTS_INVALID", "CONTAINER_NETWORKS_INVALID", "CONTAINER_MOUNTS_INVALID", "CONTAINER_LIST_INVALID",
    "NO_CONTAINERS", "DUPLICATE_SERVICE", "CONTAINER_OUTPUT_INVALID", "DATABASE_CONTAINER_UNEXPECTED",
    "DATABASE_OUTPUT_INVALID", "DATABASE_IDENTITY_INVALID", "DATABASE_VERSION_UNREVIEWED",
    "CONTAINER_OBSERVATION_UNAVAILABLE", "NO_RUNNING_DATABASE", "ARGUMENTS_NOT_SUPPORTED", "REPORT_LIMIT",
    "PROBE_DEADLINE", "INSPECTION_FAILED"))


class ProbeError(Exception):
    def __init__(self, code: str):
        self.code = code if type(code) is str and code in ERROR_CODES else "INSPECTION_FAILED"
        super().__init__(self.code)


def need(condition: bool, code: str = "INVALID_OUTPUT") -> None:
    if not condition:
        raise ProbeError(code)


def observed(data: dict) -> dict:
    return {"status": "OBSERVED", "data": data}


def unknown(code: str) -> dict:
    return {"status": "UNKNOWN", "code": code}


def absent(code: str) -> dict:
    return {"status": "NOT_OBSERVED", "code": code}


class CommandRunner:
    """Bounded pipes and process group; never echo stderr or exception text."""

    def run(self, argv: list[str], *, input_bytes: bytes | None = None,
            timeout: float = 10, max_output: int = 262144) -> bytes:
        need(isinstance(argv, list) and 0 < len(argv) <= 128
             and all(isinstance(x, str) and 0 < len(x) <= 16384 and "\0" not in x for x in argv)
             and sum(len(x) for x in argv) <= 65536, "INVALID_COMMAND")
        need(type(timeout) in (int, float) and math.isfinite(timeout) and 0 < timeout <= 60
             and type(max_output) is int and 0 < max_output <= 1048576
             and (input_bytes is None or type(input_bytes) is bytes and len(input_bytes) <= 65536),
             "INVALID_COMMAND")
        environment = {"PATH": "/usr/local/bin:/usr/bin:/bin", "LC_ALL": "C",
                       "GIT_CONFIG_NOSYSTEM": "1", "GIT_CONFIG_SYSTEM": "/dev/null",
                       "GIT_CONFIG_GLOBAL": "/dev/null", "GIT_OPTIONAL_LOCKS": "0",
                       "GIT_TERMINAL_PROMPT": "0", "GIT_PAGER": "cat",
                       "PYTHONDONTWRITEBYTECODE": "1"}
        try:
            process = subprocess.Popen(argv, stdin=subprocess.PIPE if input_bytes is not None else subprocess.DEVNULL,
                                       stdout=subprocess.PIPE, stderr=subprocess.PIPE, env=environment,
                                       start_new_session=True, bufsize=0)
        except (OSError, ValueError):
            raise ProbeError("COMMAND_START_FAILED") from None
        selector = selectors.DefaultSelector()
        result = bytearray()
        total = 0
        offset = 0
        complete = False
        deadline = time.monotonic() + timeout
        try:
            for stream, label in ((process.stdout, "stdout"), (process.stderr, "stderr")):
                os.set_blocking(stream.fileno(), False)
                selector.register(stream, selectors.EVENT_READ, label)
            if process.stdin is not None:
                if input_bytes:
                    os.set_blocking(process.stdin.fileno(), False)
                    selector.register(process.stdin, selectors.EVENT_WRITE, "stdin")
                else:
                    process.stdin.close()
            while selector.get_map() or process.poll() is None:
                remaining = deadline - time.monotonic()
                need(remaining > 0, "COMMAND_TIMEOUT")
                for key, _ in selector.select(min(remaining, 0.1)):
                    stream = key.fileobj
                    if key.data == "stdin":
                        try:
                            offset += os.write(stream.fileno(), input_bytes[offset:offset + 8192])
                        except BrokenPipeError:
                            offset = len(input_bytes)
                        if offset == len(input_bytes):
                            selector.unregister(stream)
                            stream.close()
                        continue
                    chunk = os.read(stream.fileno(), 8192)
                    if not chunk:
                        selector.unregister(stream)
                        stream.close()
                        continue
                    total += len(chunk)
                    need(total <= max_output, "COMMAND_OUTPUT_LIMIT")
                    if key.data == "stdout":
                        result.extend(chunk)
            need(process.wait(timeout=max(0.01, deadline - time.monotonic())) == 0, "COMMAND_FAILED")
            complete = True
            return bytes(result)
        except ProbeError:
            raise
        except (OSError, ValueError, subprocess.TimeoutExpired):
            raise ProbeError("COMMAND_FAILED") from None
        finally:
            if not complete:
                try:
                    os.killpg(process.pid, signal.SIGKILL)
                except ProcessLookupError:
                    pass
            selector.close()
            for stream in (process.stdin, process.stdout, process.stderr):
                if stream is not None:
                    stream.close()
            try:
                process.wait(timeout=1)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait(timeout=1)


@dataclass(frozen=True)
class Target:
    checkout: Path
    state: Path
    nginx_crm: Path
    nginx_shared: Path
    nginx_enabled: Path
    project: str = PROJECT
    scope: str = "private-target"

    def __post_init__(self):
        need(all(isinstance(p, Path) and p.is_absolute() and ".." not in p.parts
                 for p in (self.checkout, self.state, self.nginx_crm, self.nginx_shared, self.nginx_enabled)),
             "INVALID_TARGET")
        need(re.fullmatch(r"[a-z][a-z0-9_-]{0,62}", self.project) is not None
             and self.scope in ("private-target", "ci-fixture"), "INVALID_TARGET")
        if self.scope == "private-target":
            need((str(self.checkout), str(self.state), str(self.nginx_crm), str(self.nginx_shared),
                  str(self.nginx_enabled), self.project) == (
                "/srv/imagine/crm-private-candidate", "/srv/imagine/private-crm-journey",
                "/etc/nginx/sites-available/imagine-crm-private-journey",
                "/etc/nginx/sites-available/imagine-dev",
                "/etc/nginx/sites-enabled/imagine-crm-private-journey", PROJECT), "INVALID_TARGET")


def default_target() -> Target:
    return Target(Path("/srv/imagine/crm-private-candidate"), Path("/srv/imagine/private-crm-journey"),
                  Path("/etc/nginx/sites-available/imagine-crm-private-journey"),
                  Path("/etc/nginx/sites-available/imagine-dev"),
                  Path("/etc/nginx/sites-enabled/imagine-crm-private-journey"))


def parent_descriptor(path: Path) -> int:
    """Walk directory descriptors without following any parent symlink."""
    descriptor = os.open("/", os.O_RDONLY | os.O_DIRECTORY)
    try:
        for component in path.parts[1:-1]:
            following = os.open(component, os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW, dir_fd=descriptor)
            os.close(descriptor)
            descriptor = following
        return descriptor
    except BaseException:
        os.close(descriptor)
        raise


def metadata(path: Path) -> dict:
    descriptor = None
    try:
        descriptor = parent_descriptor(path)
        value = os.stat(path.name, dir_fd=descriptor, follow_symlinks=False)
        kind = ("file" if stat.S_ISREG(value.st_mode) else "directory" if stat.S_ISDIR(value.st_mode)
                else "symlink" if stat.S_ISLNK(value.st_mode) else "other")
        return observed({"kind": kind, "uid": value.st_uid, "gid": value.st_gid,
                         "mode": format(stat.S_IMODE(value.st_mode), "04o")})
    except FileNotFoundError:
        return absent("PATH_ABSENT")
    except OSError:
        return unknown("PATH_UNREADABLE")
    finally:
        if descriptor is not None:
            os.close(descriptor)


def regular_bytes(path: Path, limit: int = 262144) -> bytes:
    descriptor = parent_descriptor(path)
    file_descriptor = None
    try:
        file_descriptor = os.open(path.name, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK, dir_fd=descriptor)
        value = os.fstat(file_descriptor)
        need(stat.S_ISREG(value.st_mode) and value.st_size <= limit, "FILE_UNSAFE")
        chunks = bytearray()
        while True:
            chunk = os.read(file_descriptor, min(8192, limit + 1 - len(chunks)))
            if not chunk:
                return bytes(chunks)
            chunks.extend(chunk)
            need(len(chunks) <= limit, "FILE_LIMIT")
    finally:
        if file_descriptor is not None:
            os.close(file_descriptor)
        os.close(descriptor)


CONTAINER_FORMAT = '''{"id":{{json .Id}},"image_id":{{json .Image}},"image_reference":{{json .Config.Image}},"project":{{json (index .Config.Labels "com.docker.compose.project")}},"service":{{json (index .Config.Labels "com.docker.compose.service")}},"state":{{json .State.Status}},"ports":{{json .HostConfig.PortBindings}},"networks":{{json .NetworkSettings.Networks}},"mounts":{{json .Mounts}}}'''

# All SQL is fixed and reads only pg_catalog. Even the installation marker is
# observed as catalog structure, never as rows. No user-defined function, stored
# application data, role password or SQL body is read.
SQL = r"""BEGIN ISOLATION LEVEL REPEATABLE READ READ ONLY;
SET LOCAL search_path=pg_catalog;
SET LOCAL row_security=off;
SET LOCAL statement_timeout='3000ms';
SET LOCAL lock_timeout='500ms';
SET LOCAL idle_in_transaction_session_timeout='5000ms';
SELECT json_build_object(
 'database_matches',current_database()='imagine_crm_private_homologation',
 'server_major',current_setting('server_version_num')::integer/10000,
 'read_only',current_setting('transaction_read_only')='on',
 'repeatable_read',current_setting('transaction_isolation')='repeatable read',
 'schemas',COALESCE((SELECT json_agg(nspname ORDER BY nspname) FROM pg_namespace WHERE nspname !~ '^pg_' AND nspname<>'information_schema'),'[]'::json),
 'relations',COALESCE((SELECT json_agg(json_build_object('name',n.nspname||'.'||c.relname,'kind',c.relkind,
 'owner',pg_get_userbyid(c.relowner),'rls',c.relrowsecurity,'force_rls',c.relforcerowsecurity) ORDER BY n.nspname,c.relname)
 FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace
 WHERE n.nspname !~ '^pg_' AND n.nspname<>'information_schema' AND c.relkind IN ('r','p','v','m','f','S')),'[]'::json),
 'roles',COALESCE((SELECT json_agg(json_build_object('name',rolname,'login',rolcanlogin,'superuser',rolsuper,
 'bypass_rls',rolbypassrls,'create_db',rolcreatedb,'create_role',rolcreaterole,'inherit',rolinherit,'replication',rolreplication) ORDER BY rolname)
 FROM pg_roles WHERE rolname IN ('crm_homologation_owner','crm_homologation_app','crm_homologation_iam',
 'crm_homologation_enrollment','crm_homologation_operator','crm_auto_reader_owner','crm_auto_transport','crm_auto_sender')),'[]'::json),
 'membership_count',(SELECT count(*) FROM pg_auth_members WHERE member IN (SELECT oid FROM pg_roles
 WHERE rolname IN ('crm_homologation_owner','crm_homologation_app','crm_homologation_iam','crm_homologation_enrollment',
 'crm_homologation_operator','crm_auto_reader_owner','crm_auto_transport','crm_auto_sender'))),
 'default_acl_count',(SELECT count(*) FROM pg_default_acl),
 'user_function_count',(SELECT count(*) FROM pg_proc p JOIN pg_namespace n ON n.oid=p.pronamespace
 WHERE n.nspname !~ '^pg_' AND n.nspname<>'information_schema'),
 'owner_lock_marker',(SELECT count(*) FROM pg_attribute a JOIN pg_class c ON c.oid=a.attrelid JOIN pg_namespace n ON n.oid=c.relnamespace
 WHERE n.nspname='public' AND c.relname='crm_private_accounts' AND c.relkind='r' AND a.attnum>0 AND NOT a.attisdropped
 AND a.attname='owner_draft_lock_marker' AND a.atttypid='bigint'::regtype AND a.attnotnull)=1);
SELECT json_build_object('structure_matches',EXISTS(SELECT 1 FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace
 WHERE n.nspname='public' AND c.relname='crm_private_installation' AND c.relkind='r'
 AND NOT c.relrowsecurity AND NOT c.relforcerowsecurity
 AND c.relam=(SELECT oid FROM pg_am WHERE amname='heap')
 AND NOT EXISTS(SELECT 1 FROM pg_inherits i WHERE i.inhrelid=c.oid OR i.inhparent=c.oid)
 AND pg_get_userbyid(c.relowner)='crm_homologation_owner'
 AND (SELECT count(*) FROM pg_attribute a WHERE a.attrelid=c.oid AND a.attnum>0 AND NOT a.attisdropped)=2
 AND EXISTS(SELECT 1 FROM pg_attribute a WHERE a.attrelid=c.oid AND a.attname='id' AND a.atttypid='integer'::regtype AND a.attnum>0 AND NOT a.attisdropped)
 AND EXISTS(SELECT 1 FROM pg_attribute a WHERE a.attrelid=c.oid AND a.attname='mode' AND a.atttypid='text'::regtype AND a.attnum>0 AND NOT a.attisdropped)),
 'mode','NOT_READ');
ROLLBACK;
"""


def parse_json(raw: bytes) -> dict:
    def unique(pairs):
        result = {}
        for key, value in pairs:
            need(key not in result, "INVALID_OUTPUT")
            result[key] = value
        return result
    try:
        value = json.loads(raw.decode("utf-8"), object_pairs_hook=unique,
                           parse_constant=lambda _: (_ for _ in ()).throw(ProbeError("INVALID_OUTPUT")))
    except (UnicodeError, ValueError, RecursionError):
        raise ProbeError("INVALID_OUTPUT") from None
    need(type(value) is dict, "INVALID_OUTPUT")
    return value


class Inspector:
    def __init__(self, target: Target, runner: CommandRunner | None = None):
        self.target = target
        self.runner = runner or CommandRunner()
        self._deadline = None

    def run(self, argv: list[str], **options) -> bytes:
        if self._deadline is not None:
            remaining = self._deadline - time.monotonic()
            need(remaining > 0, "PROBE_DEADLINE")
            options["timeout"] = min(options.get("timeout", 10), remaining)
        return self.runner.run(argv, **options)

    def source(self) -> dict:
        meta = metadata(self.target.checkout)
        if meta["status"] != "OBSERVED":
            return meta
        if meta["data"]["kind"] != "directory":
            return unknown("CHECKOUT_UNSAFE")
        git = ["git", "--no-optional-locks", "-c", "core.fsmonitor=false", "-c", "core.untrackedCache=false",
               "-c", "diff.external=", "-c", "core.hooksPath=/dev/null", "-C", str(self.target.checkout)]
        try:
            head = self.run(git + ["rev-parse", "--verify", "HEAD"], max_output=128).decode("ascii").strip()
            need(SHA.fullmatch(head) is not None, "SOURCE_REVISION_INVALID")
            top = self.run(git + ["rev-parse", "--show-toplevel"], max_output=4096).decode("utf-8").strip()
            need(top == str(self.target.checkout), "SOURCE_ROOT_MISMATCH")
            # Status may invoke a clean/process filter. Inspect names only, never
            # values, and stop before status if local filters/includes exist.
            config_names = self.run(git + ["config", "--local", "--name-only", "--list"], max_output=65536).decode("utf-8").splitlines()
            need(not any(name.lower().startswith(("filter.", "include.", "includeif."))
                         or name.lower() == "extensions.worktreeconfig" for name in config_names),
                 "SOURCE_FILTER_UNREVIEWED")
            drift = self.run(git + ["status", "--porcelain=v1", "--untracked-files=no", "--ignore-submodules=all"], max_output=65536)
            return observed({"head_sha": head, "tracked_changes": bool(drift.strip())})
        except ProbeError as error:
            return unknown(error.code)
        except (UnicodeError, OSError):
            return unknown("SOURCE_UNREADABLE")

    def protected_paths(self) -> dict:
        # lstat only: runtime.env and vault contents are never opened.
        paths = {"state": self.target.state, "runtime_environment": self.target.state / "runtime.env",
                 "data": self.target.state / "data", "publication_marker": self.target.state / "published.json",
                 "reviewer_vault": self.target.state / "data/review-access.json",
                 "automated_fixture_vault": self.target.state / "data/fixtures.json"}
        return observed({name: metadata(path) for name, path in paths.items()})

    def ingress(self) -> dict:
        data = {}
        for name, path in (("crm", self.target.nginx_crm), ("shared", self.target.nginx_shared)):
            meta = metadata(path)
            if meta["status"] == "OBSERVED":
                try:
                    meta = observed({**meta["data"], "sha256": hashlib.sha256(regular_bytes(path)).hexdigest()})
                except ProbeError as error:
                    meta = unknown(error.code)
                except OSError:
                    meta = unknown("INGRESS_UNREADABLE")
            data[name] = meta
        link = metadata(self.target.nginx_enabled)
        if link["status"] == "OBSERVED":
            if link["data"]["kind"] != "symlink":
                link = unknown("INGRESS_LINK_UNEXPECTED")
            else:
                descriptor = None
                try:
                    descriptor = parent_descriptor(self.target.nginx_enabled)
                    target = os.readlink(self.target.nginx_enabled.name, dir_fd=descriptor)
                    normalized = os.path.normpath(os.path.join(str(self.target.nginx_enabled.parent), target))
                    link = observed({"points_to_expected_config": normalized == str(self.target.nginx_crm)})
                except OSError:
                    link = unknown("INGRESS_LINK_UNREADABLE")
                finally:
                    if descriptor is not None:
                        os.close(descriptor)
        data["enabled_link"] = link
        return observed(data)

    def docker(self, *args: str, **options) -> bytes:
        # Linux kernel fd directory has no config.json/contexts and cannot host
        # a user config file. Do not load Docker's home credential/context store.
        return self.run(["docker", "--config", "/proc/self/fd", "--host", SOCKET, *args], **options)

    def project_container(self, value: dict, expected_id: str) -> dict:
        need(set(value) == {"id", "image_id", "image_reference", "project", "service", "state", "ports", "networks", "mounts"}, "CONTAINER_SHAPE")
        need(value["id"] == expected_id and ID.fullmatch(value["id"]) is not None
             and type(value["image_id"]) is str and IMAGE.fullmatch(value["image_id"]) is not None,
             "CONTAINER_ID_INVALID")
        need(value["project"] == self.target.project and value["service"] in SERVICES, "CONTAINER_LABEL_MISMATCH")
        need(value["state"] in ("created", "restarting", "running", "removing", "paused", "exited", "dead"), "CONTAINER_STATE_INVALID")
        reference = value["image_reference"]
        need(type(reference) is str and len(reference) <= 256, "CONTAINER_IMAGE_INVALID")
        tag = re.fullmatch(r"imagine-crm-private-journey:([a-f0-9]{40})", reference)
        known = re.fullmatch(r"(?:postgres:16|redis:7-alpine|nginx:(?:1\.28|stable)-alpine|(?:postgres|redis|nginx)@sha256:[a-f0-9]{64}|sha256:[a-f0-9]{64}|imagine-crm-owner-draft-agents:[a-f0-9]{40})", reference)
        image_reference = reference if tag or known else None
        ports = value["ports"] or {}
        need(type(ports) is dict and len(ports) <= 16, "CONTAINER_PORTS_INVALID")
        bindings = []
        for port, entries in ports.items():
            need(type(port) is str and (entries is None or type(entries) is list and len(entries) <= 8), "CONTAINER_PORTS_INVALID")
            for entry in entries or []:
                need(type(entry) is dict and set(entry) == {"HostIp", "HostPort"}
                     and type(entry["HostIp"]) is str and type(entry["HostPort"]) is str
                     and re.fullmatch(r"[0-9]{1,5}", entry["HostPort"]) is not None
                     and 0 < int(entry["HostPort"]) <= 65535, "CONTAINER_PORTS_INVALID")
                bindings.append({"container_port": port if port in {"8080/tcp", "5432/tcp", "6379/tcp", "9000/tcp", "9001/tcp", "8443/tcp"} else "UNRECOGNIZED",
                                 "host_port": int(entry["HostPort"]),
                                 "host_binding": "loopback" if entry["HostIp"] in ("127.0.0.1", "::1") else "wildcard" if entry["HostIp"] in ("", "0.0.0.0", "::") else "other"})
        networks = value["networks"]
        need(type(networks) is dict and len(networks) <= 16, "CONTAINER_NETWORKS_INVALID")
        known_networks = []
        unknown_networks = 0
        for name, network in networks.items():
            need(type(network) is dict and type(network.get("NetworkID")) is str and ID.fullmatch(network["NetworkID"]) is not None, "CONTAINER_NETWORKS_INVALID")
            if name in (self.target.project + "_internal", self.target.project + "_ingress"):
                known_networks.append({"network": name.removeprefix(self.target.project + "_"), "id": network["NetworkID"]})
            else:
                unknown_networks += 1
        mounts = value["mounts"]
        need(type(mounts) is list and len(mounts) <= 32, "CONTAINER_MOUNTS_INVALID")
        destinations = {"/var/lib/postgresql/data", "/data", "/srv/crm/public", "/etc/nginx/conf.d/default.conf",
                        "/docker-entrypoint-initdb.d/10-schema.sql", "/docker-entrypoint-initdb.d/20-runtime-roles.sh",
                        "/certs", "/run/secrets", "/usr/local/etc/php-fpm.d/zz-owner-draft.conf", "/private"}
        bind_sources = {str(self.target.checkout / "public"): "crm_public", str(self.target.checkout / "infra/homologation/nginx.conf"): "crm_nginx",
                        str(self.target.checkout / "infra/homologation/schema.sql"): "crm_base_schema",
                        str(self.target.checkout / "infra/homologation/20-runtime-roles.sh"): "crm_runtime_roles",
                        str(self.target.state / "data"): "crm_protected_data"}
        volume_suffixes = ("private_crm_pg", "private_crm_redis", "owner_drafts_agents_pg")
        projected_mounts = []
        for mount in mounts:
            need(type(mount) is dict and mount.get("Type") in ("bind", "volume", "tmpfs")
                 and type(mount.get("RW")) is bool and type(mount.get("Destination")) is str, "CONTAINER_MOUNTS_INVALID")
            name = mount.get("Name")
            source = bind_sources.get(mount.get("Source"), "UNRECOGNIZED") if mount["Type"] == "bind" else "UNRECOGNIZED"
            if mount["Type"] == "volume" and name in tuple(self.target.project + "_" + suffix for suffix in volume_suffixes):
                source = name.removeprefix(self.target.project + "_")
            projected_mounts.append({"type": mount["Type"], "destination": mount["Destination"] if mount["Destination"] in destinations else "UNRECOGNIZED",
                                     "source": source, "writable": mount["RW"]})
        return {"service": value["service"], "id": expected_id, "image_id": value["image_id"], "state": value["state"],
                "image_reference": image_reference, "source_tag_sha": tag.group(1) if tag else None,
                "ports": bindings, "networks": known_networks, "unknown_network_count": unknown_networks, "mounts": projected_mounts}

    def containers(self) -> dict:
        try:
            raw = self.docker("ps", "--all", "--no-trunc", "--filter", "label=com.docker.compose.project=" + self.target.project,
                              "--format", "{{.ID}}", max_output=2048)
            ids = raw.decode("ascii").splitlines()
            need(len(ids) <= len(SERVICES) and len(set(ids)) == len(ids) and all(ID.fullmatch(item) for item in ids), "CONTAINER_LIST_INVALID")
            if not ids:
                return absent("NO_CONTAINERS")
            services = [self.project_container(parse_json(self.docker("inspect", "--type", "container", "--format", CONTAINER_FORMAT, item)), item) for item in ids]
            need(len({item["service"] for item in services}) == len(services), "DUPLICATE_SERVICE")
            return observed({"services": sorted(services, key=lambda item: item["service"]),
                             "app_tag_matches_checkout": None, "image_provenance_verified": False})
        except ProbeError as error:
            return unknown(error.code)
        except (UnicodeError, TypeError, KeyError):
            return unknown("CONTAINER_OUTPUT_INVALID")

    def database(self, container_id: str) -> dict:
        try:
            need(type(container_id) is str and ID.fullmatch(container_id) is not None, "CONTAINER_ID_INVALID")
            container = self.project_container(parse_json(self.docker("inspect", "--type", "container", "--format", CONTAINER_FORMAT, container_id)), container_id)
            need(container["service"] == "postgres" and container["state"] == "running"
                 and container["image_reference"] is not None
                 and re.fullmatch(r"postgres(?::16|@sha256:[a-f0-9]{64})", container["image_reference"]) is not None,
                 "DATABASE_CONTAINER_UNEXPECTED")
            raw = self.docker("exec", "-i", container_id, "env", "-i", "PATH=/usr/local/bin:/usr/bin:/bin", "LC_ALL=C",
                              "PGPASSFILE=/dev/null", "PGSERVICEFILE=/dev/null",
                              "psql", "-X", "--no-password", "-qAt", "-h", "/var/run/postgresql", "-p", "5432",
                              "-U", "crm_homologation_owner", "-d", DATABASE, "-v", "ON_ERROR_STOP=1", "-f", "-",
                              input_bytes=SQL.encode("ascii"), timeout=15, max_output=262144)
            lines = raw.splitlines()
            need(len(lines) == 2, "DATABASE_OUTPUT_INVALID")
            catalog, installation = (parse_json(line) for line in lines)
            need(set(catalog) == {"database_matches", "server_major", "read_only", "repeatable_read", "schemas", "relations", "roles", "membership_count", "default_acl_count", "user_function_count", "owner_lock_marker"}, "DATABASE_OUTPUT_INVALID")
            need(catalog["database_matches"] is True and catalog["read_only"] is True and catalog["repeatable_read"] is True, "DATABASE_IDENTITY_INVALID")
            need(type(catalog["server_major"]) is int and catalog["server_major"] == 16, "DATABASE_VERSION_UNREVIEWED")
            need(set(installation) == {"structure_matches", "mode"}
                 and type(installation["structure_matches"]) is bool and installation["mode"] == "NOT_READ",
                 "DATABASE_OUTPUT_INVALID")
            need(type(catalog["schemas"]) is list and len(catalog["schemas"]) <= 32 and all(type(x) is str for x in catalog["schemas"]), "DATABASE_OUTPUT_INVALID")
            need(len(set(catalog["schemas"])) == len(catalog["schemas"]), "DATABASE_OUTPUT_INVALID")
            need(type(catalog["relations"]) is list and len(catalog["relations"]) <= 256, "DATABASE_OUTPUT_INVALID")
            relations = []
            identities = set()
            unknown_relations = 0
            for relation in catalog["relations"]:
                need(type(relation) is dict and set(relation) == {"name", "kind", "owner", "rls", "force_rls"}
                     and all(type(relation[k]) is str for k in ("name", "kind", "owner"))
                     and relation["kind"] in ("r", "p", "v", "m", "f", "S")
                     and type(relation["rls"]) is bool and type(relation["force_rls"]) is bool, "DATABASE_OUTPUT_INVALID")
                need(relation["name"] not in identities, "DATABASE_OUTPUT_INVALID")
                identities.add(relation["name"])
                if relation["name"] in KNOWN_RELATIONS:
                    relations.append({**relation, "owner": relation["owner"] if relation["owner"] in ROLES else "UNRECOGNIZED"})
                else:
                    unknown_relations += 1
            roles = []
            seen_roles = set()
            need(type(catalog["roles"]) is list and len(catalog["roles"]) <= len(ROLES), "DATABASE_OUTPUT_INVALID")
            for role in catalog["roles"]:
                need(type(role) is dict and set(role) == {"name", "login", "superuser", "bypass_rls", "create_db", "create_role", "inherit", "replication"}
                     and role["name"] in ROLES and role["name"] not in seen_roles
                     and all(type(value) is bool for name, value in role.items() if name != "name"), "DATABASE_OUTPUT_INVALID")
                seen_roles.add(role["name"])
                roles.append(role)
            for key in ("membership_count", "default_acl_count", "user_function_count"):
                need(type(catalog[key]) is int and 0 <= catalog[key] <= 1000000, "DATABASE_OUTPUT_INVALID")
            need(type(catalog["owner_lock_marker"]) is bool, "DATABASE_OUTPUT_INVALID")
            schemas = set(catalog["schemas"])
            relation_kinds = {(item["name"], item["kind"]) for item in catalog["relations"]}
            inventory = "UNRECOGNIZED"
            if schemas == {"public"} and relation_kinds == {(name, "r") for name in BASE_TABLES}:
                inventory = "BASE_25"
            elif schemas == {"public"} and relation_kinds == {(name, "r") for name in BASE_TABLES | OWNER_TABLES}:
                inventory = "OWNER_27"
            elif schemas == {"public", "crm_auto_service"} and relation_kinds == ({(name, "r") for name in BASE_TABLES | S2S_TABLES} | {(S2S_SEQUENCE, "S")}):
                inventory = "BASE_S2S_30_WITH_SEQUENCE"
            return observed({"database": DATABASE, "server_major": 16, "transaction_read_only": True,
                             "transaction_repeatable_read": True, "installation": installation,
                             "relation_inventory": inventory, "profile_verified": False,
                             "known_schemas": sorted(schemas & {"public", "crm_auto_service"}),
                             "unknown_schema_count": len(schemas - {"public", "crm_auto_service"}),
                             "relations": sorted(relations, key=lambda item: item["name"]),
                             "unknown_relation_count": unknown_relations, "roles": sorted(roles, key=lambda item: item["name"]),
                             **{key: catalog[key] for key in ("membership_count", "default_acl_count", "user_function_count", "owner_lock_marker")}})
        except ProbeError as error:
            return unknown(error.code)
        except (UnicodeError, TypeError, KeyError):
            return unknown("DATABASE_OUTPUT_INVALID")

    def collect(self) -> dict:
        self._deadline = time.monotonic() + 45
        source = self.source()
        containers = self.containers()
        database = unknown("CONTAINER_OBSERVATION_UNAVAILABLE")
        if containers["status"] == "OBSERVED":
            services = containers["data"]["services"]
            app = next((s for s in services if s["service"] == "app"), None)
            if app and app["source_tag_sha"] is not None and source["status"] == "OBSERVED":
                containers["data"]["app_tag_matches_checkout"] = app["source_tag_sha"] == source["data"]["head_sha"]
            postgres = next((s for s in services if s["service"] == "postgres" and s["state"] == "running"), None)
            database = self.database(postgres["id"]) if postgres else absent("NO_RUNNING_DATABASE")
        elif containers["status"] == "NOT_OBSERVED":
            database = absent("NO_RUNNING_DATABASE")
        report = {"schema": SCHEMA, "scope": self.target.scope, "read_only": True,
                "deploy_approved": False, "mutations_executed": False, "consistent_snapshot_verified": False,
                "sections": {"source": source, "protected_paths": self.protected_paths(), "ingress": self.ingress(),
                             "containers": containers, "database": database}}
        need(len(json.dumps(report, ensure_ascii=True).encode("ascii")) <= 65536, "REPORT_LIMIT")
        return report


def main(args: list[str]) -> int:
    if args:
        print(json.dumps({"schema": SCHEMA, "status": "UNKNOWN", "code": "ARGUMENTS_NOT_SUPPORTED", "deploy_approved": False}))
        return 2
    try:
        report = Inspector(default_target()).collect()
        wire = json.dumps(report, ensure_ascii=True, sort_keys=True)
        need(len(wire.encode("ascii")) <= 65536, "REPORT_LIMIT")
        print(wire)
        return 0
    except Exception:
        print(json.dumps({"schema": SCHEMA, "status": "UNKNOWN", "code": "INSPECTION_FAILED", "deploy_approved": False}))
        return 1


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
