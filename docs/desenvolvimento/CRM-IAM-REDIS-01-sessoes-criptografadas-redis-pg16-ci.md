# CRM-IAM-REDIS-01 — Sessão HTTP real no Redis com MFA e PostgreSQL16

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN PÚBLICO / NO-ALLOW / NO-CHARGE.**

## Lacuna de infraestrutura corrigida no ensaio

Os testes [#597](https://github.com/rafhael100/imagine-crm/pull/597) e [#598](https://github.com/rafhael100/imagine-crm/pull/598) validaram senha, TOTP, cookies de sessão, consulta de membership PostgreSQL e revogação, mas o GitHub Actions principal usava `SESSION_DRIVER=array`, ou seja, armazenamento que não representa a persistência interprocessos do SaaS.

Esta entrega **executa as mesmas provas de HTTP Laravel e IAM/RLS PG16 com sessão real armazenada no Redis 7** no runner descartável. O Redis já existe como serviço do workflow PostgreSQL, não há serviço de terceiros, chave comercial, instalação na VPS ou dados pessoais.

- `scripts/ci/rehearse-crm-iam-redis-session.sh` exige GitHub Actions no repositório certo, `APP_ENV=testing`, Postgres16 `imagine_crm_ci` local e Redis em `127.0.0.1:6379`. Falha se os papéis `IAM_TEST_USERNAME`/`RLS_TEST_USERNAME` ou `CRM_IAM_REDIS_LAB=synthetic-only` estiverem ausentes.
- Entra em perfil dedicado `SESSION_DRIVER=redis`, `SESSION_STORE=redis`, `SESSION_CONNECTION=default`, `SESSION_ENCRYPT=true`, cookie seguro HTTP-only/SameSite Strict estabelecido pelo provider IAM, `CRM_AUTH_EXPERIMENTAL=true` somente testing, e Phpredis. Executa testes completos `CrmIamHttpBoundaryTest` e `CrmIamPostgresHttpMembershipTest`. O teste da rota recebe cookie criptografado sem dupla criptografia, como corrigido na #597.
- Verifica senha → MFA → sessão → negócio negado, CSRF, logout; IAM SQL com credencial versionada; membership real PG16, consulta A/C, isolamento B/D, revogação de sessão/empresa e troca de empresas sem resíduo.
- A suite PostgreSQL/F0 existente continua intacta e usando o armazenamento usual `array`. A prova Redis é uma etapa **adicional**, antes da suite inteira, no mesmo runner descartável. Nenhum arquivo de configuração produtiva ou Docker/VPS é alterado.
- Os resultados não incluem credenciais, TOTP de usuário real, CNPJ, e-mail, telefone, nomes de clientes ou tokens de cobrança.

## O que ainda falta

Uma prova de Redis no runner não é disponibilidade em produção, TLS/mTLS, TLS termination, replicação, alta disponibilidade, backups do Redis, expiração/revogação multi-worker sob carga ou autenticação de clientes reais. Nenhumas rotas públicas de login ou ações comerciais estão habilitadas. O Redis e o PostgreSQL da VPS não foram tocados. Necessários: revisão independente SEG/DBA #27, LGPD #259, UX #416, consentimento, contas/roles reais, segredos/KMS, deploy privado testado, MFA/recuperação e integração com o provedor aprovado antes de GO #557.
