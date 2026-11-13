# CRM F3-204 — matriz negativa CORE02/SEG: autorização do núcleo existente

**Data:** 12/11/2026. **Área:** F3-03 (issue #35), SEG/DBA #27 e CORE/SEG/DBA/LGPD #259. **Escopo:** reforço de testes sobre o **componente real já existente** `DenyByDefaultAuthorizer`; não cria um autorizador novo nem altera permissões.

## O que foi exercitado
`tests/Unit/DenyByDefaultCrossTenantBoundaryMatrixTest.php` cria envelopes **fictícios** com UUIDs estáticos, escopo por empresa e `MembershipSnapshot::Active` alegado, com descritores que correspondem ao catálogo `ExperimentalResourceCatalog`. Confirma que mesmo esse cenário aparentemente válido é sempre `POLICY_NOT_CONFIGURED`, **não ALLOW**.

Abrange:
- Seis operações (`read`, `search`, `update`, `export`, `report`, `execute`) com política e descrição válidas, todas **negadas**.
- Canais `interactive`, `job`, `ai`, `support`: operações não interativas não ganham poderes por serem executadas por um worker ou agente.
- Tentativas de cruzar empresa, trocar principal, reconstruir contexto com UUIDs iguais sem preservar o vínculo do envelope, adulterar escopo `record`/`team`/`unit`, alegar acesso a campo, adulterar classificação ou versão da política.
- Snapshots com `Suspended`/`Disabled`, ou membership de outro tenant/principal, não produzem envelope sinteticamente válido.
- Ausência de envelope não concede acesso; sinais mínimos de negação não vazam UUID de empresa ou principal.
- Nenhum método construtor público `AuthorizationDecision::allow` é oferecido.

**O que não provam:** identidade autenticada de verdade, revogação temporal, múltiplos memberships reais, PII, owner/field grants, RLS numa base persistente, conexões do CRM à VPS, proteção contra superuser, segurança do Portal e aceites LGPD. Um `TenantContext` ou `MembershipSnapshot` construído em memória não é credencial nem autorização. A matriz é uma **prova de regressão de política NO-GO**, não revisão independente.

## Aceite técnico
Requer F0/Laravel, PostgreSQL/RLS efêmero, Sites e AIA **success** no SHA exato antes de integrar na DEV. Deve manter `app/Core/Authorization`, rotas, migrações, grants, `main`, VPS e produção **sem modificações**. Não atribuir homologação a pessoa inexistente; a issue #27 exige revisor SEG/DBA independente e #259 mantém integração real Portal–CRM bloqueada.

A revisão de autorização positiva `ALLOW` continua **não especificada nem liberada**.
