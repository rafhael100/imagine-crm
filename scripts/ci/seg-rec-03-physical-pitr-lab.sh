#!/usr/bin/env bash
set -Eeuo pipefail

# SEG-REC-03: physical PostgreSQL base backup + archived WAL restore-point
# exercise. ONLY on an ephemeral GitHub-hosted PR runner, with fake data.
# No persistent environment or customer connection is permitted.

deny() { printf 'SEG-REC-03 DENIED: %s\n' "$1" >&2; exit 42; }

[[ "${CI:-}" == 'true' && "${GITHUB_ACTIONS:-}" == 'true' && \
   "${GITHUB_REPOSITORY:-}" == 'rafhael100/imagine-crm' && \
   "${GITHUB_EVENT_NAME:-}" == 'pull_request' && \
   "${GITHUB_BASE_REF:-}" == 'dev/fast-track-crm-2026-11-04' && \
   "${RUNNER_ENVIRONMENT:-}" == 'github-hosted' && \
   "${GITHUB_REF:-}" =~ ^refs/pull/[1-9][0-9]*/merge$ && \
   -n "${GITHUB_HEAD_REF:-}" ]] || deny 'unapproved CI event/repository/runner/branch'

# Refuse any explicit PostgreSQL connection overrides, credentials or URLs.
for key in DB_URL DATABASE_URL PGHOST PGSERVICE PGSERVICEFILE PGUSER PGDATABASE PGPORT PGPASSWORD DB_PASSWORD DB_HOST; do
  if [[ -n "${!key:-}" ]]; then deny "external database setting present: $key"; fi
done

command -v docker >/dev/null 2>&1 || deny 'Docker missing'
docker info >/dev/null 2>&1 || deny 'Docker daemon inaccessible'

# Every Docker resource is created inside this run. No published ports,
# host mounts, pre-existing volumes or network connections are used.
readonly TAG="seg-rec-03-${GITHUB_RUN_ID:?}-${GITHUB_RUN_ATTEMPT:-1}"
[[ "$TAG" =~ ^seg-rec-03-[0-9]+-[0-9]+$ ]] || deny 'invalid resource prefix'
readonly SOURCE="${TAG}-source" TARGET="${TAG}-target"
readonly SRC_NET="${TAG}-src-net" REST_NET="${TAG}-restore-net"
readonly SRC_DATA="${TAG}-source-data" BACKUP="${TAG}-basebackup" ARCHIVE="${TAG}-wal"
readonly DB_NAME='seg_rec_03'
readonly LAB_PASSWORD='seg-rec-03-synthetic-only'

for r in "$SOURCE" "$TARGET"; do
  if docker container inspect "$r" >/dev/null 2>&1; then deny "name already exists: $r"; fi
done
for r in "$SRC_DATA" "$BACKUP" "$ARCHIVE"; do
  if docker volume inspect "$r" >/dev/null 2>&1; then deny "volume already exists: $r"; fi
done
for r in "$SRC_NET" "$REST_NET"; do
  if docker network inspect "$r" >/dev/null 2>&1; then deny "network already exists: $r"; fi
done

created_source=false created_target=false
created_src_net=false created_rest_net=false
created_src_data=false created_backup=false created_archive=false
cleanup() {
  local status=$?
  trap - EXIT
  if [[ "$created_target" == true ]]; then docker rm -f "$TARGET" >/dev/null 2>&1 || true; fi
  if [[ "$created_source" == true ]]; then docker rm -f "$SOURCE" >/dev/null 2>&1 || true; fi
  if [[ "$created_src_net" == true ]]; then docker network rm "$SRC_NET" >/dev/null 2>&1 || true; fi
  if [[ "$created_rest_net" == true ]]; then docker network rm "$REST_NET" >/dev/null 2>&1 || true; fi
  if [[ "$created_backup" == true ]]; then docker volume rm "$BACKUP" >/dev/null 2>&1 || true; fi
  if [[ "$created_archive" == true ]]; then docker volume rm "$ARCHIVE" >/dev/null 2>&1 || true; fi
  if [[ "$created_src_data" == true ]]; then docker volume rm "$SRC_DATA" >/dev/null 2>&1 || true; fi
  printf 'SEG-REC-03 cleanup complete; exit=%d\n' "$status"
  exit "$status"
}
trap cleanup EXIT

docker network create --internal --label seg-rec-03=synthetic "$SRC_NET" >/dev/null
created_src_net=true
docker network create --internal --label seg-rec-03=synthetic "$REST_NET" >/dev/null
created_rest_net=true
docker volume create --label seg-rec-03=synthetic "$SRC_DATA" >/dev/null
created_src_data=true
docker volume create --label seg-rec-03=synthetic "$BACKUP" >/dev/null
created_backup=true
docker volume create --label seg-rec-03=synthetic "$ARCHIVE" >/dev/null
created_archive=true

# Named volumes belong solely to this job; no bind mounts into runner host.
docker run --rm --network none --mount "type=volume,src=$ARCHIVE,dst=/wal" \
  postgres:16 sh -c 'chown postgres:postgres /wal && chmod 0700 /wal'
docker run --rm --network none --mount "type=volume,src=$BACKUP,dst=/backup" \
  postgres:16 sh -c 'chown postgres:postgres /backup && chmod 0700 /backup'

