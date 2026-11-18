# CRM-TASKS-PG01 — Acompanhamentos por lead em PostgreSQL descartável

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-GRANT**

## Frente funcional

A persistência de leads/Kanban e seus eventos foi demonstrada nas PRs [#558](https://github.com/rafhael100/imagine-crm/pull/558) e [#559](https://github.com/rafhael100/imagine-crm/pull/559). As tarefas de acompanhamento ainda não tinham um ledger relacional integrado a esse mesmo lead e tenant.

O recorte `CRM-TASKS-PG01` adiciona **somente ao PostgreSQL16 descartável do CI**:

- `crm_lab_followup_tasks`: tarefa com ID UUID, empresa, lead, código de assunto exclusivamente fictício, vencimento, status `todo/done/cancelled`, versão e datas; PK, FK composta empresa+lead, UNIQUE empresa/lead/assunto e índice de pendências.
- `FORCE ROW LEVEL SECURITY`, `USING` + `WITH CHECK` por tenant, sem papel SUPERUSER/BYPASSRLS; o papel `crm_app_ci` tem SELECT e colunas estritas de INSERT/UPDATE, sem DELETE ou mudança de empresa, lead, assunto ou vencimento.
- Gatilho monotônico: só `todo` → `done/cancelled` com versão +1. Estados terminais não podem retroceder e concorrências obsoletas são recusadas.
- `crm_lab_task_events`: audit trail com versão única por tarefa, FK composta ao tenant, evento `created/completed/cancelled`, FORCE RLS e apenas INSERT pelo papel runtime de CI. Uma falha na gravação do evento desfaz a mudança de status.
- `LaboratoryTenantTaskLedger`: cria tarefa se o lead não está arquivado e pertence à organização ativa, consulta até 100 tarefas pendentes em ordem estável e conclui/cancela com CAS/versão. Todas as operações passam por `PostgresActiveMembershipStore::within` e são limitadas ao CI de teste.
- Testes negativos e positivos no PostgreSQL real: duas organizações, FK entre tenant e lead, lead arquivado, versão obsoleta, evento duplicado causando rollback, role sem DELETE, membership revogada e caminhos HTTP 404.

## Limites técnicos

A classe **não** é um serviço operacional, não envia notificações, não cria tarefa para cliente real, não integra calendários, não autentica responsáveis, não permite exportação de dados pessoais e não possui rotas públicas. `PrincipalReference` não constitui autenticação e `app.tenant_id` não é prova de identidade. O `DenyByDefaultAuthorizer` continua bloqueando efeitos de negócio reais.

Antes da operação, são necessários modelo aprovado de autorização por recurso/ação/campo, identidade e MFA, consentimento e finalidade LGPD, retenção/exclusão de tarefas, notificações com opt-in, auditoria operacional, migrações conservadoras, backup/restauração e E2E no staging.

## Dependências

[CRM-LAUNCH-01 #557](https://github.com/rafhael100/imagine-crm/issues/557), [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259) e [UX #416](https://github.com/rafhael100/imagine-crm/issues/416). Não há merge/deploy nem autorização de ALLOW por causa de CI verde.
