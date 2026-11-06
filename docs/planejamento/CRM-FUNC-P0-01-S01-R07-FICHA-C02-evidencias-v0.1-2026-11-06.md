# FUNC-P0-01/S01-R07 — Ficha individual R02-C02: Principal, organização corrente e membership (EVIDÊNCIA v0.1)

**Data da conferência:** 06/11/2026. **Estado do contrato R02-C02: PENDENTE / NÃO HOMOLOGADO.**
**Tipo:** ficha de fontes documentais efetivamente lidas; nenhuma aprovação de implementação, teste ou política executável.
**Proprietários e revisores CANDIDATOS:** CORE01/CORE02 + administração de memberships; revisão SEG. **Não há designação pessoal ou parecer formal verificados.**

## 1. Fontes versionadas lidas

- [R01 original — recorte S01](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) — blob `36a353caf695aa03aea3b0d6e0e13331a1fda5b3`; fonte de planejamento, não prova funcional.
- [CRM-REV-01 — identidade/tenant](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) — blob `ccaaeb52975d3c6861328a198523101071bb09dd`; fonte de planejamento, não prova funcional.
- [CORE-P0-01 — identidade e RLS](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md) — blob `ec7a03e38fedc3514eaa2c91c6def80d0ac6dba1`; fonte de planejamento, não prova funcional.

## 2. Evidência identificada e alcance

R01-D02-A especifica apenas vínculo próprio ativo e comprovado como elegível à S01; REV-01/CORE01 documentam identidade global com vínculos por organização e resolução confiável pelo servidor.

**Alcance da evidência:** comprovação de regras escritas em documentos de planejamento; **não** comprova que estejam implementadas, testadas, aptas para produção ou que os gates estejam fechados.

## 3. Pontos ainda sem homologação

Formalizar o owner de membership, estados/versões, confirmação do tenant selecionado, janela de revogação, convites pendentes e negativa sem revelar organizações não autorizadas.

## 4. Critérios propostos para aceitação FUTURA (não executados)

**Cenário positivo condicionado:** Identidade humana válida e vínculo próprio ativo com organização corrente comprovada.

**Cenário negativo obrigatório:** Alterar tenant em parâmetro, consultar membership de terceiro ou vínculo revogado deve negar sem revelar dados/organização.

**Ficha futura de prova:** responsável formal, requisito/versão, cenário/risco, resultado esperado, ambiente autorizado, versão de política/código, commit, evidência íntegra, avaliação SEG e owner, observações LGPD/FIN/CON quando pertinentes. **Nenhum resultado `passed` ou teste funcional é declarado nesta ficha.**

## 5. Registro de pendências / semáforo documental

| Critério | Estado verificado |
|---|---|
| Fontes de referência localizadas e versão SHA | **SIM — documentos acima** |
| Owner/aprovador oficialmente nomeado e aceito | **NÃO CONFIRMADO** |
| Política física executável / matriz final aprovada | **NÃO** |
| Provas técnicas específicas de homologação executadas e registradas | **NÃO** |
| Gate técnico e contrato R02-C02 fechado | **NÃO — PENDENTE** |

**Controle:** `main`, PRs #2/#21, branch DEV, CI, VPS e produção não fazem parte desta entrega. Todos os **67 CORE, SEG-01/02, AUT, INT/G01..G06, FIN/CON, LGPD e demais gates ABERTOS**. Somente documentação adicional na branch isolada.