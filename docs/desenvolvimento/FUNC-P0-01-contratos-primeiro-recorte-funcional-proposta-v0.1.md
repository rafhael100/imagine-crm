# FUNC-P0-01 — Primeiro recorte funcional do CRM Imagine: pacote para avaliação

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Estado:** **PROPOSTA PARA DELIBERAÇÃO**, não aprovada/implementada.  
**Registro de decisão:** [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22).  
**Referências técnicas:** [F1-10](F1-10-consolidacao-gates-seguranca.md), [PR #20](https://github.com/rafhael100/imagine-crm/pull/20), [PR documental #21](https://github.com/rafhael100/imagine-crm/pull/21) e [CORE Etapa 2](../planejamento/CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md).

> Este documento **não concede `ALLOW`**, não é contrato executável, não autoriza codificação funcional, lançamento, mudança de `main`, uso de dados reais nem VPS. Nenhuma aprovação decorre da sua publicação. A branch DEV é apenas repositório de trabalho controlado.

## 1. Por que mudar de etapa

F1-02..F1-10 forneceram provas experimentais de IAM, membership, MFA, PostgreSQL RLS, contratos de autorização deny-only, autoridade assíncrona, diagnóstico, ledger de laboratório, reconciliação consultiva e regressões. Os testes verdes e o merge DEV são **evidências de laboratório**, não homologação de política produtiva nem proteção completa do CRM.

A [matriz F1-10](F1-10-consolidacao-gates-seguranca.md) recomenda delimitar **um recorte funcional**, designar owners, fechar somente as especificações e aceites necessários a esse recorte e submetê-lo à autorização de implementação independente. Todos os **67 IDs CORE**, SEG-01/02, AUT-P01..14, ARQ/INT/FIN/CON/LGPD e G01..G06 continuam tecnicamente abertos; não serão encerrados por este pacote.

## 2. Candidato recomendado — autoconsulta mínima da própria organização

**Proposta A (recomendada para apreciação, não decidida):** primeira fatia de **leitura controlada, exclusivamente pelo usuário humano legitimamente autenticado e vinculado à própria organização do CRM**, sem acesso da equipe Imagine a dados de cliente. Não é login público, nem sistema de criação/convite/alteração. A operação só poderá ser desenvolvida após aprovações explícitas de contratos, guards, fonte de dados, LGPD, testes positivos e negativos e ambiente autorizado.

| Item | Proposta de contrato semântico, não endpoint/schema | Restrição mandatória |
|---|---|---|
| `organization.read_own_summary` | Consultar resumo mínimo da organização à qual o principal **efetivamente pertence** | A identidade e o tenant devem vir de autoridade server-side; jamais confiar em ID/tenant solicitado |
| `membership.read_self` | Consultar apenas o próprio vínculo vigente com a organização atual | Sem enumerar todos os usuários, equipes ou outras memberships por analogia |
| Ator/canal | Humano autenticado com sessão válida e vínculo ativo; primeira superfície **interna ao CRM** a decidir | Sem staff Imagine, agente/serviço/n8n, suporte por bypass ou API pública neste recorte |
| Efeito | Leitura **read-only** de dados minimizados | Sem INSERT, UPDATE, DELETE, convites, export, backup, envio, fila, cobrança, alteração de plano ou efeitos externos |
| Escopo | `tenant:self` + `membership:self`, sem escopo global | Mesmo humano em tenants A e B não mistura empresas; troca de tenant exige nova prova |
| Fonte do resultado | Proprietário funcional de Organizações/Membership a designar; PostgreSQL RLS é barreira adicional | GUC `app.tenant_id`, CNPJ, papel declarado e JWT sem validação não são prova de identidade |
| Estado de assinatura | **Pendente de decisão**: quais leituras mínimas de conta são legítimas sob trial/cortesia/pago/suspenso/cancelado | Não criar entitlement ou negar direitos de portabilidade/gestão da própria conta por analogia |
| Autorização | Catálogo versionado, MFA/step-up quando exigido pelo contrato e decisão de negócio por ação | Nenhuma concessão real por este documento; default deny enquanto qualquer fonte faltar |

**Proposta de campos mínimos** (a homologar): `organization_id` opaco; `organization_display_name` já validado; `organization_state` definido pelo owner; `membership_id` e `membership_state` do próprio principal; rótulo de papel **apenas se explicitamente autorizado**. Não incluir na primeira resposta CNPJ completo, documentos, telefone, e-mails de terceiros, dados financeiros, identificadores de integração, segredos, outras memberships, colaboradores, clientes, leads, anexos ou IA. Categorizar campo por CORE02-D04 e LGPD antes de qualquer uso real.

**Propostas alternativas para decidir** (não programar nenhuma sem escolha): B — cadastro/convites/gestão interna da organização, com escrita e risco elevado; C — operação mínima de lead/contato, que depende de catálogo DAT, LGPD, entidade, campo e entitlement. A é mais estreita, mas ainda depende de IAM, membership, resposta e homologação de segurança.

## 3. Reconciliação do PR #21 e precedência CORE02

**PR #21 está DRAFT e separado, com destino `main`, NÃO integrado nesta operação**. Versão documental observada: head `a2395af7269eaac94504f497ab473068a57ca937`. As ratificações expressas registradas no próprio PR, inclusive a mais recente D05, são **fontes documentais sob integração pendente**, não grants, código homologado nem autorizações desta nova fase.

| Bloco CORE02 | O que foi ratificado / aceito documentalmente | O que este pacote ainda exige |
|---|---|---|
| D01–D03 | Base geral cumulativa, deny-by-default | Casos do recorte com owners, dados e evidências |
| D04 A–D | Classes de campo e direitos por ação/canal preliminares | Classificar **cada campo real** do resumo próprio, incluindo derivados |
| **D05 — alternativa B** | **Expressamente ratificada em 06/11/2026**: P1, união de grants positivos válidos no mesmo tenant/escopo, + P2, deny explícito qualificado prevalente só na interseção de recurso/ação/campo/escopo; bloqueios mandatórios superiores | **Ainda falta** especificação física, proveniência/vigência de grants e denies, representação de escopo, revogação/cache, delegação, autoria/competência, UX e testes por caso. D05 integral ABERTA |
| D06 A–D | Catálogo semântico de ações e owner ratificado preliminarmente | Catálogo concreto `organization.read_own_summary` / `membership.read_self` e versões, após aprovação do owner |
| D07 A–D | Ações sensíveis, MFA/step-up e alçadas ratificados preliminarmente | Fatores/sessões/janelas e riscos próprios da leitura escolhida; não importar números dos protótipos |
| D08 A–D | Enforcement server-side, RLS e revogação ratificados preliminarmente | Garantia ponta a ponta, SQL/roles, conexões/pool, revogação e testes de tenant B |
| D09 A–D | Planos/entitlement/cotas/estados ratificados preliminarmente | Regra por estado de assinatura e leitura de autoatendimento; tabela comercial e quotas finais permanecem sob FIN/CON |
| D10 A–D | Evidências, rastreabilidade e homologação ratificadas preliminarmente | Matriz executável por SHA, ambiente, principal, tenant, campo, canal e aceite competente |

A decisão técnica **delegada** anterior [P1+P2 escopada](../planejamento/CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md) está na base do repositório, mas os trechos mais antigos da minuta D05 que dizem “não escolhido” devem ser lidos à luz da **ratificação expressa da alternativa B no PR #21**. A ratificação não muda o fato de que D05 está ABERTA, nem transforma P1+P2 em algoritmo executável. **Não alterar/mesclar o PR #21, a `main` ou `docs/planejamento` nesta etapa.** Antes de implementar, reconciliar a versão normativa publicável com governança e aprovação independente.

## 4. Contratos que precisam ser decididos antes de qualquer implementação

| ID candidato | Pergunta de decisão / entregável exigido | Owner funcional a confirmar | Gate associado |
|---|---|---|---|
| FUNC01-C01 | Selecionar A, B ou C; definir precisamente operação, superfície, ausência de efeitos, sujeitos, dados e exclusões | Direção/Produto/Organizações | CORE10-D04/D05; CORE02-D06 |
| FUNC01-C02 | Identidade/membership verificada, escolha de tenant, sessões válidas, MFA apropriado, revogação e fallback fechado | IAM/Segurança | CORE01-D04..D07; CORE04-D04/D05; SEG-01 |
| FUNC01-C03 | Catálogo de recursos/ações/campos, classificação e mascaramento, escopo próprio, política B P1+P2 contextual sem grants globais | Organização/IAM/Segurança/Privacidade | CORE02-D04..D08; LGPD |
| FUNC01-C04 | Modelo de dados mínimo, FKs tenant-bound, constraints, migrations/rollback, RLS FORCE, roles SQL, ameaça GUC/SQL/pool | DBA/Arquitetura/IAM | CORE01-D05/D07; CORE02-D08; SEG-01/02 |
| FUNC01-C05 | Estados trial/cortesia/pago/suspenso/cancelado e direitos mínimos legítimos de autoatendimento, sem assumir número comercial | Produto/FIN/CON/CORE08 | CORE02-D09; CORE08-D04..D10 |
| FUNC01-C06 | Eventos/sinais de auditoria minimizados, finalidade/base legal, prazo de retenção e acesso compatível; sem gravar PII desnecessária | Privacidade/Auditoria/Segurança | CORE06; LGPD; CORE10 |
| FUNC01-C07 | Matriz de testes positivos/negativos, evidências por SHA, ambiente e aceites segregados; NO-GO claro | QA/Segurança/owners | CORE02-D10; CORE10; G01..G06 |
| FUNC01-C08 | Ambiente autorizado de homologação, plano de reversão/observabilidade e autorização explícita de código/revisão sem `main`/VPS | Arquitetura/Operações/Direção | SEG; ARQ; DEV-GATE; CORE10 |

Nenhuma pessoa é designada como owner por este documento; as competências listadas são **propostas**. Um mesmo papel não valida unilateralmente todas as áreas. Contratos técnicos, comerciais e jurídicos devem respeitar seus respectivos fluxos e aceites.

## 5. Critérios de aceite propostos para um futuro código funcional — ainda NÃO executados

**Positivos futuros, somente após aprovação do catálogo e escopo:** identidade legítima com sessão válida, MFA conforme ação e membership ativa da organização A; entitlement/estado aplicável confirmado; leitura mínima própria sem campos não autorizados; resposta limitada a uma organização e ao próprio vínculo; auditoria mínima legítima.

**Negativos obrigatórios:** sem sessão; perfil experimental desativado; token/tenant/URL forjado; organização B; membership suspensa, convidada ou revogada; conflito P1+P2 que afeta exatamente o escopo; ausência de grant; campo F-PII/F-SEC não autorizado; role do banco owner/BYPASSRLS; GUC herdada; falha de banco/Redis; MFA insuficiente para ação que a exija; plano sem direito aplicável; tentativa de listar membros/consultar terceiros; requisições via API alternativa, job, agente ou suporte sem mandato; dados vazando em logs/erros; indisponibilidade de auditabilidade quando mandatória.

**Provas exigidas:** testes unitários + Feature Laravel + PostgreSQL 16 FORCE RLS/roles + Redis conforme fluxos + concorrência/revogação, SQL/migração reversível, revisão de ameaça por Segurança/DBA, revisão LGPD e aceite formal dos owners; links de CI no commit exato. Uma tela aprovada visualmente não substitui enforcement server-side. Os testes F1 anteriores apenas fornecem infraestrutura de prova.

**Controle de versão e saída:** decidir pacote/schemas/código em branch posterior separada, PR DRAFT inicialmente para DEV, rollback explícito, sem mudança na `main`/VPS. Qualquer primeira política `ALLOW` exigirá **outra autorização expressa** com escopo, decisão de risco, código/migração identificados e aceites de segurança; o `DenyByDefaultAuthorizer` atual não pode ser substituído silenciosamente. Não promover as fixtures `tests/` ao runtime.

## 6. NO-GO e perguntas para a direção

**NO-GO atual para implementação de funcionalidade real.** O documento é submetido à avaliação e não fecha os 67 CORE, SEG, AUT, INT, LGPD ou governança global. A aprovação de um **recorte documental** não equivale a autorizar `ALLOW`, criar usuário real, liberar endpoint público, contratar serviços, implantar na VPS ou publicar.

Para deliberação explícita:
1. **Recorte:** confirmar candidato **A — autoconsulta mínima** como primeiro módulo, ou preferir B (organização e convites) ou C (lead/contato), com justificativa e escopo.
2. **Superfície/atores:** confirmar que o primeiro uso será somente no CRM para usuários legitimamente autenticados da própria organização, sem Portal/Imagine staff, integração, WhatsApp ou IA.
3. **Dados e estados:** aprovar como direção de estudo os campos mínimos acima e encaminhar a decisão de estados/entitlement e legitimidade/privacidade aos responsáveis, sem congelar política física.
4. **Governança:** confirmar que o próximo trabalho do Codex, se autorizado em ato posterior, será **contratos e especificação de aceite**, não programação de `ALLOW` ou endpoint, até que os gates relevantes tenham donos e aceites específicos.

**Até nova autorização:** manter `main`, PR #2 e PR documental #21 DRAFT, VPS e produção preservados; não ativar dados reais nem efeitos externos.
