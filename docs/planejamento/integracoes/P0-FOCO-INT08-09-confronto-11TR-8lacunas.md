# P0-DOC-TR — Conferência focal INT-08 e INT-09 (oito lacunas candidatas)

**Data:** 2026-11-03. **Situação:** revisão focal DOCUMENTAL e rastreabilidade das fontes secundárias. **Não** equivale a ter obtido propostas originais integrais, nem à aprovação de novos requisitos.
**Origem da etapa:** continuidade da revisão P0-DOC-01/P0-TR-01 solicitada pelo responsável com “ok”. Preservar decisões funcionais já aprovadas.
**Escopo:** P0-GAP-015 a P0-GAP-022 (4 da INT-08; 4 da INT-09), referentes aos **5 TR iniciais de INT-08 e aos 6 TR iniciais de INT-09**.
**Restrição:** exclusivamente documentação GitHub; não criar automações, não testar, não programar, não alterar ambientes nem contratar ferramentas.

## 1. Fontes e nível de comprovação

| Camada | Evidência consultada | Nível de prova |
|---|---|---|
| Aprovação de INT-08 e INT-09 | Histórico contextual contém referência às aprovações completas; os documentos [INT-08](INT-08.md) e [INT-09](INT-09.md) registram situação funcional aprovada | **Existência/escopo geral reconhecidos; texto completo das mensagens e propostas não foi recuperado para cotejo literal** |
| Escopo consolidado | INT-08.md e INT-09.md (complementos), [P0-REV-INT05-08](P0-REV-INT05-08-conferencia-cruzada.md), [P0-REV-INT09-12](P0-REV-INT09-12-conferencia-cruzada.md) | Sínteses e revisões cruzadas **secundárias**, não fonte literal |
| Requisitos | [TR iniciais](INT-P0-rastreabilidade-requisitos.md), [P0-TR53](P0-TR53-vinculo-contratos-aceite.md), [P0-DOC-TR](P0-DOC-TR-deltas-candidatos-fonte-original.md) | 11 IDs conferidos, com granularidade por revisar; não inventar novos TR |
| Decisões transversais do CRM/Sites | [IN-118](../IN-118-central-backup-portabilidade-dados.md), [IN-122](../IN-122-multiplos-sites-contas-crm-cortesias.md), [IN-123](../IN-123-painel-integracoes-sites-crm.md), [IN-124](../IN-124-integracao-inteligente-formularios-leads.md), [IN-148](../IN-148-reconciliacao-inteligente-leads.md), [IN-149](../IN-149-protocolo-entrega-confirmacao-leads.md) | Textos de **outras decisões funcionais aprovadas**, sem se passar por redação original da INT-08/09 |
| Arquitetura posterior | [CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md), [CT-03/04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md), [P0-VER](P0-VER-matriz-compatibilidade-evolucao-versoes.md), [planos HT](P0-ACC-planos-homologacao-HT01-HT18.md) | Bases documentais aceitas, parâmetros/contratos específicos não fechados |

**Ressalva de fidelidade:** é possível localizar indícios de mensagens históricas e descrição/resumo de propostas originais, mas não sua íntegra imutável. Nenhuma fala aqui é citada como transcrição literal recuperada. Não construir artificialmente um arquivo chamado “proposta original completa”.

## 2. Matriz de confronto

