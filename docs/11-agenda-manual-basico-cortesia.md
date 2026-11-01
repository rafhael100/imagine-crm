# 11 — CRM Básico Cortesia: Agenda Manual (um profissional), serviços e regras operacionais

> **IN-008, IN-009, IN-011 e IN-012 APROVADAS em 2026-11-01:** o CRM Básico Cortesia permite **1 profissional com agenda própria, até 10 serviços ativos, expediente flexível e agendamentos manuais sem teto comercial mensal**, sujeitos a uso justo e controles técnicos contra abuso. A agenda permanece **exclusivamente manual e interna** (IN-007).  
> **Somente as decisões identificadas como APROVADAS têm esse status.** Regras complementares, número de reservas mensais e características não detalhadas permanecem PROPOSTOS. Não implementar, criar cobranças ou publicar a funcionalidade.

## 1. Decisões já aprovadas e limites de interpretação

- **IN-004:** o Básico Cortesia, benefício de clientes elegíveis ao Onepage/Landing Page Imagine, inclui o módulo Agenda e Agendamentos.
- **IN-007:** a agenda é **manual** no Básico: usuário autenticado e autorizado registra e administra agendamentos; visitantes não acessam página pública para reservar, nem botão, embed, chatbot ou API pública para autoagendamento. A página pública e a capacidade de reservar pelo site ficam exclusivas dos planos CRM pagos.
- **IN-008:** somente **1 profissional com agenda própria**, por organização CRM beneficiária do Básico, independentemente de quantos contratos de Onepage/Landing Page estejam vinculados à mesma empresa. Não confundir **profissional agendável** com **usuário habilitado a entrar no CRM**: a quota de usuários administrativos ainda está pendente. Se houver apenas um usuário no plano, esse usuário pode ser o profissional, mas isso deve ser configurado e validado.
- **IN-011:** aprovada a configuração semanal flexível (inclusive dias sem expediente, sábados e domingos), múltiplos períodos por dia, pausas/intervalos, bloqueios manuais de folgas, férias e feriados, duração própria de cada serviço, preservação dos compromissos nas mudanças de expediente, e **agendamento manual excepcional fora do expediente** com aviso e confirmação explícita de usuário autorizado, **sem ignorar conflitos**. Não concede página pública de autoagendamento.
- **IN-012:** aprovada **ausência de teto comercial rígido para a quantidade mensal de agendamentos manuais** da única agenda do Básico, com **uso justo e controles técnicos contra abuso**. Não estabelecer limiar mensal oculto nem bloquear utilização humana legítima por atingir quantidade de reservas; quota de reservas públicas segue indisponível no Básico (IN-007).
- **IN-009:** o profissional da empresa beneficiária pode manter **até 10 serviços ativos simultaneamente**, somando todos os tipos de atendimento cadastrados para essa agenda. Serviços inativos/arquivados **não ocupam vaga ativa**, mas seus históricos/agendamentos associados devem ser preservados. Não multiplicar a quota por site ou formulário. Ao tentar ativar o 11º serviço, bloquear a operação com explicação e opção de inativar outro serviço ou contratar plano elegível.
- As automações comerciais do Básico permanecem em modelos prontos, até **3 ativas simultaneamente por empresa como baseline atual** (IN-005), com revisão final numérica planejada (IN-006). Essas automações não autorizam agendamento público.
- Nos planos pagos Essencial, Profissional e Empresarial, a agenda e a página pública já constam do produto geral e **os agendamentos mensais foram aprovados como ilimitados**. Isso não estabelece automaticamente quota mensal para o Básico.
- O **pagamento pelo serviço agendado** (taxa, sinal, checkout, Pix/cartão dos clientes da empresa) foi rejeitado para o CRM geral e continua fora do escopo.

## 2. Modelo de dados e identidade propostos

- `crm_organization_id`: empresa/tenant.
- `bookable_professional_id`: profissional dono da agenda, pertencente à organização, ativo e elegível para o plano. **No Básico: 1 profissional ativo no máximo**.
- `actor_user_id`: usuário autenticado que fez/alterou manualmente o agendamento (pode ser diferente do profissional agendável, caso a política de usuários venha a permitir).
- `service_id`: serviço oferecido pela empresa; com nome, descrição opcional, duração, intervalo entre atendimentos, status e disponibilidade.
- `appointment_id`: serviço, início/fim armazenados em UTC com fuso IANA relevante, responsável, contato/lead vinculado, status, criação/edição e trilha de auditoria.
- `availability_rule`: janelas semanais por dia; exceções de data; férias, feriados e bloqueios. Aplicação no motor de validação de conflitos.
- Contato/lead criado pela integração do site não deve criar um appointment por si só.

