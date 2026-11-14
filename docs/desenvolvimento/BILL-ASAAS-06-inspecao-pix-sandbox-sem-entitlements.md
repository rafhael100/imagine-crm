# BILL-ASAAS-06 — conciliação PIX Sandbox, somente leitura e sem concessão de acesso

**Escopo:** laboratório Laravel, branch de revisão, **DRAFT / NO-MERGE / NO-DEPLOY**. Depende dos candidatos [#540](https://github.com/rafhael100/imagine-crm/pull/540), [#541](https://github.com/rafhael100/imagine-crm/pull/541), [#542](https://github.com/rafhael100/imagine-crm/pull/542) e [#544](https://github.com/rafhael100/imagine-crm/pull/544). **Não existe conexão com a conta Asaas do cliente nem com a VPS nesta entrega.**

## Novo contrato executável

A classe `AsaasSandboxPixReconciliationInspector` realiza exclusivamente `GET https://api-sandbox.asaas.com/v3/payments/{id}` para um ID de cobrança fictícia `pay_...`, estritamente depois de validar resultados estruturados das fases de cliente Sandbox (#542) e cobrança Pix (#544), referência externa `LAB-IMAGINE-ORDER-UUID`, vencimento ISO e preço em centavos do catálogo interno. Não confia em valor, status, cliente ou referências recebidas por um navegador. Aceita somente o prefixo de credencial Sandbox `$aact_hmlg_` em `local/testing` e exige **duas flags explícitas**, `CRM_ASAAS_SANDBOX_ENABLED=true` e `CRM_ASAAS_SANDBOX_RECONCILIATION_ENABLED=true`.

O registro retornado pelo Asaas deve concordar em **id, cliente, externalReference, dueDate, billingType PIX, valor exato e status conhecido**; resultados incompletos, divergentes ou erros de API resultam em resposta genérica, sem reproduzir segredo, CNPJ, e-mail, URL de fatura ou dados de cliente.

A classificação é deliberadamente conservadora: `PENDING` significa aguardar, `OVERDUE` pede revisão, `CONFIRMED` não é `RECEIVED`, `RECEIVED` pede revisão adicional, e reembolsos, exclusões ou chargebacks entram em risco. **Todos os estados retornam `can_activate=false`, `can_mark_paid=false`, `can_notify=false`, `can_create_subscription=false`, `settlement_confirmed=false`, `production_approved=false` e `reconciliation_required=true`.**

`AsaasSandboxPixReconciliationInspectorTest` usa `Http::fake` e `Http::preventStrayRequests()`; casos por `DataProvider` não compartilham respostas falsificadas. Verifica todos os dez estados, consulta **somente GET**, bloqueio de credenciais/live flags, divergências de referência e valores, cobrança não reconhecida, falhas da API e ausência de qualquer efeito financeiro.

## O que ainda falta

Esse leitor **não é homologação de recebimento real**. A decisão de ativação requer: identidade/tenant/consentimentos validados em backend, vínculo `tenant↔order↔customer↔payment` persistente e transacional com chave única, integração real com segredos protegidos e segregados, webhook autenticado recebido e persistido antes do HTTP 200, reconciliação de status idempotente e revisão de estorno/chargeback, regras de preço/desconto/recorrência aprovadas, logs mínimos LGPD, rollback, auditoria independente e testes reais no Sandbox antes de produção. Não criar cobrança, autenticação, assinatura, NFS-e, Pix Automático ou cobrança de cliente a partir deste pacote.

**Referências oficiais:** [Consultar cobrança por ID](https://docs.asaas.com/reference/retrieve-a-single-payment), [Eventos e diferença entre pagamento confirmado e recebido](https://docs.asaas.com/docs/eventos-de-webhooks), [Idempotência](https://docs.asaas.com/docs/como-implementar-idempotencia-em-webhooks).

**Gates abertos:** SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259) e UX [#416](https://github.com/rafhael100/imagine-crm/issues/416).
