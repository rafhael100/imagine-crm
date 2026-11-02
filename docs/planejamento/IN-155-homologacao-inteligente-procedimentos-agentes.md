# IN-155 — Homologação Inteligente de Procedimentos dos Agentes Técnicos

**Status:** APROVADA funcionalmente em 02/11/2026 pelo responsável do projeto (confirmação "ok").  
**Fase:** exclusivamente planejamento e documentação; **não iniciar programação, implantar agentes, executar alterações operacionais ou contratar serviços**.  
**Escopo:** os sete agentes técnicos especializados, Agente Supervisor Técnico, Biblioteca Inteligente de Autocorreções e Central de Incidentes Técnicos da Imagine.  
**Referências:** IN-130 a IN-132 (incidentes, agentes e autonomia), IN-137/IN-138 (uma aprovação humana para intervenções críticas e matriz de risco), IN-139 (aprendizado e biblioteca), IN-140 (avaliação contínua), IN-141/IN-142 (prevenção e capacidade), IN-143 a IN-146 (continuidade e recuperação), IN-151/IN-152 (segredos e atualizações), IN-153 (inventário) e IN-154 (mudanças técnicas).

## Objetivo e diretriz aprovada

Transformar soluções documentadas a partir dos incidentes e recuperações anteriores em **procedimentos técnicos versionados, testados, homologados e reutilizáveis** (*playbooks*). **Aprovar uma vez cada procedimento seguro em um escopo definido, e não exigir nova aprovação humana para cada execução**, desde que as condições autorizadas permaneçam atendidas.

A homologação de um playbook **não concede acesso irrestrito**, não altera a matriz de risco e **não dispensa a autorização humana única de intervenções críticas** exigida pelas IN-137/IN-138.

## Capacidades aprovadas

1. **Identificação de soluções recorrentes:** agentes registram sucessos, tentativas mal sucedidas e causas demonstradas em incidentes, e identificam padrões reutilizáveis.
2. **Documentação automática:** a IA propõe passo a passo técnico estruturado, pré-condições, limites, verificações, riscos, dependências e procedimento de recuperação, referenciando incidentes e evidências de origem.
3. **Testes em homologação:** testar os procedimentos preferencialmente em ambiente isolado, com dados sintéticos, verificação de resultados e condições de falha; procedimentos perigosos não são testados em produção automaticamente.
4. **Análise de risco:** classificar risco **da ação** independentemente da prioridade do incidente; estimar impacto por ativo, serviço, ambiente, cliente, reversibilidade, dependências, dados e custo.
5. **Homologação humana simplificada:** um responsável habilitado poderá **homologar o procedimento reutilizável para baixo/moderado risco e escopo delimitado**, após verificar testes e controles. **Não exigir dupla aprovação.**
6. **Execução automática posterior:** o agente poderá reutilizar sem nova aprovação individual somente playbooks de **baixo risco ou moderado seguro, já homologados, ativados e expressamente permitidos** para aquele ambiente/serviço, sob verificação determinística das condições de execução.
7. **Avaliação contínua:** medir eficácia, regressões, incidentes associados, repetições e efeitos colaterais, suspendendo procedimentos problemáticos até revisão.
8. **Aprendizado:** atualizações e novos procedimentos podem ser **sugeridos automaticamente** a partir de resultados, mas **não entram automaticamente em execução** sem nova homologação da versão alterada.
9. **Coordenação:** o Agente Supervisor Técnico correlaciona evidências, orienta a revisão e acompanha métricas, mas **não se autoatribui autoridade para aprovar novas ações**.

## Ciclo de vida do procedimento

1. **Observado:** incidente/solução registrada com evidência e contexto.
2. **Rascunho sugerido:** agente propõe o procedimento e seus requisitos.
3. **Em testes:** execução controlada somente em ambiente e permissões apropriados; simular falhas, limites e rollback quando tecnicamente possível.
4. **Aguardando homologação:** análise da versão proposta, eficácia, riscos, dependências e ações permitidas.
5. **Homologado e ativo:** após aprovação humana de responsável habilitado e publicação controlada da **versão exata**, elegível a execução automática se for seguro e permitido.
6. **Suspenso:** impedir novas execuções automáticas em caso de falhas, mudanças de ambiente, risco ou revogação; preservar auditoria.
7. **Revisão/substituição:** testar e homologar nova versão antes de habilitar; manter rastreabilidade da anterior.
8. **Revogado/arquivado:** impossibilitar novas execuções e conservar histórico conforme política de retenção ainda a definir.

A execução só é permitida quando coexistirem **procedimento homologado + versão ativa + permissões do agente + serviço e ambiente autorizados + condições de segurança atuais + limites operacionais e financeiros respeitados**.

## Conteúdo mínimo de um playbook

- Identificador estável, nome, descrição, categoria, proprietário, versão e origem na biblioteca de incidentes;
- problema e sintomas aos quais se aplica, causa conhecida, sinais que **não** devem dispará-lo;
- ativos, empresas, serviços, ambientes e recursos autorizados, referenciados ao inventário IN-153;
- nível de risco, versão e fundamento da política, identidade/permissões técnicas e limites;
- pré-condições verificáveis por mecanismo determinístico, entradas/saídas e sequência das etapas;
- limites de tentativas, tempo, concorrência, frequência, alteração de recursos e orçamento;
- testes prévios, resultados, evidências, critérios de sucesso e de interrupção;
- rollback ou plano de contenção quando possível e dependências críticas;
- homologador humano, data, escopo aprovado, data/revisão de validade quando aplicável;
- observações sobre dados pessoais, segredos, indisponibilidades e impacto em clientes;
- histórico de execuções, falhas, suspensões, reativações e versões.

## Distinção fundamental: homologação versus aprovação de ação crítica

