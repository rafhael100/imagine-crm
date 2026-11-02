# IN-160 — Gestão Inteligente de Logs, Métricas e Rastreamento Distribuído

**Status:** APROVADA funcionalmente pelo responsável em 02/11/2026 (resposta "ok").  
**Fase:** exclusivamente planejamento e documentação; **não iniciar programação, coleta em produção, implantação de ferramentas ou contratação de serviços**.  
**Escopo:** Sites Imagine, formulários, APIs principal e de contingência, filas de leads, CRM Imagine SaaS, Portal Administrativo Imagine, n8n, servidores, infraestrutura, sete agentes técnicos especializados e Agente Supervisor Técnico.  
**Referências:** IN-124 a IN-129 (recepção, persistência, filas, diagnóstico e auditoria de leads); IN-130 a IN-136 (incidentes, especialistas, alertas e escalonamento); IN-137 a IN-140 (autonomia, auditoria e aprendizado); IN-142 (custos); IN-143 a IN-146 (continuidade); IN-147 (exclusão de não entregues após 18 dias); IN-148/IN-149 (reconciliação e confirmação efetiva); IN-150/IN-151 (segurança e credenciais); IN-153 (inventário); IN-155 (homologação de procedimentos); IN-156 a IN-159 (saúde dos agentes, modelos, custos e central de observabilidade).

## Objetivo

Estabelecer uma **estrutura unificada de logs, métricas e rastreamento distribuído** para investigar operações entre os serviços Imagine, localizar erros, relacionar eventos de um mesmo fluxo, apoiar diagnósticos de IA e melhorar os procedimentos de recuperação. Essa estrutura integra a **Central Inteligente de Observabilidade da IN-159**: não criará uma central administrativa paralela ou fontes conflitantes de verdade.

O registro de telemetria deve permitir saber **o que aconteceu, onde, quando, qual serviço participou e qual estado foi comprovado**, sem guardar indevidamente conteúdo de formulários, segredos ou dados pessoais.

## Funcionalidades aprovadas

1. **Logs técnicos centralizados:** reunir ou consultar de forma integrada registros de servidores, aplicações, APIs, filas, CRM e automações, com identificação de serviço, evento, horário, gravidade e origem, preservando evidências em suas fontes quando necessário.
2. **Rastreamento ponta a ponta:** correlacionar os passos de uma operação por identificador estável de correlação, independente de mudanças entre API principal, contingência, filas, n8n e CRM, de modo a localizar atrasos, falhas e confirmações.
3. **Correlação de erros:** relacionar registros dos vários componentes e distinguir sintomas em cascata de falhas independentes, com integração à Central de Incidentes Técnicos IN-130 e à análise de dependências IN-153.
4. **Diagnóstico inteligente:** permitir que agentes de IA usem informações técnicas autorizadas para identificar padrões, sugerir causas e selecionar **procedimentos homologados**; conclusões de IA são hipóteses até validação técnica.
5. **Proteção de dados:** omitir ou mascarar senhas, tokens, chaves de API, cookies de sessão, informações pessoais desnecessárias e payloads completos de formulários nos logs, rastros, métricas, alertas e dados usados por modelos.
6. **Retenção diferenciada:** aplicar políticas de guarda, limpeza e minimização **por categoria de registro**, com auditoria de expiração, sem assumir um único prazo para todas as fontes de telemetria.
7. **Otimização de custos:** controlar volumes, níveis de log, amostragem quando aplicável, cardinalidade de métricas, retenção e consumo de armazenamento/consulta, sem eliminar evidências obrigatórias nem causar perda de diagnóstico crítico.
8. **Aprendizado contínuo:** documentar causas verificadas, procedimentos adotados, resultados e recorrências na biblioteca técnica IN-139, sem transferir dados sensíveis brutos à memória dos agentes.

## Classificação dos dados de observabilidade

| Categoria | Finalidade e dados permitidos | Retenção |
|---|---|---|
| Logs operacionais de aplicações/infraestrutura | Eventos técnicos, erros, status, serviço e identificadores não sensíveis | **A definir por serviço e criticidade** |
| Métricas e indicadores agregados | Contadores, latência, disponibilidade, saturação, filas e desempenho | **A definir por indicador e custo** |
| Rastros distribuídos (*traces*) | Relações entre etapas, tempo e estado da operação, IDs de correlação e serviços participantes | **A definir, com amostragem e proteção quando cabíveis** |
| Auditoria técnica de filas de leads | Metadados mínimos de tentativas, confirmações, falhas, expiração e descarte | **90 dias, já aprovado na IN-128** |
| Conteúdo temporário de lead confirmado no CRM | Payload temporário necessário à integração | **24 horas após confirmação da entrega**, conforme IN-128 |
| Conteúdo temporário de lead não entregue | Payload persistido para retentativas/recuperação operacional | **Até 18 dias totais (3+15); exclusão após prazo permitida**, conforme IN-147 |
| Auditorias sensíveis de segurança, mudanças e aprovações | Eventos de controle, identidade autorizada, ações e evidências minimizadas | **A definir por categoria, obrigação e política específica** |

