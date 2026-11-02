# IN-157 — Gestão Inteligente de Modelos de IA e Contingência dos Agentes Técnicos

**Status:** APROVADA funcionalmente pelo responsável em 02/11/2026 (confirmação "ok").  
**Etapa:** planejamento e documentação; **não iniciar programação, implantação, contratação de fornecedores nem alteração de consumo ou permissões**.  
**Escopo:** sete agentes técnicos especializados, Agente Supervisor Técnico, orquestração dos modelos e serviços técnicos de monitoramento/contingência da Imagine. Esta política deve ser conciliada com a governança e cobrança dos agentes comerciais do CRM, sem misturar quotas ou custos de empresas clientes com os custos internos Imagine.  
**Referências:** IN-130 a IN-132 (incidentes, agentes e autonomia), IN-137/IN-138 (matriz de riscos e aprovação humana única), IN-139/IN-140 (aprendizado e avaliação), IN-142 (controle de capacidade e custos), IN-143 a IN-146 (continuidade operacional), IN-150/IN-151 (segurança e segredos), IN-153 (inventário), IN-154/IN-155 (mudanças e procedimentos homologados), IN-156 (autodiagnóstico e recuperação de agentes).

## Objetivo

Permitir que agentes técnicos utilizem modelos de IA compatíveis com a atividade, com **seleção por capacidade, desempenho, confiabilidade, disponibilidade e custo**, e possam **recorrer automaticamente a alternativas homologadas** quando o modelo principal falhar, apresentar lentidão, indisponibilidade ou limite de uso. A troca de modelo deve preservar a segurança, os limites financeiros, o histórico e o controle das operações.

**A continuidade técnica crítica não pode depender exclusivamente da disponibilidade de modelos de IA.**

## Capacidades aprovadas

1. **Gerenciamento de modelos por agente e tarefa:** manter catálogo interno de modelos e fornecedores **autorizados**, com capacidades relevantes, finalidade, compatibilidade de ferramentas, restrições de dados, desempenho observado, disponibilidade e custos. Um mesmo agente poderá usar modelos diferentes segundo a tarefa.
2. **Seleção inteligente:** escolher dentre alternativas já autorizadas considerando complexidade, latência, qualidade, risco, contexto disponível, privacidade e consumo. A decisão pode ser auxiliada por IA, mas as políticas de autorização e orçamento devem ser verificadas por mecanismos independentes.
3. **Contingência automática:** diante de falha, indisponibilidade, timeout ou limite do modelo primário, encaminhar a tarefa a **outro modelo previamente homologado**, apenas quando este suportar o tipo de tarefa e os limites de segurança/financeiros aplicáveis.
4. **Controle de qualidade:** medir respostas inválidas, falhas de ferramenta, verificações de fatos técnicos e resultados práticos das intervenções; avaliar se o modelo substituto é confiável para aquela tarefa. **Não equiparar uma resposta plausível a uma ação executada com sucesso.**
5. **Controle de custos:** acompanhar consumo por agente, modelo, tarefa, ambiente e período, com tetos e alertas; **não contratar novos planos, fornecedores, modelos pagos ou ultrapassar limites financeiros aprovados sem autorização humana específica**.
6. **Continuidade das tarefas:** preservar identificador, estado, progresso verificado, decisões já autorizadas e histórico mínimo necessário para retomar tarefas após a troca, sem executar novamente efeitos externos que já tenham sido concluídos.
7. **Aprendizado contínuo:** registrar desempenho, custos, falhas, trocas, eficácia e limitações por tipo de atividade, subsidiando avaliações dos agentes e futuras revisões de modelos.
8. **Administração e supervisão:** permitir à administração Imagine definir modelos elegíveis, limites, política de preferência e contingência. O Supervisor Técnico acompanha o resultado, mas **não concede permissões nem amplia o catálogo por conta própria**.

## Regras fundamentais de segurança e autonomia

