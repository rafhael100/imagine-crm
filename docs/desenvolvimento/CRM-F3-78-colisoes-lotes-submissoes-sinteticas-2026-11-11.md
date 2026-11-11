# CRM F3-78 — Revisão posicional de colisões de submissões técnicas

**Data:** 11/11/2026. **Origem:** DAT-R08/R10 de CRM-REV-03, [F3-77](CRM-F3-77-idempotencia-logica-versus-pessoa-2026-11-11.md). **Escopo:** PHP puro, dados fictícios e nenhum efeito de negócio.

## Incremento

`SyntheticSubmissionClaimBatchInspector::inspect($claims)` analisa **entre 1 e 50 reivindicações técnicas de submissão** e devolve exclusivamente **pares de índices numéricos começando em 1**:

- `sameOrganizationClaimPositions`: mesma referência técnica de organização **declarada** e mesma referência técnica de submissão. Isso **não** comprova retransmissão autorizada.
- `crossOrganizationClaimPositions`: mesma referência de submissão declarada sob **organizações textuais diferentes**. Indício de conflito sintático, nunca prova de acesso cruzado real.
- `claimsInspected`: total de entradas de laboratório.

O algoritmo compara os pares no máximo **1.225 vezes**, sem digest ou chave hash. Reutiliza `SyntheticSubmissionClaimInspector` para verificar UUIDs estritos e permitir somente as chaves `organization_reference` e `submission_reference`. Qualquer elemento inválido rejeita **todo o lote** com mensagem genérica sem ecoar conteúdo, sem resultado parcial. Os campos, UUIDs, contatos, telefones, e-mails, hashes e payloads **não são devolvidos, armazenados ou usados para identificar pessoas**.

`SyntheticSubmissionClaimBatchSummary` sempre retorna `false` em `canTreatAsAuthorizedRetry`, `canMergeContacts`, `canPersistLead` e `canAcknowledgeDelivery`. Entradas diferentes, mesmo com conteúdo comercial igual fora do contrato, não geram deduplicação. O relatório é diagnóstico inerte, não ledger persistente, autoridade por tenant, antirreplay, autorização ou protocolo de ACK.

## QA

`SyntheticSubmissionClaimBatchInspectorTest` cobre conflitos entre organizações alegadas, UUID em caixa alta, submissões distintas, lote com uma entrada, **50 entradas iguais/1.225 pares**, triplas com duas organizações, arrays associativos, campos pessoais extras, UUID inválido e tamanho 51, rejeitando entradas malformadas sem ecoar dados. CI exige F0 Laravel, PostgreSQL/RLS, Sites e Agents verdes na SHA exata antes de merge DEV.

**NO-GO:** nenhuma API, contato real, credencial, SQL/ORM, IAM, ALLOW, serviço, filesystem, rede, custo, VPS, produção ou `main`. A revisão independente CORE/SEG/DBA/LGPD da [issue #259](https://github.com/rafhael100/imagine-crm/issues/259) permanece necessária para qualquer recepção Portal→CRM real.
