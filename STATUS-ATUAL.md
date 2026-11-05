# CRM Imagine — STATUS ATUAL

**Atualizado em:** 05/11/2026 (America/Sao_Paulo).  
**Base:** GitHub Issues, PRs, commits e Actions consultados na data.  
**Natureza:** registro operacional datado, não aprovação de gates, homologação de segurança nem autorização de produção.

## 1. Situação resumida

- **Fase:** desenvolvimento técnico controlado e experimental com Laravel 13, PostgreSQL 16 e Redis em CI, sempre com fixtures sintéticas, sem autorização de negócio.
- **Branch de trabalho:** [`dev/fast-track-crm-2026-11-04`](https://github.com/rafhael100/imagine-crm/tree/dev/fast-track-crm-2026-11-04). **F1-02, F1-03, F1-04, F1-05 e F1-06 integradas somente na DEV**.
- **Último merge:** [PR #12 — F1-06](https://github.com/rafhael100/imagine-crm/pull/12) fechado como MERGED, commit `691f8c0e508fabae5d3560cca044bf280d2fcfb5`, autorização expressa para a DEV.
- **`main`:** permanece no commit verificado `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`. [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) continua **DRAFT** para `main`, sem merge.
- **Próxima tarefa:** [Issue #13 — F1-07 observabilidade e reconciliação assíncrona seguras](https://github.com/rafhael100/imagine-crm/issues/13), preparada para Codex (nenhuma programação iniciada por esta atualização).
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
| F1-07 | [Issue #13](https://github.com/rafhael100/imagine-crm/issues/13) | Escopo pronto; Codex ainda não iniciou nesta sequência |

## 3. Testes conferidos

**Antes do merge F1-06:** head `fd08a9212a43c01d9b8a66f1188a361e66b14774`.
- [Laravel/SQLite — run 42070845197](https://github.com/rafhael100/imagine-crm/actions/runs/42070845197): **307 aprovados, 33 ignorados, 1.014 asserções**, success.
- [PostgreSQL/Redis — run 42070845055](https://github.com/rafhael100/imagine-crm/actions/runs/42070845055): **339 aprovados, 1 ignorado, 1.233 asserções**, success.

**Depois do merge F1-06:** SHA `691f8c0e508fabae5d3560cca044bf280d2fcfb5`.
- [Laravel — run 42073457091](https://github.com/rafhael100/imagine-crm/actions/runs/42073457091): **success**.
- [PostgreSQL/RLS/Redis — run 42073457093](https://github.com/rafhael100/imagine-crm/actions/runs/42073457093): **success**.

Novos commits de documentação disparam novos checks. Verificar `HEAD` e Actions atuais antes de abrir ou integrar PR. Suítes sintéticas não comprovam prontidão para produção.

## 4. Segurança e pendências preservadas

1. **`DenyByDefaultAuthorizer` continua a única autorização ativa**, sem política `ALLOW`. Contratos `AuthorizationIntent`, `AuthorizationEnvelope`, `AsyncAuthorityRequest` e `AsyncAuthorityBridge` são modelos de laboratório, não credenciais ou direitos transportáveis.
2. CORE01..CORE09, incluindo CORE02-D04..D10 e CORE03/CORE09-D04..D10, os **67 IDs técnicos CORE**, SEG-01/02, ARQ/INT/FIN/CON/LGPD permanecem abertos conforme decisões/aceites por módulo. D01..D03 de CORE03/09 são bases documentais aprovadas, não liberação técnica.
3. Um `tenant_id`/GUC `app.tenant_id` fornecido não autentica. RLS, sessão, membership, identidade técnica, dono de módulo e autorização exigem comprovação independente.
4. F1-06 tem somente inspeção/rejeição para classes A–E; não faz dispatch, não expõe webhook nem confirma fato comercial. HTTP 2xx/recibo técnico não comprovam resultado de negócio.
5. Acesso de clientes, dados pessoais, segredos, n8n, infraestrutura, fila/outbox/inbox real, contratação e VPS não estão autorizados. CI verde não encerra gates.

## 5. Próxima tarefa de desenvolvimento controlado

A [Issue #13 — F1-07](https://github.com/rafhael100/imagine-crm/issues/13) restringe o escopo à **observabilidade interna sanitizada e classificação controlada de falhas/negar/reconciliar** da ponte assíncrona, com testes sintéticos e sem eventos/integrações reais.

Codex deve iniciar a partir da HEAD atual da DEV, criar `codex/f1-07-async-observability-reconciliation` e abrir PR **DRAFT para a DEV**, sem merge. Preservar segurança deny-only. Revisão técnica/QA, CI no SHA final e aceite específico antes de nova integração.

## 6. Governança

O `AGENTS.md` é instrução de programação, `STATUS-ATUAL.md` é resumo histórico e `docs/planejamento` contém decisões normativas. GitHub PRs, Issues e checks prevalecem para situação operacional. Nenhum registro documental habilita produção.