docker run -d --name "$SOURCE" --network "$SRC_NET" --network-alias pg-source \
  --mount "type=volume,src=$SRC_DATA,dst=/var/lib/postgresql/data" \
  --mount "type=volume,src=$ARCHIVE,dst=/wal" \
  -e "POSTGRES_DB=$DB_NAME" -e POSTGRES_USER=postgres -e "POSTGRES_PASSWORD=$LAB_PASSWORD" \
  postgres:16 \
  -c wal_level=replica -c archive_mode=on \
  -c "archive_command=test ! -f /wal/%f && cp %p /wal/%f" \
  -c archive_timeout=2s -c max_wal_senders=4 >/dev/null
created_source=true

sql_source() {
  docker exec -e "PGPASSWORD=$LAB_PASSWORD" "$SOURCE" \
    psql -X -v ON_ERROR_STOP=1 -U postgres -d "$DB_NAME" -A -t -c "$1"
}
sql_target() {
  docker exec "$TARGET" psql -X -v ON_ERROR_STOP=1 -U postgres -d "$DB_NAME" -A -t -c "$1"
}
wait_ready() {
  local name=$1
  for ((i=0;i<90;i++)); do
    if docker exec "$name" pg_isready -U postgres -d "$DB_NAME" >/dev/null 2>&1; then return 0; fi
    sleep 1
  done
  docker logs "$name" --tail 80 >&2 || true
  return 1
}
wait_ready "$SOURCE"

sql_source "CREATE TABLE seg_rec_03_proof (event text PRIMARY KEY, phase integer NOT NULL)" >/dev/null
sql_source "INSERT INTO seg_rec_03_proof VALUES ('before_backup', 1)" >/dev/null

# Physical base backup (plain format with streamed WAL for consistency).
# Helper container can reach ONLY pg-source over the internal source network.
docker run --rm --network "$SRC_NET" --user postgres \
  --mount "type=volume,src=$BACKUP,dst=/backup" \
  -e "PGPASSWORD=$LAB_PASSWORD" postgres:16 \
  pg_basebackup -h pg-source -U postgres -D /backup -Fp -Xs -c fast -P >/dev/null

# Restore point exists AFTER the base backup; a later synthetic row must NOT
# appear after recovering to that named point. The origin is then stopped.
sql_source "INSERT INTO seg_rec_03_proof VALUES ('before_cut', 2)" >/dev/null
sql_source "SELECT pg_create_restore_point('seg_rec_03_cut')" >/dev/null
sql_source "INSERT INTO seg_rec_03_proof VALUES ('after_cut', 3)" >/dev/null
before_archive="$(sql_source 'SELECT archived_count FROM pg_stat_archiver')"
sql_source 'SELECT pg_switch_wal()' >/dev/null
for ((i=0;i<90;i++)); do
  after_archive="$(sql_source 'SELECT archived_count FROM pg_stat_archiver')"
  if [[ "$after_archive" =~ ^[0-9]+$ && "$before_archive" =~ ^[0-9]+$ ]] && \
     (( after_archive > before_archive )); then break; fi
  sleep 1
done
[[ "$after_archive" =~ ^[0-9]+$ && "$before_archive" =~ ^[0-9]+$ ]] && \
  (( after_archive > before_archive )) || deny 'WAL archive did not advance'

# Simulate losing the source service. Do not delete its data or run SQL DROP.
docker stop --time 10 "$SOURCE" >/dev/null

# Restore into a DISTINCT data volume and internal network, with archived WAL
# mounted read-only. The base backup lives in the restore volume only.
docker run --rm --network none --user postgres \
  --mount "type=volume,src=$BACKUP,dst=/pgdata" \
  postgres:16 sh -ec '
    touch /pgdata/recovery.signal
    cat >> /pgdata/postgresql.auto.conf <<EOF
restore_command = '\''cp /wal/%f %p'\''
recovery_target_name = '\''seg_rec_03_cut'\''
recovery_target_action = '\''promote'\''
EOF
  '

restore_start="$(date +%s)"
docker run -d --name "$TARGET" --network "$REST_NET" \
  --mount "type=volume,src=$BACKUP,dst=/var/lib/postgresql/data" \
  --mount "type=volume,src=$ARCHIVE,dst=/wal,readonly" \
  postgres:16 -c listen_addresses='' -c archive_mode=off >/dev/null
created_target=true
wait_ready "$TARGET"

# Force proof of point-in-time recovery: two rows before the named cut,
# NO post-cut row; also verify target has exited recovery and source stopped.
result="$(sql_target 'SELECT string_agg(event, '\''|'\'' ORDER BY phase) FROM seg_rec_03_proof')"
[[ "$result" == 'before_backup|before_cut' ]] || deny "wrong restored dataset: $result"
[[ "$(sql_target 'SELECT pg_is_in_recovery()')" == 'f' ]] || deny 'target not promoted after PITR'
[[ "$(sql_target "SELECT count(*) FROM seg_rec_03_proof WHERE event='after_cut'")" == '0' ]] || deny 'post-cut row leaked into recovery'
[[ "$(docker inspect -f '{{.State.Running}}' "$SOURCE")" == 'false' ]] || deny 'original service still running'

elapsed=$(( $(date +%s) - restore_start ))
printf 'SEG-REC-03 PASS: physically restored 2 synthetic rows to named WAL cut; post-cut row absent; new cluster promoted.\n'
printf 'SEG-REC-03 restore-container-start-to-validation=%ds (CI-only; NOT RTO/SLA).\n' "$elapsed"
