# CRM F3-128 — Pré-validação de envio de proposta comercial

**Data:** 11/11/2026 · **Escopo:** PHP Core em DEV; nenhum comando comercial real · **Referências:** VEN-R09/R11/R12/R13/R14, VEN-T05/T06, VEN-P04/P05/P06.

## Objetivo

Uma proposta declarada em rascunho **não pode ser enviada automaticamente** apenas porque os valores de desconto estão dentro de uma regra ilustrativa. Um desconto acima de uma suposta política gerencial tampouco pode ser tratado como aprovado porque alguém forneceu a flag `managerApprovalClaimed=true`.

`SyntheticProposalSendPreflight` compõe os dois componentes PHP já integrados:

- `SyntheticProposalLifecycleInspector` valida **todos** os eventos fictícios e determina a versão/status atuais.
- `SyntheticCommercialDiscountReview` valida **todas** as linhas comerciais fictícias, subtotal, desconto por linha e necessidade de revisão segundo um limite arbitrário de laboratório.

A pré-validação apresenta apenas versão atual, status alegado, subtotal, desconto, total após desconto, sinalização de revisão gerencial e uma lista de **códigos fechados de impedimentos**. Não recebe cliente, documento, valor fiscal, aprovador identificado, destinatário ou credencial.

## Impedimentos

O resultado sempre inclui os bloqueios independentes `discount_policy_unverified`, `sender_identity_unverified`, `proposal_version_unverified` e `recipient_access_unverified`. Adiciona `current_draft_required` se a última versão não estiver declarada em rascunho, `manager_review_required` para desconto além do teto informado, e `manager_approval_unverified` quando o teste contém uma alegação de aprovação gerencial.

Mesmo quando o cenário possui apenas os impedimentos independentes, **nenhuma permissão é concedida**. `policy_verified`, `identity_verified`, `approval_verified`, `document_verified`, `recipient_verified`, `authorized` e todas as flags `can_*` permanecem falsas.

## Cobertura de testes

`SyntheticProposalSendPreflightTest` verifica rascunho com desconto dentro da política ilustrativa, desconto fora da política com e sem alegação de aprovação, alegação de aprovação mesmo dentro da política, versão já declarada aceita, ausência de proposta, payload comercial inválido, eventos inválidos e proteção contra dados pessoais. As entradas completas são validadas antes de qualquer saída; nenhum erro inclui conteúdo informado pelo chamador.

**Não há envio, geração de PDF, assinatura, cobrança, confirmação de desconto ou aprovação real.** A autorização operacional exige revisão independente de política por empresa, alçada, identidade, trilha auditável, acesso por destinatário e documento versionado. VEN-P04/P05/P06 e CORE/SEG/DBA/LGPD permanecem NO-GO. Nenhuma rota, SQL, IAM/ALLOW, dados reais, VPS, custos, `main` ou produção.