| Lacuna candidata | Módulo | TR iniciais | Questão de granularidade | Estado da evidência |
|---|---|---|---|---|
| P0-GAP-015 | INT-08 | TR-INT08-001/002/003 | Ciclo operacional importação/exportação/migração | Representação resumida verificada; proposta/aceite integrais pendentes |
| P0-GAP-016 | INT-08 | TR-INT08-001/003/004 | Autorização por fonte, destino, arquivo, lote, registro e finalidade | Princípio e fronteiras corroborados; perfis/alçadas ainda pendentes |
| P0-GAP-017 | INT-08 | TR-INT08-001/004/005 | Separação da Central IN-118 e vinculação Sites→CRM | Decisão correlata explícita em IN; mapeamento INT contratual pendente |
| P0-GAP-018 | INT-08 | TR-INT08-002 | Formatos, arquivo, prévia, validação e compatibilidade | Regras básicas explícitas; matriz de formatos por operação pendente |
| P0-GAP-019 | INT-09 | TR-INT09-001/003/005 | Estados de modelo, instância, etapa, comando, aprovação e compensação | Separação documentada; transições específicas e origem integral pendentes |
| P0-GAP-020 | INT-09 | TR-INT09-002 | Dependências, paralelismo, loops e convergência | Escopo e princípios documentados; detalhes de execução ainda pendentes |
| P0-GAP-021 | INT-09 | TR-INT09-003/004/006 | Contingência multissistema e provedor alternativo | Diretriz existente; matriz de decisão por operação não homologada |
| P0-GAP-022 | INT-09 | TR-INT09-004/005 | Compensação reversível, parcial, irreversível e cancelamento técnico | Princípio documentado; decisão concreta por proprietário pendente |

## 3. Conferências individuais das oito lacunas

### P0-GAP-015 — Ciclo operacional importação/exportação/migração

**Requisitos já associados:** TR-INT08-001/002/003. **Fontes secundárias/correlatas verificadas:** INT-08.md; INT-R01/R02; P0-OP24; HT-07.

**O que se conseguiu confirmar documentalmente:** INT-08.md enumera planejar, configurar, revisar, aprovar, simular, executar, acompanhar, suspender, recuperar, reconciliar e encerrar; a aprovação histórica remete à automação completa. A matriz inicial agrega a sequência em poucos TR.

**O que não está integralmente especificado:** Falta tabela de estados e transições por tipo (operação/lote/registro/arquivo), decisão por etapa e distinção entre preparação, prévia sem efeito, processamento, aceite e encerramento.

**Regra de precedência preservada:** Manter abrangência funcional aprovada; propor desdobramento de TR somente após cotejo de cláusula original e exame dos estados.

**Status:** Representação resumida verificada; proposta/aceite integrais pendentes. **Próximo cotejo:** recuperar a proposição e o aceite de origem, verificar redação, escopo, exceções, competência e versão; só então concluir se cabe desdobramento de requisito, anotação de evidência ou decisão expressa nova.

### P0-GAP-016 — Autorização por fonte, destino, arquivo, lote, registro e finalidade

**Requisitos já associados:** TR-INT08-001/003/004. **Fontes secundárias/correlatas verificadas:** INT-08.md; P0-SEC; IN-118; HT-07/13/17.

**O que se conseguiu confirmar documentalmente:** INT-08.md e P0-SEC/CT-02 contemplam autorização contextual, isolamento e efeitos confirmados por proprietários; IN-118 estabelece autoatendimento nas contas autorizadas.

**O que não está integralmente especificado:** Falta matriz de ator, entidade/campo, direitos de leitura/exportação e gravação, delegação entre contas, aprovação específica e vigência por ato/risco.

**Regra de precedência preservada:** Não presumir que titularidade em uma conta autoriza migrar para qualquer outra; sem bloquear autoatendimento elegível.

**Status:** Princípio e fronteiras corroborados; perfis/alçadas ainda pendentes. **Próximo cotejo:** recuperar a proposição e o aceite de origem, verificar redação, escopo, exceções, competência e versão; só então concluir se cabe desdobramento de requisito, anotação de evidência ou decisão expressa nova.

### P0-GAP-017 — Separação da Central IN-118 e vinculação Sites→CRM

**Requisitos já associados:** TR-INT08-001/004/005. **Fontes secundárias/correlatas verificadas:** IN-118; IN-122; IN-123; IN-124; IN-149; INT-08.md.

