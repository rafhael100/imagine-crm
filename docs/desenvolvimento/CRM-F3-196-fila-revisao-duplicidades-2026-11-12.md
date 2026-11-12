# CRM F3-196 — fila de revisão priorizada e paginada para comparações sintéticas

**Data:** 12/11/2026. **Base funcional:** [CRM F3-195](CRM-F3-195-evidencia-duplicados-e-idempotencia-2026-11-12.md), [issue #35](https://github.com/rafhael100/imagine-crm/issues/35), CRM-REV-03 DAT-R08/DAT-R10. **Escopo:** núcleo de revisão PHP, sem renderização ou acesso de clientes.

## Entrega
`SyntheticLeadCollisionReviewWorklist::inspect($leadCount, $pairs, $pageSize, $page)` reutiliza diretamente o validador completo F3-195. Até 20 posições fictícias e 64 comparações são permitidas. Cada revisão usa apenas índices (nunca IDs ou dados pessoais). `pageSize` entre 1 e 10, `page` zero-based dentro das páginas existentes; fila vazia só aceita página zero.

A fila ordena **contradição em envio declarado** primeiro, depois possível coincidência de pessoa, depois repetição de envio sem contradição, por fim evidências insuficientes. Empate preserva a ordem da comparação fornecida. Não constrói uma decisão de identidade nem suprime pares com poucos dados. Paginação mantém `reason_counts` globais, `priority_review_count`, `unshown_pair_count`, `page_items` e `has_next_page`, sem consultar nenhum sistema externo.

## Fronteira de segurança
A composição é puramente informativa. A validação F3-195 percorre **todas as comparações**, inclusive as que não pertencem à página atual; dados malformados ou com novos campos são rejeitados com erro genérico. Nenhuma alegação de relação prova consentimento, pessoa, vínculo de tenant, idempotência ou autorização.

`authorized=false`, `can_assign_reviewer=false`, `can_merge=false`, `can_reconcile=false`, `can_acknowledge_delivery=false`, `can_export=false`, `can_update_lead=false`, `can_persist=false`. Nenhuma tarefa é atribuída e nenhum contato é exibido, deduplicado, enviado ou retido.

## Cobertura
Unit tests: prioridade estável, paginação/remainder, vazio, limite 64, rejeição de página inválida, duplicidade de par, PII extras mesmo fora da página visível, ausência de capacidades de escrita. Matriz transversal comercial ampliada de 40 para **41** contratos deny-only e selo negativo de `routes/web.php`. A integração DEV só ocorre após workflows F0/Laravel, Postgres/RLS, Sites e AIA verdes na HEAD exata.

**NO-GO operacional:** revisão independente #27 e aceite Portal–CRM #259 seguem pendentes. Nenhuma tela autenticada real, API, RLS nova, migração, fila persistente, dados pessoais, merge, importação, `main` ou deploy.
