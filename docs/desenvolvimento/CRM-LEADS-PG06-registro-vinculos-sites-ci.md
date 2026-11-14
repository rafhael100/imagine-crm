# CRM-LEADS-PG06 — Registro interno de vínculos Sites/CRM em PostgreSQL de CI

**Status: DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-GRANT OPERACIONAL**

## Problema resolvido apenas em laboratório

PG05 já verificava uma assinatura HMAC sobre valores sintéticos, mas uma assinatura válida sobre valores fornecidos não comprova que o site ou formulário está autorizado a entregar leads à organização e ao destino declarados. PG06 acrescenta uma **fonte server-side fictícia de vínculos** para a decisão de aceitação em CI, com testes negativos e revogação.

## Implementação

- Nova tabela efêmera `crm_lab_site_delivery_bindings` com PK composta `(organization_id,binding_reference)`, site, formulário, destino, código da origem, estado `active/suspended/revoked` e versão positiva. FK de organização para o tenant simulado.
- `FORCE ROW LEVEL SECURITY` com filtro da organização no contexto transacional existente. O papel `crm_app_ci` tem **somente SELECT por colunas**, sem INSERT/UPDATE/DELETE ou acesso a outro tenant; o proprietário das fixtures prepara/revoga os vínculos no teste.
- No caminho assinado de `LaboratoryTenantLeadLedger::captureSynthetic`, **dentro da mesma transação** que verifica membership e reserva nonce/claim/lead/auditoria, o servidor busca o vínculo correspondente e consulta seu estado usando **SELECT-only**, sem bloqueio de linha; nega se ausente, revogado, suspenso ou com origem/site/formulário/destino divergentes.
- Reenvio de mensagem assinada depois da revogação não autoriza nova leitura/entrada pelo caminho de ingestão, mesmo que a claim original já exista. Alteração de canal também nega.
- O caminho anterior de `captureSynthetic` sem assinatura permanece apenas para testes legados, nunca como API; nenhuma rota HTTP foi adicionada.
- PHPUnit de CI registra vínculo ativo, verifica rejeição sem vínculo, primeiro recebimento, revogação, troca de canal, isolamento entre duas empresas e papel SQL sem escrita. A CI exige PostgreSQL16 descartável e `verify-ephemeral-postgres.php --verify`.

## Ainda não é autenticação de serviço real

O cadastro dos vínculos é **exclusivamente fixture fictícia em banco descartável**. A chave HMAC da etapa anterior é pública e falsa, e nenhuma autorização de cliente, contratação, consentimento LGPD ou segredo real está presente. **A revogação simultânea à captura pode ocorrer após a consulta e antes do commit: este recorte NÃO demonstra revogação linearizável.** Não conceder UPDATE nem BYPASSRLS ao papel somente para forçar bloqueios de leitura. Um mecanismo de serialização revisado pelo SEG/DBA ainda é obrigatório antes de qualquer endpoint. O mecanismo de concorrência, a competência da entidade que provisiona vínculos, a rotação de credenciais, o isolamento de papéis operacionais, a retenção, exclusão, autenticação de fonte e autenticação de usuário precisam de parecer independente antes de uso real.

Este recorte não faz merge, deploy, migração de banco persistente, acesso à VPS, integração externa nem concessão de direitos. Permanecem abertos [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416) e [conclusão #557](https://github.com/rafhael100/imagine-crm/issues/557).
