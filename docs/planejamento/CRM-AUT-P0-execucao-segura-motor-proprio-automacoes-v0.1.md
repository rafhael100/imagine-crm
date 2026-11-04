# CRM-AUT-P0 — Execução segura do motor próprio de automações (v0.1)

**Data:** 04/11/2026.  
**Status:** **PROPOSTA TÉCNICO-FUNCIONAL PARA REVISÃO E DELIBERAÇÃO — NÃO APROVADA NESTA ETAPA.**  
**Origem:** avanço do planejamento autorizado pela resposta “ok” à etapa de detalhamento; decisão já **aprovada** de [motor próprio do CRM e n8n apenas para operações internas licenciadas](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md).  
**Limites:** exclusivamente documentação. Nenhuma programação, migração, configuração, teste real, contratação, ativação ou gasto autorizado.

## 1. Objetivo, fronteira e reaproveitamento

Detalhar o contrato de execução do **construtor de automações de clientes do CRM Imagine** (Fluxos, Gatilhos, Condições, Ações), sem criar novo motor redundante. Reutilizar:
- **Motor Central de Eventos** como autoridade para eventos internos tipados/versionados.
- **Motor de Fluxos** como avaliador/orquestrador de definições publicadas.
- **Motor de Condições** para avaliações determinísticas sobre projeções autorizadas.
- **Motor de Ações** para solicitar comandos tipados ao **módulo proprietário** do efeito.
- **Motor Central de Filas**, **Motor de Datas e Prazos**, **Central de Aprovações** e **Auditoria Geral** como componentes já previstos. Não implementar timers, fila, aprovação ou auditoria paralelos do AUT.
- **INT-R01/INT-09/INT-10/INT-12**, via contratos oficiais, apenas para fronteiras externas; HTTP 2xx/n8n não confirma resultado de negócio.

**Regra de separação comercial:** (a) operações obrigatórias do CRM não consomem slots comerciais e não dependem de automações comerciais ativas; (b) automações comerciais configuráveis dependem de plano e slot ativo; (c) chatbot, IA, mensagens externas, canais e integrações têm **entitlements/custos próprios**, não concedidos por mero slot. No Básico, a biblioteca de modelos prontos é distinta do editor arbitrário; quota final ainda será ratificada. Fluxos do cliente **não são traduzidos para workflows n8n**.

## 2. Topologia de lançamento (hipótese, não infraestrutura homologada)

**Proposta inicial E1B:** o backend Laravel do CRM hospeda os processos da aplicação e **workers de automações limitados**, com PostgreSQL em VPS independente. Filas duráveis e estado de workflow podem usar componentes existentes do ecossistema Laravel/PostgreSQL (tecnologia concreta ainda a homologar); **Redis/cache isolado não pode ser fonte única de persistência durável**. Quando o perfil de carga exigir, workers podem migrar a runtime isolado com mesmo contrato lógico e sem reescrever o construtor; essa opção não autoriza nova VPS antecipada.

**Não dependências:** n8n, VPS de agentes técnicos, Portal Imagine, Performance AI e IA externa **não são necessários** para reconhecer, persistir e executar atos internos obrigatórios do CRM. As duas APIs de recepção durável de leads continuam autônomas; sua política específica **3+15 dias (18 dias)** não se aplica indiscriminadamente à fila de automações, que terá retenção própria a definir.

**Requisitos não negociáveis para produção:** quotas de CPU/memória e concorrência; isolamento de dados (tenant + RLS onde aplicável), cofre/segredos, monitoramento externo, recuperação e restauração comprovável; nenhuma promoção a produção sem gates.

## 3. Modelo lógico dos objetos e identidade

Não são tabelas definitivas; são **contratos semânticos** a reconciliar com CORE/INT antes de esquema físico.

| Objeto lógico | Identidade/escopo mínimo | Regra |
|---|---|---|
| Definição de automação | `tenant_id, automation_id`, responsável, categoria, direitos, estado | Rascunho não executa; configurações por organização |
| Versão publicada | `automation_version_id`, contrato/schema, autoria, assinatura/controle de integridade | **Imutável**; nova publicação não altera execução já iniciada |
| Disparo | `event_id, event_type, schema_version, tenant_id, source_module, entity_id, causation_id, correlation_id, occurred_at, persisted_at` | Entrega **at-least-once**; nunca supor exatamente uma vez |
| Instância | `execution_id, automation_version_id, tenant_id, trigger_id` | Estado persistente e causalidade até o efeito confirmado |
| Passo | `step_id, attempt_id, state, resource_version, command_id, dedupe_key` | Tentativa distinta do efeito lógico |
| Aprovação | Identificador de autorização, escopo, validade e ator | Referência à **Central de Aprovações**, não fila duplicada |
| Efeito externo | `operation_id, provider_reference, confirmed_business_result` | Resultado oficial confirmado pelo serviço proprietário |
| Registro de auditoria | ator/identidade técnica, tenant, versão, evento, comando, estado, evidência | Dados pessoais minimizados; íntegro conforme Auditoria Geral |

