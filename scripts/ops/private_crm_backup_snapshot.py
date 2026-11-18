"""One real PostgreSQL snapshot for private-backup reads and pg_dump.

The exporter is read-only and bounded. It is closed as soon as the dump has been
captured; encryption and restore never keep a transaction open on the source.
This module has no import-time process or database action.
"""
from __future__ import annotations

from contextlib import contextmanager
from datetime import datetime, timezone
import json
import os
import re
import secrets
import selectors
import signal
import subprocess
import time

from private_crm_backup_contract import DATABASE, require

OWNER = "crm_homologation_owner"
SNAPSHOT_ID = re.compile(r"[0-9A-Fa-f]{8}-[0-9A-Fa-f]{8}-[0-9]+\Z")


def _literal(value: str) -> str:
    return "'" + value.replace("'", "''") + "'"


class PrivateBackupSnapshot:
    def __init__(self, source_command: list[str], *, duration_seconds: int = 120):
        require(source_command and all(isinstance(part, str) and part for part in source_command)
                and 5 <= duration_seconds <= 120, "Invalid private snapshot command or deadline.")
        self.source = list(source_command)
        self.application = "imagine-crm-backup-snapshot-" + secrets.token_hex(8)
        self.control_application = self.application + "-ctl"
        self.psql = self.source + ["env", "PGAPPNAME=" + self.application, "PGCONNECT_TIMEOUT=3",
                                   "PGOPTIONS=-c statement_timeout=15000 -c idle_in_transaction_session_timeout=150000",
                                   "psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-v", "VERBOSITY=sqlstate",
                                   "-U", OWNER, "-d", DATABASE]
        self.deadline = time.monotonic() + duration_seconds
        self.keeper = None
        self.backend_pid = None
        self.backend_started = None
        self.exported_at = None
        self.identifier = None
        self.closed = False
        self.operation_markers = set()
        self.observed_backends = []

    def _remaining(self) -> float:
        remaining = self.deadline - time.monotonic()
        require(remaining > 0, "Private backup snapshot deadline exceeded.")
        return remaining

    def _control_query(self, statement: str) -> str:
        # Control connections have server-side deadlines shorter than the client
        # deadline and never hold a transaction. They are tagged and checked too.
        command = self.source + ["env", "PGAPPNAME=" + self.control_application,
            "PGCONNECT_TIMEOUT=3", "PGOPTIONS=-c statement_timeout=5000 -c idle_in_transaction_session_timeout=5000",
            "psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-v", "VERBOSITY=sqlstate", "-U", OWNER, "-d", DATABASE]
        process = subprocess.Popen(command, stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                                   stderr=subprocess.PIPE, start_new_session=True)
        try:
            stdout, stderr = process.communicate(input=statement.encode(), timeout=8)
        except subprocess.TimeoutExpired:
            self._stop_client(process)
            raise RuntimeError("Private snapshot SQL deadline exceeded.") from None
        if process.returncode:
            states = re.findall(rb"(?:ERROR|FATAL):\s+([0-9A-Z]{5})\b", stderr)
            state = states[-1].decode() if states else "unavailable"
            raise RuntimeError("Private snapshot SQL failed; SQLSTATE=" + state + "; raw output suppressed.")
        return stdout.decode().strip()

    def _owned_backends(self, markers: set[str]) -> list[dict]:
        if not markers:
            return []
        names = ",".join(_literal(marker) for marker in sorted(markers))
        rows = json.loads(self._control_query(
            "SELECT COALESCE(json_agg(json_build_object('pid',pid,'started',backend_start,"
            "'application',application_name)),'[]'::json) FROM pg_stat_activity "
            "WHERE pid<>pg_backend_pid() AND datname=" + _literal(DATABASE)
            + " AND usename=" + _literal(OWNER) + " AND application_name IN (" + names + ")"))
        require(isinstance(rows, list), "Private snapshot backend inventory is invalid.")
        for row in rows:
            require(type(row.get("pid")) is int and row["pid"] > 0
                    and isinstance(row.get("started"), str) and row.get("application") in markers,
                    "Private snapshot backend identity is invalid.")
            try:
                require(datetime.fromisoformat(row["started"]).tzinfo is not None,
                        "Private backend timestamp needs an explicit offset.")
            except ValueError:
                raise RuntimeError("Private backend timestamp is invalid.") from None
        return rows

    def _cleanup_operations(self, markers: set[str]) -> None:
        # A random marker is assigned before launching each command. Lookup then
        # binds it to a real PID/start; termination rechecks every identity field.
        for row in self._owned_backends(markers):
            self.observed_backends.append(row)
            own_backend = (f"pid={row['pid']} AND backend_start=" + _literal(row["started"]) + "::timestamptz"
                " AND datname=" + _literal(DATABASE) + " AND usename=" + _literal(OWNER)
                + " AND application_name=" + _literal(row["application"]))
            stopped = self._control_query("SELECT pg_terminate_backend(pid,3000) FROM pg_stat_activity WHERE "
                                          + own_backend + " AND pid<>pg_backend_pid()")
            # The backend can finish between observation and termination. A false
            # result is acceptable only if the fresh inventory below proves exit.
            require(stopped in ("", "t", "f"), "Private snapshot operation cleanup result is invalid.")
        require(not self._owned_backends(markers), "Private snapshot operation remains active.")

    @staticmethod
    def _stop_client(process) -> None:
        # docker compose has local descendants. Each invocation gets its own
        # process group, so cancellation cannot leave the plugin holding pipes.
        try:
            os.killpg(process.pid, signal.SIGTERM)
        except ProcessLookupError:
            pass
        try:
            process.communicate(timeout=2)
        except subprocess.TimeoutExpired:
            try:
                os.killpg(process.pid, signal.SIGKILL)
            except ProcessLookupError:
                pass
            process.communicate(timeout=2)

    def _operation(self, kind: str, arguments: list[str], content: bytes | None = None,
                   *, limit_seconds: float | None = None) -> bytes:
        self._require_active()
        marker = self.application + ("-q-" if kind == "SQL" else "-d-") + secrets.token_hex(4)
        require(len(marker.encode()) <= 63, "Private snapshot operation marker would be truncated.")
        self.operation_markers.add(marker)
        command = self.source + ["env", "PGAPPNAME=" + marker, "PGCONNECT_TIMEOUT=3",
            "PGOPTIONS=-c statement_timeout=20000 -c idle_in_transaction_session_timeout=30000", *arguments]
        process = None
        original_error = None
        local_deadline = time.monotonic() + limit_seconds if limit_seconds is not None else float("inf")
        try:
            process = subprocess.Popen(command, stdin=subprocess.PIPE if content is not None else subprocess.DEVNULL,
                                       stdout=subprocess.PIPE, stderr=subprocess.PIPE, start_new_session=True)
            pending_input = content
            while True:
                remaining = min(self.deadline, local_deadline) - time.monotonic()
                require(remaining > 0, "Private snapshot " + kind + " client deadline exceeded.")
                try:
                    stdout, stderr = process.communicate(input=pending_input, timeout=min(0.2, remaining))
                    break
                except subprocess.TimeoutExpired:
                    pending_input = None
            if process.returncode:
                states = re.findall(rb"(?:ERROR|FATAL):\s+([0-9A-Z]{5})\b", stderr)
                state = states[-1].decode() if states else "unavailable"
                raise RuntimeError("Private snapshot " + kind + " failed; SQLSTATE=" + state + "; raw output suppressed.")
            return stdout
        except BaseException as error:
            original_error = error
            raise
        finally:
            if process is not None and (original_error is not None or process.poll() is None):
                try:
                    self._stop_client(process)
                except Exception:
                    if original_error is not None:
                        original_error.add_note("Private snapshot client process-group cleanup could not be confirmed.")
                    else:
                        raise
            if original_error is not None:
                try:
                    self._cleanup_operations({marker})
                except Exception:
                    original_error.add_note("Private snapshot operation cleanup could not be confirmed.")

    def open(self) -> "PrivateBackupSnapshot":
        require(self.keeper is None and not self.closed, "Private snapshot cannot be reopened.")
        self.keeper = subprocess.Popen(self.psql, stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                                       stderr=subprocess.PIPE, text=True, encoding="utf-8", start_new_session=True)
        setup = (
            "SET application_name=" + _literal(self.application) + ";\n"
            "SET statement_timeout='15s';\n"
            "SET idle_in_transaction_session_timeout='150s';\n"
            "BEGIN ISOLATION LEVEL REPEATABLE READ READ ONLY;\n"
            "SELECT json_build_object('snapshot',pg_export_snapshot(),'pid',pg_backend_pid(),"
            "'backend_started',(SELECT backend_start FROM pg_stat_activity WHERE pid=pg_backend_pid()),"
            "'exported_at',clock_timestamp(),'read_only',current_setting('transaction_read_only'),"
            "'isolation',current_setting('transaction_isolation'));\n"
        )
        self.keeper.stdin.write(setup)
        self.keeper.stdin.flush()
        metadata_bytes = b""
        export_deadline = min(self.deadline, time.monotonic() + 15)
        with selectors.DefaultSelector() as selector:
            selector.register(self.keeper.stdout, selectors.EVENT_READ)
            # A partial pipe frame must not turn readline() into an unbounded wait.
            while b"\n" not in metadata_bytes:
                remaining = export_deadline - time.monotonic()
                require(remaining > 0 and bool(selector.select(remaining)), "Private snapshot export timed out.")
                chunk = os.read(self.keeper.stdout.fileno(), 4097 - len(metadata_bytes))
                require(bool(chunk), "Private snapshot exporter closed before its metadata.")
                metadata_bytes += chunk
                require(len(metadata_bytes) <= 4096, "Private snapshot exporter metadata exceeds its limit.")
        try:
            metadata = json.loads(metadata_bytes)
        except (ValueError, TypeError):
            raise RuntimeError("Private snapshot exporter returned invalid metadata.") from None
        require(isinstance(metadata, dict) and isinstance(metadata.get("snapshot"), str)
                and SNAPSHOT_ID.fullmatch(metadata["snapshot"])
                and type(metadata.get("pid")) is int and metadata["pid"] > 0
                and isinstance(metadata.get("backend_started"), str)
                and isinstance(metadata.get("exported_at"), str)
                and metadata.get("read_only") == "on" and metadata.get("isolation") == "repeatable read",
                "Private snapshot exporter contract failed.")
        try:
            started = datetime.fromisoformat(metadata["backend_started"])
            exported = datetime.fromisoformat(metadata["exported_at"])
        except ValueError:
            raise RuntimeError("Private snapshot exporter timestamps are invalid.") from None
        require(started.tzinfo is not None and exported.tzinfo is not None,
                "Private snapshot exporter timestamps need explicit offsets.")
        self.identifier = metadata["snapshot"]
        self.backend_pid = metadata["pid"]
        self.backend_started = metadata["backend_started"]
        self.exported_at = exported.astimezone(timezone.utc).isoformat()
        return self

    def _require_active(self) -> None:
        require(not self.closed and self.identifier is not None and self.keeper is not None
                and self.keeper.poll() is None, "Private source snapshot is no longer active.")
        self._remaining()

    def query(self, statement: str) -> str:
        """Run read-only SQL under this snapshot; caller must not wrap transactions."""
        self._require_active()
        # All current callers pass reviewed, internal SELECT/SET ROLE statements.
        # Reject transaction controls explicitly; nested BEGIN must never be ignored.
        require(not re.search(r"(?:\A|;)\s*(?:BEGIN|START\s+TRANSACTION|COMMIT|END|ROLLBACK|ABORT|SAVEPOINT|RELEASE\s+SAVEPOINT|PREPARE\s+TRANSACTION|SET\s+TRANSACTION)\b",
                              statement, flags=re.I), "Snapshot query cannot manage its own transaction.")
        timeout = min(20, self._remaining())
        statement_ms = max(1, int(min(15, timeout * 0.8) * 1000))
        wrapped = (
            "BEGIN ISOLATION LEVEL REPEATABLE READ READ ONLY;\n"
            "SET TRANSACTION SNAPSHOT " + _literal(self.identifier) + ";\n"
            f"SET LOCAL statement_timeout='{statement_ms}ms';\n"
            "SET LOCAL lock_timeout='2s';\n"
            + statement + ";\nROLLBACK;\n"
        )
        arguments = ["psql", "-X", "-qAt", "-v", "ON_ERROR_STOP=1", "-v", "VERBOSITY=sqlstate",
                     "-U", OWNER, "-d", DATABASE]
        return self._operation("SQL", arguments, wrapped.encode(), limit_seconds=timeout).decode().strip()

    def dump(self) -> bytes:
        """pg_dump imports the same live snapshot; callers cannot replace its ID."""
        self._require_active()
        arguments = ["pg_dump", "-U", OWNER, "-d", DATABASE, "-Fc",
                                 "--snapshot=" + self.identifier, "--lock-wait-timeout=2s"]
        return self._operation("dump", arguments)

    def close(self) -> None:
        if self.closed:
            return
        self.closed = True
        if self.keeper is None:
            return
        cleanup_error = None
        try:
            self._cleanup_operations(self.operation_markers | {self.control_application})
        except Exception as error:
            cleanup_error = error
        if self.keeper.poll() is None:
            try:
                self.keeper.stdin.write("ROLLBACK;\n\\q\n")
                self.keeper.stdin.flush()
            except (BrokenPipeError, OSError):
                pass
        try:
            self.keeper.communicate(timeout=5)
        except subprocess.TimeoutExpired:
            try:
                self._stop_client(self.keeper)
            except Exception as error:
                cleanup_error = cleanup_error or error
        if self.backend_pid is None or self.backend_started is None:
            # Startup may fail after connecting but before metadata reaches us.
            # PGAPPNAME was set before connection, so discover/verify that owned
            # backend instead of assuming Docker EOF closed it.
            self._cleanup_operations(self.operation_markers | {self.application, self.control_application})
            if cleanup_error is not None:
                raise cleanup_error
            return
        # Docker exec client termination alone must not leave the server transaction alive.
        # Target only the exporter we observed: PID, backend start, owner, DB and random marker.
        own_backend = (
            f"pid={self.backend_pid} AND backend_start=" + _literal(self.backend_started) + "::timestamptz"
            " AND datname=" + _literal(DATABASE) + " AND usename=" + _literal(OWNER)
            + " AND application_name=" + _literal(self.application)
        )
        remaining = self._control_query("SELECT count(*) FROM pg_stat_activity WHERE " + own_backend)
        if remaining != "0":
            stopped = self._control_query("SELECT pg_terminate_backend(pid,3000) FROM pg_stat_activity WHERE "
                                          + own_backend)
            require(stopped in ("", "t", "f"), "Private snapshot exporter cleanup result is invalid.")
        require(self._control_query("SELECT count(*) FROM pg_stat_activity WHERE " + own_backend) == "0",
                "Private snapshot exporter remains active.")
        self._cleanup_operations(self.operation_markers | {self.application, self.control_application})
        if cleanup_error is not None:
            raise cleanup_error


@contextmanager
def exported_snapshot(source_command: list[str], *, duration_seconds: int = 120):
    snapshot = PrivateBackupSnapshot(source_command, duration_seconds=duration_seconds)
    original_error = None
    try:
        snapshot.open()
        yield snapshot
    except BaseException as error:
        original_error = error
        raise
    finally:
        try:
            snapshot.close()
        except Exception:
            if original_error is None:
                raise
            original_error.add_note("Private snapshot cleanup could not be confirmed; no backup success is permitted.")
