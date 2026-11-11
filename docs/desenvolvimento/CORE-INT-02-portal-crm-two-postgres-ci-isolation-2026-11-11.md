# CRM CORE-INT-02 — Laboratório cruzado Portal → CRM isolado na DEV

**Objetivo:** retirar os testes de integração real de código PHP F3-40/F3-41/F3-42 da pilha antiga de dezenas de commits, preservando a demonstração verificável em dois bancos **descartáveis**, sem transportar nenhum receiver para `app/`, `routes/` ou `database/migrations/` de produção.

## O que roda

- GitHub Actions, runner **hospedado no GitHub** somente, PostgreSQL **16** em `127.0.0.1:5432` para o CRM e PostgreSQL **18** em `127.0.0.1:5433` para o Portal, ambos com schemas específicos de CI e entidades UUID inventadas.
- Checkout do repositório Portal fixado ao commit imutável `af414ed7aece433e65b4f813be12ba3546cc5c8a`; execução do **código PHP real** das classes Portal F119/F122. Sem cópias dessas classes no runtime CRM.
- O código de testes do CRM F3-40 / F3-41 fica em `tests/Labs/F3_40/` e `tests/Labs/F3_41/`: metadados de submissão, assinatura sintética HMAC, reserva/outbox, nonce anti-replay e recibos assinados somente de laboratório. O `php -S` expõe **exclusivamente loopback local em CI**, com duas rotas `__lab`, respostas explicitamente inertes e flags de produção sempre falsas.
- O arquivo `tests/Labs/F3_42/test_real_portal_crm_cross_repo.php` verifica o caminho HTTP, commit no PostgreSQL 18 do Portal, metadados/nonce no PostgreSQL 16 do CRM, rejeição de mudanças indevidas de tenant/site/form/destino, replay e reconciliação experimental; as chaves de teste de requisição e recibo são **independentes e geradas no próprio runner**, sem segredos versionados.
- A matriz PHP 8.3 / 8.4 executa **o teste direto**, sem exigir dependências Laravel e sem atualizar Composer. CI também procura referências de importação indevida de scripts de laboratório dentro de `app`, `routes`, `bootstrap` e `database/migrations`.

## Relação com o contrato Core

A PR [CORE-INT-01 #254](https://github.com/rafhael100/imagine-crm/pull/254) isola as classes puras de JSON/HMAC/binding para o domínio da aplicação. **Esta PR CORE-INT-02 não depende da #254** para compilar ou executar: valida o trajeto experimental de dois repositórios usando as classes PHP de receipt/metadata que já existiam na DEV. Isso mantém as duas propostas pequenas e revisáveis de forma independente. Para futura integração no runtime, uma revisão transversal deverá unir evidência CORE-INT-01 com CORE-INT-02, identidade de serviço, autorização por tenant, processamento de PII consentido, durabilidade e política de retries.

## O que não está autorizado

Nenhuma rota Laravel, worker, endpoint público, registro de cliente, login/segredo real, persistência produtiva, contrato de acesso real, webhook, deploy, VPN/VPS ou custo externo. Um status HTTP de laboratório **não é** aceite de lead; um recibo de PostgreSQL descartável **não é** ACK de CRM real; uma simulação de serviços assinados não substitui IAM ou KMS. F70 Comercial, SEG/DBA/LGPD e CORE permanecem **NO-GO** para ativação.

Revisão em PR **DRAFT** sobre DEV. Nenhum merge em `main`, deploy ou conexão a serviços reais foi realizado.
