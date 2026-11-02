# IN-145 — Testes Inteligentes de Continuidade e Recuperação

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 (confirmação "ok").  
**Etapa:** planejamento e documentação; **não iniciar programação, testes reais, provisionamento ou contratação**.  
**Escopo:** infraestrutura Imagine, sites, APIs de formulários e contingência, filas de leads, CRM Imagine, backups, automações n8n e Central de Incidentes Técnicos.  
**Referências:** IN-101 (backups dos sites), IN-124 (API principal e contingência com persistência independente), IN-126 a IN-129 (filas, retenção, diagnóstico e recuperação de leads), IN-130 a IN-142 (incidentes, agentes, autonomia, alertas, aprendizado e prevenção), IN-143 (continuidade e recuperação de desastres), IN-144 (prioridade de recuperação e RTO/RPO).

## Objetivo

Planejar **testes periódicos, controlados e auditáveis** dos mecanismos de continuidade e recuperação da Imagine, para verificar se contingências, backups, filas e procedimentos de recuperação **funcionam de fato**. Os resultados devem alimentar o aprendizado contínuo dos agentes e evidenciar limitações reais antes de prometer disponibilidade, recuperação ou ausência de perda de dados.

## Capacidades aprovadas

1. **Testes das APIs:** validar em cenário controlado a disponibilidade da API principal, da API de contingência e a aptidão desta última para persistir submissões, com isolamento entre servidores e, preferencialmente, provedores (IN-124).
2. **Testes de backup:** verificar integridade e legibilidade de backups e promover restaurações de teste em **ambientes isolados**, checando consistência e possibilidade real de recuperação.
3. **Simulações de falhas:** exercitar indisponibilidade de serviços e dependências sem comprometer clientes ou serviços produtivos; preferir simuladores, homologação ou testes não destrutivos.
4. **Testes de filas:** demonstrar retomada de processamento, consistência, idempotência e reconciliação de submissões sintéticas, sem perder ou duplicar leads e sem violar os prazos de retenção.
5. **Testes de recuperação:** medir tempos observados de detecção, comutação, restauração, validação e normalização; esses números servirão para **dimensionar futuramente RTO e RPO por serviço**, ainda não definidos na IN-144.
6. **Aprendizado contínuo dos agentes:** registrar sucessos, falhas, hipóteses não confirmadas, diagnóstico, procedimentos utilizados, impactos e melhorias propostas para a biblioteca homologada (IN-139) e avaliação contínua (IN-140).
7. **Relatórios:** disponibilizar na administração Imagine cenários testados, últimas execuções, evidências, resultados, limitações, falhas abertas, recomendações, responsáveis e histórico.

## Regras de segurança aprovadas

- **Sem impacto operacional inadvertido:** testes automáticos não podem derrubar produção, apagar ou sobrescrever dados reais, disparar comunicações reais indevidas ou corromper filas de clientes.
- **Ambientes isolados como padrão:** usar infraestrutura de teste, dados sintéticos e credenciais específicas, com proibição de expor dados reais desnecessários nos relatórios.
- **Uma aprovação humana para testes sensíveis:** qualquer simulação destrutiva, teste com impacto relevante em produção, restauração que sobrescreva dados ou alteração crítica exige **uma única autorização humana expressa** (IN-137/IN-138), com escopo e condições definidos. **Sem dupla aprovação.**
- **Sem expansão financeira não autorizada:** agentes não podem contratar recursos ou gerar gastos novos com testes, duplicação de ambientes ou ferramentas sem autorização de custo (IN-142).
- **Plano de recuperação e interrupção:** cada teste deve declarar pré-condições, isolamento, critérios de êxito/fracasso, limites de duração e repetição, sinais de parada, recuperação/rollback quando possível e procedimento para incidentes surgidos durante os testes.
- **Execução conforme playbook homologado:** não conceder aos agentes novas permissões por conta do sucesso de um teste; novas automações executáveis dependem de homologação (IN-139).
- **Proteção de dados e segredos:** mascarar dados, restringir acesso a logs, evidências e backups; não compartilhar segredos, tokens ou credenciais em histórico de aprendizado.
- **Auditoria:** registrar agente/operador, ambiente, cenário, versão do procedimento, autorizações, datas, indicadores, resultado, evidências e ações corretivas.

## Fluxo aprovado

Planejamento e seleção do cenário → identificação da criticidade e dos serviços envolvidos → análise de risco e pré-condições → preparação de ambiente isolado/dados sintéticos → execução apenas de procedimento autorizado → observação e validação de integridade → medição de recuperação → registro do resultado → abertura de incidente ou recomendação se necessário → extração de lições e proposta de melhorias → homologação controlada de novos playbooks.

