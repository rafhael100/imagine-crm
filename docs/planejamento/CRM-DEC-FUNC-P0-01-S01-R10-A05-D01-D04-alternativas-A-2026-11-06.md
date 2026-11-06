# CRM-DEC — FUNC-P0-01/S01-R10-A05 — Ratificação documental das alternativas A de D01–D04

**Data da manifestação:** 06/11/2026 (America/Sao_Paulo). **Natureza:** ratificação **somente documental preliminar**. Não é homologação técnica, ato de implantação, autorização de execução ou parecer independente.

**Pauta imediatamente submetida:** “Você aprova as alternativas A de A05-D01 a A05-D04 exclusivamente como diretrizes documentais preliminares, mantendo todos os gates abertos?”
**Resposta expressa do titular G01:** **“aprovado”**.

**Fonte da deliberação:** [R10-A05 — matriz de evidências técnicas dos contratos C01–C04](CRM-FUNC-P0-01-S01-R10-A05-matriz-evidencias-tecnicas-contratos-C01-C04-proposta-v0.1-2026-11-06.md), blob `05e26146a18ad45031c7467d26a0f3e7627f2646`.

## 1. Alternativas A expressamente ratificadas

| ID | Diretriz selecionada | Situação do contrato |
|---|---|---|
| **A05-D01 — A** | Adotar a matriz R10-A05 como checklist documental de evidências C01–C04; manter contratos PENDENTES | **NENHUM contrato homologado** |
| **A05-D02 — A** | Exigir rastreabilidade de fonte, versão, owner e autorização por condição, sem presumir grants | **NENHUM contrato homologado** |
| **A05-D03 — A** | Preservar necessidade de revisões competentes e independentes de segurança/privacidade/QA quando aplicáveis; não aceitar autovalidação | **NENHUM contrato homologado** |
| **A05-D04 — A** | Prosseguir com documentação, sem tratar ratificação como GO de programação, testes, SQL ou mudança operacional | **NENHUM contrato homologado** |

Alternativas B não aprovadas. As expressões “AINDA NÃO APROVADAS” na minuta referida preservam seu estado histórico **anterior a esta decisão**, e não prevalecem para D01–D04-A ratificadas neste registro.

## 2. O que é e o que não é considerado resolvido

- **Resolvido exclusivamente em governança documental:** adotar checklist E01–E07 para C01–C04, exigir fonte/versão/owner e reconhecer a ausência de prova real. A designação interna R08-G01..G06 ao mesmo titular continua registrada.
- **Não resolvido:** fonte física e política por campo, IAM/CORE, matriz de permissões, MFA, PostgreSQL RLS não contornável, contratos C05..C08, revisões especializadas/independentes, execução de S01-T01..T22, evidência de ambiente real, aprovação de deploy.
- A assunção acumulada de G01–G06 não cria revisão independente de segurança, QA, LGPD nem parecer jurídico especializado quando exigível.

## 3. Estado de todos os contratos e gates

**R02-C01, C02, C03, C04, C05, C06, C07 e C08 = PENDENTES. Os 67 CORE técnicos, SEG-01/02, AUT, ARQ/AGT, FIN/CON, INT P0/G01..G06, LGPD, CORE10/DEV-GATE e demais gates continuam ABERTOS.** Não confundir funções R08-G01..G06 com gates INT-G01..G06.

**Proibição mantida:** não mudar `main`, PRs #2/#21, branch `dev/fast-track-crm-2026-11-04`, VPS/produção, CI, código, SQL, schema, migrations, `ALLOW`/`GRANT`, testes funcionais, configurações de dados reais ou cobrança.

## 4. Próximo encaminhamento documental, ainda não aprovado

Preparar [R10-A06 — sequenciamento e dependências reais dos contratos/gates](CRM-FUNC-P0-01-S01-R10-A06-sequenciamento-contratos-e-gates-proposta-v0.1-2026-11-06.md) para distinguir **decisão empresarial possível agora, prova técnica futura, revisão independente necessária e GO específico posterior**. Sua publicação é documental, não ratificação automática.