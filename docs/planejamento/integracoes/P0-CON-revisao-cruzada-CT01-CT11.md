# P0-CON — Revisão cruzada das 11 famílias CT-01 a CT-11

**Data:** 2026-11-03. **Estado:** **REVISÃO DOCUMENTAL / PONTOS PARA VALIDAÇÃO**, não aceite de contratos executáveis, nem fechamento P0-CON-01.
**Decisão anterior:** [CT-05 a CT-11 aceitas como bases documentais](P0-DEC-aprovacao-CT05-CT11-base.md) por “ok” contextual, sem aprovar schemas, enums, prazos, parâmetros, direitos, alçadas e execução.
**Outros aceites:** [CT-02](P0-DEC-aprovacao-CT02-base.md), [CT-03/04](P0-DEC-aprovacao-CT03-CT04.md), [P0-SEC/VER](P0-DEC-aprovacao-SEC-VER-base.md), [18 HT](P0-DEC-aprovacao-ACC-HT01-HT18-base.md). **CT-01 possui somente definição conceitual inicial e segue sem minuta especializada/aceite próprio.**
**Fonte funcional:** [INT-R01](INT-R01-governanca-contratos-homologacao.md), [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md), [P0-CON geral](P0-CON-contratos-tecnicos-minuta.md), [P0-CON CT-05..11](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) e [P0-TR53](P0-TR53-vinculo-contratos-aceite.md).
**Fase:** planejamento e documentação apenas; sem programação, nova automação, ambiente real, teste, custos ou publicação.

## 1. Critério de correspondência entre contratos

As onze famílias documentais **não são microsserviços ou motores separados**. Uma mesma operação pode consumir várias CT, mas só existe **uma autoridade competente para cada espécie de fato**. Os identificadores referenciam fatos/versões com proveniência; não conferem privilégio nem consenso transacional.

**Semântica:** comando CT-03 é pedido tipado, CT-04 é evidência/resultados e confirmação por dono; CT-02 é contrato de autorização com serviços centrais, não novo IAM. CT-05/06/07/08/09/10/11 modelam fronteiras especializadas, sem duplicar Eventos/Filas/Tarefas/Observabilidade/Auditoria.

## 2. Matriz mestre dos onze contratos

