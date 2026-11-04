# CRM-DEC — Aprovação D01–D03 de CORE-P0-01 como base do modelo lógico

**Data:** 04/11/2026.  
**Manifestação expressa do responsável:** “Aprovo as propostas D01, D02 e D03 do CORE-P0-01 como base documental do modelo lógico do CRM Imagine. Registre minha aprovação no GitHub, mantenha as dependências técnicas abertas e prossiga para o CORE-P0-02, sem iniciar programação.”  
**Estado:** **APROVADO COMO BASE DOCUMENTAL DE MODELO LÓGICO**, não homologação técnica/física, não encerramento integral de CORE-P0-01 nem autorização de programação.

## 1. Decisões aprovadas

1. **CORE01-D01 — Identidade global e memberships:** uma identidade global de login no CRM poderá vincular-se a várias organizações CRM autorizadas; cada vínculo de participação (`membership`) terá contexto e atribuições de papéis/escopos próprios, sem duplicar a identidade de login por organização. Não presume SSO com Portal/Performance nem permite entrada sem vínculo válido.
2. **CORE01-D02 — Organização = tenant lógico:** cada organização CRM será um tenant lógico com isolamento de dados, permissões, planos/franquias e histórico. O mesmo CNPJ poderá ter várias organizações conforme regras de elegibilidade e contratação aprovadas (inclusive site elegível). Contrato de site, assinatura CRM, identidade e tenant não se confundem; não se acumulam quotas em uma única organização por múltiplos sites.
3. **CORE01-D03 — Invariantes de isolamento:** aprovar como **guia de detalhamento lógico** I01–I10 da [proposta CORE-P0-01 v0.1](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md). Revalidar contexto e autorização, negar operações sem vínculo válido, preservar isolamento Laravel + PostgreSQL RLS, prevenir contaminação entre tenants e usar identidades técnicas estritamente escopadas. O aceite **não** homologa a implementação exata de cada invariante.

## 2. Dependências preservadas sem aprovação antecipada

- **CORE01-D04 — CORE-P0-02/07:** matriz final de permissões, atribuições, vínculos, assentos, equipes, convites e transições; **aberto**.
- **CORE01-D05 — CORE-P0-03/04/05/09:** implementação do contexto RLS, sessões/MFA, suporte excepcional, service principals, cofre e trabalho assíncrono; **aberto**.
- **CORE01-D06 — FIN/CON/CORE-P0-08:** estados de tenant, CNPJ/representação, provisão idempotente, benefícios de Sites, assinatura/trial e direito financeiro; **aberto**.
- **CORE01-D07 — especificação executável:** esquema físico, entidades, chaves/tipos, constraints/índices, global vs. tenant, RLS por tabela/papel/serviço, APIs/eventos, testes e versões; **aberto**.
- Dependências INT P0-OWN/SEC/CON, SEG-01/02, ARQ-01/02/03, AGT-01 e gates G01–G06 seguem seus próprios estados. Aceite lógico não encerra os bloqueios.

## 3. Ordem e limitação expressas

**Próxima revisão autorizada:** CORE-P0-02 — perfis × recursos × ações × campos, somente documentação/proposta a submeter ao responsável.  
**Proibido inferir:** permissões efetivas novas, superusuário universal, autoaprovação, schema RLS, implantações, contratação, testes reais ou autorização de Codex. Permanecer com CORE-P0-01 **parcialmente aprovado e aberto**, até reconciliação das dependências e aceites executáveis.
