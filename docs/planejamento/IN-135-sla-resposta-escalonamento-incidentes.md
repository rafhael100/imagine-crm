# IN-135 — Prazos de Resposta e Escalonamento (SLA) de Incidentes

**Estado:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 ("ok").  
**Fase:** exclusivamente planejamento/documentação, sem iniciar programação.  
**Escopo:** Central de Incidentes Técnicos Imagine, agentes especializados, Supervisor Técnico e responsáveis humanos.  
**Referências:** IN-129 (agente de TI), IN-130–IN-132 (central, autonomia e agentes), IN-133 (priorização), IN-134 (alertas e comunicação).

## Objetivo
Definir **metas internas de tempo para um responsável assumir um incidente e regras de escalonamento**, sem confundir tempo de assunção com tempo de resolução e sem converter automaticamente essas metas em SLA contratual anunciado aos clientes.

## Prazos aprovados

| Prioridade | Meta para assumir o incidente | Resposta caso não seja assumido |
|---|---|---|
| **Crítica** | **5 minutos** | Acionar substituto imediatamente; incidente permanece em acompanhamento ativo |
| **Alta** | **30 minutos** | Escalonar para responsável superior |
| **Média** | **4 horas úteis** | Avisar sobre a pendência |
| **Baixa** | **1 dia útil** | Incluir no acompanhamento administrativo |

Os marcos de início, os horários úteis e a elegibilidade ao plantão serão detalhados antes da implementação; os tempos são **metas operacionais internas propostas e aprovadas**, não garantias contratuais de atendimento humano 24/7.

## Regras funcionais aprovadas

1. **Diagnóstico automático imediato:** os agentes de IA poderão iniciar análise do incidente assim que for detectado, sem aguardar o prazo para assunção humana; a autonomia continua limitada às regras da IN-131.
2. **Monitoramento de críticos 24/7:** o sistema automatizado monitora incidentes críticos continuamente, respeitando disponibilidade das infraestruturas e canais de alerta.
3. **Tempos distintos:** o prazo para **assumir** o incidente é diferente do prazo para **resolver** o problema; tempos de resolução por classe permanecem pendentes.
4. **Responsável principal e substitutos:** cada incidente terá responsável identificável e poderá acionar substitutos na falta de confirmação ou em indisponibilidade.
5. **Supervisor Técnico:** acompanhará prazos, ausências de assunção e escalonamento de forma automática, mantendo o histórico.
6. **Cobertura humana fora do expediente:** só poderá ser comprometida a partir de uma **escala de plantão concreta**, com pessoas autorizadas e custos/jornadas dimensionados pela Imagine.
7. **Críticos:** manter o acionamento simultâneo e imediato do agente e do responsável humano quando identificado incidente crítico, conforme IN-133/IN-134. O intervalo de 5 minutos refere-se à confirmação/assunção; **não significa aguardar 5 minutos para o primeiro alerta**.
8. **Auditoria:** registrar horário de detecção, classificação, notificação, confirmação de recebimento quando disponível, assunção, escalonamentos, primeira ação, mitigação e resolução, com distinção explícita entre estados.
9. **Metas internas:** não publicar esses prazos como SLA contratado a clientes nem garantir atendimento 24/7 humano antes da validação de operação, equipe e custos.

## Cuidados para o desenho operacional
- A escala de plantão humana, horários de atendimento, dias úteis, fuso de referência, feriados, férias, afastamentos e substituições serão definidos em bloco específico.
- Alertas críticos poderão ser emitidos continuamente mesmo que nenhum humano esteja formalmente em plantão; nesse caso o painel deverá indicar ausência de cobertura humana garantida, sem afirmar que houve atendimento.
- Monitorar falhas nos próprios canais de alerta/WhatsApp, e-mail e painel (IN-134); distinguir mensagem enviada de incidente efetivamente assumido.
- Evitar resetar contadores por mudança de prioridade ou reapresentação de um mesmo erro; especificar tratamento justo quando a prioridade muda.
- Todos os prazos dependerão da robustez técnica de registro e medição de eventos em infraestrutura independente da interface administrativa.
- Agentes não podem executar operações críticas sem aprovação humana (IN-131), ainda que exista prazo interno de resposta.

## Pendências para aprovação posterior
1. Escala humana de plantão, titulares/substitutos e horários reais de cobertura.
2. Prazos de mitigação/resolução e eventual comunicação pública de SLAs.
3. Fuso, calendário útil, feriados, contagem a partir da detecção e regras de prioridade alterada.
4. Canais de escalonamento, periodicidade de lembretes, contingências dos canais e custos.
5. Métricas e relatórios para cumprimento de metas, violações e análise de causas.

**Aprovado:** metas internas de 5 min, 30 min, 4 horas úteis e 1 dia útil; agentes com diagnóstico imediato; monitoramento automático 24/7 de críticos; responsáveis/substitutos; escalonamento e distinção assunção x resolução.  
**Pendente:** operação humana e SLAs externos.  
**Programação:** não iniciada.
