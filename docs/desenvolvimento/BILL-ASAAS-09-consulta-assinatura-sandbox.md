# BILL-ASAAS-09 — Consulta de assinaturas Asaas Sandbox sem criação ou concessão de acesso

**DRAFT / NO-MERGE / NO-DEPLOY.** Implementação somente para testes e uso local controlado, **sem credenciais reais conectadas, sem envio de HTTP na CI e sem endpoints publicados**.

## Referência externa

A documentação oficial Asaas (14/11/2026) prevê `GET /v3/subscriptions` com `externalReference`, `customer`, `includeDeleted` e `limit`, recomendando seu uso para **consulta pontual/recuperação**, e Webhooks para acompanhar mudanças. O status de uma assinatura recorrente **não significa pagamento liquidado**: cada cobrança gerada tem seu próprio ID/status.
- https://docs.asaas.com/reference/list-subscriptions
- https://docs.asaas.com/docs/subscription-overview
- https://docs.asaas.com/docs/subscription-events

## Implementado

`AsaasSandboxSubscriptionLookup`:

1. Só pode ser construído em `APP_ENV=local/testing`, com flag explícita `CRM_ASAAS_SANDBOX_ENABLED=true` e chave com prefixo `$aact_hmlg_`. Nunca usa domínio de produção. A autenticação de API é `access_token`, distinta de `asaas-access-token` dos Webhooks.
2. Faz **somente GET** `https://api-sandbox.asaas.com/v3/subscriptions?externalReference=...&includeDeleted=true&limit=2`, sem retry automático, sem POST, PUT ou DELETE.
3. Valida a referência UUID, o `cus_...` esperado, estrutura/paginação, correspondência exata de `externalReference` e `customer`, e identificador `sub_...` com formato esperado.
4. Respostas sem resultado retornam apenas `sandbox_subscription_absent`, **jamais autorização de criar assinatura**. Múltiplos resultados, resposta truncada, ID divergente, pagador errado ou falha HTTP retornam erro conservador e genérico.
5. Uma assinatura encontrada permite apenas recuperar o identificador do provedor na **biblioteca de backend**; não confirma valor, plano, fatura, recebimento, CNPJ, titularidade, contrato ou direito de usuário. Flags `can_create_subscription`, `can_charge`, `can_activate`, `can_invoice` e `payment_verified` são sempre falsas.

`AsaasSandboxSubscriptionLookupTest` usa `Http::fake` + `Http::preventStrayRequests` para testar todas as variações com chave fictícia. O arquivo não registra rotas nem workers, e sua CI é a de cobrança isolada do repositório.

## Fronteiras

A etapa BILL-ASAAS-08 persiste apenas um **rascunho bloqueado**, pois catálogo/preço/termos não estão homologados; BILL-ASAAS-09 apenas permite **buscar** uma assinatura preexistente na conta de teste. Não existe fluxo de `POST /subscriptions` habilitado, nem senha de Sandbox em cofre, autorização empresarial, trilha de aceite ou homologação na VPS.

Para lançar, são necessárias aprovação comercial e de preços, autenticação/titularidade/CNPJ, contrato, API real com idempotência e conciliação persistente, Webhooks de pagamento e assinatura, auditoria LGPD, backup/rollback, TLS/DNS, custos e revisão das issues SEG/DBA #27, CORE/LGPD #259 e UX #416.

**Nenhum pagamento, assinatura, cliente ou pedido operacional foi criado, e `main` permanece intocada.**
