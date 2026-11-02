# IN-136 — Plantão Técnico e Substituição Automática

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 ("ok").  
**Fase:** exclusivamente planejamento e documentação; não autoriza programação ou contratação de equipe/serviços.  
**Escopo:** Central de Incidentes Técnicos da Imagine, agentes de IA, responsáveis e substitutos de plantão.  
**Referências:** IN-129 (falhas e agentes técnicos), IN-130 (Central de Incidentes), IN-131 (autonomia), IN-132 (especialistas e Supervisor Técnico), IN-133 (priorização), IN-134 (notificações), IN-135 (metas internas de assunção/escalonamento).

## Objetivo
Manter monitoramento e resposta automatizada de incidentes 24 horas por dia, sem confundir essa capacidade com cobertura humana 24/7. Estruturar uma escala humana configurável, com responsáveis principais, substitutos e acionamentos rastreáveis.

## Regras funcionalmente aprovadas

1. **Monitoramento automatizado 24/7:** as rotinas de monitoramento e detecção de incidentes deverão operar continuamente, desde que a infraestrutura necessária esteja funcional, sem depender da presença de um operador humano.
2. **Responsável principal:** cada incidente deverá ter responsável administrativo ou técnico designado, de acordo com a especialidade e as permissões.
3. **Substituto:** se o responsável principal não assumir o incidente no prazo configurado, o sistema acionará o substituto adequado, mantendo histórico de avisos, tentativas de contato e assunções.
4. **Incidentes críticos:** emitir aviso imediato ao plantonista humano designado e ao agente especializado em paralelo; não aguardar o esgotamento de tentativas automáticas. Permanece a meta interna de **5 minutos para assumir** e o escalonamento a substituto em seguida, conforme IN-135.
5. **Ausência de plantonista:** registrar explicitamente a ausência de cobertura humana, manter monitoramento, diagnósticos e alertas automáticos; não reportar um incidente como assumido por humano que não confirmou atendimento.
6. **Agentes de IA:** continuam atuando conforme seu nível de autonomia e permissões, inclusive autocorreções pré-aprovadas, reversíveis e de baixo risco; não podem obter mais autonomia por indisponibilidade humana.
7. **Aprovação humana obrigatória:** intervenções críticas em infraestrutura produtiva, DNS, credenciais, permissões, dados de clientes ou ações irreversíveis não serão executadas automaticamente mesmo que não exista plantonista disponível.
8. **Escala simples e configurável:** a administração Imagine definirá janelas de cobertura, responsáveis, substitutos e regras de escalonamento; o desenho inicial não exige contratação de equipe exclusiva 24 horas por dia.
9. **Rastreabilidade:** registrar períodos de cobertura, pessoa designada, indisponibilidades, eventos de notificação, confirmação de assunção, substituições, execução autorizada, resolução e encerramento.
10. **Comunicação clara:** separar no painel a disponibilidade dos agentes automatizados, disponibilidade de plantão humano, incidente detectado, responsável notificado, incidente efetivamente assumido e incidente resolvido.

## Matriz de comportamento

| Situação | Comportamento |
|---|---|
| Sistema detecta erro às 03h, sem plantonista designado | Registra incidente e prioriza; agentes autorizados atuam; alertas são emitidos, mas não se presume atendimento humano |
| Incidente crítico com plantonista designado | Notifica imediatamente plantonista e agente especialista; se não assumir em 5 minutos, aciona substituto |
| Responsável ausente ou indisponível | Encaminha a substituto elegível, com registro do escalonamento |
| Agente identifica alteração crítica necessária | Prepara diagnóstico/proposta e solicita aprovação humana; aguarda autorização |
| Incidente resolvido por autocorreção segura | Valida resultados, atualiza caso e registra evidências, segundo IN-130 e IN-131 |

## Prazos e regras já aprovados (IN-135)

- **Crítica:** até 5 minutos para assunção; acionar substituto caso não assumida.
- **Alta:** 30 minutos para assunção; escalonar a responsável superior.
- **Média:** 4 horas úteis; gerar alerta de pendência.
- **Baixa:** 1 dia útil; acompanhamento administrativo.
- Esses tempos são metas internas e **não constituem SLA contratual aos clientes**.
- **Monitoramento automatizado 24/7 não equivale a atendimento humano disponível 24/7**.

## Detalhes ainda pendentes de especificação

- Cadastro de usuários autorizados, titulares e substitutos, fusos, feriados, férias, ausências, jornadas, limites legais e agenda concreta de cobertura humana.
- Definir janelas e ordenação de substitutos, o que ocorre quando não houver nenhum responsável habilitado e como evitar alertas repetitivos excessivos.
- Garantir canais de alerta independentes da interface administrativa e tratamento de indisponibilidade dos serviços de notificação.
- Especificar políticas de escalonamento após os primeiros substitutos, atribuição definitiva de responsabilidade e acompanhamento de incidentes simultâneos.
- Dimensionar custo de eventual plantão, SLAs externos, volume de incidentes e disponibilidades reais antes de assumir compromissos de serviço.
- Especificar verificação e auditoria da efetiva assunção por humano, sem depender apenas de confirmação de entrega de e-mail/WhatsApp.
- Definir plano de contingência caso tanto os serviços automatizados quanto as notificações externas estejam indisponíveis.

**Decisão aprovada:** monitoramento automático 24/7, escala humana configurável com principal/substituto, avisos e substituição, sem exigir equipe 24/7, mantendo aprovação humana para operações críticas.  
**Programação:** não iniciada.