- **Trocar de modelo não troca a identidade, as permissões, o nível de autonomia ou o escopo do agente.** Toda ação executável continua sujeita aos controles determinísticos do orquestrador, à matriz IN-138 e aos playbooks IN-155.
- **Ações críticas** continuam exigindo **uma única aprovação humana específica**, mesmo quando a tarefa foi retomada em outro modelo. Não duplicar aprovações nem interpretar a mudança como concessão de acesso.
- Modelos podem recomendar decisões, mas não podem revogar restrições, redefinir sozinhos limites de custo, desativar monitoramento, inventar homologação ou editar políticas de segurança.
- O fallback deve consultar disponibilidade, autorização, qualidade e orçamento antes de rotear a tarefa; alternativa de IA não aprovada **não** é automaticamente elegível por estar acessível.
- Preservar proteção de dados em cada fornecedor: não enviar senhas, tokens, chaves brutas, logs sensíveis ou dados pessoais desnecessários aos prompts. Compartilhar entre fornecedores **apenas o contexto mínimo autorizado** e respeitar bases legais, contratos e regras de tratamento de dados.
- Proteger contra injeção de instruções recebidas de tickets, logs, sites e ferramentas externas, independentemente do modelo escolhido.
- A execução de tarefas com efeitos externos precisa ser idempotente ou ter método confiável para verificar se a etapa anterior foi concluída antes de repeti-la.
- O monitor independente e as políticas de contenção continuam funcionando mesmo quando o Supervisor Técnico ou todos os modelos falharem (IN-156).

## Gatilhos previstos para contingência

- **Indisponibilidade:** erro de conexão, erro do provedor, falha de autenticação ou indisponibilidade documentada.
- **Lentidão:** tempo excedido em relação ao prazo compatível com a tarefa e aos limites definidos.
- **Limites e orçamento:** quota atingida, throttling, previsão de ultrapassar limite aprovado ou restrição de consumo.
- **Qualidade incompatível:** saída fora do contrato esperado, erro repetido de uso de ferramentas, resultado não verificável ou incapacidade técnica do modelo para a tarefa.
- **Risco de segurança:** comportamento anormal ou perda de integridade de instruções/ferramentas, exigindo contenção; **nem toda falha deve ser resolvida tentando indiscriminadamente outro modelo**.

A definição dos limiares, número máximo de tentativas, ordem das alternativas e condições para retornar ao modelo principal é **pendente de detalhamento técnico**, não sendo criada aqui nenhuma quota ou preço novo.

## Fluxo funcional aprovado

1. Registrar tarefa com ID estável, tipo, criticidade, escopo, permissões e orçamento aplicável.
2. Selecionar modelo homologado adequado às capacidades e políticas da atividade.
3. Antes de cada ação executável, validar permissões e playbook independentemente da saída do modelo.
4. Detectar falha ou degradação pelo monitor independente e registrar evidência.
5. Verificar alternativa homologada e elegível, orçamento restante, compatibilidade de ferramentas, proteção de dados e risco da troca.
6. **Se elegível**, transferir apenas o contexto mínimo necessário e retomar a tarefa do ponto seguro; conferir ações externas anteriores para evitar duplicidade.
7. Avaliar qualidade, latência, resultado real, custos e eventual retorno ao modelo preferencial quando houver estabilidade.
8. **Se nenhuma alternativa for segura e elegível**, suspender a atividade dependente de IA, manter medidas de proteção independentes, registrar incidente e alertar o responsável humano.
9. Enviar resultados e aprendizados à avaliação contínua e biblioteca de conhecimento, sem promover novos modelos ou procedimentos automaticamente.

## Funcionamento degradado — regra aprovada

Quando todos os modelos elegíveis estiverem indisponíveis ou bloqueados por custo/segurança:
- **Continuam ativos**, quando a infraestrutura permitir, os mecanismos convencionais independentes de IA de monitoramento, validação, autenticação, fila, persistência de leads, rate limiting, alertas e contenção previamente homologada.
- Novas decisões que dependam de análise de IA ficam suspensas, enfileiradas ou escaladas de forma controlada; **não inventar resposta nem executar ação de risco sem modelo e validações**.
- Registrar a perda de capacidade de IA e notificar conforme prioridade; não prometer atendimento humano permanente 24/7 (IN-136).
- Recepção de leads deve continuar conforme as APIs independentes e confirmação durável da IN-124; contingência de modelo **não substitui a contingência das APIs**.
- Preservar os prazos já aprovados de leads: 3 dias de fila normal + 15 dias de falhas; após 18 dias, o payload não entregue poderá ser excluído conforme IN-147, com verificações, avisos e auditoria.

## Exemplos de uso

### Modelo principal atinge limite
O Agente de Integrações encontra bloqueio de quota. O orquestrador consulta alternativas já homologadas, verifica orçamento e capacidade e encaminha a análise sem ampliar acesso. Reenvios ao CRM seguem idempotentes; nenhuma operação é repetida sem conferir estado.

