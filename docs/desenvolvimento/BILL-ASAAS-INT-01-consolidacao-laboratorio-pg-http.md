# BILL-ASAAS-INT-01 — convergência de provas do Asaas em PostgreSQL/HTTP fictícios

**Objetivo:** testar no **mesmo commit e mesmo runner efêmero** duas trilhas anteriormente isoladas do CRM Imagine: o controle de unicidade do pagador e o percurso Pix ilustrativo. Este é um **candidato consolidado de laboratório**, não a solução operacional de pagamentos.

## Fontes e rastreabilidade

- [PR #540](https://github.com/rafhael100/imagine-crm/pull/540) — autenticação do webhook, hash HMAC idempotente e inbox PostgreSQL de laboratório.
- [PR #541](https://github.com/rafhael100/imagine-crm/pull/541) — trabalhador de revisão da inbox, com papel restrito e efeitos negados.
- [PR #542](https://github.com/rafhael100/imagine-crm/pull/542) — consulta e criação de cliente fictício somente no Sandbox, com API HTTP simulada.
- [PR #543](https://github.com/rafhael100/imagine-crm/pull/543) — reserva única de cliente fictício por organização em PostgreSQL, antes de qualquer criação remota.
- [PR #545](https://github.com/rafhael100/imagine-crm/pull/545) — reconciliação de cliente **GET-only** em caso de resultado incerto, sem repetir POST.
- [PR #544](https://github.com/rafhael100/imagine-crm/pull/544) — GET/POST de cobrança PIX exclusivamente Sandbox fictício, com plano de referência e 14 dias ilustrativos.
- [PR #546](https://github.com/rafhael100/imagine-crm/pull/546) — consulta por ID e classificação de status de pagamento, sem acesso concedido.
- [PR #547](https://github.com/rafhael100/imagine-crm/pull/547) — roteiro Laravel `Http::fake` cliente → Pix → replay → conciliação (somente sequencial).

A integração de laboratório importa os **blobs Git verificados** de classes, fixtures e testes já preparados em #545 para a mesma árvore onde estão #544/#546/#547. Os arquivos de trabalho dos PRs originais permanecem inalterados.

## O que a CI deve comprovar na HEAD exata

O workflow `ci-crm-billing-asaas.yml` agora deve verificar, em uma única execução: testes de autenticação de webhook; inbox e worker PostgreSQL16; esquema SQL exclusivo de laboratório `asaas-customer-link-ci.sql`; unicidade de `organization_ref/externalReference/customer_id` e roles de SQL; conciliação de cliente GET-only; quatro planos com valores fixos no gateway fictício; inspeção de PIX recebida/estornada sem conceder autorização; e o teste de replay **sequencial** de cliente e Pix.

As outras suítes de projeto (F0, PostgreSQL/RLS, Trial, web/HTTPS privado, Cliente/Admin, Sites e Agents) devem permanecer verdes na mesma HEAD.

**Não confundir coexistência de testes com transação real integrada.** O teste ponta a ponta com cliente e Pix ainda usa memória + `Http::fake`, enquanto os testes de unicidade do pagador usam PostgreSQL descartável. O comando operacional que una uma reserva autenticada de organização a uma ordem transacional e ao gateway Pix **não existe e não foi autorizado**. O caso concorrente de PIX (duas tentativas de POST simultâneas) ainda exige reserva durável própria de ordem e critérios de recuperação. O laboratório não prova isolamento RLS de produção, identidade, consentimento, pagamento confirmado, assinatura ou direito de acesso.

**Gates:** DRAFT / NO-MERGE / NO-DEPLOY. Continua sem Asaas API keys válidas, servidor Leaseweb, domínio, webhooks públicos, conta de cliente, dados pessoais, notas fiscais, checkout ou cobrança efetiva. Precisa dos aceites independentes [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259) e [UX #416](https://github.com/rafhael100/imagine-crm/issues/416).

## Próximas dependências reais

Homologar esquema e RLS operacional, vínculo `tenant↔pedido↔cliente↔pagamento`, chave de idempotência de pedido única no PostgreSQL, conta e cofre de credenciais Asaas Sandbox, webhooks com autenticação persistida antes de ACK, reconciliação de valores e eventos, política de preços e trial definitiva, regra de ativação e reversão, HTTPS confiável, observabilidade e backup/restore na VPS. Nenhum desses gates foi liberado automaticamente por testes.
