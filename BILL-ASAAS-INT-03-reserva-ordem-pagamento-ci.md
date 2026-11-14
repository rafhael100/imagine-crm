# BILL-ASAAS-INT-03 — reserva de pedido e vínculo Pix, laboratório CI

Status: **DRAFT · NO-MERGE · NO-DEPLOY · NO-GRANT**. Nenhum pagamento, cadastro, CNPJ, assinatura, acesso ou nota fiscal real.

## Escopo entregue

- `crm_lab_asaas_order_reservations`: tabela PostgreSQL16 de **CI descartável**, PK global por `order_ref`, UNIQUE global por `provider_payment_id`, valores em centavos e preço derivado do catálogo interno dos planos. Sem dados fiscais, conta, payload Pix, credencial ou migração operacional.
- `LaboratoryAsaasOrderReservation`: papel SQL `crm_billing_order_ci` (INSERT por colunas, sem SELECT dos pedidos), reserva `INSERT … ON CONFLICT DO NOTHING` e só permite uma primeira tentativa. Repetição ou colisão entre organizações nega HTTP. Sem transação externa pendente antes de chamar o provedor.
- `LaboratoryAsaasOrderPaymentHandoff`: simula `GET /payments` e eventual `POST /payments` com `Http::fake`, somente após uma reserva confirmada. Não retorna segredo/PII/ID do pagamento, não marca quitado, não libera assinatura ou acesso.
- `LaboratoryAsaasOrderPaymentReviewer`: papel distinto `crm_billing_order_review_ci` só com SELECT e UPDATE por coluna, verifica vínculo imutável completo; resposta externa confiável é pré-condição contratual do chamador. Nenhum endpoint ou worker.
- Trigger SQL de estado monotônico impede reabrir pedido `linked` ou `review_required`, mesmo com UPDATE via papel de revisão.
- Testes PostgreSQL e Laravel verificam duplicações, papel limitado, duas sessões, bloqueio de uma transação ainda pendente, falha de comunicação, replay sem segundo POST, colidência de IDs e ausência de rota pública.

## Falha fechada

| Situação | Resultado de laboratório |
|---|---|
| Primeiro pedido sintético e novo | Um único processo pode consultar/criar um Pix fictício |
| Repetição após reserva | `manual_review_required`; **não** repetir GET/POST |
| POST/GET inconclusivo | Marca `review_required`; **não** repetir automaticamente |
| Pagamento já ligado | Identidade do vínculo imutável; sem ativação |
| Mesmo ID para pedidos diferentes | Rejeição pelo índice UNIQUE |
| Reserva ainda não confirmada | Sem chamada externa |
| Tentativa com papel errado | Negação antes da operação |
| Tentativa de retorno `linked -> reserved` | Negação pelo gatilho SQL |

## Limites, testes e critérios de liberação

A CI roda em `postgres:16` efêmero, com `verify-ephemeral-postgres.php --verify` **antes de qualquer DDL**, quatro classes de testes de billing existentes e o novo `PostgresAsaasOrderReservationTest`. O PHP local pode apenas validar sintaxe; testes PostgreSQL só valem quando executados na HEAD exata e no runner permitido.

A presente reserva ainda **não** inclui FK `organization → customer → order` com RLS por locatário, fluxo HTTP Asaas Sandbox autenticado de verdade, cofres de segredos, confirmação server-to-server de liquidação, preços/contratos homologados, identidade do cliente, consentimento LGPD, trial durável completo, emissão de NF ou deploy Leaseweb. É proibido usar como mecanismo de cobrança.

Para revisão posterior: consolidar e aprovar segurança/DBA **#27**, Core/LGPD **#259**, UX **#416**; homologar transações, autenticação, idempotência operacional, restauração de backup e permissão de liberação financeira. A promoção exigirá PR específica, revisão e aprovação explícitas, não apenas CI verde.

## Evidência e acompanhamento

A PR #549 acumula estes recortes sobre #548. O workflow originalmente executado por *push* na branch `review/` foi corretamente barrado pelo guard SEC-CI-02 (restrito a PRs com base `dev/fast-track-crm-2026-11-04`). Para validação, usar temporariamente uma PR draft direcionada à DEV; devolver a base #548 depois de registrar CI, preservando a cadeia de revisão. Não modificar o guard para aceitar qualquer branch.
