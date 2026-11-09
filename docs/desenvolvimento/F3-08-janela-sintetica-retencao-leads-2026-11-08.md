# CRM Imagine — F3-08 — Janelas sintéticas de retenção de envios

**Data:** 08/11/2026. **Escopo:** cálculo inerte com `DateTimeImmutable` e fixtures fictícias, somente CI/DEV. Vinculado à [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [IN-127](../planejamento/IN-127-retencao-seguranca-filas-leads.md), [IN-147](../planejamento/IN-147-exclusao-leads-nao-entregues-apos-18-dias.md), [IN-148](../planejamento/IN-148-reconciliacao-inteligente-leads.md) e [IN-149](../planejamento/IN-149-protocolo-entrega-confirmacao-leads.md).

## Objetivo e invariantes

O laboratório `SyntheticRetentionWindowInspector` classifica exclusivamente **janelas de calendário** a partir do instante alegado de primeira persistência durável, sem validar se houve recebimento real ou entrega ao CRM. A contagem é em segundos absolutos (timestamps UTC), não em dias civis locais:
- T0 a 72 horas: janela normal.
- De 72h a 432h: janela de falhas; a partir de 360h (72h antes do vencimento) sinalização de **revisão de aviso**.
- A partir de 432h (18 dias): **revisão de expiração/reconciliação**, nunca exclusão automática.
- Se for fornecida uma alegação de confirmação de entrega ao CRM, após 24 horas haverá apenas **indicação de revisão de limpeza**; essa alegação não comprova entrega. O alerta de expiração do fluxo não entregue não pode suplantar uma confirmação ainda não reconciliada.

O resultado contém somente códigos neutros e indicadores de revisão; os métodos `canDeleteTemporaryPayload()`, `canRetryAutomatically()` e `canSendNotification()` são **sempre falsos**.

## Exclusões

Nenhum payload de cliente é armazenado, enviado, eliminado ou transformado; nenhuma fila, job, rotina agendada, redis, cron, webhook, banco, login, autorização real, `ALLOW`, SQL, migration, arquivo ou infraestrutura foram modificados. A retenção de **90 dias de auditoria técnica minimizada** segue aprovada normativamente, mas **não é programada** neste lote. Não há quarentena adicional de 30 dias. Nenhum evento real de advertência ou limpeza é emitido.

## Requisitos críticos antes de execução real

Validar primeira persistência por API principal/contingência com timestamp íntegro; ledger idempotente de origem; reconciliação atômica de filas e respostas tardias; checagem de restrições legais, duplicatas, restauração de backup e réplicas, alertas auditáveis, MFA/permissões e parecer SEG/DBA. Testes de 3d, 15d, 18d e 24h só comprovam os cálculos sintéticos, **não** segurança de descarte em produção.
