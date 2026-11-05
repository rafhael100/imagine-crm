# CRM Imagine — STATUS ATUAL

**Atualizado em:** 05/11/2026 (America/Sao_Paulo).  
**Base:** GitHub Issues, PRs, commits e Actions consultados na data.  
**Natureza:** registro operacional datado, não aprovação de gates, homologação de segurança nem autorização de produção.

## 1. Situação resumida

- **Fase:** desenvolvimento técnico controlado e experimental com Laravel 13, PostgreSQL 16 e Redis em CI, sempre com fixtures sintéticas, sem autorização de negócio.
- **Branch de trabalho:** [`dev/fast-track-crm-2026-11-04`](https://github.com/rafhael100/imagine-crm/tree/dev/fast-track-crm-2026-11-04). **F1-02, F1-03, F1-04, F1-05, F1-06, F1-07, F1-08 e F1-09 integradas somente na DEV**.
- **Último merge:** [PR #18 — F1-09](https://github.com/rafhael100/imagine-crm/pull/18) fechado como MERGED, commit `af4cd127655572f9ce7327b4f52dca36a5e0a864`, autorização expressa para a DEV.
- **`main`:** permanece no commit verificado `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`. [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) continua **DRAFT** para `main`, sem merge.
- **Próxima tarefa:** [Issue #19 — F1-10 baseline de segurança fast-track e matriz de gates](https://github.com/rafhael100/imagine-crm/issues/19), preparada para Codex; sem execução desta fase.
- **VPS/produção:** não foi feito deploy nesta sequência. Não foram habilitados endpoints públicos, jobs, webhooks, automações de clientes, credenciais reais ou permissões de dados comerciais.

## 2. Entregas

| Etapa | Evidência | Estado |
| --- | --- | --- |
| F0-02 | [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) | DRAFT para `main`, não integrado |
| F1-01 | [Prova IAM/RLS](docs/desenvolvimento/F1-01-modelo-IAM-RLS-evidencias-CI-2026-11-04.md) | Prova experimental, sem homologação |
| F1-02 | [PR #4](https://github.com/rafhael100/imagine-crm/pull/4) | Integrada em DEV, `3ec4cfa0621140056eab274826a639aba125e0a6` |
| F1-03 | [PR #6](https://github.com/rafhael100/imagine-crm/pull/6) | Integrada em DEV, `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10` |
| F1-04 | [PR #8](https://github.com/rafhael100/imagine-crm/pull/8) | Integrada em DEV, `407a2ead654864cd457436080999736a1186e219` |
| F1-05 | [PR #10](https://github.com/rafhael100/imagine-crm/pull/10) | Integrada em DEV, `9cc5ae4214e07903b2b69d9d40541c61f395d4bf` |
| F1-06 | [PR #12](https://github.com/rafhael100/imagine-crm/pull/12) / [Issue #11](https://github.com/rafhael100/imagine-crm/issues/11) | Integrada em DEV, `691f8c0e508fabae5d3560cca044bf280d2fcfb5`. Contratos A–E de autoridade assíncrona inertes/deny-only |
| F1-07 | [PR #14](https://github.com/rafhael100/imagine-crm/pull/14) / [Issue #13](https://github.com/rafhael100/imagine-crm/issues/13) | Integrada exclusivamente na DEV, merge `71cecea7d53fd5f2608d4b0b96d685c696a144ed`; telemetria e reconciliação sanitizadas e inertes |
| F1-08 | [PR #16](https://github.com/rafhael100/imagine-crm/pull/16) / [Issue #15](https://github.com/rafhael100/imagine-crm/issues/15) | Integrada exclusivamente na DEV, merge `2923cc57afe240f8aa6024d7028a75bb8f8b54ef`; ledger PostgreSQL sintético e inbox/outbox inertes |
| F1-09 | [PR #18](https://github.com/rafhael100/imagine-crm/pull/18) / [Issue #17](https://github.com/rafhael100/imagine-crm/issues/17) | Integrada exclusivamente na DEV, merge `af4cd127655572f9ce7327b4f52dca36a5e0a864`; integridade, restauração sintética e reconciliação consultiva, sem efeitos |
| F1-10 | [Issue #19](https://github.com/rafhael100/imagine-crm/issues/19) | Preparada para Codex; baseline automatizada de não regressão e matriz de gates/avanço funcional, sem liberação produtiva |

## 3. Testes conferidos

**Antes do merge F1-06:** head `fd08a9212a43c01d9b8a66f1188a361e66b14774`.
- [Laravel/SQLite — run 42070845197](https://github.com/rafhael100/imagine-crm/actions/runs/42070845197): **307 aprovados, 33 ignorados, 1.014 asserções**, success.
- [PostgreSQL/Redis — run 42070845055](https://github.com/rafhael100/imagine-crm/actions/runs/42070845055): **339 aprovados, 1 ignorado, 1.233 asserções**, success.

**Depois do merge F1-06:** SHA `691f8c0e508fabae5d3560cca044bf280d2fcfb5`.
- [Laravel — run 42073457091](https://github.com/rafhael100/imagine-crm/actions/runs/42073457091): **success**.
- [PostgreSQL/RLS/Redis — run 42073457093](https://github.com/rafhael100/imagine-crm/actions/runs/42073457093): **success**.

**F1-07 antes do merge:** head `c4255fd728ff29f68a8ca3668eef8861e633134d`.
- [Laravel/SQLite — run 42089372399](https://github.com/rafhael100/imagine-crm/actions/runs/42089372399): **337 aprovados, 36 ignorados, 1.304 asserções**, success.
- [PostgreSQL/Redis — run 42089372691](https://github.com/rafhael100/imagine-crm/actions/runs/42089372691): **372 aprovados, 1 ignorado, 1.569 asserções**, success.

**F1-07 depois do merge:** SHA `71cecea7d53fd5f2608d4b0b96d685c696a144ed`.
- [Laravel — run 42092229368](https://github.com/rafhael100/imagine-crm/actions/runs/42092229368): **success**.
- [PostgreSQL/RLS/Redis — run 42092229274](https://github.com/rafhael100/imagine-crm/actions/runs/42092229274): **success**.

**F1-08 antes do merge:** SHA `49bd5b661ddee3fec434cd550530c07c1430e878`.
- [Laravel/SQLite run 42119017856](https://github.com/rafhael100/imagine-crm/actions/runs/42119017856): **340 aprovados, 49 ignorados, 1.366 asserções**, success.
- [PostgreSQL 16/Redis 7 run 42119018061](https://github.com/rafhael100/imagine-crm/actions/runs/42119018061): **388 aprovados, 1 ignorado, 1.827 asserções**, success.

**F1-08 pós-merge na DEV:** SHA `2923cc57afe240f8aa6024d7028a75bb8f8b54ef`.
- [Laravel run 42122175413](https://github.com/rafhael100/imagine-crm/actions/runs/42122175413): **success**.
- [PostgreSQL/RLS/Redis run 42122175636](https://github.com/rafhael100/imagine-crm/actions/runs/42122175636): **success**.

**F1-09 antes do merge:** head `6c8dd1dbe9b0c5ff78402ff7d186cae571825536`.
- [Laravel/SQLite — run 42126536166](https://github.com/rafhael100/imagine-crm/actions/runs/42126536166): **366 aprovados, 63 ignorados, 1.574 asserções**, success.
- [PostgreSQL 16/RLS/Redis 7 — run 42126536165](https://github.com/rafhael100/imagine-crm/actions/runs/42126536165): **428 aprovados, 1 ignorado, 2.250 asserções**, success.

**F1-09 depois do merge na DEV:** SHA `af4cd127655572f9ce7327b4f52dca36a5e0a864`.
- [Laravel — run 42130265061](https://github.com/rafhael100/imagine-crm/actions/runs/42130265061): **success**.
- [PostgreSQL/RLS/Redis — run 42130265271](https://github.com/rafhael100/imagine-crm/actions/runs/42130265271): **success**.

Novos commits de documentação disparam novos checks. Verificar `HEAD` e Actions atuais antes de abrir ou integrar PR. Suítes sintéticas não comprovam prontidão para produção.

## 4. Segurança e pendências preservadas

1. **`DenyByDefaultAuthorizer` continua a única autorização ativa**, sem política `ALLOW`. Contratos `AuthorizationIntent`, `AuthorizationEnvelope`, `AsyncAuthorityRequest` e `AsyncAuthorityBridge` são modelos de laboratório, não credenciais ou direitos transportáveis.
2. CORE01..CORE09, incluindo CORE02-D04..D10 e CORE03/CORE09-D04..D10, os **67 IDs técnicos CORE**, SEG-01/02, ARQ/INT/FIN/CON/LGPD permanecem abertos conforme decisões/aceites por módulo. D01..D03 de CORE03/09 são bases documentais aprovadas, não liberação técnica.
3. Um `tenant_id`/GUC `app.tenant_id` fornecido não autentica. RLS, sessão, membership, identidade técnica, dono de módulo e autorização exigem comprovação independente.
4. F1-06 tem somente inspeção/rejeição para classes A–E; não faz dispatch, não expõe webhook nem confirma fato comercial.
5. F1-07 acrescentou diagnóstico sanitizado e reconciliação **sintética**; nenhum sink de observação, resultado oficial ou retry produtivo está habilitado. F1-08 não poderá introduzir ALLOW nem efeitos externos. HTTP 2xx/recibo técnico não comprovam resultado de negócio.
6. Acesso de clientes, dados pessoais, segredos, n8n, infraestrutura, fila/outbox/inbox **produtivos**, contratação e VPS não estão autorizados. A F1-08 contém persistência **isolada em laboratório**, sem transportador ou publicação. A F1-09 integrou apenas leitura/validação sintética e reconciliação consultiva, nunca correção automática ou efeitos. A escrita sintética F1-08 não comprova autoridade/entrega, e o teste de restauração F1-09 não comprova RPO/RTO ou recuperação real. CI verde não encerra gates.

## 5. Próxima tarefa de desenvolvimento controlado

A [Issue #19 — F1-10](https://github.com/rafhael100/imagine-crm/issues/19) restringe o escopo à **consolidação de F1-02..F1-09, testes automatizados de não regressão de segurança e matriz de gates ainda abertos** para propor próximo avanço funcional mediante aprovação humana. Não autoriza operação real ou encerramento dos gates.

Codex deve iniciar a partir da HEAD atual da DEV, criar `codex/f1-10-fast-track-security-baseline` e abrir PR **DRAFT para a DEV**, sem merge. Preservar segurança deny-only. Revisão técnica/QA, CI no SHA final e aceite específico antes de nova integração.

## 6. Governança

O `AGENTS.md` é instrução de programação, `STATUS-ATUAL.md` é resumo histórico e `docs/planejamento` contém decisões normativas. GitHub PRs, Issues e checks prevalecem para situação operacional. Nenhum registro documental habilita produção.
