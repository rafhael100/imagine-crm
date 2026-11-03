# CRM-DEC — Aceite explícito da CRM-REV-01 como base documental

**Data:** 2026-11-03. **Manifestação literal do responsável:** “aprovado”.
**Objeto da aprovação:** recomendação imediatamente anterior de aprovar a [CRM-REV-01 — Núcleo multiempresa, identidade, usuários e permissões](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) **como base documental**, preservando os 13 princípios extraídos de fontes históricas e atuais e mantendo 10 lacunas CORE-P0 abertas. A autorização contextual também abrange **continuar a documentação na CRM-REV-02 — Planos, Trial, Assinaturas, Cobranças e Benefício CRM Básico**, sem reabrir a revisão global de preços antes do encerramento do planejamento.

## Abrangência reconhecida

- A CRM-REV-01 passa de “minuta para revisão” a **base documental de trabalho aprovada**. Isso preserva as regras documentadas de SaaS multiempresa, tenant_id + RLS no PostgreSQL com aplicação Laravel, fronteiras de identidade, MFA, convites, auditoria e proteção de operações assíncronas, nos limites da fonte e sua precedência.
- Os dez pontos CORE-P0-01..10 são **lacunas de fechamento**, não dez novas funcionalidades ou decisões técnicas já homologadas.
- Prosseguir na reconciliação dos capítulos comerciais, usando fontes da branch histórica e documentos vigentes da `main`, sem transcrever sínteses como fonte primária literal nem converter propostas históricas de preços em decisões definitivas.

## Limites expressos do aceite

Esta manifestação **não aprova** schemas, planos de RLS físicos por tabela, perfis efetivos por campo, novos direitos, step-up/MFA adicional por operação, permissões de agentes, alçadas, procedimentos de identidade/recuperação, provedores, parâmetros numéricos, testes nem data de programação.

A aprovação **não encerra** os CORE-P0, IN-168, INT P0, G01–G06 ou a revisão global do projeto. A CRM-REV-02 a produzir em seguida será **proposta para revisão**, sem aceite implícito.

**Regra de desenvolvimento preservada:** somente após consolidação integral do planejamento, revisão global, referência documental versionada e aprovação expressa do responsável será autorizada programação com Codex. Nenhum código, teste executado, produção, conta/credencial real, cobrança, contratação ou implantação.
