# CRM-IAM-HTTP-01 — Senha → TOTP → Sessão → Autorização negada → Logout no HTTP Laravel

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN PÚBLICO / NO-ALLOW / NO-CHARGE**

## Lacuna encontrada após consolidar a PR #596

O CRM tem uma base interna de IAM que já testa diretamente os serviços `ExperimentalAuthenticator`, `DatabaseSessionAuthority`, `GuardPrincipalSource`, a versão de credenciais, TOTP, recuperação, sessões e revogação. Entretanto, **nenhuma rota pública de autenticação foi disponibilizada**; isto é intencional, pois login e IAM operacional dependem de revisão de segurança, migrações e infraestrutura. Também faltava uma prova do **pipeline HTTP Laravel completo** para confirmar que senha, MFA, sessão e autorização permanecem coerentes entre diferentes requisições.

## Entrega executável

O novo `tests/Feature/CrmIamHttpBoundaryTest.php` registra **apenas durante o processo PHPUnit** rotas temporárias `/_ci/iam/password`, `/_ci/iam/mfa`, `/_ci/iam/current`, `/_ci/iam/tenant/{organization}` e `/_ci/iam/logout`.

- Configura o mesmo `SyntheticIam` dos testes existentes, com dois usuários fictícios, senha fictícia e fator TOTP conhecido, armazenados no registro de sessão SQL de laboratório.
- Executa **POST senha + token CSRF real**, confirmação TOTP após rotação de sessão/CSRF e **GET da identidade vinculada à sessão validada**, usando o `GuardPrincipalSource` da aplicação.
- O endpoint de operação comercial simulado chama o **`TenantAccessBoundary` real** com um **ActiveMembershipStore explicitamente fictício**, passando pelo **`DenyByDefaultAuthorizer` real**. Mesmo com sessão MFA validada e membership ativa, recebe **403**; o callback de efeito **nunca é executado**.
- Após logout, `GET /_ci/iam/current` e tentativa de negócio voltam a **401**, com 0 sessões ativas. Senha inválida e MFA inválido falham sem autorização, CSRF ausente recebe 419, e `/login`, `/mfa`, `/register`, `/api/auth` e `/api/leads` continuam 404.
- O teste roda em SQLite descartável pelo job F0 e deve também rodar na suíte PostgreSQL de IAM, com papéis restritos existentes. A prova **não adiciona controller, arquivo de rotas, migration, fornecedor, chave ou servidor público**.

## O que esta entrega NÃO resolve

Mesmo que o CI fique verde, **não há login operacional**: as rotas temporárias são injetadas pelo próprio teste, não existem no produto implantável. O armazenamento real de sessão, Redis, políticas IAM/RBAC por usuário/empresa, consentimento, email/OTP, provisionamento, onboarding e gestão de segredo ainda precisam de aceites independentes e infraestrutura preparada. O teste usa memberships inventadas somente para provar a negativa, não constitui autorização real.

Próximo passo condicionado: contrato de identidade operacional e pipeline controlado de migrations+RLS com revisão SEG/DBA; só então integrar login em ambiente de homologação restrito, sem liberar dados ou cobranças de clientes. Gates [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416), [lançamento #557](https://github.com/rafhael100/imagine-crm/issues/557) permanecem abertos.
