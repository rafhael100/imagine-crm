# 09 — CRM Básico Cortesia: Agenda, Agendamentos e revisão de automações

> 2026-11-01 — **IN-004 APROVADA: incluir módulos Agenda e Agendamentos no Básico Cortesia** de contratantes elegíveis de Onepage ou Landing Page.
> **IN-005 APROVADA em 2026-11-01:** modelos prontos, até **3 automações comerciais ativas simultaneamente por empresa** no Básico Cortesia. Catálogo exato e detalhes da agenda ainda sob avaliação.
> **IN-006 — diretriz de catálogo:** inventariar toda a biblioteca antes de revisar as quotas de todos os planos; modelos avançados deverão ser elegíveis somente a planos pagos. As **3 ativas** do IN-005 são referência atual, sujeita a **nova aprovação** na revisão final.
> **IN-007 APROVADA em 2026-11-01:** Básico Cortesia terá **Agenda e Agendamentos apenas internos e manuais**; autoagendamento público por site, link, página do CRM ou visitante fica reservado aos **planos pagos**.
> **IN-008, IN-009 e IN-011 APROVADAS em 2026-11-01:** somente **1 profissional com agenda própria e até 10 serviços ativos por empresa**; expediente semanal flexível, pausas, bloqueios e agendamento excepcional fora do expediente mediante confirmação de usuário autorizado, sempre sem conflitos. **IN-012 aprovou agendamentos manuais sem teto comercial mensal, sujeitos a uso justo**; quotas de usuários e notificações externas ainda pendentes; ver [11 — Agenda manual](11-agenda-manual-basico-cortesia.md).
> Referência: `docs/04-empresas-e-usuarios.md` (especificação completa, funcionalidade geral CRM aprovada) e `docs/02-planos-trial-e-assinaturas.md` (planos pagos).

## 1. Decisão IN-004 aprovada

- O benefício gratuito para contratantes de site inclui acesso à **agenda do CRM** e ao **registro/gerenciamento de agendamentos**, em complemento aos módulos de captação de leads, contatos e funil simples.
- O cliente poderá acompanhar compromissos associados a leads e contatos. Regras básicas necessárias ao funcionamento seguro (validação de disponibilidade e conflitos, isolamento por tenant, histórico e cancelamento/reagendamento autorizado) devem existir independentemente do plano.
- **IN-007 APROVADA:** Agenda/Agendamentos no Básico são internos e manuais, sem página pública ou autogeração de reserva por visitantes; autoagendamento pelo site fica restrito aos planos pagos. **Pendente:** número de usuários/profissionais/serviços/unidades, quotas mensais, integrações Google/Outlook e videoconferência, agendamentos em grupo/recorrentes, notificações externas, lista de espera e relatórios avançados.
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
- **IN-013 APROVADA:** avisos e lembretes **internos no CRM** são operações do módulo agenda e **não consomem quota de automações comerciais** no Básico. E-mail automático ao cliente e WhatsApp oficial ficam para planos pagos ou adicionais elegíveis, sujeitos a condições de canal/cobrança.

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

**IN-007 substitui a proposta anterior:** Básico terá **agenda interna funcional e agendamentos manuais**; página pública, links de autoagendamento e reserva criada pelo visitante são exclusividade de planos pagos. Propostas de detalhes: definir serviço, expediente/folgas/bloqueios, disponibilidade, editar/reagendar/cancelar, vínculo ao lead/contato e histórico. **IN-008 e IN-009 já estabeleceram 1 profissional e 10 serviços ativos por empresa; IN-011 já aprovou as regras de expediente e exceções manuais.**

**IN-005 APROVADA:** permitir **até 3 automações comerciais ativas por empresa**, escolhidas de biblioteca de **modelos prontos**. **O número de modelos disponíveis e os parâmetros exatos estão sob proposta**, sem aprovação nesta etapa. Sugestões iniciais:

1. Novo lead no formulário Imagine → notificação dentro do CRM e tarefa inicial de contato.
2. Lead sem interação por período configurado → lembrete de acompanhamento.
3. Oportunidade sem movimentação por período configurado → alerta na interface.

