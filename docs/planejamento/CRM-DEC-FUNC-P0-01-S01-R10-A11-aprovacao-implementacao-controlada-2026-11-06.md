# CRM-DEC — FUNC-P0-01/S01-R10-A11 — Autorização específica de implementação controlada da proteção de migrations

**Data da manifestação:** 06/11/2026, America/Sao_Paulo.

## 1. Manifestação e âmbito

A Direção de Produto respondeu **“aprovado”** à pergunta expressa: “Você aprova a alternativa A de R10-A11 como diretriz técnica documental e autoriza especificamente o Codex a implementar essa proteção em uma branch filha da DEV, executar somente testes sintéticos e abrir um PR em rascunho, sem merge, sem VPS e sem alterações na main?”

A aprovação é **GO restrito ao recorte de segurança LAC-C03-01** do briefing [R10-A11](CRM-FUNC-P0-01-S01-R10-A11-briefing-codex-seguranca-migrations-proposta-v0.1-2026-11-06.md): isolar migration candidata do caminho padrão, exigir opt-in/ambiente para up/down, adaptar CI e testes sintéticos. Não equivale a permissão de funcionalidade comercial, concessão positiva de ACL ou liberação de todos os CORE.

## 2. Execução efetivamente realizada

- **Canal real de execução:** operações de código realizadas pelo assistente com ferramentas GitHub disponíveis; **não houve execução do agente Codex**, indisponível como executor nesta sessão. A autorização nomeava Codex como executor pretendido; o trabalho de código foi concluído e atribuído com transparência ao assistente/GitHub, submetido a PR de revisão, sem merge.
- Branch filha criada exclusivamente para o recorte: `dev/f1-11-migration-isolation-2026-11-06`, baseada na DEV SHA `8c9683acfd8decf98544402acda360d7e0f0a489`.
- Commit: `de922d6116c4b629c27a43123093af79f3b2c033`.
- [PR #23 — DRAFT, base DEV, sem merge](https://github.com/rafhael100/imagine-crm/pull/23).
- Código: migration de tenant movida de `database/migrations/` para `database/experimental-migrations/tenant/`, guarda `identity.tenant_schema_experimental` e ambiente `local/testing` em `up`/`down`; config `.env.example` e `config/identity.php`; pipeline `ci-postgresql-rls.yml`; `AuthMigrationIsolationTest.php` com testes de negativa e descoberta.
- **CI:** dois workflows sintéticos disparados pelo PR #23 — F0 CI e PostgreSQL RLS F1; resultado observado inicialmente **in_progress**, não declarar sucesso sem consultar HEAD/checks atualizados.

## 3. Não regressão e ressalvas

- A migration principal tinha caminho padrão antes da correção e um teste de release esperava instalar `crm_memberships` mesmo em simulação de produção. O recorte atual explicita migração de tenant opt-in antes do IAM no laboratório. Validar nos workflows; sem testes manuais ou dados reais.
- O controle por flag e ambiente não torna um banco real automaticamente seguro: exigir segregação de credenciais, revisão de role PostgreSQL, pipeline de deploy e aprovação formal de infraestrutura/SEG/DBA antes de produção.
- A política `DenyByDefaultAuthorizer` não foi alterada. Nada de `ALLOW`, `GRANT` comercial, rotas públicas, usuários de clientes ou acesso real, implantação, VPS, alterações em `main`, PRs #2/#21 ou merge DEV.

## 4. Gates e estado

**Todos os contratos C01..C08 PENDENTES. 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais bloqueios ABERTOS.** Este é GO pontual de laboratório, não fechamento de gate nem aprovação de produção. A análise e o futuro merge do PR #23 exigem decisão específica após CI e revisão, não são automáticos.

## 5. Atualização posterior ao registro inicial — correções de regressão sintética e CI
- Após o commit inicial `de922d61`, dois testes herdados esperavam comportamento antigo do caminho padrão de migrations: `FastTrackSecurityBaselineTest` contava 3 em vez de 4 migrations experimentais; `PostgresAsyncLedgerTest` esperava `crm_memberships` após um `migrate` padrão em schema de laboratório.
- A branch filha recebeu correções de teste exclusivamente no mesmo recorte: `b89709058dba30304a911b0f14a3c2cfff40edec` e `cb0c1791290e570c46bebb6dd88d704fd0e75617`. A expectativa passou a ser **não instalar tenant experimental** via descoberta de release.
- **HEAD do PR #23:** `cb0c1791290e570c46bebb6dd88d704fd0e75617`.
- [Workflow F0/SQLite](https://github.com/rafhael100/imagine-crm/actions/runs/42248524419): `completed/success` na HEAD atual.
- [Workflow PostgreSQL 16/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/42248524416): `completed/success` na HEAD atual.
- Revisão de SEG/DBA, autorização expressa de merge e validação por competência **permanecem pendentes**. PR continua **DRAFT**, sem merge.
