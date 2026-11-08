# CRM Imagine — Revisão técnica de PR #23: migrações e risco de comandos destrutivos

**Data:** 08/11/2026 (America/Sao_Paulo)
**Natureza:** revisão estática técnica, de autoria/execução interna; **não é auditoria independente SEG/DBA nem aceite de gate**.
**Resultado:** **CONTINUAR DRAFT — NO MERGE**. Testes combinados PASS no PR #25, mas controles de execução destrutiva e de isolamento de infraestrutura permanecem abertos.

## 1. Versões efetivamente revisadas

| Item | SHA / estado |
|---|---|
| Base DEV | `dev/fast-track-crm-2026-11-04`, `8c9683acfd8decf98544402acda360d7e0f0a489` |
| [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) | `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558` — OPEN/DRAFT, sem merge |
| [PR #24](https://github.com/rafhael100/imagine-crm/pull/24) | `072f369542d0667056627f60f1f12d07173d883b` — OPEN/DRAFT, sem merge |
| [PR #25](https://github.com/rafhael100/imagine-crm/pull/25) | `ffefef6d5de1ecc1cdf7b863bd3bb2d8ad35e7d7` — veículo de CI combinado OPEN/DRAFT/NO MERGE |

**CI combinada, sem alterações de código:** [F0 run 42278565966, tentativa 2](https://github.com/rafhael100/imagine-crm/actions/runs/42278565966), `success`, 392 passed/68 skipped/2259 assertions; [PostgreSQL/RLS run 42278565995, tentativa 2](https://github.com/rafhael100/imagine-crm/actions/runs/42278565995), `success`, 459 passed/1 skipped/3076 assertions. As execuções representam apenas laboratório sintético. Não é homologação de dados reais/ambiente persistente.

Arquivos inspecionados: diff integral do PR #23, migration `database/experimental-migrations/tenant/2026_11_04_000001_create_crm_identity_organizations_memberships.php`, `config/identity.php`, `config/database.php`, `tests/Feature/AuthMigrationIsolationTest.php`, CI `.github/workflows/ci-postgresql-rls.yml`, PR #24 e documentos R10-A12/A13 já ratificados.

## 2. Achados e prioridade

| Achado | Prioridade | Evidência | Consequência / decisão |
|---|---|---|---|
| **R-01 — Comandos destrutivos não passam por `Migration::guard()`** | **P0 operacional** | `guard()` é chamado somente em `up()` e `down()`. A guarda não intercepta `migrate:fresh`, `db:wipe`, limpeza SQL direta, DROP ou decisões do orquestrador. | O pipeline e as credenciais devem impedir a execução em bancos persistentes. Proibir `migrate:fresh`/`db:wipe` fora de contêiner isolado e descartável. **Não executar esses comandos neste recorte.** |
| **R-02 — Loopback + nome não provam banco efêmero** | **P0 operacional** | Verifica host `127.0.0.1`/`localhost`/`::1`, regex `imagine_crm_*`, `current_database()`; túnel, proxy ou PostgreSQL local durável continuam possíveis. | Exigir isolamento de contêiner/rede, lifecycle efêmero, credenciais exclusivas e ACL para DB/schema, evidências de provisionamento e destruição controlada. |
| **R-03 — Papel de migração ainda usa credencial administrativa de CI** | **P1 técnico** | Workflow PostgreSQL cria serviço temporário `postgres:16`, `POSTGRES_USER=postgres` e executa migrações com DB username `postgres`; credenciais de aplicação/roles de runtime são separadas apenas depois. | Apropriado somente para CI efêmera sem dados reais. Documentar escopo de superusuário *restrito ao serviço descartável* e proibir reaproveitar esta credencial em ambiente persistente. |
| **R-04 — Compatibilidade de migration ledger parcial** | **P1 técnico** | Teste `test_recorded_legacy_tenant_migration_is_not_rediscovered_or_double_applied()` usa schema efêmero e basename antigo. Não cobre drift persistente, tabelas parciais, upgrades interrompidos e atualização real. | Produzir matriz de estados + mecanismo de reconciliação sob validação SEG/DBA; não aplicar em base existente sem inventário, backup e ato próprio. |
| **R-05 — Proteção RLS é defesa adicional, não identidade** | **P1 de segurança** | `crm_memberships` tem `ENABLE`/`FORCE ROW LEVEL SECURITY`, mas escopo baseado em GUC `app.tenant_id` é mutável por conexão. | Manter validação de identidade/sessão/membership do backend, roles sem BYPASSRLS, contexto transacional e limpeza de GUC nos testes e runtime. Não confundir isolação por GUC com autenticação. |
| **R-06 — Prova S01 é apenas negativa** | **P1 de contrato** | `organization.self/read_self` ainda não existe no catálogo experimental; a negação `CONTRACT_INVALID` não comprova política positiva de leitura. Cenário de mesma identidade com membership em dois tenants não incluído. | Manter S01 negativa e pendência futura específica, sem permitir `ALLOW`, endpoints de leitura ou dados comerciais. |
| **R-07 — Independência ausente** | **GATE humano** | Pareceres anteriores e este relatório são internos/assistidos, sem SEG/DBA independente identificado. | Revisão competente e segregada, quando exigida, continua explicitamente pendente. |

## 3. Mitigações já demonstradas e seus limites

1. Migration de tenant movida para `database/experimental-migrations/tenant/`, fora da descoberta normal Laravel. Testes e workflow comprovam comportamento no laboratório.
2. Guarda de `up/down` verifica flag, ambiente, driver PostgreSQL, host loopback e banco consultado por `SELECT current_database()`. Rejeita parte importante das chamadas acidentais; **não valida fisicamente que o banco é descartável**.
3. No CI, banco `imagine_crm_ci` do service PostgreSQL 16 e service Redis 7 são descartáveis; CI passou no SHA combinado. A rotina `php artisan migrate --force` não instala a migration tenant até o passo opt-in explícito.
4. RLS da tabela `crm_memberships` é habilitado e forçado; os testes em PostgreSQL e as demais suites passaram. Isso não cobre todas as futuras operações comerciais nem torna seguro conectar runner a dados reais.
5. PRs #23/#24 seguem como branches independentes; o PR #25 **não pode ser integrado** como atalho para eles.

## 4. Recorte mínimo de implementação proposto — exige novo GO explícito

**Nome sugerido:** `CRM-SEC-CI-01 — Defesa em profundidade para execução de banco efêmero`.

**Não implementado nem autorizado por este documento.** Se aprovado, criar branch filha DEV isolada e **PR DRAFT**, preservar #23/#24/#25 sem modificação e executar testes somente sintéticos. Escopo técnico proposto:

- CI de pré-validação estática: exigir alvo/schema esperado, checar comandos e scripts destrutivos em workflow/deploy; impedir que comandos conhecidos de wipe/refresh sejam executados em jobs de migração normal. Reconhecer que scanner textual não substitui permissões do banco.
- Definir perfil exclusivo de runner efêmero: PostgreSQL iniciado apenas no job com data volume descartável, sem host persistente, sem tunnel/DB externo, segredos de produção ou rede de clientes, e variáveis de testing fixadas pelo workflow. Evidência verificável do endpoint/papel ativo antes de qualquer migração.
- Separar credenciais de DDL das de runtime: DDL superusuário apenas no serviço temporário do CI, jamais no código de aplicação e nunca apontando para VPS persistente. Negar DROP/CREATE na role de aplicação em bancos duráveis por grants/ownership adequados, com revisão DBA.
- Testes negativos **não destrutivos** para combinação de ambiente/flag/driver/host/DB/DSN indevidos; verificar que nenhum statement DDL é emitido antes da rejeição. Para provar comportamento do comando destrutivo, primeiro definir contêiner descartável dedicado e obter autorização de teste separada.
- Preparar runbook de identificação de banco e intervenção manual: `SELECT current_database()`, `inet_server_addr()`, identidade da role, política de backup; esses dados são *indícios*, não garantia de descartabilidade.
- Política de migration ledger: registrar casos (nova instalação, basename já registrado, schema parcial, drift, falha de deploy, rollback). Testes reais de upgrade devem usar cópias **anonimizadas e isoladas somente com GO próprio**.

### Critérios para um eventual GO de integração na DEV (não concedido)

- CI verde **na HEAD exata dos PRs individuais que se pretendem integrar**, e CI integrado quando composição relevante alterar comportamento.
- Parecer SEG/DBA competente e independente quando exigido, documentando P0/P1 aceitos ou mitigados; owner dos controles e procedimento de recuperação.
- Fronteira de testes/CI versus banco persistente validada, nenhum segredo ou conexão real de cliente presente.
- Autorização separada da Direção para cada merge na DEV. **Nunca** merge do PR #25 como substituto, nem merge/main/VPS/deploy automático.

## 5. Situação decisória

**Decisão técnica interna desta revisão: CONTINUAR DRAFT** e apresentar o recorte `CRM-SEC-CI-01` para autorização específica de programação sintética, caso a Direção deseje avançar. Este documento não concede esse GO e **não é uma nova rodada de aprovação das diretrizes preliminares anteriores**.

**Estado preservado:** C01–C08, 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01–G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates **ABERTOS**. Sem `ALLOW`, SQL persistente, mudanças de schema real, merge, mudança de `main`, implantação na VPS ou acesso a clientes.
