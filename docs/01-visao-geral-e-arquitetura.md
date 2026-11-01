# 01 — Visão geral e arquitetura

## Aprovado
- **CRM Imagine**: SaaS multiempresa (multi-tenant) para Imagine Comunicação Digital e outras empresas contratantes.
- Um mesmo login pode pertencer a várias empresas e alternar o contexto.
- Dados, assinaturas, permissões e cotas de IA são isolados entre empresas.
- **Backend:** Laravel, API REST.
- **Banco:** PostgreSQL.
- **Automação:** n8n em VPS separado.
- **IA:** integração com OpenAI.
- Infraestrutura dedicada, separada do WHM/cPanel de outras operações.

## Pendente ou não ratificado
- Estratégia de isolamento lógico dos tenants: banco compartilhado com `tenant_id` e PostgreSQL Row-Level Security (RLS) foi recomendada, mas **não está aprovada definitivamente**.
- Versionamento da API, estratégia de deploy detalhada, modelo lógico de dados, fluxo de autenticação e topologia de ambientes.
- Contratos de integração e observabilidade por serviço.

## Isolamento multiempresa no PostgreSQL — APROVADO
- Adotar arquitetura multi-tenant com identificador `tenant_id` em todos os registros pertencentes a uma empresa, respeitando exceções de entidades globais explicitamente modeladas.
- Aplicar PostgreSQL Row-Level Security (RLS) como camada de defesa no banco, com políticas que restrinjam leitura e escrita ao tenant ativo/autorizado, em conjunto com validação de empresa e permissões no Laravel.
- O contexto de tenant deve ser definido de forma segura por requisição/transação, nunca confiando em `tenant_id` arbitrário enviado pelo cliente; considerar conexões reutilizadas, filas, jobs, integrações e processos assíncronos para impedir vazamento de contexto.
- Contas vinculadas a múltiplas empresas podem alternar somente entre empresas com associação ativa/autorizada; nenhuma alternância implica acesso cruzado aos dados.
- Relatórios, consultas, APIs, exportações, arquivos e integrações devem respeitar o mesmo escopo de isolamento.
- Acessos administrativos excepcionais requerem autorização específica, justificativa, escopo limitado e auditoria; usar papéis técnicos com privilégio mínimo, sem bypass indiscriminado de RLS.
- Criar testes automatizados de isolamento entre empresas para leitura, gravação, atualizações, relatórios, exportações, jobs e endpoints, incluindo tentativas de referência cruzada e falhas de escopo.

## Próxima fase do projeto — Definição do MVP comercial (APROVADA)
- O responsável pelo CRM Imagine aprovou priorizar a definição de um MVP orientado à conquista dos primeiros clientes pagantes, evitando tentar lançar simultaneamente todas as funcionalidades especificadas.
- Objetivo: validar proposta de valor, ativação de usuários, disposição a pagar, conversão de trial para assinatura e retenção antes de ampliar escopo.
- O escopo exato, a ordem das entregas, o cronograma e as versões posteriores **ainda precisam de aprovação específica**; funcionalidades já aprovadas permanecem no roadmap e não devem ser tratadas como excluídas.
- Durante a priorização, avaliar valor ao cliente, dependências técnicas, esforço, custo operacional, diferenciação, risco e impacto na aquisição/retenção.
- Estratégia de negócio: acompanhar indicadores de aquisição, conversão, receita recorrente, churn e margem para orientar evolução do produto.

## Plataforma inicial do MVP — Web responsiva (APROVADO)
- Lançar a primeira versão comercial do CRM Imagine como aplicação **Web responsiva**, acessível via navegadores em computadores, tablets e smartphones.
- Não desenvolver nem publicar aplicativos nativos para Android e iOS na primeira versão; considerar apps móveis em fase posterior conforme demanda, adoção e retorno esperado.
- Priorizar experiência móvel utilizável no navegador, segurança, estabilidade, desempenho, recursos comerciais essenciais e validação com clientes pagantes.
- Esta decisão define o canal de acesso do MVP, não aprova nem exclui por si só outras funcionalidades do roadmap.