## 3. Serviços cadastráveis — IN-009 APROVOU A QUANTIDADE; DEMAIS CAMPOS PROPOSTOS

| Dimensão | Recomendação | Justificativa |
| --- | --- | --- |
| Quantidade | **IN-009 APROVADO: até 10 serviços ativos por organização Básico e seu único profissional** | O 11º não pode ser ativado enquanto houver 10 ativos; serviços inativos não consomem vaga e histórico permanece |
| Nome | Obrigatório, único por empresa entre serviços ativos | Ex.: Consulta, Orçamento, Avaliação, Visita Técnica |
| Descrição | Opcional | Uso interno, não vira página pública no Básico |
| Duração | Por serviço (ex.: 15, 30, 45, 60, 90 min) e opção de duração customizada validada | A duração determina ocupação e conflito |
| Intervalo | Opcional antes/depois da reserva, configurado por serviço | Evita sobreposições e agenda irrealista |
| Modalidade | Presencial, telefone ou online | Sem criação automática de links de videochamada no Básico |
| Serviço ativo/inativo | Inativar sem excluir histórico | Protege agendamentos anteriores |
| Capacidade | **Um atendimento não simultâneo para o único profissional**, salvo exceção expressamente aprovada no futuro | Alinha ao objetivo de agenda individual manual |

**Não implicar:** dez serviços não significa dez profissionais ou dez agendas. Uma mesma categoria de serviço não pode ser usada para criar outra agenda pública no Básico. **Serviço ativo** é o objeto que pode ser selecionado pelo usuário durante registro manual; serviço inativo pode ser consultado historicamente, mas não deve ser oferecido como novo atendimento até reativação autorizada. Se ocorrer downgrade com mais de 10 ativos, preservar configurações/dados, exigir escolha de até dez ativos e suspender novas seleções dos excedentes; não excluir histórico.

## 4. Horários do profissional — IN-011 APROVADA (detalhes complementares indicados)

**Regra funcional aprovada:** a agenda de um profissional pode operar em dias e horários livremente configurados, com validação real de disponibilidade, sem reservas incompatíveis e sem criação automática pelo visitante.

### 4.1 Expediente regular — APROVADO

- Configurar individualmente **segunda a domingo**, inclusive dias sem atendimento, sábados e domingos, sem obrigar um horário padrão (ex.: 08h–18h).
- Permitir **vários períodos por dia** (ex.: 08h–12h e 13h–18h).
- Configurar pausas/intervalos de almoço, descanso e **intervalos entre serviços**; cada um dos até 10 serviços ativos terá sua própria duração.
- Exibir agenda por dia, semana e mês no CRM, com foco no único profissional do Básico.
- Modificar as janelas de expediente sem apagar/rescindir automaticamente compromissos existentes; apontar conflitos e permitir análise/ajuste manual autorizado.

### 4.2 Bloqueios e exceções de expediente — APROVADO

- Inserir **bloqueio manual** por horas, datas ou períodos para almoço, folgas, férias, feriados e indisponibilidades.
- Configurar exceções pontuais para determinado dia com horário diferente do padrão, sem alterar a grade semanal.
- A existência de reserva anterior que colida com bloqueio novo exige aviso e tratamento manual; **não excluir, reagendar ou cancelar silenciosamente**.
- **Feriados:** aprovada marcação/bloqueio manual; sincronização automática com calendário nacional/municipal ou Google/Outlook **não foi aprovada** por esta decisão.

### 4.3 Agendamento manual fora do expediente — APROVADO SOB CONFIRMAÇÃO

