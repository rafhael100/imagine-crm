# FUNC-P0-01/S01-R08 — Modelo de governança e designação de responsáveis (PROPOSTA v0.1)

**Data:** 06/11/2026. **Status:** PROPOSTA DOCUMENTAL PARA DELIBERAÇÃO. **NÃO APROVADA/NENHUMA NOMEAÇÃO EFETIVA.**
**Origem:** [EVID04 — competências candidatas](CRM-FUNC-P0-01-S01-R07-EVID04-matriz-responsaveis-owner-aprovadores-2026-11-06.md); [EVID03 — eventos por domínio](CRM-FUNC-P0-01-S01-R07-EVID03-matriz-eventos-comerciais-fonte-autoritativa-2026-11-06.md); fichas C01..C08. Diretrizes R01–R07 só preliminarmente ratificadas, sem homologação.

## 1. Princípio de governança proporcional à Imagine

A Imagine pode operar com **equipe enxuta e acumulação de funções operacionais**, desde que as responsabilidades e eventuais conflitos de revisão fiquem explícitos. **Aprovação empresarial do produto, autoria de especificação, validação de segurança, avaliação jurídica/privacidade e autorização de execução são atos diferentes.**

**Modelo A recomendado:** direção do produto centraliza decisões empresariais e comerciais; técnico responsável redige e assume arquitetura; revisor de segurança/DB é identificado antes de homologação; privacidade/jurídico revisam o aplicável; evidências de QA são verificadas e aceitas separadamente antes de gates. Uma pessoa pode ocupar mais de um papel **se competente e expressamente designada**, mas não pode apresentar a própria análise como prova de revisão independente obrigatória. **IA/Codex auxiliam na elaboração, pesquisa, implementação futura e diagnóstico, mas NÃO são indivíduos responsáveis, signatários de parecer LGPD, atestadores independentes ou donos de obrigação legal.**

## 2. Seis funções mínimas propostas (postos, não nomes)

| Função | Alçada documental proposta | O que NÃO substitui | Designação atual |
|---|---|---|---|
| **G01 Direção de Produto Imagine** | decidir escopo S01, experiência, planos, condições comerciais e aceites empresariais, autorizar etapas | homologação independente de SEG/DBA/LGPD, testes executados ou permissão de runtime | **A CONFIRMAR** |
| **G02 Responsável Técnico CRM/IAM/Arquitetura** | elaborar contratos CORE01/02/03/04/07/08, manter modelo de tenant/identidade e matrizes de campos | parecer de segurança externo à própria autoria quando necessário | **A CONFIRMAR** |
| **G03 Responsável Portal/FIN/CON e Integrações** | comprovar fatos de contratos Site e CRM por respectivas fontes oficiais, elaborar reconciliação INT | capacidade de conceder membership/permissão sem CORE02 e SEG | **A CONFIRMAR**; pode exigir dois subdonos por produto |
| **G04 Revisor de Segurança e Banco (SEG/DBA)** | avaliar arquitetura Laravel/RLS, MFA, ameaça, revogação, falhas, segregação e evidência técnica; emitir parecer específico | autorização comercial ou decisão de finalidade legal | **A CONFIRMAR** |
| **G05 Responsável Privacidade/LGPD e apoio Jurídico** | validar finalidade, classificação, base legal, acesso, prazos, descarte e cláusulas de bloqueio/portabilidade | prova de segurança técnica ou gestão financeira | **A CONFIRMAR** |
| **G06 Revisor de QA/Conformidade e Gates** | revisar roteiro, integridade da evidência e critérios de aceitação; informar NO-GO/GO ao decisor quando autorizado | execução de testes sem autorização, aprovação empresarial ou parecer jurídico | **A CONFIRMAR** |

**Separação de responsabilidades:** G01 pode acumular liderança funcional de G03; G02 pode preparar documentação dos C01–C07 e automatizar a verificação futura quando autorizado; G04/G05 precisam de parecer identificado com escopo próprio quando forem revisões obrigatórias, inclusive em equipe enxuta. G06 pode ser função acumulada se não comprometer a qualidade da revisão ou os critérios de independência exigidos.

## 3. Alocação proposta de competências por contrato

| Contrato | Autor técnico/produto candidato | Aceite de produto/fato candidato | Revisão/parecer obrigatório proposto | Gate permanece |
|---|---|---|---|---|
| C01 Sessões/MFA | G02 | G01 (impacto de produto) | G04; G05 quando houver dados pessoais | **PENDENTE** |
| C02 Principal/tenant/membership | G02 | G01 para escopo | G04 + G05 para exposição de dado | **PENDENTE** |
| C03 RLS/revogação | G02 + G04 (definição por camada) | G01 apenas para riscos de produto | G04 com avaliação técnica distinguível do trabalho autoral; G06 evidências futuras | **PENDENTE** |
| C04 Campos e minimização | G01/G02 | G01 como dono de produto | G04 + G05, G06 para critérios | **PENDENTE** |
| C05 Fatos comerciais | G03 com subfontes Portal e FIN/CON CRM | G01 comercial, com ato próprio dos responsáveis de cada produto | G04/INT + G05 para dados/contratos | **PENDENTE** |
| C06 Direitos vs entitlements | G02/G03 | G01 sobre produto; G02 sobre política técnica proposta | G04 + G05 quando necessário | **PENDENTE** |
| C07 Conciliação/concorrência | G02/G03 | responsáveis do domínio de cada fato; G01 nos impactos comerciais | G04 + G06; INT | **PENDENTE** |
| C08 Auditoria/LGPD | G02/G05 | G01 define necessidades de produto, mas não valida adequação legal | G05 + G04 + G06 para evidência | **PENDENTE** |

