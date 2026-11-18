# CRM-STG-SCHEMA-01 — Migração Laravel com papéis SQL separados

**DRAFT / NO-MERGE / NO-DEPLOY / NO-GRANT PRODUTIVO / NO-PII**.

## Problema identificado

No Compose candidato [#572](https://github.com/rafhael100/imagine-crm/pull/572), `POSTGRES_USER=crm_staging` cria um usuário PostgreSQL administrador, e a aplicação PHP utilizava o mesmo usuário como `DB_USERNAME`. Isso significava executar queries da aplicação com credenciais capazes de alterar esquemas/políticas e ignorar o escopo multiempresa — proibido para um SaaS real.

## Correção de infraestrutura

- Bootstrap PostgreSQL usa `infra/staging/01-crm-staging-app-role.sh` montado somente leitura para criar `crm_staging_app` como `LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS`. O comando escapa a senha como literal SQL, sem senha versionada.
- Compose separa `CRM_STAGING_DB_PASSWORD` (administrador **somente provisionamento**) de `CRM_STAGING_APP_DB_PASSWORD` (app restrita); gera ambos com valores diferentes e aleatórios no GitHub runner, mascarados nos logs. Nenhuma porta pública de Postgres/PHP foi criada.
- O processo PHP-FPM usa exclusivamente `crm_staging_app`, não o papel de bootstrap. Nenhum privilégio `CREATE`, propriedade de tabelas ou mutação de memberships é concedido na inicialização.
- O checker estático valida separação das senhas, inicialização, `NOBYPASSRLS`, `NOSUPERUSER`, mounts somente leitura e redes internas; recusa alterações de rota financeira e flags de autenticação.
- O ensaio de recuperação inclui os grants referenciando o novo papel da aplicação. Como roles são **globais à instância PostgreSQL e não entram no backup por banco**, a instância isolada de restauração cria antes da importação uma role `crm_staging_app` **NOLOGIN/NOSUPERUSER/NOBYPASSRLS**, sem senha copiada. A primeira execução do CI encontrou justamente essa ausência e a correção está sujeita à repetição integral dos testes.
- Após o smoke e o backup/restore em contêiner isolado no mesmo runner, o ensaio `rehearse-crm-private-schema.sh` executa a migration Laravel candidata `2026_11_04_000001` com credenciais administrativas **exclusivamente no subprocesso CI**; verifica idempotência e `FORCE RLS` na tabela de memberships.
- Apenas no CI, concede `SELECT` na tabela de memberships ao papel de aplicação e insere dois tenants/usuários fictícios. O script PHP `check-staging-runtime-schema.php`, inicializado pelo Laravel real como `crm_staging_app`, comprova ausência de ownership/SUPERUSER/BYPASSRLS/CREATE/INSERT/UPDATE/DELETE e isolamento de uma membership por tenant/zero sem tenant.

## Limitações deliberadas

A configuração permanece **candidata**. O administrador `crm_staging` ainda é superusuário do banco do contêiner; ele não pode ser disponibilizado à aplicação ou clientes. O papel de runtime ainda não tem o conjunto validado de permissões necessário a `PostgresActiveMembershipStore::within`, que usa `SELECT ... FOR UPDATE`. Não conceder UPDATE arbitrário para liberar esse método; desenho IAM, credenciais e políticas ACL aguardam revisão independente SEG/DBA. `app.tenant_id` continua marcador de escopo, não identidade autenticada; nenhuma rota de login foi criada.

Esta entrega não instala schema real na VPS, não grava PII, não conecta Asaas, não envia pagamentos, não executa merge ou deploy e não aprova acesso de usuários. Riscos remanescentes em [#27](https://github.com/rafhael100/imagine-crm/issues/27), [#259](https://github.com/rafhael100/imagine-crm/issues/259), [#416](https://github.com/rafhael100/imagine-crm/issues/416), [#564](https://github.com/rafhael100/imagine-crm/issues/564), [#557](https://github.com/rafhael100/imagine-crm/issues/557).
