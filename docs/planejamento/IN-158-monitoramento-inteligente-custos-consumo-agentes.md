# IN-158 — Monitoramento Inteligente de Custos e Consumo dos Agentes Técnicos

**Status:** APROVADA funcionalmente pelo responsável do projeto em 02/11/2026 (resposta "ok").  
**Fase:** exclusivamente planejamento e documentação; **não autoriza programação, alteração de configurações reais de cobrança, compras, expansão de recursos ou contratação de fornecedores**.  
**Escopo:** custos internos dos sete agentes técnicos especializados e do Agente Supervisor Técnico Imagine, consumo de modelos, ferramentas e infraestrutura diretamente atribuível a essas operações.  
**Referências:** IN-130 a IN-132 (incidentes e agentes), IN-137/IN-138 (autonomia e aprovação humana única), IN-139/IN-140 (aprendizado e avaliação), IN-142 (capacidade e veto de gastos sem autorização), IN-153 (inventário), IN-155 (homologação de playbooks), IN-156 (autodiagnóstico) e IN-157 (gestão de modelos, fallback e limites financeiros).

## Objetivo

Implantar conceitualmente uma **Central Administrativa de Custos e Consumo dos Agentes Técnicos** para acompanhar despesas, antecipar excessos, escolher modelos adequados ao custo/desempenho e impedir ultrapassagem de limites financeiros previamente autorizados, mantendo a qualidade da operação, a segurança e a separação entre custos internos da Imagine e franquias/créditos de IA contratados por empresas clientes do CRM.

## Funcionalidades aprovadas

1. **Painel central de consumo:** exibir uso e custo estimado/confirmado por agente, modelo, fornecedor, operação, período e centro de custo; distinguir custos internos, cotas e despesas faturadas.
2. **Orçamento mensal e limites:** permitir que a administração defina teto de gasto interno por mês, por agente/modelo quando adequado, limites de operação e reserva controlada para funções críticas, conforme orçamento aprovado. **Valores e distribuição não foram definidos nem aprovados nesta IN**.
3. **Alertas inteligentes:** detectar aproximação dos tetos, mudanças relevantes de preço, gastos anormais, falhas de contabilização, consumo repetitivo e anomalias decorrentes de loops ou tentativas excessivas.
4. **Otimização automática:** priorizar, quando eficaz e permitido, modelos mais econômicos ou estratégias que reduzam consumo, respeitando capacidade, qualidade, requisitos de privacidade, permissões e restrições aprovadas na IN-157.
5. **Previsão de custos:** estimar gasto restante até o fim do mês com base em consumo observado, volume de tarefas e tendências, mostrando faixa de incerteza e distinguindo estimativas de valores faturados.
6. **Bloqueio preventivo:** impedir novas tarefas/iterações que ultrapassariam limites aprovados, preferindo conter o consumo antes de exceder o teto; respeitar operações técnicas independentes de IA e o protocolo de alertas.
7. **Relatórios e comparação:** apresentar consumo, eficiência, taxas de sucesso/recuperação e economia estimada, destacando custo por resultado verificado e não apenas custo por requisição.
8. **Supervisão responsável:** o Agente Supervisor Técnico poderá propor e executar **otimizações seguras já homologadas dentro do orçamento e das permissões vigentes**, sem alterar limites, contratar fornecedores ou reclassificar custos unilateralmente.

## Segregação financeira obrigatória

- **Custos internos da Imagine:** modelos e ferramentas usados para operação técnica de sites, APIs, infraestrutura, incidentes e Supervisor Técnico, custeados e limitados pelo orçamento interno designado.
- **Créditos e franquias dos clientes do CRM Imagine:** consumo comercial de agentes contratados por cada empresa, com regras de franquia, pacotes adicionais, cobrança e isolamento próprios, já tratados no planejamento do CRM.
- **Não transferir cobranças entre os dois contextos:** o custo de operação dos agentes técnicos internos não deve consumir créditos de clientes, e nenhum crédito de uma empresa pode financiar uso de outra ou consumo interno, salvo contratação ou regra comercial futura explicitamente aprovada.
- Relatórios internos poderão agregar dados de custo por infraestrutura/cliente para dimensionamento, sem revelar dados de outros clientes ou alterar cobrança contratada.

## Princípios de contabilização e qualidade dos dados

- Rastrear IDs de tarefa, incidente, agente, modelo, provedor, versão e categoria da operação.
- Distinguir consumo medido (ex.: tokens/operações), custo **estimado** por tabela de preços/versionamento, valores **consolidados pelo fornecedor** e gastos efetivamente **faturados**; evitar afirmar igualdade quando houver atraso, descontos, impostos, câmbio ou arredondamentos.
- Evitar dupla contabilização de tarefas reprocessadas, tentativas, fallbacks e operações compartilhadas, sem esconder os custos reais das múltiplas chamadas.
- Guardar preços e premissas de cálculo com vigência, fonte, unidade e moeda de referência; mudanças de tarifas exigem atualização de parâmetros, não podem passar despercebidas.
- Custo variável com moeda estrangeira deverá ter tratamento de conversão, tributos e taxas definido antes da implantação; **não estabelecer câmbio ou tributos fictícios**.
- Registros de consumo técnico devem ser minimizados, auditados, segregados e protegidos; custos não exigem armazenamento de prompts completos ou dados pessoais de leads.

