# FUNC-P0-01/S01-R09 — Quadro de designações, aceite e independência de responsáveis (PROPOSTA v0.1)

**Data:** 06/11/2026. **Estado:** PROPOSTA DE PROCEDIMENTO E CADASTRO DE FUNÇÕES, **NENHUMA NOMEAÇÃO REAL REGISTRADA**.
**Base documental ratificada:** [DEC-R08-D01..D05-A](CRM-DEC-FUNC-P0-01-S01-R08-D01-D05-alternativas-A-2026-11-06.md), apenas diretrizes documentais de governança.
**Escopo:** transformar funções `R08-G01..G06` em designações pessoais/contratuais **somente após confirmação expressa**, com aceite rastreável, competência e segregação por contrato `R02-C01..C08`.
**Atenção a IDs:** `R08-G01..G06` são funções internas de governança desta proposta; **não são** os gates técnicos `INT-G01..G06`, que permanecem ABERTOS.

## 1. Cadastro de cargos a preencher — status atual

| Função | Escopo autorizado apenas no modelo aprovado | Titular | Aceite do titular | Dependência |
|---|---|---|---|---|
| **R08-G01 Direção de Produto** | definição empresarial, escopo, produto e decisões comerciais; autorização de planejamento | **A CONFIRMAR** | **NÃO VERIFICADO** | declaração expressa do titular |
| **R08-G02 Responsável Técnico CRM/IAM** | redação e responsabilidade técnica de arquitetura, Laravel, PostgreSQL, identidade, CORE | **A CONFIRMAR** | **NÃO VERIFICADO** | identificação de profissional competente; atuação humana |
| **R08-G03 Responsável Portal/FIN/CON/INT** | provas de contratos, fatos CRM vs Site, reconciliação e responsabilidade pela integração | **A CONFIRMAR** | **NÃO VERIFICADO** | eventual divisão em donos Portal e FIN/CON CRM |
| **R08-G04 Segurança/DBA** | avaliação específica de segurança, arquitetura RLS, banco, sessão/revogação | **A CONFIRMAR** | **NÃO VERIFICADO** | pessoa competente e parecer distinguível da autoria |
| **R08-G05 LGPD/Jurídico** | finalidade, base legal, privacidade, cláusulas e retenção por categoria | **A CONFIRMAR** | **NÃO VERIFICADO** | validação especializada; independência quando cabível |
| **R08-G06 Qualidade/Conformidade** | revisão de evidências, rastreabilidade, testes futuros somente quando autorizados | **A CONFIRMAR** | **NÃO VERIFICADO** | critérios de QA e independência proporcionais ao risco |

**Nenhuma dessas funções deve receber nome por inferência do login GitHub, autoria de commit, histórico de chats, propriedade comercial do repositório ou aprovação genérica.** É aceitável acumular funções formalmente desde que os possíveis conflitos e pareceres obrigatórios sejam explicitados.

## 2. Procedimento de nomeação a propor à direção (sem designação automática)

1. A direção indica **nome/identificador funcional**, função R08-Gxx, competência, vínculo interno/externo, escopo de contratos C01..C08, prazo de atuação e substituto se necessário.
2. O profissional indicado **aceita a atribuição** e confirma limitação da função, data, responsabilidades e confidencialidade pertinente; uma indicação unilateral não é aceite do designado.
3. A direção registra a nomeação em decisão própria; valida eventuais incompatibilidades de acúmulo e exige revisor distinto quando necessário para segurança/LGPD/QA.
4. Fichas individuais C01..C08 recebem o link à nomeação/aceite; **nenhum contrato é homologado por esse registro**, e não se criam credenciais nem permissões.
5. Antes de homologação técnica, o titular competente submete análise e evidências na versão vigente, com revisões próprias de SEG/LGPD/QA onde aplicáveis.
6. Codex/ChatGPT podem **elaborar minuta, pesquisar, analisar riscos e auxiliar execução técnica futura autorizada**, mas não substituem pessoas designadas, nem assinam revisão legal ou laudo de segurança.

## 3. Proposta de primeira designação — R09-N01 (NÃO APROVADA)

**Pergunta de decisão:** o responsável empresarial que vem deliberando sobre o CRM Imagine **confirma que deseja assumir formalmente a função `R08-G01 — Direção de Produto`**, limitada a decisões empresariais e escopo de produto?

- **Alternativa A (recomendada):** a pessoa que dirige a Imagine confirma expressamente a função G01. Em registro posterior, conferir nome profissional/identificador e a aceitação expressa, sem atribuir a essa pessoa revisão de segurança, LGPD ou testes.
- **Alternativa B:** outra pessoa específica será indicada pelo responsável empresarial; solicitar nome e aceite do designado.
- **Alternativa C:** manter G01 vaga até escolha formal.

**Situação desta minuta:** opções A/B/C ainda não deliberadas. A pergunta simples `“Você confirma que assumirá a Direção de Produto (G01)?”` serve apenas para iniciar o preenchimento real.

## 4. Próximos dados de nomeação após G01

- **G02:** quem será o profissional humano responsável por arquitetura/CRM/IAM e pelas decisões técnicas da aplicação?
- **G03:** quem responderá pelos fatos comerciais do CRM (FIN/CON) e quem responderá pelos contratos de Sites no Portal? Poderão ser a mesma pessoa se designada e competente, mas as fontes continuam distintas.
- **G04:** quem revisará SEG/DBA e RLS, com parecer tecnicamente responsável?
- **G05:** quem fará a revisão LGPD e jurídica aplicável?
- **G06:** quem verificará QA/evidências futuras em condições de independência adequadas?

**Sem disponibilidade ou nome indicado:** manter `VACANTE / A DESIGNAR` e não apresentar os atos como realizados. A escolha de G01 não nomeia os demais.

## 5. Controles contra autohomologação ou concessão implícita

- Responsável por produto **não** pode, por ato comercial isolado, declarar RLS seguro, dispensar LGPD ou assinar prova não executada.
- Responsável técnico que criou documento/código não transforma sua própria aprovação em validação independente, quando exigida.
- Apontar um contato de fornecedor ou API não o nomeia owner técnico do cliente; o titular deve aceitar e demonstrar competência.
- Documentos e avisos aprovados não implantam contratos de produção, não habilitam acesso, não efetuam cobrança nem permitem IAM/tenant de outra empresa.
- **67 CORE, SEG-01/02, AUT, ARQ/AGT, FIN/CON, INT P0/G01..G06, LGPD, DEV-GATE e demais gates permanecem ABERTOS. R02-C01..C08 = PENDENTES.**
- Não modificar `main`, PR #2/#21, branch DEV, VPS, produção, CI, `ALLOW`/`GRANT`, SQL, código nem executar testes funcionais.

**Encaminhamento:** obter primeira manifestação expressa sobre a designação G01; depois completar G02..G06 individualmente sem presumir respostas, e conservar os gates até homologação distinta.