**Importante:** os **90 dias** aplicam-se à **auditoria técnica minimizada das filas de leads** e **não** autorizam reter o conteúdo completo do lead por 90 dias, nem determinam automaticamente a retenção de todos os logs de infraestrutura, segurança ou agentes.

## Fluxo rastreável de leads — exemplo funcional

Quando um visitante envia um formulário:

1. O envio recebe um **ID lógico estável** e um contexto de correlação seguro, conforme IN-148/IN-149.
2. A API principal ou de contingência registra o recebimento técnico; **só pode confirmar sucesso ao visitante após persistência durável**.
3. O processamento em fila registra estados e tentativas, distinguindo *recebido*, *pendente*, *falha* e *confirmado no CRM*.
4. A integração registra o encaminhamento ao CRM usando identificador de idempotência, sem assumir que uma resposta intermediária demonstra entrega.
5. O CRM comprova a persistência/associação final antes que o lead seja contado como **entregue**.
6. Caso ocorra falha, o Agente de Integrações usa o ID de correlação para identificar a etapa e a causa provável, com evidência técnica, permitindo reprocessamento dentro das regras aprovadas.
7. O histórico distingue **tentativas da mesma submissão** de submissões legítimas diferentes, mesmo quando os campos do formulário são iguais.
8. Para lead entregue, o conteúdo temporário é excluído após 24 horas; se **não** for entregue, seguem os 3 dias da fila normal mais 15 dias da fila de falhas, avisos e **permissão de exclusão após completar 18 dias**, com verificação final e auditoria minimizada por 90 dias.
9. A telemetria de rastreamento **não deve servir como cópia informal do payload**, mecanismo de recuperação não autorizado ou motivo para reiniciar prazos de retenção.

## Relação entre logs, métricas e rastros

- **Logs:** descrevem eventos discretos, erros e transições de estado com contexto técnico.
- **Métricas:** quantificam saúde, volume, latência, saturação, taxa de erro e tendência; não devem conter campos de identificação pessoal com alta cardinalidade.
- **Rastros distribuídos:** permitem seguir uma operação entre múltiplos componentes, incluindo mudanças para a API de contingência e operações assíncronas; a continuidade lógica do trace deve sobreviver a filas e tentativas, preservando identidades idempotentes.
- **Auditoria:** comprova ações relevantes e políticas de retenção com proteção e integridade apropriadas; **não é substituída** por logs operacionais descartáveis ou métricas agregadas.
- O protocolo técnico exato de instrumentação, propagação de contexto, correlação e exportação ainda será definido; esta aprovação funcional **não escolhe fornecedor ou padrão específico de implementação**.

## Segurança, segregação e integridade

- Acesso por perfis internos autorizados, com segregação estrita entre empresas/clientes/ambientes; a central não exibirá dados de outra empresa a usuários de um cliente.
- Proibir registro de senhas, tokens, chaves, credenciais, cabeçalhos de autenticação, dados completos de formulários e informações pessoais não necessárias.
- Aplicar mascaramento/minimização **na origem da coleta** e proteção nas etapas de transporte, armazenamento, indexação, pesquisa e uso pelos agentes.
- Garantir que dados provenientes de logs, sites, tickets ou mensagens externas sejam tratados como **dados não confiáveis**, nunca como instruções para executar ações.
- Conter acesso privilegiado, auditar pesquisas/exportações sensíveis e restringir compartilhamento de contexto com modelos de IA e terceiros, segundo políticas e LGPD.
- Diferenciar claramente **observações verificadas**, informação incompleta ou atrasada, hipóteses de diagnóstico e resultados comprovados.
- Evitar que falha do coletor/armazenamento de telemetria bloqueie persistência e recebimento de formulários. A indisponibilidade da interface IN-159 não bloqueia monitoramento e alertas críticos independentes.
- Preservar auditabilidade de descarte e alterações técnicas relevantes conforme as políticas próprias, mesmo que logs operacionais menos importantes sofram amostragem.