**Envelopes de evento/ação:** dados de operação estritamente necessários, origem autenticada, versão/compatibilidade, finalidade, idempotência e correlação; **sem tokens, senhas, chaves ou anexos sensíveis em payloads de auditoria**. Valores de campo apenas conforme visibilidade do papel e finalidade autorizada.

## 4. Ciclo de vida, publicação e versionamento

**Definição:** `rascunho → validado → aguardando publicação autorizada → publicado/ativo → pausado/desativado → arquivado`; nomes finais e transições condicionais serão conciliados no CORE/CRM-REV05. Erro de validação mantém rascunho. Não há publicação silenciosa por IA nem por atualização de template.

**Execução:** `recebido → persistido → elegibilidade verificada → pendente de processamento → em avaliação → (rejeitado/não elegível | aguardando condição/data/aprovação | ação preparada) → comando emitido → (confirmado | inconclusivo | falhou/compensação autorizada) → encerrado`. Estados técnicos da INT-R01 e estado de negócio do módulo proprietário **não são intercambiáveis**.

- A **publicação v2** é imutável, exige verificação de regras, dependências e permissões; novas instâncias usam a v2 e instâncias iniciadas conservam a v1 conforme política do tipo de fluxo, especialmente **conversas chatbot**.
- **Pausa/expiração de direito:** não iniciar automação comercial não elegível; instâncias pendentes precisam de regra segura de pausa/cancelamento e revalidação antes do efeito. Preservar histórico; não eliminar por downgrade.
- **Exclusão definitiva** de definições/instâncias e retenção de trilhas ficam sujeitas ao catálogo de dados, LGPD e políticas já existentes — **sem inventar período**.

## 5. Reconhecimento de eventos, integridade e entrega durável

1. Cada módulo proprietário publica **eventos oficiais** após sua confirmação de negócio. Para operações que atualizam estado e publicam evento, especificar **outbox transacional** quando compatível, ou outro mecanismo equivalente que não permita “negócio gravado, evento perdido”.
2. Consumer de automações deve ter **inbox/deduplicação persistente** por tenant + evento lógico + definição/versão + escopo; o mesmo evento pode legitimamente acionar **automações diferentes**, mas não gerar repetição do mesmo efeito lógico.
3. Recebimento externo (lead, webhook, canal) exige autenticação, persistência, versão e proveniência; eventos externos entram apenas pelos módulos e contratos de integração homologados. n8n não é evento oficial do CRM.
4. Evento tardio, reordenado ou duplicado só executa após verificar **estado atual relevante**, versão da entidade e regra de negócio. Não inferir ordem global total.
5. Garantia funcional proposta: **persistência durável + entrega ao menos uma vez + dedupe e efeitos idempotentes quando suportados**; **nunca prometer exactly-once distribuído**. Se efeito externo não for comprovável por consulta de estado, encaminhar resultado inconclusivo para revisão, não repetir escrita cegamente.

## 6. Condições, tempo e autorização

- Avaliador de condições com catálogo tipado (tipos de campo, operadores, nulos, valores monetários, datas, timezone, comparação de strings e escopo). Sem SQL, scripts arbitrários, expressões não homologadas ou leitura trans-tenant.
- Registrar para cada passo se a condição é **snapshot do evento** ou **estado atual**; para ação com efeito, priorizar **revalidação de condições críticas** no momento do comando.
- Motor de Datas e Prazos controla alarmes, rearmar por interação elegível, reabertura e cancelamentos. **ATD, AGD e VEN mantêm seus próprios relógios e estados** (não criar temporizador global único). Calcular internamente instantes em UTC, preservando timezone e política de horário comercial para exibição/agendamento. Hora inexistente/duplicada por mudança de fuso precisa semântica explícita.
- Em cada execução/retomada/ação externa: confirmar **identidade técnica**, empresa, ator causal, assinatura/plano, permissão, autorização, consentimento/finalidade aplicável, canal habilitado, limites e estado real do recurso. **Autorização passada não prevalece sobre credencial revogada ou suspensão atual**.
- IA sugere configurações e ações dentro de permissões; **não concede si própria plano, aprovação humana, execução irrestrita ou exceção à política**. Regras N1/N2/N3 dos agentes técnicos não se misturam automaticamente aos direitos de automação do cliente.

