# CRM-AUT-LIB04 v0.1 — Interação válida, prazos e antiduplicidade de LEA-01/LEA-02

**Data:** 04/11/2026.
**Status atualizado:** **SEMÂNTICA E REGRAS FUNCIONAIS DE LEA-01/LEA-02 APROVADAS** em 04/11/2026, conforme [decisão específica](CRM-DEC-aprovacao-regras-funcionais-LEA01-LEA02-2026-11-04.md), incluindo fontes manual/site, padrão 24 horas corridas, opções 4/12/24/48/72 horas, aviso interno por ciclo e tentativa sem resposta sem conclusão do atendimento. **Contratos técnicos, prova auditável de atendimento, retenção, direitos executáveis e publicação individual dos modelos permanecem pendentes.** As passagens abaixo que indicam “proposta sem aprovação” representam o estado histórico anterior ao aditamento da seção 8.
**Decisões já aprovadas:** [TAR-A — avisos internos sem tarefas comerciais automáticas](CRM-DEC-escolha-TAR-A-avisos-internos-LEA01-LEA02-2026-11-04.md); [priorização de LEA-01/LEA-02](CRM-DEC-prioridade-LEA01-LEA02-2026-11-04.md); [motor próprio CRM](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md). Fontes complementares: [AUT-LIB02](CRM-AUT-LIB02-revisao-individual-modelos-basico-v0.1.md), [CRM-REV03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [CRM-REV05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md), [AUT-P0](CRM-AUT-P0-execucao-segura-motor-proprio-automacoes-v0.1.md).

## 1. Definição comercial aprovada: LEA-02 significa primeiro atendimento pendente

**Decisão aprovada em 04/11/2026:** LEA-02 acompanha **ausência de primeiro atendimento efetivo registrado pela empresa**, conforme [CRM-DEC](CRM-DEC-semantica-LEA02-primeiro-atendimento-2026-11-04.md), e **não** o silêncio do cliente após contato. A **ausência de resposta do cliente** depois de tentativa ou mensagem deverá ser tratada separadamente, sem inclusão automática de novo modelo no Básico. A caracterização de atendimento efetivo e o tratamento das tentativas sem sucesso **continuam pendentes**.

O modelo não deve declarar que houve atendimento por leitura da ficha, atualização de etiqueta ou status técnico. Uma mensagem recebida do cliente, sem ação efetiva da equipe, **não deve encerrar o prazo de primeiro atendimento**. Registrar tentativas fracassadas e contatos efetivos como fatos distintos; decidir comercialmente se uma tentativa comprovada (ex.: ligação não atendida) basta para encerrar a janela de atendimento ou apenas para marcar "tentativa" e iniciar outro ciclo.

## 2. Quadro de interações e estados — candidato a contrato de DAT

| Fato ou ação | Encerra a janela LEA-02? | Observações e pendências |
|---|---|---|
| Abrir ficha, visualizar ou ler aviso | **Não** | Não comprova contato |
| Alterar responsável, etiqueta, campo, prioridade, score ou mover funil | **Não, por si só** | Administração interna não equivale a atendimento |
| Criar observação genérica sem classificação de contato | **Não automaticamente** | Texto livre não é prova inequívoca |
| Registrar atendimento realizado e seu resultado por usuário autorizado | **Sim, candidato prioritário** | Confirmar se a ação estruturada existe no Básico; se necessário, estender metadados de observação aprovada sem criar módulo de tarefas |
| Registrar tentativa legítima sem resposta (ex.: ligação não atendida) | **Não — aprovado funcionalmente** | Registrar a tentativa com resultado próprio; não encerrar o primeiro atendimento pendente; [decisão específica](CRM-DEC-LEA02-tentativa-nao-atendida-nao-e-atendimento-2026-11-04.md) |
| Cliente envia mensagem por canal integrado | **Não por si só** | Contato inbound sem atendimento humano não encerra SLA do responsável |
| Envio de mensagem externa com ack de provedor | **Não por si só** | Status técnico não prova conversa; canal não incluído automaticamente no Básico |
| Nota ou interação criada por IA/robô sem validação humana | **Não** | Sugestão não equivale a atendimento registrado |
| Arquivamento, conversão, encerramento ou exclusão válida do lead | **Suspender/cancelar elegibilidade conforme estado oficial** | Não interpretar como contato; preservar direitos, histórico, auditoria e privacidade |

**Não inventar uma entidade de tarefas comerciais** para registrar contato: o Básico já permite observações/histórico manual (IN-023). Propor categoria tipada de interação sobre esse mecanismo **apenas após confirmar com DAT/CORE e a matriz de permissões**.

## 3. LEA-01 — gatilho de novo lead e aviso

- **Fonte oficial:** fato de lead criado/registrado com sucesso no CRM/DAT. Persistência inicial da API de Sites, HTTP 202, fila de contingência e aceite por proxy **não bastam**.
- **Origem proposta:** incluir lead criado por formulário de Site e cadastrado manualmente para não diferenciar Básico pago de cortesia. **Importação de arquivo em massa** fica excluída do aviso LEA-01 padrão até haver política de agregação/opt-in e quotas para evitar milhares de avisos. A elegibilidade de cada origem depende de aprovação.
- **Destino previsto:** único administrador da organização Básico, autenticado com MFA e permissão atual; aviso interno leva ao registro por link protegido, sem URL pública nem envio externo.
- **Dedupe lógico:** empresa/tenant + instância de LEA-01 + ID de evento **canônico** + finalidade da notificação. **Versão de template e número de tentativa não devem produzir novo aviso para o mesmo fato**. Evitar que dois gatilhos representem o mesmo evento oficial por webhook/retentativa. Eventos legítimos distintos não são fundidos por telefone ou e-mail.
- **Comportamento:** modelo comercial opcional, conta uma vaga **quando estiver ativo**, depois de aprovado; queda/suspensão do modelo não afeta entrega oficial de lead. Falha de worker exige retry seguro e histórico de resultado.
- **Parâmetros de usuário candidatos:** ativar/pausar e escolher apenas filtros de fonte autorizados (se homologados), sem edição livre do motor e sem tarefa automática A01.

## 4. LEA-02 — relógio, ciclos e reavaliação

- **Marco inicial candidato:** momento da confirmação oficial do lead no CRM, não o instante do primeiro armazenamento do formulário na API externa. Esse atraso entre APIs deve aparecer em relatórios técnicos, mas não consumir falsamente a janela comercial antes do registro no CRM.
- **Ciclo de primeiro atendimento:** identifica lead ainda elegível sem contato válido registrado. Outras classes de inatividade comercial (após contato, após proposta, cliente sem resposta) têm **ciclos/relógios próprios** para não misturar definições de ATD, AGD e VEN.
- **Momento de execução:** Motor Central de Datas/Prazos agenda elegibilidade (UTC, com fuso IANA para configuração/exibição); no vencimento, o proprietário DAT reconfirma estado e versão, empresa, direito, ausência de interação válida, modelo ativo e autorização do destinatário. Fila atrasada não executa com snapshot vencido.
- **Sem repetição:** um único aviso interno por instância de modelo + lead + **ciclo de inatividade**. Retentativas e publicação de nova versão não abrem ciclo novo. Apenas novo fato válido definido pela política pode iniciar novo ciclo. **Não mandar avisos diariamente por lead eternamente inativo**.
- **Finalização/rearme:** interação válida reconhecida ou encerramento/arquivamento elegível cancela aviso pendente; se contato futuro legitimamente reabrir ciclo, o Motor de Datas registra nova identidade causal. Nenhum aviso anterior pode ser "ressuscitado" por retry.
- **Fronteira:** notificações são apenas internas, sem WhatsApp, e-mail, IA, tarefas comerciais, distribuição automática ou autoagendamento. Avisos da Agenda (SO) continuam sem consumir slot.

### 4.1. Prazos: proposta histórica, ratificada funcionalmente no aditamento da seção 8

**Recomendação para decisão:** usar **24 horas corridas como padrão candidato** do modelo, com opções de configuração guiada **4 h, 12 h, 24 h, 48 h ou 72 h**, se a medição de custo e as regras comerciais sustentarem. Considerar **horas úteis** somente quando houver calendário de expediente da empresa e semântica de feriados/fuso homologados; não presumir esse direito no Básico. Horas de atraso devido à fila, DST e mudanças de prazo requerem contrato específico.

A validação final deve selecionar entre **prazos livres dentro de mínimo/máximo homologados** versus **lista de opções discretas**. Para Básico com modelos prontos, lista fechada facilita suporte, capacidade e previsibilidade. Não confundir esse relógio com fechamento de conversas ATD (24/12/72h) ou com os lembretes da Agenda (24h/1h), que possuem objetivos diferentes.

**P0/pendente:** homologação técnica da capacidade por tenant, política de uso justo sem limitar leads, retenção de avisos comerciais, implementação de prazos corridos aprovados (24h padrão; 4/12/24/48/72h opções), gestão de tentativas, prova de atendimento, fuso e eventual política adicional de horas úteis. **Decisão já aprovada:** tentativa sem resposta não encerra primeiro atendimento.

## 5. Antiduplicidade, isolamento e estados

1. **LEA-01:** chave idempotente de efeito persistida por tenant + instância + evento oficial + finalidade. Não incluir attempt_id nem bump de versão do template como nova causalidade.
2. **LEA-02:** chave idempotente por tenant + instância + lead + ciclo de inatividade + finalidade, separada de alarmes técnicos. Um usuário clicando/abrindo o aviso **não reinicia nem reabre ciclo**.
3. **Consistência:** entrega ao menos uma vez com outbox/inbox ou equivalente conforme AUT-P0; o módulo dono da notificação faz confirmação durável; nunca prometer *exactly once* distribuído.
4. **Reprocessamento:** timeout após emissão demanda consulta de estado antes de repetir escrita. Efeito inconclusivo continua pendente de reconciliação, não vira sucesso por HTTP.
5. **Autorizações e LGPD:** revalidar ativo/suspenso, tenant, administrador autorizado, acesso ao lead e finalidade antes de mostrar o aviso; dados mínimos no corpo, sem enviar PII fora do CRM.
6. **Retenção:** avisos comerciais têm política própria a deliberar. Não copiar automaticamente **90 dias** da central de Agenda; não confundir com 18 dias de retenção de payload temporário ainda não entregue em Sites.
7. **Capacidade:** planejar consultas por vencimento e processamento em lotes com isolamento e quota técnica, evitando uma fila infinita por lead e não bloqueando captação ou atividades oficiais do CRM.

## 6. Casos documentais de homologação futura

| ID | Teste futuro | Resultado exigido |
|---|---|---|
| L04-T01 | Site registra payload, ainda não confirmou lead CRM | LEA-01 não emite aviso |
| L04-T02 | Reenvio de mesmo envio lógico e retentativas de evento | Um aviso por instância e evento oficial |
| L04-T03 | Novo envio legítimo com mesmo telefone | Não mescla registros automaticamente |
| L04-T04 | Importar CSV com grande volume | Não disparar avalanches de avisos sem política homologada |
| L04-T05 | Abrir a ficha ou alterar etiqueta | Não encerrar inatividade sem contato válido |
| L04-T06 | Cliente manda mensagem mas nenhum colaborador atende | Não marcar primeiro atendimento efetuado |
| L04-T07 | Contato válido é registrado antes do vencimento | Cancela job; nenhum aviso atrasado |
| L04-T08 | Inatividade continua após primeiro aviso | Não repetir o mesmo ciclo por cron/retry |
| L04-T09 | Publicação de nova versão durante fila | Não gera segundo aviso do mesmo evento/ciclo |
| L04-T10 | Mudança de plano/pausa/arquivamento durante fila | Revalidar e não emitir aviso não elegível |
| L04-T11 | Tenants distintos compartilham ID numérico de lead | Isolamento absoluto de relógios, avisos e históricos |
| L04-T12 | Worker falha entre persistência e confirmação de entrega | Recuperar sem duplicar efeito durável |
| L04-T13 | Agenda Básico emite lembrete interno SO | Não contar como slot LEA-01/02 |
| L04-T14 | Duas datas de mesmo prazo cruzam DST de organização | Vencimento único calculado em UTC sob política homologada |

**Nenhum teste foi executado.** Reutilizar casos de AUT/CAT/INT existentes sem duplicar suítes.

## 7. Pendências para decisão (LIB04-P01..P09)

- **P01 — RESOLVIDA QUANTO AO SIGNIFICADO:** LEA-02 significa **primeiro atendimento efetivo pendente pela empresa**, não silêncio do cliente, conforme [CRM-DEC](CRM-DEC-semantica-LEA02-primeiro-atendimento-2026-11-04.md). **Subdecisões sobre tentativas sem sucesso e prova de atendimento permanecem abertas em P02**.
- **P02 — PARCIALMENTE RESOLVIDA:** ligação não atendida **não** é atendimento e não encerra a condição de primeiro atendimento pendente, conforme [decisão específica](CRM-DEC-LEA02-tentativa-nao-atendida-nao-e-atendimento-2026-11-04.md) e [aprovação funcional mais ampla](CRM-DEC-aprovacao-regras-funcionais-LEA01-LEA02-2026-11-04.md). **Ainda pendente** o contrato de prova e classificação dos demais tipos de interação, identidade, auditoria e política de tentativas/escalonamento.
- **P03:** LEA-01 pode acionar cadastro manual e de Sites? Como tratar importação em lote?
- **P04:** aprovar ou rever opções **4/12/24/48/72 h**, default **24h**, horas corridas versus horas úteis e política de mudança do prazo.
- **P05:** política de um aviso por ciclo, dedupe entre versões, pausa e retomada de modelo/tenant.
- **P06:** metadados e persistência de notificação comercial A03, destinatário, estados, consulta, leitura, privacidade e retenção.
- **P07:** direito comercial de habilitar cada modelo, manutenção do teto histórico de três ativas até revisão global, custo e antiabuso.
- **P08:** contrato oficial de evento e comando com CORE/DAT/Notificações/Datas, isolamento, quotas técnicas e reconciliação.
- **P09:** critérios de homologação e aprovação individual de LEA-01/LEA-02, preservando AUT-P01..14, LIB02-P01..08, LIB-P01..12, AUT-MAT-P01..10, SEG/INT e gates P0.

**Resumo histórico desta versão antes do aditamento:** TAR-A e semântica de LEA-02 já estavam aprovadas. **A seção 8 prevalece para prazos, fontes, tentativas e regra funcional de avisos**, mantendo contratos, detalhes de versão, implementação técnica da deduplicação e aprovação individual dos modelos pendentes. Não liberar recursos em planos, não alterar cobrança nem iniciar programação, contratação, deploy ou testes reais.


## 8. Aditamento — aprovação funcional de LEA-01 e LEA-02 em 04/11/2026

O responsável respondeu **“ok”** à aprovação expressa da tabela de regras funcionais; ver [CRM-DEC de aprovação](CRM-DEC-aprovacao-regras-funcionais-LEA01-LEA02-2026-11-04.md). **Esta seção prevalece sobre a caracterização como “propostas ainda não aprovadas” dos itens funcionais nas seções anteriores.**

**Aprovado funcionalmente:** (a) LEA-01 para leads oficialmente confirmados no CRM, originados de cadastro manual e formulário/site integrado; (b) sem avalanche de avisos unitários em importação de planilha; (c) LEA-02 para primeiro atendimento efetivo pendente, com 24h corridas como padrão e configuração guiada 4/12/24/48/72h; (d) atendimento efetivo registrado por usuário autorizado como encerramento, enquanto ligação não atendida é apenas tentativa; (e) um aviso por ciclo lógico; (f) notificação interna, sem e-mail, WhatsApp ou tarefa comercial automática; (g) uso do motor próprio CRM e preservação de limites/slots vigentes sujeitos à revisão final.

**Ainda não aprovado:** os modelos individuais para ativação/publicação; schemas T01/T13/A03 e contratos técnicos; forma exata do registro auditável de atendimento; política de agregação de importação; implementação de relógios, migrações e rearmamento; retenção e consulta de notificações; limites finais de volume, direitos por plano e critérios de homologação P0. Os tickets LIB04-P02..P09 permanecem **parcialmente abertos somente para essas especificações e demais detalhes não definidos**; LIB04-P01 já se encontrava resolvida quanto ao significado. Não converter a aprovação de parâmetros em autorização de programação ou gasto.


## 9. Registro específico sobre ligações não atendidas (04/11/2026)

A [CRM-DEC — tentativa sem sucesso](CRM-DEC-LEA02-tentativa-nao-atendida-nao-e-atendimento-2026-11-04.md) reafirma a regra incluída na [aprovação funcional geral](CRM-DEC-aprovacao-regras-funcionais-LEA01-LEA02-2026-11-04.md): ligação não atendida é tentativa, **não** atendimento efetivo, e **não encerra** LEA-02 por si só. O default de **24h corridas** e opções **4/12/24/48/72h** **já constam aprovados funcionalmente** na decisão geral; permanecem pendentes provas estruturadas de contato, controle técnico, retenção, homologação individual dos modelos e implementação. Nenhum código ou ativação.


## 10. Proposta de registro estruturado LIB05 (04/11/2026)

A [CRM-AUT-LIB05 v0.1](CRM-AUT-LIB05-registro-classificacao-contato-evidencias-LEA02-v0.1.md) apresenta **proposta para revisão** de classificação de tentativa sem sucesso, atendimento efetivo validado, resposta do cliente e nota administrativa **sobre o histórico de leads já permitido no Básico**, sem criar tarefas comerciais. Reafirma decisões aprovadas: tentativa não atendida não encerra LEA-02; prazos funcionais **24 horas corridas padrão** e **4/12/24/48/72h**; efeito somente aviso interno. **Taxonomia, campos, prova do atendimento, correção, esquema/eventos, direitos executáveis e homologação individual ainda NÃO aprovados**. LIB05-P01..P09 e demais gates P0 permanecem abertos; sem programação.
