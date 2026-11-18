# CRM–Sites F400 — autenticação S2S fictícia Portal→CRM (PostgreSQL16 CI)

**DRAFT / NO-GO / NO-MERGE / NO-DEPLOY / NO-REAL-KEY / NO-PII.**

Continua a F399 (CRM PR #607), usando dados exclusivamente sintéticos do pedido e do comprador. Exercita a captação original do Sites F398 (Sites PR #479, SHA 1e24f47f7dd96c0e5d4b68f5a71aed7c861179f4).

## Comportamento executável em runner descartável

1. LaboratoryPortalS2sBindingGateway::signForCi assina com HMAC de **chaves públicas falsas de laboratório**, embutidas no código apenas para testes. Canonicaliza protocolo, serviço origem imagine-portal, destinatário imagine-crm, key ID, ação provision/revoke, organização, pedido, binding, versão do pedido, nonce UUID, emissão e expiração. Não existe emissor Portal operacional.
2. A tabela owner-only crm_lab_site_s2s_keys autoriza individualmente chave, serviço, audiência, ação e validade. Revogação da chave é terminal (trigger). A prova troca a chave provision-a para provision-b, mantendo revoke em chave independente.
3. crm_lab_site_s2s_inbox guarda recibos imutáveis e exclusivos por (source_service,nonce), inclusive entre chaves e empresas. Inserção do recibo e provisão/revogação fazem parte da mesma transação. Repetição byte-equivalente retorna synthetic_s2s_replayed; reutilização adulterada é negada.
4. A assinatura por si só não provisiona: é obrigatório pedido F399 cadastrado pelo proprietário do banco, com mesma versão, empresa e binding, contrato, aprovação, pagamento settled e consent recorded **fictícios**.
5. A prova completa percorre comando simulado Portal → F399 → binding PG07 → original emissor Sites F398 → CRM PG16/RLS → revogação de pedido/binding em transação única.

## Casos negativos automatizados

- Sem assinatura, com assinatura inválida, serviço/audiência/ação adulterada, claim de empresa diversa, binding não correspondente, versão incorreta, prazo maior que 300 s, clock futuro, key ID desconhecida e campo excedente.
- Mesmo nonce usado por outra chave; replay da chave já revogada; chave de provisionamento usada para revogação; proibição de reativar chave revogada.
- Revogação final impossibilita capturar novo lead do formulário; faturamento, contato e publicação seguem bloqueados.
- Papel crm_app_ci não lê nem altera as tabelas autoritativas S2S; chamadas fora do GitHub Actions terminam com DENY.

## Limites e gates reais

**Não utilizar a classe na operação:** as chaves HMAC são deliberadamente falsas e conhecidas de todos, logo não provam identidade. Ainda são necessários mTLS, segredos em vault, IAM de serviço, papel mínimo no PostgreSQL, APIs versionadas, contrato/PSP e LGPD reais, outbox/retry T0/72h/432h, auditoria de produção, testes Leaseweb e rollback. CRM #27 SEG/DBA, #259 CORE/LGPD, #557 GO e Sites #38 permanecem abertos.

CI dedicado: .github/workflows/ci-crm-sites-f400-pg16.yml. Mantém o verificador SEC-CI-02 original antes de qualquer DDL, usa PG16 descartável e executa o método test_portal_s2s_scoped_commands_replay_rotation_and_atomic_revoke com --fail-on-skipped. Somente considerar validado após GitHub Actions verde na HEAD exata.