# FUNC-P0-01/S01-R07 — Ficha individual R02-C06: Direito por operação distinto de assinatura e membership (EVIDÊNCIA v0.1)

**Data da conferência:** 06/11/2026. **Estado do contrato R02-C06: PENDENTE / NÃO HOMOLOGADO.**
**Tipo:** ficha de fontes documentais efetivamente lidas; nenhuma aprovação de implementação, teste ou política executável.
**Proprietários e revisores CANDIDATOS:** CORE02/CORE08 + FIN/CON; revisão SEG e donos de módulos. **Não há designação pessoal ou parecer formal verificados.**

## 1. Fontes versionadas lidas

- [CORE-P0-02 — ações/campos/permissões](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) — blob `6222b2676f5ae59282c36ee607a3f9e62dd2ead6`; fonte de planejamento, não prova funcional.
- [CORE-P0-08 — planos/entitlements](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md) — blob `177ce676a45cb55c4f09936a49f0cc8507374498`; fonte de planejamento, não prova funcional.
- [CRM-REV-02 — comércio, trial e cortesia](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) — blob `dffa14e7f4e3e9557614f4522389430e42c30b43`; fonte de planejamento, não prova funcional.
- [CRM-REV-01 — identidade/tenant](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) — blob `ccaaeb52975d3c6861328a198523101071bb09dd`; fonte de planejamento, não prova funcional.

## 2. Evidência identificada e alcance

REV-01/CORE02 distinguem perfis, campos, ações e escopos; CORE08/REV02 tratam plano pago, cortesia e trial como contratos e entitlements separados do vínculo IAM.

**Alcance da evidência:** comprovação de regras escritas em documentos de planejamento; **não** comprova que estejam implementadas, testadas, aptas para produção ou que os gates estejam fechados.

## 3. Pontos ainda sem homologação

Aprovar matriz recurso×ação×campo×tenant×principal×vigência×entitlement, limitações de plano, precedência de negações, GOV02 e contratos de acesso restrito de exportação. Ratificações CORE02 de outras branches exigem reconciliação própria.

## 4. Critérios propostos para aceitação FUTURA (não executados)

**Cenário positivo condicionado:** Pessoa elegível só acessa ação/campo individualmente autorizados, com contrato/entitlement quando aplicável.

**Cenário negativo obrigatório:** Plano 'ativo', site elegível ou membership ativo não permitem exportação, convite de usuário, acesso de terceiro ou função global sem política específica.

**Ficha futura de prova:** responsável formal, requisito/versão, cenário/risco, resultado esperado, ambiente autorizado, versão de política/código, commit, evidência íntegra, avaliação SEG e owner, observações LGPD/FIN/CON quando pertinentes. **Nenhum resultado `passed` ou teste funcional é declarado nesta ficha.**

## 5. Registro de pendências / semáforo documental

| Critério | Estado verificado |
|---|---|
| Fontes de referência localizadas e versão SHA | **SIM — documentos acima** |
| Owner/aprovador oficialmente nomeado e aceito | **NÃO CONFIRMADO** |
| Política física executável / matriz final aprovada | **NÃO** |
| Provas técnicas específicas de homologação executadas e registradas | **NÃO** |
| Gate técnico e contrato R02-C06 fechado | **NÃO — PENDENTE** |

**Controle:** `main`, PRs #2/#21, branch DEV, CI, VPS e produção não fazem parte desta entrega. Todos os **67 CORE, SEG-01/02, AUT, INT/G01..G06, FIN/CON, LGPD e demais gates ABERTOS**. Somente documentação adicional na branch isolada.