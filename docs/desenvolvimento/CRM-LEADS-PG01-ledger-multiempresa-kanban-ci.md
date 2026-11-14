# CRM-LEADS-PG01 — Persistência de leads sintéticos e Kanban por empresa

Status: **DRAFT / NO-MERGE / NO-DEPLOY / NO-GRANT / NO-PII**.

## Entrega

A demonstração visual de leads/Kanban não podia provar isolamento real do PostgreSQL. Este recorte introduz um primeiro armazenamento testável e revalidável, porém **exclusivamente dentro do PostgreSQL16 descartável do GitHub Actions**:

- `tests/Labs/leads/lead-kanban-rls-ci.sql`: tabela `crm_lab_leads` com PK global por UUID, FK de organização, etapas definidas de funil e controle de versão, arquivamento lógico e trigger que impede voltar a alterar registro arquivado ou terminal; FORCE RLS e política `USING`/`WITH CHECK`.
- `app/Core/Leads/LaboratoryTenantLeadLedger.php`: cria leads **somente fictícios** com identificadores ASCII, lista por empresa, movimenta etapas `new → qualified → proposal → won` (ou `lost`) com CAS/versão e arquiva sem exclusão.
- Cada ação usa `PostgresActiveMembershipStore::within`: verifica membership ativa com `FOR UPDATE` e `SET LOCAL app.tenant_id` **durante a mesma transação** e retorna a conexão ao estado sem tenant após commit/rollback. Duas conexões de CI são usadas nos testes.
- `crm_app_ci`: papel PostgreSQL sem superuser/BYPASSRLS/ownership, com SELECT, INSERT por colunas e UPDATE apenas dos campos permitidos; **sem DELETE**. Trigger adiciona restrições de transição.
- `tests/Feature/PostgresLabLeadsRlsTest.php`: testa duas organizações com um único principal ativo, acesso independente por organização, revalidação após revogação, tentativas de update/insert de outra empresa, versão obsoleta, transações simultâneas e ausência de rota HTTP.
- CI read-only preflight `verify-ephemeral-postgres.php --verify` é executado antes de importar a fixture. Nenhuma migration nova de produção.

## Limites de segurança — importante

**GUC `app.tenant_id` é um marcador de escopo, não autenticação.** Em PostgreSQL, um processo que obtenha credenciais da aplicação pode tentar modificar GUCs; portanto, este teste sozinho **não garante segurança multiempresa operacional**. `PrincipalReference` também não é identidade verificada. O código exige CI sintético e não é registrado como serviço HTTP: antes de qualquer rota real exige autenticação via `GuardPrincipalSource`, IAM com sessão vigente, revogação, `Authorizer` funcional, ownership/roles seguros, governança LGPD, auditoria e proteção de credenciais.

A classe `DenyByDefaultAuthorizer` permanece NEGANDO toda ação de negócio. **Não concedemos permissão via configuração experimental**, e o protótipo não recebe contatos, telefone, e-mail, CNPJ nem dados reais. Não pode ser promovido a produção por mera aprovação da PR.

## Dependências para finalizar o CRM

1. Nomear e obter revisão independente SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27); revisar modelo real de papéis, RLS com identidade não forjável, backups/restauração e migrações.
2. Aprovar o fluxo IAM e LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259), incluindo consentimento, finalidade, retenção, políticas de acesso e auditoria.
3. Revisar UX [#416](https://github.com/rafhael100/imagine-crm/issues/416), integração de front-end com endpoints protegidos e autorização por ação.
4. Consolidar o código sob [CRM-LAUNCH-01 #557](https://github.com/rafhael100/imagine-crm/issues/557), validar em staging isolado e obter GO formal.

**Evidência de CI deve informar a HEAD exata**. Não houve conexão à VPS, criação de clientes, faturamento nem mudança na branch `main`.