1. Um usuário **autenticado e autorizado** seleciona um horário fora da grade regular.
2. O CRM apresenta **aviso claro** de exceção, com dia/horário e identificação de que está fora do expediente.
3. O operador precisa **confirmar expressamente** a exceção antes da gravação. A confirmação não deve ser presumida nem reaproveitada em outras reservas.
4. O backend **revalida disponibilidade e conflito atomicamente**, incluindo duração do serviço e pausas; não aceita sobreposição incompatível mesmo que haja confirmação.
5. Registrar no histórico e auditoria autor, momento, horário, justificativa **se informada** e a marcação de exceção, para investigação e futuras alterações.
6. O aviso de fora do expediente **não autoriza superar bloqueios específicos** (férias, indisponibilidade) nem reserva pública do visitante; qualquer política para abrir exceção de bloqueio específico ou encaixe incompatível exige **decisão própria posterior**.
7. Motivo obrigatório, papéis específicos autorizados e forma exata do aviso são **detalhes de UX/permissionamento pendentes**, não presumidos além da autorização exigida.

### 4.4 Validação, horários e fusos — diretrizes técnicas derivadas (propostas de implementação)

- Validar overlap no servidor por profissional, organização, janela e ocupação total (duração + intervalos), inclusive concorrência de duas operações.
- Armazenar timestamps normalizados em UTC e fuso IANA da organização, apresentar hora local e tratar diferenças de fuso/horário de verão.
- Registrar alteração de horário padrão/bloqueio/exceção e suas consequências, respeitando separação multiempresa.
- A entrada manual pode criar um compromisso para o próprio dia ou futuro. Se agendamento retroativo será autorizado, e se há antecedência máxima ou mínima obrigatória, **segue pendente**.

**Observação:** agendamentos fora do expediente são ações **manuais dentro do CRM**. Não equivalem ao agendamento público automático, que continua restrito a planos pagos (IN-007).


## 5. Operações manuais da agenda — núcleo aprovado, detalhamento proposto

1. Usuário autorizado abre a agenda diária/semanal/mensal e escolhe **Novo agendamento**.
2. Busca/cria lead ou contato; seleciona serviço, data e horário, profissional único, modalidade e observações internas.
3. Backend valida que profissional, tenant, serviço e ator pertencem à organização; calcula duração, intervalos e conflitos e aplica regras/quotas efetivas do plano.
4. Em caso de conflito, impedir confirmação por padrão e mostrar agendamento em choque. **Não criar overbooking silencioso**; eventuais encaixes são recurso separado a aprovar.
5. Salva `Agendado`, com histórico e autor; demais estados planejados no CRM geral: `Confirmado`, `Realizado`, `Não compareceu` e `Cancelado`.
6. Editar, reagendar, cancelar e alterar status fica condicionado à permissão; preservar horário anterior, motivo de alteração quando relevante e auditoria.
7. O cliente/visitante não pode usar URL pública, API de agendamento ou formulário do Onepage para criar compromissos, mesmo que tente chamar endpoint diretamente.
8. Notificações **internas** de mudança e registro são sugeridas como funcionamento do sistema; e-mail externo e WhatsApp por API continuam pendentes (canais, consentimento, orçamento).

## 6. Limites comerciais ainda NÃO aprovados

| Recurso | Alternativas para decisão | Recomendação inicial |
| --- | --- | --- |
| Profissionais com agenda própria | **1 — IN-008 APROVADO** | 1 |
| Usuários CRM | 1 ou mais; o profissional pode ser o único usuário ou um registro distinto | **1 usuário** (a aprovar separadamente) |
| Serviços ativos | **IN-009 APROVADO: 10** | 10 simultaneamente ativos; demais inativos não consomem quota |
| Agendamentos manuais por mês | **IN-012 APROVADA: sem teto comercial rígido, com uso justo** | Quantidade mensal não é motivo isolado para exigir upgrade |
| Unidades/localidades | 1 / mais | **1 unidade lógica** sem várias agendas; a aprovar |
| Agendamentos simultâneos | Um por horário do único profissional, salvo encaixes autorizados | 1 sem sobreposição |
| Calendário | Dia/semana/mês, local e interno | Incluído no núcleo |
| Reserva pelo visitante | **Proibida no Básico por IN-007** | Exclusiva de planos pagos |
| Google/Outlook | Não incluído nesta decisão; avaliar como diferencial pago | Reservar para pagos inicialmente |
| Agenda compartilhada/equipe | Não incluída por IN-008 | Reservar para planos pagos |
| Agendamento recorrente/grupos/lista de espera | Não incluído automaticamente | Reservar para pagos inicialmente |
| Armazenamento/anexos de agenda | Definir com matriz geral do benefício | Quota específica a aprovar |
| Avisos externos e lembretes | Canal/volume/custos ainda pendentes | Internos no Básico; externos em oferta própria |

