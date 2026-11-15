# CRM-SITES-F398 — contrato de transporte Sites→CRM com fontes originais

**Candidato de revisão / DRAFT / NO-GO / NO-MERGE / NO-DEPLOY / NO-PII / NO-ALLOW.**

## Evidência técnica proposta

O CI de integração executa o emissor PHP original do Imagine Sites PR #479 em SHA fixa 1e24f47f7dd96c0e5d4b68f5a71aed7c861179f4 e compara diretamente os bytes e assinaturas com o método ORIGINAL LaboratorySignedSiteCapture::canonicalForCi do CRM. LeadSubmissionIdentity e SyntheticIdempotencyClaim também são classes originais, não imitações.

São testadas as três origens permitidas; retry idêntico; mudança de organização, vínculo, submissão, site, formulário, destino, lead, título, canal, nonce e janela de expiração; e falha segura fora da flag de CI. Sem requisição de rede, banco, emissão de lead ou dependências externas.

## Provisionamento real ainda NÃO implementado

- O contrato da captura não fornece identificação autenticada do emissor Sites ou do usuário final, nem comprova consentimento ou contrato/assinatura.
- O vínculo empresa/site/formulário/destino deve existir em registro operacional do CRM, criado somente por serviço autenticado, com auditoria, limites, revogação e versionamento.
- Original CRM LEADS-PG07 demonstra advisory lock e FORCE RLS apenas em PostgreSQL16 efêmero; a prova de bytes deste CI NÃO executa esse banco nem substitui SEG/DBA.
- Homologação requer servidor próprio, isolamento de papéis, mTLS, vault, outbox durável, idempotência, restrição de dados pessoais, backup/restore e publicação controlada.

Enquanto os gates #27, #259, #564 e #557 estiverem abertos, não ativar rotas de produção, APIs de leads, provisionamento, cobrança ou entregas aos clientes.
Links: Sites PR #479 e issue Sites #38; CRM PR #596 e seu candidato integrado ainda em revisão.