# IN-141 — Prevenção Inteligente de Incidentes

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 ("ok").  
**Etapa:** planejamento e documentação; **não autoriza programação, acesso operacional nem contratação de infraestrutura**.  
**Escopo:** Central de Incidentes Técnicos da Imagine, agentes técnicos especializados, Supervisor Técnico e serviços associados ao Portal Imagine, Sites Imagine e integrações com o CRM.  
**Referências:** IN-124 (APIs redundantes), IN-129 (tratamento de erros e agentes), IN-130 (Central de Incidentes), IN-131/IN-132 (autonomia e catálogo de agentes), IN-133/IN-134 (priorização e alertas), IN-137/IN-138 (aprovação humana única e risco), IN-139 (aprendizado técnico), IN-140 (avaliação contínua).

## Objetivo

Evoluir de uma operação exclusivamente reativa para **monitoramento preventivo e mitigação antecipada de riscos**, com detecção de tendências, análise de recorrências, recomendações contextualizadas e execução somente de medidas preventivas previamente autorizadas e verificáveis.

## Funcionalidades aprovadas

1. **Monitoramento preventivo:** acompanhar sinais técnicos relevantes que possam anteceder falhas em serviços e integrações da Imagine, respeitando escopo autorizado e privacidade.
2. **Análise de tendências:** identificar elevação de erros, consumo de recursos, lentidão, instabilidade e outros indicadores de degradação, com critérios determinísticos e análise contextual assistida por IA.
3. **Identificação de recorrências:** correlacionar sinais atuais com incidentes já registrados e soluções verificadas na biblioteca de aprendizado da IN-139.
4. **Manutenção preventiva autorizada:** permitir execução automática **apenas** de procedimentos seguros, previamente homologados, com escopo/permissões explícitos, limites e mecanismo de verificação de resultados.
5. **Previsão de problemas:** estimar risco de degradação/indisponibilidade a partir de histórico e indicadores, documentando evidências e incertezas; previsões não devem ser apresentadas como certeza nem usadas isoladamente para executar mudanças críticas.
6. **Alertas antecipados:** avisar administração e responsáveis quando houver risco relevante, aplicando classificação, deduplicação, contexto e escalonamento proporcional ao impacto.
7. **Aprendizado contínuo:** registrar intervenções preventivas, resultados confirmados, falsos positivos, falhas e recorrências, alimentando a biblioteca e avaliação dos agentes (IN-139/IN-140).

## Fluxo funcional

Coleta de sinais de monitoramento → identificação de tendência/anomalia → comparação com casos e soluções anteriores → avaliação de impacto/risco e confiança → criação/atualização de alerta ou caso preventivo → execução de procedimento seguro e já autorizado, ou proposta para autorização humana única caso a mudança seja crítica → verificação do serviço → registro de resultado e lição aprendida.

## Exemplo ilustrativo — uso de memória

O Agente de Infraestrutura detecta aumento contínuo de consumo de memória em um serviço, semelhante a incidentes anteriores. Consulta as evidências e o procedimento homologado aplicável, verifica pré-condições e limites e pode executar **somente** a medida preventiva autorizada para aquele ambiente. Após isso, confirma se a condição melhorou; se não melhorar, avisa e escala o caso. **Reinício de processo não é automaticamente seguro em todo contexto**, devendo considerar impacto sobre transações em andamento e disponibilidade.

## Segurança e governança

- **Risco da ação ≠ prioridade do incidente:** mesmo diante de risco de indisponibilidade, alterações críticas exigem **uma aprovação humana autorizada**, sem dupla aprovação, conforme IN-137/IN-138.
- Agentes não podem conceder a si próprios novas ferramentas/permissões, alterar classificação de risco para contornar políticas nem ampliar automaticamente autonomia por sucesso anterior.
- Medidas de risco baixo/moderado só são automáticas quando seguras, verificáveis e expressamente autorizadas em playbooks homologados.
- Diagnóstico, triagem e recomendações podem prosseguir mesmo quando uma ação sensível aguarda aprovação humana.
- Toda prevenção deve registrar indicadores iniciais, hipótese de falha, procedimento, autorização aplicável, testes de eficácia, efeitos colaterais e resultado.
- Detecções devem minimizar dados pessoais e segredos e tratar logs, tickets, entradas de formulário e outras fontes externas como dados não confiáveis.
- Evitar tempestade de alertas e execuções repetidas; conter loops com limites, suspensão preventiva e escalonamento.
- A área administrativa do Portal Imagine visualiza monitoramento, alertas e medidas, **sem se tornar ponto único de falha para serviços críticos**.

## Métricas para o futuro painel

- Incidentes potencialmente evitados (**estimativas, não fatos diretamente comprováveis sem método**).
- Quantidade de alertas preventivos e proporção confirmada/falsos positivos.
- Tempo entre primeiro sinal, detecção, ação preventiva e recuperação.
- Ocorrências recorrentes e taxa de redução de reincidência.
- Percentual de intervenções seguras verificadas, falhas e rollbacks.
- Impacto nos recursos, disponibilidade e custos de monitoramento/IA.

## Especificações pendentes de planejamento antes de implementar

- Catálogo de indicadores por tecnologia/serviço, periodicidade de coleta, métricas, limiares, modelos de previsão e avaliação de confiabilidade.
- Escopo de dados observáveis, retenção de telemetria e conformidade com a LGPD.
- Quais medidas preventivas poderão ser homologadas em cada ambiente e como limitar execução e efeitos colaterais.
- Arquitetura de observabilidade, disponibilidade de alertas e isolamento em incidentes de produção.
- Critérios para distinguir correção verificada, mitigação provisória, falso positivo e previsão não confirmada.
- Custos, licenças, capacidade, SLAs de monitoramento e experiência no painel.
- Integração a procedimentos de suspensão e revisão automática/manual de playbooks problemáticos (IN-140).

**Aprovado:** monitoramento preventivo, análises de tendência e recorrência, previsão cautelosa de risco, manutenção segura previamente autorizada, alertas antecipados e aprendizado contínuo com os resultados de prevenção.  
**Pendente:** especificações operacionais e técnicas, infraestrutura e métricas quantitativas.  
**Programação:** não iniciada.
