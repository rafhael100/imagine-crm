# 11 — CRM Básico Cortesia: Agenda Manual (um profissional), serviços e regras operacionais

> **IN-008 APROVADA em 2026-11-01:** o CRM Básico Cortesia permite **1 profissional com agenda própria por empresa/organização**. A agenda permanece **exclusivamente manual e interna** (IN-007).  
> **Demais parâmetros, quantidades e requisitos abaixo são PROPOSTOS e aguardam aprovação explícita.** Não implementar, criar cobranças ou publicar a funcionalidade.

## 1. Decisões já aprovadas e limites de interpretação

- **IN-004:** o Básico Cortesia, benefício de clientes elegíveis ao Onepage/Landing Page Imagine, inclui o módulo Agenda e Agendamentos.
- **IN-007:** a agenda é **manual** no Básico: usuário autenticado e autorizado registra e administra agendamentos; visitantes não acessam página pública para reservar, nem botão, embed, chatbot ou API pública para autoagendamento. A página pública e a capacidade de reservar pelo site ficam exclusivas dos planos CRM pagos.
- **IN-008:** somente **1 profissional com agenda própria**, por organização CRM beneficiária do Básico, independentemente de quantos contratos de Onepage/Landing Page estejam vinculados à mesma empresa. Não confundir **profissional agendável** com **usuário habilitado a entrar no CRM**: a quota de usuários administrativos ainda está pendente. Se houver apenas um usuário no plano, esse usuário pode ser o profissional, mas isso deve ser configurado e validado.
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

## 3. Serviços cadastráveis — PROPOSTO, NÃO APROVADO

| Dimensão | Recomendação | Justificativa |
| --- | --- | --- |
| Quantidade | **Até 10 serviços ativos por organização Básico** como ponto de partida (avaliar 5, 10 ou sem teto comercial) | Pequenos prestadores podem ter mais de um atendimento; custo baixo de cadastro, mas manter diferenciação |
| Nome | Obrigatório, único por empresa entre serviços ativos | Ex.: Consulta, Orçamento, Avaliação, Visita Técnica |
| Descrição | Opcional | Uso interno, não vira página pública no Básico |
| Duração | Por serviço (ex.: 15, 30, 45, 60, 90 min) e opção de duração customizada validada | A duração determina ocupação e conflito |
| Intervalo | Opcional antes/depois da reserva, configurado por serviço | Evita sobreposições e agenda irrealista |
| Modalidade | Presencial, telefone ou online | Sem criação automática de links de videochamada no Básico |
| Serviço ativo/inativo | Inativar sem excluir histórico | Protege agendamentos anteriores |
| Capacidade | **Um atendimento não simultâneo para o único profissional**, salvo exceção expressamente aprovada no futuro | Alinha ao objetivo de agenda individual manual |

**Não implicar:** dez serviços não significa dez profissionais ou dez agendas.

## 4. Horários do profissional — PROPOSTO, NÃO APROVADO

- Configuração semanal por dia da semana, podendo ter **múltiplos períodos por dia** (ex.: 08h–12h e 13h–18h) e dias sem expediente.
- **Fuso horário da organização** e exibição no fuso selecionado, datas armazenadas em UTC com fuso IANA; tratar mudança de horário de verão e datas ambíguas.
- Bloqueios manuais de almoço, férias, consulta pessoal ou indisponibilidade temporária; exceções por data (horário reduzido/estendido).
- Agendamento manual normalmente respeita expediente e bloqueios. Se usuário autorizado desejar agendar fora do expediente, mostrar alerta e pedir confirmação/motivo; **a permissão excepcional ainda é decisão pendente**.
- Alterações do expediente não devem cancelar automaticamente reservas existentes; informar conflitos para tratamento manual.
- Feriados: possibilidade de bloquear manualmente; importação automática de calendário nacional/municipal é recurso pendente e não incluído automaticamente.
- Recomendação de antecedência: permitir reservas para hoje e futuras, configurando antecipação máxima sem limite obrigatório inicial; decidir depois se haverá exceção de reservas retroativas para registro histórico.

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
| Serviços ativos | 5 / **10** / sem limite rígido | Até **10** |
| Agendamentos manuais por mês | 30 / 100 / **sem limite rígido com uso justo** | Sem teto rígido comercial (a confirmar) |
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

## 9. Próxima aprovação

**Primeiro definir a capacidade da agenda manual em serviços cadastrados**: quantos serviços ativos o profissional poderá manter no Básico? Em seguida, detalhar horários, exceções e o teto ou ausência de teto comercial de agendamentos mensais, sem alterar quotas de automações comerciais neste momento.

**Situação:** IN-008 aprova **1 profissional com agenda**; demais quantitativos permanecem propostos. Todas as mudanças somente em documentos de planejamento.
