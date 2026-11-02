# IN-134 — Comunicação e Alertas Inteligentes de Incidentes

**Estado:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 ("ok").  
**Etapa:** planejamento e documentação; não iniciar programação, contratar provedores nem gerar custos.  
**Escopo:** Central de Incidentes Técnicos da Imagine, área administrativa do Portal Imagine, agentes especializados e responsáveis humanos.  
**Dependências:** IN-129 (acionamento técnico automatizado), IN-130 (Central de Incidentes), IN-131 (autonomia), IN-132 (catálogo de agentes) e IN-133 (priorização e escalonamento).

## Objetivo

Garantir comunicação operacional proporcional à gravidade dos incidentes, evitando omissões em incidentes críticos, duplicidade excessiva de alertas e dependência da disponibilidade da interface administrativa do Portal Imagine.

## Canais por prioridade — APROVADOS

| Prioridade | Comunicação |
|---|---|
| **Crítica** | Notificar **imediatamente** o responsável por **painel + e-mail + WhatsApp**, desde que os canais estejam tecnicamente habilitados. Acionar em paralelo agente especializado e responsável humano sem aguardar tentativas automáticas. |
| **Alta** | **Painel + e-mail**, com escalonamento se o caso não for assumido no prazo definido pela futura política de atendimento. |
| **Média** | **Painel administrativo + central de notificações**, com acompanhamento conforme criticidade. |
| **Baixa** | **Registro no painel + relatório de acompanhamento**; não gerar urgência artificial. |

### Regras funcionais aprovadas

1. **Atribuição e confirmação:** responsáveis autorizados podem assumir o incidente e registrar essa confirmação, horário e identificação.
2. **Escalonamento automático:** se um incidente **crítico** não for assumido no prazo operacional a ser especificado, notificar responsável substituto e manter o incidente visível até haver atendimento.
3. **Atualizações relevantes:** comunicar início de correção pelo agente, solicitações de aprovação humana, mudanças relevantes de estado e conclusão verificada, conforme prioridade e preferências administrativas permitidas.
4. **Agrupamento de notificações repetidas:** correlacionar eventos do mesmo incidente para impedir tempestade de avisos sem suprimir deterioração da situação, mudanças críticas ou alertas que necessitem de novo acionamento.
5. **Relatórios periódicos:** apresentar números e evolução dos incidentes resolvidos, pendentes e recorrentes, bem como tempos de resposta/atendimento e encaminhamentos.
6. **Contingência dos alertas críticos:** o envio por canais externos deve ser capaz de continuar mesmo quando a **interface do painel administrativo** estiver indisponível, desde que o monitoramento, o serviço de notificação e os provedores externos estejam funcionais.
7. **Auditoria:** manter registro de incidente, prioridade, destinatários autorizados, canais utilizados, disparos, falhas, confirmação de recebimento quando disponível, assunção de responsabilidade, escalonamento e encerramento.
8. **Segurança e privacidade:** mensagens externas devem incluir apenas o mínimo necessário para identificar a ocorrência e encaminhar o atendimento, sem revelar senhas, segredos, tokens ou conteúdo de leads.
9. **Aprovação humana e limites de agentes:** notificações não autorizam o agente a executar ações críticas automaticamente. Continuam valendo os níveis da IN-131 e aprovação humana para mudanças sensíveis.

## Restrições de implementação e segurança

- A escolha de **WhatsApp para alertas críticos** é uma decisão funcional; selecionar uma integração oficial/suportada, custos, consentimentos/canais e configurações de entrega é uma etapa posterior. Não presumir custo zero ou entrega garantida.
- A confirmação de *envio* por gateway não implica necessariamente leitura humana; distinguir entregue, falhou, visualizado (quando disponível) e **assumido**.
- Não transformar a interface do Portal Imagine ou um único provedor de mensageria no ponto único de falha do monitoramento crítico.
- Definir política de tentativas de notificação, fallback entre canais e comportamento diante de falha de e-mail/WhatsApp sem sobrecarregar destinatários.
- Respeitar segregação de clientes, perfis de acesso e minimização de conteúdo no histórico.

## Detalhes ainda pendentes de aprovação

- Prazos numéricos para confirmar/assumir/escalonar incidentes por prioridade (SLA), inclusive disponibilidade humana fora do horário comercial.
- Cadastro de titulares e substitutos, ordem dos substitutos, confirmação de disponibilidade e escalonamento durante ausências.
- Templates dos avisos, horários e frequência de relatórios periódicos, preferências e limiares de reenvio.
- Provedor de WhatsApp, custos por mensagem, habilitação e regras aplicáveis ao canal; mecanismo de fallback.
- Retenção específica de histórico de notificações e incidentes, observando obrigações de proteção de dados e sem confundir com os **90 dias de auditoria técnica das filas** (IN-128).
- Testes periódicos de contingência e monitoramento de falhas do próprio sistema de alertas.

**Aprovado:** canais por prioridade, aviso crítico imediato, confirmação de responsável, escalonamento, avisos de progresso, agrupamento, relatórios, independência do painel e auditoria.  
**Pendente:** SLAs, custos/canais concretos e configurações operacionais.  
**Programação:** não iniciada.
