# CRM-AUT-LIB02 — Revisão individual dos modelos candidatos ao CRM Básico (v0.1)

**Data:** 04/11/2026.
**Status:** **ANÁLISE DE ELEGIBILIDADE E RECOMENDAÇÃO PARA DELIBERAÇÃO — NÃO É CATÁLOGO PUBLICÁVEL OU APROVADO.**
**Fundamento:** [AUT-LIB01 aceita como base documental](CRM-DEC-aceite-base-AUT-LIB01-2026-11-04.md), [AUT-LIB01](CRM-AUT-LIB01-biblioteca-modelos-basico-editores-por-plano-v0.1.md), [AUT-MAT01](CRM-AUT-MAT01-matriz-elegibilidade-automacoes-por-plano-v0.1.md) e [AUT-CAT01](CRM-AUT-CAT01-catalogo-gatilhos-condicoes-acoes-por-modulo-v0.1.md).
**Limites:** exclusivamente documentação. Nenhuma aprovação individual, configuração, programação, teste real, contratação, custo ou publicação.

## 1. Descoberta da revisão de direitos: cuidado com "tarefas"

- [LEG-08](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/08-plano-basico-cortesia.md): no Básico há leads e contatos, **notas e lembretes manuais básicos nos registros "se aprovados"**; **tarefas avançadas, equipes e automações** são diferenciais pagos, e o catálogo exato das automações do Básico ainda estava proposto.
- [LEG-13](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md), IN-023: aprovou **observações simples e histórico manual de atendimento**, mas afirma que **atividades avançadas, automações e envios externos dependem de permissões próprias**.
- [LEG-10](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/10-biblioteca-automacoes-e-criterios-de-planos.md), IN-005: **três automações comerciais ativas por tenant como baseline atual**, ainda sujeitas à revisão final depois da biblioteca. Isso **não equivale** a aprovar uma ação de criar tarefa avançada em todos os modelos candidatos.
- [REV-07](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md), IN-013/014: os **avisos essenciais da Agenda do Básico são internos e não consomem slots**; padrão **24h/1h**, cada um configurável. Notificações de agenda têm retenção própria de **90 dias**, distinta dos dados e da auditoria de tarefas.
- O Básico tem **um login de administrador** e **um profissional agendável**, até **10 serviços ativos**; não pode exigir automaticamente vários vendedores/equipes nem criar agendamento por formulário.

**Consequência:** priorizar modelos simples que só produzam **aviso interno ou anotação autorizada**. **Não publicar** modelos que dependam de criação/atribuição de tarefa comercial enquanto a elegibilidade da ação A01 não estiver definida para o Básico. Não substituir "tarefa" por "nota" silenciosamente: mudança do efeito altera o modelo e deve ser revisada/aceita.

## 2. Quadro decisório de candidatos

| Modelo | Gatilho fonte de verdade | Ação de menor risco e custo proposta | Situação e justificativa |
|---|---|---|---|
| **LEA-01 — Novo lead: acompanhamento** | **T01** — lead persistido e confirmado no CRM, não apenas aceito pela API do site | **A03** — aviso interno ao administrador, condicionado ao direito de notificações DAT/COM; **A01 criar tarefa** somente após confirmar direito | **Prioridade alta / condicionada**. A captura e histórico básicos são direitos conhecidos; aviso comercial opcional e sua política de dedupe ainda requerem aceite |
| **LEA-02 — Lead sem retorno** | **T13** — inatividade válida de lead, apurada pelo Motor de Datas; cancelamento/rearme após interação | **A03** aviso interno ao administrador; criar tarefa somente se A01 homologada para Básico | **Prioridade alta / condicionada**. O que conta como "interação válida" no Básico depende do catálogo, sem inferir leitura WhatsApp nem inventar horários padrão |
| **TSK-01 — Tarefa vencida** | **T12** — tarefa oficial VEN/Tarefas vencida e ainda aberta | **A03** alerta interno autorizado | **Aguardar confirmação do módulo Tarefas no Básico**. LEG-08 apenas sugere lembretes manuais básicos; não validar pelo rótulo "tarefa" sem entidade/permissão real. Não fazer derivação de eventos de calendário SO |
| **AGD-01 — Atendimento concluído: acompanhar** | **T20** — serviço/atendimento marcado **Concluído** por funcionário autenticado autorizado, após fato oficial da Agenda | **A03** aviso opcional interno; **A01** tarefa se direito comprovado | **Candidato secundário / condicional**. Não confundir "concluído" com simples aproximação do horário, ausência, pagamento nem presença declarada pelo cliente |
| **AGD-02 — Não comparecimento confirmado** | **T20** — status efetivo **Não compareceu**, confirmado pelo operador autorizado | Aviso interno opcional ou tarefa se direito; **não** mandar mensagem ao cliente | **Manter como decisão comercial aberta**: LEG-10 sugeriu Essencial+ e avaliar Básico; não há prova de elegibilidade Básico; poderia duplicar aviso SO |
| **AGD-03 — Alteração/cancelamento: follow-up opcional** | **T19/T20** — mudança oficial de agenda | Somente acompanhamento comercial **adicional** e claramente distinto do aviso operacional existente | **Não incluir no Básico por padrão** até provar diferencial funcional sobre avisos gratuitos SO; evitar contar slot por evento de cancelamento/remarcação já notificado na Central da Agenda |
| **OPP-01 — Oportunidade parada** | VEN — oportunidade oficial e relógio de inatividade | Aviso/tarefa de venda quando módulo e ação elegíveis | **Suspender qualquer elegibilidade Básico presumida**. LEG-10 marcava Básico+ como *proposta*, mas direito a funil/oportunidade no Básico e seus recursos não está encerrado na matriz geral |

