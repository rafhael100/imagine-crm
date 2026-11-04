# CRM Imagine — F0-03: proximo incremento IAM e multitenancy

**Data:** 04/11/2026. **Estado:** backlog tecnico F0, **nenhum P0 encerrado**.
**Fontes:** [CORE01-D04..D07](../planejamento/CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md), [CORE02-D04/D05](../planejamento/CRM-CORE-P0-ETAPA2-CORE02-D05-combinacao-perfis-negacoes-delegacao-proposta-v0.1.md), CORE03/04/07/08/09, SEG-01/02 e [DEC-FASTTRACK-01](../planejamento/CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md).

## Contratos antes de endpoints de negocio

1. **Identidade global:** chave interna independente de CNPJ/e-mail; MFA e sessoes (CORE04), auditoria por evento (CORE06).
2. **Organizacao e membership:** tenant independente por organizacao, uma membership logica efetiva por identidade×tenant, estados e desligamento somente naquela organizacao (CORE01-D04).
3. **Politica de acesso:** recurso×acao×campo×escopo×tenant×entitlement e owner. Negar por padrao; composicao de perfis P1–P4 **ainda sem politica definitiva** (CORE02-D05).
4. **PostgreSQL RLS:** matriz por tabela/operacao `SELECT/INSERT/UPDATE/DELETE`, `USING`/`WITH CHECK`, sem role app com `BYPASSRLS`; contexto de tenant de escopo transacional e reuso seguro de pool (CORE01-D05/D07, SEG).
5. **Servicos e jobs:** principal tecnico separado do ator causal, delegacao, idempotencia e confirmacao oficial por owner (CORE03/09/INT).
6. **Planos:** Básico com um administrador humano e MFA; cortesia por contrato de Site elegivel sem duplicidade; cobrança e autorizações FIN/CON independentes.
7. **Dados externos:** APIs de Sites nao recebem leads sem CRM de destino oficialmente autorizado; proibido migrar/redirecionar implicitamente leads para outro tenant.

## Proxima entrega de codigo recomendada

- Classes e contratos **sem persistencia** `AuthenticatedPrincipal`, `TenantMembership`, `TenantResolution` com invariantes e comportamento sem permissao por ausencia de contexto.
- Testes com objetos sintéticos de separacao entre A e B, revogacao e negacao de acao, registrados e executados somente quando ambiente de desenvolvimento estiver pronto.
- Modelo ER e alternativas RLS em review de seguranca antes de migrations e endpoints.
- Cada incremento deve referenciazar seus CORE/SEG e manter o PR como **draft** até evidencia/aceite para merge.

**Nenhuma forma de acesso funcional, conta de cliente, cobranca, agente operacional, workflow externo ou configuracao real de RLS foi criada por este roteiro.**
