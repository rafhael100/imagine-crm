# P0-DEP — Dependências críticas, ciclos de contrato e fronteiras de competência

**Data:** 2026-11-03.
**Estado:** **REVISÃO CRUZADA DOCUMENTAL / MATRIZ DE RISCOS ABERTOS**; não declara ausência total de ciclos, conflitos ou dependências não documentadas. **P0-DEP-01 ainda ABERTO**.
**Fase:** exclusivamente planejamento/versionamento; nenhuma automação nova, código, teste, implantação, contratação ou alteração de produção.
**Fontes:** [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md), [matriz de autoridades](INT-P0-autoridades-contratos.md), [P0-REC](P0-REC-conciliacao-transversal.md), [P0-TR-AUD](P0-TR-AUD-auditoria-proveniencia-requisitos.md), [P0-SEC](P0-SEC-politicas-autorizacao-CT02.md), [P0-VER](P0-VER-matriz-compatibilidade-evolucao-versoes.md), [P0-ACC](P0-ACC-planos-homologacao-HT01-HT18.md), documentos IN citados.

## 1. Critério de análise de dependência

Uma seta “A depende de B” neste módulo pode significar **contrato de dados**, **consulta de cadastro**, **elegibilidade de autorização**, **evento assíncrono**, **resultado oficial**, **observabilidade** ou **reconciliação**, e **não** necessariamente bloqueio síncrono de execução ou ordem de criação/implantação.

**Consequência:** a matriz de competências aprovada possui referências **bidirecionais** (por exemplo INT-01/02/11, INT-03/04, INT-09/10/12). Não se deve afirmar que todo o conjunto forma grafo acíclico. Para validar P0-DEP-01, será necessário separar os tipos de aresta, ordem de bootstrap/configuração, versionamento e comportamento sob indisponibilidade por operação.

**Fontes oficiais:** os objetos de negócio vivem nos módulos proprietários; autorização em Identidade e Segurança/Aprovações; catálogo em INT-11; conexão em INT-01; credenciais/escopos em INT-02; evidências na Auditoria Geral.

## 2. Matriz das doze INTs — dependências e fonte da confirmação

