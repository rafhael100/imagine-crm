# CRM-DEC — FUNC-P0-01/S01-R07 — Ratificação preliminar R07-D01 a R07-D08 (alternativas A)

**Data da decisão:** 06/11/2026.  
**Evidência da vontade:** manifestação expressa **“aprovado”** à pergunta para ratificar as alternativas A de **R07-D01 a R07-D08** exclusivamente como diretrizes documentais preliminares.  
**Fonte da pauta:** [FUNC-P0-01/S01-R07 — Divergências, autoridades e prontidão documental para homologação v0.1](CRM-FUNC-P0-01-S01-R07-divergencias-prontidao-homologacao-proposta-v0.1.md), seção 5.  
**Alcance:** ratificação **documental preliminar, exclusivamente das alternativas A**; não é homologação técnica, parecer de segurança ou autorização de programação.

## 1. Escolhas individualmente ratificadas

| ID | Alternativa | Diretriz preliminar |
|---|---|---|
| **R07-D01** | **A** | Registrar GAP-01..GAP-10 como lacunas **abertas**, sem presumir texto, origem ou precedência ainda não recuperados; exigir fontes canônicas. |
| **R07-D02** | **A** | Separar o escopo mínimo S01 (autoconsulta) de fatos e decisões comerciais internos; sem novos campos financeiros na interface. |
| **R07-D03** | **A** | Exigir confirmação formal de owner e autoridade de cada contrato R02-C01..C08; candidatos não se tornam responsáveis nomeados. |
| **R07-D04** | **A** | Exigir evidências documentais para IAM, tenant, RLS, campos e negação de C01..C04 antes de prontidão documental; sem concluir contratos. |
| **R07-D05** | **A** | Exigir evidências de FIN/CON, Portal e INT, direitos e conciliação para C05..C07; sem inferir direito por callback/cache. |
| **R07-D06** | **A** | Exigir catálogo e análise específica LGPD/SEG para auditoria, finalidade, acesso, retenção e descarte do C08. |
| **R07-D07** | **A** | Separar estados/documentos de prontidão de homologação com provas, sem testes nesta etapa. |
| **R07-D08** | **A** | Preservar todos os contratos e gates como abertos até fechamento formal, individual e independente. |

Alternativas B não foram ratificadas. O texto anterior da proposta R07 conserva o caráter histórico de minuta não aprovada; **esta decisão posterior ratifica apenas as oito opções A**.

## 2. Invariantes de controle

- **R02-C01..C08 continuam todos PENDENTES**; sem homologação, owners definitivos ou evidências de execução.
- **67 IDs técnicos CORE continuam ABERTOS**, como SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, DEV-GATE e demais gates.
- S01 continua **autoconsulta humana somente leitura**, da própria organização e próprio vínculo, com apenas nome exibível da organização corrente e estado exibível do próprio vínculo, ambos condicionados à autorização válida.
- Não criar/alterar **código, API, ALLOW/GRANT, SQL, RLS físico, migrations, CI, testes funcionais, regras comerciais executáveis, VPS, dados reais, produção ou deploy**.
- Não alterar **`main`**, PRs **#2**/**#21**, branch `dev/fast-track-crm-2026-11-04` ou servidores. Registro apenas na branch documental `docs/func-p0-01-s01-r01-r02-2026-11-06`.
- **GAP-01..GAP-10 permanecem abertos**. A concordância com R07-D01..D08 não é resolução material das lacunas, aprovação de fonte nem fechamento de qualquer condição.

## 3. Encaminhamento documental — cotejo real, não mais aprovação genérica

Iniciar análise e registro de evidências com caminhos e versões canônicas efetivamente lidas do repositório, especialmente **GAP-01** (pauta literal R01), **GAP-05** (precedência comercial FIN/CON), **GAP-06** (eventos Portal/CRM) e **GAP-09** (autoridade de owners). O cotejo deve distinguir **evidência encontrada**, **divergência em aberto** e **hipótese sem lastro**. Nenhuma das fontes citadas é homologada automaticamente por ser localizada.

**Situação após esta decisão:** somente oito diretrizes preliminares ratificadas; todos os gates e pendências preservados.