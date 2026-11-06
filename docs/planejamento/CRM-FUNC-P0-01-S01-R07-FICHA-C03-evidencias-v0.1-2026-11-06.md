# FUNC-P0-01/S01-R07 — Ficha individual R02-C03: Autorização em aplicação, RLS e revogação (EVIDÊNCIA v0.1)

**Data da conferência:** 06/11/2026. **Estado do contrato R02-C03: PENDENTE / NÃO HOMOLOGADO.**
**Tipo:** ficha de fontes documentais efetivamente lidas; nenhuma aprovação de implementação, teste ou política executável.
**Proprietários e revisores CANDIDATOS:** CORE01/02/03 + arquitetura PostgreSQL; revisão SEG. **Não há designação pessoal ou parecer formal verificados.**

## 1. Fontes versionadas lidas

- [R01 original — recorte S01](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) — blob `36a353caf695aa03aea3b0d6e0e13331a1fda5b3`; fonte de planejamento, não prova funcional.
- [CORE-P0-01 — identidade e RLS](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md) — blob `ec7a03e38fedc3514eaa2c91c6def80d0ac6dba1`; fonte de planejamento, não prova funcional.
- [CORE-P0-02 — ações/campos/permissões](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) — blob `6222b2676f5ae59282c36ee607a3f9e62dd2ead6`; fonte de planejamento, não prova funcional.
- [CORE-P0-03 — autoridade assíncrona](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md) — blob `3266eedea35f9d58f0b1fc85f4c991bc8400994a`; fonte de planejamento, não prova funcional.

## 2. Evidência identificada e alcance

As fontes tratam RLS PostgreSQL mais autorização Laravel como defesa em profundidade. A aprovação preliminar R01-D04-A prioriza RLS não contornável, revalidação e falha segura.

**Alcance da evidência:** comprovação de regras escritas em documentos de planejamento; **não** comprova que estejam implementadas, testadas, aptas para produção ou que os gates estejam fechados.

## 3. Pontos ainda sem homologação

Falta matriz concreta de papéis DB, políticas por tabela/recurso, controle de BYPASSRLS/owner, conexão/pool, cache, revogação concorrente e autorização por ação. Nenhum SQL nem GRANT foi aprovado.

## 4. Critérios propostos para aceitação FUTURA (não executados)

**Cenário positivo condicionado:** Nova leitura autorizada somente no tenant correto, com versão atual do vínculo e projeção mínima.

**Cenário negativo obrigatório:** IDOR, conexão reaproveitada com contexto de outro tenant, papel que contorna RLS ou cache após revogação nunca deve revelar dados.

**Ficha futura de prova:** responsável formal, requisito/versão, cenário/risco, resultado esperado, ambiente autorizado, versão de política/código, commit, evidência íntegra, avaliação SEG e owner, observações LGPD/FIN/CON quando pertinentes. **Nenhum resultado `passed` ou teste funcional é declarado nesta ficha.**

## 5. Registro de pendências / semáforo documental

| Critério | Estado verificado |
|---|---|
| Fontes de referência localizadas e versão SHA | **SIM — documentos acima** |
| Owner/aprovador oficialmente nomeado e aceito | **NÃO CONFIRMADO** |
| Política física executável / matriz final aprovada | **NÃO** |
| Provas técnicas específicas de homologação executadas e registradas | **NÃO** |
| Gate técnico e contrato R02-C03 fechado | **NÃO — PENDENTE** |

**Controle:** `main`, PRs #2/#21, branch DEV, CI, VPS e produção não fazem parte desta entrega. Todos os **67 CORE, SEG-01/02, AUT, INT/G01..G06, FIN/CON, LGPD e demais gates ABERTOS**. Somente documentação adicional na branch isolada.