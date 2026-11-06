# CRM Imagine — FUNC-P0-01/S01-R10-A15 — Encadeamento IAM, membership, `read_self` e bloqueios para GO funcional (PROPOSTA v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo). **Estado:** PROPOSTA DOCUMENTAL, sem decisão técnica final ou autorização de código.

## 1. Motivo e escopo

A Direção ratificou [S01-R01-D01..D05, alternativas A, somente documentais](CRM-DEC-FUNC-P0-01-S01-R01-D01-D05-alternativas-A-2026-11-06.md), após [A14-D01..D05](CRM-DEC-FUNC-P0-01-S01-R10-A14-D01-D05-alternativas-A-2026-11-06.md). Esta **pauta de dependências** responde à orientação para concentrar as próximas decisões na autenticação, no vínculo ativo e na política contextual `read_self`, sem transformar planejamento em implementação.

**Cadeia de autoridade futura candidata:** humano autentica de modo verificável → sessão válida/consentida e nível de MFA homologado → identidade oficial → membership própria ativa/versionada → tenant corrente derivado pelo backend → policy específica por ação/recurso/campo/escopo e precedência de deny → leitura com role sem bypass e contexto transacional → resposta mínima CAM01/02. **Cada seta é um requisito a comprovar, não uma concessão atual.**

## 2. Inventário de código na branch DEV (observação estática, não homologação)

| ID | Arquivo | Evidência atual | Lacuna | Dependências |
|---|---|---|---|---|
| EX-01 | `ExperimentalAuthenticator.php` | begin/completeMfa/stepUp candidatos em laboratório; comentário explícito 'No routes, registration, enrollment or external delivery'. | Apenas laboratório; não é fluxo de cliente nem interface login autorizada. | CORE04/SEG |
| EX-02 | `DatabaseSessionAuthority.php` | verifica estados da sessão, MFA, revogação, credencial, janela temporal e role PostgreSQL em transação isolada. | Contratos de sessão/MFA de produção e política de fluxo funcional não homologados. | CORE04/07/SEG |
| EX-03 | `PostgresActiveMembershipStore.php` | transaction de PostgreSQL, role sem superuser/bypass/owner, `FORCE RLS`, tenant GUC transacional e consulta membership própria ativa. | Necessita demonstrar cadeia de autoridade *sessão→identidade→vínculo→tenant* e vínculo ao mecanismo de autorização de campo, com não regressão de pool. | CORE01/02/07/SEG |
| EX-04 | `DenyByDefaultAuthorizer.php` | valida forma dos contratos experimentais e **sempre nega**, inclusive para envelope aparentemente completo. | Nenhuma ação `read_self` permitida; não acrescentar `ALLOW` por dedução deste plano. | CORE02/SEG |
| EX-05 | `routes/web.php` | sem rota CRM funcional, apenas `/up` de bootstrap. | Nenhuma superfície pública de login, leitura, API ou conta autorizada. | CORE04/02/DEV-GATE |
| EX-06 | `AppServiceProvider.php` | perfil de IAM `experimental` registrado somente em `local/testing`; authorizer vinculado a `DenyByDefaultAuthorizer`. | Feature flag/role experimental não pode ser reaproveitado como produção por simples configuração. | IAM/SEG/DEV-GATE |

**Ref verificada:** `dev/fast-track-crm-2026-11-04` HEAD `8c9683acfd8decf98544402acda360d7e0f0a489`; os caminhos acima estão sob `app/Core/Identity/`, `app/Core/Tenancy/`, `app/Core/Authorization/`, `app/Providers/`, `routes/`. É uma leitura do código existente, **sem alterar PHP**.

## 3. Contratos mínimos a resolver antes de futura implementação funcional

| ID | Contrato | Evidência e estado desejado | Decisão em aberto |
|---|---|---|---|
| IAM-01 | Principal humano legítimo | Fonte de identidade validada server-side; sessão ativa e vinculada à autenticação efetivamente concluída (MFA quando requerido). | Sem IAM `experimental` habilitado para cliente; não criar login/rota nesta fase. |
| IAM-02 | Vínculo próprio validado | Membership própria ativa, versionada, bloqueio de revogado/pendente, isolamento do tenant A/B, reavaliação por operação. | Nenhum campo de outro tenant exposto nem mesmo em erros, logs e cache. |
| IAM-03 | Policy contextual | `read_self` definida por recurso/ação/campo/escopo; sem grant aplicável ou com deny aplicável => negar. | Sem `ALLOW`, grant físico ou política positiva por esta proposta. |
| IAM-04 | RLS como defesa adicional | Leitura SQL por role não-owner/não-bypass; GUC transacional com limpeza em pool e estado consistente do tenant. | Provas de membership experimental não homologam todas as entidades. |
| IAM-05 | Contrato de estado | Não confundir estado de sessão, membership, organização e assinatura; EST-05..12 pendentes; fail-closed sem negar direitos de portabilidade por inferência. | CORE08/FIN/CON/LGPD devem decidir antes de regra executável. |
| IAM-06 | Privacidade e visibilidade | Somente CAM01/CAM02 autorizados, HTML escapado, sem exposição de IDs ou PII/campos extras. | Fonte de `display_name`, owners e classificação LGPD ainda não aprovadas. |
| IAM-07 | QA/homologação | Matriz de 22 cenários S01-T01..T22, incluindo acesso cross-tenant, revogação, permissões, roles, cache e falhas. | Revisão especializada/competente e dados sintéticos sem confundí-los com prova produtiva. |

