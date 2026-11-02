# IN-140 — Avaliação Contínua do Desempenho dos Agentes Técnicos

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 (confirmação "ok").  
**Fase:** planejamento e documentação; **não iniciar programação**.  
**Escopo:** Central de Incidentes Técnicos do Portal Imagine, os sete agentes técnicos especializados e o Agente Supervisor Técnico.
**Referências:** IN-130 (incidentes), IN-131 (autonomia), IN-132 (catálogo de agentes), IN-133 (prioridades), IN-135 (metas internas de assunção), IN-137/IN-138 (aprovações e risco simplificados), IN-139 (biblioteca e aprendizado contínuo).

## Objetivo
Avaliar continuamente qualidade, eficiência, segurança e aprendizado dos agentes técnicos da Imagine, identificando ganhos reais de desempenho, recorrência de falhas e procedimentos de autocorreção que devam ser melhorados ou suspensos.

## Indicadores funcionais aprovados

| Indicador | Finalidade |
|---|---|
| **Taxa de resolução automática** | Medir a proporção de incidentes efetivamente solucionados sem intervenção humana, distinguindo execução automática de mera proposta da IA. |
| **Tempo médio de resolução** | Acompanhar o tempo entre detecção e recuperação confirmada, por especialidade, prioridade e tipo de incidente. |
| **Problemas recorrentes** | Detectar padrões de reincidência e falhas que voltam a ocorrer após encerramento. |
| **Eficácia das soluções** | Avaliar resultados dos playbooks homologados, com confirmações técnicas e contexto de aplicação. |
| **Correções malsucedidas** | Identificar procedimentos ineficazes, falhas repetidas ou resultados adversos; permitir suspensão preventiva da execução automática. |
| **Evolução dos agentes** | Comparar desempenho ao longo do tempo sem mascarar mudanças na complexidade, volume ou criticidade dos incidentes. |

## Regras aprovadas
1. **Registro estruturado:** a Central de Incidentes alimentará indicadores com eventos auditáveis de detecção, atribuição, intervenção, validação, reabertura e encerramento.
2. **Supervisor Técnico:** acompanhará os indicadores, detectará desempenho abaixo do esperado e recomendará ajustes de procedimentos, correções, treinamento operacional/documental e revisões de escopo.
3. **Aprendizado contínuo:** cada incidente e cada resolução confirmada poderão enriquecer a memória técnica e os procedimentos, conforme IN-139; casos malsucedidos também geram lições, claramente identificadas como não confirmadas.
4. **Medição fiel:** diferenciar resolução automática confirmada, resolução com ajuda humana, mitigação temporária, pendência não resolvida e fechamento administrativo. Não declarar sucesso baseado apenas na execução de um comando.
5. **Suspensão preventiva:** se um procedimento apresentar risco, regressão, resultados adversos ou falhas repetidas, sua autorização de execução automática poderá ser suspensa preventivamente, preservando diagnóstico, aviso, correções alternativas permitidas e escalonamento.
6. **Autonomia controlada:** melhoria de desempenho **não cria novas permissões, não autoriza novos playbooks automaticamente e não elimina a aprovação humana única para ações críticas**. Não haverá dupla aprovação humana, conforme IN-137/IN-138 revisadas.
7. **Trilha de auditoria e versionamento:** registrar versões dos procedimentos, motivos de suspensão/reativação, parâmetros de avaliação, agentes e incidentes envolvidos, com acesso por perfis autorizados.
8. **Comparabilidade e qualidade:** métricas deverão ser segmentadas por prioridade, serviço, complexidade, agente e período para evitar conclusões distorcidas por mix de incidentes.
9. **Privacidade:** utilizar dados técnicos minimizados nos painéis e nas memórias, sem expor segredos, tokens, credenciais nem dados pessoais de formulários/clientes desnecessários.
10. **Feedback operacional:** melhorias propostas pelo Supervisor entram em fluxo de homologação e teste, não diretamente em execução automática.

## Fluxo funcional
Detecção e resolução de incidente → validação da recuperação → medição de desempenho → aprendizado documentado → análise de tendências pelo Supervisor → sugestão de melhoria ou suspensão preventiva → teste e homologação de procedimento alterado → liberação controlada com auditoria.

## Exemplos de critérios que NÃO devem ser presumidos como aprovados
- Percentual mínimo obrigatório de resolução automática.
- Limiares exatos para suspender playbooks, quantidade de falhas consecutivas, periodicidade de revisão e metas numéricas por agente.
- Publicação dessas métricas para clientes, ranking de agentes ou aplicação de custos adicionais.
- Retenção de registros detalhados de incidentes ou memórias técnicas: não confundir com a retenção de 90 dias de auditoria das filas de leads (IN-128).

## Pendências de especificação
- Fórmulas dos indicadores, janelas de acompanhamento, métricas de latência, normalização por gravidade e critérios para casos reabertos.
- Políticas objetivas de suspensão/reativação de playbooks e mecanismos de bloqueio independentes da decisão livre do agente.
- Design do painel e relatórios, notificações para desempenho ruim e papéis de validação.
- Processo formal para homologar melhorias derivadas de aprendizados e testes de regressão.
- Retenção de dados, custos e capacidade de armazenamento e análise das métricas.
- Medidas para identificar alterações na infraestrutura e impedir aplicação de solução obsoleta.

**Aprovado:** indicadores acima, acompanhamento pelo Supervisor, aprendizado por resoluções, identificação/suspensão preventiva de procedimentos problemáticos, auditoria e autonomia controlada.
**Pendente:** fórmulas, limiares, cadências, políticas operacionais e implementação.
**Programação:** não iniciada.
