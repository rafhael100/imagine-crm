# BILL-ASAAS-07 — jornada única simulada Cliente → Pix → Repetição → Conciliação

**Situação:** protótipo de teste automatizado **estritamente interno ao PHPUnit**, herda as fases BILL-ASAAS-01..06 nas PRs [#540](https://github.com/rafhael100/imagine-crm/pull/540), [#541](https://github.com/rafhael100/imagine-crm/pull/541), [#542](https://github.com/rafhael100/imagine-crm/pull/542), [#544](https://github.com/rafhael100/imagine-crm/pull/544) e [#546](https://github.com/rafhael100/imagine-crm/pull/546). Não é uma integração de produção.

## Cenário executável e resultado esperado

`AsaasSandboxBillingJourneyTest` utiliza uma única instância do cliente HTTP Laravel com `Http::fake` e `preventStrayRequests()`, chave **fictícia**, referências UUID fictícias, CNPJ de laboratório e planos internos de referência. As **sete chamadas interceptadas**, em sequência, são:

1. `GET /v3/customers` — cliente ausente;
2. `POST /v3/customers` — cadastro sintético com `notificationDisabled=true`;
3. `GET /v3/payments` — cobrança Pix inexistente;
4. `POST /v3/payments` — criação fictícia do plano Básico, R$ 69, vencimento após 14 dias;
5. `GET /v3/customers` — mesmo cliente encontrado;
6. `GET /v3/payments` — mesma cobrança encontrada sem duplicação;
7. `GET /v3/payments/{id}` — estado simulado `RECEIVED` passa por conciliação **somente leitura**.

A suíte exige **exatamente dois POSTs** (um cliente e uma cobrança), com os demais GETs, todos no host Asaas Sandbox simulado. Verifica que dados fictícios ou credenciais não aparecem nas respostas resumidas, que nenhum `subscription`, cartão, desconto, juros, parcelas ou split é enviado, e que o evento de recebimento **nunca concede ativação**, quitação operacional, assinatura ou outro efeito de negócio.

Nenhum pedido fica persistido, nenhuma transação financeira é enviada, nenhum usuário é autenticado, nenhum CNPJ real é validado. O `POST` existe **apenas na resposta do mock** e não depende de uma conta Asaas ou credencial válida. O fluxo confirma o contrato das classes, mas **não** elimina o risco de criação concorrente em dois processos nem substitui lock e `UNIQUE` de PostgreSQL, retries duráveis, segregação por tenant, webhook efetivo, pagamento real, acesso Portal/CRM, faturamento, NFS-e ou rollback.

O workflow Asaas/PgSQL de laboratório foi ampliado para executar este teste em separado, antes da prova de inbox/worker. A aprovação depende de workflows verdes na HEAD exata e revisão de diff.

**Gates:** DRAFT, NO-MERGE, NO-DEPLOY; [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259) e [UX #416](https://github.com/rafhael100/imagine-crm/issues/416) continuam abertos. Regras de preços definitivos, estrutura do pedido e autorização real não são ratificadas por esse teste.

**Referências Asaas:** [criação de cobranças](https://docs.asaas.com/reference/create-new-payment), [listagem por referência externa](https://docs.asaas.com/reference/list-payments), [consulta por ID](https://docs.asaas.com/reference/retrieve-a-single-payment), [idempotência de Webhooks](https://docs.asaas.com/docs/como-implementar-idempotencia-em-webhooks).
