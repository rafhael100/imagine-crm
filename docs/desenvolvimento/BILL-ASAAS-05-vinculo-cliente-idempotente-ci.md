# BILL-ASAAS-05/06 — Vínculo local durável organização ↔ Asaas (laboratório)

**Estado:** código de laboratório (CI PostgreSQL + HTTP totalmente simulado), em revisão DRAFT. Nenhum ambiente de produção ou homologação compartilhada pode executar este módulo.

## Lacuna de negócio que esta entrega ataca

O cliente HTTP Asaas Sandbox da etapa BILL-ASAAS-04 consultava por `externalReference` e criava um pagador fictício quando a lista estava vazia. Essa consulta isolada **não protege contra duas requisições concorrentes**, resposta incerta de POST ou queda do processo entre criar o pagador e gravar seu identificador local.

A implementação adiciona uma reserva imutável persistente **antes** da requisição. Uma tentativa repetida não pode executar novo POST só porque o processo perdeu a resposta.

## Componentes

- `tests/Labs/billing/asaas-customer-link-ci.sql` define `crm_lab_asaas_customer_links`: `organization_ref` UUID com chave primária, `external_reference` UUID exclusivo, `provider_customer_id` único e opcional, `state` e `reserved_at`. Estados fechados: `pending_lookup`, `review_required`, `linked`. Restrição SQL impede registro `linked` sem `cus_...`. Nenhuma informação fiscal, contato, nome, pagamento ou segredo.
- Papel `crm_billing_link_ci` de teste, distinto dos papéis de Webhook. Permite inserir apenas organização/referência/tempo, consultar os identificadores sintéticos e alterar apenas estado/ID do pagador. Não pode alterar organização/referência, apagar ou truncar. **As permissões de leitura desta tabela sintética são mais amplas que seriam aceitáveis em produção**; exigir RLS/SECURITY DEFINER e autorização por mandatos para uma implementação real.
- `LaboratoryAsaasCustomerLink::reserve()` usa `INSERT ... ON CONFLICT DO NOTHING` para arbitrar concorrência. O primeiro processo recebe `new_reservation` uma única vez. Os seguintes recebem `existing_pending`, `needs_review` ou `already_linked` sem receber o identificador externo. Se o mesmo `externalReference` está reservado por outra organização, a operação falha de modo conservador.
- `bind()` registra `cus_...` apenas na mesma organização/referência, quando o estado é `pending_lookup`; aceita repetição idêntica mas rejeita substituição ou uso do mesmo pagador por outra organização. Nunca gera assinatura, pagamento ou acesso.
- `LaboratoryAsaasCustomerHandoff::run()` reserva primeiro; somente a primeira reserva consulta/cria um **pagador fictício** via `AsaasSandboxCustomerGateway`. Qualquer falha de HTTP, validação do retorno ou persistência do vínculo passa a `review_required`, quando possível. Retentativas permanecem bloqueadas até reconciliação independente. Não implementamos uma ação de “desbloquear” automaticamente.

## Testes na CI descartável

`PostgresAsaasCustomerLinkTest` cobre duas conexões, unicidade de organização e `externalReference`, imutabilidade do ID Asaas, colisão de pagador, rollback e proteção contra papéis administrativos. Com `Http::preventStrayRequests()` e `Http::fake()` comprova que primeiro `GET` + `POST` ocorrem **uma vez**, que uma resposta ambígua não gera segunda tentativa, e que pagador existente é reutilizado sem POST. Verifica ausência de rotas de cadastro/checkout.

O teste roda **somente** após `scripts/ci/verify-ephemeral-postgres.php --verify` em PostgreSQL 16 descartável, na workflow `ci-crm-billing-asaas.yml`, sem migração na pasta `database/migrations`.

## Bloqueadores para uso real

1. **Identidade e LGPD:** organização autenticamente cadastrada, CNPJ válido e titularidade/representação verificados, base legal e consentimento quando aplicável, retenção e auditoria.
2. **Trava e autoridade:** roles/RLS com isolamento por organização, conflito entre requisição e reconciliação de Webhook, trilha imutável de tentativas e acesso administrativo segregado.
3. **Provedor real:** credenciais Asaas Sandbox em cofre, referência externa permanente por empresa e reconciliação server-to-server após resposta incerta. Não recriar cliente sem prova de inexistência.
4. **Faturamento:** catálogo e aceites finais, assinatura/parcelas, estados de cobrança, estorno, nota fiscal e idempotência das APIs de pagamento.
5. **Operação:** homologação privada na VPS Leaseweb, TLS de domínio, monitoração, restore/backup, rotação segura de chaves, aprovação SEG/DBA #27, CORE/LGPD #259 e UX #416.

**DRAFT / NO-MERGE / NO-DEPLOY:** nenhum endpoint, web hook real, envio Asaas, API key operacional, cliente ou pagamento real, tenant, contratação, VPS, DNS ou alteração da branch `main` nesta entrega.
