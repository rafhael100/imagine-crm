# CRM-DEC — FUNC-P0-01/S01-R10-A14 — Ratificação preliminar das alternativas A de D01 a D05

**Data:** 06/11/2026 (America/Sao_Paulo).
**Natureza:** aprovação restrita a diretrizes DOCUMENTAIS PRELIMINARES, não homologação de requisitos físicos, implementação, testes funcionais ou fechamento de gates.

## 1. Manifestação e fonte

Pergunta imediatamente submetida ao responsável: “Você aprova essas cinco alternativas A, exclusivamente para planejamento documental?”
Resposta literal: **“ok”**. Dado o objeto explícito da pergunta, fica registrada a anuência às alternativas **A14-D01-A, D02-A, D03-A, D04-A e D05-A**, sem ampliar seu alcance.

Fonte versionada: [R10-A14 — jornada mínima de autenticação/autoconsulta e critérios de GO](CRM-FUNC-P0-01-S01-R10-A14-jornada-minima-autenticacao-autoconsulta-criterios-go-proposta-v0.1-2026-11-06.md), blob `95f0b50c3ccb53ba5757c4fde2a44d3eccb62bba`. O estado 'NÃO APROVADAS' na minuta descreve seu estado histórico anterior a esta ratificação.

## 2. Escopo exato aprovado

| Decisão | Alternativa A ratificada documentalmente | Limite vigente |
|---|---|---|
| **A14-D01-A** | A primeira superfície interna CRM deverá exibir **somente** o nome de apresentação da própria organização corrente e o rótulo do próprio vínculo ativo, após autorização legítima, sem seletor ou outros dados. | CAM-01/CAM-02 propostos não viram modelo físico/DTO/tela aprovada. |
| **A14-D02-A** | Prever leitura `read_self` estritamente escopada e negação enquanto sessão, membership, campo, RLS e estado não forem homologados. | Não existe autorização para `ALLOW`, grants, rota ou política positiva hoje. |
| **A14-D03-A** | Manter EST-05..EST-12 e os direitos mínimos legítimos sujeitos a CORE08/FIN/CON/LGPD, sem confundir pagamento com autenticação. | Nenhuma regra de status comercial é decidida por inferência. |
| **A14-D04-A** | Conservar os 22 cenários S01-T01..S01-T22 e os oito checkpoints G-S01 como exigências para o futuro processo de validação. | Nenhum teste funcional S01 foi autorizado, executado ou homologado neste ato. |
| **A14-D05-A** | Após um GO específico da jornada mínima, priorizar, em novos pacotes, leads/contatos, oportunidades/funil, atividades e ferramentas de dados. | Ordem de produto não é permissão para programar módulos comerciais. |

Alternativas B não ratificadas. Esta decisão preserva a aprovação anterior de **S01-D01..D03 e diretrizes gerais S01-D04..D08 como bases documentais preliminares**, sem promover a proposta S01-R01 / R01-D01..D05 — que segue **NÃO APROVADA** — a contrato físico ou operacional.

## 3. Pendências técnicas para liberação da primeira jornada (próximo trabalho documental)

| Ordem | Dependência essencial | Evidência/decisão a produzir | Estado |
|---|---|---|---|
| 1 | **Campos e estados S01-R01 / R01-D01..D05** | Decidir visibilidade e fonte CAM-01/02, negações de CAM-03..10 e distinção das quatro dimensões de estado; classificar dados. | ABERTO |
| 2 | **IAM/CORE04/CORE07** | Sessão humana/MFA por risco, membership ativa, revalidação, revogação e contexto confiável. | ABERTO |
| 3 | **CORE02/SEG — `read_self`** | Contrato de recurso/ação/campo/escopo, grants/denies e precedência; comprovar política específica antes de qualquer `ALLOW`. | ABERTO |
| 4 | **CORE01/SEG/DBA — RLS** | Contexto transacional, roles sem bypass, integridade tenant-bound, pool, cache e evidência técnica especializada. | ABERTO |
| 5 | **CORE08/FIN/CON/LGPD** | Tratamento de trial, cortesia, suspensão, cancelamento e direitos de acesso mínimos sem dedução comercial. | ABERTO |
| 6 | **CORE10/QA/DEV-GATE e GO expresso** | Matriz S01-T01..T22, revisores competentes, ambiente sintético, branch e limites específicos, autorização própria para eventual implementação. | ABERTO |

## 4. Preservação e estado

- **10%** continua somente a referência gerencial estimada do progresso do código do CRM completo, faixa orientativa 5–15%. Ratificações documentais não elevam o percentual.
- A DEV `dev/fast-track-crm-2026-11-04`, a `main`, a VPS e PRs #2/#21/#23 não foram alterados. PRs seguem DRAFT e não integrados.
- PR #23 é proteção de migrations em laboratório e está desvinculado do GO funcional S01; CI verde não aprova `ALLOW` nem leitura de negócio.
- **C01..C08 PENDENTES; 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS**. Funções internas G01..G06 não substituem revisão independente quando exigida.
- Esta etapa acrescenta somente um arquivo Markdown na branch documental. **NÃO** há autorização de código Laravel, migrations, SQL, testes S01, grants, alterações de CI, merge, integração à `main`, operação de clientes ou implantação.

**Encaminhamento recomendado:** voltar às decisões ainda não ratificadas **S01-R01-D01..D05** e ao contrato seguro de identidade/tenant, evitando nova rodada de aprovações genéricas. Uma autorização futura de programação precisará ser delimitada e explícita.