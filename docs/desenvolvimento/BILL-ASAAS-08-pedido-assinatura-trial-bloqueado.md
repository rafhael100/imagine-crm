# BILL-ASAAS-08 — Pedido de assinatura mensal bloqueado até aceites

**Status: laboratório isolado / DRAFT / NO-MERGE / NO-DEPLOY.** Nenhum provedor ou cliente real foi acionado.

## Objetivo

Completar a etapa anterior de vínculo do pagador Asaas sem presumir que o pagador já está autorizado a ser cobrado. O CRM precisa congelar o plano, o valor, a data do primeiro vencimento e a referência da assinatura para futura revisão, mas **não** gerar uma assinatura, boleto, Pix, cobrança, NFS-e ou direito de acesso sem os aceites devidos.

A documentação oficial do Asaas, consultada em 14/11/2026, confirma que `POST /v3/subscriptions` exige `customer`, `billingType`, `value`, `nextDueDate` e `cycle`. A criação da recorrência não confirma recebimento de nenhum pagamento; cada cobrança tem um ciclo e ID próprios. Os próximos lançamentos precisam ser acompanhados por eventos de assinatura e pagamento:
- https://docs.asaas.com/reference/create-new-subscription
- https://docs.asaas.com/docs/creating-a-subscription
- https://docs.asaas.com/docs/subscription-events
- https://docs.asaas.com/docs/about-webhooks

## Implementado de fato nesta PR

**Catálogo e domínio:** `AsaasSubscriptionDraftSpec::preview()` usa exclusivamente `CommercialPlanReferenceCatalog`, sem preço vindo do cliente ou número flutuante, valores **provisórios** em centavos: Básico 6.900, Essencial 8.900, Profissional 14.900 e Empresarial 29.900 centavos/mês. Não concede desconto, valor final de contrato, recursos, direitos ou checkout. O método exige `PIX` ou `BOLETO` explícito (não coleta cartão), referência UUID, data de início do trial e primeiro vencimento não anterior ao fim da janela de 14 dias. Seu resultado sempre tem `price_homologated=false`, `terms_accepted=false`, `company_owner_verified=false`, `can_create_subscription=false`, `can_charge=false`, `can_activate=false` e `can_invoice=false`.

**Persistência PostgreSQL:** fixture `tests/Labs/billing/asaas-subscription-draft-ci.sql`, **fora** das migrations padrão, cria `crm_lab_asaas_subscription_drafts`. `organization_ref` (PK e FK para pagador já vinculado) e `external_reference` (única) vedam duas propostas divergentes e troca de referência. SQL CHECK congela o mapeamento de valores do catálogo referencial, `MONTHLY`, `BRL`, data mínima e **todos os estados e direitos bloqueados**.

**Papel separado:** `crm_billing_subscription_ci` recebe exclusivamente as permissões necessárias para consultar metadados sintéticos do pagador, inserir e ler o próprio rascunho. Não pode ler o ID Asaas do pagador, atualizar o rascunho, apagar, truncar, ativar, cobrar nem aprovar preço ou termo.

**Serviço de aplicação:** `LaboratoryAsaasSubscriptionDraft::persist()` recompõe o objeto a partir do catálogo controlado pelo servidor. Antes da inserção exige vínculo do pagador em estado `linked`, usando `INSERT ... SELECT` com unicidade física; repetição com os mesmos dados é reconhecida, mas plano, referência, data e valor divergentes são rejeitados conservadoramente. Não existe endpoint público, integração HTTP com Asaas, worker nem chamada de pagamento.

## Evidências a obter com a CI

- `AsaasSubscriptionDraftSpecTest`: quatro valores de referência, moeda em centavos, janela de 14 dias (inclusive mudança de ano e ano bissexto), vencimento inadequado, referência indevida, tipo não aprovado, proibições de direitos.
- `PostgresAsaasSubscriptionDraftTest`: organização sem pagador ou com vínculo pendente não pode gerar rascunho; associação `linked` permite um único; duplicidade em duas conexões, conflito com transação aberta, rollback, colisão da mesma referência por duas organizações e imutabilidade do snapshot; flags do banco permanentemente falsas; tentativas de injetar preço, contrato ou permissão falham; papéis e rotas continuam bloqueados.
- Suite de Asaas em PostgreSQL 16 descartável após `scripts/ci/verify-ephemeral-postgres.php --verify`, mantendo intocado o inventário de RLS da CI original. As suites existentes de F0, Trial, UX, Comercial, Sites, Agentes e HTTPS também precisam passar na **HEAD exata**.

## Bloqueios de produção

1. **Comercial:** homologar os valores e características finais dos planos e política de descontos, primeira cobrança e retenção. Esta PR **não** autoriza usar referências como preços de contratação.
2. **Contrato:** aceite explícito, termos datados, consentimento quando aplicável, comprovação de representação/CNPJ, auditoria assinada e armazenamento seguro.
3. **Backend:** organização autenticada, IAM/MFA, quotas e RLS com isolamento por empresa, transação de ativação atômica, idempotência do envio da assinatura e reconciliação de API.
4. **Asaas:** Sandbox com chave de teste em cofre, criação controlada de assinatura real, retorno do ID `sub_...`, acompanhamento separado de eventos de cobrança/assinatura, chargebacks e falha de geração.
5. **Infra e LGPD:** TLS de domínio, DNS, staging privado na VPS Leaseweb, backups/restore/rollback, critérios de segurança e privacidade SEG/DBA #27, CORE/LGPD #259 e UX #416.

**Não converter as fixtures/testes em serviço público sem esses gates.** `main`, VPS, DNS, banco de clientes, credenciais e pagamentos reais não foram tocados.
