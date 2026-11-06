# CRM-DEC — FUNC-P0-01/S01-R01 — Ratificação documental das alternativas A, D01 a D05

**Data:** 06/11/2026 (America/Sao_Paulo). **Natureza:** DIRETRIZES DOCUMENTAIS PRELIMINARES. Não é homologação operacional ou autorização de programação.

## 1. Manifestação e precedência

A Direção respondeu **“ok aprovado”** após a recomendação expressa da etapa anterior: avançar para a **aprovação documental de S01-R01-D01 a D05**, que define campos e estados da primeira tela, e posteriormente concentrar autenticação e autorização. O aceite refere-se às **alternativas A recomendadas** da minuta S01-R01, **não** a outras regras ou ao documento inteiro como contrato executável.

**Fonte exata:** [FUNC-P0-01 / S01-R01 — campos, estados e critérios de segurança](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) (blob `36a353caf695aa03aea3b0d6e0e13331a1fda5b3`). Ratificação anterior [A14-D01..D05](CRM-DEC-FUNC-P0-01-S01-R10-A14-D01-D05-alternativas-A-2026-11-06.md) é orientação documental de priorização, não substitui a decisão específica R01.

## 2. Decisões aprovadas documentalmente

| ID | Tema | Alternativa A aprovada como diretriz | O que permanece aberto |
|---|---|---|---|
| R01-D01-A | Visibilidade | CAM-01 (nome de apresentação da organização corrente comprovada) e CAM-02 (rótulo genérico do próprio vínculo ativo) são os **únicos dois atributos candidatos à apresentação**; CAM-03..CAM-10 excluídos da V0. | Modelo físico, origem definitiva do nome, taxonomia e autorização por campo permanecem pendentes. |
| R01-D02-A | Membership | Somente a própria membership ativa, validada no backend e associada à sessão humana legítima e ao tenant correto, pode ser **elegível** para consulta; estados pendente/suspenso/revogado ou inconclusivos negam. | Elegibilidade não equivale a `ALLOW`. MFA, revogação e sessão executável continuam pendentes. |
| R01-D03-A | Estados | Manter **quatro dimensões distintas** — sessão/identidade, membership, estado técnico da organização e estado contratual/comercial — sem definir regras definitivas de EST-05..EST-12. | CORE08/FIN/CON/LGPD, direitos mínimos e transições permanecem sem homologação. |
| R01-D04-A | Proteções | Priorizar sessão verificada, tenant resolvido server-side, MFA proporcional a definir, RLS não-bypass, contexto transacional, cache restrito e fail-closed. | Sem escolha física de roles, algoritmo P1/P2, grants/denies, rotas, DB ou step-up. |
| R01-D05-A | Evidência | Preservar **S01-T01..S01-T22** como futuros casos de homologação, com responsável, ambiente, SHA, risco, resultado e aceite. | Nenhum teste funcional S01 foi aprovado ou executado; CI F1 sintético não homologa o fluxo. |

**Alternativas B não foram aprovadas.** Estas cinco decisões não ratificam qualquer schema, campo físico, endpoint, algoritmo de acesso, TTL, política concreta de MFA, status de plano/trial/cortesia, modelo P1+P2/GOV02, titularidade de atributo ou implementação.

## 3. Matriz mínima de condições de negação (documental, não executada)

| Condição | Tratamento proposto até contrato aprovado | Dependência |
|---|---|---|
| Sessão ausente, expirada, revogada ou inconclusiva | Negar sem identificar a empresa | IAM/CORE04/SEG |
| Vínculo não ativo ou pertencente a outro humano/tenant | Negar sem listar colaboradores ou associação com terceiros | CORE01/07/RLS |
| Tenant derivado de query/header/GUC injetável | Não aceitar como autenticação/autorização | CORE01/SEG/DBA |
| Campo adicional ou estado financeiro sem direito homologado | Não divulgar CAM-03..10 nem inferir entitlement | CORE02/08/FIN/CON/LGPD |
| Ausência de grant validado ou colisão com deny aplicável | Negação por padrão, sem `ALLOW` automático | CORE02/SEG |
| Falha DB/Redis/auditoria mandatória/cache/versão do vínculo | Negar sem usar resposta obsoleta/cross-tenant | CORE04/06/SEG |

## 4. Próxima ação útil — deixar as lacunas técnicas verificáveis

Consolidar **uma única pauta** para autenticação humana, seleção comprovada de membership ativa, política futura `read_self`, isolamento de banco e estados comerciais, separando (a) provas que já existem no laboratório F1, (b) lacunas de contrato, e (c) evidência/revisão necessária para futura autorização restrita de código. **Não** avançar por simples contagem de documentos ou aprovações.

## 5. Barreiras preservadas

- `DenyByDefaultAuthorizer` continua negando efeitos de negócio; não criar `ALLOW`, grants, rota funcional, middleware de acesso positivo, autenticação pública, SQL, testes funcionais, merge ou deploy.
- PR #23 permanece DRAFT na branch filha DEV; PRs #2/#21 também DRAFT, `main`, DEV base e VPS inalteradas por esta decisão.
- **C01..C08, 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates continuam ABERTOS.**
- A aprovação do conteúdo de orientação não configura aceitação final, homologação de segurança ou resolução da revisão independente SEG/DBA.