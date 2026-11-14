# CRM-CORE-INTEG-01 — Captação Sites + Leads/Kanban + Tarefas na mesma versão

**Status DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-GRANT.**

## Por que integrar

A [PR #566](https://github.com/rafhael100/imagine-crm/pull/566) trabalha a proteção concorrente de vínculos de captação Sites/CRM, e a [PR #565](https://github.com/rafhael100/imagine-crm/pull/565) entrega tarefas auditáveis por lead. Enquanto os módulos permanecessem em PRs diferentes, cada workflow demonstrava propriedades isoladas mas não o funcionamento integrado.

## Entrega

Esta HEAD é construída sobre a branch da PR #566, incluindo os arquivos do módulo de tarefas da PR #565 e os cenários de testes dela **no mesmo workflow `ci-crm-leads-rls.yml`**:

- PostgreSQL16 **efêmero**, protegido por `scripts/ci/verify-ephemeral-postgres.php --verify`, cria as tabelas de leads, captura assinada, cadastro de vínculos, auditoria e tarefas com FK composta tenant/lead e FORCE RLS.
- `crm_app_ci` continua sem SUPERUSER, BYPASSRLS nem UPDATE de vínculos e sem DELETE de leads/tarefas. As permissões são restritas a colunas quando necessário.
- HMAC sintético, nonce, vínculo de formulário aprovado por fixture, serialização do fluxo de revogação por advisory lock, reserva idempotente e auditoria do lead funcionam no mesmo banco que as tarefas.
- Tarefas têm estado `todo → done/cancelled`, versão otimista e trilha append-only com rollback atômico; não existem notificações nem contatos.
- Teste integrado novo: tarefa para lead ainda inexistente negada; submissão assinada fictícia cria lead; o mesmo lead recebe tarefa no tenant correto; replay não duplica; conclusão grava evento; outro tenant não enxerga dados; todos os registros conservam a organização original.
- Os 31 testes da suíte dedicada incluem casos históricos de isolamento RLS, membership revogada, assinatura alterada/expirada, nonce repetido, auditoria falha, revogação concorrente e tarefas.

## Não confundir com produção

As chaves HMAC e empresas usadas são fixtures públicas. `PrincipalReference` não comprova autenticação; `app.tenant_id` é só marcador de escopo, não identidade. `DenyByDefaultAuthorizer` continua negando permissões operacionais e **não existem APIs públicas conectadas a esses ledgers**.

Permanecem pendentes migrations operacionais com parecer SEG/DBA, autenticação real do emissor Sites, login/MFA de usuário, aprovação de consentimento LGPD, políticas efetivas por recurso/ação, segregação de roles e secrets, fila durável, métricas/observabilidade, rollback/backup e homologação na VPS.

Gates: [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416), [concorrência #564](https://github.com/rafhael100/imagine-crm/issues/564) ainda depende de aceite independente, [lançamento #557](https://github.com/rafhael100/imagine-crm/issues/557). Não houve merge, deploy, dados pessoais, credenciais reais ou contato com Asaas/VPS.
