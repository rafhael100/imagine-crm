# CRM F3-43 — Concorrência real: quatro processos PHP, HTTP assinado e PostgreSQL 16

**Escopo exclusivamente GitHub Actions:** esta etapa testa a consistência de concorrência do receptor experimental F3-40/F3-41 do CRM Imagine. Não adiciona rota Laravel, migration, worker operacional, credencial produtiva, processamento de contatos ou confirmação de entrega a clientes.

## Lacuna corrigida na cobertura de testes

A F3-42 demonstrou que o código **real de laboratório** do Portal F119/F122 se comunica por HTTP com o receptor **real de laboratório** do CRM F3-41, usando PostgreSQL 18 e 16 separados. Até então as provas de idempotência do lado CRM eram principalmente sequenciais ou utilizavam um PHP built-in server de único processo.

A F3-43 aumenta a simultaneidade de maneira concreta: o CI executa `PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:19141`, verifica pelo sistema operacional a existência de **pelo menos quatro processos filhos**, e envia requisições HTTP concorrentes do Node 22. Cada processo PHP abre seu próprio PDO para o PostgreSQL 16 descartável, garantindo que a PK física e as transações sejam realmente disputadas.

## Matriz de 77 requisições HTTP assinadas por execução

1. **16 tentativas com nonces diferentes para uma única submissão**: exatamente uma resposta `LAB_MOCK_COMMITTED_FIRST`, quinze `LAB_MOCK_COMMITTED_LOGICAL_REPLAY`; no PostgreSQL há **1 submissão** e **16 tentativas**.
2. **12 tentativas simultâneas usando exatamente o mesmo nonce e mesma submissão**: apenas uma primeira gravação e onze rejeições `REQUEST_NONCE_REPLAY`; o banco contém **1 submissão** e **1 nonce**.
3. **12 submissões diferentes reutilizando um único nonce**: somente uma é persistida, onze recebem `REQUEST_NONCE_REPLAY`; o mesmo nonce não pode ser deslocado para outra identidade de negócio.
4. **12 solicitações assinadas com contexto de empresa ou destino adulterado**: todas recebem `403 PINNED_CONTEXT_CONFLICT` e deixam as tabelas inalteradas. Um MAC sintético válido não confere autorização por tenant.
5. **12 solicitações usando uma chave HMAC errada**: todas recebem `401 SYNTHETIC_AUTH_REJECTED` antes da gravação.
6. **Uma gravação com resposta perdida após commit e 12 leituras simultâneas assinadas em domínio de consulta**: a gravação retorna `503 UNKNOWN_OUTCOME_NO_RECEIPT`, sem recibo; as consultas podem comprovar **somente o registro da base sintética**, sem adicionar nonces ou executar um segundo envio. Os recibos de teste são verificados pelo cliente com chave exclusiva de recibos, e uma chave de requisição não os autentica.

O novo arquivo `tests/Labs/F3_43/concurrent_http_pg16.mjs` implementa a matriz por `Promise.all`, `fetch` local e consultas de contagem no PostgreSQL por `psql` local. Ele recusa execução fora do runner GitHub-hospedado, porta externa, PG distinto, falta de flags de CI ou chaves não aleatórias geradas no job. Os resultados HTTP nunca podem alegar `realCrmPersisted`, `tenantAuthenticatedForProduction`, `canAcknowledgeVisitor`, `canAutoRetryUnknownOutcome` ou `productionAuthorized`.

O workflow `.github/workflows/ci-f3-43-concurrent-idempotency.yml` executa toda a prova nas versões PHP **8.3 e 8.4**, com PostgreSQL **16** em contêiner descartável ligado a `127.0.0.1:5432`, roteador de laboratório em `127.0.0.1:19141`, quatro subprocessos PHP e chaves HMAC distintas por execução. Faz regressão da F3-40, valida sintaxe e bloqueia qualquer importação dessa fixture para código runtime Laravel. Nenhuma chamada externa de aplicação é realizada.

## Interpretação dos resultados

A prova será considerada **verde apenas quando o workflow da SHA final estiver concluído com sucesso em ambas as versões PHP**. O fato de o PostgreSQL preservar unicidade durante concorrência sintética não certifica o tratamento de dados reais, autenticação de servidores, segurança contra bots, criptografia, limite de concorrência operacional, revogação de credenciais, auditoria, rate limiting ou recuperação em múltiplos datacenters.

O próximo passo produtivo exige desenho de serviços e parecer independente SEG/DBA/LGPD para o receptor Laravel real, identidades de serviço confiáveis, autorização por tenant derivada do servidor, persistência de conteúdo consentido e recibos verificáveis em staging isolado. O gate F70 do Imagine Comercial permanece **bloqueado**.

**Sem alterações na `main`, DNS, VPS, deploy, contatos reais ou custos externos.** Todas as novas rotas e tabelas são apenas de laboratório no CI.
