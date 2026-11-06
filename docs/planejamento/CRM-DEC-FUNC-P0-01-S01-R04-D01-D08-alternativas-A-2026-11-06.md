# CRM-DEC — FUNC-P0-01 / S01-R04 — Ratificação preliminar das alternativas A (R04-D01 a R04-D08)

**Data:** 06/11/2026.  
**Evidência da decisão:** resposta expressa **“aprovado”** do responsável à recomendação de aprovação **das oito alternativas A de R04-D01 a R04-D08**, exclusivamente como diretrizes documentais preliminares.  
**Pauta de origem:** [FUNC-P0-01/S01-R04 — Detalhamento dos contratos de segurança por responsável e evidência, proposta v0.1](CRM-FUNC-P0-01-S01-R04-contratos-seguranca-por-owner-evidencias-proposta-v0.1.md), seção 10.  
**Escopo:** ratificação da **alternativa A** por ID; a proposta R04 original permanece versão histórica, e esta decisão identifica explicitamente a ratificação posterior.  
**Branch isolada:** `docs/func-p0-01-s01-r01-r02-2026-11-06`. Não existe autorização de PR, merge, deploy, alteração em `main`, branch DEV ou VPS por esta decisão.

## 1. Escolhas documentalmente ratificadas

| ID | Alternativa | Diretriz preliminar expressamente ratificada |
|---|---|---|
| **R04-D01** | **A** | Registrar requisitos de sessão, autenticação humana, MFA e step-up por perfil, com evidências a definir; políticas especializadas continuam abertas. |
| **R04-D02** | **A** | Exigir correspondência versionada entre principal autenticado, organização corrente e próprio vínculo (membership), sem tenant arbitrário indicado pela interface. |
| **R04-D03** | **A** | Prever autorização em aplicação **e** isolamento por RLS, revalidação/revogação e critérios negativos para homologação futura, sem escrever SQL nesta etapa. |
| **R04-D04** | **A** | Restringir a projeção S01 aos dois campos mínimos sobre a própria organização e vínculo, quando autorizados; negar enumeração de registros, estados e organizações alheios. |
| **R04-D05** | **A** | Exigir fatos comerciais oficiais, autenticados, versionados e conciliados por domínio competente; **sem** transformá-los em autorização para S01. |
| **R04-D06** | **A** | Separar direitos, memberships, entitlements e estados comerciais, deixando a matriz de precedência e políticas executáveis pendentes. |
| **R04-D07** | **A** | Divergências sob conciliação pela autoridade responsável, sem nova concessão/cobrança inferida e sem suspensão arbitrária de direito previamente comprovado. |
| **R04-D08** | **A** | Auditoria mínima e segregada, retenção conforme finalidade/categoria, classificação LGPD e homologação especializada futura. |

As alternativas B são apenas contrapontos históricos da pauta, **não aprovadas**.

## 2. Exclusões e gates preservados

- **R02-C01..R02-C08 permanecem integralmente PENDENTES**. Aprovar R04-D01..D08 não implica homologação de contratos C01..C08, suas implementações, campos, estados, proprietários definitivos ou critérios de aceite.
- Não homologar código, políticas `ALLOW`/`GRANT`, SQL, RLS executável, migrations, API, testes funcionais, CI, alterações comerciais, contratos reais, despesas ou produção.
- Preservar **67 IDs técnicos CORE**, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, DEV-GATE global e demais gates **ABERTOS** até fechamento próprio.
- S01 continua **autoconsulta humana de somente leitura** à própria organização e ao próprio vínculo, sem dados financeiros, empregados, terceiros ou outras organizações.
- `main`, **PRs #2 e #21**, branch `dev/fast-track-crm-2026-11-04`, VPS e produção não estão no escopo. O registro ocorre exclusivamente como novo Markdown nesta branch documental.

## 3. Encaminhamento

Prosseguir **apenas no detalhamento documental** de matriz de evidências de segurança, critérios de aceite, dependências e responsáveis, mediante [FUNC-P0-01 / S01-R05 — matriz de evidências e critérios de homologação, proposta v0.1](CRM-FUNC-P0-01-S01-R05-matriz-evidencias-criterios-homologacao-seguranca-proposta-v0.1.md).

**R05-D01..D08 permanecem propostas não aprovadas**; não baixar nenhum gate. Qualquer teste, código ou homologação exige autorização específica posterior.

**Rastreabilidade pendente:** recuperar e cotejar o teor original de R01-D01..D05 e conciliar CORE/SEG/AUT/FIN/CON/INT/LGPD e regras comerciais de Portal/CRM; não inferir fechamento dessas dependências das aprovações preliminares.
