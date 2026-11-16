# CRM P14 — Contrato recíproco de campos com código original dos Agents

**16/11/2026 — leitura mínima de laboratório, não uma API operacional.**

A candidata CRM [#630](https://github.com/rafhael100/imagine-crm/pull/630) usa PostgreSQL16 efêmero, role `SELECT` de coluna com `FORCE RLS` e projeta somente `ref` e `stage` em dois tenants fictícios. Os Agentes possuem o contrato `MockCrmReadRequest`, que só aceita os mesmos campos e exige JSON canônico e referências sintéticas.

A P14 executa os **dois códigos originais** no mesmo runner, sem reconstruir uma cópia do contrato:

- Checkout read-only do CRM P14 e da versão Agents [P12 #364](https://github.com/rafhael100/imagine-ai-agents/pull/364), **fixada no SHA `aacbfdaa9fbfd878834d3c7b46eeaf99b4b8fdd9`**.
- O teste `Op01AgentsReciprocalFieldContractTest` carrega o `MockCrmReadRequest` original da versão Agents e usa reflexão na classe PHP real `Op01SyntheticLeadProjectionLab` do CRM para confirmar a paridade exata `ref/stage`. Testa quatro combinações válidas de campos, vínculo de tenant/purpose/resource, rejeição de e-mail, notas e duplicidades, JSON adulterado e ausência de rota de negócio.
- No mesmo job são executados os testes **reais PostgreSQL16** de role/coluna/RLS da P2 e o adaptador P0 deny-by-default com flag OFF. Isso confirma contratos e isolamento **somente com dados fictícios**.
- Nenhuma rota `/api/agents/op01` é adicionada, nenhum SSO ou login compartilhado é criado, e a aplicação real não recebe `ALLOW`.

**Limitações relevantes:** executar testes PHP originais em um runner não equivale a conectar os serviços nem a provar IAM operacional, ACL de lead/campo ou consentimento do cliente. O adaptador CRM e a projeção P2 permanecem desacoplados por desenho. Ainda não há certificado mTLS real, vínculo a contratos de cliente, serviço Agent autenticado, chave de assinatura de produção, auditoria LGPD, rollout/rollback VPS ou aprovação do CRM OP-01 [#57](https://github.com/rafhael100/imagine-crm/pull/57). O checkpoint é uma evidência de compatibilidade de código e de banco no CI, **não uma homologação**.

**DRAFT / NO-GO / SEM MERGE / SEM DEPLOY / SEM CUSTOS.**