| CT | Domínio do contrato | Proprietários/componentes | Fonte de resultado oficial | Fronteira crítica | Vínculos TR53* | HT de referência | Grau de documentação |
|---|---|---|---|---|---:|---|---|
| CT-01 | Catálogo, conector, conexão e capacidade | INT-11 homologa versões/capacidades; INT-01 é dona da conexão; INT-04 descreve operação tipada | INT-11 para elegibilidade da versão; INT-01 para vínculo da conta; provedor quando aplicável | Catálogo homologado **não** equivale à conexão, autorização ou confirmação de efeito | 11 | HT-01/12/18 | Família conceitual no P0-CON, sem minuta CT-01 especializada aprovada |
| CT-02 | Autorização contextual e alçadas | Identidade e Segurança, INT-02 (externas), Central de Aprovações; proprietário autoriza efeito | Serviços centrais atestam permissão/aprovação, proprietário valida o ato | Ter token/assinatura não concede direito a tenant, campo, efeito ou aprovação humana | 21 | HT-02/04/13/15 | Base documental aceita; perfis/MFA por ato e alçadas definitivas pendentes |
| CT-03 | Comando tipado e identidade lógica | INT-04 transporta; INT-09 coordena quando aplicável; proprietário mantém regras | INT-04 atesta transmissão; o negócio é atestado por proprietário | command_ref lógico e attempt_ref físico não se confundem; uma retentativa não é novo ato | 12 | HT-03/08/09/16 | Base documental aceita; schema, idempotência real do provedor e concorrência detalhada pendentes |
| CT-04 | Resultados, estados e confirmação oficial | INT-04 traduz evidência técnica; proprietário atesta efeito; INT-10 reconcilia tecnicamente | Provedor/executor para efeito técnico; módulo proprietário para confirmação oficial | HTTP 2xx, webhook/n8n concluído ≠ negócio confirmado; rejeitado não implica nenhum efeito parcial | 14 | HT-05/08/09/14 | Base documental aceita; enum/transições por domínio e fonte oficial por efeito pendentes |
| CT-05 | Evento/webhook externo e assinatura | INT-06 fronteira externa; Motor Central de Eventos detém evento interno; COM comunica ao cliente | INT-06 para entrada/entrega externa; Motor de Eventos para interno; proprietário para efeito | Webhook válido pode conter pedido não autorizado; ack não é entrega de negócio; deduplicação por identidade | 4 | HT-05/13/16 | Base documental aceita neste turno; protocolo por provedor/assinatura/retentativa pendente |
| CT-06 | Sincronização, cursor e checkpoint | INT-07 controla sync/cursor; INT-03 mapeia; proprietário confirma dados | INT-07 para progresso de cursor; proprietário para registro efetivamente aplicado | Não avançar cursor por aceite técnico; autoridade por campo não surge da bidirecionalidade | 4 | HT-06/08/16 | Base documental aceita neste turno; critérios por fonte, conflito e exclusão pendentes |
| CT-07 | Arquivo, lote e movimentação delimitada | INT-08 coordena lote; IN-118 detém Central de Portabilidade; Arquivos armazena; proprietários gravam | INT-08 para processamento por lote; proprietário para item; IN-118 para serviço central de portabilidade | Importação/exportação não substitui backup/ restauração; vínculo de site não migra históricos; sem merge destrutivo | 7 | HT-03/07/13/17 | Base documental aceita neste turno; arquivo/manifesto, campos, escopos e formato definitivos pendentes |
| CT-08 | Modelo, instância, etapa e dependências | INT-09 detém coordenação; Eventos/Tarefas/Datas/Aprovações centrais preservados | INT-09 atesta execução da instância; proprietários confirmam cada negócio | Instância v1 não recebe modelo v2 silenciosamente; convergência requer dependências reais | 5 | HT-08/09/10/12/15 | Base documental aceita neste turno; máquina de estados/limites por processo pendentes |
| CT-09 | Diagnóstico, incidente e recuperação técnica | INT-10 especializa, Observabilidade IN-159/160 e Incidentes são centrais | INT-10 para saúde técnica; proprietário para negócio; auditoria pela Auditoria Geral | Provedor saudável ≠ comandos concluídos; ausência de sinal ≠ saudável; IA sem permissão crítica | 10 | HT-08/11/15/16 | Base documental aceita neste turno; frescor, alertas, playbooks/alçadas pendentes |
| CT-10 | Compensação tipada por efeito | INT-09 coordena; proprietário do efeito decide, executa, confirma; Aprovações conforme risco | Proprietário do ato compensatório, com correlação ao ato original | Cancelar execução técnica não cancela FIN/CON/AGD; rollback universal não existe | 2 | HT-10/15 | Base documental aceita neste turno; compensabilidade por efeito/alçada/semântica pendentes |
| CT-11 | Evidência, investigação e exportação controlada | Auditoria Geral é fonte oficial; INT-12 consulta/correlaciona; Analytics conserva relatórios oficiais | Auditoria Geral para proveniência; proprietário para fatos; INT-12 não altera prova | Log técnico ≠ auditoria oficial; retenção por categoria; leitura ≠ exportação sem direito | 15 | HT-13/14/17 | Base documental aceita neste turno; modelo de evidência/retention e perfis pendentes |

\* **Contagem automatizada nesta revisão:** 53/53 linhas do inventário inicial TR53; cada TR pode referenciar **mais de uma CT**. Foram contados **105 vínculos TR→CT** somando as famílias, e não 105 requisitos distintos ou aprovações originais recuperadas. Vínculos apenas **propostos** nas matrizes iniciais, não exaustividade certificada.

## 3. Cobertura das operações OP24 e cenários HT

