# BILL-ASAAS-04 — Cliente Asaas Sandbox, consulta e reutilização sem cobrança

**Estado: DRAFT / somente sandbox/HTTP falso em CI / nenhum cliente real.**

## Fonte de verdade

Documentação oficial Asaas revisada em 14/11/2026:
- [Autenticação API](https://docs.asaas.com/docs/autentica%C3%A7%C3%A3o-1): saída usa `access_token` (não `asaas-access-token`); `User-Agent` obrigatório para contas recentes; ambiente de sandbox `https://api-sandbox.asaas.com/v3`.
- [Cadastro de cliente](https://docs.asaas.com/docs/creating-customers): `POST /v3/customers`, `externalReference` para vincular ao registro interno, retorno `cus_...`, necessidade de consultar e reutilizar antes de recriar.
- [Consulta de clientes](https://docs.asaas.com/reference/list-customers): `GET /v3/customers?externalReference=...` tem filtro documentado.

## Implementação

`AsaasSandboxCustomerGateway` é um cliente HTTP Laravel **limitado ao domínio Asaas Sandbox** e desligado por padrão: só instancia em ambiente `local/testing` se `CRM_ASAAS_SANDBOX_ENABLED=true`, exige chave com prefixo de Sandbox `$aact_hmlg_`. Não utiliza a chave do webhook. O método `findOrCreateSyntheticCustomer()` aceita somente identidade de teste com nome iniciando em `LAB-IMAGINE-`, CNPJ fixo de fixture e referência UUID; sem e-mail, telefone, dados de cartão ou notificações (`notificationDisabled=true`).

Processo: consulta Asaas por `externalReference` (limite 2); se há um resultado identificável, reutiliza `cus_...`; se não há, envia uma criação via `POST /v3/customers`; se há duplicidade, paginação, resposta malformada ou erro de conexão, falha de modo conservador sem tentar repetição automática de POST. Nunca cobra, ativa trial ou cria assinatura.

## Limites críticos

- Não foi executada chamada real ao Asaas. Testes usam `Http::fake` e `Http::preventStrayRequests()`; chaves são fictícias.
- Consulta antes de criar **não** garante unicidade sob corrida, indisponibilidade ou falha parcial. Para produção, exigir registro de vínculo local único por CNPJ/organização, trava/transaction por externalReference, persistência durável do `cus_...` e reconciliação após timeout.
- O input aceita exclusivamente dados de laboratório; CNPJ real, nome, contato, base legal e representatividade continuam proibidos até LGPD e mecanismos de segurança homologados.
- Não há endpoint público, contrato, plano, valor, assinatura, pagamento, Pix Automático, boleto, nota fiscal, permissão de cliente ou credencial válida conectada. `main`, Leaseweb, DNS e banco operacional inalterados.

`php artisan test --filter=AsaasSandboxCustomerGatewayTest --no-ansi` prova a montagem de `GET/POST`, credenciais isoladas, resposta sanitizada e negação de duplicidade ambígua. **Essa evidência não equivale a integração em Sandbox real**; para tal, a Direção deve configurar credenciais em cofre e permitir a homologação controlada, sem compartilhar segredos em chat ou repositório.

## Próximos passos de implementação

1. Criar registro local idempotente de vínculo Asaas/organização após autenticação, CNPJ/representação/consentimento confirmados, com testes de concorrência e restore.
2. Executar integração real no Asaas Sandbox com segredo gerenciado e conta de teste.
3. Somente então criar contrato e assinatura recorrente, com a primeira cobrança após escolha explícita do plano e fim do trial, conciliar webhooks, estornos, atrasos e NFS-e.
4. Validar ambientes e parecer independente SEG/DBA/LGPD (issues #27 e #259), homologar UX (#416) e infraestrutura Leaseweb.