## Diretriz de diferenciação do MVP — IA e automações
- O responsável pelo projeto explicitou que **agentes de IA e automações comerciais são importantes desde o lançamento**, para diferenciar o CRM Imagine de concorrentes; não devem ser simplesmente adiados para versões posteriores.
- Ajustar a proposta de escopo do MVP para contemplar automações comerciais e agentes de IA em uma primeira versão enxuta, a ser detalhada e aprovada; manter arquitetura extensível para evolução.
- Proposta em discussão (ainda não aprovada em detalhes): automações essenciais (distribuição de leads, tarefas e alertas), IA Prospector (priorização e análise de leads) e IA Comercial (sugestões de abordagem, resumos e próximos passos), com revisão humana antes de enviar mensagens.
- Manter a monetização dos agentes como serviços opcionais e limites/custos controlados conforme decisões anteriores; definir escopo exato, integrações e disponibilidade no lançamento antes de fechar roadmap.

## MVP — WhatsApp integrado com automações e agentes de IA (diretriz solicitada)
- O responsável **aprovou incluir os dois agentes de IA (Prospector e Comercial) e automações comerciais essenciais no MVP**.
- Explicitou como requisito estratégico adicional do lançamento a integração do **WhatsApp com envio automático de mensagens disparado por automações e por agentes de IA**, além de atendimento integrado. Não tratar WhatsApp como recurso meramente posterior.
- Proposta de implementação para validação: WhatsApp Business Platform oficial; caixa de entrada e histórico vinculados a contatos/leads; recebimento e envio; gatilhos de automação; respostas/qualificação com IA; transferência para atendente; templates e variáveis; auditoria, consentimento e opt-out.
- Aplicar regras da Meta, incluindo janela de atendimento e templates aprovados quando exigidos; limites, prevenção de spam, controle de custos, permissões e possibilidade de pausar agentes e disparos.
- O escopo detalhado do MVP WhatsApp, graus de autonomia dos agentes e modelo de cobrança da integração ainda exigem decisões específicas; editor avançado de chatbot pode ser fase posterior sem impedir automações básicas de mensagens.

## MVP — Central de Atendimento WhatsApp oficial e IA autônoma (APROVADO)
- Integrar exclusivamente pela **WhatsApp Business Platform/API oficial da Meta**, sem dependência de APIs não oficiais.
- Construir **Central de Atendimento WhatsApp dentro do CRM Imagine**, com caixa de entrada compartilhada, visibilidade por permissões, histórico cronológico unificado por cliente e vínculo com contato, lead e oportunidade.
- Identificar claramente a autoria/origem de cada interação: cliente, atendente humano, automação ou agente de IA; distinguir mensagens efetivamente enviadas de eventos internos (tarefas, mudanças de estágio, transferências etc.).
- Permitir que agentes de IA conversem **autonomamente** com clientes, recebam e enviem mensagens, sem aprovação humana mensagem a mensagem, dentro de políticas e limites configurados por empresa.
- Permitir envio automático de mensagens por gatilhos/regras de automações e agentes de IA, com templates aprovados quando exigidos, consentimento, opt-out, controle de custos, horários e limites, auditoria e interrupção imediata.
- Permitir que humanos visualizem todas as interações autorizadas, respondam, assumam conversas e transfiram atendimento entre humano e IA sem perder contexto.
- **Recomendação pendente de aprovação específica:** ao humano assumir uma conversa, pausar respostas automáticas do agente naquela conversa até devolução explícita para IA, evitando conflitos.
- Detalhes ainda pendentes: regras de distribuição e filas, modelo de números por empresa, níveis de acesso, custos e configuração de templates.

## Regra de posse da conversa WhatsApp — APROVADA
- Ao atendente humano **assumir** uma conversa, pausar imediatamente as respostas automáticas do agente de IA **somente naquela conversa**; demais conversas continuam normalmente.
- Enquanto a conversa estiver sob responsabilidade humana, o agente não pode enviar novas mensagens automáticas nela.
- A IA só poderá voltar a responder quando um humano autorizado **devolver explicitamente** a conversa à IA, preservando o histórico e o contexto atualizado.
- Registrar em auditoria quem assumiu/devolveu, data, hora e mudanças de responsabilidade.
- Implementação deve validar a posse da conversa imediatamente antes do envio, inclusive para mensagens já enfileiradas, impedindo disparos indevidos após a transferência; preservar logs e permitir tratamento seguro de mensagens em processamento.
- Definir posteriormente se e como automações não conversacionais ou lembretes programados são pausados durante atendimento humano; não confundir com autorização para a IA enviar mensagens.

