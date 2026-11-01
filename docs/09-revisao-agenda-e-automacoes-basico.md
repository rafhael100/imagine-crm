# 09 — CRM Básico Cortesia: Agenda, Agendamentos e revisão de automações

> 2026-11-01 — **IN-004 APROVADA: incluir módulos Agenda e Agendamentos no Básico Cortesia** de contratantes elegíveis de Onepage ou Landing Page.
> **IN-005 APROVADA em 2026-11-01:** modelos prontos, até **3 automações comerciais ativas simultaneamente por empresa** no Básico Cortesia. Catálogo exato e detalhes da agenda ainda sob avaliação.
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
| Básico Cortesia | **Até 3, somente de modelos prontos (IN-005)** | **Não incluído por IN-005; eventual liberação exige decisão própria** |

Rascunhos de automações não consomem quota; no downgrade, preservar configurações e pausar excedentes, sem apagar histórico. O **trial público de 14 dias tem 2 automações**, mas isso não define o benefício do Básico e não constitui precedente automático.

## 4. Recomendação para a matriz do Básico (não aprovada)

Propor **agenda interna funcional + agendamento manual + página pública simples de agendamento ligada ao Onepage**, sujeita a decisão específica. Funcionalidades iniciais sugeridas: configurar um serviço e sua duração, expediente/folgas/bloqueios, disponibilidade, agendar/reagendar/cancelar, vincular ao contato/lead, evitar conflitos, histórico e link de autoagendamento; integrações externas avançadas e multiequipe ficariam nos planos pagos. **Um serviço e um profissional são apenas exemplos, não limites aprovados.**

**IN-005 APROVADA:** permitir **até 3 automações comerciais ativas por empresa**, escolhidas de biblioteca de **modelos prontos**. **O número de modelos disponíveis e os parâmetros exatos estão sob proposta**, sem aprovação nesta etapa. Sugestões iniciais:

1. Novo lead no formulário Imagine → notificação dentro do CRM e tarefa inicial de contato.
2. Lead sem interação por período configurado → lembrete de acompanhamento.
3. Oportunidade sem movimentação por período configurado → alerta na interface.

Os lembretes/avisos operacionais indispensáveis à agenda podem ser considerados **automações de sistema**, não contados como regra comercial configurável, evitando que um usuário fique sem aviso do próprio agendamento. A comunicação externa por e-mail e WhatsApp requer política/custos/consentimento separados.

**Por que não liberar tudo de imediato?** O Básico cortesia deve entregar valor imediato sem sacrificar diferenciação dos planos pagos. Distribuição/redistribuição por equipes, construtor genérico de regras, scoring configurável/avançado, integrações oficiais de WhatsApp, agendamentos em grupo, sincronização múltipla de calendários, automações por proposta e relatórios programados podem justificar o Essencial/Profissional/Empresarial.

**Já aprovado:** até 3 automações comerciais ativas de modelos prontos (IN-005). **Ainda não aprovados:** catálogo exato de modelos, parâmetros/editabilidade permitidos, agenda pública, cotas de agendamentos, sincronização Google/Outlook, lembretes externos e-mail/WhatsApp, usuários, número de serviços/profissionais e o tratamento de notificações operacionais de sistema.

## 4A. Decisão IN-005 — Modelos prontos e 3 automações ativas (APROVADA)