| INT | Dependências contratuais principais | O que a INT detém | Fronteira para impedir duplicação/autoridade indevida | Origem do resultado oficial | ID de análise |
|---|---|---|---|---|---|
| INT-01 | INT-11 catálogo; INT-02 autorizações; INT-04 capacidades técnicas | Cadastro, versões, estado e vínculo oficial de conta/conexão por tenant | Homologação não libera conexão; credencial não cria vínculo; alteração não redireciona efeito pendente | INT-01 registra conexão; INT-11 confirma homologação; proprietário confirma negócio | DEP-01 |
| INT-02 | Identidade e Segurança; IN-151 cofre central; INT-01 conexão; Central de Aprovações | Autorização externa e referência segura a escopos/credenciais por finalidade | Não duplicar IAM/cofre; token externo não dispensa RBAC ou aprovação humana quando aplicável | INT-02/Segurança atestam autorização; provedor atesta estado externo quando pertinente | DEP-02 |
| INT-03 | INT-04 contrato; INT-07 sincronização; INT-08 lote; proprietários dos dados | Esquemas, correspondências e transformação tipada versionada | Mapear não autoriza gravar, mesclar, excluir nem migrar instâncias sem compatibilidade | INT-03 atesta validação; proprietário atesta persistência | DEP-03 |
| INT-04 | INT-01/02 conexão/autorização; INT-03 schema; INT-11 conector; CT-03/04 | Adapter e comando tipado; transporte/resultado técnico verificável | HTTP/webhook/n8n não confirma negócio; retry com escrita incerta exige reconciliação | INT-04 evidencia transporte; módulo proprietário confirma efeito | DEP-04 |
| INT-05 | INT-02/04; Identidade e Segurança; política do plano/trial; proprietários | Aplicativos e fronteira de API pública por tenant/escopo | API pública no trial bloqueada; APIs nativas de formulários IN-124/150 não são via liberada do trial | INT-05 prova autorização da API; proprietário confirma ação | DEP-05 |
| INT-06 | INT-02/04, Motor Central de Eventos, Infraestrutura Central de Filas, COM | Webhook externo com autenticação, entrega técnica, deduplicação e causalidade | Motor Central de Eventos detém fatos internos; COM mensagens dirigidas; evento externo ≠ fato confirmado | INT-06 atesta fronteira externa; Eventos/proprietário seus atos | DEP-06 |
| INT-07 | INT-03/04/06 e INT-10; módulos proprietários, filas/checkpoints | Sync contínua, autoridade por entidade/campo, cursor e retomada | INT-10 diagnostica falhas mas não sobregrava dados; cursor não avança sem prova; INT-08 detém lote delimitado | INT-07 checkpoint; proprietário dados aplicados | DEP-07 |
| INT-08 | INT-02/03/04, Arquivos central, IN-118 Portabilidade, Segurança e proprietários | Operação delimitada de importação/exportação/migração com lotes e aceite | IN-118 detém Central de Portabilidade; Básico mantém autoatendimento essencial; sem backup universal ou mesclagem destrutiva por INT-08 | INT-08 coordena lote; proprietários aceitam registros; IN-118 confirma pacote/backup | DEP-08 |
| INT-09 | INT-01..08, Eventos, Tarefas, Aprovações, Datas e Prazos, Filas, COM e proprietários | Modelo/instância/etapa e dependências; coordenação e compensações condicionadas | Não se torna proprietário de FIN/CON/AGD; não confirma efeito por status do orquestrador; sem mudança silenciosa de modelo ativo | INT-09 estado de coordenação; donos de negócio fatos e compensações | DEP-09 |
| INT-10 | INT-01..09, IN-159/160 Observabilidade, Incidentes, Filas, Segurança e proprietários | Diagnóstico, saúde e recuperação técnica homologada | Não criar central paralela; provedor saudável não prova negócio concluído; IA não executa ato crítico | INT-10 saúde/recuperação; proprietário confirma efeito de negócio | DEP-10 |
| INT-11 | INT-01/02/03/04, Identidade e Segurança, Central de Aprovações, catálogo homologado | Parceiros, provedores, versões e publicação controlada de conectores | INT-11 homologa versão, não concede credencial/conexão; sem código livre/publicação autônoma ou contratos FIN/CON | INT-11 confirma homologação; INT-01/02 validam vínculo/acesso | DEP-11 |
| INT-12 | INT-01..11, Auditoria Geral, Analytics, Segurança, IN-162 | Visão especializada, correlação, investigação e exportação governada de evidências | Auditoria Geral é fonte oficial; logs técnicos não substituem fatos estruturados; não executar reconciliação de negócio | Auditoria Geral comprova proveniência; INT-12 correlaciona sem alterar fontes | DEP-12 |

**Estado da seção:** 12/12 blocos de responsabilidade **confrontados com as sínteses e a INT-R02**, sem cotejo literal integral das propostas originais. Não equivale a comprovar inexistência de conflitos no código, pois não há programação.

## 3. Dependências centrais não duplicáveis

