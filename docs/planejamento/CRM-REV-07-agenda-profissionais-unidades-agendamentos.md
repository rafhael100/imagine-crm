# CRM-REV-07 — Agenda, Profissionais, Unidades e Agendamentos

**Data:** 2026-11-03. **Estado atualizado:** **APROVADA COMO BASE DOCUMENTAL DE REVISÃO**, conforme [CRM-DEC de aceite](CRM-DEC-aprovacao-REV-07-base.md). Mantidos AGD-P01..18, AGD-D01..16 e as seis rejeições expressas. Não aprova schemas, contratos, provedores, preços nem implementação.
**Escopo:** AGD, serviços, profissionais, equipe, unidades, disponibilidade, reservas, notificações, agenda Básico e integrações; fronteiras com CRM-01/02/03/04/05/06 e INT.
**Restrições:** exclusivamente planejamento e documentação GitHub; sem programação, testes reais, integrações externas, cadastramento de clientes reais, cobrança, novas automações, implantação, criação de contas ou despesas.

## 1. Fontes históricas examinadas

- [Capítulo 04 — Empresas, usuários e módulos CRM](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) — numerosas seções de Agenda com marcações APROVADO e **REJEITADO**, inclusive revisões posteriores no mesmo capítulo
- [Capítulo 11 — Agenda Manual Básico e regras IN-007..032](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/11-agenda-manual-basico-cortesia.md) — limites aprovados e detalhes complementares ainda propostos
- [Capítulo 08 — escopo do Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/08-plano-basico-cortesia.md) e [Capítulo 13 — permissões do Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md)
- [CRM-REV-01 — Identidade e permissões](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md), [CRM-REV-02 — Planos](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md), [CRM-REV-03 — Dados](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [CRM-REV-04 — Atendimento](CRM-REV-04-atendimento-whatsapp-conversas.md), [CRM-REV-05 — Automações](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md), [INT-R01](integracoes/INT-R01-governanca-contratos-homologacao.md)

**Regra de precedência:** quando uma seção posterior **REJEITADO** substitui uma sugestão previamente aprovada (check-in pelo cliente e exceção prioritária na espera), **preservar a rejeição posterior**. Não reviver propostas superadas apenas por estarem em versão antiga.

## 2. Quadro de regras funcionais de referência — 42 itens de cotejo, não novas funcionalidades

| ID de revisão | Assunto | Regra identificada nas fontes |
|---|---|---|
| AGD-R01 | Agenda única por organização | Agenda integrada por empresa, com visões dia/semana/mês, histórico e vínculos a clientes, funcionários e serviço; tarefas comerciais são objeto distinto. |
| AGD-R02 | Básico estritamente interno | No Básico: agenda só manual e interna; nenhum link público, botão/site/embed, chatbot ou API pública para cliente autoagendar. |
| AGD-R03 | Básico: limites | Uma agenda de profissional, até 10 serviços ativos e um usuário autenticado administrador da empresa, com MFA; profissional agendável não precisa ser usuário autenticado. |
| AGD-R04 | Básico: volume | Agendamentos manuais mensais sem teto comercial rígido, sujeitos a uso justo e antiabuso técnico, sem criar limite oculto. |
| AGD-R05 | Básico: horários | Expediente semanal flexível, múltiplos blocos diários, pausas e bloqueios manuais; reserva excepcional fora do expediente exige aviso e confirmação humana autorizada, sem ignorar conflitos. |
| AGD-R06 | Básico: avisos internos | Lembretes internos padrão 24h e 1h antes (cada um configurável/desativável); não consomem slots comerciais; não enviam automaticamente e-mail ou WhatsApp ao cliente. |
| AGD-R07 | Básico: central de notificações | Lida ao abrir detalhes, permite marcar novamente não lida; filtros por estado/tipo/período, ordenação recente, arquivar/restaurar em até 90 dias. |
| AGD-R08 | Básico: retenção de avisos | Avisos internos expiram da central após 90 dias, mesmo não lidos ou arquivados; isso não exclui agendamentos, históricos nem auditoria. |
| AGD-R09 | Serviços e durações | Serviço com nome/descrição, duração, buffer entre atendimentos, profissional habilitado, antecedência e regras de capacidade/modalidade/visibilidade. |
| AGD-R10 | Disponibilidade consistente | Revalidar horário, unidade, profissional, equipe, sala/equipamento, bloqueios, recorrência, capacidade e calendários externos ao confirmar, com proteção de concorrência. |
| AGD-R11 | Agenda pública apenas planos elegíveis | Empresa paga elegível pode oferecer página pública sem login CRM, com identidade visual, serviços/profissional/unidade, antifraude e links seguros de alteração. |
| AGD-R12 | Portal do cliente | Link pessoal de escopo limitado permite consultar próximos/histórico próprios e confirmar/reagendar/cancelar dentro das regras; não autoriza check-in autônomo. |
| AGD-R13 | Google Calendar e Outlook | Sync bidirecional autorizada; compromissos externos privados aparecem para terceiros apenas como Ocupado; revogação e falhas sinalizadas. |
| AGD-R14 | Videoconferência | Google Meet, Microsoft Teams e Zoom quando integrações permitem, com convites, atualização e falhas tratadas conforme capacidade/consentimento do provedor. |
| AGD-R15 | Múltiplas unidades | Unidades dentro da mesma empresa com jornadas, feriados, endereço, serviços, profissionais e recursos; permissões por unidade e conflitos entre recursos compartilhados. |
| AGD-R16 | Franquia de unidades | Essencial 1, Profissional 3, Empresarial 10 unidades incluídas conforme capítulo histórico; adicionais pagos e downgrade preserva cadastro/histórico, inativando excedentes. |
| AGD-R17 | Fusos e feriados | Empresas/profissionais podem ter fusos; armazenar instantes UTC com fuso IANA/horário local necessário à recorrência, tratar horários ambíguos e feriados por unidade. |
| AGD-R18 | Bloqueios e expediente especial | Bloqueios únicos/lote/recorrentes, feriados e horário excepcional por unidade; alertar conflito com reservas existentes, sem cancelá-las automaticamente. |
| AGD-R19 | Recorrência | Diária, semanal, quinzenal ou mensal com fim por data/ocorrências; validar cada ocorrência e permitir exceções sem perder histórico. |
| AGD-R20 | Sala/recurso | Salas, equipamentos e capacidade reservados junto do profissional quando necessário; impedir dupla reserva física, mesmo em grupos. |
| AGD-R21 | Grupos e participantes | Sessão coletiva com capacidade, participantes individuais, confirmação/presença/cancelamento individual; cancelar um participante não cancela os demais. |
| AGD-R22 | Lista de espera FIFO | Fila cronológica estrita, sem prioridade manual; oferta de vaga, prazo de resposta e reserva somente após aceite com revalidação transacional. |
| AGD-R23 | Confirmação separada | Estado de confirmação pendente/confirmado/não confirmado separado da situação operacional do agendamento; manual e link seguro convergem sem duplicar confirmação. |
| AGD-R24 | Sem cancelamento por silêncio por padrão | Falta de confirmação não libera vaga; cancelamento automático opcional só quando administrador configurar expressamente, com avisos/prazos/exceções. |
| AGD-R25 | Reagendamentos e transferência | Reagendar pelo portal ou equipe conforme direitos, preservando identificador e história; troca de unidade/profissional valida destino antes de liberar origem. |
| AGD-R26 | Serviços múltiplos | Reserva composta com dois ou mais serviços encadeados, profissionais/recursos e validação atômica de todas as etapas; cancelamento parcial conforme regra. |
| AGD-R27 | Profissionais alternativos/substituição | Substituição autorizada e disponibilidade real sem recriar a reserva; aceite do cliente quando serviço exigir; registrar profissional anterior/novo. |
| AGD-R28 | Dependentes e terceiros | Titular autorizado pode reservar para dependente/terceiro conforme serviço; histórico e privacidade próprios do atendido, sem poderes genéricos de representação. |
| AGD-R29 | Aprovação prévia por serviço | Pedido com aprovação mantém bloqueio temporário com expiração; solicitação não é confirmação; painel/fila de aprovação revalida cada caso individualmente. |
| AGD-R30 | Encaixes especiais | Usuário autorizado pode fazer encaixe excepcional com justificativa, sem deslocar reservas existentes nem ultrapassar capacidade física. |
| AGD-R31 | Status operacionais | Agendado, Aguardando confirmação, Confirmado, Aguardando atendimento, Em atendimento, Concluído, Não compareceu, Cancelado; confirmação e presença não se confundem. |
| AGD-R32 | Presença só por funcionário | Check-in/check-out e presença individual nos grupos são registrados apenas por funcionários autorizados; cliente não faz auto check-in. |
| AGD-R33 | Notificações a clientes | Lembretes/avisos por e-mail ou WhatsApp somente se canal contratado/permitido e com consentimentos/templates pertinentes; histórico de envio separado da entrega. |
| AGD-R34 | CSAT da agenda | Pesquisa 1–5 após serviço realizado com comentário opcional, diferente do CSAT de conversa WhatsApp; relatório por serviço/profissional/unidade. |
| AGD-R35 | Formulários prévios | Formulários por serviço, campos/arquivos e acesso protegidos, link com prazo, respostas vinculadas à reserva e ao contato. |
| AGD-R36 | Anexos | Documentos vinculados à reserva com validação, antimalware, permissões e quota de arquivos; não reduzir capacidade de dados estruturados. |
| AGD-R37 | Permissões e auditoria | Escopos por profissional/equipe/unidade/empresa; ações criar, ver, editar, cancelar, transferir e bloquear distintas; histórico protegido de alterações. |
| AGD-R38 | Atendimento e cancelamento por equipe | Cancelamento exige motivo; reagendamento mantém agendamento, verifica reserva destino atomicamente e só então libera horário anterior; avisar com canal elegível. |
| AGD-R39 | Operações internas de produtividade | Busca de clientes, cadastro rápido, calendário pessoal, painel diário, agendamento rápido, duplicação manual sem gerar recorrência e avisos opcionais por funcionário. |
| AGD-R40 | Capacidade e métricas | Painel de ocupação e relatórios por profissional/unidade/serviço e filtros autorizados, sem criar calendário paralelo. |
| AGD-R41 | Agenda paga ilimitada | Essencial, Profissional e Empresarial com agendamentos mensais sem teto comercial; limites técnicos de abuso não podem virar cobrança por reserva. |
| AGD-R42 | Sem checkout de serviços | Agendamentos não processam sinal, checkout, Pix, cartão nem pagamento do serviço do cliente; não altera cobrança da assinatura SaaS Imagine. |

## 3. Funcionalidades e comportamentos explicitamente rejeitados

| ID | Tema | Regra negativa que não pode reaparecer no desenvolvimento |
|---|---|---|
| AGD-N01 | Pagamento ligado ao agendamento | Não incluir checkout, sinal, Pix/cartão dos serviços dos clientes. Não confundir com assinatura CRM. |
| AGD-N02 | Check-in autônomo do cliente | Não disponibilizar chegada por portal/link; somente equipe autorizada. Decisão posterior prevalece sobre seção anterior. |
| AGD-N03 | Prioridade manual em lista de espera | Lista de espera deve ser FIFO estrita, sem VIP ou antecipação por gestor; decisão posterior revogou exceção antiga. |
| AGD-N04 | Favoritos pessoais | Não implementar favoritos de combinações pré-salvas no agendamento rápido. |
| AGD-N05 | Teto de reservas futuras por cliente | Não criar limite numérico global/por serviço; conflitos/recursos e sobreposição continuam aplicáveis. |
| AGD-N06 | Encerramento por horário decorrido | Não criar painel exclusivo para reservas passadas abertas ou conclusão automática só porque passou o horário. |

**Atenção especial:** a **lista de espera é FIFO estrita**, mesmo quando o atendimento admite encaixes excepcionais autorizados; não há classificação prioritária dentro da espera. O **portal do cliente não fornece check-in autônomo**, apesar de seção histórica anterior prever essa opção.

## 4. Comparativo de modalidades e limites

| Critério | CRM Básico (público/cortesia conforme elegibilidade) | Essencial / Profissional / Empresarial |
|---|---|---|
| Agenda e reserva | **Manual e interna** | Agenda interna e **pública** quando plano/contrato elegível |
| Profissionais agendáveis | **1** | Capacidade conforme plano/addons a rever |
| Serviços ativos simultâneos | **Até 10** | Capacidade conforme plano/addons a rever |
| Usuários autenticados Básico | **1**, perfil Administrador da Empresa/MFA | Direitos de usuários conforme plano e CRM-REV-01 |
| Agendamentos mensais | **Sem teto comercial rígido** | **Ilimitados comercialmente** nos três planos, sujeitos a antiabuso |
| Agenda por site | **Mesma quota da organização**, sem multiplicação por contratos de site | Por organização/entitlements reais |
| Confirmação e lembretes | **Internos** (padrão 24h e 1h, editáveis/desativáveis) | Internos e externos quando canais elegíveis e autorizados |
| Notificações internas Básico | Centro com **90 dias** de retenção, independentemente de leitura | Retenções por módulo/categoria a definir, não copiar automaticamente |
| Unidades incluídas | Regime Básico próprio (não presumir quota da tabela paga) | Essencial **1**, Profissional **3**, Empresarial **10**, extras sujeitos à cobrança contratada |
| Autoagendamento WhatsApp/site/portal | **Não incluído** | Conforme assinatura, consentimento, conexão e elegibilidade |

**Preços e capacidades finais** continuam sujeitos à revisão conjunta comercial, sem alteração nesta REV-07.

## 5. Fronteiras técnicas e de autoridade a preservar

**Reserva/conflito:** a Agenda é a autoridade da reserva e da disponibilidade efetiva. INT, chatbot ou calendário externo **não declaram confirmado** um agendamento por simples HTTP/webhook; revalidar conflitos em gravação transacional. Reserva de grupo admite participantes na mesma sessão sem atribuir conflito físico inexistente.

**Aprovações:** serviço pode exigir aprovação prévia; manter hold temporário com expiração, sem confirmação ao cliente até aprovação válida e disponibilidade revalidada. Reutilizar Central de Aprovações, sem criar outra.

**Canais:** e-mail/WhatsApp externo dependem de contrato, consentimento, template/janela, elegibilidade e resultado técnico próprio; a Agenda preserva fato oficial e auditoria. Notificações internas essenciais do Básico não consomem slots comerciais.

**Privacidade/segurança:** usuários somente veem agenda/equipe/unidade conforme permissões; cliente pode ver só suas próprias reservas por link seguro; dependentes/terceiros exigem vínculo autorizado e finalidade legítima. Anexos não escapam da franquia de arquivos nem da proteção antimalware.

**Fuso e calendário:** instantes UTC, fuso IANA, regras de horário local e feriados/sazonalidade; não presumir que evento externo privado é compartilhável, nem que um feriado impõe fechamento automático sem configuração.

**Rejeições:** sem pagamento de serviços pelo CRM, sem check-in autônomo do cliente, sem prioridade na espera, sem favoritos, sem teto de reservas futuras por cliente e sem encerramento automático por horário decorrido. O faturamento da assinatura do CRM **permanece separado** dessas rejeições.

## 6. Diferenças e precedências a reconciliar

| ID | Diferença de fonte ou risco |
|---|---|
| AGD-D01 | Capacidade Básico 1 profissional/10 serviços/1 login, sem página pública, diferente dos planos pagos. |
| AGD-D02 | Data/horário UTC e fuso IANA local são aspectos diferentes; horário de verão pode criar instantes inválidos/ambíguos. |
| AGD-D03 | Lista de espera originalmente admitia exceção prioritária, mas revisão posterior **REJEITOU** prioridade: FIFO estrita. |
| AGD-D04 | Seção de presença anteriormente oferecia check-in por cliente; revisão posterior **REJEITOU**: apenas funcionário autorizado. |
| AGD-D05 | Avisos internos Básico 24h/1h e 90 dias de notificação não são retenção dos próprios agendamentos ou auditoria. |
| AGD-D06 | Confirmação de presença não é estado Concluído, nem confirmação de pagamento. |
| AGD-D07 | Prazo sem confirmação não implica cancelamento automático ativado por padrão. |
| AGD-D08 | Agendamento prioritário/encaixe autorizado não dá prioridade na lista de espera FIFO. |
| AGD-D09 | Unidades incluídas dos planos pagos são referência funcional; custos de unidades adicionais ainda precisam homologação. |
| AGD-D10 | Compromisso externo Ocupado não permite exibir detalhes pessoais de Google/Outlook aos demais funcionários. |
| AGD-D11 | Reserva de grupo tem participantes individuais; não bloquear participantes da mesma sessão como conflito de reserva. |
| AGD-D12 | Agenda e tarefas comerciais compartilham calendário visual, mas possuem modelos e trilhas distintos. |
| AGD-D13 | Check-in de funcionário e pesquisa CSAT da agenda não são check-in cliente nem CSAT de atendimento WhatsApp. |
| AGD-D14 | Agendamento público em site/Portal é recurso do CRM pago elegível, não prova que o cliente Básico recebe autoagendamento. |
| AGD-D15 | Integração com provedor externo não autoriza alteração de agenda sem versão, direitos e validação de concorrência. |
| AGD-D16 | Modelos de dados/horários complementares no capítulo Básico ainda marcados como propostas não passam a aprovados só porque quotas IN estão aprovadas. |

## 7. Lacunas para fechamento documental e técnico

| ID | Questão ainda aberta | Estado |
|---|---|---|
| AGD-P01 | Entidades e estados exatos: empresa, unidade, profissional, serviço, recurso, reserva, série, sessão, participante, consentimento e histórico | Pendente para validação/aceite |
| AGD-P02 | Modelo transacional de disponibilidade, idempotência, locks, concorrência e reserva de capacidade | Pendente para validação/aceite |
| AGD-P03 | Matriz plano×recurso×quota e limites por serviço/profissional/unidade/usuário | Pendente para validação/aceite |
| AGD-P04 | Direitos granulares por unidade/equipe/profissional/cliente, ações especiais e delegações | Pendente para validação/aceite |
| AGD-P05 | Agenda Básico manual, notificações 24h/1h, central 90 dias e elegibilidade dos envios externos pagos | Pendente para validação/aceite |
| AGD-P06 | Canais público/portal/chatbot, links com escopo e expiração, autorização do titular/dependente | Pendente para validação/aceite |
| AGD-P07 | Conectores Google/Outlook/Meet/Teams/Zoom, credenciais/cofre, estados de sync, conflitos e recuperação | Pendente para validação/aceite |
| AGD-P08 | Política de confirmação, cancelamento por falta de resposta, reagendamento, falta e estados operacionais | Pendente para validação/aceite |
| AGD-P09 | Serviços encadeados, sessão em grupo, múltiplos participantes e recursos físicos | Pendente para validação/aceite |
| AGD-P10 | FIFO de espera e oferta de vaga, prazo de reserva, expiração, ordenação e exceções proibidas | Pendente para validação/aceite |
| AGD-P11 | Aprovação prévia e retenção temporária de horário; uso da Central de Aprovações existente | Pendente para validação/aceite |
| AGD-P12 | Fusos IANA, DST, feriados nacionais/estaduais/municipais e regras de antecedência | Pendente para validação/aceite |
| AGD-P13 | Proteção de dados, anexos/formulários prévios, menores e consentimentos LGPD | Pendente para validação/aceite |
| AGD-P14 | Regras e custos dos adicionais de unidades, profissionais e serviços por plano | Pendente para validação/aceite |
| AGD-P15 | Auditoria, categorias de retenção, acesso histórico e exportações autorizadas | Pendente para validação/aceite |
| AGD-P16 | Fonte original integral AGD e IN-007..022; critérios de aceite futuros por cenário | Pendente para validação/aceite |
| AGD-P17 | Contratos de execução ATD/VEN/IA/INT sem redundância com Filas, Eventos, Prazos, Tarefas e Notificações | Pendente para validação/aceite |
| AGD-P18 | Consolidação final do que é rejeitado e política de migração de estados/versões de agendas já existentes | Pendente para validação/aceite |

As 18 lacunas são **pontos de revisão**, não 18 novos módulos ou direitos previamente aprovados. O status de aprovação **por seção** dos capítulos originais continua prevalecendo sobre exemplos propostos de schema.

## 8. Cenários de validação futura — 0 executados

| ID | Resultado negativo/positivo a comprovar futuramente |
|---|---|
| AGD-T01 | Básico tenta link público ou chatbot de autoagendamento: negar sem desabilitar agenda manual |
| AGD-T02 | Básico tenta ativar 11º serviço ou segundo profissional: negar e preservar históricos |
| AGD-T03 | Agendar manualmente fora de expediente no Básico: exigir aviso e confirmação, ainda verificar conflitos |
| AGD-T04 | Lembrete interno Básico e slot comercial zerado: lembrete continua, sem envio externo automático |
| AGD-T05 | Notificação interna arquivada com 90 dias: some da central, mas agendamento continua |
| AGD-T06 | Google Calendar com evento privado: bloquear horário e mostrar apenas Ocupado |
| AGD-T07 | Reserva simultânea de sala/equipamento por dois canais: só uma confirmação |
| AGD-T08 | Vaga de lista de espera oferecida ao segundo da fila antes do primeiro: negar |
| AGD-T09 | Cliente tenta check-in digital: negar; check-in manual da equipe continua |
| AGD-T10 | Cliente agenda múltiplos serviços e um serviço falha: não confirmar reserva parcialmente |
| AGD-T11 | Sessão em grupo recebe duas inscrições na última vaga: não exceder capacidade |
| AGD-T12 | Cliente ausente de confirmação: não cancelar por padrão |
| AGD-T13 | Aprovação prévia expira: liberar reserva temporária sem confirmar cliente |
| AGD-T14 | Profissional transferido para outra unidade sem disponibilidade: negar e manter reserva original |
| AGD-T15 | Funcionário atualiza agendamento com versão antiga: exibir conflito sem sobrescrever |
| AGD-T16 | Alteração de horário com DST inexistente/ambíguo: validar e solicitar decisão |
| AGD-T17 | Solicitação de pagamento de serviço via Agenda: nenhuma operação de cobrança habilitada |
| AGD-T18 | Relatório/anexo de cliente de outro tenant: negar no servidor |
| AGD-T19 | Unidade excedente em downgrade: preservar dados/agendamentos e restringir nova operação indevida |
| AGD-T20 | Check-in de um participante de grupo: não modificar presença dos outros |
| AGD-T21 | Feriado detectado em unidade, mas empresa declarou aberto: respeitar decisão explícita |
| AGD-T22 | Passagem de horário previsto sem ação humana: não concluir atendimento automaticamente |

Esses 22 cenários são critérios documentais candidatos; **não representam testes HT executados** nem programação autorizada.

## 9. Aprovação recomendada e próxima etapa

**CRM-REV-07 foi aprovada como base documental de revisão**, conforme [decisão](CRM-DEC-aprovacao-REV-07-base.md), preservando as 42 regras funcionais cotejadas e as **seis exclusões expressas**, com AGD-P01..18 ainda abertos. Esse aceite não aprova provedores externos, políticas numéricas finais, tabelas, APIs, alçadas, planos de preço, novas funcionalidades ou execução.

A próxima revisão indicada permanece **CRM-REV-08 — Agentes de IA, Consumo, Base de Conhecimento e Supervisão**. A [nova decisão de gratuidade administrativa em qualquer plano](CRM-DEC-gratuidade-administrativa-qualquer-plano.md) altera a elegibilidade comercial por empresa, **não** as regras de Agenda nem os benefícios do plano Básico.

**Situação global atualizada:** CRM-REV-01..07 aceitas como bases documentais, gates CORE-P0/INT-P0/IN-168/G01–G06 permanecem abertos. Nenhuma programação, teste, automação nova ou implantação iniciada.
