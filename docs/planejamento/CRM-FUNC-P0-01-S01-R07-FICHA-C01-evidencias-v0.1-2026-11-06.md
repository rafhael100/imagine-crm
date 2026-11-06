# FUNC-P0-01/S01-R07 — Ficha individual R02-C01: Autenticação humana, sessão e MFA (EVIDÊNCIA v0.1)

**Data da conferência:** 06/11/2026. **Estado do contrato R02-C01: PENDENTE / NÃO HOMOLOGADO.**
**Tipo:** ficha de fontes documentais efetivamente lidas; nenhuma aprovação de implementação, teste ou política executável.
**Proprietários e revisores CANDIDATOS:** IAM / CORE04; revisão SEG-01/02. **Não há designação pessoal ou parecer formal verificados.**

## 1. Fontes versionadas lidas

- [R01 original — recorte S01](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) — blob `36a353caf695aa03aea3b0d6e0e13331a1fda5b3`; fonte de planejamento, não prova funcional.
- [CRM-REV-01 — identidade/tenant](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) — blob `ccaaeb52975d3c6861328a198523101071bb09dd`; fonte de planejamento, não prova funcional.
- [CORE-P0-04 — MFA/sessões](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md) — blob `adf09f4e790aefd8679735e62111489f3a74db68`; fonte de planejamento, não prova funcional.

## 2. Evidência identificada e alcance

REV-01 e CORE04 documentam MFA obrigatório para administrador geral e administrador da empresa; para funcionários, política dependente da organização. R01 requer sessão humana confiável antes da autoconsulta.

**Alcance da evidência:** comprovação de regras escritas em documentos de planejamento; **não** comprova que estejam implementadas, testadas, aptas para produção ou que os gates estejam fechados.

## 3. Pontos ainda sem homologação

Definir método de prova da sessão, níveis de MFA/step-up por ação, revogação/expiração e resposta de negativa sem enumeração. Não aplicar a todos os perfis o timeout histórico de 30 minutos do admin geral.

## 4. Critérios propostos para aceitação FUTURA (não executados)

**Cenário positivo condicionado:** Pessoa humana autenticada, no tenant autorizado, com membership própria ativa e fatores suficientes solicita somente os campos mínimos.

**Cenário negativo obrigatório:** Sessão ausente/revogada, MFA insuficiente ou identidade substituída não produz dados protegidos.

**Ficha futura de prova:** responsável formal, requisito/versão, cenário/risco, resultado esperado, ambiente autorizado, versão de política/código, commit, evidência íntegra, avaliação SEG e owner, observações LGPD/FIN/CON quando pertinentes. **Nenhum resultado `passed` ou teste funcional é declarado nesta ficha.**

## 5. Registro de pendências / semáforo documental

| Critério | Estado verificado |
|---|---|
| Fontes de referência localizadas e versão SHA | **SIM — documentos acima** |
| Owner/aprovador oficialmente nomeado e aceito | **NÃO CONFIRMADO** |
| Política física executável / matriz final aprovada | **NÃO** |
| Provas técnicas específicas de homologação executadas e registradas | **NÃO** |
| Gate técnico e contrato R02-C01 fechado | **NÃO — PENDENTE** |

**Controle:** `main`, PRs #2/#21, branch DEV, CI, VPS e produção não fazem parte desta entrega. Todos os **67 CORE, SEG-01/02, AUT, INT/G01..G06, FIN/CON, LGPD e demais gates ABERTOS**. Somente documentação adicional na branch isolada.