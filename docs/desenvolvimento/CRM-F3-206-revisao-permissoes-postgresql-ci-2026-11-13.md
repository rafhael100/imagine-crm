# CRM F3-206 — auditoria automatizada, somente leitura, dos papéis do PostgreSQL CI

**Data:** 13/11/2026. **Origem:** [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) (SEGDBA-03), [CORE/SEG/DBA/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), F3-03 da [issue #35](https://github.com/rafhael100/imagine-crm/issues/35). **Status:** evidência de regressão técnica limitada ao PostgreSQL 16 descartável do GitHub-hosted CI, **não** parecer independente, não aprova DB persistente nem libera acesso real.

## O que foi adicionado

Novo `tests/Feature/PostgresCiLeastPrivilegeReadOnlyTest.php` verifica **diretamente o catálogo real PostgreSQL**, não mocks, após migrations experimentais já instaladas pelo pipeline em seu contêiner descartável. A suíte permanece inelegível para execução como teste de evidência em SQLite; em PostgreSQL, exige `CI=true`, `GITHUB_ACTIONS=true`, base `imagine_crm_ci` e as cinco identidades explicitamente esperadas:

- `crm_app_ci`: leitor/escritor restrito à tabela de membership experimental;
- `crm_iam_ci`: tarefas IAM experimentais;
- `crm_provision_ci`: provisionamento IAM experimental;
- `crm_async_ci`: operações da fila assíncrona experimental;
- `crm_async_audit_ci`: consulta/auditoria da fila experimental.

Usa **somente funções de leitura de metadados**: `pg_roles`, `has_database_privilege`, `has_schema_privilege`, `pg_has_role`, `has_table_privilege`.

### Garantias testadas dentro do contêiner CI

1. Nenhuma das cinco identidades pode ser `SUPERUSER`, `BYPASSRLS`, `CREATEDB`, `CREATEROLE`, `REPLICATION` ou possuir herança implícita; o conjunto da CI deve usar `NOINHERIT`.
2. Nenhuma pode criar schema no banco nem tabela no schema `public`.
3. Nenhuma pode herdar ou assumir `postgres` nem as funções sensíveis `pg_read_all_data`, `pg_write_all_data`, `pg_read_server_files`, `pg_write_server_files`, `pg_execute_server_program`, `pg_signal_backend`, `pg_monitor`.
4. Perfis de um módulo não têm `SELECT`, `INSERT`, `UPDATE` ou `DELETE` nas tabelas experimentais reservadas a outro. Verifica também permissões mínimas esperadas e evita teste trivial que só falha por conta desconfigurada.

**Nenhuma alteração** em roles, ACLs, RLS, DDL, migration, código de autorização, rotas, credenciais, armazenamento ou dados. A conexão superuser CI é usada **apenas para consulta ao catálogo**, nunca como adapter de runtime. Nada acessa VPS, prod ou identidades reais.

## Limitações e próximos gates

Essas evidências **não testam** privilégios do PostgreSQL real da VPS, nem garantem inexistência de túneis/proxies, volumes persistentes, escalonamento por extensões, SQL arbitrário, políticas sobre outros schemas, segurança da aplicação real, consentimento ou separação de reviewer. Revisão independente #27 segue NO-GO (SEGDBA-01/02/04/05/07, inclusive revisão PRs #23/#26) e integração Portal–CRM #259 segue NO-GO.

O caso de uma pessoa com memberships ativas simultaneamente em duas empresas **já consta no código existente** `PostgresMembershipRlsTest` e é executado na CI; não foi reimplementado nem apresentado como um novo recurso nesta PR. Os testes atuais servem somente para validar o isolamento do laboratório.

**Critério de merge DEV:** F0/Laravel, PostgreSQL/RLS, Sites e Agentes com `success` na mesma HEAD exata; diff limitado a esse teste + documentação. `main`, VPS e produção continuam inalteradas.
