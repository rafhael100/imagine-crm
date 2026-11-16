# CRM-AIA-F249 — candidato unificado de integração com Imagine Agentes de IA

**15/11/2026 · pré-homologação estritamente sintética · DRAFT / NO-GO / NO-MERGE / NO-DEPLOY**

Esta revisão reúne **F241–F248** a partir do CRM Laravel privado (PR #614) em uma única branch, para evitar conclusões baseadas em testes dispersos. O workflow de aceitação `ci-crm-aia-f249-unified.yml` executa no **mesmo commit** o teste de ingresso HTTP 404, assinatura Ed25519 original Agents, escopo fictício, nonce PostgreSQL16 atômico entre duas conexões, keyring CRM com empresa derivada do backend, role de leitura exclusiva/ FORCE RLS e bloqueio físico de revogação concorrente.

## Testes obrigatórios executados pelo workflow

- `AgentsPrivateIngressCandidateTest`: antigo F248 da aplicação privada, apenas HTTP 404.
- `AgentsPrivateHttpCycleTest`: F241, gera assinatura com classes originais Agents, reconhece negativa Laravel/Symfony, nunca ALLOW.
- `AgentsPrivatePgNonceTest`: F242, nonce PostgreSQL16 contra replay por conexões independentes.
- `AgentsPrivateHttpPgCycleTest`: F243, ingress HTTP sintético + assinatura + nonce na mesma prova.
- `AiaPostgresAtomicNonceCandidateTest`: teste anterior de unicidade e rollback do ledger PG16 original.
- `AgentsPrivateOwnedKeyringTest`: F245, empresa e chave pública de tabela CRM globalmente única, rejeita spoofing/revogação.
- `AgentsPrivateRestrictedKeyringTest`: F246 e F247, leitura de chave por role **SELECT-only**, FORCE RLS, integração com nonce e negativa uniforme.
- `AgentsPrivateKeyRevocationLockTest`: F248, lock da linha da chave sob role restrita e tentativa de revogação por outra conexão; após revogação, nega nova prova.

Duas revisões de Agents são fixadas: `fbf50543e7c52e6c59c6ffec58b7f6a6d30564b5` (F238–F240) e `fe79e7e9a55e4d6728ab42c2ef578e40d9bd68ba` (contrato histórico F248 CRM). Os checkouts são somente leitura, sem credenciais persistentes. O workflow recusa HEAD divergente, rotas de Agents e publicação no CI.

## Segurança e restrições

Nenhuma API de leitura de lead é adicionada. Todos os diagnósticos são sintéticos e o adaptador HTTP continua exclusivamente `404 / DENIED_OR_NOT_FOUND`; jamais devolve dados ou autorização. Funções PHP de consulta de chave são explicitamente restritas ao runner e ao banco descartável `imagine_crm_ci`. Roles e tabelas são eliminadas após os testes. **CI verde é qualidade de laboratório, não autorização de produção.**

Ainda faltam: reconciliação com o IAM empresarial efetivo e regras de delegação/revogação; papel PostgreSQL operacional com mínimo privilégio no mesmo contrato transacional; certificados S2S/mTLS, TLS de origem, key management e rotation; auditoria/retenção LGPD, tratamento de falhas/rollback, revisão independente CORE/SEG/DBA/IAM e os **14 gates, 7 decisões e 13 cenários reais da CRM #57**.

Nenhum merge, deploy, DNS, credencial operacional, pagamento ou escrita publicitária é permitido por este recorte.
