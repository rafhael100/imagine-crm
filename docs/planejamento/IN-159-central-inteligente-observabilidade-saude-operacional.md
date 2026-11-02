# IN-159 — Central Inteligente de Observabilidade e Saúde Operacional

**Status:** APROVADA funcionalmente pelo responsável do projeto em 02/11/2026 (resposta "ok").  
**Fase:** exclusivamente planejamento e documentação; **não iniciar programação, instalação de coletores, intervenção em servidores nem contratações**.  
**Escopo:** Portal Administrativo Imagine, Sites Imagine, CRM Imagine SaaS, APIs principal e de contingência, filas e processadores de leads, n8n, servidores, bases de dados, sete agentes técnicos especializados, Agente Supervisor Técnico, incidentes e custos internos de IA.  
**Referências:** IN-124 a IN-129 (APIs, filas e diagnóstico de leads); IN-130 a IN-136 (Central de Incidentes, agentes, priorização, comunicação, metas e plantão); IN-137 a IN-140 (aprovações, riscos, aprendizagem e avaliação); IN-141 a IN-146 (prevenção, capacidade, continuidade, testes e contingência); IN-147 a IN-149 (retenção e entrega de leads); IN-150 a IN-155 (segurança, credenciais, vulnerabilidades, inventário, mudanças e homologação); IN-156 (saúde dos agentes); IN-157 (modelos de IA e contingência); IN-158 (custos e consumo de IA).

## Objetivo

Criar uma **Central Inteligente de Observabilidade e Saúde Operacional**, acessível por funcionários autorizados no Portal Administrativo Imagine, que ofereça visão consolidada e atualizável da operação, sem duplicar a lógica de sistemas já previstos. A central deverá **agregar telemetria, diagnósticos, indicadores, incidentes, comportamento dos agentes e custos**, facilitando a detecção, a investigação, a correção autorizada e a prevenção de falhas.

**Regra de continuidade fundamental:** a indisponibilidade da interface administrativa **não pode impedir** o monitoramento independente, a operação dos serviços essenciais, o recebimento de formulários nem a transmissão de alertas críticos por caminhos adequados.

## Funcionalidades aprovadas

1. **Painel operacional centralizado:** apresentar situação geral por domínio técnico, criticidade, ambiente e período: saudável, degradado, indisponível, desconhecido ou em manutenção, com origem da informação e instante da última aferição.
2. **Monitoramento de serviços:** acompanhar sites, servidores, APIs, CRM, processamento de filas, integrações, n8n, dependências críticas e bancos, respeitando permissões e estados efetivamente observados do inventário IN-153.
3. **Saúde dos agentes:** indicar disponibilidade, execução de tarefas, falhas, recuperações, suspensões, fallback de modelos e necessidade de intervenção humana, aproveitando a IN-156/IN-157.
4. **Indicadores de desempenho:** exibir disponibilidade observada, erros, latência, saturação, backlog, tentativas de recuperação e tempo de resposta, sem inventar métricas que não foram instrumentadas.
5. **Monitoramento de leads:** acompanhar submissões recebidas e persistidas, encaminhamento, entrega **efetivamente confirmada** no CRM, divergências, duplicações impedidas, falhas de integração, filas e expirações.
6. **Diagnóstico inteligente e correlação:** relacionar sinais em diferentes serviços e dependências, agrupar ocorrências da mesma causa provável, sugerir priorização e apresentar evidências, sem confundir hipótese de IA com causa comprovada.
7. **Visão financeira:** integrar indicadores de consumo, orçamento, projeção e anomalias da **IN-158**, com segregação entre operação técnica interna Imagine e créditos de IA dos clientes do CRM.
8. **Relatórios operacionais e executivos:** resumir estabilidade, incidentes, tendências, capacidade, correções automáticas, escalonamentos humanos, resultados validados e oportunidades de melhoria, com período e fonte explícitos.
9. **Integração, não duplicação:** exibir e vincular dados da Central de Incidentes (IN-130), inventário e dependências (IN-153), biblioteca de autocorreções (IN-139/IN-155), gestão de modelos (IN-157) e custos (IN-158), respeitando a autoridade e a retenção próprias de cada subsistema.

## Regras de interpretação da saúde operacional