## 7. Filas, concorrência e prevenção de loops

- Filas duráveis por prioridade e categoria, sem obrigar VPS exclusiva no início; separar logicamente **sistema obrigatório**, **comercial**, **externo/canal** e **recuperação**, para evitar que campanha ou automação de um tenant bloqueie operações essenciais.
- **Quota por tenant e por integração**: limitar execuções simultâneas, taxa de disparo e custo de ferramentas por perfil/contrato; valores numéricos **pendentes** e não dedutíveis dos slots de automação.
- Controle de concorrência/compare-and-set/locks por recurso lógico onde necessário: lead, oportunidade, mensagem, reserva/agenda, pagamento e crédito IA. Comandos com efeito exigem idempotency key estável e checagem no proprietário.
- **Prevenção de ciclos**: causation_id/trace, detector de repetição, profundidade/frequência máximas e trava para automação que re-dispare a si própria ou cadeia sem progresso; thresholds e conduta de bloqueio ainda a homologar.
- **Justiça e contenção de falhas**: cotas por tenant, prioridade de sistema, backpressure, circuit breaker por provedor, tempos máximos e liberação de locks ao recuperar. Sem fila compartilhada com APIs independentes de captação como única persistência.

## 8. Falhas, reprocessamento, resultado inconclusivo e compensação

Classificar: **temporária**, **limite/quota**, **sem permissão**, **contrato/versão incompatível**, **dados inválidos**, **segurança**, **resultado externo desconhecido**, **regra de negócio rejeitada**, **indisponibilidade persistente**.

| Falha | Tratamento proposto | Proibição |
|---|---|---|
| Erro temporário antes de transmitir comando | Retentativa limitada com backoff/jitter, preservando identidade e rastro | Repetir ilimitadamente |
| Timeout **após** transmitir ação externa | Estado **inconclusivo**; consultar provedor/proprietário/reconciliador antes de novo envio | Reenviar escrita não idempotente às cegas |
| Plano/consentimento/acesso revogado | Suspender e bloquear efeito, registrar motivo, permitir nova avaliação autorizada | Executar com autorização histórica |
| Mensageria/chatbot com atendimento humano assumido | Bloquear/envio não permitido e descartar eventual mensagem automática acumulada conforme política de posse | Enviar após a transferência só porque estava na fila |
| Agendamento concorrente | Agenda como autoridade confirma ou rejeita reserva atômica | Aceitar dupla reserva por status de workflow |
| Contrato externo sem confirmação | INT reconcilia evidência com módulo proprietário, ou escalona humano | Marcar “sucesso” só por 2xx |
| Falha persistente/dados inválidos | Fila de falhas/quarentena **lógica** com permissão de reprocessamento e alerta; prazo específico a decidir | Copiar retenção de 18 dias de leads ou apagar automaticamente |
| Compensação | Só por comando tipado homologado e alçada/consentimento exigidos; auditar antes/depois | Supor rollback reversível para qualquer ação |

Um worker que reinicia pode retomar a partir de checkpoint confirmado; cada tentativa tem ID distinto, mas **não duplica o ato de negócio**. Reconciliação prevalece sobre retentativa em operações inconclusivas. **Nenhum prazo numérico novo de retry, fila morta, exclusão ou reprocessamento está aprovado por este documento**.

## 9. Quotas, planos, direitos e custos

**Referências registradas na REV-05, ainda sujeitas a ratificação comercial final:**
- Slots comerciais ativos por tenant: **Básico 3 (modelos permitidos), Essencial 5, Profissional 20, Empresarial 50; trial 2**.
- Chatbot/WhatsApp ativo: **Essencial 3, Profissional 10, Empresarial 30** — quota distinta e condicionada a canal/entitlement; não deduzir direito ao chatbot em Básico nem por slot comercial.
- **Rascunhos não contam slots**. Downgrade não apaga definições e histórico; excedentes são pausados conforme seleção administrativa autorizada.
- **Avisos essenciais internos da Agenda no Básico não consomem slot**. Leads/contatos/dados estruturados não têm limite rígido de arquivo que impeça operações oficiais; quotas de anexo e canal são independentes.
- Consumo de IA por créditos/empresa, recarga opcional e tarifação por operação são **outro regime**; não conceder franquia IA por habilitar automação.
- Não aprovar cobrança, contratação, upgrade ou adicional por excesso de limite automaticamente; comunicar e permitir decisão administrativa do cliente.
- Planos congelados, suspensos, em cortesia e em trial exigem transições de direito detalhadas em REV-02 e segurança, verificadas na hora do efeito. Nunca confundir **benefício do site** com assinatura comercial independente do CRM.

