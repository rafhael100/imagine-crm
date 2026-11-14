# BILL-MULTIPSP-01 — Contrato Portal → CRM multi-gateway (offline, sem dinheiro)

Branch empilhada sobre integração do CRM com Billing Asaas (#572). Não altera rotas, configurações de produção, adapters financeiros nem criação de faturas.

\`PortalGatewayDecisionEnvelope\` estabelece o esquema \`imagine.payment-routing.review.v1\`: referência de organização/pedido, PSP selecionado (Asaas/C6), modalidade, centavos inteiros, tarifa estimada, revisão da política e versão comercial, assinado com HMAC SHA-256 separado por domínio.

Os flags \`can_charge=false\` e \`can_activate=false\` são obrigatórios. Rejeita adulteração financeira, replay entre organizações, campos secretos extras, PSP desconhecido e assinatura inadequada. Chaves de teste são sintéticas; produção exigirá segredos em vault rotacionáveis, assinatura de emissor autenticado, controle antirreplay (nonce/TTL), IAM, RLS por organização e ledger de conciliação.

Esta assinatura protege um **formato de mensagem de revisão**, não autoriza criação/compensação de pagamentos reais. Integração operacional só após Portal e CRM firmarem contrato versionado, persistência transacional, rastreamento por gateway e conciliação externa verificada. Nenhuma API C6 real é presumida.

**DRAFT / NO-MERGE / NO-DEPLOY / NO-CHARGE / NO-PII**.