## 4. Trabalho realmente bloqueante — não apenas mais documentação

1. **CORE04/SEG:** fechar contrato de autenticação humana, sessão, autenticação multifator proporcional, expiração, revogação, erro e auditoria; sinalizar claramente o que é perfil experimental vs uso real.
2. **CORE01/07/SEG-DBA:** definir fonte confiável da seleção de tenant, validade da membership, isolamento SQL, cache/pool e consequências da revogação no mesmo instante lógico da consulta.
3. **CORE02-D04..D10:** especificar fonte de grant/deny, legitimidade por campo CAM01/02, escopo e precedência da alternativa B/M2 documentada; GOV02 ainda precisa de deliberação quando relevante. **Nada de `ALLOW` hoje.**
4. **CORE08/FIN/CON/LGPD:** resolver EST-05..12 e direitos mínimos legítimos em trial/cortesia/suspensão/cancelamento sem misturar assinatura, membership e autenticação.
5. **QA/CORE10/DEV-GATE:** definir evidências positivas/negativas S01-T01..T22, owners e revisões técnicas; aprovação distinta para qualquer branch/código/teste funcional e para eventual implantação.

## 5. Propostas A15-D01..D05 — requerem decisão posterior

| ID | Tema | Alternativa A recomendada DOCUMENTALMENTE | Alternativa B não recomendada |
|---|---|---|---|
| A15-D01 | Fronteira de IAM | A: manter autenticação humana real como contrato/entrega independente da tela S01; sessão válida de serviço experimental não vira acesso público; definir MFA/step-up e origem confiável em CORE04. | B: abrir login público reaproveitando flags e endpoints inexistentes. |
| A15-D02 | Principal/tenant/membership | A: exigir encadeamento verificável principal autenticado→membership ativa e versionada→tenant corrente no servidor→escopo de recurso/campo, com revalidação e fail-closed. | B: aceitar `tenant_id`, GUC ou CNPJ enviado como prova. |
| A15-D03 | Autorização `read_self` | A: especificar primeiro a decisão contextual e campos CAM01/02/deny (CORE02) como contrato; **manter `DenyByDefaultAuthorizer` sem ALLOW** até revisão técnica, autorização específica e homologação pertinente. | B: permitir leitura genérica antes de política e auditoria. |
| A15-D04 | Estados comerciais e LGPD | A: manter EST-05..12 pendentes por CORE08/FIN/CON/LGPD e decidir tratamento proporcional de direitos legítimos sem presumir entitlement de pagamento. | B: bloquear ou permitir indiscriminadamente por status do plano. |
| A15-D05 | Ordem de engenharia / GO | A: consolidar evidências e pareceres antes de habilitar a jornada, eventualmente separar tarefa de testes negativos **sem acesso positivo** como incremento futuro após autorização específica, com branch filha e PR DRAFT. | B: fazer merge, código funcional ou deploy por aprovação deste documento. |

**As alternativas de A15 não foram aprovadas por esta publicação.** A aceitação de A15 documentaria prioridades e barreiras; não produziria GO de código. Não basta criar 5 novos documentos para fechar CORE/SEG ou homologar credenciais.

## 6. Primeiro recorte de engenharia potencial — ainda NÃO autorizado

Se houver decisão expressa posterior e requisitos aplicáveis forem satisfeitos, o primeiro recorte técnico pode ser **somente testes negativos/contratos de entrada da cadeia de autenticação e autorização sem fluxo de leitura positivo**. Evitar reprogramar IAM experimental existente. Não inventar uma rota de login, evitar ALLOW, dados reais, SQL de produção, n8n, Portal e integrações. Exigir: código/branch/arquivos e limites nominados, cenários de falha, CI sintético, PR DRAFT, revisão humana competente quando aplicável e autorização separada de merge.

## 7. Controles de mudança e estado

**C01..C08 PENDENTES; 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS.** PR #23 (migrations) DRAFT, PRs #2/#21 DRAFT, DEV base e `main` preservadas; nenhuma rota, SQL, `ALLOW`, teste funcional S01, VPS, usuário ou dado real criado. A estimativa gerencial de progresso do código permanece em **~10%**, sem aumento por progresso documental.