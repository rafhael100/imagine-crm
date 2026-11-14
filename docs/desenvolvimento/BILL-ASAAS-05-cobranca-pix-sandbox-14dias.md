# BILL-ASAAS-05 — cobrança PIX de laboratório no Asaas Sandbox

**Estado:** candidato em PR DRAFT, **NO-MERGE / NO-DEPLOY / NO-GO financeiro**. Nenhuma operação foi enviada a uma conta Asaas nesta entrega; o teste intercepta **todas** as chamadas via Laravel `Http::fake` e `preventStrayRequests`.

## Objetivo funcional e encadeamento

Continua as PRs [#540](https://github.com/rafhael100/imagine-crm/pull/540) (webhook autenticado/inbox idempotente), [#541](https://github.com/rafhael100/imagine-crm/pull/541) (worker sem efeitos) e [#542](https://github.com/rafhael100/imagine-crm/pull/542) (cliente Asaas Sandbox fictício). A classe `AsaasSandboxPixPaymentGateway` executa **somente em `local/testing`**, quando as chaves separadas `CRM_ASAAS_SANDBOX_ENABLED` e `CRM_ASAAS_SANDBOX_PAYMENTS_ENABLED` são ambas `true` e a chave fornecida começa por `$aact_hmlg_`. Os testes usam uma credencial completamente falsa e nunca acessam a Internet.

O fluxo exige a estrutura de saída reconhecida do cliente fictício da Fase 04 — flags `can_charge=false`, `can_activate=false`, `can_create_subscription=false` e `company_owner_verified=false`; só aceita referência prefixada `LAB-IMAGINE-ORDER-` seguida de UUID; valida o prazo ilustrativo de **14 a 30 dias** entre início fictício de trial e vencimento. Obtém o preço exclusivamente do `CommercialPlanReferenceCatalog::preview(plan,1)`, com valores inteiros de centavos; **não aceita valor enviado pelo navegador**. Referências comerciais ainda **não estão homologadas**.

Em Sandbox, o cliente executa `GET /v3/payments?externalReference=...&customer=...&limit=2`; se existe exatamente uma cobrança `PIX/PENDING` para o mesmo pedido, pagador, vencimento e valor, a reutiliza. Havendo zero resultados, constrói `POST /v3/payments` para **uma única cobrança Pix de teste**, sem assinatura, parcelamento, desconto, juros, split ou notificações externas deliberadas. Se o GET estiver indisponível, ambíguo, paginado ou divergente, **não faz POST**; após resposta de POST duvidosa, **não tenta novamente automaticamente**. A igualdade do `externalReference` é um controle parcial, **não uma chave idempotente transacional garantida pelo provedor**.

O retorno contém somente ID técnico Asaas (fictício no teste), código de estado `sandbox_pix_payment_found|created`, plano, centavos e flags negativas. O estado da cobrança Asaas, mesmo que futuramente pago, **não libera acesso/tenant/assinatura**. A duração do trial informada pelo chamador **não é prova autenticada de direito**.

## Evidência e proteções

`tests/Feature/AsaasSandboxPixPaymentGatewayTest.php` usa Laravel HTTP fake em todos os cenários. Testa os **quatro valores de referência (R$69, R$89, R$149 e R$299)**, pagamento existente, criação controlada, GET e POST na ordem, token de API separado de token do webhook, método, destino Sandbox, impedimento de efeitos financeiros, bloqueio de CNPJ/nome/operação real, preço desconhecido, vencimento inválido, cliente de origem duvidosa, respostas conflitantes, status recebido, falha de rede e prevenção de reenvio automático. O workflow `ci-crm-billing-asaas.yml` invoca a suíte e os testes prévios de Postgres16 inbox/worker.

### Gates obrigatórios para pagamento real

Sem endpoint público, checkout, dados reais, Asaas API key, segredos de produção, migração Laravel, vínculo `tenant↔order↔customer↔payment` durável, lock e deduplicação do pedido em PostgreSQL, autorização e consentimento verificados, conciliação server-to-server, política de descontos, preços definitivos, NFS-e, retenção auditável, testes de rollback ou revisão de risco, **o fluxo permanece NO-GO**.

Antes de integração de cobrança real será preciso provar idempotência em banco próprio e conciliação de status contra a API da conta, separar eventos `PAYMENT_CONFIRMED` de `PAYMENT_RECEIVED` e resistir a estornos/chargebacks. A API do Asaas documenta `customer`, `billingType`, `value`, `dueDate` e `externalReference` na criação, e filtros `customer/externalReference` na listagem. A criação não significa recebimento.

**Referências oficiais:** [Criar cobrança](https://docs.asaas.com/reference/create-new-payment), [Listar cobranças](https://docs.asaas.com/reference/list-payments), [Webhooks idempotentes](https://docs.asaas.com/docs/como-implementar-idempotencia-em-webhooks).

**Gates que continuam abertos:** SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259), UX [#416](https://github.com/rafhael100/imagine-crm/issues/416). Sem VPS, DNS, credenciais, cadastro, assinatura ou deploy.