Os lembretes/avisos operacionais indispensáveis à agenda podem ser considerados **automações de sistema**, não contados como regra comercial configurável, evitando que um usuário fique sem aviso do próprio agendamento. A comunicação externa por e-mail e WhatsApp requer política/custos/consentimento separados.

**Por que não liberar tudo de imediato?** O Básico cortesia deve entregar valor imediato sem sacrificar diferenciação dos planos pagos. Distribuição/redistribuição por equipes, construtor genérico de regras, scoring configurável/avançado, integrações oficiais de WhatsApp, agendamentos em grupo, sincronização múltipla de calendários, automações por proposta e relatórios programados podem justificar o Essencial/Profissional/Empresarial.

**IN-013/IN-014 aprovados:** avisos e lembretes de agenda com padrões 24h e 1h, ajustáveis individualmente, somente internos no CRM Básico, sem consumir vagas de automações; e-mails automáticos ao cliente/WhatsApp Business API restritos a planos pagos ou adicionais elegíveis. **Já aprovado:** até 3 automações comerciais ativas de modelos prontos (IN-005) e **IN-007: agendamento manual/interno no Básico, público/automático só em planos pagos**. **Ainda não aprovados:** catálogo exato de modelos, parâmetros/editabilidade, política técnica de uso justo de agendamentos manuais (IN-012), Google/Outlook, lembretes externos e-mail/WhatsApp, usuários, número de serviços/profissionais e o tratamento de notificações operacionais de sistema.

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
| T04 | Compromisso próximo | Proximidade de agendamento → aviso interno no CRM | **IN-013 a IN-015:** 24h/1h ajustáveis, com aviso persistente lido/não lido mesmo após o usuário retornar | Operação de sistema, não template comercial | **Não conta como automação ativa** |
| T05 | Atendimento realizado: próximo passo | Agendamento marcado como realizado | Criar tarefa opcional de acompanhamento comercial vinculada ao lead/contato | Atraso da tarefa e serviço selecionado | 1 ativa, se aprovada no catálogo |

**Os cinco exemplos não estão aprovados.** A aprovação do IN-005 permite escolher de modelos prontos **até três ativos ao mesmo tempo**; não limita a biblioteca a apenas três templates. Um template não pode enviar mensagens automáticas a visitantes por WhatsApp oficial sem produto/canal habilitado, aceite e política de custos. Nenhum modelo de rotina agenda deve emitir cobrança de agendamento, recurso rejeitado para o CRM.

### Regras de execução recomendadas (não aprovadas individualmente)

1. Cada instância tem `tenant_id`, `template_id`, `template_version`, `config`, `status`, `last_run`; ativação transacional confere 3 slots sem condições de corrida.
2. Evento de origem inclui ID único, versão e dados mínimos; fila/outbox com retry/backoff e trilha de execuções.
3. Tarefa/alerta duplicado em replay é proibido por chave idempotente. Nova interação encerra/atualiza o relógio de inatividade.
4. Suspender, editar parâmetros e reativar requer permissão administrativa da empresa. Modelo removido de catálogo não deve parar silenciosamente instâncias existentes.
5. Critérios de uso justo contra abuso de formulários e automações não devem bloquear o recebimento de contatos legítimos sem mensagem de erro apropriada.
6. Contagem e quota de uso da API externa, quando habilitada, são regras diferentes do número de 3 automações ativas.

**IN-015 APROVADA:** a central da agenda Básico persiste notificações e lembretes dentro do CRM com status lido/não lido, inclusive se vencerem durante ausência da sessão. **IN-016 APROVADA:** abrir os detalhes de um aviso marca aquele aviso como lido, e o usuário pode marcá-lo novamente como não lido. Simples visualização da lista não muda o status. **IN-017 aprovou filtros por estado (Todas, Não lidas e Lidas), tipo de evento e período, com ordenação das notificações mais recentes primeiro**. **IN-018 APROVADA:** a central do Básico retém notificações internas por 90 dias, sem afetar compromissos e auditoria. **IN-019 APROVADA:** arquivar/restaurar lidas e não lidas dentro dos 90 dias sem alterar leitura; avisos de todos os estados deixam a central aos 90 dias, preservando compromissos e auditoria. **Pendentes:** pesquisa textual, ações em lote, descarte técnico e detalhes de UX. Não se trata de push externo, e-mail ou WhatsApp; não consome automações comerciais.

