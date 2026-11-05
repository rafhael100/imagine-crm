# CRM Imagine — STATUS ATUAL

**Atualização de acompanhamento:** 05/11/2026 (America/Sao_Paulo).  
**Fonte:** PRs, Issues, branches, commits e checks efetivamente consultados no GitHub.  
**Natureza:** fotografia operacional. Não constitui aceite de contratos CORE/SEG, homologação nem autorização de deploy.

## 1. Resumo executivo

- **Fase:** desenvolvimento técnico controlado em Laravel 13 / PostgreSQL 16 (Redis efêmero em CI), com fixtures sintéticas e segurança deny-by-default.
- **Branch de desenvolvimento:** [`dev/fast-track-crm-2026-11-04`](https://github.com/rafhael100/imagine-crm/tree/dev/fast-track-crm-2026-11-04), com F1-02, F1-03 e F1-04 integradas **apenas nela**. O merge F1-04 foi `407a2ead654864cd457436080999736a1186e219`.
- **`main`:** conferida no SHA `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`, sem merge novo. [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) continua DRAFT para `main`.
- **Próxima tarefa preparada:** [Issue #9 — F1-05 contratos de autorização recurso/ação/campo/escopo](https://github.com/rafhael100/imagine-crm/issues/9); ainda **não** executada pelo Codex.
- **Infraestrutura:** VPS e produção não sofreram implantação nesta sequência. Sem login público, credenciais reais, dados de clientes ou autorização comercial liberada.

## 2. Quadro das entregas

| Etapa | Evidência | Estado de implementação |
| --- | --- | --- |
| F0-02 | [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) | Branch DEV em DRAFT para `main`, sem merge |
| F1-01 | [Evidências](docs/desenvolvimento/F1-01-modelo-IAM-RLS-evidencias-CI-2026-11-04.md) | Prova de conceito IAM/RLS, não homologação |
| F1-02 | [PR #4](https://github.com/rafhael100/imagine-crm/pull/4) | Integrada na DEV, commit `3ec4cfa0621140056eab274826a639aba125e0a6` |
| F1-03 | [PR #6](https://github.com/rafhael100/imagine-crm/pull/6) | Integrada na DEV, commit `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10` |
| F1-04 | [PR #8](https://github.com/rafhael100/imagine-crm/pull/8) / [Issue #7](https://github.com/rafhael100/imagine-crm/issues/7) | Integrada na DEV, commit `407a2ead654864cd457436080999736a1186e219`; somente laboratório |
| F1-05 | [Issue #9](https://github.com/rafhael100/imagine-crm/issues/9) | Escopo preparado; aguarda Codex abrir PR DRAFT da DEV |

## 3. Verificações concretas

**F1-04 antes do merge:** SHA `228d63a2c191df8fa50e2b98cb19883a88b866bc`.
- [Laravel/SQLite — run 42027807893](https://github.com/rafhael100/imagine-crm/actions/runs/42027807893): 77 aprovados, 30 ignorados em ambiente SQLite, 252 asserções; success.
- [PostgreSQL/Redis — run 42027808128](https://github.com/rafhael100/imagine-crm/actions/runs/42027808128): 106 aprovados, 1 ignorado exclusivo SQLite, 399 asserções; success.
- Antes do merge: PR #8 sem conflitos, 17 commits à frente, 0 atrás da DEV; `AGENTS.md` e `STATUS-ATUAL.md` sincronizados.

**F1-04 após o merge na DEV:** commit `407a2ead654864cd457436080999736a1186e219`; [Laravel CI run 42036309101](https://github.com/rafhael100/imagine-crm/actions/runs/42036309101) e [PostgreSQL/Redis CI run 42036309165](https://github.com/rafhael100/imagine-crm/actions/runs/42036309165) **concluídos com sucesso**.

Novos commits documentais podem iniciar novos checks; verificar sempre o SHA atual antes de integração.

## 4. Bloqueios ainda abertos

1. CORE01/02/03/04/05/06/07/08/09, os **67 IDs técnicos CORE**, SEG-01/02 e demais gates ARQ/INT/FIN/CON/LGPD continuam dependentes de revisão e aceite próprios. O encerramento de Issue de programação **não** fecha esses requisitos.
2. `DenyByDefaultAuthorizer` ainda nega todos os efeitos comerciais. `app.tenant_id` (GUC) não é autenticação; RLS só complementa as provas de identidade/membership e autorização efetiva.
3. F1-04 introduziu em laboratório provisão sintética por mandatos, auditoria minimizada, antiabuso Redis e compensação de falhas entre sessão/SQL. São **mecanismos experimentais**, não autenticação pública, gestão real de mandatos, recovery real, cofre produtivo ou auditoria geral homologada.
4. Não acessar clientes, dados pessoais, n8n, VPS, APIs externas nem enviar mensagens comerciais. Não criar permissões `ALLOW` sem contratos técnicos e decisões CORE/SEG revisados.
5. `main` e PR #2 permanecem fora do escopo de merges experimentais; merge em DEV não libera release.

## 5. Próxima etapa operacional

**F1-05:** implementar contratos verificáveis para recurso/ação/campo/escopo, identidade e tenant, apenas no recorte de teste, preservando a negação de quaisquer efeitos. A [Issue #9](https://github.com/rafhael100/imagine-crm/issues/9) especifica escopo, critérios negativos, documentação e limites; o Codex deve criar `codex/f1-05-authorization-contracts` derivada da branch DEV atual e abrir PR DRAFT para DEV.

Depois de concluída, revisão técnica de segurança, checks reais no SHA final e aprovação explícita antes de outro merge. **Não iniciar produção/VPS.**

## 6. Atualização deste registro

`AGENTS.md` define instruções duradouras; `STATUS-ATUAL.md` é histórico datado; `docs/planejamento` contém decisões; Issues e PRs contêm execução. Conferir sempre o estado real de GitHub, pois atualizações documentais não mudam automaticamente status de implementação nem autorização.
