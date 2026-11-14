# CRM-LEADS-PG04 — Vinculação imutável da submissão Sites → CRM

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-GRANT**

## Contexto

PG03 provou idempotência transacional por organização, origem e hash de evento, além de FK diferida e auditoria atômica. No entanto, o chamador informava um digest, que não vinculava o primeiro recebimento ao site/formulário/destino original. PG04 fixa esses valores sem alegar que foram autenticados.

## Alterações

- O método `LaboratoryTenantLeadLedger::captureSynthetic` recebe um `SyntheticIdempotencyClaim` tipado (já existente no CRM) em vez de um SHA arbitrário. A organização reivindicada precisa coincidir com a organização solicitada; o digest é **derivado internamente** de organização + submissão pela função sintética já existente.
- O ledger PostgreSQL mantém campos UUID imutáveis: `binding_reference`, `submission_reference`, `site_reference`, `form_reference` e `destination_reference`.
- A constraint `UNIQUE (organization_id, submission_reference)` impede o mesmo envio de aparecer como outro `source_code` (além da PK organização/origem/hash).
- Reenvio com quaisquer referências alteradas, mesmo com a mesma organização e submissão, retorna `synthetic_conflict_denied`, sem tocar no lead, no evento ou na reserva. Um reenvio idêntico retorna `synthetic_replayed`.
- A transação ainda reserva a claim antes do lead e do evento, com FK composta diferida para impedir commit parcial; conta PostgreSQL não tem UPDATE/DELETE dessas referências.
- PHPUnit real no PG16 efêmero testa troca de vínculo, site, formulário, destino e origem, mantendo exatamente um lead e uma entrada de auditoria.

## Crítico: fonte reivindicada não é fonte confiável

`SyntheticIdempotencyClaim` contém **valores fornecidos, não prova criptográfica de origem**. O servidor de produção precisa conferir originator por credencial gerenciada, HMAC/assinatura e tempo/nonce, vincular o formulário a contrato/tenant aprovado, verificar consentimento LGPD, configurar canais e impedir reuso de tokens. **O método não é uma API do Sites, não grava lead real e não concede `ALLOW`.**

A classe é restrita ao GitHub CI, com identificadores e dados fictícios. Não usar esses UUIDs como evidência de autenticação, nem transpor migrations de teste para a VPS. `DenyByDefaultAuthorizer` e as rotas atuais permanecem intactos.

Permanecem abertos: [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416), [CRM-LAUNCH-01 #557](https://github.com/rafhael100/imagine-crm/issues/557).