**IN-020 APROVADA:** o Básico Cortesia permite **um único usuário autenticado por organização**, independente de seu **único profissional agendável (IN-008)**. O usuário com login pode ser o titular da agenda ou operador de outro profissional, dependendo da matriz de papéis a ser aprovada. A ativação do 2º usuário deve mostrar limite 1/1 e upgrade elegível sem contratação automática (IN-010). **IN-021 APROVADA:** o único usuário possui perfil Administrador da Empresa, MFA obrigatório e direitos somente às funcionalidades incluídas no Básico. Permissões por módulo, troca de titular, convites e desativação seguem em especificação. **IN-022 APROVADA:** o módulo Administração da Empresa e Segurança permite editar cadastro comum, alterar CNPJ apenas após validação específica, gerir senha/e-mail/MFA com verificação de identidade, consultar/encerrar sessões, ver plano e contratar upgrade voluntário; sem segundo usuário ou acesso global. **IN-023 APROVADA:** Leads e Contatos com consulta, cadastro/edição manual, busca, notas/histórico, alerta de duplicidade sem mesclagem e arquivamento com histórico preservado; quotas, importação, exportação e exclusão pendentes. Ver [13 — Matriz por módulo](13-matriz-permissoes-basico-cortesia.md).

## 4C. Agenda Básico Cortesia — modo manual APROVADO (IN-007)

**Decisão:** o cliente do **Básico Cortesia** poderá registrar, editar, reagendar, concluir e cancelar agendamentos **manualmente**, dentro do CRM e segundo suas permissões. Visitantes do Onepage/Landing Page **não poderão escolher horários ou registrar reservas automaticamente** por link, página pública, widget, API de agendamento ou chatbot conectado ao benefício. O autoagendamento público é **exclusivo dos planos pagos** Essencial, Profissional e Empresarial, condicionado à verificação do plano e à habilitação autorizada pela empresa.

### Jornada no Básico — manual e interna

1. Visitante entra em contato pelo formulário de lead ou WhatsApp do site. Formulário apenas registra lead; **não agenda**.
2. Empresa combina manualmente data/hora por seu canal de contato.
3. Usuário autenticado e autorizado entra no CRM, seleciona lead/contato, serviço e horário, confere disponibilidade e **salva** agendamento.
4. Backend valida conflitos, estados, autorização e limites; registra criação e histórico. No CRM, usuário poderá editar, remarcar, cancelar, marcar como realizado/falta e administrar calendário dia/semana/mês no escopo permitido.
5. Modelos de automação comercial permitidos ao Básico (IN-005) podem ser acionados por eventos legítimos da agenda **sem conceder ao visitante permissão para criar reservas automaticamente**.

### Jornada em plano pago — agendamento público

1. Empresa com plano pago efetivo habilita página/link de reservas e autoriza eventual inclusão de botão no site.
2. Visitante escolhe serviço e horário disponível; informa dados com privacidade; CRM verifica disponibilidade novamente em transação e cria/associa o agendamento ao lead/contato, impedindo concorrência e reservas duplicadas.
3. A API valida o **entitlement do plano em cada requisição pública de disponibilidade e criação**, não só ocultando o botão do site.
4. Mudança do plano pago para Básico **bloqueia novas reservas públicas**, mas preserva compromissos/histórico e permite administração manual. O site deve retirar/desabilitar CTA de forma controlada e autorizada, sem inventar sucesso nem alterar publicação sem fluxo de aprovação pertinente.
5. O sistema não processa pagamentos de serviços agendados, conforme rejeição registrada no módulo geral de CRM.

### Matriz específica aprovada