- **Saudável** somente quando verificações recentes e adequadas confirmarem o serviço; sem medição, mostrar **desconhecido/telemetria ausente**, não saudável por presunção.
- **Degradado** quando o serviço ainda estiver funcionando, mas com falhas, latência, filas, perda de capacidade ou comportamento anormal que prejudique requisitos definidos.
- **Indisponível** conforme verificação apropriada à função; por exemplo, sucesso de HTTP de uma API não prova que a mensagem foi persistida duravelmente, nem que o CRM a recebeu.
- **Em manutenção** não deve silenciar alertas graves indevidamente, nem mascarar falhas de outro componente.
- Apresentar **última verificação, origem da telemetria, qualidade/atraso da informação** e eventual incerteza.
- Agregações por serviço, cliente e ambiente devem evitar contabilização dupla de incidentes, eventos de failover e tentativas do mesmo lead.

## Visões funcionais da central

### Visão executiva
- Saúde geral dos principais serviços Imagine, situação de incidentes críticos/altos, disponibilidade aferida, serviços degradados, backlog de leads e evolução dos custos internos dos agentes.
- Síntese de ações concluídas automaticamente versus pendentes de autorização humana, com resultados reais verificados.
- Tendências e riscos prioritários apresentados de forma compreensível, sem expor segredos ou dados pessoais.

### Visão técnica
- Filtros por ambiente, serviço, categoria, severidade, período e incidente.
- Consulta ao mapa de dependências, versões e responsáveis conforme IN-153.
- Eventos e indicadores associados a incidentes, playbooks, mudanças, verificações de saúde e validações posteriores.
- Diagnósticos de erro, fluxo de recuperação e alertas, com controles de acesso por função.

### Visão de integrações e leads
- Distinguir **recebimento durável pela API** de **entrega confirmada pelo CRM**, conforme IN-124/IN-148/IN-149.
- Mostrar fila normal (até 3 dias), fila de falhas (+15 dias; total 18), avisos antes de expirar, expirações e exclusões autorizadas na IN-147.
- **Após completar 18 dias sem entrega confirmada**, permitir exclusão automática do payload temporário com verificação final, alerta crítico e auditoria, sem quarentena adicional.
- **Não expor conteúdos completos de formulários** em indicadores/telemetria da central. Histórico técnico minimizado segue retenção de 90 dias da IN-128, distinta da retenção de outros registros de observabilidade que ainda será definida.

### Visão dos agentes e dos modelos
- Exibir funções, estado e tarefas dos sete especialistas e do Supervisor Técnico.
- Correlacionar incidentes, tarefa, modelo homologado utilizado, fallback, bloqueios de risco e consumos.
- A indisponibilidade do Supervisor é detectada por **monitoramento independente**, sem depender da própria IA para sinalizar sua falha (IN-156).

### Visão financeira
- Exibir consumo interno dos agentes técnicos por período e previsão de custos, respeitando limites aprovados.
- **Não misturar** orçamento técnico da Imagine com franquias/créditos contratados por empresas clientes do CRM.
- Falha do painel de custos não autoriza consumo ilimitado nem contratação automática; controle orçamentário deve operar em mecanismo apropriado (IN-158).

## Correlação inteligente e abertura de incidentes

1. Receber sinais verificáveis de coletores, testes de saúde, ferramentas e serviços existentes.
2. Normalizar categoria, serviço, horário, ambiente, identificadores técnicos e estado da medição.
3. Correlacionar eventos conforme dependências e contexto, com algoritmos e regras determinísticas e apoio opcional de IA.
4. **Agrupar ocorrências relacionadas** e vinculá-las ao mesmo incidente quando apropriado, sem ocultar evidências independentes nem perder o histórico.
5. Identificar causa **provável** e grau de confiança, diferenciando-a da causa verificada após análise e testes.
6. Encaminhar eventos à Central de Incidentes, aos agentes responsáveis e ao sistema de alertas e aprovações, conforme regras já aprovadas.
7. Acompanhar solução, verificar indicadores reais de recuperação e associar aprendizado, evitando marcar incidente como resolvido somente porque o agente declarou sucesso.

## Disponibilidade, contingência e alertas independentes