**O que se conseguiu confirmar documentalmente:** IN-118 é decisão funcional documentada de backup completo, importação/exportação, restauração e portabilidade em todos os planos, inclusive Básico; IN-122/123 determinam troca técnica gratuita via chamado, sem migração histórica pela Imagine.

**O que não está integralmente especificado:** Falta contrato de responsabilidade entre Central IN-118, Arquivos, INT-08, proprietários de dados e serviço de vínculo; distinguir exportação simples, backup íntegro, restauração, migração, mudança de vínculo e entrega de leads pendentes.

**Regra de precedência preservada:** Preservar IN-118 como fonte aprovada da capacidade; INT-08 coordena movimentação delimitada sem absorver a Central.

**Status:** Decisão correlata explícita em IN; mapeamento INT contratual pendente. **Próximo cotejo:** recuperar a proposição e o aceite de origem, verificar redação, escopo, exceções, competência e versão; só então concluir se cabe desdobramento de requisito, anotação de evidência ou decisão expressa nova.

### P0-GAP-018 — Formatos, arquivo, prévia, validação e compatibilidade

**Requisitos já associados:** TR-INT08-002. **Fontes secundárias/correlatas verificadas:** INT-08.md; IN-118; HT-03/07/17.

**O que se conseguiu confirmar documentalmente:** INT-08.md cita CSV/XLSX/JSON conforme contrato; IN-118 garante formatos essenciais CSV/XLSX para importação/exportação; operações do trial incluem importação CSV.

**O que não está integralmente especificado:** Falta matriz específica por formato/origem/destino: importável, exportável, compatível com backup integral, limitações e evidência de validação; não inferir suporte ilimitado de JSON como portabilidade universal.

**Regra de precedência preservada:** Preservar CSV/XLSX como essenciais de IN-118 e JSON somente conforme contrato tipado homologado.

**Status:** Regras básicas explícitas; matriz de formatos por operação pendente. **Próximo cotejo:** recuperar a proposição e o aceite de origem, verificar redação, escopo, exceções, competência e versão; só então concluir se cabe desdobramento de requisito, anotação de evidência ou decisão expressa nova.

### P0-GAP-019 — Estados de modelo, instância, etapa, comando, aprovação e compensação

**Requisitos já associados:** TR-INT09-001/003/005. **Fontes secundárias/correlatas verificadas:** INT-09.md; P0-REV-INT09-12; P0-CON-CT03-CT04; HT-08/09/10.

**O que se conseguiu confirmar documentalmente:** INT-09.md conserva distinção dos estados e enumera em complemento P0 múltiplas classes e marcos; P0-CON CT-03/04 documenta separação transporte/técnico/negócio.

**O que não está integralmente especificado:** Falta máquina de estados por dimensão, transições admissíveis, estado final com ressalva, eventos tardios e confirmação competente; os seis TR da INT-09 não cobrem individualmente cada classe.

**Regra de precedência preservada:** Não confundir estado de coordenação com estado do negócio ou propor máquina central paralela.

**Status:** Separação documentada; transições específicas e origem integral pendentes. **Próximo cotejo:** recuperar a proposição e o aceite de origem, verificar redação, escopo, exceções, competência e versão; só então concluir se cabe desdobramento de requisito, anotação de evidência ou decisão expressa nova.

### P0-GAP-020 — Dependências, paralelismo, loops e convergência

**Requisitos já associados:** TR-INT09-002. **Fontes secundárias/correlatas verificadas:** INT-09.md; P0-DEP; INT-R01/R02; HT-09/16.

**O que se conseguiu confirmar documentalmente:** INT-09.md contém caminhos sequenciais, condicionais, paralelos, convergentes, por eventos/prazos, subprocessos, tarefas humanas e limites de ciclos; INT-R01/R02 exigem confirmação oficial.

**O que não está integralmente especificado:** Falta matriz por tipo de dependência, condição de entrada/saída, limitação de iterações, bloqueio de ciclos, timeout técnico vs prazo de negócio e recuperação de execução parcial.

