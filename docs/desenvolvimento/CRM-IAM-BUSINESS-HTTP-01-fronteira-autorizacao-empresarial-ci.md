# CRM-IAM-BUSINESS-HTTP-01 — Fronteira integrada de autorização empresarial

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN / NO-ALLOW / NO-CHARGE.**

## Objetivo

Completar uma prova que faltava ao candidato #604: a cadeia **HTTP Laravel real
→ identidade de sessão verificada com MFA → membership PostgreSQL16 restrita,
com `FORCE RLS` → política de autorização de negócio**. A prova é
deliberadamente negativa e não disponibiliza login ou dados de clientes.

## Implementação

- Novo `tests/Feature/CrmIamBusinessHttpGateTest.php` com rotas registradas
  exclusivamente dentro do PHPUnit, em `/_ci/iam-business/*`.
- O teste usa as classes reais
  `GuardPrincipalSource`, `PostgresActiveMembershipStore`,
  `TenantAccessBoundary` e `DenyByDefaultAuthorizer`, sem mocks
  substituindo autenticação/membership/decisão de autorização.
- Execução com duas identidades fictícias, duas organizações distintas
  e papéis PostgreSQL do CI sem `BYPASSRLS`. Uma identidade tem
  membership ativa no tenant C; outra, somente no tenant D.
- A suíte verifica 401 sem MFA, 403 para read/update/export ainda que
  a identidade e o vínculo sejam válidos, 403 para outra empresa,
  falsificação de usuário/cabeçalho/role/query negada, 403 após
  revogação do vínculo, 401 após alteração da versão de credenciais,
  zero callbacks de negócio em todos os casos e ausência de escopo
  residual `app.tenant_id` na conexão PostgreSQL.
- O job isolado Redis 7/PG16 executa explicitamente a nova suíte
  em `scripts/ci/rehearse-crm-iam-redis-session.sh`, sem alterar a
  configuração de sessão dos demais testes.

## Limites

O teste não cria autorização de negócio, não conecta uma interface
humana a contatos reais, não publica rotas, não permite CRM público
nem altera o `DenyByDefaultAuthorizer`. HTTP GET/POST de laboratório
usa resposta 403/401 e **nunca** chama CRUD, exportação ou cobrança.

A própria existência de credencial MFA + membership válida não basta
para autorizar leads/contatos. Ainda faltam: modelo de papéis e políticas
verificadas, perfis de assinatura/trial, provisionamento e consentimento,
separação real de ambiente, auditoria de dados pessoais, revisão
independente de SEG/DBA #27, LGPD #259, UX #416 e GO #557.

**Aceite técnico:** dez workflows GitHub Actions na HEAD exata,
especialmente PG16+Redis e F0, com as rotas de produção negadas.
Não há mudança na `main` nem na VPS.