## Pausa de automações de mensagens durante atendimento humano — APROVADA
- Quando um atendente humano assumir a conversa WhatsApp, suspender **todos os envios automáticos voltados ao cliente naquela conversa**, tanto disparos de agentes de IA quanto de automações (follow-ups, lembretes e mensagens programadas).
- Automações **internas** que não enviem mensagens ao cliente podem continuar, respeitando permissões, regras e auditoria (tarefas, alertas internos, atualizações de CRM).
- A retomada de atendimento automatizado exige devolução explícita à IA; na retomada, **não descarregar automaticamente uma fila de mensagens acumuladas**. Reavaliar gatilho, atualidade, consentimento, janela de atendimento da Meta, templates, duplicidade, horários, custos e posse da conversa antes de cada envio.
- Validar posse e permissões no momento efetivo do disparo para impedir corrida entre troca de responsável e processamento de filas.
- Manter histórico de mensagens suspensas, descartadas, reavaliadas e enviadas para rastreabilidade.

## WhatsApp — múltiplos números por empresa (APROVADO)
- Permitir **vários números oficiais de WhatsApp** vinculados à mesma empresa/tenant, sem exigir contas separadas do CRM Imagine.
- Modelo comercial da integração: **um número incluído na contratação do adicional WhatsApp**; números excedentes contratados como adicionais pagos, com preços definidos posteriormente. Esta inclusão não significa que a plataforma assuma os custos variáveis cobrados pela Meta/BSP; detalhar repasse e cobrança antes do lançamento.
- Prever **equipes, filas de atendimento, automações e agentes de IA configuráveis por número**, respeitando permissões de acesso e isolamento entre empresas.
- Fornecer **Central de Atendimento unificada** com filtro por número e visibilidade conforme autorização do usuário.
- Cada conversa e cada envio/recebimento devem identificar o **número oficial de origem/destino** correto; mensagens de saída usam a identidade do canal associado à conversa, sujeitas a regras da Meta.
- Prever no modelo de dados identificadores estáveis de cada linha/canal conectado, suas credenciais, configurações, estados, eventos e trilhas de auditoria; segredos armazenados com proteção adequada.
- **Aprovado:** cadastro único por cliente com histórico integrado entre números oficiais, mantendo conversas individualizadas por número e acesso conforme permissões. Transferência de atendimento entre números e critérios de vinculação de contatos ainda pendentes.

## Histórico unificado por cliente em múltiplos números WhatsApp — APROVADO
- Uma empresa mantém **cadastro único de contato/cliente** que pode estar vinculado a interações realizadas por diferentes números oficiais de WhatsApp da própria empresa.
- A ficha do cliente reúne, com controle de autorização, **linha do tempo de interações**, contatos, oportunidades, atividades, automações e intervenções dos agentes de IA ou de funcionários, identificando canal e autoria.
- Na **Central de Atendimento**, manter **conversas distintas para cada número oficial/linha**, sem misturar seus remetentes, estados, responsáveis, filas, permissões ou janelas de atendimento; a visão consolidada da ficha não funde conversas operacionais.
- Um funcionário autorizado pode consultar histórico em outros números da mesma empresa, enquanto funcionário sem permissão não deve visualizar conteúdo restrito; o isolamento entre empresas (tenant) é absoluto.
- Registrar os identificadores estáveis do contato, do número empresarial de WhatsApp, da conversa e de cada mensagem, preservando rastreabilidade e auditoria.
- **Aprovado:** reconhecimento automático quando o mesmo número de telefone/WhatsApp já estiver associado inequivocamente a um contato da mesma empresa; para números distintos, oferecer apenas sugestões, exigindo vinculação humana autorizada. Transferência entre linhas aprovada em nível funcional; regras de início de contato no destino, notificações e autorização de IA ainda requerem detalhamento.