**Regra de precedência preservada:** Etapa independente não dispensa dependência obrigatória; confirmação deve vir do proprietário competente.

**Status:** Escopo e princípios documentados; detalhes de execução ainda pendentes. **Próximo cotejo:** recuperar a proposição e o aceite de origem, verificar redação, escopo, exceções, competência e versão; só então concluir se cabe desdobramento de requisito, anotação de evidência ou decisão expressa nova.

### P0-GAP-021 — Contingência multissistema e provedor alternativo

**Requisitos já associados:** TR-INT09-003/004/006. **Fontes secundárias/correlatas verificadas:** INT-09.md; INT-11.md; P0-VER; HT-08/11/12/18.

**O que se conseguiu confirmar documentalmente:** INT-09.md prevê alternativas homologadas, compatíveis e autorizadas, sem troca crítica unilateral pela IA; P0-VER e INT-11 descrevem versionamento/homologação.

**O que não está integralmente especificado:** Faltam critérios por efeito, provedor e momento: quando consultar, quando bloquear, quando migrar instância ou manter versão; resultado de escrita inconclusiva antes de contingência.

**Regra de precedência preservada:** Provedor alternativo não autoriza retry cego ou alteração de autoridade/conta/resultado.

**Status:** Diretriz existente; matriz de decisão por operação não homologada. **Próximo cotejo:** recuperar a proposição e o aceite de origem, verificar redação, escopo, exceções, competência e versão; só então concluir se cabe desdobramento de requisito, anotação de evidência ou decisão expressa nova.

### P0-GAP-022 — Compensação reversível, parcial, irreversível e cancelamento técnico

**Requisitos já associados:** TR-INT09-004/005. **Fontes secundárias/correlatas verificadas:** INT-09.md; P0-CON-CT03-CT04; P0-OP24; HT-10/15.

**O que se conseguiu confirmar documentalmente:** INT-09.md exige elegibilidade, reversibilidade, aprovação, execução e confirmação próprias; FIN/CON/AGD e demais proprietários decidem seus atos. CT-03/04 mantém evidências de efeitos anteriores.

**O que não está integralmente especificado:** Falta contrato por efeito/ente: ato que pode ser compensado, aprovador, irreversibilidade, estado parcial, limite de reexecução, confirmação da compensação e relação com cancelamento técnico.

**Regra de precedência preservada:** Sem rollback universal, sem estorno automático pela IA; cancelamento técnico não desfaz negócio confirmado.

**Status:** Princípio documentado; decisão concreta por proprietário pendente. **Próximo cotejo:** recuperar a proposição e o aceite de origem, verificar redação, escopo, exceções, competência e versão; só então concluir se cabe desdobramento de requisito, anotação de evidência ou decisão expressa nova.


## 4. Matriz de aceites funcionais por fronteira — sem nova regra

| Fronteira | Efeito preservado | Origem verificável no GitHub | Contrato ainda por finalizar |
|---|---|---|---|
| Operação INT-08 vs Central de Portabilidade | INT-08 executa movimentação delimitada; IN-118 entrega autoatendimento de importação/exportação, backup completo, restauração e migração compatível em **todos os planos, inclusive Básico**, sem cobrança adicional por operação essencial | IN-118 e INT-08 | CT-07, responsabilidades de Arquivos/Backups, esquema de pacote e limites por formato |
| Site/CRM e troca de conta | Imagine faz gratuitamente **somente** alteração técnica de vínculo via chamado; cliente migra históricos por suas ferramentas | IN-122/123 e INT-08 | Identidade e autorização entre contas; destino de operações antigas |
| Recebimento de formulário vs entrega efetiva ao CRM | Recepção durável pela API é distinta de confirmação de persistência no CRM; mesma submissão usa identidade lógica estável, mesmo conteúdo pode representar submissão distinta | IN-124/148/149 | CT-03/04/05, filas, confirmação do proprietário e reconciliação |
| Processo INT-09 vs módulo de negócio | Modelo/instância/etapa da INT-09 não substitui decisão e confirmação do proprietário | INT-09 e INT-R01/R02 | CT-08, fontes oficiais por tipo de ação |
| INT-09 vs INT-10/INT-11/INT-12 | INT-09 coordena, INT-10 recupera, INT-11 homologa conector, INT-12 correlaciona por Auditoria Geral | INT-09..12 e P0-DEP | CT-01/08/09/11, compatibilidade de instâncias e ações seguras |
| Cancelamento/compensação vs fatos confirmados | Cancelamento técnico não revoga contrato/pagamento/reserva; compensação requer ato autorizado, executado e confirmado pelo proprietário | INT-09 e CT-03/04 | CT-10, tipos de compensação e alçada por recurso |
| Trial e importação | Importação CSV em trial permanece prevista no escopo, enquanto API pública do trial fica indisponível | INT-08 e INT-05 | Elegibilidade e perfis de importação, sem confundir API pública e ferramenta interna |