**Não interpretar a coluna "prioridade" como publicação ou aprovação de um modelo.** A escolha final de LEA-01/02 pode variar se o efeito de aviso comercial entrar em colisão com avisos de sistema; os modelos serão versionados por contrato de ação e não por rótulo.

## 3. Fichas de análise e gates item a item

### LIB02-M01 — LEA-01, novo lead confirmado

- **Fonte:** evento oficial de criação/associação de lead DAT, ligado a tenant, site/formulário/origem, versionamento e ID de envio lógico idempotente; API principal/contingência confirma apenas persistência no ingresso, não o evento CRM.
- **Condição:** tenant/organização elegível, lead visível ao único administrador, situação e consentimentos aplicáveis; ausência de evento duplicado; possível filtro de origem como parâmetro **sujeito à aprovação**.
- **Efeito proposto de menor risco:** aviso interno "novo lead para acompanhamento". **Criar tarefa/atribuir** exige permissão separada e não está aprovado pela mera existência de slots do Básico.
- **Pré-homologação:** confirmar se a notificação comercial configurável A03 é permitida no Básico, forma de contagem do slot, se cadastro manual também dispara, modo como cliente ativa/desativa e evita avisos duplicados.
- **Teste candidato:** uma submissão legítima reenviada 3x causa um único efeito lógico; envios distintos com mesmo telefone **não** se fundem automaticamente.

### LIB02-M02 — LEA-02, sem retorno após prazo

- **Fonte:** Motor de Datas e estado do lead DAT confirmado; entrada/atividade que conta como "retorno" tem catálogo por módulo.
- **Condição:** lead elegível e ainda sem interação, prazo definido em horas/dias conforme política, escopo do administrador; **não criar relógio paralelo**.
- **Efeito proposto:** aviso interno não repetitivo; somente tarefa comercial se direito próprio confirmado.
- **Pré-homologação:** identificar quais registros Básico podem resetar o relógio (observação manual? alteração do lead? contato efetivamente registrado?), política de não resposta, prazo seguro, reativação, evento tardio, mudanças de fuso.
- **Teste candidato:** atendente registra uma interação antes do prazo, o lembrete é cancelado/recálculo ocorre sem alerta atrasado; sem novos avisos intermináveis por inatividade contínua.

### LIB02-M03 — TSK-01, tarefa vencida

- **Fonte:** motor oficial de tarefas (se entidade efetivamente habilitada no Básico).
- **Condição:** tarefa autorizada aberta, vencimento oficial com fuso, não duplicada; não é notificação de agendamento (SO).
- **Efeito proposto:** alerta interno ao único administrador, limitado e auditado; sem duplicar tarefa já existente.
- **Pendência eliminatória:** confirmar se **Tarefas** e o direito de **criar/consultar** tarefas e alertas comerciais foram incluídos no Básico ou se só existem notas/lembretes manuais simples. **Se não**, reservar TSK-01 aos planos que tenham o módulo.

### LIB02-M04 — AGD-01, atendimento concluído

