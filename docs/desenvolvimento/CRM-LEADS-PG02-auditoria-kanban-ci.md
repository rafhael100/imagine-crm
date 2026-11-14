# CRM-LEADS-PG02 — auditoria transacional do Kanban sintético

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-GRANT**.

## Incremento sobre CRM-LEADS-PG01

A tabela `crm_lab_leads` já possui segregação por empresa via FORCE RLS e um controle otimista de versão. O evento da movimentação era efêmero: uma atualização de etapa poderia não ter um histórico verificável.

PG02 acrescenta `crm_lab_lead_events` **somente no PostgreSQL16 descartável do CI**, com:

- Eventos `created`, `stage_moved` e `archived` e versões sequenciais por lead.
- PK do evento, FK composta `(lead_id, organization_id)` e UNIQUE `(lead_id, version)`.
- FORCE RLS e permissão de **INSERT por colunas somente**, sem SELECT, UPDATE, DELETE ou TRUNCATE do papel `crm_app_ci`.
- Escrita do evento dentro da mesma transação já protegida pela membership `FOR UPDATE` e contexto tenant `SET LOCAL`.
- Falha da inserção de evento desfaz a criação, movimentação ou arquivamento, sem deixar versão avançada sem histórico.
- Testes com duas organizações e teste de colisão da versão de evento que força rollback real.

## Limites críticos

Esta auditoria é **estrutural de laboratório**, não um mecanismo final de logs de produção: um proprietário de tabela/superusuário consegue alterar as linhas; não existe retenção imutável externa, carimbo seguro, assinatura, consolidação de eventos, exportação LGPD nem revisão de reconstituição de estado.

O marcador `app.tenant_id` continua não sendo autenticação. `PrincipalReference` é um identificador, não prova de login. O `DenyByDefaultAuthorizer` permanece bloqueando efeitos reais. Nenhuma rota, tela pública, dado de pessoa, credencial de Asaas, alteração da VPS ou migration operacional.

## Gates

SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259), UX [#416](https://github.com/rafhael100/imagine-crm/issues/416) e conclusão [#557](https://github.com/rafhael100/imagine-crm/issues/557). Testes CI devem ser confirmados na HEAD exata antes de qualquer aprovação técnica de laboratório.