## Identificação de clientes por WhatsApp — APROVADA
- No recebimento de mensagens, normalizar e comparar o identificador/telefone do WhatsApp com os números vinculados aos contatos **dentro do mesmo tenant**.
- Havendo correspondência **inequívoca** com um único contato, associar automaticamente a conversa ao cadastro existente e preservar o histórico consolidado entre diferentes números oficiais de atendimento da empresa.
- Se um número de origem estiver associado a vários contatos ou houver outro conflito de identificação, **não realizar associação arriscada**; sinalizar para conferência por funcionário autorizado.
- Para um contato que utiliza **número de telefone diferente**, o CRM poderá sugerir correspondências com base em dados disponíveis, mas **não poderá vincular ou mesclar automaticamente** cadastros, conversas ou históricos; exigir decisão explícita de funcionário com permissão.
- Registrar em auditoria vínculo, desvinculação, responsável, data e motivação, mantendo rastreabilidade e permitindo correção segura de associação equivocada sem apagar registros.
- Garantir isolamento entre empresas e respeito às permissões de visualização; não expor informações restritas em sugestões de correspondência.
- Detalhes para especificação técnica: normalização internacional de telefones, identificação do remetente retornada pela API oficial, gestão de telefones compartilhados/reutilizados e revisão de associação de contatos.

## Transferência de atendimento entre números oficiais WhatsApp — APROVADA
- Permitir que **funcionário autorizado** transfira um atendimento da linha oficial de origem para outra linha oficial da **mesma empresa**, por exemplo Vendas → Suporte.
- Registrar o motivo da transferência, atendente solicitante, origem, destino, horários e status no histórico auditável.
- Manter **conversas operacionais distintas por linha**, sem fingir que o WhatsApp possui um recurso de transporte da mesma conversa entre números. Criar/vincular atendimento no número de destino e referenciar o atendimento de origem no CRM; o contato/cliente permanece único.
- Atendentes e agentes de IA do destino podem consultar contexto da origem **somente conforme permissões**; preservar isolamento de dados e equipes.
- Sinalizar na origem o status de transferência e evitar atendimento concorrente ou envio automático descontextualizado durante a transição, aplicando regras de posse humano/IA já aprovadas.
- **Não presumir que o novo número possa iniciar mensagem livremente**: qualquer contato ativo de destino deve seguir as políticas da WhatsApp Business Platform, consentimento e, quando aplicável, templates aprovados e janela de atendimento específica da linha.
- **Aprovado:** agentes de IA podem iniciar autonomamente transferências entre números autorizados por configuração de administrador. **Pendente:** quando notificar o cliente e qual confirmação/fluxo de entrada usar para o número de destino; detalhar transferências rejeitadas ou sem atendente disponível.

## Transferência autônoma entre números WhatsApp por agentes de IA — APROVADA
- Agentes de IA podem **transferir automaticamente atendimentos entre diferentes números oficiais de WhatsApp da mesma empresa**, sem exigir autorização humana por transferência, desde que o administrador tenha configurado os destinos e critérios permitidos por agente.
- Condições de roteamento podem considerar assunto/intenção da mensagem, setor (Vendas, Financeiro, Suporte), equipe responsável e regras da empresa; transferências fora dos destinos ou condições configuradas são bloqueadas.
- Antes de iniciar, validar tenant, permissões do agente, número de destino ativo, estado/posse da conversa e condições de atendimento; impedir loops de transferência e registrar razão, agente solicitante, origem e destino em auditoria.
- A operação gera **atendimentos vinculados porém separados por número**, preservando cadastro único do cliente, histórico, autoria, visibilidade por equipe e vínculo com o atendimento anterior; não equivale a mover tecnicamente a thread no WhatsApp.
- Enviar mensagens pelo número de destino **somente se permitido pelas regras oficiais Meta**, inclusive consentimento, janela de atendimento específica do número de destino e templates aprovados quando exigidos. Nunca interpretar transferência interna como autorização automática de envio.
- Se o destino estiver indisponível, o envio não for autorizado ou outra regra impedir a transferência, manter solicitação e histórico íntegros, registrar a falha e encaminhar à fila/atendente responsável conforme regras configuradas, evitando perda do atendimento.
- **Pendente de detalhamento:** número de tentativas/timeout e tratamento operacional de indisponibilidade do destino; a regra de conclusão e manutenção do responsável de origem foi aprovada posteriormente. Notificação automática ao cliente aprovada em decisão posterior.

