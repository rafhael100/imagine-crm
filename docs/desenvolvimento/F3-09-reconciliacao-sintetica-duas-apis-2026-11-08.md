# CRM Imagine — F3-09 — Reconciliação descritiva de duas APIs (laboratório)

**Data:** 08/11/2026. **Escopo:** código PHP puro, fixtures sintéticas e testes em DEV, sem serviços ou dados reais. Vinculado à [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35) e às especificações [IN-124](../planejamento/IN-124-integracao-inteligente-formularios-leads.md), [IN-148](../planejamento/IN-148-reconciliacao-inteligente-leads.md) e [IN-149](../planejamento/IN-149-protocolo-entrega-confirmacao-leads.md).

## Entrega

- `SyntheticReceiverStatus`: enum de **alegações não verificadas**: desconhecido, persistência alegada, falha alegada e expiração alegada.
- `SyntheticReceiverObservation`: descreve qual rota (principal/contingência) e a tripla de identidade `LeadSubmissionIdentity` *alegada*.
- `SyntheticLeadReconciliationInspector`: código determinístico sem I/O que compara duas observações contra uma identidade esperada e produz apenas um código neutro de diagnóstico. Detecta conflitos de organização/vínculo/envio; falsa atribuição de papel da API; ambas as APIs alegando persistência; conflito de expiração vs entrega e resposta tardia do CRM.
- `SyntheticReconciliationAssessment`: retorno sem conteúdo pessoal ou identificadores. **Nunca concede** confirmação de entrega, autorização de retry, limpeza de payload ou sucesso ao visitante. Todos os métodos `canMarkDelivered()`, `canScheduleRetry()`, `canDeleteTemporaryPayload()` e `canAcknowledgeVisitor()` são invariavelmente falsos.

A igualdade entre IDs alegados, mesmo com duas APIs informando persistência e o CRM alegando confirmação, **não constitui comprovação de persistência nem idempotência atômica**.

## O que não está implementado

API pública, autenticação/assinatura, vínculo real, geração de chave, deduplicação durável, conciliação transacional, resposta HTTP, armazenamento independente de contingência, worker, verificação efetiva no CRM, bancos, tabelas, quotas, clientes reais, integração entre servidores, exceções legais e ações automáticas.

**Gates preservados:** 67 CORE, SEG/DBA Issue #27, AUT/INT/ARQ/LGPD e produção. A CI avalia somente as combinações sintéticas testadas. Nenhum serviço externo ou custo novo, sem `main`, VPS ou deploy.

**Próxima etapa técnica recomendada:** inventariar pré-condições para ledger de idempotência durável, versão de contrato de API e verificação de evidência confiável; somente depois da revisão SEG/DBA e homologação de segurança permitir um fluxo funcional.
