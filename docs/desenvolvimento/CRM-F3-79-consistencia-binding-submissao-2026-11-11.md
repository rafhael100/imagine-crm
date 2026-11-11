# CRM F3-79 — Consistência de referência técnica de vínculo por submissão

**Data:** 11/11/2026. **Fundamento:** [CRM-REV-03 DAT-R07/R08/R10](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), F3-77/F3-78, [issue #259](https://github.com/rafhael100/imagine-crm/issues/259). **Escopo:** PHP puro com entradas exclusivamente sintéticas.

## Funcionalidade entregue

`SyntheticSubmissionBindingClaimInspector::compare(first, second)` compara **três referências técnicas declaradas pelo remetente** em duas tentativas: `organization_reference`, `submission_reference` e `binding_reference`. Cada campo é um UUID estrito, e cada entrada possui **exatamente** essas três chaves. Campos extras de pessoa, consentimento, nonce ou payload são rejeitados como `CLAIM_INVALID` e jamais ecoados.

A classificação é puramente sintática:

- **`SAME_SUBMISSION_SAME_BINDING_CLAIM_UNVERIFIED`**: organização, submissão e vínculo declarados são iguais — **não há autenticação nem prova de retry**;
- **`SUBMISSION_BINDING_CLAIM_CONFLICT`**: organização e submissão declaradas são iguais, mas o vínculo declarado mudou — sinal de inconsistência, **nunca alteração automática do destino**;
- **`SUBMISSION_ORGANIZATION_CLAIM_CONFLICT`**: referência de submissão coincide entre organizações textuais distintas (tem precedência mesmo se vínculo coincide);
- **`DISTINCT_SUBMISSIONS_UNVERIFIED`**: mesma organização declarada, submissões distintas (não são uma pessoa duplicada);
- **`UNRELATED_SUBMISSION_CLAIMS`**: organizações e submissões declaradas distintas;
- **`CLAIM_INVALID`**: campos malformados, extras ou ausentes.

Reutiliza a classificação estrita F3-77 `SyntheticSubmissionClaimInspector` para organização e submissão, com validação separada da referência de vínculo. Retorna `SyntheticSubmissionClaimAssessment`, que **jamais concede** `canTreatAsAuthorizedRetry`, `canInferContactDuplicate`, `canMergeContacts`, `canPersistLead` ou `canAcknowledgeDelivery`; todos retornam `false`. Não guarda dados, IDs, tokens, hashes ou conteúdo do envio.

## QA e limites

`SyntheticSubmissionBindingClaimInspectorTest` cobre igualdade com caixa hexadecimal distinta, mudança unilateral de vínculo, conflitos interempresa com diferentes vínculos, submissões distintas com mesmo vínculo e campos adversariais (dados pessoais, consentimento, nonce, payload, UUID inválido) — sempre deny-only e sem vazamento dos identificadores.

Este código **não valida assinatura, não autentica Portal, não consulta organização, não vincula serviço, não confere consentimento, não escreve e não reconcilia ACK**. A propriedade de um binding só poderá ser validada por fonte independente do remetente, com IAM, segredo/rotação, tenant, RLS, observabilidade, persistência durável e homologação ainda pendentes na issue #259. Nada é ativado em `main`, produção, VPS, SQL, APIs, filas ou custos. A revisão independente CORE/SEG/DBA/LGPD continua NO-GO. Merge somente DEV após 4 workflows success na HEAD exata, diff revisado e `behind_by=0`.