## Avisos automáticos ao cliente em transferências entre números WhatsApp — APROVADO
- Sempre que um atendimento for transferido entre números oficiais de WhatsApp da mesma empresa, seja por **funcionário autorizado ou agente de IA**, o CRM deve **notificar o cliente automaticamente**, respeitando as permissões e as regras da WhatsApp Business Platform.
- Permitir **modelo personalizado por empresa/setor**, com variáveis autorizadas (nome, setor de destino etc.), sem revelar dados internos ou sensíveis indevidamente.
- Registrar no histórico do contato a solicitação de transferência, origem/destino, agente ou humano responsável, conteúdo/status do aviso, horários e eventuais falhas.
- Priorizar aviso pelo **número de origem** quando permitido; se o número de destino não puder iniciar uma conversa válida, fornecer ao cliente **link para iniciar o contato com o setor de destino**, também por canal permitido.
- O fluxo de transferir atendimento na aplicação **não significa transferir a thread nativa do WhatsApp** e **não autoriza automaticamente mensagem ativa no número de destino**. Validar consentimento, janela de atendimento e template aprovado quando exigido.
- A interface deve distinguir aviso enfileirado/solicitado, enviado, entregue, lido e falha conforme os retornos efetivamente disponibilizados pela integração. Nunca confirmar como entregue antes da confirmação correspondente.
- **Detalhes técnicos pendentes:** política de tentativas, preferências de templates por setor, mensagens alternativas quando o aviso falhar e definição detalhada do mecanismo de aceite do destino e notificações de pendência.

## Transferências pendentes: origem mantém a responsabilidade — APROVADO
- Quando um humano ou agente de IA solicitar transferência entre números oficiais de WhatsApp da mesma empresa, criar um registro de transferência com status **aguardando transferência** até confirmação efetiva.
- **Não encerrar nem abandonar o atendimento de origem** somente porque foi solicitado encaminhamento, enviado aviso ou fornecido link ao número de destino. A origem continua responsável pelo cliente durante a pendência.
- Considerar transferência **concluída apenas quando o atendimento no destino for efetivamente iniciado ou expressamente aceito pelo destino**, com registro identificável da confirmação e vínculo entre atendimentos.
- Se o cliente não clicar no link, o setor de destino não aceitar ou houver indisponibilidade/falha técnica, manter transferência pendente (ou marcar falha conforme tratamento futuro) e **sinalizar ao responsável de origem**, sem perder o histórico ou o cliente.
- Registrar solicitação, status, origem, destino, timestamps, responsáveis e eventos de aceite/conclusão/falha com trilha de auditoria, evitando confirmações artificiais ou duplicadas.
- Evitar disparos automáticos contraditórios durante a transição, observando as regras já aprovadas de posse humano/IA, pausa de automações externas e políticas da Meta.
- **Aprovado posteriormente:** prazo configurável e alerta automático por transferência pendente. **A detalhar:** tentativas, eventual encaminhamento ao gestor, critérios de aceite e tratamento de transferência cancelada ou recusada.

## Alertas automáticos para transferências pendentes entre números WhatsApp — APROVADO
- Cada empresa pode configurar um **prazo máximo de espera por aceite/início efetivo do atendimento de destino**, com **10 minutos como valor inicial sugerido**, ajustável conforme suas operações.
- Uma transferência ainda não concluída ao fim desse prazo deve **gerar alerta interno automático ao responsável da conversa de origem** e **sinalização clara de pendência na Central de Atendimento**.
- A origem **mantém a responsabilidade pelo cliente** após o alerta; expiração do prazo não representa aceite, transferência concluída ou autorização para encerrar atendimento.
- Usuários com permissão poderão **cancelar ou redirecionar** uma transferência pendente, mantendo íntegros o histórico original e a trilha de auditoria.
- Registrar configuração aplicável, início da transferência, prazo, disparo do alerta, destinatários, intervenções humanas, mudanças de status e conclusão efetiva.
- Evitar alertas indevidos quando o destino já tiver assumido o atendimento; executar checagem do estado antes do alerta, com proteção contra envios repetidos da mesma ocorrência.
- Horários úteis por equipe e contagem dos prazos de transferência foram aprovados posteriormente; alertas reincidentes, escalonamento para gestores e limites de prazo permanecem para decisão futura.