- **Beneficiário:** organização/empresa elegível ao CRM Básico cortesia do Onepage ou futura Landing Page Imagine, não cada usuário nem cada formulário.
- **Mecanismo:** escolher e ativar automações a partir de **modelos homologados** de uma biblioteca controlada. O Básico não recebe automaticamente o editor genérico de gatilhos, expressões, condições arbitrárias, webhooks ou executores de código.
- **Limite:** no máximo **3 automações comerciais em estado `active` simultaneamente por empresa**; um modelo ativo corresponde a uma automação configurada, ainda que a mesma automação execute em muitos leads. Rascunhos/pausadas não consomem slots ativos, consistente com o tratamento de rascunhos no CRM geral.
- **Histórico e segurança:** toda execução registra gatilho, tenant, versão do template, ID do registro, horário, resultado/erro; idempotência por evento+automação, proteção contra loops, permissões, rate limit e orçamento operacional. Uma automação que atinja seus limites operacionais deverá suspender ações futuras e alertar o gestor, sem descartar lead ou apagar registros.
- **Upgrade:** ao migrar ao Essencial/Profissional/Empresarial, os modelos e históricos continuam; o conjunto de recursos/cotas passa a obedecer o plano pago efetivo. Retorno ao Básico após término do pago preserva configurações e pausa automações excedentes até seleção autorizada.
- **Não aprovado nesta decisão:** tipos exatos de modelo, valor padrão de prazo, número de disparos permitidos, limite de execuções/dia, parâmetros editáveis, e-mails/WhatsApp, custos de provedores, regras de aviso e quantidade de usuários do benefício.
- **Avisos operacionais da agenda** (mudança de horário, conflito, histórico e notificações dentro do sistema) são distintos de uma automação comercial que o cliente escolhe ativar. O que fica fora da quota das 3 ainda deve ser explicitado ao finalizar os requisitos de agenda.

## 4B. Catálogo inicial detalhado de modelos (PROPOSTO PARA APROVAÇÃO)

| ID | Nome comercial sugerido | Gatilho | Condições e ações | Parâmetros que o usuário poderia ajustar | Contagem |
| --- | --- | --- | --- | --- | --- |
| T01 | Novo lead: não deixar sem resposta | Novo lead validado via formulário/site e salvo no CRM | Criar tarefa de primeiro contato e mostrar notificação interna ao administrador | Prioridade da tarefa, prazo em minutos/horas, origem de formulário (opcional) | 1 ativa |
| T02 | Lead aguardando contato | Lead sem interação comercial válida após prazo | Alertar no CRM e/ou criar tarefa de acompanhamento se não existir outra pendente equivalente | Atraso configurável (horas/dias) e habilitar notificação interna | 1 ativa |
| T03 | Oportunidade parada no funil | Oportunidade aberta sem mudança de etapa/interação por período | Alerta interno e/ou tarefa de retorno | Dias de inatividade, etapas selecionadas | 1 ativa |
| T04 | Compromisso próximo | Agendamento futuro válido, ainda não cancelado | Notificar internamente a empresa; comunicação ao cliente por e-mail dependeria de aprovação técnica/comercial | Antecedência e canal habilitado | **Decidir se regra de sistema ou automação comercial** |
| T05 | Atendimento realizado: próximo passo | Agendamento marcado como realizado | Criar tarefa opcional de acompanhamento comercial vinculada ao lead/contato | Atraso da tarefa e serviço selecionado | 1 ativa, se aprovada no catálogo |

**Os cinco exemplos não estão aprovados.** A aprovação do IN-005 permite escolher de modelos prontos **até três ativos ao mesmo tempo**; não limita a biblioteca a apenas três templates. Um template não pode enviar mensagens automáticas a visitantes por WhatsApp oficial sem produto/canal habilitado, aceite e política de custos. Nenhum modelo de rotina agenda deve emitir cobrança de agendamento, recurso rejeitado para o CRM.

### Regras de execução recomendadas (não aprovadas individualmente)

1. Cada instância tem `tenant_id`, `template_id`, `template_version`, `config`, `status`, `last_run`; ativação transacional confere 3 slots sem condições de corrida.
2. Evento de origem inclui ID único, versão e dados mínimos; fila/outbox com retry/backoff e trilha de execuções.
3. Tarefa/alerta duplicado em replay é proibido por chave idempotente. Nova interação encerra/atualiza o relógio de inatividade.
4. Suspender, editar parâmetros e reativar requer permissão administrativa da empresa. Modelo removido de catálogo não deve parar silenciosamente instâncias existentes.
5. Critérios de uso justo contra abuso de formulários e automações não devem bloquear o recebimento de contatos legítimos sem mensagem de erro apropriada.
6. Contagem e quota de uso da API externa, quando habilitada, são regras diferentes do número de 3 automações ativas.

## 4C. Agenda Básico Cortesia — desenho recomendado (AINDA PENDENTE)

**Núcleo já autorizado por IN-004:** uma agenda para registrar, visualizar, criar, reagendar e cancelar compromissos com lead/contato; calendário dia/semana/mês; prevenção de conflitos, bloqueios de horários, estados de reserva e trilha auditada. A política exata dos limites ainda não está aprovada.

