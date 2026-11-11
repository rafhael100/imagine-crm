# CRM F3-77 — Separação de submissão lógica e duplicidade de pessoa

**Data:** 11/11/2026. **Base funcional:** [CRM-REV-03](../../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), DAT-R08/DAT-R10, e [issue de desenvolvimento #35](https://github.com/rafhael100/imagine-crm/issues/35). **Escopo:** PHP puro, sem identidade de serviço, dados reais, banco ou efeitos de negócio.

## Problema que o Core precisa distinguir

Uma mesma submissão lógica de um formulário pode ser retransmitida, através de mais de uma tentativa ou API, sem necessariamente representar uma pessoa duplicada. **Envios diferentes, mesmo com dados iguais, continuam submissões diferentes** e não devem ser mesclados. Além disso, um identificador declarado pelo remetente **não prova** a propriedade da organização nem a autenticidade da entrega; é necessário vínculo servidor–servidor verificado, idempotência persistente e ACK durável, ainda pendentes de aprovação.

## Código entregue

`SyntheticSubmissionClaimInspector::compare(first, second)` recebe dois pares **exatamente com as chaves** `organization_reference` e `submission_reference`, ambos UUIDs técnicos. Não aceita nome, e-mail, telefone, hash, payload ou campos extras. Dados inválidos retornam `CLAIM_INVALID`, sempre sem ecoar o conteúdo.

Os possíveis códigos de avaliação **somente estrutural, não autorizante** são:

- `SAME_SUBMISSION_CLAIM_UNVERIFIED`: ambas as referências declaradas coincidem, mas nada foi autenticado;
- `DISTINCT_SUBMISSIONS_UNVERIFIED`: organização textual coincide, submissão difere;
- `SUBMISSION_ORGANIZATION_CLAIM_CONFLICT`: mesma submissão declarada sob referências de organizações diferentes;
- `UNRELATED_SUBMISSION_CLAIMS`: ambas as referências diferem;
- `CLAIM_INVALID`: formato/estrutura rejeitado, sem expor campo ou entrada.

O retorno `SyntheticSubmissionClaimAssessment` contém **somente** `reasonCode` e os métodos `canTreatAsAuthorizedRetry()`, `canInferContactDuplicate()`, `canMergeContacts()`, `canPersistLead()` e `canAcknowledgeDelivery()`, todos **false** para todos os casos. Isso previne que a classificação de string seja confundida com controle de autorizações, deduplicação de pessoa ou confirmação de persistência.

## Testes e limites

`SyntheticSubmissionClaimInspectorTest` cobre pares iguais, IDs distintos na mesma organização, alegação cruzada entre empresas, casos não relacionados, capitalização hexadecimal, repetição de chamadas e estrutura adversarial com dados pessoais extras/UUIDs malformados — sempre fail-closed e sem vazamento de ID na resposta.

A integração só pode ocorrer **na DEV** após F0 Laravel, PostgreSQL/RLS, paridade Sites e Agents na HEAD exata, revisão de diff e `behind_by=0`. Nenhuma rota ou serviço foi criado, nenhum payload real é aceito, e nenhuma alteração em `main`, VPS, custos, produção, IAM/ALLOW, SQL ou migrations foi autorizada. O gate independente CORE/SEG/DBA/LGPD e [issue #259](https://github.com/rafhael100/imagine-crm/issues/259) **permanecem NO-GO** para integrar Portal→CRM real.