| CT | INT associadas | Casos OP24 representativos | Cenários HT relacionados |
|---|---|---|---|
| CT-01 | INT-01/02/04/11 | OP-01-CAD, OP-01-CIC, OP-11-HOM, OP-11-SUS | HT-01/12/18 |
| CT-02 | INT-02 + Segurança/Aprovações + proprietários | OP-02-AUT, OP-02-REV, OP-05-API, OP-09-COM | HT-02/04/13/15 |
| CT-03 | INT-04/09 + produtores | OP-04-ENV, OP-09-INI, OP-07-SYNC, OP-08-IMP | HT-03/08/09/16 |
| CT-04 | INT-04 + proprietários | OP-04-RSL, OP-06-IN, OP-08-IMP, OP-09-INI | HT-05/08/09/14 |
| CT-05 | INT-06 + Eventos/Filas/COM | OP-06-IN, OP-06-OUT | HT-05/13/16 |
| CT-06 | INT-07 + INT-03/04/10 + proprietários | OP-07-SYNC, OP-07-RET | HT-06/08/16 |
| CT-07 | INT-08 + IN-118/Arquivos + proprietários | OP-08-IMP, OP-08-EXP | HT-03/07/13/17 |
| CT-08 | INT-09 + motores centrais/proprietários | OP-09-INI, OP-09-COM | HT-08/09/10/12/15 |
| CT-09 | INT-10 + Observabilidade/Incidentes/Segurança | OP-10-DIA, OP-10-REC | HT-08/11/15/16 |
| CT-10 | INT-09 + FIN/CON/AGD e outros proprietários | OP-09-COM | HT-10/15 |
| CT-11 | INT-12 + Auditoria Geral/Segurança | OP-12-INV, OP-12-AUD | HT-13/14/17 |

**Caveat:** 24 operações são amostras aceitas como base de trabalho, não catálogo completo. HT-01..HT-18 têm roteiros documentais aceitos como bases; **0 testes executados**. A ausência de determinado código HT em uma linha não significa que a norma/contrato não o requeira; depende de revisão completa de requisitos.

## 4. Distinções de semântica que precisam se manter verdadeiras

| Situação | CT responsável pelo dado | Contrato que faz verificação | Fato que NÃO está autorizado por esse dado |
|---|---|---|---|
| Provedor e versão homologados | CT-01, INT-11 | CT-02 para acesso; CT-03 para comando | Usar qualquer tenant sem conexão/credencial válida |
| Token externo válido | CT-02/INT-02 | CT-02 + proprietário por ato | Acesso universal ao CRM ou aprovação financeira |
| Retorno HTTP 200/202 | CT-04 | Proprietário do negócio | Pagamento, reserva, entrega de lead ou contrato confirmado |
| Webhook válido e recebido | CT-05 | CT-02 e Motor Central de Eventos | Executar efeito não autorizado só por evento externo |
| Checkpoint registrado | CT-06 | CT-04 e fonte oficial dos dados | Concluir sincronização de registros não confirmados |
| Arquivo importado parcialmente | CT-07 | CT-04 e proprietário de cada registro | Marcar operação completa ou desfazer todos dados automaticamente |
| Processo INT-09 tecnicamente concluído | CT-08 | CT-04 e proprietários por etapa | Encerrar atos de negócio ainda pendentes |
| Provedor novamente saudável | CT-09 | CT-04/11 e proprietário | Encerrar todos os comandos antes inconclusivos |
| Compensação transmitida | CT-10 | CT-04 e dono do ato compensatório | Estorno/cancelamento concluído sem confirmação oficial |
| Resultado de consulta à auditoria | CT-11 | Auditoria Geral como fonte oficial | Editar fatos, confundir hipótese de IA com prova |

## 5. Riscos de interpretação, lacunas e colisões de fronteira

