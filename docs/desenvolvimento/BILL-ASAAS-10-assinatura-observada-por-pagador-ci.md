# BILL-ASAAS-10 — Assinatura existente vinculada ao pagador certo, sem direito de acesso

**Estado:** implementação experimental em PostgreSQL de GitHub Actions, com HTTP Asaas simulado. **DRAFT / NO-MERGE / NO-DEPLOY.**

## Por que é necessário

A PR #551 permite consultar por `externalReference` uma assinatura Asaas Sandbox e confirmar que o campo `customer` corresponde ao pagador esperado. Mas essa informação ainda não era associada a um registro durável da organização no banco. Em sistemas concorrentes, repetir o mesmo evento de assinatura ou perder a resposta pode causar nova tentativa desnecessária. A documentação oficial [Retries e idempotência](https://docs.asaas.com/docs/retries-e-idempot%C3%AAncia) recomenda criar um identificador persistente antes da chamada e reconciliar antes de qualquer repetição.

## Componente entregue

- SQL em `tests/Labs/billing/asaas-subscription-observation-ci.sql` cria tabela **exclusivamente de testes** `crm_lab_asaas_subscription_observations`: PK `organization_ref` com FK do rascunho, UNIQUE `external_reference` e UNIQUE `provider_subscription_id`. `CHECK` garante estado `observed_no_rights`, `can_charge=false`, `can_activate=false`, `payment_verified=false` e `terms_accepted=false`.
- Role CI `crm_billing_subscription_review_ci`, distinta da role de quem criou o rascunho. Lê somente dados do pagador necessários à comparação e insere colunas sintéticas na tabela de observações; não pode UPDATE, DELETE nem TRUNCATE. As permissões para ver identificadores do pagador são aceitáveis **apenas no laboratório sem tenants reais**; exigem RLS e escopo por organização em staging/produção.
- `LaboratoryAsaasSubscriptionObservation::reconcile()` verifica ambiente GitHub CI e papel, recusa transação SQL externa em aberto, exige rascunho imutável `blocked_review` e pagador `linked`, verifica associação prévia antes de consultar o provedor e chama **somente o GET** do `AsaasSandboxSubscriptionLookup`. Resultado confirmado e consistente é registrado via `INSERT ... SELECT ... ON CONFLICT DO NOTHING`.
- Resultados ausentes/inconclusivos vão a `synthetic_review_required` sem criar nova assinatura. Duas conexões concorrentes não conseguem criar duas associações para a mesma organização ou dar ao mesmo `sub_...` duas organizações; colisões divergentes falham fechadas.
- O método **não recebe ou expõe** valores, documentos, dados de contato, identificadores do Asaas em seu resultado, status financeiro ou chaves de API. Jamais considera `ACTIVE` como assinatura paga ou conta ativa.

## Evidência de validação

`PostgresAsaasSubscriptionObservationTest` exerce PostgreSQL 16 real e `Http::fake / Http::preventStrayRequests`. Testa:

1. Primeira observação persistida e segunda execução idempotente sem novo GET.
2. Ausência de rascunho ou referência externa divergente sem acesso externo.
3. Resultado ausente, erro de API ou pagador divergente deixam revisão sem persistência.
4. Tentativa de vincular um `sub_...` a duas organizações, bloqueada fisicamente.
5. Papel de banco e transação não autorizados, negados antes da requisição.
6. Direitos e termos sempre falsos, ausência de checkout e endpoints reais.

Workflow `.github/workflows/ci-crm-billing-asaas.yml` roda a fixture SQL somente **depois** do preflight `scripts/ci/verify-ephemeral-postgres.php --verify`, das tabelas do pagador e do rascunho, e sem tocar no inventário das migrations operacionais.

## Limitações essenciais para conclusão do CRM

O próprio Asaas orienta que criar uma assinatura não confirma pagamento; os valores e status de cada cobrança gerada precisam de conciliação própria: <https://docs.asaas.com/reference/create-new-subscription>. Essa etapa **não** cria assinaturas nem pagamentos. Não possui cofre de credenciais, assinatura contratual, preço final homologado, titularidade/representação verificada, login, endpoint autenticado, migração operacional com isolamento por tenant ou VPS de homologação.

Próximas entregas: workflow operacional de aceite contratual e validação de preços, associação transacional de tenants, Sandbox Asaas autenticado e revisado, processamento de eventos de pagamento por máquina de estados, auditoria e NFS-e quando cabível. Gates de segurança e privacidade SEG/DBA #27, CORE/LGPD #259, UX #416 continuam abertos. **Sem merge, deploy, custo, cobrança ou liberação de clientes.**
