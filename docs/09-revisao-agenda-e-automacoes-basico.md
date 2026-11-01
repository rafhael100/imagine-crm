# 09 — CRM Básico Cortesia: Agenda, Agendamentos e revisão de automações

> 2026-11-01 — **IN-004 APROVADA: incluir módulos Agenda e Agendamentos no Básico Cortesia** de contratantes elegíveis de Onepage ou Landing Page.
> **Escopo fino, quotas, integração com calendários e quais automações comerciais liberar: EM ANÁLISE, NÃO APROVADOS.**
> Referência: `docs/04-empresas-e-usuarios.md` (especificação completa, funcionalidade geral CRM aprovada) e `docs/02-planos-trial-e-assinaturas.md` (planos pagos).

## 1. Decisão IN-004 aprovada

- O benefício gratuito para contratantes de site inclui acesso à **agenda do CRM** e ao **registro/gerenciamento de agendamentos**, em complemento aos módulos de captação de leads, contatos e funil simples.
- O cliente poderá acompanhar compromissos associados a leads e contatos. Regras básicas necessárias ao funcionamento seguro (validação de disponibilidade e conflitos, isolamento por tenant, histórico e cancelamento/reagendamento autorizado) devem existir independentemente do plano.
- **Pendente decidir** alcance da página pública de autoagendamento/incorporação no Onepage, número de usuários/profissionais/serviços/unidades, marcações mensais, integração Google/Outlook, Meet/Teams/Zoom, agendamentos em grupo/recorrentes, notificações externas, lista de espera e relatórios avançados.
- O CRM geral já tem agenda e agendamentos **aprovados como produto**; liberação no Básico não significa habilitar automaticamente **todas** as funcionalidades e integrações do módulo no benefício.
- Nos planos **Essencial, Profissional e Empresarial** os agendamentos mensais são ilimitados (aprovado). Não transportar essa decisão para o Básico sem aprovação própria.
- **Pagamentos de agendamentos** no CRM (checkout, sinal, Pix/cartão de clientes das empresas) continuam **rejeitados** para o produto, inclusive o Básico. Assinaturas do CRM/site são contratos separados.

## 2. Automações já aprovadas no CRM geral — inventário funcional

### 2.1 Construtor de regras comerciais (Capítulo 04 — seção "Automações comerciais")

- Criar automações sem código, com gatilhos, condições e ações, ativar/pausar/desativar.
- **Gatilhos:** lead criado; alteração de etapa de oportunidade; tarefa vencida; proposta aceita ou recusada.
- **Condições:** responsável, etapa, valor, campos personalizados.
- **Ações:** criar tarefa, atribuir responsável, notificar usuário e atualizar campo.
- Execução imediata ou após prazo configurado; histórico/auditoria, sem loops e com idempotência.

### 2.2 Prazo e inatividade

- Lead sem contato após X horas/dias → tarefa/lembrete.
- Oportunidade parada na etapa após X dias → notificação ao vendedor/gestor.
- Proposta sem resposta após X dias → lembrete.
- A interação subsequente reinicia a contagem; deduplicar alertas.

### 2.3 Distribuição e redistribuição

- Atribuição automática por rodízio, menor carga, equipe, origem/campanha, vendedor fixo.
- Redistribuir lead não atendido após prazo, alertando responsável e respeitando exceções.
- **Dependência:** mais de um usuário/vendedor habilitado para rodízio ou redistribuição; com 1 usuário sugerido para Básico, várias dessas regras não oferecem ganho.

### 2.4 Lead Scoring por regras

- Pontuação automática de 0–100 por origem, perfil, interações e inatividade.
- Faixas padrão aprovadas: Frio **0–39**, Morno **40–69**, Quente **70–100**; personalizáveis por empresa.
- Histórico/gráfico e uso em regras de atribuição/tarefas/notificações. Não depende obrigatoriamente de IA.

### 2.5 Formulários de lead, CRM e acompanhamento

- Formulários públicos criados no CRM, com origem, campanhas, proteção antispam, consentimento e duplicidades.
- Integração com regras de distribuição, scoring e automações.
- Formulários dos Onepages/Landing Pages da Imagine direcionarão leads para o CRM via integração servidor-servidor com enfileiramento (IN-001), sem expor credenciais no site.

### 2.6 Agenda e agendamentos

- Confirmações, lembretes, reagendamentos e cancelamentos, com histórico de tentativas.
- Notificações internas; e-mail externo/WhatsApp apenas conforme serviços autorizados e custos/canais disponíveis.
- Uma página pública de autoagendamento, Google/Outlook, videoconferência, grupo, lista de espera e outras extensões foram aprovadas no **CRM geral**, não especificamente para o Básico.
- O envio automático de confirmações/lembretes operacionais do módulo agenda é distinto de permitir ao cliente configurar **automações comerciais genéricas**; definir se usa quotas separadas, a fim de não bloquear lembretes essenciais por exceder limite de fluxos.

