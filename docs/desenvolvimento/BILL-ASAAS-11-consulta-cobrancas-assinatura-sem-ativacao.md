# BILL-ASAAS-11 — Consultar cobranças geradas pela assinatura sem liberar o CRM

**DRAFT / NO-MERGE / NO-DEPLOY.** Apenas cliente HTTP Laravel de Sandbox desligado por padrão, testes `Http::fake`, sem request ao Asaas real nem pagamento.

## Base oficial Asaas

A API [List payments of a subscription](https://docs.asaas.com/reference/list-payments-of-a-subscription) `GET /v3/subscriptions/{id}/payments` devolve apenas as cobranças **já geradas** da recorrência, e cada cobrança tem seu próprio ID/status e ciclo. Recomenda-se Webhooks para alterações; GET para busca e conciliação pontual. A página [Eventos para cobranças](https://docs.asaas.com/docs/webhook-para-cobrancas) distingue `PAYMENT_CONFIRMED` (pagamento efetuado ainda não disponibilizado), `PAYMENT_RECEIVED` (recebimento disponível), `PAYMENT_REFUNDED`, estornos parciais e chargeback. A presença de um desses eventos não deve ser convertida isoladamente em autorização de acesso.

## Entrega

`AsaasSandboxSubscriptionPaymentsReview::inspect()` só funciona em ambiente `local/testing`, com flag `CRM_ASAAS_SANDBOX_ENABLED=true` e chave Asaas `$aact_hmlg_` fictícia nos testes. Usa somente `GET https://api-sandbox.asaas.com/v3/subscriptions/{sub_id}/payments?limit=50` com header da API `access_token` e User-Agent. O parâmetro de assinatura é rigidamente validado para impedir injeção de URL.

A resposta não disponibiliza dados pessoais, IDs de cobrança, valores, status individuais ou chaves; apresenta unicamente agregados/sinais **para revisão interna**:

| Situação encontrada no lote completo | Sinal |
|---|---|
| Nenhuma cobrança gerada | `synthetic_none_generated` |
| Somente cobranças pendentes/vencidas | `synthetic_pending_review` |
| `CONFIRMED` ou `RECEIVED` | `synthetic_provider_receipt_review` |
| Qualquer estorno, chargeback ou recebimento revertido | `synthetic_reversal_risk_review` (precedência conservadora) |

O método rejeita o lote se o provedor fornecer `hasMore=true`, mais de 50 registros, IDs duplicados, pagamentos cujo `subscription` não corresponde à assinatura consultada, estado desconhecido, erro HTTP ou JSON malformado. Nesses casos não existe sinal de pagamento confiável: exige reconciliação com paginação e tratamento dos eventos mais recentes. **Não muda nenhum registro de fatura, trial, contrato ou permissão.**

Todas as saídas possuem `payment_verified=false`, `subscription_payment_verified=false`, `can_activate=false`, `can_charge=false`, `can_invoice=false` e `can_reenable=false`, inclusive após um `RECEIVED`. Este sinal é somente informativo, não comprovante financeiro, ordem de lançamento nem regra contratual.

## Testes

`AsaasSandboxSubscriptionPaymentsReviewTest` usa `Http::preventStrayRequests` e `Http::fake`, validando endpoint, método GET, chave Sandbox, nenhuma cobrança, atraso, recebimento, estorno, chargeback, duplicidade, status inesperado, resultado incompleto, pertencimento a assinatura diferente, chave inválida, ausência de rota pública e falta de vazamento de dados.

A CI `ci-crm-billing-asaas.yml` reúne esses testes com o histórico anterior de trial, pagador, rascunho de assinatura, observação persistente e reconciliação de Webhooks em banco efêmero.

## Antes de liberar clientes

Ainda são necessários: login e representantes verificados, preço e termos homologados, conta/assinatura Asaas reais, webhook público autenticado, máquina de estados durável com histórico de eventos e reversões, conciliação server-to-server paginada e limites de negócio, inadimplência e cancelamento, auditoria e isolamento por tenant, NFS-e, chaves em cofre, staging privado na VPS Leaseweb com backups/restore, e pareceres SEG/DBA #27, CORE/LGPD #259, UX #416. Este recorte **não** completa essas dependências, não cria POST nem altera `main` ou infraestrutura.

## Ajuste adicional no receptor de Webhooks

O receptor `AsaasWebhookEnvelope` e o `CHECK` da tabela de eventos em PostgreSQL CI foram alinhados ao inventário documentado de riscos relevantes: contestação/chargeback, análise de risco, estorno em andamento ou recusado, recebimento desfeito, restauração e cancelamento de boleto, além dos eventos já recebidos. Essa expansão **não processa nem aprova** esses estados: apenas permite uma observação idempotente de notificações reconhecidas pelo provedor.

`AsaasWebhookEnvelopeTest::test_event_allowlist_is_identical_to_physical_postgresql_check_inventory` impede que o PHP aceite um evento que o banco descarte. Eventos desconhecidos fora da allowlist continuam negados até revisão documental e configuração de quais eventos o webhook efetivamente enviará.
