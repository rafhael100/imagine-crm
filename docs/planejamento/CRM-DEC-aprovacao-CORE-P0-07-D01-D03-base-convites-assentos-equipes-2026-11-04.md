# CRM-DEC — Aceite expresso de CORE07-D01, D02 e D03 — convites, assentos e equipes

**Data:** 04/11/2026.
**Manifestação literal do responsável:** “Aprovo CORE07-D01, D02 e D03 como base documental do CRM Imagine. Registre a aprovação no GitHub, mantenha CORE07-D04 a D10 e todos os demais bloqueios técnicos abertos e prossiga ao CORE-P0-08, sem iniciar programação.”
**Estado:** **CORE07-D01–D03 APROVADAS COMO BASE DOCUMENTAL**; **CORE-P0-07 permanece PARCIAL/ABERTO**, sem homologação técnica, sem assentos concedidos e sem desenvolvimento.
**Documento-base deliberado:** [CORE-P0-07 v0.1](CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md).

## 1. Aprovação delimitada às três diretrizes lógicas

1. **CORE07-D01 — Convites por organização:** aceitar o modelo lógico de convites seguros individualizados com **validade de 7 dias**, usuário novo ou identidade global já existente, confirmação de legitimidade e organização, cancelamento/reenvio, proteção contra duplicidade e aceite idempotente. Convite e-mail/token não equivalem a membership ativa. Não ratifica esquema de token, algoritmo de verificação nem reservas de vagas.
2. **CORE07-D02 — Assentos e vínculos por tenant:** aceitar distinção entre identidade de login global, membership em cada organização e assento de usuário humano por tenant, com plano e entitlement próprios. Regras do **Básico: 1 usuário autenticado com papel Administrador da Empresa e MFA obrigatório**; equipes, profissionais agendáveis e identidades técnicas não são automaticamente assentos humanos. Acesso a várias empresas não confunde quotas, planos ou papéis.
3. **CORE07-D03 — Equipes, escopos e desligamento proporcional:** aceitar participação em múltiplas equipes do mesmo tenant, visibilidade própria/equipe/empresa subordinada à autorização por módulo/ação/campo, transferências/redistribuições por operadores competentes e auditadas. Desativação em uma organização revoga seus acessos e sessões ali sem eliminar a identidade global, vínculos válidos em outras organizações ou históricos e fatos legítimos da empresa; reativação revalida quotas e direitos.

Não foram aprovados preços, usuários incluídos de planos pagos, concessão irrestrita de assentos, transferência automática em massa, exclusão de dados, papéis executáveis, superacesso ou convites reais.

## 2. Itens explicitamente em aberto

| ID | Dependência técnica/operacional NÃO aprovada |
|---|---|
| **CORE07-D04** | Token físico, expiração/reenvio/cancelamento, prova de e-mail, conta existente, concorrência e idempotência do convite |
| **CORE07-D05** | Assento ocupado ou reservado, contagem por estado, convites simultâneos, trial, entitlements, quotas e adicionais |
| **CORE07-D06** | Administrador principal e administrador único Básico: recuperação, substituição, transferência de titularidade, salvaguardas e alçadas |
| **CORE07-D07** | Equipes, gestores, unidades, caixas, escopo exato e redistribuição pós-desligamento |
| **CORE07-D08** | Revogação em sessões, tokens, cache, jobs, exportações, anexos, canais e efeitos de mudança de plano |
| **CORE07-D09** | Auditoria e retenção de convites, papéis, vínculos, transferências e desativações, com LGPD |
| **CORE07-D10** | Contratos, testes negativos futuros, isolamento/concorrência e pacote de homologação |

**Demais bloqueios preservados:** CORE01-D04–D07; CORE02-D04–D10; CORE03-D04–D10; CORE04-D04–D10; CORE05-D04–D10; CORE06-D04–D10; CORE-P0-08/09/10; INT P0-OWN/SEC/CON/ACC, IN-168 ARQ-01/02/03, SEG-01/02, AGT-01 e G01–G06. Permanecem as dependências de FIN/CON, LGPD e fechamento global do planejamento.

## 3. Autorização de continuidade

Prosseguir apenas com **CORE-P0-08 — Básico público/cortesia, múltiplas organizações e trial único por CNPJ** como nova **proposta documental a submeter a aceite separado**, preservando a diferença entre contrato de site, elegibilidade de cortesia, plano do CRM, identidade/assento, organização e estados financeiros.

**Sem autorização para programação/Codex, testes reais, criação de contas/convites, implantação, cobranças, mudanças de infraestrutura, exclusão de dados ou contratação** até fechamento global e aceite final.