## Cenários de teste prioritários

### API principal e API de contingência
- Verificar recebimento sintético com **persistência durável confirmada** em cada via.
- Simular indisponibilidade da principal em ambiente controlado e verificar comportamento de contingência.
- Verificar ausência de confirmação falsa de entrega quando as duas vias não estiverem disponíveis.
- Verificar reconciliação e idempotência de eventos de teste após restauração.

### Filas e entrega de leads
- Testar reinício de workers, repetição de processamento, falhas parciais e reconexão ao CRM, usando submissões sintéticas e contas de homologação.
- Preservar as regras aprovadas de **3 dias em fila normal + 15 dias em fila de falhas** (IN-127), sem reiniciar contagem por retentativas, e alertas antes de atingir o prazo (IN-128).
- Não usar **testes** como justificativa para descarte de mensagens reais. A política operacional de **exclusão automática de envios não entregues após completar 18 dias** foi aprovada posteriormente na **IN-147**, com avisos, verificação de elegibilidade e auditoria.

### Backups de sites, CRM e serviços
- Validar separadamente cópias de sites e os dados do CRM, pois possuem **políticas independentes**.
- Realizar recuperação de arquivos/configurações e restauração de banco **somente em ambiente isolado** para os testes automatizados de rotina.
- Comprovar consistência e capacidade de reativar o serviço restaurado, não apenas existência do arquivo de backup.

### Infraestrutura, DNS e automações
- Testar detecção de falha, alertas, dependências, procedimento de recuperação de serviço e status efetivo.
- Não executar alterações destrutivas em DNS ou produção durante uma simulação regular sem autorização humana e janela aprovada.

## Responsabilidades

- **Agentes especializados:** preparar e executar testes seguros autorizados no seu domínio; avaliar evidências técnicas e propor correções.
- **Agente Supervisor Técnico:** coordenar agenda/casos, revisar qualidade dos resultados, identificar testes com falha ou cenários descobertos, acompanhar a evolução de métricas.
- **Administração Imagine:** configurar políticas e limites, aprovar intervenções críticas e despesas, acompanhar relatórios, homologar novos procedimentos.
- **Central de Incidentes Técnicos:** criar ou atualizar incidentes para falhas reais e deficiências relevantes detectadas nos testes, com a priorização da IN-133 e comunicação da IN-134.

## RTO, RPO e evidências
- Registrar valores medidos durante cada exercício com metodologia, ambiente, pré-condições e limitações.
- Não confundir **resultado de um teste** com garantia de recuperação sob qualquer desastre real.
- RTO/RPO **numéricos ainda não estão aprovados**, conforme IN-144; testes auxiliarão seu dimensionamento técnico e econômico.
- Evitar declarar "perda zero" sem demonstração arquitetural suficiente e testes adequados, inclusive para falhas simultâneas e persistência.

## Pendências antes de iniciar implementação

1. Calendário e periodicidade de testes por criticidade, sistema e serviço.
2. Inventário de cenários, dependências, passos, dados sintéticos, limites e métricas.
3. Ambientes isolados de teste, requisitos de segurança, capacidade e custos.
4. Parâmetros de critérios de aprovação, políticas de teste em produção excepcional e notificações.
5. Modelos de relatórios, retenção de logs e evidências e governança de melhoria/homologação de playbooks.
6. Métodos de cálculo dos tempos medidos e verificação de consistência de backups e filas.
7. Escolha dos valores de RTO/RPO depois de observar resultados e avaliar custos.

**Decisão aprovada:** testes de APIs e contingência, backups, simulação de falhas, filas, tempos de recuperação, aprendizado dos agentes, relatórios e controles para evitar riscos à produção.  
**Pendente:** cadência, ambientes e capacidade, cenários técnicos, metas quantitativas, custos e implementação.  
**Programação:** não iniciada.


## Complemento posterior — IN-147 (01/11/2026)

O descarte de envios **não entregues** após o prazo de **3 dias na fila normal + 15 dias na fila de falhas** está autorizado pela [IN-147](IN-147-exclusao-leads-nao-entregues-apos-18-dias.md). Preservam-se os avisos 72h antes, alerta crítico no vencimento, verificação determinística antes de excluir, auditoria minimizada por 90 dias e exceções legais. Não criar quarentena operacional adicional. Esta atualização prevalece sobre qualquer indicação histórica de que o descarte final permanecia sem aprovação; **não se aplica a leads confirmados no CRM**.