**Justificativa para não impor um teto baixo de reservas manuais sem análise:** o cliente já precisa registrar manualmente cada atendimento e há apenas um profissional; o custo incremental por evento é pequeno, mas ainda existem riscos de abuso e limites de infraestrutura. Quotas técnicas, antifraude e retenção independem do número de registros permitido comercialmente.

### Regra IN-010 — Comunicação do bloqueio no contexto da agenda

**Aprovado:** ao tentar ativar **11º serviço**, **2º profissional agendável** ou página pública de autoagendamento no Básico, exibir mensagem clara indicando a condição do plano e permitir comparação/upgrade **se** um plano pago oferecer a capacidade. Para serviços, permitir inativar outro serviço antes de recomendar nova assinatura. No limite mensal de reservas, se tal limite for aprovado futuramente, usar contagem real e apresentar explicação; não inventar quota agora. O CRM deve distinguir restrição contratual de erro técnico e de falta de permissão do operador. Nunca processar upgrade ou cobrança sem aceite e pagamento autorizado. [Regra geral IN-010](12-limites-e-upgrade-contextual.md).

## 7. Diferenciação e upgrade

- O diferencial pago principal é **autoagendamento público e online**, além de maior equipe/recursos e integrações conforme sua matriz.
- O Básico pode manter dados históricos de agendamentos criados quando o cliente tinha plano pago, mas **não pode receber novas reservas públicas** enquanto estiver no benefício gratuito.
- No upgrade, ativar reservas públicas apenas com autorização da empresa, página habilitada e verificação server-side da assinatura paga. Site estático pode receber botão/link somente após fluxo de modificação/autorização versionada; páginas públicas no CRM devem consultar entitlement a cada reserva.
- Cancelamento do plano pago e retorno ao Básico exige suspender novas reservas públicas sem apagar dados e sem impedir administração manual.

## 8. Testes de aceitação

1. Básico não permite cadastrar/ativar um segundo profissional agendável; pode manter histórico de profissional anterior inativo, sem apagar compromissos.
2. Número de usuários autenticados não é confundido com número de agendas; permissões da empresa continuam protegidas.
3. Usuário autenticado da organização A não cria nem edita compromissos da B.
4. Visitas ao Onepage, submit de formulário e chamadas de endpoints públicos não criam reserva para a organização no Básico.
5. Horários conflitantes são rejeitados por validação no backend, inclusive concorrência de criação e intervalo do serviço.
6. Alteração de expediente não apaga reservas preexistentes.
7. Registros manuais têm autor, fuso horário, período, serviço e histórico auditável.
8. Upgrade para plano pago pode habilitar autoagendamento apenas após confirmação e autorização da organização.
9. Retorno de plano pago a Básico mantém reservas/histórico; bloqueia novas públicas e impede cadastro do segundo profissional ativo se a quota for excedida, sem exclusão silenciosa.
10. A contagem das três automações comerciais ativas é distinta de validar agendamentos ou manter o calendário.

## 9. Detalhamento para próxima aprovação — horários e agenda mensal

### 9.1 Expediente, pausas e exceções — IN-011 APROVADA

As regras de expediente flexível, múltiplos períodos, pausas, duração por serviço, bloqueios, folgas, férias, feriados manuais, exceções por data, preservação dos compromissos e agendamentos manuais excepcionais fora do expediente **com aviso/confirmação e sem conflitos** estão aprovadas. Os detalhes operacionais constam na seção 4.

**Pendente:** política exata para bloqueios específicos e possíveis exceções, justificativa obrigatória ou opcional, agendamento retroativo, integrações externas e a quantidade mensal de reservas; **não interpretar aprovação de horários como aprovação de agendamentos ilimitados**.


### 9.2 Quantidade mensal de agendamentos manuais — IN-012 APROVADA

**Regra aprovada:** o profissional com agenda manual no Básico Cortesia **não terá quota comercial mensal numérica de agendamentos**. A empresa poderá registrar o volume de atendimentos necessário, com **uso justo e controles técnicos contra abuso**, sem um bloqueio comercial escondido em 30, 100, 300 ou outra quantidade fixa.

