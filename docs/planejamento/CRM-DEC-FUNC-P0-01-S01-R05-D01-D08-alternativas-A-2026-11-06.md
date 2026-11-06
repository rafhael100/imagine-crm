# CRM-DEC — FUNC-P0-01/S01-R05 — Ratificação das alternativas A (R05-D01 a R05-D08)

**Data da manifestação:** 06/11/2026.  
**Evidência:** resposta expressa **“aprovado”** à recomendação de aprovar exclusivamente como diretrizes documentais preliminares as alternativas **A de R05-D01 a R05-D08**.  
**Pauta original:** [FUNC-P0-01 / S01-R05 — Matriz de evidências e critérios de homologação de segurança v0.1](CRM-FUNC-P0-01-S01-R05-matriz-evidencias-criterios-homologacao-seguranca-proposta-v0.1.md), seção 5.  
**Alcance:** somente **ratificação documental preliminar**, sem aprovação integral do documento, homologação de contrato, implementação, teste ou fechamento de gate. Registro restrito à branch documental isolada `docs/func-p0-01-s01-r01-r02-2026-11-06`.

## 1. Ratificação individual das alternativas A

| ID | Alternativa | Diretriz ratificada |
|---|---|---|
| **R05-D01** | **A** | Exigir ficha de evidência versionada por contrato, com owner, risco, fonte e decisão. |
| **R05-D02** | **A** | Prever cenário positivo condicionado e casos negativos específicos por R02-C01..C08. |
| **R05-D03** | **A** | Separar parecer documental, prova técnica efetivamente executada e homologação, com status inequívoco. |
| **R05-D04** | **A** | Exigir homologação por owner e SEG/LGPD/FIN/CON competentes, conforme o domínio, sem delegação implícita. |
| **R05-D05** | **A** | Versionar e preservar integridade de origem, contexto tenant, política e futuras evidências. |
| **R05-D06** | **A** | Rejeitar autoconcessão por estado comercial, cache ou webhook; conflitos passam pela conciliação oficial. |
| **R05-D07** | **A** | Exigir critérios específicos de RLS, revogação, não enumeração, auditoria mínima e privacidade. |
| **R05-D08** | **A** | Preservar R02-C01..C08, os 67 CORE, SEG, AUT e demais gates **ABERTOS** até fechamento individual próprio. |

**Alternativas B não ratificadas.** A aprovação destes oito itens não representa decisão de executar os cenários, concluir controles ou fechar contratos.

## 2. Estado de segurança e demais limitações obrigatórias

- **Todos os contratos R02-C01..C08 permanecem PENDENTES**, sem homologação de requisitos físicos, owners nomeados, evidências finais, critérios executáveis ou aprovações de suas autoridades.
- **67 IDs técnicos CORE permanecem ABERTOS**, assim como CORE01/02/03/04/08, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, DEV-GATE global e demais gates. O número 67 não é reduzido por esta ratificação.
- **FUNC-P0-01/S01** continua como proposta para **autoconsulta humana, somente leitura, da própria organização e do próprio vínculo**. Os únicos campos candidatos à projeção são nome exibível da própria organização e estado exibível do próprio vínculo após autorização; detalhes comerciais são apenas contexto interno e não campos da tela.
- Proibidos novos **ALLOW/GRANT, SQL/RLS físico, migrations, código, testes funcionais, CI, API, alterações comerciais, acesso a dados reais, cobrança, credenciais, integração real, deploy** e qualquer intervenção na VPS/produção.
- Preservar `main`, PR **#2**, PR **#21**, branch `dev/fast-track-crm-2026-11-04`, sem edição ou merge. A presente decisão apenas adiciona Markdown à branch documental isolada.
- A pauta literal original **R01-D01..D05** ainda exige cotejo canônico. Conflitos de versões CORE/SEG/AUT/FIN/CON/INT/Portal/LGPD seguem abertos.

## 3. Encaminhamento (NÃO aprovado automaticamente)

Elaborar e apresentar a [proposta R06 — responsáveis, precedência e pendências de homologação](CRM-FUNC-P0-01-S01-R06-responsaveis-precedencia-pendencias-homologacao-proposta-v0.1.md) exclusivamente para avaliação documental, **sem conceder efeito automático às opções R06**.

**R06-D01..D08, quaisquer owners candidatos e todos os contratos C01..C08 continuam PENDENTES até deliberação específica.**