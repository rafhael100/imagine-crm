# CRM-AUT-LIB05 v0.1 — Classificação e comprovação do primeiro atendimento do lead no Básico

**Data:** 04/11/2026. **Estado:** **MINUTA TÉCNICO-FUNCIONAL PARA REVISÃO; NÃO APROVADA COMO CONTRATO, SCHEMA, UI OU IMPLEMENTAÇÃO.**
**Avanço autorizado:** após o responsável concordar em prosseguir, detalhar o mecanismo de registro de tentativas e de atendimento efetivo sem reabrir decisões aprovadas.
**Precedência:** [Aprovação funcional LEA-01/02](CRM-DEC-aprovacao-regras-funcionais-LEA01-LEA02-2026-11-04.md), [semântica LEA-02](CRM-DEC-semantica-LEA02-primeiro-atendimento-2026-11-04.md), [tentativa sem sucesso](CRM-DEC-LEA02-tentativa-nao-atendida-nao-e-atendimento-2026-11-04.md), [LIB04](CRM-AUT-LIB04-criterios-interacao-prazos-antiduplicidade-LEA01-LEA02-v0.1.md), [CRM-REV03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [CRM-REV05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md).
**Proibição:** planejamento e documentos apenas. Não programar, editar dados de clientes, ativar modelos, instalar ou contratar serviços, nem executar testes reais.

## 1. O que está aprovado versus o que a minuta propõe

**Já aprovado:** LEA-02 monitora primeiro atendimento efetivo pendente da empresa; ligação não atendida é tentativa e não encerra pendência; primeiro atendimento só é concluído por registro válido de usuário autorizado; TAR-A emite avisos internos, não tarefas; um aviso por ciclo; cronômetro de **24 horas corridas padrão**, opções **4, 12, 24, 48 ou 72 horas corridas**, a contar do registro **oficial do lead no CRM**. LEA-01 contempla fatos oficiais de captação manual e de sites integrados, com tratamento especial para importações em massa, sem avalanche de avisos.

**Ainda pendente:** o esquema oficial que materializa um registro válido de atendimento, permissões exatas do Básico, UX/validadores, estados, provas, tratamento de contestação, contatos externos, retenção, API/eventos T01/T13/A03, homologação individual dos modelos, plano de testes e implantação.

**Regra de não duplicação:** o Básico permite **observações e histórico manual** (IN-023). Esta minuta recomenda **adicionar uma classificação estruturada ao histórico existente** e registrar fato oficial pelo serviço DAT/Atendimento somente quando permitido, **sem criar um segundo motor de tarefas** e sem liberar tarefa comercial A01 no Básico.

## 2. Taxonomia candidata de acontecimentos

| Código de minuta | Tipo/fato de negócio | Pode marcar primeiro atendimento realizado? | Regra |
|---|---|---|---|
| R01 | Tentativa telefônica sem resposta, número ocupado, chamada não completada | **Não** | Persistir tentativa autorizada e resultado, separada de atendimento |
| R02 | Mensagem de saída sem comprovação de contato efetivo | **Não por si só** | Entrega/leitura de canal não é atendimento; canal externo exige direito próprio |
| R03 | Contato telefônico atendido com conversa efetiva e resultado registrado | **Sim, após validação** | Ator autorizado declara canal, resultado e resumo mínimo; não implica venda concluída |
| R04 | Atendimento presencial efetivo ou conversa direta realizada e registrada | **Sim, após validação** | Fato atribuído ao usuário, momento e contexto válidos |
| R05 | Cliente envia mensagem de entrada, sem atuação da empresa | **Não por si só** | Contato inbound é diferente de atendimento realizado pela empresa |
| R06 | Troca de mensagens com resposta efetiva da equipe em canal habilitado e registro oficial | **Sim, se o contrato do canal comprovar e o serviço proprietário confirmar** | Não presume WhatsApp, chatbot, API pública ou IA no Básico |
| R07 | Observação livre, nota, visualização, alteração de campo/etiqueta/responsável, mudança de etapa | **Não automaticamente** | São histórico administrativo, sem prova de contato |
| R08 | Atendimento registrado incorretamente e corrigido por responsável autorizado | **Reavaliar** | Preservar evento original e correção, não apagar trilha; recalcular pendência sem duplicar aviso |

**Não confundir** resultado de uma ligação, status de entrega de mensagem, resposta do cliente, resolução do assunto e efetiva interação da empresa com o lead. *Atendimento efetivo* não requer fechamento de venda, mas requer uma interação real com o cliente e registro atribuível. Uma autodeclaração manual fornece evidência documental, não prova tecnológica infalível de que a conversa aconteceu. A interface não deve apresentar isso como verificação externa automática.

## 3. Campos semânticos propostos (não equivalem a tabelas nem contrato)

- **Identidade/tenancy:** `tenant_id`, `lead_id`, `interaction_id`, `actor_user_id`, escopo/autorização, módulo produtor e `correlation_id`.
- **Classificação:** `interaction_kind` (tentativa / atendimento realizado / resposta do cliente / nota administrativa), `channel_type` (ex.: telefone/presencial/canal já contratado), `contact_outcome` (ex.: não atendida / conversa realizada / resposta recebida / não aplicável), origem manual ou fonte homologada.
- **Tempo e evidência:** instante do evento informado, instante de persistência oficial, timezone de referência, resumo mínimo não sensível conforme finalidade, referência ao registro de origem quando houver, tipo de comprovação e revisão/retificação.
- **Controle de idempotência e versão:** `event_id`, versão semântica, `source_event_id` se houver, `causation_id`, estado válido/corrigido/revogado, ator que corrigiu e motivo.
- **Resultado consumível pelo motor de automações:** um **fato tipado validado** publicado pelo módulo proprietário, e não um valor booleano editável livremente pelo navegador ou por prompt de IA.

Os nomes e valores acima são **semânticos candidatos**, não schema de banco nem endpoint. Evitar campo “atendido=true” isolado, sem ator, resultado, autorização e histórico. Restringir texto livre para não coletar desnecessariamente dados sensíveis; logs técnicos não armazenam conteúdo de conversa ou segredo de canal.

## 4. UX guiada proposta ao único administrador do Básico

1. Na ficha do lead, preservar o histórico atual e oferecer, quando aprovado, registro com **tipo de contato** em lista fechada: *Tentativa sem sucesso*, *Atendimento efetivo*, *Resposta do cliente* ou *Observação administrativa*. Não criar acesso a recursos avançados de equipe ou chatbot.
2. Para tentativa sem sucesso: selecionar modalidade permitida, resultado e observação curta opcional. O CRM mostra “Tentativa registrada; primeiro atendimento continua pendente”.
3. Para atendimento efetivo: selecionar modalidade, informar que houve interação real e registrar resultado suficiente, com autoria e momento. Confirmar explícita e conscientemente o lançamento. A autoridade final é o módulo DAT/Atendimento, e **não** a interface, com auditoria/regras de consistência.
4. Para resposta recebida: registrar resposta como fato distinto; sem marcar automaticamente atendimento da empresa, salvo se tiver ocorrido atendimento efetivo distinto e validado.
5. Para nota administrativa: oferecer histórico simples já autorizado, **sem** alterar o relógio de primeiro atendimento.
6. Corrigir registro: permitir retificação autorizada **com motivo e rastreabilidade imutável**, sem excluir o fato anterior, evitando que uma correção vire um segundo primeiro atendimento.
7. Mostrar estado legível do LEA-02: *Primeiro atendimento pendente*, *Tentativa(s) registrada(s)*, *Primeiro atendimento realizado* ou *Modelo não ativo / lead inelegível*; são **rótulos de interface propostos**, não novos estados oficiais do sistema.
8. Quando o usuário desativar um modelo, perder direito, arquivar lead ou houver incongruência, revalidar a elegibilidade antes de qualquer aviso. Reaproveitar os motores de Datas, Eventos, Notificações e Auditoria Geral.

## 5. Contagem de prazo e invariantes aprovadas

- Contagem inicial de **24h corridas por padrão**, opções guiadas **4/12/24/48/72h**, a partir do **fato oficial de criação do lead no CRM**, não da primeira recepção pelo backend Sites. Prazos são direitos funcionais já aprovados; cálculos, timezone, política de alteração do prazo e implementação carecem de homologação.
- Tentativa não atendida **não** interrompe LEA-02. Registrar uma ou muitas tentativas não cria “atendimento efetivo” por contagem.
- Atendimento efetivo validado **encerra o primeiro ciclo pendente**, mesmo se cliente não comprar/não concluir contratação; a política de acompanhamento depois do primeiro atendimento pertence a outro fluxo não habilitado aqui.
- Se o primeiro contato for retificado como inválido, o DAT deve reconciliar a condição e o Motor de Datas reavaliar o ciclo, **sem inventar novo início, duplicar notificação ou apagar auditoria**. A consequência para aviso já entregue exige especificação.
- Cliente respondeu antes de funcionário atender: não encerrar pendência; quando funcionário efetivamente atende e há registro válido, encerrar.
- Cada aviso interno comercial LEA-02 conta como efeito do modelo **quando este estiver homologado/ativado**, com dedupe por organização + modelo + lead + ciclo; **sem duplicação** por retry/versionamento de workflow.
- O aviso de LEA-01 (novo lead) não prova atendimento e não deve encerrar LEA-02. Ler o aviso, abrir o lead e marcar como visto também não.
- Alertas essenciais da Agenda Básico (SO) permanecem independentes dos slots comerciais. Custos e retenção das notificações comerciais serão homologados separadamente dos 90 dias da Agenda.

## 6. Contrato de produtor e consumidor — hipótese a validar

| Fronteira | Autoridade | Resultado esperado |
|---|---|---|
| Ação humana na ficha | Administrador autenticado, 1 assento Básico com MFA | Pedido de registrar uma interação tipada elegível, não prova técnica por si só |
| Validação/persistência | DAT/Atendimento, serviço oficial proprietário | Verificar tenant, autorização, lead, esquema, tipo, autoria, dados e idempotência; gravar fato e trilha |
| Publicação de evento | Motor Central de Eventos/contrato de outbox | Emitir fato tipado somente depois de confirmação oficial |
| Controle de prazo | Motor de Datas/Prazos e Motor de Fluxos | Cancelar/reavaliar prazo LEA-02 ao reconhecer primeiro atendimento efetivo; tentativas não o encerram |
| Aviso | Notificações internas A03, somente quando modelo/direito autorizados | Persistir aviso único por ciclo e mostrar somente a usuário com acesso atual |
| Auditoria/correções | Auditoria Geral + serviço proprietário | Proveniência, retificação, tentativa, ator, versão e evento sem apagar evidências |

Não criar integração direta que permita ao site, webhook externo, n8n, agente IA, formulário público ou cliente não autenticado declarar o primeiro atendimento efetivo e suprimir o alerta. Qualquer processamento automatizado futuro de fatos de canais oficiais deve ser autorizado/homologado por produto e plano específico.

## 7. Cenários obrigatórios de homologação futura — não testados

| ID | Evento | Critério esperado |
|---|---|---|
| L05-T01 | Ligação sem resposta gravada | Registra tentativa; LEA-02 segue pendente |
| L05-T02 | Atendimento telefônico realmente realizado e documentado | Serviço proprietário confirma o fato e encerra condição de primeiro atendimento |
| L05-T03 | Usuário abre ficha e altera etiqueta | Não encerra prazo |
| L05-T04 | Cliente envia mensagem sem resposta humana | Não encerra pendência |
| L05-T05 | Operador lança uma nota administrativa | Não equivale a atendimento |
| L05-T06 | Cliente declara atendimento anterior com data retroativa | Política de backdating/retroatividade a homologar; impedir ocultação indevida de alerta já emitido |
| L05-T07 | Atendimento inválido é retificado | Rastro permanece, condição de LEA-02 é reconciliada sem duplo aviso |
| L05-T08 | Tentativa duplicada chega por retentativa técnica | Não duplica registro lógico nem aviso |
| L05-T09 | Segundo usuário indevido em Básico tenta registrar contato | Negar por RBAC/tenant; Básico tem 1 admin |
| L05-T10 | Registro de atendimento usa lead de outra empresa | Rejeitar, auditar, não expor dados |
| L05-T11 | Lead foi excluído/arquivado antes do registro | Bloquear ou tratar conforme estado protegido e política de titularidade |
| L05-T12 | Canal WhatsApp não está habilitado no Básico | Não conceder canal/autoatendimento por formulário de interação |
| L05-T13 | Vencimento e registro chegam simultaneamente | Concorrência e estado oficial impedem aviso indevido ou duplicado |
| L05-T14 | Relação entre sítio Imagine e CRM alterada | Não reatribuir histórico antigo nem gerar falso primeiro atendimento |
| L05-T15 | Em Basic cortesia ou pago, mesmo modelo e evento | Mesmas regras e quotas por organização, sem multiplicação por site |

**Nenhum cenário executado.** Reconciliar com LIB04, AUT-X, DAT-T e HT/G01..G06, sem multiplicar suites redundantes.

## 8. Pendências específicas LIB05-P01..P09

1. **LIB05-P01:** aceitar ou ajustar taxonomia R01..R08, considerando canais e casos de contato sem resposta.
2. **LIB05-P02:** ratificar o aproveitamento do histórico manual de leads (IN-023) com **tipo e resultado estruturados**, sem módulo novo de tarefas.
3. **LIB05-P03:** definir campos obrigatórios, catálogo de resultados, prova/verificação, retificação e política contra marcação indevida; **sem afirmar prova externa para registro manual**.
4. **LIB05-P04:** matriz de direitos CORE-P0: quem registra, visualiza, corrige, audita, quais campos e quando; Básico 1 admin com MFA.
5. **LIB05-P05:** regras de estados especiais/registro retroativo, lead arquivado, excluído, convertido, migrado ou reaberto, incluindo consistência do relógio.
6. **LIB05-P06:** contratos tipados DAT/ATD/Notificações/Eventos, idempotência, proveniência e concorrência para LEA-02.
7. **LIB05-P07:** critérios de UX/ acessibilidade/privacidade, mensagens do aviso comercial e retenção (distinta de 90 dias da Agenda).
8. **LIB05-P08:** capacidade de carga, uso justo, monitoramento, alertas técnicos e validação quantitativa.
9. **LIB05-P09:** conciliação AUT-P01..14, LIB02-P01..P08, LIB04-P02..P09, DAT-P01..12, CORE/SEG/INT e gates P0 para aprovação individual dos modelos.

**Decisão recomendada na próxima etapa:** aceitar esta taxonomia e a experiência guiada como **base de revisão**, sem inferir aprovação de schemas, validação técnica de “prova”, campos ou software. Depois discutir como verificar atendimento e retificações e homologar o contrato de interação com DAT.

**Status final:** documento não aprovado; programação, implantação, contratação e ativação não autorizadas.