## Agentes técnicos e procedimentos

1. Os agentes especializados consultam somente logs/métricas/rastros necessários à sua atividade e conforme permissões.
2. O Agente Supervisor Técnico correlaciona sinais e prioriza diagnósticos, sem acesso irrestrito a dados ou credenciais.
3. A detecção de falha pode produzir sugestão ou disparar **procedimento seguro previamente homologado** conforme IN-155.
4. Uma ação crítica continua exigindo **uma aprovação humana específica**, quando aplicável, sem dupla autorização, conforme IN-137/IN-138.
5. Nenhum diagnóstico de IA autoriza alterar dados, políticas de retenção ou níveis de segurança sem procedimento e permissão válidos.
6. Evidências de resolução alimentarão a biblioteca de conhecimento IN-139 **em formato minimizado e reutilizável**, mantendo distinção entre sucesso verificado, falha e hipótese.

## Otimização de custos e resiliência

- Controlar verbosidade por serviço, taxas de amostragem, frequência de coleta, armazenamento e consultas caras; não eliminar auditabilidade obrigatória ou evidências essenciais para incidentes críticos.
- Distinguir custos previstos, medidos e efetivamente faturados; novas contratações, ferramentas pagas e ampliação de recursos dependem de autorização humana (IN-142/IN-158).
- Considerar retenção curta para dados altamente volumosos quando permitido, sem definir prazos arbitrários neste documento.
- Prever indisponibilidade parcial de coletores, atraso de ingestão, filas de exportação e armazenamento técnico, registrando lacunas de observabilidade em vez de indicar falsamente serviço saudável.
- Para contingência, evitar dependência única entre APIs independentes, processamento de leads, monitoramento e repositório de telemetria.

## Indicadores previstos

- Proporção de operações com IDs de correlação válidos e rastreamento completo ou parcialmente indisponível.
- Latência por etapa e ponta a ponta, divergência entre persistência na API e confirmação de entrega no CRM.
- Volume de erros por serviço, falhas repetidas, pontos de atraso, filas e taxa de reprocessamento.
- Correlação de incidentes e taxa de diagnósticos com causa confirmada.
- Eventos de expiração/descarte de leads e integridade da auditoria técnica de 90 dias.
- Volume e custo de logs/métricas/rastros por serviço, categoria e período.
- Alertas de perda de telemetria, violação de limites e exposição indevida de informação sensível.
- Qualidade de procedimentos autocorretivos derivados de evidências técnicas.

## Pendências para detalhamento antes da implementação

1. Tecnologia e arquitetura de captura/consulta, formatos de evento, instrumentação, propagação de contexto e compatibilidade com APIs, Laravel, n8n, hospedagem e filas.
2. Identificadores de correlação, idempotência, delimitação entre empresas e serviços e tratamento de operações assíncronas com retentativas.
3. Modelo de logs estruturados, métricas e *traces*, nomenclatura e contratos de dados por componente.
4. Matriz **de retenção por categoria**, incluindo logs gerais, métricas, traces, segurança, incidentes, aprovações e auditorias, conforme LGPD e requisitos legais; preservar o prazo já aprovado de 90 dias de auditoria das filas.
5. Estratégia de anonimização, mascaramento, criptografia, controle de acesso e exportação de evidências.
6. Política de amostragem e agregação sem perda de eventos essenciais, custos e dimensionamento.
7. Detecção de indisponibilidade dos coletores, contingência dos pipelines de telemetria e alertas independentes.
8. Integrações com Central de Incidentes, inventário, Agente Supervisor, avaliação contínua, biblioteca e relatórios.
9. Cenários de teste ponta a ponta, incluindo failover/failback, timeout de confirmação, duplicidades, dados incompletos, expiração e descarte.
10. Definição de métricas, limiares, interfaces, retenção de evidências e critérios de qualidade.

**Aprovado:** centralização integrada de logs, métricas e rastreamento; correlação de identificadores ponta a ponta; diagnósticos inteligentes; segurança, minimização e retenção diferenciada; controle de custos e aprendizado contínuo.  
**Preservado:** auditoria técnica minimizada das filas de leads por **90 dias**; payload entregue em 24 horas; envios não entregues sujeitos a descarte após **18 dias totais** sem quarentena adicional.  
**Pendente:** matriz de retenção geral, ferramentas, padrões, orçamento, arquitetura e testes.  
**Programação:** não iniciada.