**Alcance preciso:**
- Vale somente para **agendamentos criados ou administrados manualmente por usuário autenticado/autorizado no CRM** da empresa beneficiária, vinculados ao único profissional (IN-008) e a até dez serviços ativos (IN-009).
- **Não libera** agendamento pelo visitante, página pública, formulário de reserva, bot, API pública de criação, importação automatizada ou criação em massa não homologada; essas capacidades seguem seus próprios direitos.
- Aplicar proteção contra scripts, automações maliciosas e tráfego abusivo (rate limit, limites de payload, autenticação, logs, detecção de anomalias e eventuais limites por janela curta), com valores técnicos e regras de revisão ainda a documentar.
- **Não converter controle técnico de segurança em teto comercial mensal não comunicado.** Se houver restrição por atividade suspeita, informar motivo honesto e fluxo de suporte, sem apresentar upgrade como solução enganosa (IN-010).
- Reservas aceitas, históricos, alterações de horário e contatos vinculados devem permanecer íntegros. Bloqueio de um evento suspeito não pode apagar os demais nem invalidar reserva já confirmada.
- Os planos CRM pagos Essencial, Profissional e Empresarial já possuem agendamentos mensais ilimitados aprovados. A ausência de teto comercial no Básico não estende a esse benefício a capacidade de autoagendamento público.

**Não decidido por IN-012:** canais de confirmação/lembrete, prazo e antecedência, quotas de envio externo, armazenamento e retenção, múltiplos profissionais, usuários licenciados, agendamento recorrente e regras para operações em massa.

### 9.2A Agenda: notificações e lembretes — próxima revisão, NÃO APROVADOS para o Básico

O CRM geral já tem **central de notificações internas**, preferências e avisos de alteração de agendamento, confirmações/lembretes ao cliente por canais habilitados e registros de envio. **Ainda não está definido quais desses recursos serão incluídos especificamente no Básico Cortesia.**

**Proposta A — avisos operacionais internos de sistema (recomendada):** registro, cancelamento, reagendamento, alterações de disponibilidade e compromissos próximos aparecem no painel/central de notificações; os avisos necessários à operação não consomem as três automações comerciais ativas do IN-005. Definir antecedência de lembrete e possibilidade de ajustes sem spam ou avisos duplicados.

**Proposta B — mensagens externas transacionais por e-mail:** notificações/confirmacões de agenda para clientes, exigindo canal verificado, política de privacidade/consentimento, proteção de dados, entrega e custos; pode ser incluída ou oferecida somente em planos pagos conforme aprovação.

**Proposta C — WhatsApp oficial:** envio automático exige integração WhatsApp Business API, templates/categorias válidas, consentimento, custos e direitos apropriados. Um botão de WhatsApp ou link simples não equivale a envio automático. Não habilitar automaticamente no Básico.

**Separação obrigatória para a futura decisão:** notificação operacional gerada pelo próprio módulo agenda ≠ **modelo configurável de automação comercial**, que conta uma vaga ativa quando o cliente o habilitar. Não permitir que alertas essenciais deixem de funcionar por a empresa ter usado três modelos comerciais.


### 9.3 Decisões seguintes

1. **IN-009 e IN-011 já aprovaram** até 10 serviços ativos e a agenda flexível, incluindo bloqueios e exceção manual fora do expediente com confirmação e sem conflitos. Consolidar apenas detalhes técnicos/UX ainda abertos.
2. **IN-012 aprovada:** agendamentos manuais sem teto comercial mensal; especificar apenas métricas técnicas e controles proporcionais de uso justo, sem quota escondida.
3. **Próxima decisão:** definir quais notificações internas da agenda são operações de sistema sem consumir as vagas de automação comercial; avaliar envio externo por e-mail e WhatsApp em etapa separada.
4. Revisar campos de serviço, permissões e limite de usuários do Básico; funcionalidades externas (WhatsApp Business API, Outlook/Google Calendar etc.) não são autorizadas pela IN-009.


**Situação:** IN-008 aprova **1 profissional com agenda**; IN-009 aprova **até 10 serviços ativos**; IN-011 aprova horários flexíveis e exceções manuais fora do expediente sem conflitos; **IN-012 aprova agendamentos manuais sem teto comercial mensal, com uso justo e controles técnicos contra abuso**. **Notificações, lembretes e integrações externas permanecem pendentes**. Somente documentação de planejamento, sem implementação.
