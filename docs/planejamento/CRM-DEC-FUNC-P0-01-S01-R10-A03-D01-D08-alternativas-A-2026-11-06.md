# CRM-DEC — FUNC-P0-01/S01-R10-A03 — Ratificação documental das alternativas A (D01–D08)

**Data:** 06/11/2026 (America/Sao_Paulo). **Escopo:** ratificação **somente de diretrizes documentais preliminares**. Sem aprovação de código ou homologação.

**Manifestação literal da direção:** “aprovado”, imediatamente após a pergunta: “Você aprova as alternativas A de A03-D01 a A03-D08 somente como diretrizes documentais preliminares, mantendo todos os gates abertos?”

**Pauta-fonte:** [R10-A03 — Fontes de identidade, tenant e isolamento, proposta v0.1](CRM-FUNC-P0-01-S01-R10-A03-fontes-identidade-isolamento-proposta-v0.1-2026-11-06.md), blob `d06e86e3bf5e6b49f6dc379d00ddb9b734ed1d80`. As indicações “não aprovadas” na minuta-fonte têm valor histórico; o presente ato ratifica **apenas** as alternativas A listadas abaixo.

## 1. Decisões ratificadas

| ID | Contrato | Alternativa A aceita exclusivamente como orientação de planejamento |
|---|---|---|
| **A03-D01** | C01/C02 | CRM/IAM como autoridade de identidade humana e sessão, não identificador client-side |
| **A03-D02** | C02/C03 | Organização CRM como tenant lógico, tenant corrente resolvido e verificado no backend |
| **A03-D03** | C02 | Membership própria com estado/versão confiável e revogação escopada por organização |
| **A03-D04** | C04 | CAM01 de organização CRM e CAM02 do vínculo próprio ativo; sem campos extras |
| **A03-D05** | C03/C04 | Autorização cumulativa por operação, deny-by-default, sem grants por plano ou sessão |
| **A03-D06** | C03 | Defesa em profundidade Laravel + PostgreSQL RLS, ainda sem desenho físico |
| **A03-D07** | C01/C03 | Identidades técnicas e suporte com mandatos próprios, sem personificação da S01 humana |
| **A03-D08** | C01–C04/C08 | Fail-closed ante ausência/revogação/indisponibilidade, logs mínimos e portabilidade por fluxo próprio |

**Alternativas B não aprovadas.** Não fixar por inferência estrutura de DB, contrato de API, permissões, papéis, nomes técnicos nem regras comerciais adicionais.

## 2. Pendências não alteradas

- **C01:** CORE04-D04..D10, seleção de métodos de MFA, sessão, recuperação, step-up, revogação e SEG01/02 permanecem abertos.
- **C02:** CORE01-D04..D07, definição real de membership/tenant/contexto, troca de organização e evidência de revalidação continuam abertas.
- **C03:** CORE02-D04..D10, roles/RLS físicos, pool e GUC transacional, BYPASSRLS, FKs, cache, CORE03 e SEG/DBA permanecem para aceite distinto.
- **C04:** proprietário de CAM01/02, classificação, formato/escape seguro e revisão LGPD mantidos pendentes.
- **C05..C08, EST05..EST12, auditoria, direitos específicos de portabilidade, FIN/CON e INT** não foram homologados; não gerar ALLOW por autorização geral, adiantamento de plano ou relação via CNPJ.
- Todos os **67 IDs CORE**, SEG, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates **ABERTOS**. **R02-C01..C08 PENDENTES**.
- O mesmo titular dirige documentalmente G01..G06. Isto não constitui parecer técnico independente, LGPD/jurídico formal, revisão QA nem evidência de testes.

## 3. Controle de alteração

Não alterar `main`, PRs #2/#21, branch `dev/fast-track-crm-2026-11-04`, VPS, CI, código, DB, SQL, `ALLOW`/`GRANT`, testes funcionais, deploy, dados reais, contratos comerciais ou cobranças. Registro exclusivo em Markdown da branch documental isolada.

## 4. Encaminhamento documental não aprovado por antecipação

Aprovar R10-A03 só autoriza avançar em **propostas** documentais. Próxima minuta recomendada: [R10-A04 — ciclo de vida e evidência de revalidação por tenant](CRM-FUNC-P0-01-S01-R10-A04-estados-versoes-revalidacao-tenant-proposta-v0.1-2026-11-06.md), para deliberar posteriormente, **sem autorizar código**.