| Função | Básico Cortesia | CRM Essencial/Profissional/Empresarial |
| --- | --- | --- |
| Calendário interno e registro de compromissos | SIM | SIM |
| Criar, consultar, editar, reagendar e cancelar manualmente | SIM | SIM |
| Relacionar compromisso a lead/contato | SIM | SIM |
| Verificação técnica de disponibilidade, conflitos e histórico | SIM | SIM |
| Página/link público de autoagendamento | **NÃO** | **SIM** (conforme plano habilitado) |
| Visitante realizar reserva pelo site sem atuação do usuário CRM | **NÃO** | **SIM** (conforme configuração e direitos) |
| Gerar agendamento automaticamente ao enviar formulário de lead | **NÃO** | **Apenas por fluxo de agendamento autorizado; formulário de lead não implica reserva** |
| Integrações Google/Outlook, múltiplos profissionais e unidades | PENDENTE; não concedidas por IN-007 | Conforme produto e direitos pagos |
| Limite mensal de agendamentos | A definir para Básico | Ilimitados já aprovados para planos pagos |

**IN-008 aprovou 1 profissional, IN-009 aprovou até 10 serviços ativos e IN-020 aprovou até 1 usuário autenticado por empresa**, quota separada do profissional. **Pendentes:** perfil e permissões do usuário único, troca de titular de acesso, unidades no Básico; limites técnicos de uso justo (sem teto comercial mensal, IN-012); limites de armazenamento; notificações internas ou externas; confirmação por e-mail/WhatsApp; integração Google/Outlook; calendário de equipe; modalidades coletivas/recorrentes. A quota de 3 automações comerciais do Básico (IN-005) é **independente** das operações obrigatórias de registrar e validar agenda.


### Decisão posterior IN-006 — biblioteca central e revisão de quotas no fim do planejamento

Construir primeiro o inventário completo, documentar modelos, dependências, custos e elegibilidade por plano. A biblioteca terá modelos comuns e modelos avançados propostos para planos pagos; não liberar todo o catálogo por padrão ao Básico. **Não discutir nem mudar agora a quantidade de slots** (Básico 3, Essencial 5, Profissional 20, Empresarial 50), pois o responsável pediu revisão conjunta somente após fechar a documentação da biblioteca. As quantidades anteriores continuam registradas e não foram revogadas automaticamente.

**Documento principal:** [10 — Biblioteca de Automações e critérios de planos](10-biblioteca-automacoes-e-criterios-de-planos.md).

## 5. Próximas decisões em sequência

1. **IN-005 definida:** modelos prontos, até 3 ativas por empresa; ainda aprovar quais modelos e personalizações disponibilizar.
2. Definir regras de edição, pausa/reativação, exclusão e downgrade dos modelos; não permitir editor universal no Básico sem revisão.
3. **IN-007 e IN-008 decidiram:** agenda Básico interna/manual, apenas 1 profissional; agendamento público pelo site apenas nos planos pagos. Detalhar serviços, horários e quotas do modo manual em [11 — Agenda manual](11-agenda-manual-basico-cortesia.md).
4. Definir notificações: dentro do CRM, e-mail transacional, WhatsApp API; custos externos e titular do canal.
5. Definir política de retenção, armazenamento e uso justo do Básico e recursos que exigem upgrade.
6. Testar captação de lead → agenda → lembrete/ação → upgrade e retorno, sem perda de registros nem cobrança indevida.

**Situação:** IN-004 aprovou Agenda e Agendamentos; **IN-005 aprovou biblioteca de modelos prontos e até três automações comerciais ativas por empresa no Básico**; **IN-006 orienta diferenciar a biblioteca por plano e revisar todas as quotas apenas ao fim da especificação**; **IN-007 definiu agenda somente manual no Básico e autoagendamento público exclusivo dos planos pagos**; **IN-008 definiu 1 profissional agendável; IN-009 definiu até 10 serviços ativos; IN-011 definiu horários flexíveis; IN-012 definiu agendamentos manuais sem teto comercial mensal, com uso justo**. Catálogo específico, parâmetros e funcionalidades detalhadas da agenda permanecem em análise. Nenhuma programação autorizada.
