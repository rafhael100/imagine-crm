# CRM F3-103 — Janela ilustrativa pós-cancelamento

**Data:** 11/11/2026. **Ambiente:** DEV, dados totalmente sintéticos, sem conta, assinatura, pagamento ou banco reais.

## Regra de apresentação

O cálculo simula a janela candidata de **120 dias de 24 horas a partir do instante UTC informado como cancelamento**, sem tentar verificar se a assinatura de uma empresa realmente foi cancelada. O relógio recebido do chamador também é apenas uma amostra. As fases informacionais são `before_cancellation`, `within_review_window` e `review_window_elapsed`.

O prazo candidato pós-cancelamento é **separado** da janela ilustrativa de 60 dias após o trial (F3-102). A existência ou término de qualquer um desses intervalos não autoriza reativação, exclusão, cobrança ou acesso aos dados do cliente. Uma política definitiva precisa considerar obrigações contratuais, direitos LGPD, backups, retenção legal, pedido do titular, credenciais e serviços ativos.

## Implementação e testes

`SyntheticCancellationRetentionTimeline` aceita dois instantes UTC inteiros sintéticos, valida negativos e overflow `PHP_INT_MAX`, calcula o fim e o tempo remanescente e retorna somente valores numéricos, fases fixas e cinco flags de negação. PHPUnit verifica instantes antes, no início, durante, no último segundo e depois do prazo, mais invariantes de autorização e rejeições adversariais.

Nenhuma API, scheduler, fila, exclusão, restore, SQL, IAM/ALLOW, VPS, custo, `main` ou produção. CORE/SEG/DBA/LGPD e liberação operacional permanecem NO-GO.