## Horários de funcionamento por equipe e SLA de transferência — APROVADO
- Cada empresa pode configurar dias da semana e **horários de funcionamento por equipe/setor**, incluindo opção de funcionamento **24 horas, 7 dias por semana**.
- O prazo configurável para aceitar/iniciar uma transferência entre números WhatsApp deve contar **somente durante os períodos de funcionamento da equipe de destino**; suspender a contagem fora do expediente e retomá-la no próximo período de atendimento, preservando tempo já transcorrido.
- Se uma transferência for solicitada fora do expediente de destino, iniciar contagem no próximo início do expediente. Exemplo: suporte 08h–18h, transferência às 22h, prazo de 10 minutos => alerta às 08h10 do próximo dia em que houver expediente, salvo aceite anterior.
- Para equipes configuradas como 24/7, contagem e alertas ocorrem continuamente.
- Executar o cálculo segundo **fuso horário configurado da operação/equipe**, guardando timestamps em UTC para evitar erros em transições de horário de verão; considerar dias de funcionamento e exceções configuradas.
- Mesmo fora do expediente, **manter atendimento de origem responsável** pela transferência pendente até o início efetivo ou aceite do destino; não concluir automaticamente nem perder a solicitação.
- Registrar alterações de horários e regras de SLA em auditoria; decidir posteriormente aplicação retroativa dessas mudanças a transferências já pendentes e configuração de feriados/ausências nesta central.

## Atendimento por IA 24/7 independente do expediente humano — APROVADO
- A empresa poderá **habilitar ou desabilitar por configuração** o atendimento automatizado dos agentes de IA **24 horas por dia, 7 dias por semana**, mesmo quando equipes humanas não estiverem em expediente.
- Quando habilitados e disponíveis, os agentes poderão receber e responder mensagens dos clientes, qualificar leads, consultar dados autorizados do CRM, registrar atividades e executar automações permitidas, respeitando limites de custo, segurança e regras da WhatsApp Business Platform.
- A IA poderá preparar transferências/encaminhamentos para equipes humanas fora do expediente, informando com clareza o horário de funcionamento do destino **sem prometer resposta humana imediata**.
- Aplicar as regras já aprovadas de transferência pendente: prazo de aceite contado no expediente da equipe de destino, e responsabilidade mantida na origem até efetivo início ou aceite do atendimento no destino.
- **A IA não deve reassumir automaticamente uma conversa já assumida por humano**, mesmo fora do expediente; exige devolução explícita e autorizada à IA. Suspensões de mensagens automáticas durante posse humana continuam válidas.
- Atendimento 24/7 significa capacidade de responder a contatos recebidos fora do expediente, **não permissão irrestrita para iniciar disparos**: cumprir consentimento, opt-out, janela de atendimento e templates aprovados quando requeridos, além dos limites da API Meta e das restrições próprias de cada empresa.
- A indisponibilidade da integração/API, bloqueio de custos, pausa administrativa ou falhas operacionais devem ser tratadas sem prometer atendimento contínuo infalível; rastrear estado e avisar responsáveis conforme políticas existentes.
- **A definir posteriormente:** tratamento detalhado de feriados e férias por equipe, escalonamento fora de expediente e configuração granular por agente/número.

## Central de Atendimento — Transparência dos agentes de IA (APROVADO)
- No início do atendimento automatizado no WhatsApp, identificar explicitamente o agente como assistente virtual, podendo personalizar nome e mensagem de abertura por empresa.
- Cliente pode solicitar atendimento humano a qualquer momento; o CRM deve encaminhar o pedido, preservar histórico e registrar o fluxo até aceite efetivo, respeitando horários do setor.
- Se fora do expediente, informar horário de atendimento humano e não prometer resposta imediata.
- Não confundir solicitação de transferência com aceite/conclusão da transferência; aplicam-se as regras anteriores de posse de conversa e responsabilidade.
- **Resolvido:** IA permanece pausada enquanto pedido humano aguarda aceite; cliente pode desistir e pedir retorno à IA antes de humano assumir.