**Aprovação de reutilização automática:** permite executar repetidamente uma **ação segura classificada como baixo/moderado risco** dentro do perímetro especificado, sem solicitar uma nova aprovação por ocorrência.

**Aprovação para intervenção crítica:** mesmo que um procedimento de alto risco esteja documentado e testado, **uma execução crítica específica ainda requer uma única aprovação humana autorizada**, com contexto, alvo, impacto e validade delimitados, conforme IN-137/IN-138. Homologação não equivale a autorização genérica para operações críticas.

**Sem burocracia adicional:** não criar comissões, dupla aprovação ou homologação manual de cada repetição de uma ação segura já autorizada.

## Fluxo de atuação de um agente

Detecção do erro → consulta à biblioteca IN-139 → busca de procedimento homologado compatível → conferência de inventário atual IN-153, risco e permissões → verificação automática das pré-condições → **execução imediata quando baixo/moderado risco e homologado**, ou **pedido de uma aprovação humana se crítico** → validação do serviço e das dependências → registro do resultado, lições e métricas.

Se a verificação falhar, se houver conflito de versões, risco elevado, ausência de permissão ou procedimento suspenso, **não executar**. Registrar o diagnóstico, realizar apenas alternativas seguras autorizadas e escalar a necessidade.

## Exemplos ilustrativos

- **Falha recorrente de processo de integração:** o primeiro incidente produz uma solução documentada. Após testes, homologação humana e autorização de escopo, o Agente de Integrações pode executar automaticamente o reinício controlado em futuras ocorrências com as mesmas condições, testar o fluxo de leads e registrar o resultado.
- **Workflow n8n com falha recuperável:** o Agente de Automações pode reprocessar apenas quando a operação for idempotente, limitada e homologada, evitando efeitos duplicados em integrações.
- **Mudança crítica de servidor, DNS, credencial ou dado:** o agente pode preparar/verificar plano reutilizável, mas a intervenção específica de risco alto/crítico continua exigindo a autorização humana aplicável; não autorizar execução irrestrita só pela existência de playbook.
- **Procedimento antes eficaz passa a falhar:** o Supervisor e a avaliação contínua IN-140 sinalizam regressão, bloqueiam nova execução automática do procedimento afetado e propõem nova versão para testes e homologação.

## Controles de segurança e governança

- Homologar por **ação, ambiente, serviço e versão**, não por uma descrição vaga; configuração observada não deve divergir do estado validado para aquela execução.
- Não deixar o próprio modelo alterar livremente o risco, desabilitar limites, conceder permissões ou transformar uma sugestão em procedimento ativo.
- Implementar checagens independentes da IA para permissões e pré-condições, evitando execução de comandos ditados por logs, tickets, sites ou mensagens externas.
- Preservar minimização de dados e isolamento de clientes: não incluir tokens, chaves, senhas ou payloads pessoais em playbooks ou memórias.
- Prevenir loops de autocorreção, alterações concorrentes, mudanças em cascata e regressões; suspender procedimento quando os sinais ultrapassarem limites definidos.
- Não autorizar custos adicionais, contratação de serviços ou provisionamento tarifado apenas porque o procedimento foi homologado (IN-142).
- Medidas de contingência não podem afetar simultaneamente os dois caminhos independentes de recepção de formulários nem violar integridade e idempotência de leads.
- Preservar as políticas já aprovadas para leads: **3 dias de tentativas normais + 15 dias na fila de falhas**, limpeza após entrega confirmada em 24 horas e permissão para excluir payload não entregue após completar 18 dias (IN-147), com auditoria.
- Auditar toda execução: contexto, playbook, versão, permissões, risco, horário, agente executor, resultado, verificações, rollback, incidentes e motivo de eventual suspensão.

## Indicadores para o acompanhamento

- Procedimentos sugeridos, testados, homologados, ativos, suspensos, revogados e reprovados;
- taxa de sucesso validado por playbook, agente, versão e ambiente;
- tempo poupado entre diagnóstico e recuperação em incidentes semelhantes;
- quantidade de execuções seguras automáticas versus solicitações de autorização;
- falsas correspondências, efeitos adversos, recorrências, rollbacks e falhas de pré-condição;
- tempo de homologação, revisões de versão e procedimentos obsoletos.

## Especificações ainda pendentes antes da implementação

1. Catálogo inicial de playbooks por especialista, risco, escopo e permissões.
2. Modelo de versionamento, identidade, assinatura/integridade, homologação, publicação, suspensão e revogação de versões.
3. Matriz objetiva de riscos e testes obrigatórios por categoria de operação; pré-condições checadas independentemente da IA.
4. Processo de aprovação humana **única** de novos playbooks e mecanismo de autorização humana única por intervenção crítica quando necessário.
5. Ambientes de homologação, testes sintéticos, métricas de regressão, critérios de validação e rollback.
6. Regras de execução concorrente, limites de tentativas, tempo, cotas e custo.
7. Retenção de evidências de testes e histórico de execução, anonimização e isolamento multiempresa.
8. Integração com inventário, incidentes, mudanças técnicas, observabilidade, aprendizado e avaliações dos agentes.
9. Tratamento de playbook cujo ambiente, versão ou dependência mudaram após a homologação.

**Aprovado:** identificação de soluções, criação assistida por IA de procedimentos, testes em homologação, análise de risco, **homologação por um responsável**, **reutilização automática de procedimentos seguros sem aprovação a cada uso**, avaliação contínua e aprendizado.  
**Ressalva permanente:** ações críticas exigem autorização humana única por intervenção conforme IN-137/IN-138; homologar não é conceder autonomia crítica irrestrita.  
**Pendente:** especificações técnicas, controles quantitativos, ambientes e implementação.  
**Programação:** não iniciada.
