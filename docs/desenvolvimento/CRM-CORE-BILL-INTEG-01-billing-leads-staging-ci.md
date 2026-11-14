# CRM-CORE-BILL-INTEG-01 — Integração de módulos Billing + Leads + Tarefas + Staging

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-GRANT / NO-CHARGE.**

## Objetivo

A [PR #555](https://github.com/rafhael100/imagine-crm/pull/555) validou em PostgreSQL16 de CI os componentes Asaas de pagador, pedido Pix, rascunho de assinatura e consultas/revisões, mas estava separada da [PR #571](https://github.com/rafhael100/imagine-crm/pull/571), que contém a cadeia CRM Leads → Kanban → Tarefas e a configuração privada com recuperação PostgreSQL real em runner descartável.

Este recorte reúne **44 arquivos-fonte** da revisão de Billing à base integrada, preservando o isolamento de códigos e os workflows originais. Inclui `app/Core/Billing/*`, fixtures PostgreSQL, testes PHPUnit, classes de planos que são dependências de rascunhos e workflow dedicado de Billing.

## Evidências exigidas na HEAD unificada

- Execução do workflow `ci-crm-billing-asaas.yml` sobre todos os testes dos webhooks, clientes vinculados, Pix, reservas, drafts, consultas de assinaturas/pagamentos e ligação entre pagador/pedido/assinatura em PostgreSQL16 efêmero.
- Execução do workflow RLS/Leads do CRM, Laravel F0, paridade Sites/Agentes e testes de Compose privado.
- Smoke Compose `/up` 200 e endpoints de negócio 404; fonte de Billing presente no contêiner mas **sem rota pública ou provider ligado**. Flags `CRM_ASAAS_SANDBOX_ENABLED=false` e demais flags financeiras permanecem desligadas.
- Backup custom-format PostgreSQL16, restore em segundo contêiner `--network none`, integridade de leads/tarefas/auditoria sintéticos e RLS preservado, exclusivamente no runner.
- Nenhum endpoint de cobrança real, chave Asaas, criação de assinatura, pagamento, ativação de tenant ou invoice. A classe de rascunho não homologa preço e não atesta aceitação.

## Contrato Asaas e riscos

A documentação oficial Asaas (consultada em 14/11/2026) confirma `POST /v3/subscriptions` no Sandbox com `customer`, `billingType`, `value`, `nextDueDate`, `cycle`. Criar a assinatura **não confirma pagamento**: cobranças têm identificadores/status próprios e o Asaas recomenda webhooks para sincronização. Sandbox e Produção possuem chaves e URLs separadas. Documentação: https://docs.asaas.com/reference/create-new-subscription, https://docs.asaas.com/docs/creating-a-subscription, https://docs.asaas.com/docs/authentication-2.

**Este PR não executa POST de assinatura e não autoriza GET/POST externo fora dos testes `Http::fake`.** Para go-live, ainda faltam preço/termos formalmente homologados, identidade real do titular, consentimento, reserva idempotente operacional, segredo Asaas Sandbox em cofre, webhook real autenticado e conciliação de pagamento com estorno antes de conceder benefícios.

## Gates

SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259), UX [#416](https://github.com/rafhael100/imagine-crm/issues/416), concorrência de vínculo [#564](https://github.com/rafhael100/imagine-crm/issues/564) e [CRM-LAUNCH-01 #557](https://github.com/rafhael100/imagine-crm/issues/557). A VPS Leaseweb, main e bancos reais não são alterados.
