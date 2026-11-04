# CRM-AUT-CAT01 — Catálogo de Gatilhos, Condições e Ações por Módulo (v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA DE ESPECIFICAÇÃO FUNCIONAL PARA REVISÃO — NÃO APROVADA.** O “ok” do responsável autorizou **avançar o planejamento e preparar este catálogo**, não ratificou seus itens individualmente.  
**Escopo:** automações configuráveis por clientes dentro do **motor próprio CRM Imagine**, e fronteiras com operações obrigatórias, IA, atendimento, agenda, vendas, dados, canais, FIN/CON/INT.  
**Regra:** exclusivamente documentação; não executar automação, configurar VPS, comprar licenças, alterar repositórios de código de aplicação nem iniciar programação.  
**Fontes:** [CRM-REV-05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md), [AUT-P0 v0.1 — execução segura, base documental aceita](CRM-AUT-P0-execucao-segura-motor-proprio-automacoes-v0.1.md), [CRM-DEC motor próprio](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md), [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [CRM-REV-04](CRM-REV-04-atendimento-whatsapp-conversas.md), [CRM-REV-06](CRM-REV-06-vendas-funis-propostas-catalogo.md), [CRM-REV-07](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md), [CRM-REV-08](CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md), [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md), [INT-R01](integracoes/INT-R01-governanca-contratos-homologacao.md).

## 1. Objetivo e classificação do catálogo

Um único **catálogo lógico, versionado e homologável**, cujos itens apontam para serviços/motores centrais já definidos; **não criar novo motor, fila, relógio, aprovação nem executor paralelo**.

**Classificação funcional:**
- **SO — Sistema obrigatório:** operação necessária do sistema; não é um slot de automação comercial, não depende de automação ativa e não pode ser desabilitada por esgotamento de planos.
- **AC — Automação comercial configurável:** depende de plano/entitlement, permissões, slot comercial e regra publicada; não cria automaticamente direitos sobre canais.
- **CE — Canal/externo/chatbot/IA:** requer contrato, provedor, credenciais, consentimento quando aplicável, franquia, política e autorização distintos; não é desbloqueado só por possuir slot AC.
- **PR — Protegido/reservado:** somente comando de módulo proprietário ou de operador autorizado com aprovação adicional quando exigida. Não aparece como ação livre do editor comercial.

**Proveniência de cada linha:** **R** = regra/família funcional já descrita nas revisões aprovadas como base; este catálogo ainda carece de schemas/condições finais. **C** = candidato de organização do catálogo, derivado de família aprovada, ainda requer decisão expressa e fonte/evento oficial. **X** = não permitir exposição como ação configurável genérica, preservando autoridade exclusiva. **R não significa que o evento técnico ou ID já esteja homologado**.

A interface deve mostrar **somente** itens compatíveis com tenant, usuário, permissões, plano, configuração e integrações. Não confiar no filtro visual; a execução e retomada devem revalidar tudo no servidor.

## 2. Inventário de gatilhos (tipos semânticos candidatos, sem nomes de eventos técnicos oficiais)

| ID CAT | Domínio/autoridade | Gatilho oferecível | Tipo/status | Restrição e confirmação |
|---|---|---|---|---|
| T01 | DAT/Leads | Lead criado **e confirmado no CRM** | AC/R | Não disparar por mero HTTP 202 da API de Sites; ID lógico estável e confirmação CRM |
| T02 | DAT/Leads | Alteração autorizada de campo/etiqueta de lead ou contato | AC/R | Somente campos elegíveis, versões e mudanças reais, não todas as escritas técnicas |
| T03 | DAT/Leads | Responsável comercial alterado | AC/C | Evento oficial após transferência efetiva; não por reserva provisória |
| T04 | DAT/Leads | Lead pontuado/reclassificado | AC/C | Score 0–100 oficial e mudança válida, não a mera sugestão da IA |
| T05 | VEN/Funil | Oportunidade criada | AC/R | CRM/VEN confirma objeto/tenant; deduplicação por ID |
| T06 | VEN/Funil | Oportunidade mudou de etapa | AC/R | Incluir etapa anterior/nova e causa sem permitir fluxo autogatilhar indefinidamente |
| T07 | VEN/Funil | Oportunidade marcada como ganha/perdida | AC/R | Respeitar regras de motivo/permissão e fatos oficiais VEN |
| T08 | VEN/Proposta | Proposta enviada | AC/C | Somente status efetivo; rascunho/aprovação pendente não contam |
| T09 | VEN/Proposta | Proposta aceita/recusada | AC/R | Versão exata e aceite/recusa oficial; visita ao link não é aceite |
| T10 | VEN/Proposta | Proposta expirada ou vencimento próximo | AC/C | Regras do Motor de Datas, validade e fuso; não renovar silenciosamente |
| T11 | VEN/Tarefas | Tarefa atribuída ou alterada | AC/R | Evitar reação repetida a mero heartbeat/atualização interna |
| T12 | VEN/Tarefas | Tarefa vencida ou concluída | AC/R | Instante e estado oficialmente confirmados |
| T13 | DAT/VEN | Inatividade de lead/oportunidade/proposta durante prazo configurado | AC/R | Nova interação elegível rearma tempo; relógios próprios por domínio |
| T14 | ATD/Atendimento | Nova conversa/mensagem recebida e validada pelo canal | CE/R | Respeitar janela do provedor e origem autenticada |
| T15 | ATD/Atendimento | Solicitação de humano ou entrada em fila | CE/R | Fila FIFO; não atribuir posse humana automaticamente |
| T16 | ATD/Atendimento | Atendimento assumido, transferido ou encerrado efetivamente | CE/R | Origem mantém posse até aceite real; bloquear respostas automáticas após posse humana |
| T17 | ATD/Atendimento | Atendimento reaberto após retorno do cliente | CE/R | Estados e política de reabertura pertencem ao ATD |
| T18 | ATD/CSAT | Avaliação de atendimento recebida | AC/C | Alerta interno conforme autorização; não oferecer envio externo sem direito |
| T19 | AGD/Agenda | Agendamento criado ou status confirmado/alterado | AC/R | Propriedade AGD; diferenciar confirmação de presença e situação operacional |
| T20 | AGD/Agenda | Agendamento cancelado/reagendado/concluído/não compareceu | AC/C | Cancelamento/reagendamento oficial; não replicar efeito sobre recursos |
| T21 | AGD/Agenda | Lembrete interno devido (ex. padrão Básico 24h e 1h, configuráveis) | SO/R | **Não consome slot** e não envia automaticamente WhatsApp/e-mail ao cliente |
| T22 | AGD/Agenda | Vaga de lista de espera disponibilizada | CE/C | FIFO estrito; vaga só confirmada após aceite e revalidação do recurso |
| T23 | INT | Integração externa retornou resultado **oficialmente confirmado** | CE/C | INT transporta, proprietário confirma; evento inconclusivo não vira sucesso |
| T24 | IA | Resultado de análise IA autorizado e validado pelo proprietário | CE/C | Crédito, custo, segurança, origem e controle humano; texto do modelo não é fato oficial |
| T25 | FIN/CON | Assinatura/benefício alterado ou pagamento confirmado | SO/PR-C | **Eventos oficiais do proprietário** podem alimentar avisos internos do sistema; **não expor mudança de plano/receita/pagamento como gatilho comercial aberto sem decisão específica** |

**Dependência para oficialização:** identificar para cada T01–T25 o evento canônico real, módulo produtor, versão de esquema, origem da transação, entidade afetada, cardinalidade, idempotency key, tempo de persistência e elegibilidade por plano. Os IDs CAT são apenas identificadores de **minuta**, não contratos executáveis.

## 3. Catálogo de condições (filtros tipados)

| ID | Família de condição | Operadores candidatos permitidos | Fonte/limite |
|---|---|---|---|
| C01 | Identidade e escopo | empresa igual à instância, equipe/unidade/operador conforme membership | **Obrigatória**; nunca condição configurável para escapar do tenant |
| C02 | Dados de lead/contato | campo vazio/preenchido, igual/diferente, inclui/exclui tag, origem conhecida | DAT; limitar campos e finalidades; dados sensíveis condicionais e protegidos |
| C03 | Etapa e responsável comercial | etapa atual/anterior, funil, responsável, status | VEN; valor no evento versus estado atual documentado |
| C04 | Valores comerciais | maior/menor/intervalo, moeda, campos numéricos | VEN; regras de permissão e normalização; não calcular tributo/preço oficial no motor |
| C05 | Score de lead | faixa 0–100, mudou categoria, limite | DAT/VEN; score oficial; não um palpite não confirmado |
| C06 | Tarefa/atividade | vencida/não vencida, prioridade, atribuída/concluída | VEN/Tarefas; timezone e causalidade |
| C07 | Prazo/expediente/inatividade | horas/dias, janela comercial, período transcorrido, ausência de interação | Motor de Datas + domínio; DST/fuso IANA; rearme por interação válida |
| C08 | Atendimento humano | posse humano/IA/fila, disponibilidade, expediente, canal, equipe | ATD; **revalidação obrigatória ao enviar** |
| C09 | Proposta | estado, versão, validade, motivo, aceite confirmado | VEN; visualização de link não é aceite |
| C10 | Agenda | status operacional/confirmado, serviço, profissional, unidade, recurso, capacidade | AGD; condição não reserva vaga, disponibilidade revalidada pelo próprio AGD |
| C11 | Consentimento/privacidade/canal | finalidade permitida, opt-in necessário, contrato canal ativo, janela/template | Segurança/ATD/INT; validação no momento do efeito, sem direito automático por slot |
| C12 | Plano e direito | plano elegível, slot, quota/canal/IA disponível, estado de assinatura | FIN/CON+IAM; **controle obrigatório do servidor**, não bypass pelo construtor |
| C13 | Estado de integração | conexão autorizada, capacidade homologada, status confirmado | INT/módulo proprietário; timeout/200 não é “confirmado” |
| C14 | IA e conhecimento | análise disponível, classificação validada, modelo/uso elegíveis | IA; output é dado não confiável, exigir validação e permissão para qualquer efeito |
| C15 | Combinação lógica | E/OU/negação tipados, lista finita e validação de dependências | Motor de Condições; limites de profundidade/tamanho **pendentes**, evitar expressão ou código livre |

**Tipos previstos de campos:** texto, número, moeda, data, seleção única/múltipla e verdadeiro/falso (REV-03); operadores finais e semântica para vazio/nulo/ausência, números/moeda, campos aposentados e consentimentos devem ser homologados. **Não oferecer SQL, JavaScript/PHP livre, URLs arbitrárias, acesso a segredo, condição baseada em PII de outro tenant ou leitura ilimitada de histórico**.

**Duas classes de avaliação:** condição de seleção no **snapshot do evento** e pré-condição no **estado atual**; efeitos exigem revalidação de autorização, posse do atendimento, disponibilidade, estado do recurso, consentimento e quota imediatamente antes da execução.

## 4. Catálogo de ações propostas (donos, permissões e fronteiras)

| ID | Ação | Tipo/proveniência | Proprietário do efeito e autorização mínima | Bloqueio essencial |
|---|---|---|---|---|
| A01 | Criar tarefa interna vinculada a lead/contato/oportunidade | AC/R | Tarefas/VEN; permissão criar tarefa no escopo | Idempotência por evento/automação/ação para evitar tarefa duplicada |
| A02 | Atribuir/reatribuir responsável | AC/R | DAT/VEN ou ATD conforme recurso; permissão atribuir e escopo equipe | Não mover atendimento humano sem aceite/posse real |
| A03 | Notificação interna a usuário/equipe | AC/R | COM/notificações; destinatário autorizado | Não equivale a mensagem externa; anti-loop/dedupe |
| A04 | Atualizar campo ou etiqueta permitida | AC/R | DAT/VEN; editar campo autorizado, finalidade e versão | Não alterar consentimento/autorizações sem fato e direito próprio |
| A05 | Alterar etapa de oportunidade | AC/C | VEN; permissão mover oportunidade, validar política do funil | Não marcar ganho/perda sem regras, motivo e confirmação VEN |
| A06 | Criar atividade/observação interna autorizada | AC/R | VEN/DAT; permissão escrever, escopo e proteção de dados | Não transformar anotação em mensagem ao cliente |
| A07 | Solicitar distribuição comercial de lead | AC/R | Distribuição/ATD ou VEN conforme recurso; equipe/usuário elegível | Respeitar disponibilidade, carga e política; nenhuma posse humana automática |
| A08 | Pausar/retomar fluxo comercial | AC/C | Motor de Fluxos, gestor com direito de gestão de automação | Retomada revalida plano/usuário e não executa efeito vencido às cegas |
| A09 | Agendar próximo passo/aviso interno | AC/R | Motor de Datas/Tarefas; elegibilidade e permissão | Não criar cron paralelo; evitar duplicidade em mudança de data |
| A10 | Solicitar consulta de disponibilidade de agenda | CE/C | AGD, leitura autorizada pelo contrato de agenda | Consulta não é reserva nem confirmação de vaga |
| A11 | Solicitar agendamento/reagendamento/cancelamento | CE/C | **AGD efetua somente após validação** de capacidade, titularidade, direitos e regras | **Sem autoagendamento no Básico**; nunca ignorar conflitos ou cancelar por silêncio como padrão |
| A12 | Solicitar mensagem WhatsApp ao cliente | CE/R | ATD/COM + provedor oficial; canal/entitlement, template, janela e consentimento | **Posse humana impede disparo automático ao cliente**, mesmo se enfileirado |
| A13 | Solicitar e-mail externo ou webhook homologado | CE/C | COM/INT; canal/contrato/escopo e destino permitido | Não permitir URL livre, segredos no payload ou cobrança implícita |
| A14 | Solicitar atendimento/transferência humana | CE/R | ATD; política de transferência/destino; posse efetiva no aceite | Originador permanece responsável até destino aceitar |
| A15 | Solicitar análise IA, score/sugestão/resumo | CE/R | IA; créditos, modelo homologado, finalidade, fonte, permissão | Sugestão não grava alteração de CRM sem comando posterior autorizado |
| A16 | Registrar alerta para supervisor/gestor sobre CSAT baixo | AC/C | ATD/AGD/COM conforme origem, permissão de visualização | Alertas não expõem conversa/confidencialidade entre equipes |
| A17 | Solicitar comando a integração homologada | CE/C | INT coordena; **módulo proprietário confirma o efeito** | Resultado inconclusivo exige reconciliação; não repetir escrita irreversível |
| A18 | Marcar proposta como enviada/aceita ou cancelar | PR/X | VEN, sob fluxo próprio/atores autorizados | **Não ação livre**; aceite exige versão/prova/autoridade |
| A19 | Confirmar pagamento, conceder plano, isenção, crédito de IA ou reajuste | PR/X | FIN/CON e administração com alçada própria | **Proibido no editor genérico**; cliente não concede assinatura/benefício via automação |
| A20 | Apagar/mesclar dados, exportar base, gerir permissões, apagar auditoria | PR/X | DAT/Segurança/privacidade em processos específicos | **Proibido como ação genérica**; mesclagem e exclusão demandam decisão humana e regras próprias |
| A21 | Efetivar presença/check-in, reserva de recurso ou prioridade na lista de espera | PR/X | AGD e funcionários autorizados, FIFO e capacidade real | **Não expor ação livre**; cliente não faz auto-check-in; sem prioridade VIP/fila excepcional |
| A22 | Alterar diretamente dados de outros tenants, executar SQL, código, shell ou n8n de cliente | PR/X | Nenhum no construtor comercial | **Proibido**; uso de n8n deve seguir licença e motor próprio aprovado |

**Permissão mínima não é papel fixo**: a matriz final CORE-P0-02 mapeará perfil × organização × equipe × recurso × campo × verbo; em geral, **visualizar ≠ alterar ≠ exportar ≠ publicar automação ≠ aprovar ação crítica**. O Administrador da Empresa só atua em sua organização e limites. A IA usa identidade de serviço com autorizações explícitas, não herda direitos amplos do administrador nem da configuração textual.

## 5. Políticas transversais de execução e autorização

1. **Antes de publicar:** validar origem e contratos dos gatilhos, tipos de campos, dependências, ciclo/permissões, categorias de ações, plano e canais. Publicar versão imutável sem mudar instâncias em curso. AI pode sugerir, não publicar ação crítica autonomamente.
2. **Ao reconhecer evento:** aceitar **somente** fato oficial com tenant, correlação, identidade causal, versão e persistência durável; consumir ao menos uma vez com dedupe. API dos Sites só confirma recepção de lead na fila; evento T01 exige criação/associação confirmada pelo CRM.
3. **Ao executar:** revalidar membership, escopo de dados/campos, plano, slot, canal, consentimento, janela, posse humana, estado da oportunidade/agendamento, versão e conflito; auditoria antes/depois.
4. **No timeout/erro de efeito externo:** manter **inconclusivo** e reconciliar com proprietário antes de retentar escrita. Não tomar sucesso HTTP, callback n8n ou resultado de IA como confirmação comercial.
5. **Na suspensão/downgrade:** nenhum slot comercial extra ativo; preservar rascunho/versões/históricos; não bloquear operações obrigatórias. Excedentes pausados por seleção do administrador autorizado.
6. **No custo:** ações com IA/canal/WhatsApp são restritas a seus próprios direitos e franquias; limites por execução/concorrência/retentativa ainda não fixados. Não comprar pacote/upgrade automaticamente.
7. **No isolamento:** o mesmo identificador de lead em duas empresas nunca correlaciona execuções; privilégios do serviço e banco RLS devem conferir tenant real, não aceitar apenas tenant_id enviado pelo cliente.
8. **No tempo:** usar Motor de Datas; não unificar relógio VEN/ATD/AGD; respeitar fuso IANA e políticas por módulo.
9. **No monitoramento:** mensurar backlog, latência, falhas e custos; preservar alertas externos de infraestrutura; operações essenciais do CRM não dependem de n8n nem agentes técnicos.
10. **Na recepção/consistência:** fila de leads de Sites (3 dias + 15 dias; até 18 dias) e fila de automações CRM têm **retenções distintas**; não derivar prazo de AUT da IN-127.

## 6. Regras por plano (não ratificar quotas novas)

**Referências pendentes de revisão comercial final (CRM-REV-05):** automações comerciais ativas por tenant **Básico 3 modelos habilitados, Essencial 5, Profissional 20, Empresarial 50, trial 2**. Chatbot ativo **Essencial 3, Profissional 10, Empresarial 30** (quando canal elegível); **não presumir chatbot no Básico**.

- **Básico:** apenas modelos de catálogo habilitados, sem editor arbitrário; agenda interna/manual; até 10 serviços ativos, 1 profissional agendável, 1 usuário autenticado administrador com MFA; avisos internos de agenda 24h/1h configuráveis são **SO** e não ocupam slots.
- **Trial:** 14 dias sem cartão, dois usuários, 20 leads, dois funis, duas automações, 20 MB, API pública desligada; não conceder automaticamente integração externa ou canais.
- **Essencial/Profissional/Empresarial:** ampliar catálogo, número de slots, canais e editor somente conforme direito específico final; ativação de automação não inclui custo de WhatsApp, provedor ou crédito de IA.
- **Franquias de anexos** 1/5/20/50 GB com bloqueio apenas de novos uploads em 100% não podem impedir tarefas/contatos/agenda/automação de dados estruturados; regras de quota de automações são outras.
- **Rascunhos, pausas e downgrade** preservam definições/históricos e não disparam cobrança/upgrade.

## 7. Exemplos de fluxos para validação (não criados)

| Exemplo | Disparo + condição + ação | Regras de negócio que impedem efeito indevido |
|---|---|---|
| **F01 — novo lead** | T01 CRM confirma lead + C02 origem formulário X + A01 criar tarefa no responsável autorizado | Não responder ao 202 inicial da API; duas tentativas do mesmo envio = uma tarefa lógica |
| **F02 — oportunidade parada** | T13 inatividade + C03 etapa de negociação + A03 notificação interna ao vendedor | Interação válida rearma; tarefa/alerta sem duplicidade e sem mensagem externa |
| **F03 — proposta aceita** | T09 aceite oficial da versão atual + C09 estado vigente + A01 tarefa de próximo passo | Não concluir venda nem gerar pagamento por visualização de link |
| **F04 — conversa fora do expediente** | T14 mensagem recebida + C08 fora do expediente + A14 solicitar fila/transferência | Fila FIFO, sem posse humana fictícia; não enviar se atendente já assumiu |
| **F05 — lembrete agenda Básico** | T21 aviso 24h/1h do Motor de Datas + C10 agendamento elegível + A03 aviso **interno** | **SO**, não conta slot comercial nem envia WhatsApp ao cliente |
| **F06 — cliente agenda paga** | T19 estado confirmado + C11 canal habilitado + A12 aviso WhatsApp autorizado | Somente quando contratado, template/janela válidos; status do provedor ≠ confirmação AGD |
| **F07 — score alto** | T04 reclassificação oficial + C05 faixa definida pela empresa + A01 tarefa ao vendedor | Não usar sugestão IA não confirmada para alterar registro, nem consumir crédito por mero slot |

**Exemplos não são modelos automaticamente disponíveis em cada plano**. A classificação final de templates e os thresholds serão decididos depois da reconciliação do catálogo LEG-10 e dos módulos.

## 8. Cenários negativos e critérios de aceitação

| Teste documental | Resultado esperado |
|---|---|
| N01 — usuário publica gatilho de outro tenant | Negar e auditar |
| N02 — usuário Básico tenta ação reservada à Agenda pública | Negar sem reservar horário |
| N03 — ação A12 preparada, mas humano assumiu antes do envio | Bloquear mensagem; não reenviar acumulado após retorno |
| N04 — duas atualizações de oportunidade provocam fluxo recursivo | Limitar profundidade, pausar e alertar; sem loop infinito |
| N05 — HTTP 202 da API de leads chega antes do CRM | Nenhum T01 até o CRM confirmar |
| N06 — proposta visualizada duas vezes | Não gerar T09 de aceite |
| N07 — duas tarefas tentam alterar a mesma reserva | Só AGD valida efeito atômico, nenhuma dupla reserva |
| N08 — plano é suspenso após enfileiramento de A12 | Revalidar entitlement, bloquear e preservar evidência |
| N09 — execução recebe 2xx sem confirmação externa | Inconclusivo, reconciliar; sem sucesso presumido |
| N10 — IA recomenda conceder benefício ou crédito | Não executar A19; IA não ganha poder financeiro |
| N11 — downgrade mantém automações excedentes publicadas | Pausar excedentes, preservar histórico; avisos SO continuam |
| N12 — mensagens/ações de diferentes tenants compartilham entity_id | Separação total por tenant e causa |
| N13 — cliente tenta usar entrada SQL/script/URL arbitrária | Rejeitar no editor/API e na execução |
| N14 — reprocessamento após crash tenta criar ação duplicada | Dedupe por efeito/contrato/tenant e confirmação oficial |
| N15 — integração externa perdeu credencial | Não retentar sem revalidação; alerta seguro, sem vazamento de segredo |
| N16 — falta de confirmação em reserva AGD | Não cancelar por silêncio na configuração padrão |

Estes são **testes futuros não executados**; devem ser conciliados com AUT-T01..14, AUT-X01..18 e HT/G01..G06, **sem multiplicar obrigações duplicadas**.

## 9. Matriz de responsabilidades e permissões a homologar

| Papel/principal | Pode propor/configurar | Publicar/pausar | Pode executar atos protegidos? |
|---|---|---|---|
| Administrador da Empresa | Configurar somente recursos do tenant/planos elegíveis | Condicional a permissão de gerir automações | **Não** por ser administrador; segue autoridade do módulo e alçada |
| Gestor/funcionário autorizado | Apenas equipes/recursos/ações explicitamente delegados | Separar criar, editar, publicar e pausar por perfil | Não ganha exclusão, financeiro, exportação ou superpermissão |
| Serviço executor do motor | Receber contrato de versão publicada e escopo imutável | Não altera própria definição publicada | Somente comandos tipados e revalidados; sucesso depende do dono |
| Agente IA de cliente | Sugerir edição/fluxo, quando direito e crédito permitirem | Não publicar por inferência textual sem regra aprovada | Nunca concede direitos, plano, permissão ou acesso |
| Agente técnico Imagine/n8n | Operações internas do ecossistema dentro de licença e segregação | Não administra workflows de clientes CRM por n8n Community | Sem bypass RBAC/RLS nem root amplo |
| Admin Geral/operador de suporte Imagine | Gestão SaaS/suporte com limites e acesso excepcional autorizado | Conforme procedimento autorizado | Não herda dados do cliente ou atos financeiros sem aprovação formal |

**Sem permissões globais presumidas:** matriz CORE-P0-02/SEG-01 definitiva deve especificar verbo, recurso, atributo/campo, empresa, equipe, unidade, finalidade, prova de autorização, revogação e auditoria antes da implementação.

## 10. Rastreabilidade e próximos fechamentos

- **AUT-P01 (gatilhos):** este documento apresenta inventário T01..T25 **candidato**, mas evento oficial/owner/schema/catálogo definitivo pendentes.
- **AUT-P02 (condições):** famílias C01..C15; operadores, campos/normalização/semântica final pendentes.
- **AUT-P03 (ações):** catálogo A01..A22, incluindo ações proibidas; contratos de comandos, permissões e confirmações oficiais pendentes.
- **AUT-P04/P05:** loops, idempotência, concorrência, versões e publicação continuam especificações pendentes de parâmetros/homologação.
- **AUT-P06/P07/P08/P13/P14:** catálogo de templates por plano, slots, direitos/quotas, provedores externos e reconciliação comercial ainda pendentes.
- **AUT-P09/P10/P11/P12:** importação, execução/INT, alçadas/visibilidade, tempos/estado por módulo ainda pendentes.
- **CORE-P0-02/03, SEG-01/02 e INT-R01/P0:** identidade/permissões, autorização em jobs, cofre, contratos, idempotência e evidência oficial ainda exigem conciliação formal.

**Deliberações sugeridas ao responsável, somente após revisão desta proposta:**
1. Aceitar T01..T25 / C01..C15 / A01..A22 como **inventário candidato de catálogo**, com as ações PR/X vetadas no editor genérico.
2. Priorizar homologação do subconjunto mínimo DAT + VEN + tarefas/notificações internas + avisos obrigatórios da agenda, preservando canais/chatbot/IA/INT como caminhos condicionais de elegibilidade e contratos.
3. Mandatar a matriz exata produtor do evento → condição elegível → comando tipado → módulo dono do resultado → permissão/entitlement → fonte de auditoria, **sem inventar endpoints, schemas, SLAs ou números agora**.

**Não marcar AUT-P01..14 concluídos com esta minuta.** É estudo funcional, não autorização de software. Nenhum contrato, teste real, infraestrutura ou pagamento aprovado.
