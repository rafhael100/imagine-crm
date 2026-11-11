# CRM F3-51 — teste bilateral do código PHP Sites ↔ CRM (envelope e autoridade)

**Data:** 10/11/2026. **Escopo:** CI sintética, sem transferência de dados reais.

## A entrega

O projeto Sites já contém a classe `SyntheticCrmLeadEnvelopeV1Draft` (F1-131) e o `SyntheticLeadBindingAuthorityPreflight` (F1-138), que validam o formato e a correspondência de cinco referências técnicas com uma fonte externa **ainda não autenticada**.

A F3-51 executa o **código PHP real do Imagine Sites**, fixado no commit `057b6f859df5050c842a86d18676e6332a79d1f9` (PR Sites #183), ao lado do `SyntheticLeadEnvelopeV1Inspector` do **CRM real**. O script `scripts/ci/check-sites-crm-envelope-authority-witness.php` compara:

- Envelope gerado pelo Sites com 8 campos, seis UUIDs, versão 1, protocolo `imagine.lead-intake`, e resposta CRM `STRUCTURE_ONLY_UNVERIFIED`.
- Fonte de autoridade ausente/erro, vínculo inexistente, inativo, revisão inválida e divergência individual de organização, vínculo, site, formulário e destino.
- Uma correspondência de fixture `AUTHORITY_MATCH_UNVERIFIED`: **nenhuma** das duas aplicações pode aceitá-la como autenticação, permissão de persistência ou prova de entrega ao visitante.
- Falsificação de empresa ainda sintaticamente válida no CRM, mas rejeitada no preflight Sites, além de campos pessoais inesperados, falsa autorização, quebra de versão e ausência de referências.
- Resposta de avaliação restrita ao código de diagnóstico, sem ecoar identidade, PII ou payload.

A CI `.github/workflows/ci-crm-sites-synthetic-parity.yml` obtém o código Sites em um SHA fixado e executa em matriz PHP 8.3/8.4 sem banco, migrações, Composer, secrets ou credenciais. O checkout GitHub é apenas leitura.

## O que NÃO foi feito

Não existe conexão operacional Sites → CRM, token, serviço de autenticação, bind de tenant verificado, endpoint, entrega de formulário, payload de contato, consentimento LGPD, PostgreSQL persistente, enfileiramento real, VPS, custo nem `ALLOW`. O laboratório prova **paridade do código e semântica de negativa**, não SSO nem entrega de lead.

## Gates

Avaliação independente SEG/DBA #27, contrato Sites OP04 #36, Sites/Portal/CRM RLS & IAM, integrações F3-40..F3-46 empilhadas, revisão LGPD, staging e aprovação de produção continuam pendentes. Esta PR pode ser integrada somente à DEV após revisão de código, checks verdes na SHA exata e `behind_by=0`. Não alterar `main`.
