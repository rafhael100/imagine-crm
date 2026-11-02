# IN-133 — Priorização e Escalonamento Inteligente de Incidentes

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 ("ok").  
**Fase:** planejamento e documentação; não iniciar programação.  
**Escopo:** Central de Incidentes Técnicos da Imagine e seus agentes especializados.  
**Referências:** IN-124–IN-129 (formulários, filas, diagnóstico e agente de integrações), IN-130 (Central de Incidentes), IN-131 (autonomia), IN-132 (catálogo e Supervisor Técnico).

## Objetivo
Priorizar casos técnicos de acordo com impacto, abrangência, urgência e risco, distribuir incidentes a agentes especializados, escalar para humanos quando necessário e dar tratamento imediato a ocorrências críticas.

## Níveis de prioridade aprovados

| Prioridade | Exemplo ilustrativo | Tratamento funcional |
|---|---|---|
| **Crítica** | API principal e API de contingência de formulários indisponíveis simultaneamente | **Acionar imediatamente, em paralelo, o agente especializado e o responsável técnico humano**, sem aguardar a conclusão de tentativas automáticas |
| **Alta** | Leads de vários clientes sem entrega | Abrir/priorizar incidente e iniciar diagnóstico pelo especialista |
| **Média** | Uma integração de cliente apresenta falha | Abrir caso e permitir correção automática se segura e previamente autorizada |
| **Baixa** | Problema sem impacto operacional imediato | Registrar, acompanhar e programar correção segundo prioridade |

Exemplos são referenciais: a classificação concreta deve considerar **impacto real, abrangência, gravidade e evolução do evento**, e não depender somente do código de erro ou da quantidade de sites.

## Regras adicionais aprovadas

1. **Classificação automática com critérios auditáveis:** atribuir prioridade inicial a cada incidente e mostrar as razões e evidências relevantes.
2. **Reclassificação dinâmica:** elevar ou reduzir a prioridade quando mudarem abrangência, impacto, estado de recuperação ou risco, mantendo o histórico.
3. **Correlação e agrupamento:** falhas relacionadas devem ser agrupadas em um incidente principal quando compartilham uma causa ou dependência, evitando chamados repetidos; preservar cada site, conta, envio e ocorrência afetada com rastreabilidade e segregação de dados.
4. **Distribuição especializada:** encaminhar casos ao agente técnico responsável pela categoria (IN-132); colaboração de outros agentes é permitida com coordenador ou responsável principal.
5. **Escalonamento humano:** acionar responsável humano quando o agente não resolver, quando não possuir autorização para corrigir, quando houver risco elevado ou quando se aproximarem prazos operacionais.
6. **Incidentes críticos:** acionar **simultaneamente** agente especializado e responsável humano, com notificação imediata e registro; não esperar a falha se repetir nem o término de qualquer ciclo de retentativas.
7. **Supervisor Técnico:** acompanhar andamento, recorrências, evolução de prioridade, casos sem solução e confirmação de recuperação; não substituir aprovações humanas para mudanças críticas.
8. **Histórico integral:** registrar detecção, classificação, mudanças de prioridade, agrupamento, atribuições, ações, tentativas, aprovações, encaminhamentos, solução e tempos de resposta/resolução.
9. **Prazos de atendimento:** permitir SLAs/tempos de resposta e escalonamento **configuráveis por prioridade**; os valores numéricos serão dimensionados e aprovados antes da implementação.
10. **Autonomia limitada:** ações críticas em servidores, DNS, credenciais, permissões, dados de clientes e alterações irreversíveis permanecem dependentes de aprovação humana, conforme IN-131.

## Interação com a operação de leads

- Integrar eventos das APIs principal/secundária, filas e CRM à Central de Incidentes.
- Considerar a retenção aprovada de **3 dias em fila normal + 15 dias em falhas**, os alertas pré-expiração e a interrupção de retentativas automáticas no dia 18 (IN-127/IN-128).
- Detecção de erro que exija correção técnica abre caso e aciona o Agente de Integrações sem aguardar 3 dias (IN-129).
- Incidente de recepção com ambas as APIs fora do ar deve ser tratado como crítico; mensagem de sucesso ao visitante só quando algum endpoint confirmar persistência durável do envio, conforme IN-124.

## Especificações ainda pendentes
- Matriz objetiva de impacto e urgência, classificação e reclassificação, abrangência por clientes e severidade.
- Tempos concretos de resposta, escalonamento e resolução, horários de cobertura, contatos humanos e canais de notificação.
- Deduplicação e regras de correlação entre incidentes distintos e múltiplas empresas sem exposição indevida de dados.
- Alertas confiáveis mesmo se o Portal Imagine estiver indisponível; reconciliação e auditoria de notificações.
- Catálogo de playbooks por prioridade e políticas de mudanças emergenciais com autorização humana e rollback.
- Métricas, relatórios, testes periódicos de escalação e regras de fechamento.

**Aprovado:** níveis crítica/alta/média/baixa, reclassificação automática, agrupamento, escalonamento humano, Supervisor Técnico, histórico e configuração futura de SLA, com notificação imediata e paralela de humano e agente para incidentes críticos.  
**Pendente:** parâmetros operacionais e tempos concretos.  
**Programação:** não iniciada.
