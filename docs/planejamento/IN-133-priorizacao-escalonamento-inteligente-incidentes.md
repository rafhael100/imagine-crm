# IN-133 — Priorização e Escalonamento Inteligente de Incidentes

**Status:** APROVADA funcionalmente em 01/11/2026, mediante confirmação do responsável do projeto ("ok").  
**Etapa:** planejamento e documentação; **não autoriza iniciar programação**.  
**Escopo:** Central de Incidentes Técnicos e agentes especializados da Imagine.  
**Referências:** IN-124 a IN-129 (formulários, APIs, entrega, filas e recuperação), IN-130 (Central de Incidentes), IN-131 (autonomia), IN-132 (catálogo e Supervisor Técnico).

## Objetivo
Classificar incidentes técnicos segundo gravidade e impacto, priorizar a resposta, direcioná-los aos agentes especializados e escalar a responsáveis humanos quando necessário, mantendo contexto, rastreabilidade e controle de operações críticas.

## Níveis de prioridade aprovados

| Prioridade | Exemplo | Ação |
|---|---|---|
| **Crítica** | Ambas as APIs de recepção de formulários indisponíveis | **Acionamento imediato e simultâneo** do agente especializado e do responsável humano, sem aguardar conclusão da correção automática |
| **Alta** | Entrega de leads de múltiplos clientes comprometida | Diagnóstico prioritário pelo agente especializado, alerta e escalonamento conforme impacto |
| **Média** | Falha isolada em integração específica | Abrir caso e permitir procedimento de autocorreção previamente autorizado |
| **Baixa** | Problema sem impacto imediato na operação | Registrar, acompanhar e programar correção |

Os exemplos são ilustrativos; a classificação final exige critérios objetivos de impacto, alcance, urgência, segurança e continuidade operacional.

## Regras funcionais aprovadas

1. **Classificação automática inicial** dos incidentes em baixa, média, alta ou crítica, com justificativa visível na Central.
2. **Reclassificação automática** se o impacto/urgência aumentar ou diminuir, registrando motivo, momento e histórico. Permitir revisão por responsável autorizado.
3. **Correlação e agrupamento** de eventos e erros relacionados em um único incidente rastreável, evitando abertura de chamados duplicados sem apagar eventos individuais.
4. **Distribuição** a agente especializado conforme o serviço afetado, com um responsável principal pelo incidente e colaboração entre especialistas quando necessário.
5. **Incidentes críticos:** acionar **simultaneamente** o agente especializado e o responsável humano designado; não postergar o alerta humano enquanto o agente tenta diagnosticar ou corrigir. Registrar tentativas e falhas de notificação.
6. **Escalonamento humano** sempre que o agente não resolver, não possuir a autorização necessária, detectar risco maior, ou houver falha persistente; preservar diagnóstico, evidências e ações já realizadas.
7. **Supervisor Técnico** acompanha casos recorrentes, agravamentos de prioridade, qualidade das correções e pendências, mas não pode dispensar aprovações humanas obrigatórias.
8. **Histórico completo:** registrar identificação, prioridade e suas alterações, impacto, detecção, atribuição, notificações, respostas, diagnósticos, aprovações, intervenções, resultados, tempos de resposta e resolução.
9. **Prazos de resposta configuráveis por prioridade**, com valores a dimensionar e aprovar antes da implementação; não fixar SLAs numéricos nesta etapa.
10. **Segurança:** respeitar os três níveis da IN-131; ações críticas e alterações sensíveis exigem aprovação humana explícita e autorizada mesmo em incidentes críticos. Severidade não concede novos privilégios aos agentes.

## Integração com a recuperação de leads
- Preservar a **fila de entrega por 3 dias** e a **fila de falhas por mais 15 dias**, totalizando 18 dias; a classificação crítica não reinicia esses prazos.
- Manter o aviso administrativo 72 horas antes do vencimento, alerta crítico e interrupção de retentativas automáticas no dia 18, conforme IN-127 e IN-128.
- Se ambas as APIs não puderem receber formulários, o site não pode confirmar envio bem-sucedido sem persistência durável; os responsáveis humanos e agentes devem ser acionados imediatamente.
- Não descartar silenciosamente leads pendentes. A política final de descarte continua pendente de aprovação específica.

## Pontos técnicos a especificar
- Matriz objetiva de severidade (quantidade de clientes, criticidade do serviço, duração, segurança, perda potencial, impacto financeiro).
- SLAs/tempos de resposta por prioridade, eventuais escalonamentos por ausência de confirmação e escalas de atendimento humano.
- Canais e redundância de alertas, confirmação de recebimento, fallbacks e janelas de atendimento.
- Regras de correlação e deduplicação de incidentes, reabertura, despriorização e prevenção de oscilação entre níveis.
- Proteção de dados sensíveis em alertas e histórico, segregação por cliente e perfis autorizados.
- Métricas de tempo de detecção, resposta, resolução, recorrência e validação de correções.

**Aprovado:** níveis de prioridade, classificação e reclassificação, agrupamento, escalonamento, histórico, Supervisor Técnico e acionamento humano simultâneo em incidentes críticos.  
**Pendente:** valores de SLA, canais operacionais, escalas, critérios quantitativos e detalhamento de automações.  
**Programação:** não iniciada.
