# CRM Imagine — Resultado da CI combinada PR #23 + PR #24 — PR #25

**Data da verificação:** 08/11/2026, hora de Brasília. **Status:** CI sintética combinada **PASS** no commit `ffefef6d5de1ecc1cdf7b863bd3bb2d8ad35e7d7`; **NÃO significa GO para merge/produção**.

## Escopo e proveniência

- Repositório: `rafhael100/imagine-crm`.
- [PR #25](https://github.com/rafhael100/imagine-crm/pull/25), **DRAFT / NO MERGE**, HEAD `ffefef6d5de1ecc1cdf7b863bd3bb2d8ad35e7d7`, base `dev/fast-track-crm-2026-11-04` SHA `8c9683acfd8decf98544402acda360d7e0f0a489`.
- Mesma árvore de código do commit `3cbe52e5cbaa505d45d85c9292856f4921b56de7`, tree `b7969b59ee76581bb32aa3278e16d7e5886ce61f`: 7 arquivos do [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) de migrações experimentais + 1 arquivo de testes negativos S01 do [PR #24](https://github.com/rafhael100/imagine-crm/pull/24). Não houve implementação de política positiva `ALLOW`, rota comercial ou alteração na DEV.

## Evidência do GitHub Actions na HEAD exata

| Workflow | Run (tentativa) | Conclusão | Testes | Asserções |
|---|---|---|---|---|
| PHP 8.4 / Laravel 13 / PHPUnit (SQLite / F0) | [42278565966](https://github.com/rafhael100/imagine-crm/actions/runs/42278565966) (attempt 2) | **SUCCESS** | **392 passed, 68 skipped** | **2.259** |
| PostgreSQL 16 / RLS / PHP 8.4 / Redis (F1) | [42278565995](https://github.com/rafhael100/imagine-crm/actions/runs/42278565995) (attempt 2) | **SUCCESS** | **459 passed, 1 skipped** | **3.076** |

Verificações: os logs dos dois jobs foram recuperados e confirmam as somas acima; os jobs passaram por checkout, dependências, migrações previstas em CI e PHPUnit. O teste `S01NegativeBoundaryTest` foi executado com sucesso no workflow PostgreSQL; no F0, os testes dependentes de PostgreSQL são ignorados. A suíte combinada agora foi comprovadamente executada no runner GitHub-hosted após o repositório CRM mudar para **público**. Resultado F0 e PostgreSQL desta rodada **não** substitui testes em futura HEAD modificada.

## Falha anterior, como foi superada

Antes de o repositório ser público, ambas as execuções falharam antes do runner com anotação informando **pagamentos recentes falhados ou limite de gastos**. A Direção informou que alterou os projetos para público. A repetição na mesma HEAD executou com sucesso sem qualquer alteração de código, sem contratar GitHub Pro ou executar instalação na VPS. Não assumir que os mecanismos de cobrança da conta foram totalmente regularizados: somente este caso de runners públicos foi testado.

## Limitações e riscos abertos

- O PR #23 continua com riscos de comandos destrutivos fora do guard das migrations (`migrate:fresh`, `db:wipe`), banco/alvo efêmero e revisão SEG/DBA especializada pendente.
- O PR #24 é uma suíte **negativa**: sua negativa em `organization.self/read_self` resulta do recurso/ação não suportado pelo catálogo experimental, não prova política positiva futura. Falta testar identidade única com memberships ativas em tenants distintos.
- Não foi autorizada integração dos PRs #23/#24 ou do PR #25 na DEV, `main` ou VPS; PR #25 continua somente veículo de prova, **não fazer merge**.
- Ao tornar públicos repositórios, há risco de exposição de código, documentação e segredos em qualquer branch/histórico anterior. Proteger credenciais via varredura de secrets do GitHub, revisar histórico/arquivos e retornar à visibilidade privada após a necessidade temporária de CI. A inspeção estática pontual de nomes de arquivos não substitui secret scanning e revisão completa.
- Todos os gates CORE, SEG-01/02, AUT e demais homologações ainda estão abertos; sem aprovação de `ALLOW`, deploy ou dados reais.

## Conclusão operacional

**VAL-PR23-PR24 / CI COMBINADA: PASS em laboratório, exclusivamente para comportamento negativo e migrações experimentais.** Próxima decisão deve ser **revisão independente de SEG/DBA**, saneamento de escopo destrutivo e eventual nova autorização para integrar cada PR individualmente na DEV. Não executar merge por CI verde.
