# CRM F241 — prova recíproca com código original dos Agentes em Laravel privado

**15/11/2026 — DRAFT / NO-GO / NO-MERGE / NO-DEPLOY / SEM NOVOS CUSTOS**

## Objetivo

Testar o envelope assinado gerado pelo **código original Imagine AI Agents** (F238/F239, PR #332), dentro do aplicativo **Laravel funcional privado** (CRM PR #614), sem registrar API de leitura de leads.

A prova fixa Agents SHA `fbf50543e7c52e6c59c6ffec58b7f6a6d30564b5`, que passou CI Agents R01/CRM no respectivo commit. A origem CRM é a branch da PR #614, não a árvore F0 de segurança da PR #602. Não há merge entre árvores.

## Código

- `tests/Feature/AgentsPrivateHttpCycleTest.php` carrega classes Agents a partir do checkout exato no runner, monta um envelope F238 após Ed25519 + escopo + replay sintético F235, usa `Request::create` **somente em memória**, sem URL de rede e sem registrar a rota, e invoca o adaptador original `AgentsSignedIngressCandidate` da CRM F248.
- Primeira assinatura de teste é verificada pela implementação original Agents; segunda tentativa com mesmo nonce falha no verificador local. Ambas têm a **mesma negativa** HTTP 404, `DENIED_OR_NOT_FOUND`, `projection=null`, `real_authorization=false`, `external_calls=0`.
- O parser original Agents F239 recebe os bytes Laravel e valida o perfil exato F248 (`private, no-store, max-age=0`, `x-robots-tag`, `x-content-type-options`). Cabeçalhos falsificados de Authorization, Cookie, tenant, role e service identity são recusados antes da callback, sem token ou serviço externo.
- O workflow `ci-crm-aia-f241-reciprocal.yml` fixa ambas as SHAs, executa PHP 8.4 e testes F241/F248 existentes em GitHub Actions, sem PostgreSQL, dados de negócio ou implantação. Entra exclusivamente por PR em rascunho contra a PR funcional #614.

## Limites e próximos gates

O ensaio é de **prova executável de compatibilidade sintética**. Não demonstra sessão real de serviço, TLS/mTLS, IAM/ACL aprovado, banco de nonces PG16 partilhado, fonte operacional de chave protegida, autorização por principal/recurso/campo ou consentimento LGPD. O CRM continua autoridade exclusiva sobre eventual acesso. Não há endpoint de leitura de leads, rotas novas, deploy, alteração de DNS, VPS ou `main`.

**CRM #57:** 14 gates, 7 decisões e 13 cenários reais permanecem sem homologação. A reconciliação controlada dos mecanismos de CRM #602 com o aplicativo privado #614 exige revisão independente de CORE/SEG/IAM/DBA/LGPD antes de qualquer GO.

**Nenhuma ação posterior é autorizada automaticamente pela aprovação da CI.**

**Ajuste do contrato de cache:** o teste recíproco revelou que Symfony normaliza o cabeçalho do CRM F248 para `max-age=0, no-store, private` e não a ordem literal declarada na classe. A revisão Agents `fbf50543e7c52e6c59c6ffec58b7f6a6d30564b5` preserva dois perfis fechados de negativa (F246/F248), aceitando essa normalização sem permitir 2xx/ALLOW. A verificação CRM F241 exige CI recíproca verde nesta revisão exata.