## 5. Roteiro proposto de resolução (sem gerar novos TR prematuramente)

1. Para INT-08, confrontar sucessivamente ciclo e estados, autorização por operação/lote/registro, fronteira com IN-118 e formatos. Vincular a **TR-INT08-001..005**, **CT-02/03/04/07/11**, **HT-03/07/13/17** conforme os critérios específicos; vínculos existentes não viram automaticamente definitivos.
2. Para INT-09, confrontar as classes de estados, dependências/loops/convergência, contingência e compensação. Vincular a **TR-INT09-001..006**, **CT-02/03/04/08/09/10/11**, **HT-08/09/10/11/12/15/18** conforme elegibilidade; nenhuma HT nova criada.
3. Recuperar **propostas e aceites integrais originais** com versão e referência primária, e compará-los cláusula a cláusula; os fragmentos de contexto não substituem essa evidência.
4. Só depois decidir se a lacuna é **já coberta**, **subitem a desdobrar**, **parâmetro pendente**, **contradição real a resolver** ou **fonte indisponível**. Registrar decisões sem reescrever silenciosamente o aprovado.
5. Se um texto aprovado impuser condição especial, preservá-la no domínio correto; não transformar prazos específicos de leads, cotas de anexos ou política de trial em regras globais das INTs.

## 6. Localizadores históricos e proveniência (03/11/2026)

- [P0-ORIGEM-INT08-09](P0-ORIGEM-INT08-09-localizadores-historicos.md) registra os indícios contextuais da proposta INT-08, seu aceite e a apresentação da INT-09 no dia 02/11/2026, além da cronologia comprovada dos commits de síntese no GitHub.
- Os marcadores de conversa indicam propostas extensas, mas **não são os respectivos textos completos**. O GitHub registra arquivos de consolidação, sem cópia integral verificável das propostas.
- Nenhuma das 8 lacunas candidatas foi certificada por cotejo literal; os 11 IDs TR iniciais permanecem preservados.

## 7. Resultado e status P0

- **INT-08:** 4/4 lacunas candidatas analisadas em relação às fontes secundárias e decisões IN correlatas; **5/5 IDs TR existentes preservados**, sem desdobramento prematuro.
- **INT-09:** 4/4 lacunas candidatas analisadas contra as sínteses e arquitetura posterior; **6/6 IDs TR existentes preservados**, sem desdobramento prematuro.
- **Propostas/aceites integrais de INT-08 e INT-09:** **NÃO RECUPERADOS**; portanto **nenhuma lacuna declarada resolvida por verificação literal**.
- **P0-DOC-01 e P0-TR-01 permanecem ABERTOS/PARCIAIS**. O documento é uma ferramenta de revisão de fontes, não certificado de que toda condição original foi reproduzida.
- **Nenhum teste executado, nenhuma programação iniciada, nenhuma automação nova aprovada.**