## 4. Regras de designação nominal (sujeitas a aprovação)

- Designação expressa deve registrar **nome do responsável ou representante externo**, função, organização/equipe, competência, escopo por contrato, data e aceite. Pessoa identificada em GitHub como autor de commit não equivale a designada.
- **Direção de Produto** poderá ser formalmente atribuída ao responsável empresarial somente por decisão expressa de aceitação desta função. **Este documento não faz essa atribuição automaticamente.**
- A IA (ChatGPT, Codex e agentes) pode preparar documentos e apontar riscos, mas não substitui profissional responsável pelo parecer de segurança, privacidade ou garantia de evidência.
- Onde a Imagine não dispuser da competência necessária, registrar `VACANTE / CONTRATAÇÃO OU REVISÃO EXTERNA PENDENTE` para a alçada, sem alegar aprovação. Não é necessário contratar antes de produzir documentação preliminar; **não homologar/implantar sem a revisão obrigatória aplicável**.
- A equipe pode ter uma pessoa com várias funções, mas parecer SEG ou LGPD precisa ser distinguível da autoria e das decisões comerciais, e sua independência deve ser avaliada por risco e exigência legal/contratual.
- Designação **não** cria conta, permissão, credencial, `ALLOW`, acesso a tenant, poder de cobrança nem autorização de Codex.

## 5. Decisões objetivas solicitadas — R08-D01..D05, todas NÃO APROVADAS

| ID | Alternativa A recomendada | Alternativa B de comparação |
|---|---|---|
| **R08-D01** | A: adotar as seis funções G01..G06 com papéis, não necessariamente seis contratações | B: exigir uma pessoa contratada exclusiva para cada função |
| **R08-D02** | A: concentrar a direção comercial/de produto em G01, separando-a de pareceres técnicos e jurídicos | B: tratar aprovação comercial como homologação geral |
| **R08-D03** | A: permitir acúmulo operacional qualificado, mas exigir análise específica de independência das revisões SEG/LGPD/QA | B: homologar revisão pelo autor sem avaliar conflito |
| **R08-D04** | A: tratar ChatGPT/Codex como executores/assistentes sob direção humana, nunca signatários técnicos ou jurídicos | B: usar saída automática de IA como assinatura e evidência independente |
| **R08-D05** | A: atribuir os G01..G06 a nomes concretos em ato separado, mantendo vacâncias até confirmação e C01..C08 PENDENTES | B: considerar os papéis sugeridos já preenchidos |

**Deliberação desta pauta:** aprovar ou revisar apenas as **diretrizes R08-D01..D05**. Para nomeações concretas posteriores, apresentar quem exercerá cada função (podem existir acúmulos explícitos); sem essa informação, todos permanecem A CONFIRMAR.

## 6. Próximo formulário de designações (NÃO preenchido)

| Código | Nome/representante responsável | Atuação/interna/externa | Aceitou responsabilidade? | Contratos/limites |
|---|---|---|---|---|
| G01 | **A CONFIRMAR** | **A CONFIRMAR** | **NÃO VERIFICADO** | Produto/escopo/comercial |
| G02 | **A CONFIRMAR** | **A CONFIRMAR** | **NÃO VERIFICADO** | CORE/IAM/arquitetura |
| G03 | **A CONFIRMAR** | **A CONFIRMAR** | **NÃO VERIFICADO** | Portal + FIN/CON/INT (pode separar subdonos) |
| G04 | **A CONFIRMAR** | **A CONFIRMAR** | **NÃO VERIFICADO** | SEG/DBA |
| G05 | **A CONFIRMAR** | **A CONFIRMAR** | **NÃO VERIFICADO** | LGPD/jurídico |
| G06 | **A CONFIRMAR** | **A CONFIRMAR** | **NÃO VERIFICADO** | QA/aceite das evidências |

## 7. Invariantes técnicos e documentais

- **67 IDs CORE, SEG-01/02, AUT, FIN/CON, INT P0/G01..G06, LGPD, DEV-GATE e todos os demais gates ABERTOS**. **R02-C01..C08 PENDENTES**.
- Regras vigentes de planejamento: autoconsulta S01 somente do nome exibível da própria organização e rótulo do próprio vínculo ativo; nunca comerciais/terceiros. Estado comercial nunca prova membership.
- Não alterar `main`, PRs #2/#21, branch DEV, VPS, produção, código, SQL, ALLOW/GRANT, CI, testes funcionais ou contratos reais. Somente Markdown em branch documental isolada.
