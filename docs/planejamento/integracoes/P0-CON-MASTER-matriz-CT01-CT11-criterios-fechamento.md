# P0-CON-MASTER — Matriz consolidada CT-01 a CT-11 e critérios de fechamento

**Data:** 2026-11-03.
**Situação:** **ACEITA COMO INSTRUMENTO DE ACOMPANHAMENTO DOCUMENTAL** em 2026-11-03, conforme [decisão contextual](P0-DEC-aprovacao-CON-MASTER-base.md). As **11 famílias CT-01..11 estão aceitas como BASES DOCUMENTAIS**, mas **os critérios candidatos desta matriz, direitos, parâmetros e detalhes de cada operação não foram aprovados individualmente como especificação executável**. [Primeiro lote de 15 operações](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) permanece **proposta para revisão**.
**Decisões de base:** [CT-01](P0-DEC-aprovacao-CT01-base.md), [CT-02](P0-DEC-aprovacao-CT02-base.md), [CT-03/04](P0-DEC-aprovacao-CT03-CT04.md), [CT-05..11](P0-DEC-aprovacao-CT05-CT11-base.md). Respeitam [INT-R01](INT-R01-governanca-contratos-homologacao.md) e [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
**Fase e proibições:** só planejamento/documentação. Não programar, executar testes, criar novas automações, publicar conectores, criar serviços, migrar dados, contratar, gerar custo ou alterar produção.
**Limite de proveniência:** [53 TR iniciais](P0-TR53-vinculo-contratos-aceite.md) referenciam contratos por hipótese documental, **sem cotejo integral** com as aprovações originais INT-01..12; [P0-DOC/TR](P0-TR-AUD-auditoria-proveniencia-requisitos.md) continuam abertos.

## 1. Critério de leitura de status

| Nível | Significado | Estado atual das 11 CT |
|---|---|---|
| A — Base documental | Princípios, responsabilidades e fronteiras aceitos como material de arquitetura | **11/11 aceitas como base** |
| B — Especificação para implementação | Campos, enums, contratos por operação/versão/provedor, condições de erro, autorização, proprietário e parâmetros críticos revisados/aprovados | **Não concluída** em nenhuma das CT |
| C — Homologação técnica | Teste real em ambiente autorizado após implementação, evidência e aceite de execução | **0/18 HT executados**; não autorizado nesta etapa |
| D — Fechamento global | Gates G01..G06 comprovados e versão final do planejamento aprovada | **Não concluído** |

**Nenhuma CT foi declarada pronta para implementar pelo simples aceite de uma base.** O conjunto não representa onze microsserviços, autorizadores ou motores adicionais.

## 2. Matriz consolidada de responsabilidades, regras e lacunas

| Família | Domínio | Autoridade competente | Evidência documental exigível no desenho | Lacuna concreta ainda aberta | Vínculos iniciais TR→CT* | HT de referência |
|---|---|---|---|---|---:|---|
| CT-01 | Catálogo/conector/conexão/capacidade | INT-11 homologa; INT-01 registra conexão; INT-02/Segurança autorizam; INT-04 informa operação | Conector/versão homologados, vínculo por tenant e estado/escopo elegível; pendências preservadas | Sem schema e estados por provedor; coexistência/migração e alteração crítica sem alçada | 11 | HT-01/02/12/18 |
| CT-02 | Autorização contextual | Identidade e Segurança, INT-02, Central de Aprovações; proprietário por ato | Fonte de identidade/tenant, permissão por recurso/campo, aprovação vigente quando exigida, decisão auditável | RBAC/MFA reais, direitos por campo/ato, delegações, expiração/revogação e avaliação da política | 21 | HT-02/04/13/15 |
| CT-03 | Comando tipado | INT-04 transmite, INT-09 coordena quando houver, proprietário comanda efeito | Operação, recurso, versão, intenção lógica, tentativa, correlação, precondição, autorização e idempotência contextual | Schema físico, regras por operação/provedor, concorrência e retentativas com estado desconhecido | 12 | HT-03/08/09/16 |
| CT-04 | Resultado técnico, oficial e inconclusivo | INT-04 obtém resultado técnico; proprietário confirma negócio; INT-10 investiga falha | Separar transporte, aplicação técnica, confirmação oficial e reconciliação por fonte; eventos tardios | Estados e transições por entidade, prova oficial por efeito, tratamento de divergência e fallback | 14 | HT-05/08/09/14 |
| CT-05 | Evento/webhook | INT-06 na fronteira externa; Motor Central de Eventos interno; proprietário confirma negócio | Origem/assinatura/versão, causalidade, deduplicação contextual e distinção ack/efeito | Esquemas de eventos, assinatura/anti-replay por provedor, rotas, ordenação, retenção/retentativa | 4 | HT-05/13/16 |
| CT-06 | Sincronização e checkpoint | INT-07 detém cursor/sync; INT-03 mapeamento; proprietário detém registro | Autoridade por campo/direção, progresso comprovado, checkpoint durável e retomada reconciliada | Tipos de cursor, conflitos, exclusões, ordering e políticas por fonte/destino | 4 | HT-03/06/08/16 |
| CT-07 | Lotes e portabilidade | INT-08 movimentação finita; IN-118 central; Arquivos armazenamento; proprietário grava | Arquivo/manifesto, prévia sem escrita, lote/registro, exportação autorizada e prova de persistência | Formatos/limites por operação, schemas, conflito/restauração e responsabilidade em cada etapa | 7 | HT-03/07/13/17 |
| CT-08 | Modelo/instância/etapa multissistema | INT-09 coordenação; Eventos, Filas, Tarefas e Aprovações centrais; proprietários de cada efeito | Instância versionada, dependências, condições de avanço e comprovação oficial por etapa | Grafo/estados/limites de ciclos, políticas de migração, compensação e convenções por operação | 5 | HT-08/09/10/12/15 |
| CT-09 | Saúde, incidente e recuperação técnica | INT-10 especializada; Observabilidade/Incidentes centrais; proprietário confirma negócio | Telemetria confiável vs ausente, playbook homologado, autorização, reconciliação antes de retry | Limiar de saúde, métricas por provedor, limites de playbook, escalonamento e evidência de retorno | 10 | HT-02/08/11/15/16 |
| CT-10 | Compensação tipada | INT-09 coordena; proprietário executa/atesta, Aprovações quando exigidas | Efeito original comprovado, elegibilidade/reversibilidade, autorização, intenção e confirmação próprias | Mapa de compensabilidade por domínio e alçada; efeitos irreversíveis/parciais e retries | 2 | HT-08/10/15 |
| CT-11 | Auditoria/proveniência/evidências | Auditoria Geral oficial; INT-12 consulta/correlaciona; Segurança controla acesso | Fonte/correlação/versão, integridade, minimização, consulta/exportação e auditoria da auditoria | Modelo físico, consulta por caso/campo, retenção por categoria e prova de integridade | 15 | HT-13/14/17 |

\* Contagem obtida por cruzamento das **53 linhas TR iniciais** de [P0-TR53](P0-TR53-vinculo-contratos-aceite.md). Há **105 associações propostas TR→CT** porque um TR pode ter várias CT; **não** são 105 requisitos distintos, regras aprovadas integralmente ou testes executados. O inventário de requisitos originais ainda precisa ser recuperado e validado.

## 3. Referência às bases aprovadas

| Contrato | Documento detalhado | Relações iniciais | Estado |
|---|---|---|---|
| CT-01 | [Base documental](P0-CON-CT01-conectores-conexoes-capacidades-minuta.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |
| CT-02 | [Base documental](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |
| CT-03 | [Base documental](P0-CON-CT03-CT04-comandos-resultados-transicoes.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |
| CT-04 | [Base documental](P0-CON-CT03-CT04-comandos-resultados-transicoes.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |
| CT-05 | [Base documental](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |
| CT-06 | [Base documental](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |
| CT-07 | [Base documental](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |
| CT-08 | [Base documental](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |
| CT-09 | [Base documental](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |
| CT-10 | [Base documental](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |
| CT-11 | [Base documental](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) | [Rastreabilidade proposta](P0-TR53-vinculo-contratos-aceite.md) | **Aceita como base**; definição executável pendente |

**Hierarquia:** as CT desdobram os contratos de serviços e módulos já existentes; a **operação individual** decide quais campos/controles são exigíveis e quem confirma o efeito. A INT de transporte/coordenação não recebe autoridade de negócio. Qualquer divergência com documento IN/INT aprovado deve ser resolvida por fonte e precedência, não por atualização silenciosa desta matriz.

## 4. Critérios transversais de aceite documental por operação (candidatos)

| ID de controle proposto | Tema | Evidência que deve ser verificável | CT relacionadas | Lacuna de fechamento |
|---|---|---|---|---|
| M-CON01 | Fonte de versão/objeto | O tenant/conta/conexão e versão homologada não conferem direito externo nem interno | CT-01/02, INT-01/02/04/11 | Identificadores e estados tipados; serviço responsável; condições de suspensão/migração |
| M-CON02 | Autorização por ato | Toda operação protegida revalida contexto sem criar privilégio por IA ou token externo | CT-02/03/08/10/11 | Matriz papel/recurso/campo/ato e decisão humana aplicável, com validade/reuso |
| M-CON03 | Comando e resultado | A identidade do pedido lógico separada da tentativa e da confirmação de negócio | CT-03/04/08/09 | Transições por operação, precondições, idempotência, provedor e prova oficial |
| M-CON04 | Interpretação de eventos | Webhook externo e evento interno não equivalem a autorização/negócio concluído | CT-04/05/08/11 | Esquema autenticado, dedup/replay, fonte de evento interno e confirmação |
| M-CON05 | Mutação de dados | Checkpoint ou lote não avançam com base em simples ACK e nunca mesclam registros destrutivamente por inferência | CT-03/04/06/07 | Schema por campo/lote, origem de verdade, versionamento, conflitos e retomada |
| M-CON06 | Recuperação e reversão | Saúde técnica e compensação são distintos do efeito original e exigem autoridade própria | CT-04/08/09/10 | Catálogo de efeitos, elegibilidade, risco/alçada, prova de retorno e de ato compensatório |
| M-CON07 | Evidência oficial | Consulta investigativa não substitui Auditoria Geral ou regra de retenção por categoria | CT-04/09/11 | Proveniência/integridade, acesso, classificação, consulta/exportação e guarda |
| M-CON08 | Compatibilidade | Instância, conector, credencial, comando, evento e cursor preservam suas versões | CT-01/02/03/05/06/08 | Matriz de compatibilidade/coexistência, critérios por operação e migração autorizada |
| M-CON09 | Portabilidade | IN-118 oferece recursos essenciais em todos os planos, INT-08 não assume backup inteiro | CT-07/11 e IN-118 | Manifestos, direitos de exportação/importação, destino de dados e arquivos |
| M-CON10 | Acesso de app/trial | API pública no trial bloqueada; integrações nativas elegíveis permanecem permitidas | CT-01/02/03 e INT-05 | Catálogo de entradas/identidades, escopos, delegação e plano |

Estes IDs M-CON01..10 são **itens de checklist propostos**, não novas funcionalidades, direitos, workflows, APIs ou testes. A validação futura deverá marcar por operação: **aplicável**, **não aplicável com justificativa**, **definição pendente**, **documentado e revisado**, **aprovado**. Não presumir que toda operação consome as 11 CT.

## 5. Critério de pacote mínimo para contrato por operação

A documentação final por operação deverá conter referências verificáveis a:
1. **Decisão de origem** e versão: INT/IN, cláusula/aceite, tenant/empresa, finalidade, módulo proprietário e competência central.
2. **Identidade do ato:** OP/CT, comando lógico, recurso/campo/entidade, contrato/versão/capacidade, precondição e distinção das tentativas.
3. **Autorização:** perfil/atribuição efetiva, escopo/credencial, validade/revogação, aprovador e alçada quando exigidos. **Sem inventar valores ou direitos universais**.
4. **Transporte e resultado:** preparado/enviado/aceito/aplicado/confirmado/rejeitado/pendente/inconclusivo **conforme dimensão correta**, sem usar essa lista como enum universal físico.
5. **Idempotência/concorrência:** identificadores contextuais, retomada, fallbacks, resultado desconhecido e limiar de bloqueio seguro, com regra real por operação/provedor.
6. **Compatibilidade:** referência da versão, impactos em instâncias/filas/checkpoints antigos, convivência, migração ou interrupção controlada.
7. **Recuperação/compensação:** dono e elegibilidade, irreversibilidade, alcance do ato, resultados parciais, confirmação oficial e tratamento de falha.
8. **Auditoria:** origem, correlação, classe de retenção, regra de consulta/exportação e trilha da própria investigação quando necessário.
9. **Testes futuros:** HT-01..18 pertinentes, precondições e dataset sintético, resultado esperado, evidências e função responsável; **não executar agora**.

**Regra:** não criar campos físicos, APIs ou enums definitivos apenas para preencher itens. Se um dado ainda não foi decidido, registrar como pendência com dono funcional, impacto e prioridade.

## 6. Relações críticas que exigem validação de fronteiras

- **CT-01 + CT-02 + CT-03:** conector homologado, conexão cadastrada e token externo válido **não autorizam isoladamente um ato**.
- **CT-03 + CT-04 + CT-10:** timeout ou resposta desconhecida não autorizam retry de escrita nem reversão automática.
- **CT-04 + CT-05 + CT-08:** webhook assinado e workflow concluído não confirmam resultado oficial de negócio.
- **CT-06 + CT-07:** sync contínua e lote finito que atinjam mesmo recurso precisam de versão, propriedade/concorrência e regra de conflito.
- **CT-07 + IN-118:** a Central de Backup e Portabilidade preserva autoatendimento dos planos inclusive Básico; INT-08 não absorve a Central.
- **CT-08 + CT-09 + CT-10:** saúde técnica e conclusão do processo/compensação são fatos separados, com autoridades distintas.
- **CT-09 + CT-11:** observabilidade/log técnico não substitui a Auditoria Geral nem sua retenção específica.
- **CT-02 + CT-11:** leitura de evidência não concede exportação ampla ou acesso cruzado entre tenants.
- **TR-INT08-005 → CT-10:** a ligação no inventário inicial sinaliza **proibição de rollback universal/mesclagem destrutiva**, não direito de INT-08 executar compensações. Exige cotejo literal antes de alterar a associação.
- **Mudança de vínculo Site→CRM:** alteração técnica pela Imagine via chamado não transfere dados históricos; submissões anteriormente aceitas mantêm referência ao destino correto.

Os [13 pontos de atenção da revisão cruzada](P0-CON-revisao-cruzada-CT01-CT11.md) continuam em análise. Este documento não os declara resolvidos nem cria uma nova central de governança.

## 7. Evidências para encerrar P0-CON-01 na fase DOCUMENTAL

| Evidência documental | Aceite necessário para declarar P0-CON-01 fechada | Estado 03/11/2026 |
|---|---|---|
| E-CON01 — Bases das famílias | Aceite identificável de CT-01 a CT-11, sem confundir princípios com schemas | **Disponível — 11/11 aceitas como bases** |
| E-CON02 — Cobertura e proveniência | Fonte primária INT/IN recuperada, TR granulares completos e mapa CT/INV/HT verificado | **Pendente — P0-DOC-01/02 e P0-TR-01** |
| E-CON03 — Autoridade e permissão | Proprietários por operação/recurso/campo; direitos e alçadas aplicáveis aprovados | **Pendente — P0-OWN/SEC** |
| E-CON04 — Contratos por operação | Campos obrigatórios condicionais, versões, esquemas, estados, aceites, erros, evidência oficial | **Pendente — P0-CON-01** |
| E-CON05 — Idempotência e recuperação | Retentativas, concorrência, reconciliação, resultados inconclusivos e compensabilidade por efeito | **Pendente — P0-CON/DEP** |
| E-CON06 — Integração e versões | Bootstrap, dependências/eventos/loops, compatibilidade e instâncias ativas | **Pendente — P0-DEP/VER** |
| E-CON07 — Serviços adjacentes | IN-118/124/148/149/151/159/160/162/167/168 e outros, conforme cada contrato, sem sobreposições | **Parcial — P0-XREF-01** |
| E-CON08 — Critérios de aceite documental | Plano de verificação HT aplicável, revisores/competências e responsáveis designados para fechamento | **Parcial — P0-ACC-01: planos HT aceitos como base** |
| E-CON09 — Revisão e aprovação final | Contratos por operação aprovados documentalmente, decisões faltantes resolvidas, referências e versão imutável após revisão | **Pendente — P0-GIT/G05/G06** |

### Regra de fechamento por decisão (sem alterar prioridades aprovadas)

P0-CON-01 **somente poderá ser declarada documentalmente encerrada** quando:
- os contratos aplicáveis estiverem detalhados e aprovados no grau necessário à programação segura, ou quando exceções específicas estiverem justificadas e aprovadas pelo proprietário;
- parâmetros ainda em P1/P2 forem atribuídos ao período correto de decisão, **sem deixar sem resposta item de segurança/contrato que bloqueia programação**;
- todas as pendências de autoridade, versão, origem e fronteira que afetem o contrato estiverem ligadas aos respectivos P0 e sem contradição aberta;
- houver evidência documental de revisão e aceite da versão; nenhum HT precisa aparecer artificialmente como **executado** para isso.

**Importante:** P0-ACC-01 requer fechar a **documentação dos planos e critérios** nesta fase; a realização física dos 18 HT será tratada na etapa futura autorizada de desenvolvimento/homologação, não como condição impossível de cumprir antes de começar a programar.

## 8. Relação com as condições gerais G01–G06 (INT-R02)

| Gate de fechamento do módulo | P0 relacionada | O que ainda impede o gate | Situação |
|---|---|---|---|
| G01 — Rastreabilidade | P0-DOC-01/02 e P0-TR-01 | Texto integral original e origem referenciada por cláusula; 53 IDs iniciais não são exaustivos | Aberto |
| G02 — Documentação integral | P0-CON-01 e documentação INT-01..12 | Contrato por operação e versão, inclusive CT-01..11, decisões pendentes classificadas e anexos de definição | Parcial |
| G03 — Competências e dependências | P0-OWN/SEC/DEP/VER/XREF | Fontes oficiais, dados/atos, limites de cada motor e recuperação e risco de ciclos tratados | Parcial |
| G04 — Pendências controladas | Painel P0, IN-168 independente | Responsável, prioridade, impacto e etapa para cada decisão faltante; bloqueadores identificados | Parcial |
| G05 — Aceite da arquitetura | P0-CON/ACC e revisão total | Parecer e aceite documental expresso dos proprietários/Segurança, sem confundir testes executados | Pendente |
| G06 — Referência final | P0-GIT-01 | Versão documental global final, commit imutável e aprovação expressa após conferência | Pendente |

**Não encerrar G01–G06 apenas por possuir 11 contratos-base.** Somente revisão/aceite global, cotejo da fonte de origem e referência final imutável podem concluir o módulo INT.

## 9. Próximos passos documentais sugeridos

1. Preparar uma **matriz granular de contrato por operação**, a partir das 24 OP de referência e do catálogo de requisitos que vier a ser recuperado, começando pelas operações com efeito de negócio, compensação e alterações de conexão.
2. Desdobrar os 13 pontos de fronteira e suas dependências por tipo de aresta, sem assumir erro arquitetural comprovado.
3. Recuperar aprovação original por cláusula (INT-01..12, INT-R01/R02) e corrigir/confirmar vínculos TR→CT/INV/HT apenas quando fundamentados.
4. Obter aceite expresso desta matriz e das decisões concretas antes de declarar P0-CON-01 concluída.

**Conclusão:** matriz mestre das **11 CT** criada, 105 vínculos iniciais TR→CT identificados, critérios documentais P0-CON-01 estruturados. **P0-CON-01 permanece ABERTO/PARCIAL**, sem autorização para implementação, testes ou criação de automações.