**Aprovação necessária 1 — onde o cliente agenda:**
- **A. Apenas CRM interno:** o empresário cadastra os agendamentos manualmente; o visitante do Onepage pode apenas pedir contato via formulário/WhatsApp.
- **B. CRM interno + link/página pública de autoagendamento (recomendado):** botões no Onepage/Landing Page levam à página segura de reserva do CRM, com serviços/horários disponíveis. O visitante confirma o horário sem login CRM; o agendamento gera/vincula lead/contato e é exibido na agenda. Esse recurso precisa estar incluído especificamente no Básico.
- **C. Opção B + calendário externo Google/Outlook:** acrescenta OAuth e sincronização de bloqueios, edição, timezone e falhas; sugere-se deixar para planos pagos inicialmente ou aprovar em decisão adicional.

**Aprovação necessária 2 — capacidade comercial:** número de usuários/profissionais com agenda, unidades, serviços cadastráveis, agendamentos mensais e capacidade por horário. Sugestão técnica inicial: **1 usuário, 1 profissional, 1 unidade e até 2 serviços; agendamentos mensais sem teto rígido sujeito a uso justo**. São exemplos, não decisão aprovada; não copiar os agendamentos ilimitados já aprovados para planos pagos.

**Aprovação necessária 3 — recursos operacionais essenciais:** horários de expediente, duração e intervalos, antecedência mínima/máxima, bloqueios, antifraude contra reservas em massa, link seguro para cancelar/reagendar, estados confirmado/realizado/cancelado/não compareceu, notificações internas, timezone/horário de verão, logs de alteração. Não exigir API oficial de WhatsApp para publicar uma página de reservas.

**Aprovação necessária 4 — comunicações:** aviso interno de novo agendamento e alterações no CRM; confirmações para visitante por e-mail transacional se autorizadas/configuradas; WhatsApp por **link do visitante** pode ser oferecido sem API, mas não equivale a mensagem automática. Mensagens de WhatsApp Business API oficial permanecem serviço opcional sujeito a condições comerciais e políticas da Meta.

**Não incluir automaticamente:** integração Google/Outlook, geração de links Meet/Teams/Zoom, agenda de equipe, várias salas/profissionais/unidades, grupos, recorrências, lista de espera, relatórios avançados, formulários prévios personalizados, pagamentos de atendimentos, agentes de IA para gerir reservas ou fluxos de chatbot WhatsApp. Todas são decisões próprias, não consequência automática de IN-004.

**Ciclo de reserva de autoagendamento sugerido:** visitante seleciona serviço → vê agenda publicada e horários disponíveis → informa nome/telefone/e-mail com aviso de privacidade → servidor valida novamente horário em transação e cria/vincula lead/contato + reserva → retorna confirmação real e link de gestão com token restrito/expirável → empresário visualiza agenda e pode acompanhar o lead → alterações seguem disponibilidade e permissões. Conflitos, fusos, notificações e disponibilidade devem respeitar o mesmo motor de regras da agenda interna.


## 5. Próximas decisões em sequência

1. **IN-005 definida:** modelos prontos, até 3 ativas por empresa; ainda aprovar quais modelos e personalizações disponibilizar.
2. Definir regras de edição, pausa/reativação, exclusão e downgrade dos modelos; não permitir editor universal no Básico sem revisão.
3. Definir extensão da agenda Básico: **interna somente** ou **página pública de agendamento incorporada ao site**, com eventuais limites (serviços, profissionais, reservas).
4. Definir notificações: dentro do CRM, e-mail transacional, WhatsApp API; custos externos e titular do canal.
5. Definir política de retenção, armazenamento e uso justo do Básico e recursos que exigem upgrade.
6. Testar captação de lead → agenda → lembrete/ação → upgrade e retorno, sem perda de registros nem cobrança indevida.

**Situação:** IN-004 aprovou Agenda e Agendamentos; **IN-005 aprovou biblioteca de modelos prontos e até três automações comerciais ativas por empresa no Básico**. Catálogo específico, parâmetros e funcionalidades detalhadas da agenda permanecem em análise. Nenhuma programação autorizada.