### Modelo retorna resposta incorreta repetidamente
Monitor independente detecta saídas inconsistentes, impede uso da resposta como evidência de sucesso e suspende tarefas de risco. Um substituto pode ser escolhido **somente se a falha não indicar comprometimento que exija contenção geral**.

### Indisponibilidade de todos os fornecedores elegíveis
A análise por IA fica indisponível; mecanismos determinísticos e alertas essenciais continuam ativos no limite de sua infraestrutura. Incidente é aberto e escalado para responsável humano, sem contratação automática de novo fornecedor.

## Indicadores propostos (valores ainda não aprovados)

- Disponibilidade e latência por modelo, fornecedor, agente e operação;
- taxa de falha, qualidade verificada, recuperação bem-sucedida e mudanças automáticas de modelo;
- custo técnico por tarefa, agente, incidente, modelo e período; consumo próximo do limite;
- incidentes de erro repetitivo, fallback malsucedido ou transferência incompleta;
- ações duplicadas impedidas, tarefas suspensas por risco e tempo em operação degradada;
- precisão e estabilidade dos resultados após fallback e retorno ao primário.

## Pendências para detalhamento antes da implantação

1. Catálogo de modelos e fornecedores efetivamente contratados ou a avaliar, com versões e compatibilidade.
2. Quotas e tetos financeiros por agente, modelo, tarefa e período, além de alertas e regras de bloqueio.
3. Política de roteamento, prioridade, fallback/failback, limites de latência, tentativas e *circuit breaker*.
4. Contrato de ferramentas e formatos de saída, validação determinística das ações e verificação independente do sucesso.
5. Regras para transferência de contexto e dados entre fornecedores, análise contratual e LGPD.
6. Armazenamento de identificadores, checkpoints de tarefas e idempotência em operações externas.
7. Rotinas de avaliação, testes de modelos e procedimentos de homologação/desativação.
8. Monitoramento independente, plano de operação degradada sem IA e cobertura de alertas.
9. Segregação dos custos e permissões dos agentes internos Imagine versus agentes contratados pelas empresas clientes do CRM.

**Aprovado:** catálogo de modelos por agente, seleção inteligente, contingência automática para alternativas homologadas, controle de qualidade e custos, continuidade segura de tarefas, aprendizado e operação técnica degradada independente de IA.  
**Pendente:** fornecedores e modelos específicos, quotas, indicadores quantitativos, arquitetura e testes.  
**Programação:** não iniciada.


## Complemento posterior — IN-165 (02/11/2026)

A [IN-165 — Versionamento e Homologação das Instruções dos Agentes Técnicos](IN-165-versionamento-homologacao-instrucoes-agentes.md) aprovou versionamento, testes, controle de publicação, auditoria e rollback das **instruções comportamentais**. Ajustes de baixo risco podem ser automáticos **somente dentro de política e procedimento previamente homologados**. Mudanças relevantes de comportamento exigem homologação humana; mudanças críticas seguem a aprovação humana única aplicável. **Instruções/prompts não concedem ferramentas, permissões ou autorização de execução, tampouco substituem homologação de playbooks ou controles externos da IA.** A retenção das versões de instruções ainda será definida separadamente e não herda automaticamente o prazo de 24 meses aplicável ao conhecimento arquivado da IN-163.


## Complemento aprovado — classes de modelos por tarefa (IA-01, 02/11/2026)

O responsável **aprovou funcionalmente** a classificação dos modelos elegíveis em **três classes: econômica, intermediária e avançada**, com seleção por complexidade, qualidade e custo da tarefa, preservando a política multimodelo e fallback já aprovada nesta IN-157. A [matriz IA-01 de classes por tarefa](IA-01-matriz-classes-modelos-por-tarefa-agentes-tecnicos.md) define exemplos de atividades, escalonamento controlado, uso prioritário de regras determinísticas quando suficientes, verificação de resultados, limites de autonomia e segurança de dados. O nível de classe **não altera permissões do agente, alçada ou aprovação humana necessária**, e modelos externos continuam sujeitos a homologação, orçamento e políticas de dados.

**Permanecem pendentes e não foram implicitamente aprovados:** provedores e modelos específicos, versões, preços, metas numéricas, ordem de preferência/fallback por tarefa, limites e reservas financeiras, contratos de proteção de dados, testes e implantação. **Não iniciar programação ou contratação.**