- **Fonte:** Agenda AGD confirma mudança efetiva para "Concluído"; evento deve diferenciar agendamento, presença, confirmação e conclusão.
- **Condição:** serviço da agenda Básico (um profissional, até dez serviços ativos), vínculo correto com lead/contato, usuário autorizado.
- **Efeito:** aviso interno de acompanhamento pós-atendimento, não comunicação ao cliente, cobrança ou alteração da reserva; tarefa comercial apenas se direito próprio.
- **Pendência:** distinguir do aviso obrigatório de mudança de status que já pertence ao sistema (SO); se o conteúdo for idêntico, **não cobrar slot** e não lançar novo template comercial.

### LIB02-M05 — AGD-02, cliente não compareceu

- **Fonte:** status AGD "Não compareceu" registrado por funcionário autorizado; não inferir falta por silêncio de confirmação do cliente nem automaticamente pela hora.
- **Condição:** sem disparo duplicado; não substituir estado de reserva por aviso e não enviar ao cliente.
- **Efeito:** ação comercial realmente adicional e útil após falta, se aprovada; aviso operacional SO já permanece.
- **Pendência:** comercial LEG-10 sugere Essencial+, com Básico a avaliar; manter **pendente de decisão de produto**, sem incluir por analogia a LEA-02.

### LIB02-M06 — AGD-03, remarcação/cancelamento

- **Fonte:** alteração efetiva/confirmada AGD, com histórico de horário anterior/novo e razão autorizada.
- **Condição:** distinguir mudança do próprio usuário e cancelamento protegido, nunca reservar/cancelar autonomamente.
- **Efeito:** acompanhamento adicional opcional, se comprovado valor comercial e direito de ação; **aviso operacional de remarcação/cancelamento é SO**.
- **Pendência:** não permitir dupla notificação nem cobrança implícita por ação que já integra avisos internos da Agenda.

## 4. Comparativo de valor versus esforço/risco (sem orçamento inventado)

| Modelo | Utilidade Básico | Dependência ainda em aberto | Custo marginal mais relevante | Priorização |
|---|---|---|---|---|
| LEA-01 | Alta — agir após captação | Categoria A03 de aviso comercial e A01 de tarefa | Volume de eventos/dedupe e retenção de histórico | Estudar primeiro |
| LEA-02 | Alta — prevenir esquecimento | Relógio de inatividade, critérios de interação, aviso | Jobs agendados e idempotência, carga por tenant | Estudar segundo |
| TSK-01 | Média — gerir prazos | Existência do objeto Tarefa no Básico | Fila de prazos e avisos | Decidir direito antes |
| AGD-01 | Média — pós-atendimento | Diferença SO vs AC e ação própria | Eventos e avisos de Agenda | Estudar após DAT |
| AGD-02 | Média — recuperar ausência | Permissão comercial e confirmação de falta | Eventos de falta e avisos | Diferir para revisão de planos |
| AGD-03 | Baixa se reproduzir aviso SO | Valor adicional distinto do sistema obrigatório | Duplicação de avisos | Não ofertar sem diferencial |

**Não associar custos externos por padrão:** nenhuma dessas automações requer WhatsApp, e-mail externo, IA, provedores pagos, autoagendamento ou n8n. Pode haver custo interno de execução/armazenamento, ainda sem métricas; não alegar custo zero.

## 5. Direitos que NÃO podem ser concedidos por esses modelos

- Básico não recebe editor de lógica livre, scripts, URL arbitrária, chatbot/WhatsApp API, e-mail automático ao cliente, IA Prospector/Comercial, agenda pública nem múltiplos usuários por causa de modelo/slot.
- Uma automação não pode assumir que uma anotação manual é "tarefa comercial criada" ou que a comunicação humana foi realizada.
- Duplicidade de pessoas não autoriza mesclar leads/contatos, operação reservada à iniciativa e confirmação do administrador; sem exclusão em cascata.
- Alterar permissões, plano pago, créditos, pagamento, status oficial financeiro ou auditoria fica fora do editor genérico.
- Ganho de disponibilidade ou ativação de template não altera recebimento durável de leads, política 3+15 dias (18 dias) dos payloads de Site nem avisos obrigatórios da Agenda.
- Básico **pago** e Básico **cortesia** são o mesmo plano funcional, e uma organização CRM vinculada a vários sites não recebe vagas extras por quantidade de sites.

## 6. Matriz de validação futura e rastreabilidade

