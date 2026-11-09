# CRM Imagine — SEC-CI-02 — Guard de PostgreSQL efêmero para PR e push

**Data:** 09/11/2026. **Estado:** desenvolvimento isolado na DEV. **Não** é auditoria independente, garantia de produção ou liberação de migrations novas.

## Problema investigado

A [Issue #27 — SEGDBA-06](https://github.com/rafhael100/imagine-crm/issues/27) apontava incompatibilidade do preflight [PR #26](https://github.com/rafhael100/imagine-crm/pull/26) com os eventos `push` e `pull_request`. Além disso, o PR #26 inclui alterações no isolamento de migrações e tem base antiga, não sendo seguro integrá-lo indiscriminadamente.

**SEC-CI-02 é uma implementação independente, baseada na HEAD atual da DEV, sem trazer migrações do PR #23 ou mudanças de esquema do #26.**

## Entrega delimitada

- `scripts/ci/verify-ephemeral-postgres.php`: preflight que **não executa DDL, escrita, migrations ou comandos destrutivos**. Valida antes de qualquer banco/rede: CI GitHub Actions, repositório exato, runner hospedado no GitHub, APP_ENV/testing, driver/host/porta/banco/usuário literais, credencial presente, ausência de opções libpq e URLs alternativas.
- **Eventos permitidos apenas**: `push` para `refs/heads/dev/fast-track-crm-2026-11-04` ou `pull_request` com base `main` ou `dev/fast-track-crm-2026-11-04` e ref no formato `refs/pull/N/merge`. Outros eventos, outras refs e fork com `GITHUB_REPOSITORY` diferente falham.
- `--static-only`: apenas para executar cenários negativos sintéticos e não conectar a banco.
- `--verify`: inspeciona exatamente um contêiner `postgres:16` em execução sem bind mounts, e compara `pg_control_system().system_identifier` consultado internamente e pela conexão loopback `127.0.0.1:5432` do job. Consulta **read-only** antes da primeira migration.
- A CI [`ci-postgresql-rls.yml`](../../.github/workflows/ci-postgresql-rls.yml) invoca **`--verify` antes das três migrations que já existiam**: normal, auth experimental e async experimental. Nenhuma migration extra.
- `tests/Unit/CiEphemeralPreflightV2Test.php`: matriz de eventos válidos e negativos, ambientes indevidos, credenciais/DSN, tentativa de modo destrutivo, e verificação estática de preflight antes de **cada** migration.

## Garantias que este guard NÃO oferece

- O comando é executado no runner do GitHub Actions e confia na plataforma/escopo do workflow; se um ator puder modificar workflow ou executar código arbitrário no mesmo runner, também poderá contornar o guard. **Não é uma barreira de confiança contra alteração maliciosa do próprio job**.
- Um runner hospedado não é, sozinho, prova de descarte de volumes ou ausência de proxy/rede extraordinária. A comparação de cluster com o contêiner reduz acidente de destino, não elimina todas as possibilidades.
- A conta `postgres` no CI continua superusuário dentro do serviço **efêmero**. NÃO transportar essa configuração para VPS/produção; uso de owner/role runtime requer revisão separada.
- O preflight não intercepta `migrate:fresh`, `db:wipe`, `DROP` ou scripts de execução não protegidos; proteger conta, papéis, pipeline e ambiente **fora da migration** é exigência SEGDBA-01 que permanece aberta.
- A correção do gatilho de `push` não significa aprovação de releases ou direitos reais. O revisor responsável ainda deve avaliar consistência, ameaça e fluxo de branch protegida.

## Critérios antes de integrar à DEV

1. Diferença exata entre 4 arquivos: script standalone, testes, workflow existente, documentação; **sem migração/schema/código de produto**.
2. CI Laravel/PHP e PostgreSQL/RLS verdes na SHA exata do PR, incluindo testes negativos e execução real `--verify` nas três etapas.
3. A CI em `push` na DEV após merge deve permanecer verde (esse cenário não é substituído por PR verde).
4. Nenhuma alteração de `main`, VPS, produção, cliente, segredo, `ALLOW`, permissão real ou gasto.
5. [PR #23](https://github.com/rafhael100/imagine-crm/pull/23), [PR #26](https://github.com/rafhael100/imagine-crm/pull/26) e [PR #49](https://github.com/rafhael100/imagine-crm/pull/49) seguem sem merge; **não fechar SEGDBA-01..07** com base na execução deste preflight.

**Gates abertos:** 67 CORE, SEG/DBA Issue #27, AUT/INT/LGPD, migrações operacionais e produção.