## Solicitação de humano pelo cliente — pausa automática da IA (APROVADO)
- Quando cliente pedir atendimento humano, a IA confirma o pedido e **cessa respostas automáticas naquela conversa**, aguardando transferência/aceite humano; pendência permanece ativa conforme regras de expediente e alertas.
- Enquanto nenhum humano tiver assumido efetivamente, o cliente pode solicitar retorno à IA; cancelar/retirar solicitação pendente com auditoria e reativar o agente apenas se permitido pelas configurações e regras de canal.
- Depois da tomada de posse por atendente, volta a valer a regra de **devolução explícita por humano autorizado**; mensagem do cliente pedindo IA não ignora essa proteção.
- Preservar histórico e controlar mensagens enfileiradas para evitar envio após pausa e respostas simultâneas.

## Distribuição automática de conversas para atendimento humano — APROVADO
- Quando cliente solicitar atendimento humano ou uma conversa for encaminhada a equipe humana, o CRM poderá **distribuir automaticamente a conversa a funcionário elegível e disponível** da equipe responsável, conforme configuração da empresa/equipe.
- Oferecer como estratégias configuráveis de distribuição: **rodízio (Round Robin)** entre funcionários disponíveis e **menor carga de atendimentos ativos**; a empresa/equipe define a estratégia aplicável.
- Considerar equipe/número de WhatsApp, permissões de visualização e atendimento, isolamento por tenant, disponibilidade e estado de expediente antes de distribuir. Um usuário não autorizado ou indisponível não deve receber atribuição automática.
- Se não houver atendente elegível disponível, manter **fila de espera por atendimento humano**, sem encerrar ou abandonar a conversa e sem reativar a IA automaticamente; registrar claramente pendência e equipe de destino.
- Registrar funcionário designado, horários, estratégia, mudanças de responsável e eventuais redistribuições em trilha auditável.
- **Designação automática não equivale necessariamente a aceite/início de atendimento**: preservar distinção entre conversa designada, assumida e em espera, conforme regras anteriores de posse e transferência.
- A IA permanece pausada diante de pedido de humano; não enviar respostas automatizadas enquanto o cliente aguarda ou quando um humano assumiu a conversa, nos termos das decisões já aprovadas.
- **Ainda pendente:** forma de definir disponibilidade do funcionário (status manual, login, escala e expediente), limites de capacidade por atendente, timeout de aceite e reatribuição automática, prioridades nas filas.

## Status de disponibilidade na Central de Atendimento WhatsApp — APROVADO
- Funcionário pode selecionar manualmente os estados **Disponível**, **Ocupado**, **Ausente** e **Offline**. Apenas o funcionário efetivamente **Disponível** poderá receber novas conversas por distribuição automática, sujeito também a equipe, permissão, expediente e outros requisitos de elegibilidade.
- **Disponível:** apto a receber novas conversas na fila/equipe autorizada; **Ocupado**, **Ausente** e **Offline:** não recebe novas conversas automaticamente.
- O CRM deve **detectar desconexões automaticamente**, sem assumir que a sessão está conectada apenas porque uma página ficou aberta em algum dispositivo. Monitorar atividade/conectividade de maneira segura e considerar múltiplas sessões antes de determinar desconexão completa.
- A detecção de desconexão impede novas atribuições quando o funcionário estiver efetivamente offline, **sem transferir ou encerrar automaticamente conversas em andamento** por uma desconexão breve. Regras de indisponibilidade prolongada e redistribuição serão definidas à parte.
- O sistema deve evitar oscilação indevida entre online/offline por falhas transitórias, com período de tolerância a definir; mudanças de estado manuais ou automáticas relevantes ficam registradas e visíveis aos autorizados.
- Definições ainda pendentes: timeout/heartbeat, regras de estado manual após reconexão, disponibilidade em múltiplos dispositivos, limites de atendimentos simultâneos (aprovados; critérios de contagem ainda a definir), redistribuição de conversas de atendente offline prolongadamente.

