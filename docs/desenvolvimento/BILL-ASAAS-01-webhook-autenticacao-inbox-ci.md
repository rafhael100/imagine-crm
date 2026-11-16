# BILL-ASAAS-01/02 — Asaas: autenticação de Webhook e inbox idempotente

**Fase:** base técnica de integração financeira, somente laboratório GitHub. **Não habilitar uma rota de recebimento nem fazer cobrança real.**

## Fontes oficiais conferidas em 14/11/2026

- Asaas, [opções do Webhook](https://central.ajuda.asaas.com/hc/pt-br/articles/55669339031835): token do Webhook (`authToken`) de 32 a 255 caracteres, distinto da API key; o header de entrada é `asaas-access-token`.
- Asaas, [autenticação da API](https://docs.asaas.com/docs/autentica%C3%A7%C3%A3o-1): saída para o Asaas usa header `access_token`, com bases separadas `https://api-sandbox.asaas.com/v3` e `https://api.asaas.com/v3`. **Não confundir** os dois segredos.
- Asaas, [idempotência de Webhooks](https://docs.asaas.com/docs/como-implementar-idempotencia-em-webhooks): entrega *at least once*, reutilização do campo `id` e gravação durável antes de responder 200, com processamento posterior.
- Asaas, [eventos de cobranças](https://docs.asaas.com/docs/webhook-para-cobrancas) e [eventos de assinaturas](https://docs.asaas.com/docs/eventos-para-assinaturas): `PAYMENT_CONFIRMED` não é sinônimo de liberação de saldo, `PAYMENT_RECEIVED` pode ocorrer depois; assinaturas geram cobranças separadas.

## Implementado

1. `app/Core/Billing/AsaasWebhookEnvelope.php` é um **núcleo PHP puro**, sem efeitos: valida token de entrada por `hash_equals`, rejeita cabeçalhos ambíguos, JSON malformado e tipos não reconhecidos. O limite inicial é de 64 KiB. Valida `id`, `event`, `payment.id` ou `subscription.id`; ignora novos campos anexados pelo Asaas. O conteúdo original **não é retornado, logado ou armazenado**.
2. Produz apenas `eventKey` (HMAC-SHA256 separado por domínio do `id` do evento), `resourceKey` (HMAC do ID do pagamento ou assinatura), tipo e HMAC-SHA256 do corpo, com separação de domínio para reduzir ataques de dicionário sobre informações pessoais presentes no JSON. O segredo HMAC é distinto do token do Webhook. Esses identificadores continuam privados.
3. `AsaasWebhookInboxCandidate.php` insere atomicamente sob `PRIMARY KEY(event_key)` usando `INSERT ... ON CONFLICT DO NOTHING`. **Apenas para PostgreSQL descartável em GitHub Actions**, via role `crm_billing_ci` com `INSERT` nas cinco colunas necessárias; sem permissão SELECT/UPDATE/DELETE/TRUNCATE, sem dados pessoais, sem credenciais reais, sem valor da cobrança.
4. O estado permanente do registro nesta versão é `pending_review`. Nenhum fluxo pode transformá-lo em conta, permissão, pagamento ou contrato. Não há Worker, retry externo, assinatura criada ou nota fiscal emitida.
5. Os testes verificam autenticação inválida, replay, duas conexões PostgreSQL, inserção concorrente sem commit, rollback, não exposição de dados e rota HTTP **instalada exclusivamente em PHPUnit**.

## Decisões ainda necessárias para lançamento

- **Cadastro e contratação:** identidade, CNPJ, contato e representação empresarial verificados; aceites de LGPD e termos; criação atômica da empresa e da assinatura interna.
- **Cobrança:** políticas comerciais e preços definitivos; criação/associação de cliente e assinatura Asaas com idempotência local e API sandbox, posteriormente produção autorizada.
- **Reconciliar:** controle de vínculo entre IDs de clientes/assinaturas/cobranças locais e Asaas, consulta server-to-server, ordenação temporal, estorno/chargeback, processamento assíncrono e recuperação de falhas.
- **Operação:** migrations reais aprovadas para o banco alvo, segredos em cofre, segregação de contas sandbox/produção, TLS, rota pública autenticada, monitoramento, backups/restore e suporte a retenção legal adequada. Enfileirar/processar antes de HTTP 200 só após persistência garantida. Receber webhook **não equivale a direito de acesso**, incluindo `PAYMENT_RECEIVED`.
- **Segurança:** tratar JSON ambíguo (inclusive chaves duplicadas) e nomes de cabeçalho duplicados na fronteira HTTP real, revisar ataques e payloads desconhecidos; AppSec/DBA/LGPD independente. Não copiar headers/valores da CI para produção.
- Revisar gates de segurança e privacidade: issues #27, #259, #416.

## Como testar

A CI dedicada `.github/workflows/ci-crm-billing-asaas.yml` roda em PostgreSQL 16 efêmero com `scripts/ci/verify-ephemeral-postgres.php --verify` **antes** da fixture DDL. Nenhuma migration padrão foi adicionada: `tests/Labs/billing/asaas-webhook-inbox-ci.sql` não é migrável em `php artisan migrate`.

```bash
php artisan test --filter=AsaasWebhookEnvelopeTest --no-ansi
php artisan test --filter=PostgresAsaasWebhookInboxTest --no-ansi
```

**NO-GO / NO-MERGE / NO-DEPLOY:** a evidência prova uma fronteira de entrada e idempotência física, não um cliente real nem integração de API Asaas. `main`, VPS, DNS, chaves e serviços de cobrança permanecem inalterados.

---

## BILL-ASAAS-03 — consumidor persistente sem efeitos financeiros

A etapa seguinte separa o recebimento do processamento para suportar entrega *at least once* e workers concorrentes com recuperação:

- `AsaasWebhookReviewWorkerCandidate` recebe **somente** o papel `crm_billing_review_ci`, diferente do receptor.
- O revisor consulta exclusivamente `event_key`, `event_kind`, `state` e `observed_at`, sem acesso ao `body_digest` ou `resource_key`. Pode atualizar apenas a coluna `state`. Não pode inserir, apagar, truncar, cobrar, conciliar ou conceder acesso.
- Em transação PostgreSQL, `SELECT ... FOR UPDATE SKIP LOCKED` coordena vários workers; a primeira revisão marca `reviewed_no_effect`, e uma segunda revisão não processa novamente o mesmo registro. Se a transação falha, um rollback devolve `pending_review`, permitindo repetir.
- A verificação `PostgresAsaasWebhookReviewWorkerTest` testa dois workers independentes, lock, rollback, replay, grants restritos e rejeição de papéis errados.

**Importante:** `reviewed_no_effect` é um estado **exclusivamente de teste**, não confirmação de pagamento, fatura ou acesso. O próximo desenvolvimento real exige uma máquina de estados de pagamentos vinculada a uma cobrança local preexistente, consulta autenticada à API Asaas, controles de ordem/estorno, auditoria e revisão dos contratos/comercial. Nenhuma rota ou worker recorrente foi ativado. A migração está restrita a `tests/Labs/billing/` e a CI é descartável.