### 2.7 Relatórios agendados

- Relatórios periódicos diários/semanais/mensais, com filtros, permissões, formatos e envio por link seguro. Recurso do CRM geral, não necessariamente automação comercial no limite de 5/20/50.

### 2.8 WhatsApp Business API e chatbot

- Há integrações com inbox, atribuição de conversas, horários/ausência, SLA, chatbot sem código, gatilhos, automações e chatbot-agenda.
- APIs oficiais, templates e mensagens podem gerar custos externos. Botão/link WhatsApp no site pode existir sem essas integrações.
- Não confundir **fluxos ativos do chatbot** com **automações comerciais ativas** (quotas separadas dos planos pagos: 3/10/30 fluxos versus 5/20/50 automações).

## 3. Limites preexistentes dos planos pagos — não aplicar automaticamente ao Básico

| Plano | Automações comerciais simultâneas | Fluxos de chatbot WhatsApp |
| --- | ---: | ---: |
| Essencial | 5 | 3 |
| Profissional | 20 | 10 |
| Empresarial | 50 | 30 |
| Básico Cortesia | **A definir** | **A definir; não presume inclusão** |

Rascunhos de automações não consomem quota; no downgrade, preservar configurações e pausar excedentes, sem apagar histórico. O **trial público de 14 dias tem 2 automações**, mas isso não define o benefício do Básico e não constitui precedente automático.

## 4. Recomendação para a matriz do Básico (não aprovada)

Propor **agenda interna funcional + agendamento manual + página pública simples de agendamento ligada ao Onepage**, sujeita a decisão específica. Funcionalidades iniciais sugeridas: configurar um serviço e sua duração, expediente/folgas/bloqueios, disponibilidade, agendar/reagendar/cancelar, vincular ao contato/lead, evitar conflitos, histórico e link de autoagendamento; integrações externas avançadas e multiequipe ficariam nos planos pagos. **Um serviço e um profissional são apenas exemplos, não limites aprovados.**

Para automações, propor começar com **até 3 automações comerciais ativas por empresa**, de uma biblioteca de modelos limitados e sem editor visual totalmente livre no benefício, por exemplo:

1. Novo lead no formulário Imagine → notificação dentro do CRM e tarefa inicial de contato.
2. Lead sem interação por período configurado → lembrete de acompanhamento.
3. Oportunidade sem movimentação por período configurado → alerta na interface.

Os lembretes/avisos operacionais indispensáveis à agenda podem ser considerados **automações de sistema**, não contados como regra comercial configurável, evitando que um usuário fique sem aviso do próprio agendamento. A comunicação externa por e-mail e WhatsApp requer política/custos/consentimento separados.

**Por que não liberar tudo de imediato?** O Básico cortesia deve entregar valor imediato sem sacrificar diferenciação dos planos pagos. Distribuição/redistribuição por equipes, construtor genérico de regras, scoring configurável/avançado, integrações oficiais de WhatsApp, agendamentos em grupo, sincronização múltipla de calendários, automações por proposta e relatórios programados podem justificar o Essencial/Profissional/Empresarial.

**Ainda não aprovados:** número de 3 automações, modelos disponíveis, agenda pública, cota de agendamentos, sincronização Google/Outlook, lembretes, e-mail/WhatsApp, usuários, número de serviços/profissionais, e quais ações são consideradas automações de sistema.

## 5. Próximas decisões em sequência

1. Definir se o Básico concede **somente automações de sistema para leads/agendamentos**, um catálogo restrito de automações comerciais ou o editor completo (com quota).
2. Definir quantidade de automações ativas e regras de modelos/downgrade, se forem liberadas.
3. Definir extensão da agenda Básico: **interna somente** ou **página pública de agendamento incorporada ao site**, com eventuais limites (serviços, profissionais, reservas).
4. Definir notificações: dentro do CRM, e-mail transacional, WhatsApp API; custos externos e titular do canal.
5. Definir política de retenção, armazenamento e uso justo do Básico e recursos que exigem upgrade.
6. Testar captação de lead → agenda → lembrete/ação → upgrade e retorno, sem perda de registros nem cobrança indevida.

**Situação:** IN-004 aprovada no nível dos módulos Agenda e Agendamentos. Catálogo/matriz de automações Básico em revisão, sem aprovação da configuração sugerida. Nenhuma programação autorizada.