**Proposta de limites técnicos (números pendentes):** concorrência por tenant, taxa por tipo de gatilho, throughput global de workers, tentativas, tempo de processamento, profundidade de cadeia, uso de ferramentas, faturamento/custo de canais, limiares de alertas e retenção de instâncias/execuções. Não confundir **quantidade de definições ativas** com **número de execuções**.

## 10. Auditoria, observabilidade e segurança

- Auditoria Geral guarda quem criou, alterou, publicou, pausou e aprovou a automação, versões, escopos, causalidade, tentativas e resultados oficiais; acessível apenas a papéis autorizados, sem privilégio do administrador de tenant para adulterar trilha.
- Separar **métricas de execução e traces técnicos** de histórico funcional por lead, oportunidade, agendamento e conversa. Políticas vigentes de logs técnicos (IN-162) não devem ser transpostas como retenção de dados CRM sem classificação.
- Painel do tenant mostra status legível (**em execução, aguardando, pausado, falhou, inconclusivo, concluído**) e motivo apropriado sem expor segredo, dado de outra empresa ou detalhes sensíveis de infraestrutura.
- Monitoramento e alertas críticos externos à VPS CRM e n8n; backlog, idade da tarefa mais antiga, falhas repetidas, inconsistências, atrasos de relógio, status outbox/inbox, crescimento de disco e saturação. Canal técnico para a Central de Incidentes, sem depender da IA.
- Recuperação offsite, backup/PITR, replay **apenas de eventos elegíveis**, controle de integridade, testes com dados sintéticos, documentação de falhas e verificação de restore; RPO/RTO de automações pendentes de definição e teste.
- Usar PostgreSQL/RLS/tenant_id e token de contexto de serviço validado. Não confiar exclusivamente no tenant_id que venha do body; cofre para credenciais de conectores e versões de segredo, sem armazenar tokens em workflows/exports.

## 11. Critérios de homologação documental e futura prova

| Código | Cenário futuro obrigatório | Evidência esperada |
|---|---|---|
| AUT-X01 | Evento duplicado de mesmo tenant e definição | Uma instância/efeito lógico, tentativas auditadas |
| AUT-X02 | Dois tenants com mesmo entity_id numérico | Isolamento integral de eventos, execuções, permissões e segredos |
| AUT-X03 | Workflow comercial suspenso, mas evento obrigatório de sistema | Processamento obrigatório continua |
| AUT-X04 | Plano cai e job já estava na fila | Revalida direito; bloqueia efeito não permitido e preserva histórico |
| AUT-X05 | Executor recebe HTTP timeout depois de transmitir pagamento/envio | Marca inconclusivo, reconcilia; sem duplicidade |
| AUT-X06 | Fluxo A desencadeia A ou cadeia circular | Limite, contenção, evidência e alerta |
| AUT-X07 | Publicação de v2 durante execução da v1 | Instância existente mantém versão; novas seguem política de publicação |
| AUT-X08 | Recurso de Agenda disputado por dois fluxos | Só Agenda confirma reserva; nenhuma dupla marcação |
| AUT-X09 | Humano assumiu conversa antes do disparo da mensagem | Mensagem automática não enviada |
| AUT-X10 | Integração/canal externo indisponível | Backoff ou suspensão; outros tenants e sistema obrigatório continuam |
| AUT-X11 | Crash antes/depois da outbox gravada e ACK da fila | Reconciliar sem perder ou duplicar efeito |
| AUT-X12 | Revogação de credencial/consentimento/escopo | Ação bloqueada antes do efeito, inclusive ao reprocessar |
| AUT-X13 | Esgotamento de slot ou crédito IA | Não comprar/ativar/recargar automaticamente; comunicação adequada |
| AUT-X14 | Restauração de backup e replay controlado | Reconciliar checkpoints, versões e efeitos já confirmados |
| AUT-X15 | Gatilho temporal com mudança de horário local/fuso | Executar no instante elegível único conforme contrato homologado |
| AUT-X16 | Importação e alteração de template de chatbot | Rascunho, remapeamento e versão publicada preservada; nenhum segredo importado |
| AUT-X17 | Falha de n8n ou agentes técnicos | Motor próprio CRM e ingresso durável de leads não dependem destes |
| AUT-X18 | Regras de prioridade e quota sobrecarregadas em tenant A | Não bloquear operações obrigatórias e demais tenants |

