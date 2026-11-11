# CRM F3-41 — HTTP local real → classes CRM existentes → PostgreSQL 16 descartável

**Escopo: somente GitHub Actions em `127.0.0.1`.** Não é uma API operacional, rota Laravel, credencial real, migração, envio de contato ou confirmação real de CRM.

## Resultado procurado

A F3-40 já valida o contrato de metadados do Portal F121 usando as classes **reais do CRM** (`SyntheticLeadEnvelopeV1Inspector` e `SyntheticLeadReceiptMacInspector`), com gravação transacional em um PostgreSQL 16 descartável, nonce e recibo HMAC experimental assinado após o commit. A F3-41 conecta esse componente a um **servidor HTTP PHP embutido em loopback**, usando requisições assinadas no próprio CI, em vez de testar apenas métodos PHP.

## Arquivos

- `tests/Labs/F3_41/router_crm_loopback.php`: roteador PHP interno de teste que rejeita qualquer ambiente que não seja GitHub-hosted CI com banco `imagine_crm_handoff_ci`, host local e chave sintética. Permite somente `POST /__lab/crm/ingest` e `POST /__lab/crm/readback`, JSON, corpo até 2 KiB e headers `X-Lab-Issued-At`, `X-Lab-Expires-At`, `X-Lab-Nonce`, `X-Lab-Signature`. Reutiliza o receptor F3-40 sem alterar as classes do domínio.
- `tests/Labs/F3_41/test_http_handoff_pg16.php`: cliente PHP que efetua **requisições HTTP reais** ao loopback e verifica erros 400/401/403/404/413/415/422, primeira gravação em PG, replay de nonce com HTTP 409, repetição lógica com nonce diferente sem duplicação e recibo sintético reconhecido pelo verificador real CRM F3-15. O teste também utiliza assinatura de **domínio diferente para lookup**, recusando reutilizá-la como requisição de gravação.
- `.github/workflows/ci-f3-41-portal-handoff-http.yml`: matriz PHP 8.3 e 8.4, PostgreSQL 16 em contêiner descartável, inicializa a F3-40 e cria duas chaves de HMAC temporárias distintas por job. O servidor é iniciado somente em `127.0.0.1:19141`, fechado após o teste e nunca instalado na aplicação Laravel. O workflow falha se houver referência a essa fixture nos diretórios operacionais.

## Teste de falha de comunicação

O primeiro banco grava a submissão técnica; o cliente pode **perder a resposta HTTP mesmo depois do commit**, recebendo `503 UNKNOWN_OUTCOME_NO_RECEIPT`. Isso não autoriza reenvio, liberação de lead ou confirmação ao visitante. O cliente gera outra assinatura com domínio exclusivo `imagine.portal.synthetic-crm-lookup.v1`, consulta por leitura no receptor e recebe novo recibo de teste só se a linha de metadados estiver presente no PostgreSQL. A consulta não grava um segundo nonce de requisição.

A resposta em todos os cenários expõe somente indicadores de **teste**; mesmo `202 Accepted` e `200` de consulta mantêm `realCrmPersisted=false`, `canAcknowledgeVisitor=false`, `canAutoRetryUnknownOutcome=false` e `productionAuthorized=false`. O recibo `LAB_MAC_MATCH_UNTRUSTED` demonstra integridade do contrato sintético, não credenciais produtivas, identidade de destino ou entrega de contatos.

## Pendências externas e bloqueios

Para uma **integração operacional verdadeira**, Portal e CRM ainda precisam de identidade de serviço confiável (sem chave fornecida pelo solicitante), vínculos de tenant derivados do servidor, HTTPS/mTLS ou token validado pelo provedor, política de rotação e revogação de chaves, validação e retenção de dados pessoais com fundamento LGPD, idempotência persistente de leads com payload, worker de outbox, reconciliação de timeouts em infraestrutura privada e parecer técnico SEG/DBA. A F70 do Imagine Comercial continua **bloqueada**.

Nenhuma rota Laravel, migration operacional, pessoa/cliente real, `main`, VPS, DNS, produção ou custo externo foi alterado. Branch e PR draft filhas da F3-40 na DEV.
