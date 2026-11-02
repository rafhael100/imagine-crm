# IN-130 — Central de Incidentes Técnicos e Agentes de IA Imagine

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026.  
**Fase:** planejamento e documentação; não iniciar programação.  
**Escopo:** área administrativa Imagine, monitoramento de integrações e incidentes técnicos, orquestração de agentes especializados e escalação para responsáveis humanos.  
**Dependências:** IN-123/IN-124 (formulários e integrações), IN-126 (monitoramento), IN-127/IN-128 (filas, retenção e auditoria), IN-129 (acionamento do agente de TI), e políticas de segurança, permissões e gestão dos serviços Imagine.

## Objetivo
Criar **uma Central de Incidentes Técnicos** na área administrativa do Portal Imagine, com abertura e acompanhamento de casos, priorização de incidentes, encaminhamento automático a agentes de IA especializados, correção segura com controle de autonomia e escalonamento humano quando necessário.

O primeiro caso de uso é a recuperação de erros na entrega de formulários/leads ao CRM; a arquitetura da central deverá permitir expansão futura a **SSL, DNS, hospedagem, integrações, automações e outros serviços Imagine**, com regras e permissões próprias por serviço. A expansão futura não significa que todos esses domínios já estejam autorizados para execução autônoma.

## Funcionalidades aprovadas
1. **Centralização:** exibir incidentes técnicos com informações suficientes para administração Imagine acompanhar a situação dos serviços.
2. **Classificação de prioridade:** níveis **baixa, média, alta e crítica**, com critérios detalhados para impacto, urgência e risco ainda a especificar.
3. **Abertura automática:** falhas técnicas detectadas que exijam intervenção, como erros de autenticação, configuração ou integração, devem gerar caso/incidente e acionar o agente de TI especializado conforme IN-129.
4. **Distribuição inteligente:** direcionar cada caso ao agente de IA especializado mais apropriado, respeitando escopo, permissões e disponibilidade; quando não houver agente habilitado, encaminhar a uma equipe humana.
5. **Diagnóstico e correção automatizados:** os agentes poderão analisar telemetria e executar apenas procedimentos **seguros, reversíveis, previamente autorizados e de baixo risco**; ações com impactos significativos devem receber aprovação humana.
6. **Aprovação humana obrigatória:** alterações críticas, credenciais/segredos, permissões, infraestrutura de produção, vínculos entre contas de clientes, exclusões irreversíveis e novas despesas exigem consentimento explícito de responsável autorizado antes da execução.
7. **Escalonamento:** se o agente não conseguir resolver, se sua autorização for insuficiente ou se o risco aumentar, encaminhar à equipe técnica humana, preservando diagnóstico e histórico; considerar prazo de retenção e criticidade dos leads envolvidos.
8. **Histórico auditável:** registrar classificação, abertura, atribuição, contexto técnico mínimo, tentativas, diagnósticos, propostas, aprovações, correções, resultados, tempo de resolução e encerramento.
9. **Aprendizado controlado:** preservar soluções comprovadas e aprovadas como procedimentos reutilizáveis para incidentes semelhantes; exigir versionamento, revisão, restrições por serviço e impedir que o agente se autoatribua novas permissões.
10. **Encerramento fundamentado:** um caso só pode ser encerrado quando a recuperação for verificada ou uma decisão administrativa explícita justificar sua resolução/encerramento, com trilha de auditoria.
11. **Separação por cliente/serviço:** informações e ações de cada incidente devem respeitar segregação entre empresas, acesso mínimo necessário, proteção de dados pessoais e segurança do ambiente Imagine.

## Fluxo funcional

Detecção de anomalia/erro → classificação e abertura de incidente → identificação do serviço e responsável → agente especializado analisa → 
- **Correção de baixo risco previamente autorizada:** execução controlada, validação e registro;
- **Ação crítica ou sem permissão:** solicitar aprovação humana, sem execução até a autorização;
- **Sem solução segura/resultado satisfatório:** escalonar ao responsável técnico humano;
→ verificar recuperação → atualizar incidentes e métricas → encerrar com evidências.

## Exemplos de expansão futura (arquitetura preparada, implementação específica pendente)

- Integrações de formulários e recuperação de leads (escopo imediato aprovado).
- Certificados SSL e validade/renovação.
- Configuração DNS e publicação de domínios.
- Hospedagem, indisponibilidade e problemas de desempenho.
- Automações e execuções de workflows.
- Integrações externas autorizadas.

## Restrições e detalhes pendentes para especificação

- Catálogo de agentes técnicos, papéis, ferramentas, autorização, ambientes, níveis de autonomia, rate limits, limites de custo, revogação e proteção contra prompt injection.
- Padrões de severidade e SLA; distribuição, substituição e escalonamento de responsáveis técnicos; canais de alerta para incidentes críticos.
- Definição de entidades como evento, alerta, incidente, caso, tarefa e problema recorrente; correlação de ocorrências para impedir múltiplos casos duplicados.
- Integração do painel com serviços e armazenamentos sem transformá-lo em ponto único de falha para recepção de formulários.
- Log mínimo, mascaramento de dados, proteção de credenciais e acesso a logs de produção; prazos específicos de retenção dos registros de incidentes, diferenciados dos **90 dias de auditoria técnica das filas** da IN-128.
- Catálogo de procedimentos aprovados, verificação de resultados, rollback, avaliação de desempenho do agente, revisão e versionamento de soluções.
- Critérios de incidentes que exigem intervenção imediata; política para casos críticos durante ausência de responsável.
- Implementação de novos domínios (SSL, DNS, hospedagem, automações) dependerá de detalhamento e de políticas de operação/autonomia pertinentes.

## Integração com as políticas de leads já aprovadas

As filas de leads mantêm: **3 dias de tentativas normais + 15 dias na fila de falhas**, somando até **18 dias**; alertas e diagnóstico dos erros; notificação 72 horas antes do vencimento; alerta crítico e parada de retentativas automáticas ao completar 18 dias; sem autorização para descarte silencioso. A Central de Incidentes deverá considerar esses prazos ao priorizar e acompanhar os casos.

**Aprovado:** central unificada, classificações, distribuição a agentes, autocorreção segura, aprovação humana de ações críticas, escalonamento, histórico, reutilização de soluções verificadas e arquitetura extensível a outros serviços.  
**Pendente:** parâmetros operacionais, políticas de autonomia e especificações por domínio.  
**Programação:** não iniciada.
