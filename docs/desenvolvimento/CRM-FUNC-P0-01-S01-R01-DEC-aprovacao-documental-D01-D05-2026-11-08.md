# CRM Imagine — FUNC-P0-01 / S01-R01 — Ratificação documental R01-D01..D05

**Data:** 08/11/2026 (America/Sao_Paulo).  
**Decisão expressa da Direção:** “aprovado todos”, imediatamente após a apresentação das cinco alternativas A de **R01-D01 a R01-D05** para aprovação **exclusivamente documental**.  
**Status:** **APROVADAS COMO DIRETRIZES DOCUMENTAIS** — não significa homologação técnica, `ALLOW`, início de programação, execução de testes funcionais, merge, deploy ou uso real.  
**Rastreio:** [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22); [proposta S01-R01](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md).

## 1. Decisões expressamente ratificadas

| Decisão | Alternativa A ratificada documentalmente | Limites |
| --- | --- | --- |
| **R01-D01 — Visibilidade** | V0 futura mostra apenas **CAM-01** (nome de apresentação da própria organização) e **CAM-02** (rótulo “Seu vínculo: Ativo”). **CAM-03..10** não aparecem. | Campos internos eventualmente necessários exigem justificativa; classificação, fonte, autorização e payload ainda não homologados. |
| **R01-D02 — Membership** | Somente vínculo **próprio, ativo e comprovado** pode ser considerado elegível; vínculos pendentes, suspensos, revogados, expirados ou inexistentes devem negar esta autoconsulta. | Elegibilidade não é autorização; identidade, sessão, tenant, regras de estado e política de concessões continuam pendentes. |
| **R01-D03 — Estados** | Separar sessão/identidade, membership, organização e contrato/plano; **não** decidir agora acesso final de estados não homologados. | EST-05..12, trial, cortesia, pago, inadimplência, suspensão, cancelamento, retenção e portabilidade exigem decisões específicas CORE08/FIN/CON/LGPD. |
| **R01-D04 — Proteções** | Priorizar sessão humana verificada, tenant server-side, MFA proporcional ao risco, RLS sem bypass, revalidação, cache restrito e **fail-closed**; detalhar provas antes de código. | Sem aprovar TTL/MFA específico, políticas SQL/role, algoritmo de grants, cache HTTP, endpoints ou testes de homologação. |
| **R01-D05 — Evidência** | Preservar **S01-T01..T22** como matriz de aceitação **futura** com owner, ambiente, SHA, risco e resultado verificável por caso. | Não equivale a autorização para executar os testes nem reconhecimento de que passaram. |

## 2. Efeito e limites da ratificação

- Mantido **recorte A**: autoconsulta read-only da organização corrente comprovada no servidor e do próprio vínculo humano legitimamente autenticado, somente em futura área interna do CRM. Sem seletor multiempresa, outros usuários, leads, contatos, planos, exportação ou operações comerciais.
- **`DenyByDefaultAuthorizer` permanece único e deny-only.** As cinco decisões não constituem `ALLOW`, autorização de código funcional, migrações, SQL, roles, grants, API, login público, workers, integração, alteração de dados reais, autorização financeira ou deploy.
- **CORE02-D05 alternativa B (P1+P2 contextual) e GOV01 M2** seguem somente como base documental ratificada na trilha do PR #21; **GOV02 não aprovado**. Não há exceção para suporte, IA, n8n, administrador global, nem escalada entre empresas.
- **Continuam abertos:** 67 IDs CORE, CORE01/02/04/06/07/08/09/10, SEG-01/02 e Issue #27 SEG/DBA, AUT-P01..14, ARQ/INT/FIN/CON/LGPD, DEV-GATE e G01..G06. Não há owners técnicos/SEG/DBA formalmente designados por esta ratificação, nem homologação de RPO/RTO.
- **Preservações:** `main`, branch `dev/fast-track-crm-2026-11-04`, PRs #2/#21/#23..#30, VPS, produção, clientes, segredos e dados reais **inalterados por esta decisão**. Sem merge automático, contratação ou gastos.
- **Protótipo visual navegável:** sua preparação é uma possibilidade a especificar em escopo separado e com dados inteiramente fictícios; não foi autorizada automaticamente nenhuma programação de tela/UI.

## 3. Próxima documentação proposta, sem GO para programação

**S01-R02**: detalhar contratos de exibição e estados seguros, origem/classificação dos campos CAM-01/02, critérios de erro/caching, matriz de owners e dependências, rastreabilidade S01-C01..C06 e S01-T01..T22 e escopo possível de protótipo sintético. Esses detalhes serão **propostas para nova avaliação**, não direitos concedidos nem decisões físicas aprovadas.

**Regra de precedência:** esta ratificação substitui exclusivamente a indicação “ainda não aprovadas” das cinco alternativas A na proposta R01. Todas as ressalvas, proibições, autorizações necessárias e gates permanecem vigentes.
