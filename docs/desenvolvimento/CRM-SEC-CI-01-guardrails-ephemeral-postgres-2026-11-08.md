# CRM-SEC-CI-01 — Proteção adicional para laboratório PostgreSQL de CI

**Data:** 08/11/2026. **Natureza:** incremento autorizado de programação **somente** em branch filha isolada, PR DRAFT, testes sintéticos. **Nenhum GO de merge, VPS, `main`, produção, DDL persistente ou ALLOW.**

## 1. Escopo e genealogia

- Parent da branch: [PR #23](https://github.com/rafhael100/imagine-crm/pull/23), `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558` (migração tenant isolada).
- Branch deste recorte: `dev/sec-ci-01-ephemeral-guardrails-2026-11-08`.
- Destino do PR DRAFT: `dev/fast-track-crm-2026-11-04` exclusivamente para execução do CI e avaliação. **Não efetuar merge**. O diff em relação à DEV inclui a base experimental do #23; não confundir com licença de integração.
- PRs #23/#24/#25 originais, `main`, DEV original, VPS, infra de clientes e política de autorização permanecem intactos.

## 2. Código do recorte

1. `scripts/ci/verify-ephemeral-postgres.php`: utilitário CLI fail-closed. Primeiro verifica exclusivamente variáveis de ambiente e DSN (nenhuma conexão aberta antes disso); exige GitHub Actions no repositório correto, evento pull_request, runner GitHub-hosted, `APP_ENV=testing`, `pgsql`, host loopback 127.0.0.1, porta 5432, nome `imagine_crm_ci` e perfil administrativo estritamente do CI. Bloqueia overrides `DB_URL`, `DATABASE_URL`, `PGHOST`, `PGSERVICE`, `PGSERVICEFILE`.
2. No modo padrão `--verify`, exige contêiner PostgreSQL 16 único, ativo, com imagem exata `postgres:16`, sem montagens bind e com IP de rede identificado; executa **exclusivamente SELECT** via `psql` e compara `current_database`, `current_user`, `inet_server_addr()` com a imagem do contêiner. Rejeita divergências. Modo `--static-only` é **apenas unidade sintética** e não prova alvo do banco.
3. `.github/workflows/ci-postgresql-rls.yml`: invoca `--verify` imediatamente antes de cada passo de migração (padrão, tenant, auth, async), sem migrar fora do ambiente já configurado. CI mantém banco e Redis como services descartáveis existentes, não adiciona segredos nem bancos persistentes.
4. `tests/Unit/CiEphemeralPreflightTest.php`: teste positivo estritamente estático, 17 cenários negativos de configuração, negação de operação desconhecida, e teste de que o YAML executa preflight antes das migrações sem conter `migrate:fresh`/`db:wipe`/`migrate:refresh`. São **testes sem SQL**; `--verify` real requer os containers do workflow PostgreSQL.

## 3. Limitações explícitas e ameaças residuais

- O preflight **não protege bancos reais** contra `migrate:fresh`, `db:wipe`, `DROP`, mudanças no workflow por colaboradores maliciosos, execução direta do Laravel fora deste CI, nem túnel ou credencial indevida fora dos parâmetros inspecionados. Não instalar/aplicar este script como autorização de DDL na VPS.
- Ele é uma **barreira adicional de CI**, não prova criptográfica de efemeridade nem substituto de isolamento por infraestrutura, permissões, dono de dados, backups, revisão SEG/DBA independente ou bloqueio de comandos destrutivos em ambientes persistentes.
- O usuário `postgres` superuser é aceitável **somente no serviço PostgreSQL descartável do CI** para setup, nunca como credencial de runtime/cliente. App/IAM/async possuem roles com escopo restrito para os testes.
- `CI` verde valida somente as hipóteses testadas na HEAD exata; não homologa migração para DB real, RLS comercial, login, `read_self`, LGPD, merges ou implantação.
- Workflow executado de repositório público: código fornecido em PR pode ser não confiável. Não disponibilizar segredos reais nem runners próprios com acesso a produção.

## 4. Validação exigida

- `ci-f0.yml` deverá executar os testes unitários negativos (sem Docker/PostgreSQL).
- `ci-postgresql-rls.yml` deverá passar pelas **quatro** verificações do serviço ativo, depois por todas as migrações de laboratório e suíte RLS.
- Em falha, inspecionar logs; corrigir **somente a branch deste recorte**, repetir e registrar evidências dos SHAs e runs.
- Os cenários de comandos destrutivos **não serão executados** nesta atividade. Testes de `migrate:fresh` ou `db:wipe` exigem GO distinto e contêiner efêmero inequivocamente dedicado.

**Governança:** recorte técnico aprovado para código/testes sintéticos, não integração na DEV. PR DRAFT, review competente/independente SEG/DBA ainda pendente. Gates C01..C08, 67 CORE, SEG-01/02, AUT, INT, LGPD, FIN/CON e DEV-GATE permanecem **abertos**.
