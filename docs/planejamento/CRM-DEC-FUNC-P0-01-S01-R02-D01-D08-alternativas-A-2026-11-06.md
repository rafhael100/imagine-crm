# CRM-DEC — FUNC-P0-01/S01-R02 — Ratificação preliminar das alternativas A (D01 a D08)

**Decisão expressa:** 06/11/2026; resposta do responsável **“aprovado”** à pergunta dirigida: “Você aprova essas oito alternativas A para continuarmos a documentação?”.  
**Origem da pauta aprovada:** [CRM-FUNC-P0-01-S01-R02 estados comerciais e contratos de segurança — proposta v0.1](CRM-FUNC-P0-01-S01-R02-estados-comerciais-contratos-seguranca-proposta-v0.1.md), seção 6.  
**Natureza:** **ratificação documental preliminar**, não homologação de implementação, mudança de status dos gates ou autorização executável.  
**Branch de registro:** `docs/func-p0-01-s01-r01-r02-2026-11-06`. Nenhum PR/merge/CI/alteração de `main`.

## 1. Escopo exato das oito escolhas

| Decisão | Alternativa ratificada | Diretriz documental aprovada, conforme pauta |
|---|---|---|
| **R02-D01** | **A** | Quatro eixos independentes: identidade/vínculo, trial/assinatura CRM, benefício Básico por site e operacionalidade do CRM; derivar estado operacional apenas de matriz oficial. |
| **R02-D02** | **A** | Independência do CRM pago perante o site; inadimplência do site não bloqueia, por si, assinatura paga do CRM. |
| **R02-D03** | **A** | Separar relógios, marcos de vigência e retenção de trial, CRM pago e benefício por site. |
| **R02-D04** | **A** | Distinguir transição normal de transição restrita por inadimplência do Básico; considerar outros sites elegíveis. |
| **R02-D05** | **A** | Estados comerciais referenciados somente por fatos oficiais, versionados e conciliados; sem autoconcessão por cache/interface/evento não confirmado. |
| **R02-D06** | **A** | Autoconsulta S01 restrita aos dois campos mínimos já propostos, sem faturamento nem dados financeiros. |
| **R02-D07** | **A** | Verificar principal, tenant, membership e revogação por operação; prever isolamento de banco como defesa em profundidade, sujeito aos gates SEG/CORE. |
| **R02-D08** | **A** | Divergência/falha permanece não verificada, sem conceder direito nem cobrar automaticamente; exigir conciliação. |

Essas alternativas são **orientações de planejamento**, não aprovação de mudanças de cobrança, direitos, acesso, payloads, tabelas, condições de retomada ou algoritmo de decisão.

## 2. Itens deliberadamente não aprovados

- **R02-C01 a R02-C08:** contratos conceituais de segurança **PENDENTES**, ainda sujeitos a detalhamento documental e homologação especializada. A escolha das alternativas A não transforma esses contratos em implementação aprovada.
- **FUNC-P0-01/S01-R01:** alternativas A de R01-D01..D05 ratificadas em [decisão separada](CRM-DEC-FUNC-P0-01-S01-R01-alternativas-A-D01-D05-2026-11-06.md); a pauta literal original de R01 carece de cotejo.
- **R03:** campos, estados, responsáveis, classificação de dados, fontes e critérios de segurança propostos na minuta seguinte permanecem **NÃO APROVADOS** até manifestação expressa.
- **FIN/CON:** valores, cobranças, quotas, prazos físicos, PSP, tributos, fatos de pagamento, precedência jurídica e regras finais não são homologados por esta ratificação.

## 3. Limites técnicos e operacionais preservados

Permanecem **ABERTOS os 67 IDs técnicos CORE**, SEG-01/02, AUT e demais gates CORE, ARQ/AGT, INT, FIN/CON, LGPD e DEV-GATE global. Não fechar gates nem conceder **ALLOW/GRANT**, ampliar permissão, habilitar endpoint, criar código, SQL, migrations, testes funcionais, CI, integração real, cobrança, dados reais ou deploy.

Preservar **`main`**, **PR #2** e **PR #21** (sem edição, rebase, merge ou revisão nesta tarefa), branch `dev/fast-track-crm-2026-11-04`, VPS e produção. O registro é **somente em documentação numa branch isolada**. Não declarar inspeção real da VPS ou execução de testes.

## 4. Próximo detalhamento apresentado, sem aprovação automática

[CRM-FUNC-P0-01-S01-R03 — matriz documental de campos, estados, responsáveis e segurança](CRM-FUNC-P0-01-S01-R03-matriz-campos-estados-responsaveis-seguranca-proposta-v0.1.md).  
R03 deverá diferenciar **projeção mínima autorizável na autoconsulta** de **atributos internos de suporte à decisão comercial**, com owner, evidência, fonte oficial, condições negativas e dependências; não misturar planos/benefício com membership.

Esta decisão não altera a classificação da proposta R02 original como **minuta histórica**, mas registra a aprovação pontual das suas oito alternativas A e sua precedência documental.