| Capacidade central/documento | Autoridade existente | Fronteira das INTs | HT futura relacionada |
|---|---|---|---|
| Identidade e Segurança + IN-151 | Identidade, RBAC/MFA aplicável, cofre de segredos, credenciais técnicas | INT-02 representa autorizações externas, sem IAM/cofre paralelo | HT-02, HT-13, HT-15 |
| Central de Aprovações | Decisão humana, escopo, alçada, validade/reuso de aprovação | INT-09/10 coordenam/aplicam somente dentro da decisão | HT-09, HT-10, HT-15 |
| Eventos/Filas/Tarefas/Datas e Prazos | Eventos oficiais internos, transporte durável, tarefas humanas e prazos de negócio | INT-06 fronteira externa; INT-09 coordena sem criar motores próprios | HT-05, HT-09, HT-16 |
| Arquivos + IN-118 | Armazenamento/anexos e Central de Backup/Portabilidade de autoatendimento | INT-08 opera importação/exportação/migração delimitada; sem serviço concorrente | HT-07, HT-17 |
| Observabilidade + IN-159/160 | Saúde central e rastreamento técnico; Incidentes existentes | INT-10 utiliza capacidades centrais, sem central paralela | HT-11, HT-16 |
| Auditoria Geral + IN-162 | Evidência oficial, integridade, consultas e retenções específicas; logs técnicos categorizados | INT-12 audita via fonte oficial, não replica banco de auditoria | HT-14, HT-17 |
| COM e Analytics | Comunicação oficial dirigida aos clientes e indicadores oficiais | INT-06 pode lidar com webhooks técnicos, não comunicação comercial; INT-12 não vira Analytics | HT-14, HT-17 |
| Módulos FIN/CON/CAT/VEN/AGD/ATD/PVD/RET/MKT/CMS | Fonte autorizada de decisão e confirmação por efeito de negócio | INT-04/09/10/12 não substituem titularidade; compensações dependem de proprietário | HT-08, HT-09, HT-10 |
| IN-167/168 agentes técnicos | N1/N2/N3, identidades escopadas, playbooks homologados, bloqueio de privilégio da IA | INT-10 executa somente procedimento elegível; pendências de agentes não são encerradas por INT | HT-11, HT-15 |

## 4. Pontos de circularidade, sobreposição e necessidade de contrato

| ID | Arestas / competência em tensão | Risco de dependência | Interpretação de fronteira já sustentada pelo planejamento | Evidência ainda necessária | Situação |
|---|---|---|---|---|---|
| DEP-X01 | INT-01 ↔ INT-02 ↔ INT-11 | Circularidade de referência entre conexão, autorização e conector homologado | Separar **cadastro versionado** de **elegibilidade de uso**: catálogo/capacidade elegível, conexão identificada, autorização vigente. Rejeitar ativação se faltar etapa; nenhuma INT assume objeto de outra. | Contrato CT-01/02/03 por operação e temporalidade; transições de suspensão e efeito de filas pendentes | Aberto para especificação |
| DEP-X02 | INT-03 ↔ INT-04 | Operação tipada depende de schema; schema pode ser descoberto nas capacidades dos adaptadores | INT-04 descreve **capacidade homologada** e valida compatibilidade; INT-03 publica/valida **esquema e mapeamento**; versão de comando fixa referências. Sem bootstrap cíclico de execução. | Definir publicação/compatibilidade e rejeição de schema divergente | Aberto |
| DEP-X03 | INT-06 ↔ INT-09 ↔ Motor Central de Eventos | Webhook dispara processo que emite eventos/webhooks novos | Separar **recepção externa**, **evento interno oficial** e **instância/etapa**; IDs de causalidade, idempotência, limitação de loops e confirmação oficial. | Contratos CT-05/08 e regra de ciclo/replay/ACK | Aberto |
| DEP-X04 | INT-07 ↔ INT-10 | Sincronização depende de recuperação e recuperação examina checkpoints | INT-07 detém cursor/efeito de dados; INT-10 diagnostica/retoma tecnicamente dentro da competência. Não avançar checkpoint por saúde técnica. | Escopo de recuperação e estados de sync por etapa/entidade | Aberto |
| DEP-X05 | INT-09 ↔ INT-10 ↔ INT-12 | Orquestrador, recuperação e evidências se consultam recursivamente | INT-09 detém coordenação; INT-10 saúde/reconciliação técnica; INT-12 leitura investigativa pela Auditoria Geral. A execução não deve esperar interpretação de IA ou relatório de auditoria como confirmação de negócio. | Contrato de evento/incidente, referência oficial e tratamento de falha de telemetria/auditoria | Aberto |
| DEP-X06 | INT-08 ↔ IN-118 ↔ Arquivos | Portabilidade vs importação em lote vs backup central | IN-118 é a **Central de Backup/Portabilidade** de autoatendimento; INT-08 opera lote delimitado, Arquivos gerencia armazenamento; proprietário confirma dados. | Manifesto, compatibilidade, limites temporários e ownership de restauração | Aberto |
| DEP-X07 | INT-05 ↔ Sites/CRM (IN-124/150) | Restrição comercial do trial vs formulário público e API interna | API pública do CRM no trial desabilitada; recepção de formulários via serviço nativo autenticado na infraestrutura com identificador público sem segredo. | Catálogo de pontos de entrada, principal técnico, permissão e plano | Aberto |
| DEP-X08 | INT-10 ↔ IN-159/160 e Central de Incidentes | Diagnóstico especial de integrações vs observabilidade institucional | INT-10 consome a Central existente; sem segundo monitoramento/incident manager oficial; qualidade da telemetria e recuperação são coisas distintas. | Contratos de sinais/saúde, encaminhamento de incidente, recuperação autorizada | Aberto |
| DEP-X09 | INT-12 ↔ Auditoria Geral ↔ IN-162 | Conflito de retenção entre logs e evidência auditável | A Auditoria Geral é única fonte oficial; IN-162 aplica-se a logs técnicos por categoria; retenções de negócios seguem políticas específicas. | Classificação por registro, retenção aplicável, autorizações de exportação | Aberto |
| DEP-X10 | INT-02/09/10 ↔ Central de Aprovações ↔ IN-167 | Risco de aprovações humanas duplicadas ou autonomia indevida de IA | Uma aprovação para **mesma decisão** quando exigida; não dispensar autorizações distintas por proprietário. IA não é autorizador; N2 só playbook seguro e homologado. | Matriz por operação: ato, alçada, âmbito, validade/reuso e risco | Aberto |
| DEP-X11 | INT-06/07/09 ↔ Filas ↔ APIs de leads IN-148/149 | Entregue tecnicamente vs no CRM; fila normal/erros e estados desconhecidos | Distinguir persistência durável na recepção de persistência final no CRM, mantendo ID lógico; retenções de fila de leads (3+15 dias e limpeza própria) não viram regra global das INTs. | Contrato de estados, outbox, reconciliação e política especializada por origem | Aberto |

