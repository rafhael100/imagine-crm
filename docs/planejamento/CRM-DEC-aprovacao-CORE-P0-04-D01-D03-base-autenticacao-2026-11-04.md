# CRM-DEC — Aprovação de CORE04-D01, D02 e D03 como base documental do CRM Imagine

**Data:** 04/11/2026.
**Manifestação expressa do responsável:** “Aprovo CORE04-D01, D02 e D03 como base documental do CRM Imagine. Registre a aprovação no GitHub, mantenha CORE04-D04 a D10 e os demais bloqueios técnicos abertos e prossiga para o CORE-P0-05, sem iniciar programação.”
**Natureza:** aprovação de diretrizes lógicas de autenticação, recuperação e sessões, **não** da política executável, implementação, homologação ou fechamento integral do CORE-P0-04.
**Fonte deliberada:** [CORE-P0-04 v0.1](CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md).

## 1. Decisões aprovadas

- **CORE04-D01 — Política canônica de MFA por identidade, papel e organização.** A identidade de login global é distinta de permissões e memberships em cada tenant. MFA **obrigatório** para Administradores Gerais Imagine e Administradores da Empresa (inclusive o único do Básico). Para funcionários, MFA **opcional por padrão**, podendo ser exigido pela organização. A comprovação de MFA deve respeitar o contexto efetivo ao alternar tenant ou atuar em papel administrativo, sem conceder acesso entre organizações ou criar exigência universal indevida.
- **CORE04-D02 — Recuperação de credenciais e perda do segundo fator.** Manter recuperação de senha via endereço de e-mail verificado e link temporário, com verificações e rastreabilidade. Para Admin Geral sem fator e sem códigos de recuperação, a verificação por superadministrador competente e auditoria é obrigatória; **não basta e-mail**. Recuperar identidade não transfere titularidade, eleva privilégios, cria membership nem desliga MFA. O Administrador único do Básico requer procedimento protegido específico, sem segundo usuário operacional permanente por suposição.
- **CORE04-D03 — Sessões e autenticação reforçada para ações sensíveis.** Distinguir sessão da identidade, tenant/membership vigente e contexto de Administração Geral. Preservar **30 minutos de inatividade exclusivamente para a Administração Geral** conforme regra histórica, sem replicar automaticamente esse prazo para outros perfis. Sessões/dispositivos devem ser revogáveis conforme direitos, revogação por tenant não destrói os demais vínculos legítimos, e operações críticas poderão exigir reautenticação/step-up proporcional conforme política específica futura, **sem segunda aprovação administrativa universal**.

## 2. Itens não aprovados por este aceite

| Pendência aberta | Conteúdo |
|---|---|
| **CORE04-D04** | Métodos físicos de autenticação, autenticadores/passkeys, verificação e segurança de fatores, interoperabilidade/acessibilidade |
| **CORE04-D05** | Sessões, cookies/tokens, expiração por perfil fora da Administração Geral, rotação, dispositivos e concorrência |
| **CORE04-D06** | Antifraude, proteção contra tentativas abusivas, avisos/bloqueio/desbloqueio e limites |
| **CORE04-D07** | Recuperação de MFA/titularidade por papel, responsáveis, evidências, alçadas, contestação, caso sem outro superadministrador e único admin Básico |
| **CORE04-D08** | Matriz efetiva de ações críticas e requisitos de step-up/reautenticação/aprovação por ação, limites e período |
| **CORE04-D09** | Auditoria/retencão de autenticação, máscaras, classificação, integridade e consulta |
| **CORE04-D10** | Contratos de revogação em sessões/API/jobs/integrações e critérios técnicos de homologação |

**Dependências transversais mantidas:** CORE01-D04..D07; CORE02-D04..D10; CORE03-D04..D10; CORE-P0-05..10; INT P0-OWN/SEC/CON; IN-168 (SEG-01/02, ARQ-01/02/03, AGT-01), G01–G06, revisão global de política de segurança e aprovação final de desenvolvimento.

## 3. Continuidade autorizada

Prosseguir **somente com planejamento do CORE-P0-05 — acesso excepcional do pessoal Imagine aos ambientes de clientes**; separar suporte técnico, diagnóstico por telemetria e leitura/alteração de dados comerciais, com mandato por caso, finalidade, escopo, duração, revogação e auditoria. Preservar ausência de leitura irrestrita por Admin Geral, MFA exigido, RLS e autorização cumulativa CORE01/02/03/04. Nenhuma aprovação antecipada de novo papel, alçada, prazo de mandato ou procedimento de emergência.

**Não programar, executar testes, implantar, provisionar, criar credenciais reais, contratar ou gerar despesas** até fechamento global de planejamento com aceite expresso.
