# CRM-DEC — FUNC-P0-01/S01-R03 — Ratificação preliminar R03-D01..R03-D08 (alternativas A)

**Data da decisão:** 06/11/2026.  
**Evidência de vontade:** resposta expressa **“aprovado”** do responsável à pergunta referente às oito alternativas A de `R03-D01..R03-D08` apresentadas na conversa; objeto exato registrado na [pauta R03 v0.1](CRM-FUNC-P0-01-S01-R03-matriz-campos-estados-responsaveis-seguranca-proposta-v0.1.md), seção 5.  
**Natureza:** **diretrizes documentais preliminares apenas**, sem homologação dos contratos de segurança, campos físicos, operações, lógica comercial ou concessão de privilégios.  
**Local:** branch documental isolada `docs/func-p0-01-s01-r01-r02-2026-11-06`. Não há autorização para abrir/editar PR, fazer merge ou operar sistemas.

## 1. Matriz das escolhas expressamente ratificadas

| ID | Alternativa | Diretriz documental aprovada |
|---|---|---|
| **R03-D01** | **A** | Projeção S01 restrita ao nome exibível da própria organização e ao estado exibível do próprio vínculo; demais campos internos. |
| **R03-D02** | **A** | Estado do próprio vínculo somente se já houver autorização válida; estados não autorizados geram negativa sem enumeração de organização ou vínculo. |
| **R03-D03** | **A** | Contexto de tenant confirmado no backend e revalidado por operação, sem aceitar tenant livremente indicado pelo cliente. |
| **R03-D04** | **A** | FIN/CON e Portal são autoridades de seus fatos comerciais oficiais versionados/conciliados; IAM mantém autoridade sobre o vínculo de identidade. |
| **R03-D05** | **A** | Estados comerciais e entitlements são independentes, sujeitos a matriz de precedência e política deny-by-default; não há acesso global implícito. |
| **R03-D06** | **A** | Divergência/falta de fato requer conciliação com owner; não inferir novas concessões/cobranças nem suspender arbitrariamente acesso anteriormente comprovado. |
| **R03-D07** | **A** | Detalhar os oito contratos R02-C01..C08 por owner e evidência; **todos continuam abertos** até homologação separada. |
| **R03-D08** | **A** | Auditoria mínima e específica por categoria/finalidade, acesso estrito, retenção dependente de LGPD e regras especializadas. |

**Não aprovado por consequência:** implementação da leitura, ALLOW/GRANT, SQL, payloads, estados executáveis, autorização de exportação, planos/cobranças, critérios temporais físicos, aprovação de R02-C01..C08 ou da futura pauta R04. A redação técnica da pauta R03 continua sendo minuta de trabalho; a aprovação aqui é **somente das oito alternativas A**, não homologação integral do documento.

## 2. Invariantes preservadas

- A operação S01 continua **autoconsulta humana somente leitura** da **própria** organização corrente e do **próprio** vínculo; vedada descoberta de organizações alheias, ampliação financeira/comercial, extração de dados pessoais de terceiros e inferência de permissão por UI.
- Nenhum estado de assinatura, site ou benefício cria membership ou autorização automaticamente. Status financeiro nunca substitui autenticação, verificação de tenant e política por ação.
- **67 IDs técnicos CORE**, SEG-01/SEG-02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, DEV-GATE global e demais gates **ABERTOS**; decisões preliminares não baixam pendências.
- **Sem código, ALLOW/GRANT, SQL/migration, teste funcional, CI, integração, dados reais, cobrança, deploy, VPS ou produção.**
- Preservar `main`, PRs **#2**/**#21** e a branch `dev/fast-track-crm-2026-11-04` **sem intervenção**. Apenas documento novo nesta branch isolada.

## 3. Encaminhamento não ratificado

Próxima [proposta R04 — detalhamento por contrato de segurança R02-C01..C08](CRM-FUNC-P0-01-S01-R04-contratos-seguranca-por-owner-evidencias-proposta-v0.1.md). Seus critérios, proprietários candidatos, testes negativos futuros e escolhas `R04-D01..D08` são **pendentes de avaliação**. Não implementar e não marcar quaisquer C01..C08 como concluídos.

**Pendências de cotejo:** texto literal da pauta original `R01-D01..D05` ainda não recuperado como arquivo canônico; precedência e versões `REV02-D01..12`, `FIN/CON`, `IN-119..123`/`COM-08/09`, `CORE`, `SEG`, `AUT`, `INT` e `LGPD` continuam a conferir documentalmente.