**Importante:** esta tabela não declara contradição efetiva já comprovada entre aprovações. São **riscos e perguntas técnicas** identificáveis pela composição das dependências, que precisam de contratos por fase e conferência das fontes primárias. Nenhum item fica resolvido apenas por registrar uma interpretação.

## 5. Percursos para validar independência entre camadas

### P1 — Habilitar uma conexão
1. INT-11 disponibiliza **versão e capacidade homologadas**; não concede por si direito de uso de empresa.
2. INT-01 registra **conexão e vínculo de conta**; não gera token por conta própria.
3. INT-02/Identidade e Segurança referenciam **autorização vigente e cofre IN-151**; Central de Aprovações decide quando necessário.
4. INT-04 pode executar **comando tipado** dentro do escopo; proprietário confirma efeito. INT-12 pode reconstruir a evidência pela Auditoria Geral.

O fluxo é uma **ordem lógica candidata de verificação de elegibilidade**, não a imposição de sequência síncrona para toda ação, nem garantia de que serviços dependam circularmente na inicialização.

### P2 — Evento externo e resultado de processo multissistema
1. INT-06 autentica ingresso externo e distingue transporte de evento interno oficial.
2. Motor Central de Eventos distribui evento com causalidade; INT-09 governa instância e dependências, não confirma venda, reserva ou pagamento por conta própria.
3. INT-04 remete comando; se o provedor ficar inconclusivo, INT-10 auxilia recuperação e consulta segura; o módulo proprietário atesta o negócio.
4. INT-12 correlaciona os atos na Auditoria Geral; nenhuma interpretação de auditoria altera automaticamente o resultado de negócio.

**Riscos pendentes:** duplicidades de webhook/replay, loops entre eventos e processos, falha de fila/ACK, idempotência de escrita, autorização na retomada e regras de compensação.