- **O painel é uma interface de visualização**, não a única fonte da saúde operacional nem o único mecanismo de emissão de alertas.
- Monitoramento e alertas críticos devem utilizar componentes adequadamente independentes, conforme IN-130/IN-134/IN-136 e IN-156.
- Queda do Portal Imagine **não deve interromper** APIs de formulários, persistência independente, integração com CRM, regras determinísticas de filas ou alertas essenciais.
- Uma falha compartilhada em coletores, rede, armazenamento de métricas ou autenticação deverá ser identificada como risco de observabilidade, em vez de indicar falsamente que todos os serviços estão saudáveis.
- Mecanismos sem IA continuam verificando falhas e aplicando proteções seguras já autorizadas mesmo quando os modelos estiverem indisponíveis (IN-157).
- Evitar tempestade de alertas com agrupamento, deduplicação e escalonamento, sem atrasar a notificação de incidentes críticos.

## Governança, autorização e segurança

- Acesso por perfis internos autorizados no Portal Imagine; usuários de empresas clientes **não** recebem acesso à topologia, custos internos ou dados operacionais de outras empresas.
- Dados pessoais, tokens, senhas, chaves de API e conteúdo de formulários não devem aparecer em logs ou relatórios sem necessidade e autorização específica.
- Monitoramento e diagnóstico não significam permissão para alterar servidores, credenciais ou produção.
- Autocorreções de baixo/moderado risco podem ser executadas por **playbooks seguros previamente homologados**, conforme IN-138/IN-155.
- Ações críticas continuam exigindo **uma aprovação humana específica**, quando necessária, sem dupla aprovação.
- **Nenhuma expansão paga**, contratação de ferramenta, provedor de observabilidade ou aumento de gasto é automática sem autorização humana (IN-142/IN-158).
- Registrar consultas sensíveis, intervenções, alterações de configuração, origem de dados, revisões de diagnósticos e mudanças de classificação, conforme política de auditoria a detalhar.

## Indicadores e relatórios previstos

- Disponibilidade e integridade de medições por serviço, última verificação e cobertura de monitoramento.
- Erros, latência, volume, saturação e tendências de capacidade por componente.
- Incidentes abertos/resolvidos, criticidade, recorrência, tempo de detecção e recuperação, respeitando a distinção entre assumir e resolver definida na IN-135.
- Leads recebidos, persistidos, entregues com confirmação, em falha, reprocessados e expirados/excluídos, sem contar expiração como entrega.
- Agentes saudáveis/degradados/suspensos, loops bloqueados, recuperações, erros e sucesso validado.
- Gastos internos com modelos, economia estimada, alertas financeiros e orçamento disponível (IN-158).
- Diferença entre resoluções automáticas homologadas e casos que exigiram autorização ou atuação humana.

## Pendências para detalhamento antes da implementação

1. Tecnologias e arquitetura de telemetria, coletores, armazenamento, painéis e canais independentes de alerta; evitar novos pontos únicos de falha.
2. Catálogo de indicadores, fontes confiáveis, medição de disponibilidade e critérios objetivos para saudável/degradado/indisponível/desconhecido.
3. Modelo de correlação de incidentes e deduplicação, com evidências, proveniência, hipóteses e causa confirmada.
4. Contratos de integração entre Central de Incidentes, inventário, agentes, custos e sistemas existentes.
5. Retenção e custo de logs, métricas e trilhas de auditoria, inclusive minimização e LGPD; não presumir que todos os logs tenham os 90 dias aprovados para auditoria de filas de leads.
6. Política de acesso por perfil, segmentação de dados, proteção de segredos e auditoria de consultas/ações.
7. Layouts de visão executiva e técnica, filtros, agrupamentos e periodicidade de relatórios.
8. Cenários e testes de falha do painel, perda de telemetria, indisponibilidade de IA e falha simultânea de serviços.
9. Orçamento de ferramentas, infraestrutura de monitoramento, ingestão de telemetria e custos recorrentes (sem despesas automáticas).

**Aprovado:** central administrativa de saúde dos serviços e agentes, indicadores técnicos, monitoramento das filas e entregas de leads, correlação de incidentes, visão de custos internos, relatórios operacionais/executivos e funcionamento independente dos alertas e serviços essenciais.  
**Pendente:** ferramentas, arquitetura de observabilidade, indicadores quantitativos, retenção, interfaces, dimensionamento e testes.  
**Programação:** não iniciada.
