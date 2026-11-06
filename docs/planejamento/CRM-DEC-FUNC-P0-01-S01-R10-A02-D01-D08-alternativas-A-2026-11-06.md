# CRM-DEC — FUNC-P0-01/S01-R10-A02 — Ratificação das alternativas A de A02-D01..D08

**Data da manifestação:** 06/11/2026 (America/Sao_Paulo). **Natureza:** decisão **documental preliminar**. Sem homologação técnica nem autorização de código/teste.

**Pauta literal imediatamente anterior:** “você aprova as alternativas A de A02-D01 a A02-D08 exclusivamente como diretrizes documentais preliminares, mantendo código, testes, SQL, ALLOW, main, PRs e VPS inalterados?”
**Manifestação expressa:** **“aprovado”**.

**Documento de origem versionado:** [R10-A02 — contratos conceituais C01..C04](CRM-FUNC-P0-01-S01-R10-A02-contratos-conceituais-C01-C04-proposta-v0.1-2026-11-06.md), blob `facede12447a189f10bcb05fa52862416660b8d4`. O status 'PROPOSTA NÃO APROVADA' no arquivo-base representa a redação histórica da minuta e é superado **exclusivamente quanto às oito alternativas A** por este ato.

## 1. Oito diretrizes ratificadas exclusivamente para detalhamento documental

| Decisão | Recorte | Alternativa ratificada | Limite de alcance |
|---|---|---|---|
| **A02-D01** | C01 | **A** — Sessão humana verificada pelo backend e falha segura sem inferir identidade na interface. | Apenas orientação documental; nenhum ALLOW/SQL/código ou aceite de gate |
| **A02-D02** | C01 | **A** — MFA conforme perfil/organização/risco, sem novo desafio universal a cada leitura; preservar política CORE04. | Apenas orientação documental; nenhum ALLOW/SQL/código ou aceite de gate |
| **A02-D03** | C02 | **A** — Identidade e membership por fonte confiável CRM/IAM, nunca por CNPJ/site/webhook/parâmetro arbitrário. | Apenas orientação documental; nenhum ALLOW/SQL/código ou aceite de gate |
| **A02-D04** | C02 | **A** — Revogação isolada por vínculo/tenant e revalidação; múltiplas organizações legítimas preservadas. | Apenas orientação documental; nenhum ALLOW/SQL/código ou aceite de gate |
| **A02-D05** | C03 | **A** — Autorização por recurso, ação, campo e escopo com deny-by-default; sem direito originado da UI. | Apenas orientação documental; nenhum ALLOW/SQL/código ou aceite de gate |
| **A02-D06** | C03 | **A** — Defesa em profundidade Laravel + PostgreSQL RLS não contornável, sob futuro desenho físico/DBA/SEG. | Apenas orientação documental; nenhum ALLOW/SQL/código ou aceite de gate |
| **A02-D07** | C04 | **A** — Apenas CAM01/CAM02 na saída V0, sem CAM03..CAM10; renderização segura. | Apenas orientação documental; nenhum ALLOW/SQL/código ou aceite de gate |
| **A02-D08** | C03/C04 | **A** — Negativas seguras, não enumeração, sem cache/fallback que contorne S01; portabilidade é fluxo próprio. | Apenas orientação documental; nenhum ALLOW/SQL/código ou aceite de gate |

**As alternativas B não foram aprovadas.** Não criar direitos implícitos por retórica de aprovação ou por identidade do declarante.

## 2. Pendências preservadas de maneira expressa

- **C01 (sessão/MFA):** CORE04-D04..D10, fatores, step-up, relógios por papel, sessão web/API e revisão SEG continuam abertos. Preservar MFA de administradores e referência dos 30 minutos de inatividade da administração geral, sem replicar prazo a outros perfis.
- **C02 (tenant/membership):** CORE01-D04..D07 e ciclos de vínculo/autorização permanecem abertos; identificadores no navegador e mesmo CNPJ não provam vínculo.
- **C03 (autorização/RLS):** contratos CORE02/03, papel SQL, BYPASSRLS/owner, pools/conexões, contexto por transação, cache, FK tenant-bound e revisão técnica/DBA permanecem para aprovação posterior. Nenhum SQL/ALLOW/GRANT aprovado.
- **C04 (campos):** CAM01 nome da organização corrente + CAM02 próprio vínculo ativo, enquanto CAM03..CAM10 não visíveis. Fonte oficial, classificação, UX e LGPD específicas ainda por homologar.
- Estados da organização/comércio EST05..EST12, C05..C08, INT, FIN/CON, LGPD, portabilidade por fluxo próprio e matriz S01-T01..T22 não são fechados por este ato.

## 3. Governança e proibições

O declarante assumiu documentalmente as **seis funções R08-G01..G06**, mas o acúmulo interno **não** emite parecer independente SEG/DBA/LGPD/QA. A presente aprovação não é revisão externa nem atestado de evidência.

**R02-C01..C08 seguem PENDENTES. Os 67 IDs CORE, SEG01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates permanecem ABERTOS.** Funções R08-Gxx não são gates INT-Gxx.

**Preservar** `main`, PRs #2/#21, branch `dev/fast-track-crm-2026-11-04`, código, CI, SQL, `ALLOW`, `GRANT`, RLS físico, testes funcionais, VPS, produção, contratos e pagamentos. Nenhum deploy ou merge autorizado.

## 4. Encaminhamento documental (não aprovado por antecipação)

Elaborar [R10-A03 — fontes oficiais de identidade e fronteiras de isolamento](CRM-FUNC-P0-01-S01-R10-A03-fontes-identidade-isolamento-proposta-v0.1-2026-11-06.md) para decidir contratos conceituais pendentes; **publicar minuta não a aprova**.