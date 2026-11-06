# CRM-DEC — FUNC-P0-01/S01-R10-A04 — Ratificação das alternativas A de A04-D01..D06

**Data da manifestação:** 06/11/2026 (America/Sao_Paulo). **Estado:** DIRETRIZES DOCUMENTAIS PRELIMINARES RATIFICADAS — SEM HOMOLOGAÇÃO.

**Pergunta submetida à direção:** “Você aprova as alternativas A de A04-D01 a A04-D06 somente como diretrizes documentais preliminares, mantendo código, testes, SQL, permissões, main e VPS inalterados?”
**Manifestação expressa da direção:** **“aprovado”**.

**Fonte de decisão:** [R10-A04 — Estados, versões e revalidação por tenant (proposta v0.1)](CRM-FUNC-P0-01-S01-R10-A04-estados-versoes-revalidacao-tenant-proposta-v0.1-2026-11-06.md), blob `6cd8fe2444cdd9ef2bd8131e2f2bbe5bb5e29736`. O status “não aprovado” dessa minuta é histórico anterior à presente ratificação **somente das alternativas A**.

## 1. Deliberações específicas

| ID | Contrato relacionado | Alternativa selecionada | Resultado |
|---|---|---|---|
| **A04-D01** | C02 | **A** — Inventariar estados de membership por tenant, sem tratar convite como vínculo ativo | **Diretriz documental preliminar ratificada; nenhum contrato/gate fechado** |
| **A04-D02** | C01/C02 | **A** — Revalidar sessão, fatores, tenant e membership antes de cada nova operação protegida | **Diretriz documental preliminar ratificada; nenhum contrato/gate fechado** |
| **A04-D03** | C02/C03 | **A** — Reconstruir contexto autorizado em eventual troca A→B fora da S01, sem herdar permissões | **Diretriz documental preliminar ratificada; nenhum contrato/gate fechado** |
| **A04-D04** | C03 | **A** — Tratar revogação concorrente, cache, filas e pool como riscos de homologação futura, sem prometer instantaneidade absoluta | **Diretriz documental preliminar ratificada; nenhum contrato/gate fechado** |
| **A04-D05** | C03/C04 | **A** — Manter somente CAM01/CAM02, mensagens de erro neutras, escaping e auditoria mínima | **Diretriz documental preliminar ratificada; nenhum contrato/gate fechado** |
| **A04-D06** | C02/C03/C06 | **A** — Separar sessão, membership, estado organizacional e situação comercial sem grants implícitos | **Diretriz documental preliminar ratificada; nenhum contrato/gate fechado** |

Todas as alternativas B permanecem **NÃO aprovadas**; nenhuma interpretação extensiva cria nomes de tabelas, novas funcionalidades, permissões, chamadas API, arquitetura física, prazo de revogação ou esquemas SQL.

## 2. Proteções específicas que permanecem pendentes

- C01 e CORE04-D04..D10: forma e janela de sessão, MFA/step-up por risco/perfil, prova de principal humano, recuperação, revogação e testes futuros.
- C02 e CORE01-D04..D07/CORE07: versão, estados físicos, titularidade da origem e revalidação da membership/tenant; um mesmo CNPJ/assinatura não equivale à própria membership ativa.
- C03 e CORE02-D04..D10/CORE03/SEG: política positiva específica por ação, escopo e campo; RLS não-bypass, papéis SQL, pool, concorrência e versionamento de autorização; sem SQL/ALLOW real.
- C04 e CORE02/CORE06/LGPD: CAM01/CAM02 mínimos, fonte real, sanitação/escaping, classificação por contexto de empresa/MEI e mensagens neutras. CAM03..CAM10 fora do recorte.
- C05–C08, FIN/CON, INT, LGPD, estados EST05..12 e fluxo separado de portabilidade/regularização não homologados.
- O mesmo titular assumiu G01..G06 internamente, **sem comprovar revisão independente** de segurança, parecer jurídico/LGPD ou QA; o aceite documental não substitui evidência de execução.

## 3. Estado global e proibições

**R02-C01..C08 seguem PENDENTES. Todos os 67 IDs CORE, SEG-01/02, AUT, ARQ/AGT, FIN/CON, INT P0/G01..G06, LGPD, CORE10/DEV-GATE e demais gates seguem ABERTOS.** Não confundir funções de governança R08-Gxx com gates técnicos INT-Gxx.

**NÃO** alterar `main`, PRs #2/#21, branch `dev/fast-track-crm-2026-11-04`, código, migrations, SQL, `ALLOW`/`GRANT`, CI, testes funcionais, VPS, produção, dados reais, cobrança ou contratos comerciais.

## 4. Encaminhamento proposto (não aprovado antecipadamente)

Preparar [R10-A05 — Matriz de evidências, critérios e lacunas para eventual homologação C01–C04](CRM-FUNC-P0-01-S01-R10-A05-matriz-evidencias-tecnicas-contratos-C01-C04-proposta-v0.1-2026-11-06.md) sem programar nem iniciar testes. O futuro GO requer atos independentes e específicos, não é consequência deste registro.