## Redistribuição automática após desconexão prolongada do atendente — APROVADO
- Cada empresa poderá definir um **prazo de desconexão contínua** para reavaliar e redistribuir atendimentos humanos; **10 minutos é o valor inicial sugerido**, configurável.
- Após detectar que o funcionário não possui mais sessão/conexão ativa, marcar seu estado como **Offline**, tornando-o inelegível para novas distribuições; considerar todas as sessões válidas e tolerância a falhas transitórias antes de confirmar a desconexão.
- **Antes de vencer o prazo** não redistribuir automaticamente conversas já atribuídas ao funcionário apenas pela queda da conexão. Caso o funcionário volte em tempo, preservar responsabilidade e andamento.
- Se a desconexão ultrapassar o prazo, identificar as **conversas ainda abertas que precisam de resposta/continuidade**, verificar novamente indisponibilidade e posse atual e **redistribuir automaticamente somente as elegíveis**, obedecendo equipe, permissões, expediente, capacidade e estratégia configurada (rodízio ou menor carga).
- Havendo funcionário disponível, atribuir atendimento a ele com registro de horário, motivo "desconexão prolongada" e contexto completo preservado.
- Sem atendente elegível disponível, **manter as conversas em fila e alertar a equipe/responsável autorizado**; não encerrar atendimento nem reativar IA automaticamente enquanto houver pedido de humano ou posse humana.
- Registrar de modo auditável a desconexão detectada, prazo aplicável, reavaliações, alertas, responsáveis anteriores e novos e motivo das transferências; evitar ações duplicadas e condições de corrida.
- Se o funcionário original reconectar após a redistribuição, **não retomar automaticamente** as conversas já atribuídas a terceiros; só mediante nova transferência autorizada.
- **A detalhar posteriormente:** quais conversas são consideradas sem resposta para redistribuição, exceções por equipe, precedência de limites de carga simultânea, prazos de presença/heartbeat e tratamento de situações em que uma resposta esteja sendo enviada.

## Limite configurável de atendimentos simultâneos por funcionário/equipe — APROVADO
- Cada empresa pode configurar limites máximos de conversas simultâneas por **funcionário e/ou equipe** na Central de Atendimento WhatsApp; não há um teto fixo universal imposto pelo CRM Imagine para todas as empresas.
- A distribuição automática deve considerar a **capacidade restante** de cada funcionário elegível, além das regras já aprovadas de disponibilidade, equipe, expediente, permissões e estratégia de alocação.
- Quando um funcionário atingir o limite configurado, **não receberá novas conversas automaticamente** até liberar capacidade; isso não precisa alterar seu estado manual Disponível/Ocupado/Ausente/Offline.
- Encerramento ou transferência efetiva de responsabilidade libera capacidade, segundo os critérios de contagem aprovados posteriormente. Se todos os atendentes elegíveis estiverem no limite, manter novas solicitações em **fila de espera**, com histórico e status preservados e sem reativação indevida de IA.
- O limite configurável poderá ser diferenciado por funcionário e equipe. A precedência entre limites individuais e coletivos ainda precisa ser especificada; os estados de conversa contabilizados foram aprovados posteriormente.
- Evitar distribuição acima do limite por corridas entre eventos simultâneos, usando alocação transacional/controle de concorrência; não retirar conversas já atribuídas automaticamente quando um administrador reduzir o teto abaixo da ocupação atual.
- Exemplo apenas ilustrativo: limite 5 por funcionário. Nenhum número foi aprovado como valor padrão obrigatório.

## Quais conversas contam no limite simultâneo humano — APROVADO
- A capacidade de atendimento de cada funcionário considera **somente conversas abertas sob sua responsabilidade efetiva**.
- Contabilizar: (1) conversas abertas em atendimento humano, (2) conversas abertas atribuídas ao humano mesmo quando ele está **aguardando resposta do cliente**, e (3) **transferências pendentes** nas quais o atendente de origem permanece responsável até aceite ou início efetivo no destino.
- Não contabilizar: (1) conversas encerradas, (2) conversas sob atendimento **exclusivo de IA**, e (3) conversas **na fila sem funcionário responsável**.
- Quando uma transferência é efetivamente aceita/iniciada por outro funcionário, remover a conversa da contagem do responsável anterior e contabilizá-la no novo responsável, evitando dupla contagem.
- A contagem deve ser alterada de forma atômica nas mudanças de responsável/status para evitar exceder limites por concorrência ou criar capacidade fictícia.
- O estado de funcionário (Disponível, Ocupado, Ausente, Offline) define **elegibilidade para novas atribuições** e não altera por si só a quantidade de conversas abertas que já estão sob sua responsabilidade.
- **A definir:** tratamento da reserva de capacidade quando uma conversa é pré-atribuída mas ainda não aceita, e precedência entre limite individual e limite coletivo.