| Caso | Resultado mínimo |
|---|---|
| M01-T01 | LEA-01 não reage a HTTP 202 do Site; apenas evento oficial CRM |
| M01-T02 | Reenvio técnico do mesmo lead não duplica aviso/tarefa |
| M01-T03 | Dois envios legítimos para mesma pessoa permanecem distintos; sem mesclagem automática |
| M02-T01 | Interação válida antes do prazo rearma ou cancela LEA-02 corretamente |
| M02-T02 | Permanência sem interação não gera lembretes repetidos sem limitação homologada |
| M03-T01 | TSK-01 recusa ser ativado se o direito Tarefas comerciais não constar do Básico |
| M04-T01 | Conclusão de atendimento AGD não é inferida do horário de término |
| M04-T02 | Modelo AGD-01 não duplica aviso SO no mesmo evento |
| M05-T01 | Cliente não confirma a reserva; sistema **não** registra "não compareceu" nem cancela automaticamente por silêncio padrão |
| M06-T01 | Remarcação gera aviso operacional sem cobrar slot; follow-up adicional requer modelo autorizado |
| SEC-T01 | Outro tenant/usuário não recebe aviso nem consulta dados do lead ou agendamento |
| FIN-T01 | Exceder limite vigente de slots não compra upgrade nem bloqueia operações SO |

**Nenhum teste foi executado.** São cenários de análise para integrar AUT-T/AUT-X/CAT01/HT sem criar suites duplicadas.

## 7. Pendências específicas LIB02-P01..P08

| ID | Pendência | Proprietário de decisão / requisito |
|---|---|---|
| **LIB02-P01** | Confirmar no Básico: aviso comercial configurável A03 e permissões de tarefa comercial A01, sem confundir com notas/lembretes manuais | Comercial CRM + DAT/Tarefas + CORE-P0 |
| **LIB02-P02** | Aprovar LEA-01 individualmente (com efeito, filtros, origem manual/site, direitos, idempotência e contagem de slot) | Produto CRM + DAT |
| **LIB02-P03** | Aprovar LEA-02 individualmente (critério de interação, prazos, anti-repetição, condições do Básico) | Produto CRM + DAT/Datas |
| **LIB02-P04** | Decidir inclusão de TSK-01 ou reserva a planos pagos após confirmar o módulo Tarefas do Básico | Produto CRM + Tarefas |
| **LIB02-P05** | Decidir se AGD-01 tem ação comercial distinta do aviso SO | Produto CRM + AGD |
| **LIB02-P06** | Decidir se AGD-02 tem valor comercial e elegibilidade Básico | Produto CRM + AGD |
| **LIB02-P07** | Evitar AGD-03 duplicar avisos SO; se oferecido, aprovar efeito realmente adicional | Produto CRM + AGD/COM |
| **LIB02-P08** | Confrontar matriz LEG-08/13, LEG-10, AUT-CAT01/MAT01/LIB01, custos, gatilhos, códigos e testes antes de homologação | Comercial + Arquitetura + Segurança |

**Status após revisão:** LIB01 é **base documental aceita**; LIB02 é **proposta de conferência individual, não aprovada**. AUT-P01..14, AUT-MAT-P01..P10, LIB-P01..P12, CORE/INT/SEG e gates P0 permanecem em aberto.

**Próxima deliberação recomendada:** ratificar o critério de **priorizar LEA-01/02 como estudos candidatos para avisos internos**, sem presumir que criação automática de tarefa avançada está liberada no Básico; resolver **LIB02-P01** antes de aprovar cada modelo. O restante do catálogo pode ser trabalhado em bloco por categoria.

**Somente documentação no GitHub; sem programação, contratação, testes reais ou despesas.**


## 8. Prioridade LEA-01/02 e alternativa de tarefas (04/11/2026)

A [decisão formal](CRM-DEC-prioridade-LEA01-LEA02-2026-11-04.md) aprovou **priorizar o estudo** de LEA-01 e LEA-02 na biblioteca do CRM Básico, usando notificações internas quando autorizadas, **sem aprovar individualmente os modelos** nem criação de tarefas comerciais. O modelo de dados e regras de acesso ainda dependem de validação CORE/DAT.

**LEA-01:** evento oficial de lead confirmado no CRM/DAT, após persistência; mero recebimento HTTP 202 na API de Sites não gera aviso. Propor um aviso interno ao administrador, condicionado a autorização A03. Diferenciar origens site, manual e importação; repetição do mesmo evento de origem não produz duplicidade, mas envios legítimos distintos não devem ser mesclados.