### P3 — Portabilidade e Sites Imagine
1. A **Central IN-118** mantém autoatendimento de backup, importação, exportação, restauração compatível e migração em todos os planos, inclusive Básico.
2. INT-08 gerencia **operações delimitadas por arquivo/lote** com INT-03 (mapeamentos) e proprietários (dados), sem absorver toda a Central.
3. Formulários seguem IN-124/148/149: **recepção durável na API não é entrega ao CRM**; mudança de conta vinculada não redireciona envios anteriores.
4. Filas de leads têm regras temporárias próprias; retenções gerais do CRM/Auditoria não são sobrescritas por políticas de payload transitório.

### P4 — Recuperação e auditoria
1. IN-159/160 Observabilidade e Central de Incidentes fornecem telemetria oficial técnica; INT-10 diagnostica e recupera somente no risco/escopo autorizado.
2. IN-167/168 limitam identidades e ferramentas dos agentes; IA não se autoconcede privilégios, N2 usa playbook homologado/seguro e N3 tem aprovação humana específica quando exigida.
3. INT-12 **consulta** Auditoria Geral para investigar, sem banco paralelo; IN-162 estabelece retenções de logs técnicos por categoria, não todos os fatos de negócio.

## 6. Critérios de fechamento P0-DEP-01 — ainda não comprovados

| Critério | Evidência necessária para o aceite | Estado |
|---|---|---|
| D-G01 — Fonte e propriedade | Cada objeto/ato possui um dono de cadastro, autorização, execução e confirmação claramente identificado | **Parcial: tabelas conceituais, sem todas operações** |
| D-G02 — Arestas tipadas | Separar dependências de boot, runtime síncrono, evento, reconciliação, auditoria e configuração por operação | **Pendente** |
| D-G03 — Sem deadlocks de configuração | Modelar bootstrap e independência de serviços centrais nas situações aplicáveis, com fallback seguro | **Pendente** |
| D-G04 — Sem loops de efeitos | Definir ciclos de eventos, compensações, assincronismo e idempotência com limites e critérios de parada | **Pendente** |
| D-G05 — Sem motores paralelos | Comprovar que Tarefas, Eventos, Datas, Filas, Aprovações, Observabilidade e Auditoria têm uma fonte oficial | **Parcial: fronteiras planejadas** |
| D-G06 — Interoperabilidade e versões | Compatibilidade de CT-01..11, migrações de conexões/instâncias, schemas e políticas | **Parcial: P0-VER base aprovada** |
| D-G07 — Tratamento de indisponibilidade | Política por operação quando autorizador, provedor, fila, observabilidade ou Auditoria estiver indisponível | **Pendente** |
| D-G08 — Sem concentração indevida de poder | Perfis, escopos, alçadas e separação de proprietários revisados por operação | **Parcial: P0-SEC base aprovada** |
| D-G09 — Proveniência integral | Aprovações originais confrontadas por requisito e suas exceções | **Pendente: P0-DOC-01/02** |
| D-G10 — Validação de roteiros | HT relevantes revisados documentalmente e efetivamente homologados somente na fase futura autorizada | **Parcial: 18 planos como base; 0 testes** |

## 7. Decisões de especificação ainda necessárias

- Catalogar arestas de **dependência síncrona/assíncrona** por 24 casos OP iniciais e por todas as operações adicionais recuperadas das aprovações integrais.
- Descrever ordem de elegibilidade de versões, conexão, credencial e ação sem acoplamento circular; políticas de falha segura e retomada.
- Formalizar contratos entre INT-06 e Eventos, INT-09 e eventos/filas, INT-10 e Observabilidade, INT-12 e Auditoria Geral, sem criar serviços concorrentes.
- Concluir propriedade e regra de inconsistência por recurso/campo/instância; definir alçada em compensações e critérios de confirmação por negócio.
- Completar CT-05..11 e as políticas de erros, estados e transições por tipo; valores numéricos ainda sem aprovação.
- Obter fontes originais literais INT-01..12, R01/R02 e fechar G01–G06 somente mediante aceites expressos verificáveis.

**Resultado:** conferência **de fronteiras e riscos** registrada; não marcar P0-DEP-01 encerrada, nem afirmar ausência de deadlocks/loops ou prontidão para programação.