| ID | Famílias ou fonte | Potencial risco de desenho/interpretação (não falha comprovada) | Salvaguarda preservada | Evidência/decisão ainda necessária | Pendência P0 |
|---|---|---|---|---|---|
| REV-01 | CT-01 ↔ CT-02 ↔ CT-03 | Catálogo/conexão, autorização e comando tipado são dependências recíprocas; tentativas de bootstrap circular | Separar existência/configuração da elegibilidade de execução; negar uso até todos os elementos necessários estarem válidos | Regras de transição/capacidades e vínculos por tenant/ambiente, com provas por operação | P0-DEP/CON/SEC |
| REV-02 | CT-02 ↔ CT-03 ↔ CT-08 | Instância pendente pode ter aprovação expirado/revogada; revalidar não deve impor aprovação duplicada da mesma decisão | Revalidar escopo e condições nas fronteiras protegidas; central retém referência da aprovação específica | Semântica de validade e mudança de contexto por etapa | P0-SEC/OWN |
| REV-03 | CT-03 ↔ CT-04 ↔ CT-10 | Timeout de comando original seguido de compensação/retentativa pode duplicar efeito | Pedir estado oficial, reconciliar; compensação é ato distinto com idempotência e autoridade próprias | Prova por operação de garantias externas/compensabilidade | P0-CON/OWN |
| REV-04 | CT-04 ↔ CT-05 | Webhook recebido e status de entrega podem ser confundidos com confirmação oficial | Tratar autenticidade, recepção técnica e evento interno como três fatos diferentes; só proprietário confirma negócio | Versionamento de evento e fonte da confirmação por módulo | P0-CON/XREF |
| REV-05 | CT-05 ↔ CT-08 | Webhook dispara processo que gera novo evento e reinicia mesmo processo (loop) | IDs de causalidade e limites de processamento por tipo, sem motor paralelo | Modelo tipado de eventos, proteção anti-loop e tratamento fora de ordem | P0-DEP/CON |
| REV-06 | CT-06 ↔ CT-03/04 | Checkpoint pode avançar após ack técnico sem persistência proprietária | Avançar apenas progresso comprovado; inconclusivo exige reconciliação | Contrato de cursor por provedor, fonte oficial e ordenação | P0-CON/VER |
| REV-07 | CT-06 ↔ CT-07 | Sync contínua vs lote delimitado de importação podem escrever no mesmo registro e criar conflito | Isolar propriedade/versões, controle de concorrência e escopo; INT-07 controla cursor, INT-08 lote | Guarda por entidade/campo/recurso e resolução de conflitos por proprietário | P0-DEP/OWN |
| REV-08 | CT-07 ↔ IN-118 ↔ CT-11 | Exportação/backup/restauração podem ser tratados como mesma operação e ter retenção equivocada | IN-118 detém Central; INT-08 movimentação delimitada; Arquivos armazenamento; Auditoria Geral evidência | Contrato de pacote, manifesto, restauração e expiração por classe | P0-XREF/CON |
| REV-09 | CT-08 ↔ CT-09 ↔ CT-10 | Restabelecimento de conexão pode encerrar instância com compensação ainda pendente | Separar saúde, coordenação, negócio e compensação; manter pendências com provas | Estados por processo, conduta de retorno/encerramento e alçadas | P0-CON/DEP |
| REV-10 | CT-01 ↔ CT-08 ↔ P0-VER | Versão de conector suspensa enquanto instância está aberta gera reenvio indevido em versão nova | Manter referência de versão do comando/instância e reavaliar compatibilidade/autorização antes de qualquer migração | Política de convivência, suspensão e preservação de evidências | P0-VER/CON |
| REV-11 | CT-09 ↔ CT-11 | Telemetria/log técnico de prazo curto pode ser tomado como trilha de auditoria oficial ou estado normal | Usar Observabilidade para sinal, Auditoria Geral para prova; preservar lacunas e retenções específicas | Classificação de evidência, integridade e contingência de logs/auditoria | P0-XREF/ACC |
| REV-12 | CT-07 ↔ CT-10 (TR-INT08-005) | Vínculo CT-10 na matriz TR53 pode ser interpretado como permissão de compensação para INT-08 | Interpretar provisoriamente como **restrição negativa** a rollback universal/mesclagem, não concessão de função de compensar | Conferir o texto original da INT-08 e ajustar associação sem alterar aprovação funcional | P0-TR/CON |
| REV-13 | CT-11 ↔ CT-02 | Investigador autorizado a ler tenta exportar evidência sensível de outro tenant | Separar direito de leitura/exportação e escopo por caso/tenant/finalidade | RBAC, classificação e alçadas por categoria de evidência | P0-SEC/OWN |

**Status das 13 linhas:** **riscos documentais para revisão**, não treze novos requisitos, incidentes, vulnerabilidades comprovadas ou conflitos literais entre decisões aprovadas. **REV-12** é ressalva na associação *proposta* `TR-INT08-005 → CT-10`, não a revogação de requisito da INT-08.

## 6. Percursos de validação entre contratos, sem teste executado

### PC-01 — Conectar e executar operação externa
- CT-01 confirma conector/capacidade homologados e vínculo de conexão; CT-02 valida credencial/identidade/escopo/aprovação específica.
- CT-03 representa comando tipado; CT-04 evidencia envio/aceite/aplicação e remete ao **proprietário** a confirmação oficial.
- CT-11 liga evidência à Auditoria Geral. Uma atualização de conector não migra solicitação ativa silenciosamente.
- **Revisão futura:** HT-01/02/08/12/18, sem testes agora.

