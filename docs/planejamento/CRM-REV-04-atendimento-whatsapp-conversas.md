# CRM-REV-04 — Atendimento, WhatsApp e Gestão de Conversas

**Data:** 2026-11-03. **Status atualizado:** **APROVADA COMO BASE DOCUMENTAL**, conforme [CRM-DEC](CRM-DEC-aprovacao-REV-04-base.md). Mantidas as 14 pendências ATD-P01..14, riscos ATD-D01..12, contratos, provedores, direitos e parâmetros. A [CRM-REV-05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md) é **minuta para revisão, ainda não aprovada**.
**Escopo:** Central de Atendimento WhatsApp, posse, filas, equipe, transferências, IA e interfaces com chatbot, CRM, integrações e privacidade. Não criar motores duplicados nem alterar decisões comerciais.
**Fase:** apenas planejamento/documentação GitHub. **Sem programação, testes executados, mensagens reais, credenciamento Meta/BSP, automações novas, produção ou despesas**.

## 1. Fontes históricas verificadas

- [01 — Visão geral e arquitetura](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md): Central oficial, posse, múltiplos números, transferência, FIFO, reserva e distribuição
- [04 — Empresas e usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md): Caixa compartilhada, SLA, CSAT, chatbot, anexos, respostas rápidas, etiquetas e fluxos
- [05 — Agentes de IA](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/05-agentes-de-ia.md): IA 24/7, transparência e solicitação de humano
- [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md), [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md), [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [INT-R01](integracoes/INT-R01-governanca-contratos-homologacao.md) e [IN-167](IN-167-consolidacao-arquitetura-agentes-tecnicos.md)

**Limite de origem:** as seções históricas marcadas APROVADO são fontes documentais, mas o cotejo literal integral de ATD-01..12 com as conversas ainda está pendente. As decisões posteriores aprovadas de posse e pausa prevalecem sobre menções iniciais a “proposta pendente”.

## 2. Regras funcionais identificadas — não novos requisitos

| ID | Área | Regra e fronteira preservada |
|---|---|---|
| ATD-R01 | Canal oficial | WhatsApp Business Platform/API oficial; integração opt-in por empresa e políticas do provedor. |
| ATD-R02 | Central compartilhada | Caixa de entrada no CRM; vincular lead/contato, vendedor/equipe, autoria de cliente, humano, bot e IA. |
| ATD-R03 | Números | Vários números por empresa; um no adicional WhatsApp e excedentes contratáveis, com valores e custos externos a revisar. |
| ATD-R04 | Histórico | Contato único na empresa; conversas distintas por número, equipe, janela, estado e responsabilidade. |
| ATD-R05 | Posse humana | Quando humano assume, cessar respostas da IA e mensagens automáticas ao cliente naquela conversa; tarefas internas autorizadas podem continuar. |
| ATD-R06 | Volta à IA | Depois de posse humana, só humano autorizado devolve; pedido pendente de humano, sem aceite, pode ser cancelado pelo cliente. |
| ATD-R07 | Transparência | IA se identifica como virtual e informa como solicitar humano, sem prometer disponibilidade inexistente. |
| ATD-R08 | IA 24/7 | Configuração opcional, mesmo fora de expediente humano, sujeita a regras do canal, créditos, privacidade e direitos. |
| ATD-R09 | Transferência entre números | Humano autorizado e agente IA com destinos pré-configurados podem iniciar, sem mover uma thread nativa. |
| ATD-R10 | Responsabilidade na transferência | Origem continua responsável até destino aceitar ou iniciar efetivamente; link/aviso ao cliente não conclui transferência. |
| ATD-R11 | Aviso de transferência | Notificar por envio autorizado e preservar status técnico sem inventar entrega/lido; destino não herda permissão de disparo. |
| ATD-R12 | Equipe e expediente | SLA de transferência humana conta durante expediente da equipe de destino; IA pode permanecer ativa por configuração. |
| ATD-R13 | Distribuição | Round Robin ou menor carga, considerando equipe, estado Disponível, permissão, expediente e capacidade. |
| ATD-R14 | Estados de funcionário | Disponível, Ocupado, Ausente, Offline; desconexão breve não encerra ou transfere automaticamente atendimentos. |
| ATD-R15 | Capacidade simultânea | Limites configuráveis por funcionário/equipe; conversas humanas abertas e transferência pendente na origem contam; IA exclusiva e fila sem dono não contam. |
| ATD-R16 | Fila humana FIFO | Fila por equipe/entrada, sem prioridade VIP no MVP; novas mensagens do cliente não alteram posição. |
| ATD-R17 | Desistência e retorno | Desistência voluntária remove da fila; pedido futuro de humano entra no fim da fila com novo horário. |
| ATD-R18 | Aceite explícito | Distribuição reserva capacidade; conversa só se torna humana após atendente aceitar; prazo configurável, 2 min como referência inicial. |
| ATD-R19 | Recusa | Recusa libera reserva e permite redistribuir; motivo/observação opcionais, registro de auditoria. |
| ATD-R20 | Desconexão prolongada | Redistribuição só após verificação e prazo configurável; 10 min referência inicial, não valor universal fixo. |
| ATD-R21 | SLA e gestão | Prazos primeira resposta e subsequentes, avisos/escalonamentos e relatórios conforme horários e permissões. |
| ATD-R22 | Encerrar e reabrir | Encerramento autorizado com motivo, retorno do cliente reabre conforme política, preservando histórico. |
| ATD-R23 | CSAT | Pesquisa opcional de 1 a 5 e comentário opcional após atendimento; alertas e relatórios sujeitos a regras de envio. |
| ATD-R24 | Respostas/etiquetas | Modelos internos, respostas rápidas e tags da empresa com permissões, sem confundir com template aprovado pelo provedor. |
| ATD-R25 | Mídia | Anexos e áudios com validação e autorização, aplicando franquia de arquivos e regras do canal. |
| ATD-R26 | Chatbot visual | Fluxos de menus, perguntas, condições, ações e transferência, sem exigir IA conversacional. |
| ATD-R27 | Fluxos versionados | Rascunho e publicação, versão fixa durante conversa, simulador futuro com dados fictícios; limites por plano sujeitos à revisão comercial. |
| ATD-R28 | Rastreabilidade | Separar mensagens realmente enviadas, eventos internos, notas, transferências, SLA, agente/atendente e resultado de negócio. |

## 3. Dimensões que não podem ser confundidas

| Dimensão | Regra documental |
|---|---|
| Provedor WhatsApp | Envio/recebimento, status real da mensagem e elegibilidade de template/janela; não confirma atendimento humano |
| Conversa CRM | Estado por número, posse/atendente, história; não mover thread entre linhas |
| Fila e reserva | Posição FIFO, reserva provisória, aceite ou recusa do humano; reserva não é posse |
| Transferência | Pedido, pendência, confirmação real no destino; origem responsável enquanto não aceita/iniciada |
| Disponibilidade/SLA | Expediente humano, estado Disponível/Offline, capacidade, pausa e vencimento |
| IA e automação | Pausa por posse humana, retomada com autorização, versão de fluxo e saldo elegível |
| Efeito comercial | Lead/oportunidade/tarefa só confirmado pelo módulo proprietário, não por HTTP/n8n/mensagem entregue |
| Auditoria/CSAT | Direitos de acesso, origem e retenções por categoria, sem acesso universal de gestor |

Estes termos são **dimensões conceituais**, não enums/esquemas físicos homologados.

## 4. Divergências e alertas de reconciliação

| ID | Risco de leitura | Tratamento a manter |
|---|---|---|
| ATD-D01 | Nota inicial de pausa IA ainda pendente é superada por seções posteriores expressamente aprovadas de posse e pausa de automações. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D02 | Contato único com histórico agregado não funde threads/filas/posse de números distintos. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D03 | Transferência IA aprovada somente para destinos e regras definidos, não autoriza disparo externo vedado. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D04 | Fila FIFO do MVP não herda pontuação comercial, VIP ou prioridade de agenda. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D05 | Referências 2 min (reserva) e 10 min (alerta/redistribuição) são configuráveis, não constantes universais. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D06 | Chatbot por fluxo é distinto de agente IA conversacional e suas franquias de créditos. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D07 | Pausa de mensagens ao cliente não suspende tarefas/alertas internos legitimamente autorizados. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D08 | Acordo de atendimento humano não equivale ao atendimento de IA 24/7; SLA de equipe depende de expediente. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D09 | Status de mensagem no provedor não é aceite humano, transferência concluída nem efeito comercial. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D10 | Custos e políticas de Meta/BSP podem mudar e devem ser verificados antes da implementação. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D11 | Regra histórica de inatividade 24/12/72 exige conferência na aprovação ATD específica, sem fixação por suposição. | Confrontar original e preservar regra aprovada mais específica |
| ATD-D12 | Visibilidade do contato por gestor não autoriza leitura de toda conversa confidencial de outra equipe. | Confrontar original e preservar regra aprovada mais específica |

## 5. Pendências para especificação do Atendimento

| ID | Tema ainda pendente | Situação |
|---|---|---|
| ATD-P01 | Modelo de mensagens, eventos, anexos e estados técnicos versus CRM | Aberto para especificação e aceite |
| ATD-P02 | Posse humano/IA, revalidação antes de enviar, fila e transições atômicas | Aberto para especificação e aceite |
| ATD-P03 | Escopos multiempresa, equipe, número, notas internas e histórico unificado | Aberto para especificação e aceite |
| ATD-P04 | FIFO, empate, aceite/recusa, reservas e limite simultâneo sem corrida | Aberto para especificação e aceite |
| ATD-P05 | SLA/expediente, feriados, pausas e escalonamento | Aberto para especificação e aceite |
| ATD-P06 | Valores finais configuráveis de aceite, desconexão e alerta | Aberto para especificação e aceite |
| ATD-P07 | Transferências multi-número, opt-in, janela e autorização de destino | Aberto para especificação e aceite |
| ATD-P08 | Provedor oficial/BSP, custos e adicionais, contratos e templates vigentes | Aberto para especificação e aceite |
| ATD-P09 | Autonomia IA, créditos, memória, resumos e limite de ferramentas | Aberto para especificação e aceite |
| ATD-P10 | Chatbot, versão, gatilhos, simulador e motor central | Aberto para especificação e aceite |
| ATD-P11 | Encerramento e inatividade; conferir marcos 24h/12h/72h nos originais ATD | Aberto para especificação e aceite |
| ATD-P12 | LGPD, opt-out, auditoria por classe e retenção de mensagens/anexos | Aberto para especificação e aceite |
| ATD-P13 | Benefícios por plano, trial, Básico, fluxos ativos e número contratado | Aberto para especificação e aceite |
| ATD-P14 | Cotejo do texto original dos blocos ATD-01..12 e prevalência de atualizações | Aberto para especificação e aceite |

São itens de **revisão documental**, não novos serviços, normas já aprovadas ou testes. Devem ser conciliados com os P0 transversais existentes sem duplicar exigências.

## 6. Cenários futuros para critérios de aceite — NÃO EXECUTADOS

| Caso | Comportamento que futuramente deve ser verificado |
|---|---|
| ATD-T01 | Humano assume com mensagem IA preparada: envio ao cliente bloqueado |
| ATD-T02 | Cliente pede humano e depois desiste antes de aceite: retorno IA condicionado; após aceite humano, só devolução humana |
| ATD-T03 | Nova mensagem em fila: posição FIFO e instante de entrada preservados |
| ATD-T04 | Desistência e novo pedido: última posição da fila sem perda de histórico |
| ATD-T05 | Recusa de reserva: capacidade liberada e distribuição segura |
| ATD-T06 | Distribuições concorrentes: não superar limite individual/equipe |
| ATD-T07 | Desconexão breve e retorno: não redistribuir |
| ATD-T08 | Desconexão longa sem disponível: fila/alerta, sem encerrar conversa |
| ATD-T09 | Transferência entre números, destino indisponível: origem permanece responsável |
| ATD-T10 | IA tenta destino não permitido: negar e auditar |
| ATD-T11 | Aviso ao cliente entregue, destino sem aceite: transferência ainda pendente |
| ATD-T12 | Usuário de uma equipe acessa conversa de outra sem permissão: negar |
| ATD-T13 | Fluxo v1 ativo quando publica v2: preservar versão em andamento |
| ATD-T14 | Simulador de chatbot tenta criar lead real: não realizar efeitos externos |
| ATD-T15 | IA 24/7 e humano sem expediente: pedido de humano pendente, sem prometer atendimento imediato |
| ATD-T16 | Cliente reabre conversa resolvida: histórico e SLA coerentes |

## 7. Próxima decisão

**CRM-REV-04 foi aprovada como base documental** das 28 regras identificadas conforme [decisão](CRM-DEC-aprovacao-REV-04-base.md); os 14 itens ATD-P01..14 continuam abertos. **Não** aprovar por inferência o provedor, preço de WhatsApp, schemas, números finais de timeout, regras externas vigentes da Meta, estado técnico exato, novas permissões, fluxos executáveis ou cronograma.

A [CRM-REV-05 — Chatbot, Fluxos, Gatilhos, Condições e Automações](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md) foi redigida como **minuta para revisão**, sem duplicação do Motor Central. **P0 global, INT-P0, IN-168 e gates G01–G06 seguem abertos**; desenvolvimento somente após aprovação integral e final do planejamento.
