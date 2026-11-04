# CRM-AUT-LIB01 — Biblioteca de modelos prontos do Básico e capacidades dos editores por plano (v0.1)

**Data:** 04/11/2026.  
**Status:** **PROPOSTA FUNCIONAL/COMERCIAL PARA REVISÃO; NÃO APROVADA.** O responsável respondeu “ok” à proposta anterior de **detalhar** a biblioteca e os níveis de edição. Isso **autoriza somente produzir a minuta**, não publicar modelos, fixar quotas/preços ou homologar funcionalidades.

**Fase:** exclusivamente planejamento e documentação. **Não programar, executar testes reais, ativar modelos, comprar licenças/canais, provisionar servidores ou alterar produção.**

**Fontes obrigatórias de precedência:**
- [LEG-10 (branch histórica) — Biblioteca e diferenciação por plano](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/10-biblioteca-automacoes-e-criterios-de-planos.md), com IN-003/004/005/006/007/010/013 e inventário candidato LEA/OPP/AGD/TSK/RPT/MSG/INT/IA.
- [LEG-04 (branch histórica) — Empresas/usuários/CRM](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md), com automações comerciais por evento/tempo, distribuição e editor visual de **chatbot WhatsApp** (domínio distinto).
- [CRM-REV-05 — Automações e chatbot](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md); [CAT01 — inventário funcional aceito](CRM-AUT-CAT01-catalogo-gatilhos-condicoes-acoes-por-modulo-v0.1.md); [MAT01 — segmentação por plano aceita como base](CRM-AUT-MAT01-matriz-elegibilidade-automacoes-por-plano-v0.1.md).
- [REV-02 — Planos/assinaturas](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md), [REV-03 — Dados](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [REV-04 — Atendimento](CRM-REV-04-atendimento-whatsapp-conversas.md), [REV-06 — Vendas](CRM-REV-06-vendas-funis-propostas-catalogo.md), [REV-07 — Agenda](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md), [REV-08 — IA](CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md).
- [AUT-P0 — Contrato de execução segura, base aceita](CRM-AUT-P0-execucao-segura-motor-proprio-automacoes-v0.1.md); [Decisão — motor próprio CRM / n8n interno](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md).

## 1. Diretrizes já existentes — não reabrir nem converter em capacidade nova

1. **Básico, público ou em cortesia, possui as mesmas funcionalidades do plano Básico**; preço e isenção não alteram direitos. O Básico tem **biblioteca de modelos permitidos**, sem editor arbitrário de gatilhos/ações; não herda canal WhatsApp, agente IA, autoagendamento, integrações pagas ou funcionalidades de planos superiores. Agenda Básico é **interna/manual**, com **um usuário autenticado (administrador com MFA), um profissional agendável e até 10 serviços ativos**.
2. **Números históricos de referência a ratificar somente após inventário integral:** slots de automações comerciais ativas **Básico 3 / Essencial 5 / Profissional 20 / Empresarial 50 / Trial 2**, por **empresa/tenant**. Modelo em rascunho não consome slot; não há limite fechado de número de modelos disponíveis nem de rascunhos; volume de execução é política separada. Chatbots WhatsApp: **Essencial 3 / Profissional 10 / Empresarial 30**, sujeitos a canal e contrato específicos.
3. **IN-013:** lembretes e notificações internas essenciais da Agenda Básico — inclusive aviso configurável padrão **24h e 1h** — são **operação de sistema SO**, e não ocupam slot comercial. Notificação de compromisso criado, remarcado, cancelado ou conflito gerado pela Agenda segue a mesma classificação de sistema quando essencial; **não** equivale a WhatsApp/e-mail ao cliente.
4. **Motor próprio do CRM** executa automações dos clientes; n8n para operações internas Imagine permitidas pela licença. O construtor de automações comerciais **não** emite workflows n8n ocultos.
5. Chatbot WhatsApp possui **seu próprio editor visual já aprovado funcionalmente** no capítulo histórico; isso **não aprova automaticamente** um editor visual com o mesmo escopo no Básico ou um editor comercial arbitrário em qualquer plano. WhatsApp oficial, comunicação externa, API, IA, calendário externo, relatórios agendados e outros provedores são **separadamente habilitados e tarifados** conforme regras vigentes.
6. Direitos/quotas por módulo, integração, canal, equipe, versão, ação, campo e organização são revalidados em **ativação e em cada efeito**; uma ação PR/X não pode ser montada no editor genérico, nem por IA, webhook, template importado ou API.
7. Operações fundamentais de criar/confirmar lead, autenticar acesso, confirmar agenda com prevenção de dupla reserva, manter dados e auditoria **não dependem de slot comercial nem de saldo de IA**.

## 2. Proposta de lançamento: biblioteca Básico com poucos modelos úteis, não poucos modelos totais

**Objetivo de produto sugerido (ainda não aprovado):** Básico deve ajudar empresas pequenas a agir sobre leads e compromissos **sem custos variáveis externos**, em especial os leads captados pelos sites Imagine, mas também leads originados legitimamente dentro do CRM Básico. A cortesia não é condição para acessar a biblioteca: cliente pagante Básico tem os mesmos recursos.

**Princípio de curadoria:** catalogar modelos suficientes para o cliente escolher; **não** criar na implementação um modelo premium disfarçado, nem limitar artificialmente a biblioteca a três itens pelo fato de existir referência histórica a três **automações ativas**.

### 2.1. Candidatos prioritários (somente previsão de catálogo, não publicáveis ainda)

| Cód. histórico | Nome amigável | Disparo oficial e condição | Efeito proposto | Parâmetros que o cliente poderá definir de modo guiado | Risco e pendência |
|---|---|---|---|---|---|
| **LEA-01** | Novo lead: criar acompanhamento | **T01** apenas após lead **efetivamente registrado/confirmado no CRM**, com origem autorizada | **A01** criar tarefa interna e/ou **A03** aviso interno para responsável existente | Ativar/desativar; responsável elegível (no Básico, o único admin se aplicável); categorias/etiquetas permitidas; texto interno sem segredos | **Prioridade B1 (P)**. Não disparar por mero HTTP 202 da API dos Sites. Validar se tarefas/notificações e parametrizações fazem parte dos direitos do Básico |
| **LEA-02** | Lead sem retorno: lembrar responsável | **T13** inatividade confirmada, sem interação válida no prazo | **A03** aviso interno e/ou **A01** tarefa interna | Prazo em horas/dias dentro de política homologada; tipo de lead elegível; ativar/desativar aviso | **B1 (P)**. Motor de Datas; reset por interação válida; dedupe e proteção antispam; não disparar mensagem externa |
| **TSK-01** | Tarefa vencida: alerta | **T12** prazo vencido com tarefa ainda não concluída | **A03** aviso interno ao responsável autorizado | Tipos de tarefa ou prioridade elegíveis, ativar/desativar | **B1 (P)** se tarefas disponíveis no Básico. Não criar tarefa de retorno duplicada nem cobrar slot pelo aviso obrigatório de outra natureza |
| **AGD-01** | Atendimento realizado: acompanhamento interno | **T20** apenas após conclusão efetiva AGD registrada por funcionário autorizado | **A01** tarefa interna de pós-atendimento e/ou **A03** aviso interno | Serviço de agenda elegível, prazo de acompanhamento, texto interno | **B2 (P)**; não criar reserva ou contato externo; exige AGD confirmar o atendimento e avaliar se tarefa comercial está inclusa |
| **AGD-02** | Falta confirmada: lembrar acompanhamento | **T20** status "Não compareceu" efetivo AGD | **A01/A03** tarefa/alerta interno | Serviços elegíveis, prazo e responsável | **B2 (P)**; documento LEG-10 sugeria Essencial+, Básico **a avaliar**; não presumir direito |
| **AGD-03** | Alteração/cancelamento: acompanhamento opcional | **T19/T20** em mudança real e validada | **A01** tarefa opcional de contato interno (se houver direito) | Evento de interesse permitido, prazo e responsável | **B2 (P)**; separar de aviso essencial **SO**, que não ocupa slot; não autorizar cancelamento automático nem mensagem ao cliente |

**B1:** forte candidato à biblioteca do Básico, ainda sem aprovação como item; **B2:** candidato secundário dependente de decisão de módulo/monetização. **P significa proposta**. A entrada no catálogo requer direitos por plano, contratos de evento/ação, custos, critérios de segurança e homologação específicos. Não definir valor-padrão fictício para tempos/alertas.

### 2.2. Modelos históricos que NÃO devem ser incluídos automaticamente no Básico

| Código histórico | Descrição | Tratamento recomendado |
|---|---|---|
| **OPP-01** | Oportunidade parada | LEG-10 sugeriu "Básico + pagos", mas MAT01/REV-03 não confirmam acesso do Básico ao módulo de oportunidades/VEN. **Bloquear elegibilidade Básico até conciliar o direito efetivo.** Não confundir lead sem resposta (LEA-02) com oportunidade VEN |
| **OPP-02/03/04** | Proposta sem retorno; aceita/recusada; mudança de etapa com múltiplas condições | **Planos pagos candidatos**, exigindo VEN/propostas, estados e versões oficiais; OPP-04 avançado condicionado |
| **LEA-03/04/05/06** | Rodízio, menor carga, origem/campanha, redistribuição por SLA | Geralmente requer vários usuários/equipes, indisponíveis como recursos nativos do Básico de **um usuário**; candidatos pagos e condicionados |
| **LEA-07** | Scoring + ação condicional | Requer scoring confirmado por módulo, permissão e elegibilidade comercial; não conceder automaticamente no Básico |
| **RPT-01** | Relatório agendado | Categoria **relatórios** com direitos/custo separados, não um slot livre que habilita exportação/disparo |
| **INT-01** | Aplicativo externo | Conector/API + autorização/contrato, sem webhook/URL arbitrária |
| **MSG-01/02** | Chatbot e WhatsApp externo | Serviços/canal com condições e quotas distintas; **não incluídos em modelos Básico** |
| **IA-01** | Agente de IA qualifica/atua | Crédito, modelos e entitlements próprios, nunca liberados por slot de automação |

**Modelo "Compromisso próximo":** avisos internos padrão da Agenda Básico são **SO e gratuitos em slots**, não devem renascer como LEA/AGD comercial cobrando uma vaga. Quando houver uma **regra adicional opcional de pós-atendimento**, a classificação comercial poderá ser diferente, se expressamente aprovada.

## 3. UX do Básico: configuração guiada, sem editor de lógica

**Proposta de experiência:**
1. O administrador do Básico entra em **Automações > Biblioteca** e vê modelos com nome simples, descrição do efeito e classificação: disponível para seu plano, não disponível ou sujeito a outro serviço. Não exibir elementos que façam o usuário acreditar possuir recurso não contratado.
2. Ao selecionar modelo elegível, visualizar **o que dispara, o que verifica e o que fará**, com ícone e campos de configuração de **lista fechada** por modelo. Não mostrar construção livre de nós, gatilhos, expressões, scripts nem escolha arbitrária de ação.
3. A configuração só permite parâmetros explícitos do template homologado: nome interno, responsável elegível, prazo dentro de opções aprovadas, serviço/etiqueta/campo acessível, ativar/pausar. **Cada parâmetro permitido deve ser homologado por modelo**; os exemplos acima não são direitos já concedidos.
4. Antes de ativar, exibir contagem de **vagas ativas consumidas** e restante. Se cheio, explicar e oferecer **pausar modelo ativo** ou consultar planos superiores, sem criar upgrade/cobrança automática.
5. Mostrar estado ativo, pausado, bloqueado por plano, histórico, última execução e falha sem expor segredos; permitir suporte e reativação com auditoria.
6. Se o Básico for cortesia ou assinatura paga, a UX e o catálogo são **idênticos**. Nenhum site adicional multiplica os slots da **mesma organização CRM**. Empresas CRM diferentes possuem direitos próprios apenas quando seus contratos/vínculos forem válidos.
7. Gatilhos essenciais continuam operando com zero automações comerciais. O usuário pode desativar **avisos internos da Agenda quando a própria política permite**, sem isso consumir/liberar vagas da biblioteca.

**Não incluir sem aprovação:** importação livre de JSON, chamada SQL, scripts, regex aberta sobre PII, edição livre do fluxo, envios externos, gatilhos sobre dados financeiros, integração de apps por URL, publicação por IA sem autorização, acesso ao n8n e autoagendamento público.

## 4. Editor de automações comerciais nos planos pagos — proposta de diferenciação (não direitos aprovados)

O **editor visual do chatbot WhatsApp** (já definido no capítulo histórico) e o **editor de automações comerciais** são experiências/cotas distintas, compartilhando motores centrais, identidade, segurança e auditoria, sem duplicar runtime. É possível reaproveitar componentes de interface, mas **não conceder canal/quotas/credenciais por analogia**.

| Capacidade | Básico | Essencial — proposta E1 | Profissional — proposta P1 | Empresarial — proposta M1 | Trial |
|---|---|---|---|---|---|
| Biblioteca de modelos prontos | Apenas modelos homologados para Básico | Catálogo do plano + elegibilidade | Catálogo do plano + elegibilidade | Catálogo do plano + elegibilidade | Subconjunto trial, a definir |
| Personalizar parâmetros de modelos | **Guiada/lista fechada** | Guiada + parâmetros tipados permitidos | Guiada + parâmetros tipados permitidos | Guiada + parâmetros tipados permitidos | Limitada conforme trial |
| Criar automação nova do zero | **Não** | **Proposta:** construtor comercial simples tipado | **Proposta:** construtor visual mais expressivo | **Proposta:** mesmo motor visual, governança ampliada | **A decidir**: prova limitada e sem canais externos presumidos |
| Gatilhos/condições | Fixos por modelo | **Proposta:** combinar gatilho + filtros autorizados, sem código livre | **Proposta:** condições compostas E/OU, ramificação e ações encadeadas | **Proposta:** regras compostas com escopos de equipe/unidade e maior governança | Sem presumir todas as capacidades de Profissional |
| Ramificação e execução | Fixas por modelo | **Proposta:** sequência simples, sem complexidade avançada | **Proposta:** ramificações e múltiplas ações homologadas | **Proposta:** versões, compartilhamento e gestão conforme RBAC | Limitações a aprovar |
| Escopo de equipes/unidades | Um usuário no Básico | Conforme usuários/unidades efetivos do plano | Equipes, distribuição e disponibilidade, quando direitos contratados | Múltiplas unidades/equipes e alçadas se direito | Trial 2 usuários, escopo restrito |
| Simulação e prévia | Prévia de efeitos de modelo; simulação sintética a homologar | **Proposta:** teste sintético de regra antes de publicar | **Proposta:** caminhos múltiplos com dados sintéticos e análise de conflitos | **Proposta:** rastreabilidade e aprovação por papel quando exigido | **Proposta:** amostra segura, nunca efeito real |
| Rascunhos, versões, pausas, histórico | Conforme modelo | Contratos de estado e publicação | Idem + composição complexa | Idem + governança e controle por responsáveis | Preservar limites do trial e retenção |
| Integrações, WhatsApp, IA, agenda pública | **Não presumir direito** | **Separados/condicionados** | **Separados/condicionados** | **Separados/condicionados** | **Não presumir**; API pública do trial desligada |
| SQL, shell, código externo arbitrário, bypass de permissão | **Proibidos** | **Proibidos** | **Proibidos** | **Proibidos** | **Proibidos** |

**E1/P1/M1 são rótulos de proposta, não planos técnicos ou produtos novos.** Recomendação: evoluir com um só mecanismo de edição e execução **tipado, autorizado e versionado**, com capacidades habilitadas por entitlement; **evitar três motores diferentes ou duplicar motor do chatbot**. Empresas de maior porte ganham controles/escalas **se contratados** — não poderes de alterar cobrança, segurança ou outros tenants.

A diferenciação acima é **hipótese comercial** para posterior aprovação, especialmente o **editor simples do Essencial**, a **ramificação mais avançada do Profissional** e a **governança de múltiplas unidades do Empresarial**. Não transformar essas opções em direitos pela redação de um documento.

## 5. Matriz de candidatos históricos para futura publicação (sem conceder plano)

| Código | Família | Básico | Essencial | Profissional | Empresarial | Status |
|---|---|---|---|---|---|---|
| LEA-01/02 | Lead novo / sem resposta | **B1 proposta** | Candidato | Candidato | Candidato | Dependem de contrato DAT/Tarefa e revisão |
| TSK-01 | Tarefa vencida | **B1 proposta** | Candidato | Candidato | Candidato | Confirmar escopo tarefas |
| AGD-01/02/03 | Pós-atendimento, no-show, alterações da agenda | **B2 proposta** somente ações opcionais elegíveis | Candidato | Candidato | Candidato | Distinguir SO e AC |
| OPP-01 | Oportunidade parada | **Não presumir** | Candidato com VEN | Candidato com VEN | Candidato com VEN | Reconciliação LEG-10 × MAT01 |
| OPP-02/03/04 | Propostas/etapas complexas | **Não** | Candidato por módulo; OPP-04 a avaliar | Candidato | Candidato | Propostas e versões oficiais |
| LEA-03/04/05/06/07 | Distribuição, SLA, scoring | **Não presumir** (um usuário) | Candidato conforme equipe, canal e scoring | Candidato | Candidato | Equipe real/permite |
| RPT-01 | Relatório agendado | **Não** | **Condicionado** | **Condicionado** | **Condicionado** | Direito de relatório/exportação próprio |
| INT-01 | Acionar aplicativo externo | **Não** | **Condicionado** | **Condicionado** | **Condicionado** | Integração homologada |
| MSG-01/02 | Chatbot e mensagens WhatsApp | **Não presumir** | **Condicionado** | **Condicionado** | **Condicionado** | Canais/provedores e quotas independentes |
| IA-01 | Agente IA | **Não presumir** | **Condicionado** | **Condicionado** | **Condicionado** | Créditos, autorização e modelo próprios |

**Candidato não significa automaticamente disponível.** O inventário histórico LEG-10 não possui aprovação individual de elegibilidade por template. Devem ser aprovados os modelos por **dependência, plano, custo/risco, permissões, teste e versão**, depois da revisão dos módulos.

## 6. Metadados e estados de cada modelo

**Metadados candidatos (não schema físico):**
- Identificador e versão estável, nome/finalidade, grupo e público, resumo compreensível.
- Gatilho **oficial** e produtor DAT/VEN/AGD/ATD/INT; condição com campos/autorização, ação tipada e módulo **proprietário do efeito**, e referências T/C/A do CAT01 (sem IDs executáveis inventados).
- Plano(s)/direitos elegíveis, contrato de canal adicional (se houver), campos configuráveis de lista fechada e valores/validações.
- Risco/custo/impacto; consumo de slot comercial **sim/não** (SO falso), retenção/auditoria, idempotência, antirrecursão, prévia/simulação, casos de erro e teste.
- Publicação/autoria, compatibilidade/versão, descontinuação/migração, localização e manual de uso/Universidade Imagine **somente para clientes elegíveis durante serviço contratado** (não conceder acesso à Universidade no trial sem contrato efetivo).
- **Não** registrar senha, token, API key ou dado pessoal em definição exportável.

**Fases de biblioteca:** proposto → revisado → homologado → publicável → publicado → depreciado/retirado conforme política final. **Rótulos documentais**, não criação de estados de banco.

**Ciclo da instância do tenant:** escolher modelo → rascunho/configuração → validação de direito e schema → ativação autorizada → em execução/pausada/bloqueada/desativada, preservando histórico e versão; downgrade pausa excedente e versão não é alterada silenciosamente. Confirmar as transições oficiais na AUT-P0/CORE.

## 7. Critérios eliminatórios, casos de teste e controle de custo

| Caso futuro | Resultado mínimo exigido |
|---|---|
| LIB-T01 — Duas URLs de formulário/duas tentativas entregam mesmo lead lógico | LEA-01 gera no máximo **um efeito lógico** de tarefa por automação/lead, mantendo tentativas |
| LIB-T02 — Um lead tem **novo envio legítimo distinto** | Não deduplicar pessoas por telefone/e-mail; agir conforme eventos oficiais distintos |
| LIB-T03 — Lead chegou na API Sites, mas CRM ainda não confirmou | LEA-01 não dispara até evento oficial DAT/CRM |
| LIB-T04 — Cliente Básico tenta editar o gatilho livremente | Negar/editor guiado; somente parâmetros homologados |
| LIB-T05 — Básico possui 3 slots ativos e tenta ativar quarto (número histórico provisório) | Explicar quota; oferecer pausa de modelo ou comparação de planos, sem compra automática |
| LIB-T06 — Básico com avisos Agenda 24h/1h e nenhum slot comercial | Avisos SO continuam sem consumir slot |
| LIB-T07 — Agenda teve falta de comparecimento confirmada, mas cliente tenta acionar WhatsApp | Somente interno se modelo elegível; negar canal externo sem contrato |
| LIB-T08 — Básico tenta usar OPP-01 sem direito ao módulo de oportunidades | Negar; não supor que modelo histórico da biblioteca concede VEN |
| LIB-T09 — Downgrade de plano interrompe modelo premium durante fila | Revalidar antes do efeito, pausar e preservar definição/histórico |
| LIB-T10 — Funcionário de tenant A tenta publicar modelo em tenant B | Negar via RLS/RBAC e auditar |
| LIB-T11 — Editor pago monta URL/SQL/código livre disfarçado de ação | Negar na UI, API e execução |
| LIB-T12 — Evento externo retorna HTTP 200/202 sem efeito oficial confirmado | Status pendente/inconclusivo, reconciliação; não marcar sucesso |
| LIB-T13 — Atendimento foi assumido por humano | Qualquer mensagem automática ao cliente é impedida mesmo após fila/retry |
| LIB-T14 — Proposta foi somente visualizada | Não disparar evento de proposta aceita |
| LIB-T15 — Dois agendamentos tentam confirmar mesmo recurso | Agenda evita conflito/transação; motor não declara reserva |
| LIB-T16 — Agente IA pede autorização para alterar preço/plano | Negar, IA não possui alçada financeira por prompt |
| LIB-T17 — Publicar v2 de modelo altera instância v1 ativa | Proibir mutação silenciosa; política de migração e versão explícita |
| LIB-T18 — Empresa muda plano mas mantém sites/cortesia elegíveis | Reavaliar o direito pelo estado contratual oficial, preservar dados sem duplicar quotas por site |

**Cenários não executados**; deverão conciliar AUT-T01..14, AUT-X01..18, CAT01 N01..16 e HT/G01..G06 sem replicar obrigações homologadas. Não fixar taxas de disparo, tempo, slots ou retenção novos por esta proposta.

## 8. Pendências vinculadas e trilha de homologação

| ID | Tema de decisão | Evidência necessária antes de fechamento |
|---|---|---|
| **LIB-P01** | Lista definitiva de modelos do Básico | Aceite **por item** de LEA-01/02, TSK-01, AGD-01/02/03 e eventuais outros, com módulos e canais excluídos |
| **LIB-P02** | Direitos do Básico para tarefas, agenda e acompanhamento | Compatibilizar matriz Básico, ausência de oportunidades/funcionários extras e modelos legacy OPP-01 |
| **LIB-P03** | Template JSON/esquema e parâmetros guiados | Tipos, validação, valores permitidos, autorizações e versionamento por modelo |
| **LIB-P04** | Editor Essencial | Decidir construtor tipado simples/fluxos lineares, conjuntos de gatilhos/ações e complexidade |
| **LIB-P05** | Editor Profissional | Decidir ramificação E/OU, ações múltiplas, equipes e distribuição, todos sob direitos reais |
| **LIB-P06** | Editor Empresarial | Decidir governança, escopos multiunidade, alçadas, compartilhamento e relatórios, sem superpoder |
| **LIB-P07** | Trial | Conjunto de modelos/editor, tempo 14 dias, 2 slots, limites próprios, sem canal externo presumido |
| **LIB-P08** | Verificação econômica | Custo por ação, quotas técnicas, canais, antiabuso, volume de execuções; não confundir slots com eventos |
| **LIB-P09** | Catálogo público/UX, migração e descontinuação | Elegibilidade, estados, histórico, troca de modelo/versão, downgrade e acessibilidade |
| **LIB-P10** | Revisão global dos limites comerciais | **Depois** do inventário/encerramento de catálogo e dos custos: ratificar ou alterar números 3/5/20/50, trial 2 e chatbot 3/10/30 mediante aprovação expressa |
| **LIB-P11** | Governança dos testes e contratos | P0 CORE/SEG/INT, produtor dos eventos, efeito oficial, permissões e reconciliação com AUT-P01..14 |
| **LIB-P12** | Comunicação/educação | Guias e treinamento in-product; Universidade somente quando produto for desenvolvido e serviço elegível ativo, sem acesso público/trial presumido |

**AUT-P06/P07/P08/P09/P10/P11/P14, AUT-MAT-P01..P10, CORE/SEG/INT e gates P0 continuam abertos**. Este documento apresenta decisões comerciais para revisão; **não** declara prontidão de implementação.

## 9. Próximo aceite sugerido — não implícito neste documento

Para próxima deliberação:
1. **Aceitar esta v0.1 como base documental** da biblioteca com candidatos B1/B2, preservando a necessidade de aprovar cada modelo do Básico antes de habilitá-lo.
2. **Aprovar ou ajustar a proposta de diferenciação editorial** E1/P1/M1: editor simples tipado no Essencial; ramificação e múltiplas ações no Profissional; governança/escopos no Empresarial, todos sem código livre e com n8n fora do executor comercial.
3. Depois fechar **LIB-P01..P12** com CAT01/MAT01/REV-05, sem alterar quotas ou criar novas ofertas por suposição.

**Fase continua estritamente documental; programação não iniciada; nenhuma licença, contrato, servidor, canal, preço, limite ou implementação foi aprovado.**