### PC-02 — Webhook que dispara processo com efeitos e compensação
- CT-05 valida ingresso externo e envia evento ao **Motor Central de Eventos**; CT-08 coordena instância e dependências via serviços centrais.
- CT-02 exige autorização por etapa, CT-03/04 cuidam de comando e fato, e CT-10 só permite compensação quando houver competência proprietária, elegibilidade e aprovação.
- CT-09 auxilia em falha técnica e reconciliação, CT-11 audita por fonte oficial.
- **Revisão futura:** HT-05/08/09/10/14/15/16.

### PC-03 — Sincronização e lote de portabilidade no mesmo dado
- CT-06 mantém checkpoint/progresso de sync e autoridade por campo; CT-07 mantém lote finito com prévia/mapeamento e estados por item.
- INT-03 valida esquemas; CT-02 autoriza e CT-03/04 identificam escritas/tentativas/resultados.
- IN-118 continua responsável por Backup e Portabilidade autoatendidos (inclusive Básico); INT-08 não substitui a Central. CT-11 preserva auditoria de importação/exportação.
- **Revisão futura:** HT-03/06/07/13/17.

### PC-04 — Incidente de provedor e evidência histórica
- CT-09 consome Observabilidade central IN-159/160, com saúde desconhecida quando não há evidência.
- CT-04 conserva comandos enviados sem resultado, CT-02 revalida acesso, CT-10 só permite compensação tipada conforme dono.
- CT-11 consulta Auditoria Geral; retenção IN-162 de log técnico não vira política universal de fatos de negócio.
- **Revisão futura:** HT-08/11/14/16/18.

## 7. Pendências de revisão documental e especificação por prioridade

| Item | Precisa definir | Status |
|---|---|---|
| CT-X01 | **CT-01**: ficha especializada, fronteiras INT-01/02/11, estados de conexão/capacidade, versões e condições de ativação/suspensão | **Pendente de minuta/aceite** |
| CT-X02 | Fonte primária integral e escopo real de cada regra INT-01..12 e R01/R02, incluindo TR-INT08-005 | **P0-DOC-01/02 e P0-TR-01 abertos** |
| CT-X03 | Schemas físicos, campos obrigatórios condicionais e tipagem por operação CT-01..11 | **P0-CON-01 aberto** |
| CT-X04 | IDs, versões, garantias externas, idempotência, concurrency, timeouts, retry e tratamento de falhas por conector/efeito | **P0-CON/VER abertos** |
| CT-X05 | Perfil, recurso/campo, MFA, delegação, escopos e alçadas por ação/tenant | **P0-OWN/SEC abertos** |
| CT-X06 | Tipagem das arestas dependentes e ciclos/loops seguros entre eventos/instâncias/recuperações | **P0-DEP aberto** |
| CT-X07 | Retenção/capacidade e política especializada de filas de leads, portabilidade e auditoria sem regras globais indevidas | **P0-XREF aberto** |
| CT-X08 | Cobertura integral TR/CT/INV/HT, responsáveis, critérios e evidências de aceite futuro | **P0-TR/ACC abertos** |

## 8. Próximo avanço sem programação

1. **Detalhar CT-01** como família ainda somente conceitual, definindo separação entre catálogo homologado, conexão, autorização externa e operação.
2. Refinar o cenário **REV-12** para não interpretar a relação negativa de CT-10 com INT-08 como permissão de compensação, mediante cotejo original quando disponível.
3. Preparar esquemas **documentais não executáveis**, tabelas de transição e matriz de autoridade por contrato/operador, sem preencher lacunas com números inventados.
4. Solicitar aceite expresso do responsável sobre esta revisão cruzada e a minuta de CT-01, antes de considerar as fronteiras documentais definitivamente aceitas.

**Conclusão:** 11/11 famílias CT analisadas em revisão cruzada, 13 riscos documentais catalogados, 105 associações TR→CT do inventário inicial conferidas por referência. **P0-CON-01, P0-DOC-01, P0-TR-01, P0-DEP-01 e G01–G06 continuam abertos/parciais. Nenhum teste ou código foi executado.**
