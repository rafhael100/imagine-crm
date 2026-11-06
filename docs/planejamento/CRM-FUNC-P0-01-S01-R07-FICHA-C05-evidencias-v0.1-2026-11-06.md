# FUNC-P0-01/S01-R07 — Ficha individual R02-C05: Proveniência de fatos comerciais, sem exposição S01 (EVIDÊNCIA v0.1)

**Data da conferência:** 06/11/2026. **Estado do contrato R02-C05: PENDENTE / NÃO HOMOLOGADO.**
**Tipo:** ficha de fontes documentais efetivamente lidas; nenhuma aprovação de implementação, teste ou política executável.
**Proprietários e revisores CANDIDATOS:** FIN/CON CRM + Portal Sites; revisão INT/CORE08/SEG. **Não há designação pessoal ou parecer formal verificados.**

## 1. Fontes versionadas lidas

- [CRM-REV-02 — comércio, trial e cortesia](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) — blob `dffa14e7f4e3e9557614f4522389430e42c30b43`; fonte de planejamento, não prova funcional.
- [CORE-P0-08 — planos/entitlements](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md) — blob `177ce676a45cb55c4f09936a49f0cc8507374498`; fonte de planejamento, não prova funcional.
- [IN-119 — cortesia](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-119-gestao-cortesia-crm-basico.md) — blob `014c00b8487b786a4a546c5edee8cbc30bfc1159`; fonte de planejamento, não prova funcional.
- [COM-08 — inadimplência site](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md) — blob `7f44060c8486356a46c3332682d630e29c49ee39`; fonte de planejamento, não prova funcional.
- [COM-09 — duas transições](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md) — blob `a8b6d8d71c0ae882cf4336326c0647aee34823e8`; fonte de planejamento, não prova funcional.

## 2. Evidência identificada e alcance

REV-02 distingue trial 14 dias, atraso CRM pago 7/30, benefício por site, transições por cancelamento comum/restrito e retenção CRM após cancelamento próprio. COM-08/09 diferenciam suspensão do site, cancelamento do site e efeito na cortesia.

**Alcance da evidência:** comprovação de regras escritas em documentos de planejamento; **não** comprova que estejam implementadas, testadas, aptas para produção ou que os gates estejam fechados.

## 3. Pontos ainda sem homologação

Definir catálogo oficial de eventos, autenticação, tenant/contrato, versão, marco temporal/fuso, regra de dia útil, confirmação de pagamento, precedência de eventos e revisão jurídica. Fonte histórica IN-092 independente não localizada nas branches atuais consultadas.

## 4. Critérios propostos para aceitação FUTURA (não executados)

**Cenário positivo condicionado:** Uma transição documental somente referencia fato oficial validado e reconciliado pelo owner comercial, sem incluir dados comerciais na resposta S01.

**Cenário negativo obrigatório:** Callback HTTP 2xx, fatura pendente, evento de site de outro tenant ou replay não criam pagamento, cobrança ou concessão de acesso.

**Ficha futura de prova:** responsável formal, requisito/versão, cenário/risco, resultado esperado, ambiente autorizado, versão de política/código, commit, evidência íntegra, avaliação SEG e owner, observações LGPD/FIN/CON quando pertinentes. **Nenhum resultado `passed` ou teste funcional é declarado nesta ficha.**

## 5. Registro de pendências / semáforo documental

| Critério | Estado verificado |
|---|---|
| Fontes de referência localizadas e versão SHA | **SIM — documentos acima** |
| Owner/aprovador oficialmente nomeado e aceito | **NÃO CONFIRMADO** |
| Política física executável / matriz final aprovada | **NÃO** |
| Provas técnicas específicas de homologação executadas e registradas | **NÃO** |
| Gate técnico e contrato R02-C05 fechado | **NÃO — PENDENTE** |

**Controle:** `main`, PRs #2/#21, branch DEV, CI, VPS e produção não fazem parte desta entrega. Todos os **67 CORE, SEG-01/02, AUT, INT/G01..G06, FIN/CON, LGPD e demais gates ABERTOS**. Somente documentação adicional na branch isolada.