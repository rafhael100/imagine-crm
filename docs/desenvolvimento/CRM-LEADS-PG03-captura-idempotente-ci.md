# CRM-LEADS-PG03 — Captura idempotente Sites → CRM em PostgreSQL descartável

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-GRANT**.

## Objetivo do incremento

O CRM já dispõe de ledger sintético de leads com isolamento PostgreSQL FORCE RLS (PG01) e histórico atômico do Kanban (PG02), mas `createSynthetic` falha em um reenvio legítimo do mesmo evento de origem. O Imagine Sites precisará reenviar formulários quando a comunicação ou entrega forem incertas. Sem idempotência durável, esse comportamento pode duplicar leads ou perder o vínculo da auditoria.

## Implementado no laboratório

- `crm_lab_lead_capture_keys`: PK composta `(organization_id, source_code, event_sha256)`, ID de lead globalmente único, FK composta para `crm_lab_leads` **`DEFERRABLE INITIALLY DEFERRED`**. Claims não podem ser editadas nem apagadas pelo papel `crm_app_ci`.
- `LaboratoryTenantLeadLedger::captureSynthetic` aceita somente `sites`, `landing` ou `onepage`, um UUID sintético, título sintético limitado a ASCII e um digest hexadecimal SHA-256 de teste. O digest **não é** prova de origem, assinatura, HMAC de produção nem autenticação de cliente.
- Dentro de `PostgresActiveMembershipStore::within`, com membership ativa `FOR UPDATE` e `SET LOCAL app.tenant_id`, primeiro faz `INSERT ... ON CONFLICT DO NOTHING` do evento e depois insere lead e auditoria. Todo o trabalho está na mesma transação; a FK diferida só permite commit de evento se o lead existir sob a mesma organização.
- Reenvio exato de organização, origem, hash, lead e título: `synthetic_replayed`, sem novo lead ou evento. Reenvios divergentes e reutilização do ID sob outro evento: `synthetic_conflict_denied`, sem revelar identidades.
- Erro na inserção do lead: rollback da reserva, sem claim órfã, lead parcial ou auditoria falsa.
- Testes PostgreSQL16 reais com duas conexões e duas organizações cobrem replay, colisões, isolamento, membership revogada, role append-only e rollback de conflito de unicidade.

## Limites e próximos gates

**Esta entrega não conecta o Imagine Sites nem recebe formulários reais.** Ainda faltam prova criptográfica de origem, vínculo de serviço/tenant autorizado, consentimento e validação de campos, HMAC com chave gerenciada, retenção de metadados, fila/reenvio observável, quotas, rate limit, autenticação, auditoria operacional, RLS revisado e APIs privadas. Esta implementação serve para testar **uma propriedade transacional**, não para emitir autorização comercial ou produção.

`app.tenant_id` continua mero marcador de escopo, não prova de identidade; `PrincipalReference` continua não autenticada por si só. `DenyByDefaultAuthorizer` permanece intacto. Nenhum endpoint publicado ou migração operacional.

Gates abertos: [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416), [plano de release #557](https://github.com/rafhael100/imagine-crm/issues/557). Não executar merge/deploy automático só por CI verde.

**Verificação de CI:** a HEAD exata, workflows e logs devem ser registrados na descrição da PR antes de considerar aprovado o laboratório.
