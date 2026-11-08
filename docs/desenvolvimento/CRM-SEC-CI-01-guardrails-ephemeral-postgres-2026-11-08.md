# CRM-SEC-CI-01 — Proteção adicional para laboratório PostgreSQL de CI

**Data:** 08/11/2026. **Natureza:** incremento autorizado de programação **somente** em branch filha isolada, PR DRAFT, testes sintéticos. **Nenhum GO de merge, VPS, `main`, produção, DDL persistente ou ALLOW.**

## 1. Escopo e genealogia

- Parent da branch: [PR #23](https://github.com/rafhael100/imagine-crm/pull/23), `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558` (migração tenant isolada).
- Branch deste recorte: `dev/sec-ci-01-ephemeral-guardrails-2026-11-08`.
- Destino do PR DRAFT: `dev/fast-track-crm-2026-11-04` exclusivamente para execução do CI e avaliação. **Não efetuar merge**. O diff em relação à DEV inclui a base experimental do #23; não confundir com licença de integração.
- PRs #23/#24/#25 originais, `main`, DEV original, VPS, infra de clientes e política de autorização permanecem intactos.

## 2. Código do recorte

1. `scripts/ci/verify-ephemeral-postgres.php`: utilitário CLI fail-closed. Primeiro verifica exclusivamente variáveis de ambiente e DSN (nenhuma conexão aberta antes disso); exige GitHub Actions no repositório correto, evento pull_request, runner GitHub-hosted, `APP_ENV=testing`, `pgsql`, host loopback 127.0.0.1, porta 5432, nome `imagine_crm_ci` e perfil administrativo estritamente do CI. Bloqueia overrides `DB_URL`, `DATABASE_URL`, `PGHOST`, `PGSERVICE`, `PGSERVICEFILE`.
2. No modo padrão `--verify`, exige contêiner PostgreSQL 16 único, ativo, com imagem exata `postgres:16`, sem montagens bind e com IP de rede identificado; executa **exclusivamente SELECT** via `psql` em duas conexões (dentro do contêiner Docker e pelo endpoint 127.0.0.1), compara `current_database`, `current_user` e o `system_identifier` do cluster obtido por `pg_control_system()`. Rejeita divergências. Não compara IP de rede com `inet_server_addr()` porque o NAT do runner altera esse endereço. Modo `--static-only` é **apenas unidade sintética** e não prova alvo do banco.
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


## 5. Evidências de execução — 08/11/2026

- **Execução inicial (SHA `0005ec5e5fd459e3b244870cfe63f7137d13c97c`):** F0 [42495962455](https://github.com/rafhael100/imagine-crm/actions/runs/42495962455) **SUCCESS**; PostgreSQL [42495962423](https://github.com/rafhael100/imagine-crm/actions/runs/42495962423) **FAIL** no primeiro preflight com `CRM-SEC-CI-01 DENIED: connected PostgreSQL endpoint does not match the disposable CI service`. O guard negou a migração; nenhum migration step posterior ocorreu. Causa técnica: `inet_server_addr()` refletia o endpoint pós-NAT, diferente do endereço retornado por `docker inspect`.
- **Correção exclusivamente na branch SEC-CI-01 (SHA `0d75b158138ba266a7e6a56f1aed4ba484a8c891`):** adotado identificador imutável de cluster `system_identifier` retornado por `pg_control_system()`, consultado em modo **read-only** pelo endpoint e diretamente em `docker exec` no contêiner identificado. Divergência continua fail-closed; verificação de runner, service container e de ausência de bind mounts preservadas.
- **Reexecução após correção:** F0 [42496269980](https://github.com/rafhael100/imagine-crm/actions/runs/42496269980) **SUCCESS**, **404 passed / 66 skipped / 2.275 assertions**; PostgreSQL [42496269950](https://github.com/rafhael100/imagine-crm/actions/runs/42496269950) **SUCCESS**, **469 passed / 1 skipped / 3.084 assertions**. Nos logs do PostgreSQL aparecem **quatro** mensagens de preflight read-only verificado, uma antes de cada etapa de migration.
- Avisos não bloqueantes do runner: `actions/checkout@v4` usa runtime Node 20 em descontinuação e Redis em laboratório emite aviso `vm.overcommit_memory`. Não atribuir conclusão de segurança a esses avisos.
- A revalidação de CI acima é válida para a **HEAD de código** indicada. Qualquer commit documental posterior deve ter sua própria HEAD e checks atualizados antes de consideração de integração.

**Estado do recorte:** implementação experimental com CI aprovada em laboratório; risco P0 de comandos destrutivos fora do CI e revisão SEG/DBA independente permanecem **abertos**. Não fazer merge, produção, VPS ou mudança em `main` sem ato posterior.
