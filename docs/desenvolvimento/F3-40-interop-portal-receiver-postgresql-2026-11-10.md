# CRM F3-40 — Receptor experimental do contrato Portal F121 com classes **reais do CRM**

**Escopo estritamente CI:** PHP 8.3/8.4, PostgreSQL 16 efêmero no GitHub Actions, dados sintéticos. Sem rota Laravel, migração operacional, API, chave real, payload de cliente, contato, consentimento, worker, ação de negócio, assinatura de produção ou implantação.

## Motivo e compatibilidade

O Portal Imagine F121, PR #304, já demonstra entrega entre **dois bancos efêmeros**, com HMAC-SHA256 por domínio separado e recibo simulado assinado após gravação. O teste F3-40 incorpora uma **contraparte no próprio repositório do CRM**, consumindo os componentes existentes `App\Core\Leads\SyntheticLeadEnvelopeV1Inspector`, `LeadSubmissionIdentity`, `SyntheticIdempotencyClaim` e `SyntheticLeadReceiptMacInspector`. Isso evita manter mais uma versão manual e divergente do contrato de recibos no lado CRM.

Uma correspondência no verificador HMAC F3-15 significa apenas **integridade sintética com chave de teste**. Não confere autorização, validade de origem ou prova de gravação do CRM operacional.

## Componentes adicionados

- `tests/Labs/F3_40/crm_handoff_pg16.sql`: schema efêmero `crm_portal_handoff_ci`, tabela `metadata_receipts` com seis UUIDs de escopo + timestamp, tabela `request_nonces` com FK diferida e PK por empresa+nonce, políticas `FORCE RLS` para dois tenants fictícios e roles `NOLOGIN/NOBYPASSRLS` com apenas SELECT/INSERT.
- `tests/Labs/F3_40/SyntheticPortalHandoffReceiver.php`: receptor PHP **somente em testes**, sem HTTP. Recebe envelope técnico F3-11, fixture separada de vínculo, tempo, nonce e HMAC; confere domínio `imagine.portal.synthetic-service-request.v1`. Se válido, grava nonce e metadados em transação PostgreSQL 16 com role A fixa; rejeita replay de nonce e alteração de contexto original. Depois do commit, cria recibo técnico com domínio **distinto**, `imagine.lead-intake.synthetic-receipt.v1`.
- O mesmo receptor oferece `lookup()` apenas de leitura, assinado com domínio exclusivo `imagine.portal.synthetic-crm-lookup.v1`. Se o registro existe e coincide com vínculo/site/form/destino, assina recibo sintético após consultar o banco; se não, nega confirmação.
- `tests/Labs/F3_40/test_handoff_pg16.php`: usa **o verificador real do CRM F3-15** para verificar o recibo compatível com Portal F121, incluindo testes de chave incorreta, prazo expirado, assinatura/versão adulterada, cliente tentando trocar tenant, dados pessoais inesperados, nonce repetido, nova tentativa sem novo lead, destino original pinado e recuperação após descartar a resposta de um commit anterior.
- `.github/workflows/ci-f3-40-portal-handoff.yml`: runs PHP 8.3/8.4 contra PostgreSQL 16 em `127.0.0.1` em base recém-criada `imagine_crm_handoff_ci`, sem Composer, migrations operacionais ou route Laravel. Verificação explícita proíbe referências ao laboratório em `routes/`, `app/`, `bootstrap/` e `database/migrations/`. A base e os papéis desaparecem ao final do job.

## Garantias e limites

O banco experimental persiste metadados verdadeiros **somente no sentido de executar transações SQL no CI**. As respostas explicitamente mantêm `mockCommitted` como estado de laboratório, `realCrmPersisted=false`, `tenantAuthenticatedForProduction=false`, `canAcknowledgeVisitor=false`, `canAutoRetryUnknownOutcome=false`, `productionAuthorized=false`. O status `LAB_MOCK_COMMITTED_FIRST` não é recibo de lead real.

O HMAC não assina o corpo pessoal do orçamento, não tem chave em cofre, TLS/mTLS, identidade de cliente, autenticação de serviço robusta, revogação, autorização de membership, confirmação operacional, auditoria LGPD ou reconciliação em staging. Uma consulta posterior só comprova **o estado da base mock**; não resolve consenso ou falhas de partição reais.

## Próximos marcos

Para iniciar integração real, CRM e Portal ainda precisam do desenho aprovado de API interna, IAM de serviço confiável, escopos por empresa derivados no servidor, persistência criptografada de dados pessoais e consentimento, idempotência transacional, recibos reais verificáveis por lookup, controle de retry/hold, observabilidade, rollback e parecer SEG/DBA/LGPD. A F70 do Imagine Comercial continua **bloqueando captação pública**.

Base `dev/fast-track-crm-2026-11-04`, PR draft em DEV; não foram usados dados de clientes, não houve merge na `main`, deploy, VPS, DNS, chamadas externas ou gastos.