**TODOS OS CENÁRIOS ACIMA SÃO FUTUROS E NÃO EXECUTADOS.** A matriz complementa, **não substitui**, AUT-T01..14 da REV-05 e HT/G01..G06 das integrações. Resultados, limites quantitativos e evidências de homologação serão preenchidos depois da autorização global para programar e testar.

## 12. Rastreabilidade dos itens AUT-P01..14 (não declarar fechamento prematuro)

| Pendência | Avanço proposto por este documento | Situação após v0.1 |
|---|---|---|
| **AUT-P01** | Envelope de evento e fonte oficial do módulo | Catálogo gatilhos/owners por módulo **pendente** |
| **AUT-P02** | Semântica de condição tipada, snapshot x estado atual | Operadores/campos e escopos finais **pendentes** |
| **AUT-P03** | Contrato ação tipada, efeito oficial e autorização | Inventário ação×módulo e permissões **pendente** |
| **AUT-P04** | Outbox/inbox, idempotência, locks, loop guard, replay | Política quantitativa e contratos executáveis **pendentes** |
| **AUT-P05** | Versões imutáveis, transição, pausa/revalidação | Política de migração/compatibilidade final **pendente** |
| **AUT-P06** | Preservada biblioteca e elegibilidade | Catálogo comercial final **pendente** |
| **AUT-P07** | Não mudou slots 3/5/20/50; trial2; chatbots 3/10/30 | Revisão comercial conjunta **pendente** |
| **AUT-P08** | Contadores separados de slot/canal/IA/execução | Entitlements e quotas finais **pendentes** |
| **AUT-P09** | Proteção de imports e remapeamento | Schema JSON e compatibilidade **pendentes** |
| **AUT-P10** | Donos motores centrais vs INT/n8n e filas | Contratos CORE/INT executáveis **pendentes** |
| **AUT-P11** | Revalidar escopo, papéis, auditoria/retomada | Matriz criação/publicação/pausa **pendente** |
| **AUT-P12** | ATD/AGD/VEN próprios relógios, sem timer novo | Mapear tempos/estados específicos **pendente** |
| **AUT-P13** | Não habilitar canais externos por slot | Custo/canal/Meta/BSP **pendente** |
| **AUT-P14** | Respeitar LEG-10, ATD/VEN/AGD/IA | Conciliação inventário final **pendente** |

## 13. Deliberação solicitada e gates

**Para análise posterior, sem aprovação por silêncio:**
1. Ratificar o **contrato de execução proposto**: outbox/inbox quando aplicável, checkpoints, confirmação oficial de efeitos e revalidação no momento da ação, sem motores paralelos.
2. Ratificar **separação entre operações obrigatórias / comerciais / canais** e regra de que fila do CRM não equivale à fila técnica independente de captação de leads.
3. Definir em etapas específicas, com responsáveis por negócio/segurança: catálogo de gatilhos/ações por módulo, política de prioridades, contadores técnicos, limites numéricos, retenção de execuções, versões/migrações e direitos finais.
4. Reconciliar CORE/INT, REV-01/02/04/05, SEG-01/02, AUT-P01..14 e gates G01..G06 antes de fechar P0. **Esta minuta não resolve nenhum deles automaticamente**.

**Nenhum código, teste real, infraestrutura contratada, migração de n8n ou provisionamento autorizado.** A decisão aprovada de *motor próprio do CRM* permanece; o detalhamento v0.1 é apenas **proposta** para avaliação.

## Fontes de planejamento

- [CRM-REV-05 — Chatbot, Fluxos, Gatilhos, Condições e Automações](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md)
- [CRM-DEC — Motor próprio de automações / n8n interno](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md)
- [CRM-REV-02 — Planos e direitos](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md)
- [CRM-REV-09 — Infraestrutura e operações](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md)
- [INT-R01 — Governança e contratos técnicos](integracoes/INT-R01-governanca-contratos-homologacao.md)
- [IN-168 — Pendências de arquitetura](IN-168-matriz-pendencias-fechamento-arquitetura.md)
