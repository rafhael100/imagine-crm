# CRM SEGDBA F3-207 — testes de regressão do SEC-CI-02 em modo estático

**Data:** 13/11/2026. **Escopo:** [issue #27](https://github.com/rafhael100/imagine-crm/issues/27), item SEGDBA-06 (compatibilidade entre `push` e `pull_request`). **Status:** evidência de comportamento do script de guarda, não homologação de segurança de bancos persistentes.

## Implementação

Novo `tests/Unit/EphemeralPostgresStaticPolicyMatrixTest.php` usa o **script de produção do próprio CI** `scripts/ci/verify-ephemeral-postgres.php` através de `Symfony\Component\Process\Process`, passando **somente `--static-only`**. O caso positivo exige que o processo diga explicitamente `database NOT verified`; não executa Docker, `psql`, migrações, DDL, credenciais reais ou operações de rede. O `PATH` do processo filho é inválido de propósito, garantindo que comandos externos não sejam necessários para o caminho estático.

### Casos positivos do gatilho

- Evento `pull_request`, merge ref `refs/pull/123/merge`, base **DEV** exata;
- Evento `pull_request`, mesmo merge ref, base **main**;
- Evento `push`, ref exata `refs/heads/dev/fast-track-crm-2026-11-04` e sem base PR.

Esses caminhos são os suportados no código SEC-CI-02 atual. Sucesso em `--static-only` **não** implica que a base de dados seja descartável nem autoriza migrations reais. O caminho `--verify` da própria CI permanece responsável por comparar a identidade do contêiner PostgreSQL e a conexão local.

### Casos negativos

Negar eventuais `push` em `main`, outras branches, tags e `push` com `GITHUB_BASE_REF`; `pull_request_target`, `schedule`, `workflow_dispatch`; refs `head`, sem sufixo `merge`, número PR inválido, destino não aprovado; fork, runner auto-hospedado, `CI=false`, `GITHUB_ACTIONS=false`; `production`, SQLite, outro host/porta/banco/usuário, senha ausente/divergente; alternativas `DB_URL`, `DATABASE_URL`, `PGHOST`, `PGHOSTADDR`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGSERVICE`, `PGSERVICEFILE`, `PGOPTIONS`, `PGSSLMODE` e `PGPASSFILE`; opção desconhecida `--migrate`.

O programa deve sair com **código 42** nos negativos, sem declarar sucesso e sem ecoar o valor de senha de teste. Os dados em ambiente são **inteiramente sintéticos e localizados no processo filho**, sem qualquer conexão real.

## Limites

- Esses testes não provam ciclo de vida, rede, mounts, storage ou ausência de proxy/túnel do PostgreSQL. Isso continua no `--verify` do workflow e ainda demanda revisão independente SEG/DBA.
- Verificam eventos e alvos **estritamente permitidos no código atual**, não toda a segurança operacional.
- Não alteram preflight, trigger do workflow, migrations, IAM, permissões, `main`, VPS ou produção; apenas arquivo de teste e documentação.
- Não encerram SEGDBA-01..07 nem revisão das PRs #23 e #26. A issue #27 e a #259 continuam NO-GO.

**Merge DEV somente se F0, PostgreSQL/RLS, Sites e Agentes passarem na HEAD exata.**
