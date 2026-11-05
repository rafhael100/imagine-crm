# CRM Imagine — STATUS ATUAL

**Atualização de acompanhamento:** 05/11/2026 (America/Sao_Paulo).  
**Fonte:** PRs, Issues, branches, commits e GitHub Actions conferidos no GitHub.  
**Natureza:** fotografia operacional; não constitui aprovação de contratos CORE/SEG, homologação de segurança nem autorização de deploy.

## 1. Resumo executivo

- **Fase:** desenvolvimento técnico controlado do núcleo Laravel 13 / PostgreSQL 16 / Redis efêmero em CI, apenas com dados sintéticos, sem autorização comercial real.
- **Branch autorizada:** [`dev/fast-track-crm-2026-11-04`](https://github.com/rafhael100/imagine-crm/tree/dev/fast-track-crm-2026-11-04). As etapas F1-02, F1-03, F1-04 e **F1-05 já estão integradas exclusivamente nela**.
- **Último merge técnico:** [PR #10 — F1-05](https://github.com/rafhael100/imagine-crm/pull/10), commit `9cc5ae4214e07903b2b69d9d40541c61f395d4bf`.
- **Branch `main`:** conferida sem alteração no SHA `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`; [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) continua DRAFT, sem merge em `main`.
- **Próxima tarefa:** [Issue #11 — F1-06, ponte de autoridade assíncrona](https://github.com/rafhael100/imagine-crm/issues/11), preparada para o Codex, ainda sem novo PR desta fase.
- **VPS/produção:** sem implantação nesta sequência. Sem login público, dados reais, filas externas ou permissões comerciais habilitadas.

## 2. Entregas integradas à DEV

| Marco | Evidência | Estado verificado |
| --- | --- | --- |
| F0-02 | [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) | DRAFT com destino `main`; ainda NÃO integrado |
| F1-01 | [Prova IAM/RLS](docs/desenvolvimento/F1-01-modelo-IAM-RLS-evidencias-CI-2026-11-04.md) | Protótipo documentado, sem homologação |
| F1-02 | [PR #4](https://github.com/rafhael100/imagine-crm/pull/4) | Merge em DEV `3ec4cfa0621140056eab274826a639aba125e0a6` |
| F1-03 | [PR #6](https://github.com/rafhael100/imagine-crm/pull/6) | Merge em DEV `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10` |
| F1-04 | [PR #8](https://github.com/rafhael100/imagine-crm/pull/8) | Merge em DEV `407a2ead654864cd457436080999736a1186e219` |
| F1-05 | [PR #10](https://github.com/rafhael100/imagine-crm/pull/10) / [Issue #9](https://github.com/rafhael100/imagine-crm/issues/9) | Merge em DEV `9cc5ae4214e07903b2b69d9d40541c61f395d4bf`, autorização deny-only preservada |
| F1-06 | [Issue #11](https://github.com/rafhael100/imagine-crm/issues/11) | Backlog preparado para Codex, sem programação ou merge executados nesta etapa |

## 3. Testes confirmados

**F1-05, head pré-merge `312f2933cb5d8a0675b1a0e5925aeb7e3f1d6909`:**
- [Laravel/SQLite](https://github.com/rafhael100/imagine-crm/actions/runs/42042627062): **229 aprovados, 32 skips, 753 asserções**, `success`.
- [PostgreSQL/Redis](https://github.com/rafhael100/imagine-crm/actions/runs/42042626980): **260 aprovados, 1 skip, 917 asserções**, `success`.

**F1-05, após merge em DEV `9cc5ae4214e07903b2b69d9d40541c61f395d4bf`:**
- [Laravel — run 42052640678](https://github.com/rafhael100/imagine-crm/actions/runs/42052640678): **success**.
- [PostgreSQL/RLS/Redis — run 42052641333](https://github.com/rafhael100/imagine-crm/actions/runs/42052641333): **success**.

Verificar novamente os testes e o SHA após qualquer commit documental, pois novos commits podem disparar novas execuções. Checks verdes demonstram apenas os cenários sintéticos cobertos.

## 4. Segurança e impedimentos preservados

1. **`DenyByDefaultAuthorizer` continua sendo a única política ativa**, sem `ALLOW` para operação de negócio. O catálogo `lab.record` da F1-05 apenas descreve fixtures e nunca concede acesso.
2. CORE01..CORE09 (inclusive CORE02-D04..D10 e CORE03-D04..D10), os **67 IDs técnicos CORE**, SEG-01/02, ARQ/INT/FIN/CON/LGPD continuam abertos conforme contratos/aceites específicos. CORE03/CORE09-D01..D03 são somente base documental, sem mecanismo físico homologado.
3. A GUC `app.tenant_id` não é identidade nem autorização: session registry, membership ativa, RLS, contexto tenant e decisão do módulo precisam ser verificáveis.
4. Provas experimentais de IAM/MFA, provisioning, antiabuso, auditoria IAM, autorização e contratos assíncronos não configuram login real, suporte, plano comercial, jobs produtivos ou integração externa.
5. Nenhuma implantação na VPS, alteração em `main`, emissão de tokens reais, dados de clientes ou uso produtivo de n8n é autorizado.

## 5. Próxima etapa operacional

**F1-06** (Issue #11): ponte de autoridade para atos humanos diferidos, rotinas internas obrigatórias, automações do motor próprio, ingresso externo/site e ações técnicas Imagine, distinguindo ator causal, executor técnico, tenant, escopo e resultado oficial. **Somente contratos/provas sintéticas deny-only**, sem construir filas produtivas, ativar webhooks ou usar n8n para automações de clientes.

O Codex deverá partir da HEAD atual de DEV, criar `codex/f1-06-async-authority-contracts` e abrir PR DRAFT contra DEV, executar ambas as CI, documentar riscos e manter `main`/VPS intactas. Revisão humana e aceite explícito precedem eventual novo merge.

## 6. Governança do registro

Este arquivo documenta evidência histórica, não substitui `AGENTS.md` nem decisões em `docs/planejamento`. Conferir sempre o estado real da branch, PRs, Issues e Actions antes de aceitar novas alterações.
