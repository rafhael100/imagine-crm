# CRM-SEC-CI-02 — Compatibilidade estrita dos eventos de CI PostgreSQL

**Data:** 08/11/2026. **Natureza:** recorte específico de código/testes sintéticos aprovado pela Direção após issue #27. **Não** é GO de merge, DBA/SEG externo, produção, SQL persistente, VPS ou `ALLOW`.

## 1. Problema identificado

O workflow `.github/workflows/ci-postgresql-rls.yml` da branch `dev/fast-track-crm-2026-11-04` aceita `pull_request` contra `main` ou DEV **e** `push` direto na DEV. Contudo, o verificador `scripts/ci/verify-ephemeral-postgres.php` no [PR #26](https://github.com/rafhael100/imagine-crm/pull/26) exigia exclusivamente `GITHUB_EVENT_NAME=pull_request`: após uma integração hipotética, o workflow de `push` não conseguiria migrar no laboratório e falharia com código 42.

## 2. Implementação isolada

**Branch filha da HEAD do PR #26:** `dev/sec-ci-02-event-boundary-2026-11-08`, base ancestral `86ec918036e895f99cd6d1d98ca6469021846d2c`. **PR DRAFT direcionado à DEV apenas para CI; nunca fazer merge neste recorte.**

Mudanças restritas:

1. Em `scripts/ci/verify-ephemeral-postgres.php`, retirar condição rígida `GITHUB_EVENT_NAME=pull_request` da tabela simples e introduzir uma matriz fechada e explícita de evento/ref:
   - `pull_request`: `GITHUB_REF` obrigatório no formato `refs/pull/<número positivo>/merge`; `GITHUB_BASE_REF` somente `main` ou `dev/fast-track-crm-2026-11-04`; `GITHUB_HEAD_REF` não vazio.
   - `push`: **somente** `GITHUB_REF=refs/heads/dev/fast-track-crm-2026-11-04`; `GITHUB_BASE_REF` e `GITHUB_HEAD_REF` vazios/ausentes.
   - Qualquer outro evento/ref/target, tags, `workflow_dispatch`, `schedule`, `pull_request_target`, `push` na `main`, PR com target não autorizado: **deny por exit 42 antes de qualquer consulta ao banco**.
2. `tests/Unit/CiEphemeralPreflightTest.php`: perfis sintéticos positivos PR em DEV, PR em `main` e `push` na DEV; cenários negativos para variantes não autorizadas; verificação dos triggers do YAML. Nada executa `migrate:fresh`, `db:wipe`, `migrate:refresh` ou `DROP`.
3. Workflow PostgreSQL mantém triggers existentes (sem ampliação), `permissions: contents: read`, serviço descartável e quatro verificações read-only antes das migrações.

## 3. O que NÃO foi alterado

Permanece obrigatório: `CI=true`, `GITHUB_ACTIONS=true`, repositório `rafhael100/imagine-crm`, `RUNNER_ENVIRONMENT=github-hosted`, `APP_ENV=testing`, conexão `pgsql` em `127.0.0.1:5432`, DB `imagine_crm_ci`, role `postgres` exclusivamente no contêiner efêmero, proibição de DSNs alternativos, identidade do cluster por `pg_control_system()`, verificação do contêiner único PostgreSQL 16 e bloqueio de bind mounts.

O verificador **não substitui** separação operacional de credenciais, segregação de redes, isolamento real da VPS, backups e DBA/SEG independente. O usuário `postgres` é exclusivo de CI descartável; nunca utilizar em banco real.

## 4. Evidência e limitações da validação

- Os testes de `push` na DEV são **simulações unitárias do contexto do GitHub**; não será feito `push` à DEV original sem autorização independente. A CI deste PR real executa o evento `pull_request`; isso prova este fluxo e as simulações, **não constitui prova de integração ou execução real pós-merge na DEV**.
- `CI` verde valida a HEAD do PR para os cenários testados, sem encerrar risco P0 de comandos destrutivos fora deste runner e sem substituir parecer SEG/DBA.
- [Issue #27 — Revisão independente SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27) segue aberta; não atribuir parecer independente à mesma autoria.

## 5. Gates e precedência

PR #23 e PR #26 ainda não foram aprovados para integração, e esta branch filha contém o diff acumulado dos dois. PRs #23/#24/#25/#26, PRs #2/#21, DEV, `main` e VPS permanecem inalterados. **NÃO FAZER MERGE** deste PR como atalho; revisão técnica independente e autorização específica devem preceder qualquer integração. Gates CORE, SEG-01/02, AUT, INT, LGPD, FIN/CON e DEV-GATE permanecem **abertos**.