**LEA-02:** detectar inatividade pelo Motor de Datas, com prazo a homologar. Definir qual interação realmente conta como retorno; abrir a ficha do lead ou ler a notificação não demonstra contato. Propor um aviso por ciclo de inatividade, rearmando somente após interação legítima; revalidar estado e permissões na execução.

**Tarefas comerciais no Básico — decisão separada e pendente:**

| Alternativa | Efeito | Avaliação |
|---|---|---|
| TAR-A | Somente aviso interno comercial, sem tarefa | Preferência para primeira avaliação; exige direito próprio de aviso A03 |
| TAR-B | Pendência/tarefa básica limitada para o único administrador e ligada ao lead | Maior utilidade, mas exige autorização expressa e reaproveitamento do serviço de tarefas existente |
| TAR-C | Tarefa comercial avançada com equipes, SLA e recorrência | Não recomendada no Básico; preservá-la nos planos pagos elegíveis |

**Ainda pendente:** escolha TAR-A/B/C, autorização do aviso comercial distinto dos avisos obrigatórios da Agenda, contratos oficiais T01/T13/A03 e A01 se aplicável, prazos, retenção, limites de execução e aprovação individual dos modelos. Preservar LIB02-P01..08, LIB-P01..12, AUT-P01..14, CORE/SEG/INT e gates P0. Sem programação, custos ou execução real.


## 9. Escolha TAR-A aprovada em 04/11/2026

Por resposta “ok” à recomendação de **Alternativa A — avisos internos**, o responsável **aprovou a diretriz funcional TAR-A** para a primeira versão dos candidatos LEA-01 (lead confirmado pelo CRM) e LEA-02 (inatividade validada), conforme [CRM-DEC TAR-A](CRM-DEC-escolha-TAR-A-avisos-internos-LEA01-LEA02-2026-11-04.md).

**Efeito escolhido:** aviso **interno comercial** ao administrador autorizado, sujeito ao contrato oficial de notificações A03 e à validação de direitos, estado e tenant. **Não criar tarefas automaticamente A01** nesses dois modelos da primeira versão; a hipótese TAR-B/TAR-C não foi aprovada. **A escolha de tipo de efeito não equivale à aprovação individual dos modelos.**

Continuam pendentes: eventos canônicos, fontes, prazo/interação válida do LEA-02, contenção de avisos repetidos, autorização técnica A03 e política de retenção, slots finais, casos de teste e homologação CORE/SEG/INT. Os lembretes operacionais da Agenda permanecem **SO fora das vagas comerciais**. Nenhuma programação, implantação, compra ou ativação autorizada.


## 10. Complemento de critérios LIB04: interação, prazos e antiduplicidade (04/11/2026)

A [AUT-LIB04 v0.1](CRM-AUT-LIB04-criterios-interacao-prazos-antiduplicidade-LEA01-LEA02-v0.1.md) foi preparada como **proposta sujeita a deliberação específica**. Recomenda LEA-02 com semântica de **primeiro atendimento pendente pela empresa**, separada de "cliente não respondeu"; sugere marcar interação válida somente por fato oficial auditável, **24h corridas como prazo padrão candidato** e opções guiadas 4/12/24/48/72h. Prevê um aviso por evento/ciclo, sem reiniciar por leitura ou por mudança de versão. **Nada disso está aprovado como parâmetro final**, salvo a diretriz TAR-A já vigente. Preservar LIB04-P01..P09, AUT-P01..14, LIB02-P01..P08 e gates P0; sem implementação.


## 11. Semântica de LEA-02 aprovada, parâmetros ainda pendentes (04/11/2026)

[CRM-DEC](CRM-DEC-semantica-LEA02-primeiro-atendimento-2026-11-04.md): LEA-02 = **lead sem primeiro atendimento efetivo registrado pela empresa**, não cliente que deixou de responder após contato. **Somente essa definição funcional foi aprovada**. Tentativa não atendida, prova de atendimento, regra para observações manuais do Básico, horários, prazo padrão, idempotência, retenção, fontes e homologação do próprio modelo LEA-02 continuam por decidir. Não misturar eventos ATD com clock DAT/primeiro atendimento. Continuidade documental apenas, sem ativação de avisos, tarefas ou código.