## Motor de orçamento e bloqueio

1. **A administração aprova tetos**, centros de custo, autorizações de fornecedores/modelos e, quando aplicável, reserva financeira para serviços essenciais.
2. O orquestrador verifica **antes da requisição** o orçamento disponível, política de custo, permissões, adequação do modelo e riscos de loops/tentativas.
3. Se houver opção homologada de custo menor com qualidade adequada, poderá selecioná-la automaticamente, conforme IN-157.
4. Em caso de previsão de estouro, registrar alerta e, quando necessário, aplicar bloqueio preventivo ou limitar tentativas de tarefas consumidoras de IA. **Não elevar teto nem contratar plano adicional automaticamente**.
5. Funções críticas **independentes de IA** — recepção de leads, persistência nas APIs, regras determinísticas de segurança, processamento de filas previamente projetado e alertas essenciais — não devem parar somente porque foi atingido o limite de IA.
6. Reserva para tarefas de IA críticas, se adotada, deverá ser **explicitamente aprovada e fazer parte do orçamento**, não constituindo autorização implícita para exceder o teto.
7. Para aumentar o orçamento, desbloquear gasto extraordinário ou adquirir recurso novo, exigir **uma autorização humana** específica e auditada; não criar fluxo de dupla aprovação.
8. O sistema deve registrar decisões de bloqueio, reencaminhamento, custos evitados estimados e eventuais tarefas suspensas ou em atraso.

## Alertas e anomalias

- Alertas proporcionais para aproximação de limites, picos abruptos, mudança de padrão, aumentos inesperados de preço, tarefas sem progresso e repetição excessiva.
- Evitar falsos positivos e tempestade de notificações, correlacionando eventos e gerando incidentes quando pertinente.
- O agente que consome recursos **não pode ocultar seu próprio gasto**, desligar monitoramento, resetar contador, modificar orçamento nem retirar rastros de auditoria.
- Em falha de medição ou consulta ao fornecedor, trabalhar com estimativas conservadoras, indicar incerteza e limitar novas despesas conforme política aprovada; não presumir custo zero.

## Exemplo ilustrativo

O Agente de Integrações passa a consumir mais recursos ao investigar uma falha. A Central identifica aumento de chamadas, verifica se há um loop, interrompe tentativas excessivas e apresenta um modelo alternativo já autorizado, de menor custo e capacidade suficiente. Se o orçamento não permitir prosseguir, suspende a análise dependente de IA e alerta o responsável, **sem bloquear a API independente de recepção de leads nem o mecanismo determinístico de fila**. O Supervisor registra os resultados para aperfeiçoar procedimentos.

## Interações com outras decisões

- **IN-157:** fallback automático apenas entre modelos homologados, elegíveis em segurança, qualidade e orçamento; não contratar alternativa por conta própria.
- **IN-156:** autodiagnóstico de loops e suspensões por consumo anormal.
- **IN-155:** otimizações executáveis automaticamente devem ser seguras e homologadas; procedimentos novos exigem homologação.
- **IN-142:** nenhuma expansão paga de infraestrutura ou custo operacional sem aprovação humana.
- **IN-137/IN-138:** uma autorização humana quando necessária; nunca dupla aprovação.
- **IN-147/IN-149:** custos de IA não alteram os prazos de integração de leads nem transformam entregas incertas em confirmadas.

## Indicadores propostos

- Consumo e custos internos por agente/modelo/fornecedor, período e tipo de tarefa.
- Custo estimado x consolidado x faturado; divergências e ajustes.
- Percentual do orçamento utilizado e previsão de fechamento mensal.
- Custo por incidente resolvido, procedimento validado e recuperação bem-sucedida.
- Economia estimada decorrente de roteamento inteligente; taxa de qualidade após otimização.
- Número de alertas, bloqueios preventivos, tentativas excessivas e ocorrências de consumo anormal.
- Tempo/quantidade de tarefas de IA suspensas por orçamento e impacto operacional registrado.

## Pontos ainda pendentes antes da implementação

1. **Valores monetários concretos:** orçamento mensal total, limites individuais por agente, modelo, fornecedor, operações e eventual reserva de contingência.
2. Regras e percentuais de alertas, definição de período orçamentário, moedas, impostos e conversão.
3. Contratos reais, tabelas de preços, formatos de telemetria e conciliação com extratos/faturas de provedores.
4. Política de bloqueio, desbloqueio, emergência e aprovação de gastos adicionais.
5. Métricas de qualidade, latência e custo para seleção de modelos, com testes e limites de degradação.
6. Modelo de dados de consumo, atribuição de custos compartilhados e segregação entre uso interno/empresas.
7. Retenção, auditoria, privacidade, permissões e interfaces de relatórios.
8. Contingência quando o serviço de contabilização estiver indisponível, sem abrir caminho para consumo ilimitado.
9. Integração com Central de Incidentes, Agente Supervisor, inventário, modelos, agentes, orçamento e faturamento.

**Aprovado:** painel, orçamento mensal configurável, alertas, otimização automática homologada, previsão de custos, bloqueio preventivo, relatórios e separação de custos técnicos internos e créditos dos clientes.  
**Pendente:** valores, limiares, regras quantitativas, provedores, arquitetura e testes.  
**Programação:** não iniciada.
