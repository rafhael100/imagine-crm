# Registro consolidado de decisões — 31/10/2026

**Origem:** aprovações fornecidas pelo responsável do projeto em conversas de especificação do CRM Imagine.

| Tema | Decisão aprovada |
|---|---|
| Plataforma | SaaS multiempresa, conta em múltiplos tenants |
| Tecnologia | Laravel REST, PostgreSQL, n8n VPS separado e OpenAI |
| Planos | Essencial, Profissional, Empresarial; assentos adicionais |
| Trial | 14 dias, CNPJ único, e-mail + telefone OTP, sem extensão |
| Trial — cotas | 2 usuários, 20 leads, 2 pipelines, 2 automações, 20 MB, CSV sim, API não |
| Trial — IA | Prospector 10 análises, Comercial 10 solicitações |
| Retenção pós-trial | 60 dias após expiração |
| Inadimplência | Tolerância 7 dias; cancelamento aos 30 dias de atraso |
| Retenção pós-assinatura | 120 dias |
| Pagamentos | Cartão, Pix Automático, Pix avulso, boleto |
| Financeiro | Créditos, estornos, NFS-e automatizada, portal financeiro |
| IA | Agentes opcionais, cotas isoladas, compras extras autorizadas |
| Gestão de clientes | Suspensão/reativação administrativas justificadas e auditadas |
| Administração geral | Permissões detalhadas, MFA, timeout 30 min, auditoria 5 anos |
| Operações | Produção/homologação, deploy controlado, painel de saúde |
| Backups | Completo diário, incrementais/PITR, criptografia externa, 30 dias |
| Recuperação | Metas RPO 15 min e RTO 4 h |
| Administração da empresa | Administrador principal e adicionais isolados |
| Permissões do cliente | Perfis por ação/módulo; escopo próprio/equipe/empresa |
| Equipes | Gestor, integrantes e vínculo em várias equipes |
| Transferências | Individual/em lote com histórico e auditoria |
| Desativação | Revogar acesso/sessões na empresa, preservar histórico |
| Convites | E-mail, validade 7 dias, limite de assentos |
| Segurança cliente | MFA obrigatório para admins da empresa; opcional ou exigível aos demais |
| Auditoria comercial | Imutável por admin cliente, retenção padrão 12 meses |
| Exportação comercial | CSV/XLSX, permissão específica, escopo de visibilidade, auditoria, grandes volumes assíncronos, link seguro temporário, sem credenciais |
| Importação comercial | CSV/XLSX, mapeamento, prévia, detecção de duplicados, ignorar/atualizar, relatório, processamento assíncrono, limites e auditoria |
| Campos personalizados | Por empresa, em leads/contatos/oportunidades; texto, número, moeda, data, seleção única/múltipla e booleano; formulários, filtros, relatórios e importação/exportação |
| Limites de campos personalizados | Por módulo: Essencial 10, Profissional 25, Empresarial 50; downgrade preserva existentes, bloqueia criação enquanto acima do limite |
| Alteração do tipo de campo | Sem dados: permitida; com dados: apenas conversão compatível validada e pré-visualizada; incompatíveis bloqueadas; permissão e auditoria |
| Linha do tempo comercial | Leads, contatos e oportunidades: criação, campos, responsável, etapas, tarefas e interações; usuário/data/hora, visibilidade por permissão; separada da auditoria técnica |
| Proteção de campos personalizados | Arquivamento preserva dados; exclusão com dados bloqueada até tratamento; permissão, confirmação, auditoria e alerta de dependências |
| Validação de campos personalizados | Obrigatoriedade na criação/edição, valor padrão, validações por tipo, opções, ordem de exibição e proteção de campos essenciais; aplicar no backend |

**Atenção:** os arquivos detalhados prevalecem sobre resumos desta tabela. Uma proposta em discussão não equivale a aprovação.

- **Anotações internas (aprovado):** texto, menções @usuário, anexos, visibilidade conforme permissões, notificações internas, histórico de edição/exclusão e sem envio automático aos clientes.

- **Tarefas e lembretes comerciais (aprovado):** vinculados a leads, contatos e oportunidades; título, descrição, responsável, prioridade, prazo, quatro status, lembretes, recorrência, lista/calendário e eventos na linha do tempo, conforme permissões.

- **Atividades comerciais (aprovado):** ligação, WhatsApp, e-mail, reunião, visita e outro; registro independente de tarefa, com data, horário, responsável, descrição e resultado, vinculado a leads, contatos ou oportunidades e exibido na linha do tempo conforme permissões.

- **Funis de vendas Kanban (aprovado):** múltiplos funis por empresa, etapas personalizáveis/reordenáveis, movimentação Kanban, responsáveis, valor, previsão de fechamento, histórico, permissões e estados finais Ganha/Perdida com motivos de perda configuráveis.

- **Regras de movimentação de oportunidades (aprovado):** requisitos e campos obrigatórios por etapa, permissões, bloqueio de movimentações inválidas, retorno de etapa com histórico, confirmação para ganhar/perder e motivo obrigatório da perda; regras opcionais por funil e aplicadas no backend, inclusive a integrações/automações.

- **Probabilidade e previsão ponderada (aprovado):** percentual configurável por etapa/funil, ajuste por oportunidade com permissão, ganha 100%, perdida 0%, valor ponderado = valor × probabilidade, disponível em relatórios/previsões; percentuais ilustrativos não são padrões aprovados.

- **Catálogo comercial (aprovado):** produtos e serviços por empresa, com nome/código/descrição/preço/status, múltiplos itens por oportunidade, quantidade/preço unitário, descontos por item ou total conforme permissões, cálculo automático, histórico; sem estoque no escopo inicial.

- **Propostas comerciais (aprovado):** geração a partir de oportunidades com templates por empresa, dados comerciais e itens, PDF, numeração única por empresa, validade, versionamento, status rascunho/enviada/aceita/recusada/expirada, envio por e-mail ou link seguro; aceite registrado no CRM, sem assinatura eletrônica integrada na primeira etapa.

- **Fluxo de aprovação de propostas (aprovado):** opcional por empresa; gestor aprova descontos acima do limite e preços fora da política; bloquear envio enquanto pendente; registrar decisão, responsável, data e justificativa; validar também via backend.

- **Compartilhamento de propostas (aprovado):** link seguro exclusivo com validade configurável, código de verificação opcional, registro de visualizações, aviso ao vendedor, revogação e vínculo à versão correta; visualização não constitui aceite.

- **Aceitação e recusa de propostas (aprovado):** aceite com data/responsável/versão; recusa com motivo; impedir aceite de proposta expirada sem renovação; revisão exige novo aceite; atualizar histórico da oportunidade; empresa escolhe ganho automático ou confirmação do vendedor; aceite comercial sem assinatura eletrônica formal.

- **Motivos de perda (aprovado):** lista inicial Preço, Concorrência, Sem orçamento, Sem interesse, Prazo, Sem retorno e Outro; personalização por empresa, motivo obrigatório ao perder oportunidade, observação opcional e análise em relatórios.

- **Reabertura de oportunidades (aprovado):** ganhas ou perdidas podem ser reabertas por usuário autorizado, com justificativa, histórico preservado, escolha da etapa de retorno, recálculo da probabilidade/valor ponderado e relatórios sem duplicidade de ganhos/perdas.

- **Metas comerciais (aprovado):** individuais por vendedor, por equipe e por empresa; períodos mensal/trimestral/anual; indicadores valor vendido, quantidade de vendas e oportunidades ganhas; acompanhamento de atingimento/evolução; gestão e visualização por permissão; sem duplicidade de vendas reabertas.

- **Dashboard comercial (aprovado):** indicadores automáticos de leads, oportunidades, funil, previsão total/ponderada, conversão, metas, tarefas, desempenho e motivos de perda; gráficos e filtros por período, funil, vendedor e equipe; isolamento por empresa e permissões.

- **Relatórios comerciais (aprovado):** vendas por período, conversão de leads, desempenho por vendedor/equipe, funil, motivos de perda, atividades, metas e previsão de vendas; filtros por período/funil/vendedor/equipe, exportação CSV/XLSX/PDF e permissões por empresa.

- **Relatórios personalizados (aprovado):** seleção de módulos/campos, colunas reordenáveis, filtros combináveis, agrupamentos, contagem/soma/média, configurações salvas, compartilhamento privado ou autorizado e exportação CSV/XLSX/PDF; campos personalizados e isolamento/permissões aplicados.

- **Agendamento de relatórios (aprovado):** frequência diária/semanal/mensal, destinatários internos autorizados, PDF/XLSX/CSV, filtros salvos, envio de link seguro por e-mail, revalidação de permissões na geração, pausa/edição/cancelamento e histórico de execução/entrega/falha.

- **Automações comerciais (aprovado):** gatilhos por lead, etapa, tarefa vencida e proposta aceita/recusada; condições por responsável/etapa/valor/campos personalizados; ações de tarefa, atribuição, notificação e atualização; execução imediata ou agendada, histórico, permissões, controle de ativação e proteção contra loops/duplicações.

- **Automações por tempo e inatividade (aprovado):** prazo em horas/dias; criar tarefa para lead sem contato, notificar oportunidade parada, lembrar proposta sem resposta; nova interação reinicia contagem e execução deve evitar duplicidades.

- **Distribuição de leads (aprovado):** rodízio, menor carga, equipe/origem/campanha, responsável fixo e atribuição manual; apenas vendedores ativos/habilitados, com permissões e histórico.

- **Redistribuição automática de leads sem atendimento (aprovado):** opcional por empresa, prazo em minutos/horas, notificação, redistribuição por atraso/indisponibilidade a vendedor habilitado, limite de tentativas, histórico e exceções para leads estratégicos; não redistribuir após atendimento válido.

- **Lead Scoring (aprovado):** pontuação 0–100 por regras configuráveis de origem, perfil, engajamento e inatividade; classificação frio/morno/quente; integração com automações de atribuição, tarefas e notificações; sem IA obrigatória no cálculo inicial. Pesos e faixas pendentes.

- **Faixas do Lead Scoring (aprovado):** padrão inicial Frio 0–39, Morno 40–69, Quente 70–100; limites personalizáveis por empresa sem sobreposição; classificação recalculada quando a pontuação mudar.

- **Histórico do Lead Scoring (aprovado):** registrar pontuação anterior/nova, motivo/regra, data/hora e evento/automação; exibir gráfico de evolução no cadastro do lead, separado da auditoria técnica e sujeito às permissões.

- **Duplicidade e mesclagem de leads (aprovado):** detectar por telefone, e-mail e identificadores em cadastro/importação/integrações; alertar, consultar, ignorar/atualizar conforme permissão; mesclar com registro principal e revisão de conflitos, preservando históricos, origens e auditoria.

- **Formulários públicos de captação (aprovado):** criação sem código com campos padrão/personalizados; link público ou incorporação; rastreio de formulário/campanha; distribuição, antispam, limites, consentimento LGPD registrado, integração com scoring/duplicidade/automações e métricas de visualizações, envios e conversão; isolamento por empresa.

- **Personalização visual dos formulários (aprovado):** logotipo, cores, ordenação/obrigatoriedade de campos, mensagem de sucesso, redirecionamento pós-envio, responsividade, pré-visualização e atualização sem mudar link; configurações isoladas por empresa.

- **Formulários com WhatsApp (aprovado):** botão após envio via link oficial sem API obrigatória, mensagem e número configuráveis, intenção de contato vinculada ao lead, métrica de cliques, tarefas/notificações e privacidade na URL; clique não confirma mensagem enviada.

- **WhatsApp Business API oficial (aprovado):** integração opcional por empresa com número habilitado, caixa de entrada e respostas no CRM, atribuição a equipes/vendedores, histórico vinculado a leads/contatos, automações, templates aprovados, regras da Meta, permissões/auditoria/privacidade e custos da Meta separados da assinatura.

- **Caixa de entrada compartilhada do WhatsApp (aprovado):** múltiplos atendentes por número, responsável por conversa, transferências entre vendedores/equipes, status aguardando/em atendimento/resolvida, notas internas, histórico, supervisão autorizada e prevenção de respostas conflitantes.

- **Distribuição automática das conversas do WhatsApp (aprovado):** rodízio, menor carga, equipe/assunto, horário e disponibilidade; priorizar último responsável em retornos, fila quando ninguém estiver disponível, histórico de atribuições/transferências e permissões.

- **Horários e mensagens automáticas do WhatsApp (aprovado):** expediente semanal, feriados e horários especiais; saudações, ausência e aviso de fila personalizáveis por número/equipe; retomar distribuição no próximo expediente; prevenir repetição e respeitar políticas da Meta.

- **SLA de atendimento pelo WhatsApp (aprovado):** prazos configuráveis para primeira resposta e respostas seguintes; contagem apenas no expediente, alertas prévios e escalonamento ao gestor, painel de cumprimento, relatórios de tempo médio/taxa de cumprimento e pausas configuráveis.

- **CSAT WhatsApp (aprovado):** pesquisa opcional por empresa após atendimento, nota de 1 a 5 e comentário facultativo; envio manual ou automático conforme regras da Meta, histórico por conversa/atendente, médias por vendedor/equipe e alertas de notas baixas com limiar configurável.

- **Respostas rápidas e biblioteca WhatsApp (aprovado):** mensagens prontas com atalhos e variáveis (cliente, empresa, vendedor), biblioteca compartilhada por equipe/empresa, gestão autorizada, pré-visualização, registro no histórico e distinção entre respostas livres e templates oficiais da Meta.

- **Etiquetas de conversas WhatsApp (aprovado):** categorias e cores personalizadas por empresa, múltiplas etiquetas por conversa, aplicação manual/automática, filtros e relatórios por etiqueta, gestão sujeita a permissões e registro das alterações no histórico.

- **Anexos WhatsApp (aprovado):** imagens, documentos, áudios e vídeos compatíveis; associação ao histórico de conversa/contato, busca por tipo/data, validação de formatos/tamanhos/acesso e consumo do armazenamento do plano, observando limites da API oficial.

- **Encerramento/reabertura WhatsApp (aprovado):** resolução por atendente autorizado com motivo configurável; nova mensagem reabre e prioriza último responsável disponível, preserva histórico, reinicia SLA, permite CSAT por atendimento e relatórios de resoluções/reaberturas.

- **Chatbot WhatsApp sem código (aprovado):** fluxos visuais baseados em regras e menus com saudação, opções, perguntas, condições, encaminhamento para equipe/vendedor, expediente, registro de respostas, intervenção humana e proteção contra loops; IA apenas como recurso opcional futuro, sujeito às regras da Meta.

- **Transferência chatbot → humano (aprovado):** solicitação do cliente, falhas de compreensão ou assunto prioritário; resumo do contexto ao atendente, pausa do bot, retorno autorizado, fila quando indisponível e registro de motivo/horário/responsável, seguindo distribuição e SLA.

- **Editor visual do chatbot WhatsApp (aprovado):** blocos de mensagem, pergunta, menu, condição, espera, ação no CRM, transferência e encerramento; rascunhos, testes antes da publicação e versionamento de fluxos.

- **Gatilhos do chatbot WhatsApp (aprovado):** primeira mensagem, palavra-chave, fora do expediente, origem/campanha/formulário, conversa reaberta e ação manual; prioridade configurável entre gatilhos e bloqueio de reinício do bot durante atendimento humano ativo.

- **Erros e inatividade do chatbot WhatsApp (aprovado):** repetir pergunta após resposta inválida, transferir após tentativas excedidas, lembrete por ausência, pausa/encerramento por inatividade, retomada ou reinício no retorno, registro e notificação de falhas, prevenção de repetição/loops e histórico; observar regras da Meta.

- **Métricas e relatórios do chatbot WhatsApp (aprovado):** conversas iniciadas, conclusão, transferência humana, abandono, etapas críticas, duração média, erros e conversão em leads/oportunidades; filtros por período/fluxo/número/equipe e exportação autorizada.

- **Limites de fluxos ativos do chatbot WhatsApp (aprovado):** Essencial 3, Profissional 10, Empresarial 30 por empresa; rascunhos não contam, upgrade amplia limite e downgrade preserva fluxos mas pausa excedentes até escolha do administrador; independente do limite de automações comerciais.

- **Versionamento e publicação do chatbot (aprovado):** rascunho independente da versão publicada, validação antes de publicar, conversas em andamento fixadas à versão inicial, novas conversas na última versão, histórico de autor/data/alterações, reversão autorizada e desativação sem apagar histórico.

- **Simulador de fluxos do chatbot (aprovado):** testes de conversas e ramificações sem mensagens reais, dados fictícios, validação de blocos e diagnóstico por etapa, simulação sem alterar CRM, bloqueio de publicação com erros críticos e histórico de testes.

- **Biblioteca de modelos de chatbot (aprovado):** fluxos prontos para atendimento inicial, captação, qualificação, orçamento, suporte, fora do expediente e satisfação; cópia e personalização por empresa sem alterar originais; administração Imagine atualiza biblioteca central sem sobrescrever cópias dos clientes.

- **Importação/exportação de fluxos de chatbot (aprovado):** JSON com validação de estrutura/compatibilidade, duplicação sem alterar original, remapeamento de equipes/campos/etiquetas, exclusão de segredos e dados de clientes, importação como rascunho, histórico e permissões por empresa; limites do plano aplicados aos fluxos ativos.

- **Integração do chatbot com agendamentos (aprovado):** solicitação por serviço/assunto, consulta de horários na agenda conectada, criação com data/horário/responsável, confirmação WhatsApp, reagendamento/cancelamento conforme regras da empresa, lembretes, vínculo a lead/contato e prevenção de duplicidade; respeitar políticas da Meta.

- **Agenda integrada do CRM (aprovado):** calendário diário/semanal/mensal; criar/editar/reagendar/cancelar; responsáveis/equipes, disponibilidade, folgas/bloqueios, participantes leads/contatos/convidados, prevenção de conflitos, agenda compartilhada com chatbot, notificações e histórico; independente de tarefas, com visualização conjunta no calendário.

- **Integração da agenda com Google Calendar e Outlook/Microsoft 365 (aprovado):** conexão individual, sincronização bidirecional, eventos externos bloqueiam disponibilidade, privacidade de eventos pessoais como “Ocupado”, prevenção de conflitos, desconexão e alertas de falha; respeitar permissões e isolamento entre empresas.

- **Página pública de agendamentos (aprovado):** link exclusivo por empresa, identidade visual personalizada, seleção de serviço/profissional, disponibilidade integrada ao CRM/Google/Outlook, formulário configurável, confirmação por e-mail/WhatsApp, link seguro para reagendar/cancelar, vínculo automático a lead/contato e prevenção de spam e duplicidades.

- **Configuração de serviços e agendamentos (aprovado):** nome/descrição, duração, intervalo, antecedência mínima/máxima, capacidade, profissionais habilitados, modalidade presencial/online/telefone, prazo de cancelamento e visibilidade pública/interna; regras compartilhadas entre agenda CRM, página pública e chatbot, respeitando permissões.

- **Confirmação de presença e faltas (aprovado):** confirmação antecipada via WhatsApp/link seguro; status agendado/confirmado/realizado/não compareceu/cancelado; check-in por funcionário; registro automático ou manual de falta; reagendamento, histórico, relatórios por serviço/profissional e automações por status, integrados à agenda, chatbot e página pública.

- **Videoconferências na agenda (aprovado):** integração com Google Meet, Microsoft Teams e Zoom para gerar links conforme autorização, enviar convites, atualizar reagendamentos, cancelar eventos/notificar participantes, registrar links e histórico, proteger credenciais; sujeito às capacidades de cada plataforma.

- **Agendamentos recorrentes (aprovado):** periodicidade diária/semanal/quinzenal/mensal; fim por data ou quantidade; conflitos validados por ocorrência; edição/cancelamento individual ou de toda série; lembretes por ocorrência, sincronização Google/Outlook e histórico de alterações/exceções.

- **Lista de espera para agendamentos (aprovado):** interesse e preferências de dias/horários/profissionais; identificação de vagas liberadas, avisos por WhatsApp/e-mail, prazo configurável, prioridade por ordem de entrada com exceções autorizadas, confirmação somente após aceite e prevenção de dupla reserva; histórico de ofertas/aceites/recusas.

- **Gerenciamento de salas e recursos (aprovado):** cadastro de salas/equipamentos/recursos, capacidade, disponibilidade e bloqueios, vínculo com serviços, reserva simultânea de profissional e recurso, prevenção de dupla reserva, bloqueios de manutenção, calendário por recurso e histórico; integrado à agenda, chatbot e página pública.

- **Agendamentos em grupo (aprovado):** sessões individuais ou coletivas, capacidade/vagas restantes, múltiplas inscrições por sessão, lista de espera ao esgotar, confirmação e presença individuais, cancelamento liberando apenas a vaga, histórico e relatórios de ocupação/presença/cancelamentos; conflitos de profissionais e recursos validados por sessão.

- **Pagamentos por agendamento (rejeitado):** não implementar cobrança antecipada, sinal ou checkout de Pix/cartão para reservas de serviços das empresas. Agenda continua sem processamento de pagamentos; cobrança das assinaturas do CRM permanece independente.

- **Personalização de notificações de agendamento (aprovado):** confirmação, lembretes com antecedência configurável, reagendamento e cancelamento; variáveis nome/serviço/data/horário/profissional; canais e-mail e WhatsApp integrado, ativação individual por tipo e histórico de envios/falhas; respeitar políticas da Meta.

- **Fusos horários na agenda (aprovado):** fuso principal da empresa, fuso individual opcional por funcionário e exibição no fuso identificado/selecionado do cliente; instantes padronizados em UTC, fuso explícito nas confirmações, conversão Google/Outlook, horário de verão e relatórios no fuso escolhido; prevenção de conflitos.

- **Relatórios e indicadores da agenda (aprovado):** total de agendamentos, comparecimento, cancelamentos, faltas, ocupação de profissionais/salas, serviços mais procurados, origem (chatbot/página pública/funcionários), horários de maior demanda; filtros período/serviço/profissional/equipe/unidade e exportação CSV/XLSX/PDF com permissões.

- **Múltiplas unidades de atendimento (aprovado):** cadastro de filiais/locais com endereço e contato, expediente/feriados próprios, profissionais em uma ou mais unidades, serviços e recursos por unidade, seleção de unidade nos agendamentos, permissões por unidade e relatórios comparativos; unidades compartilham assinatura da mesma empresa.

- **Limites de unidades por plano (aprovado):** Essencial 1, Profissional 3, Empresarial 10 unidades incluídas por empresa; contratação de unidades adicionais com preço extra a definir, sem exigir troca de plano; upgrade amplia limite e downgrade inativa excedentes sem apagar configurações, histórico ou agendamentos anteriores.

- **Transferência de agendamentos entre unidades (aprovado):** mover reservas entre unidades da mesma empresa com validação de profissional/sala/horário, confirmação por usuário autorizado, aviso ao cliente de endereço/horário, histórico de origem/destino/responsável, bloqueio de conflitos e identificação em relatórios.

- **Bloqueios e indisponibilidades da agenda (aprovado):** bloqueios individuais, por unidade e por recurso, únicos ou recorrentes, com motivo; funcionário gerencia sua agenda e gestor as equipes conforme permissões; alertar conflitos com agendamentos existentes antes de aplicar, registrar histórico e impedir novas reservas nos horários bloqueados em todos os canais.

- **Permissões específicas da agenda (aprovado):** visualização própria/equipe/unidade/empresa, criar/editar/cancelar próprios ou de terceiros, transferir entre profissionais/unidades, gerenciar bloqueios, configurar serviços/horários/recursos, consultar relatórios por perfil e auditar alterações; verificações no backend e isolamento entre empresas.

- **Auditoria e histórico da agenda (aprovado):** criação (autor/data/canal), alterações (antes/depois), reagendamento, cancelamento com motivo, transferências, confirmações, check-in/faltas e origem de mudanças Google/Outlook; registros protegidos contra edição/exclusão, retenção de 12 meses durante assinatura ativa, ressalvadas obrigações legais.

- **Portal do cliente para agendamentos (aprovado):** página responsiva com identidade da empresa e link seguro por e-mail/WhatsApp, sem senha própria; consultar próximos agendamentos e histórico, confirmar presença, reagendar/cancelar conforme regras, acessar links de Meet/Teams/Zoom e visualizar exclusivamente os próprios agendamentos.

- **Pesquisa de satisfação após agendamentos (aprovado):** envio automático após atendimento realizado por e-mail/WhatsApp integrado, nota de 1 a 5, comentário opcional, mensagem e prazo configuráveis, vínculo a serviço/profissional/unidade, alertas de notas baixas, relatórios por dimensão e permissões; independente da CSAT do WhatsApp.

- **Formulários prévios aos atendimentos (aprovado):** perguntas por serviço, campos de texto/seleção/data/número/anexo obrigatórios ou opcionais, envio por link seguro via e-mail/WhatsApp, prazo configurável, respostas ligadas a agendamento/contato, aviso ao responsável, acesso por permissões e histórico de envio/preenchimento.

- **Documentos e anexos dos agendamentos (aprovado):** clientes enviam via portal/formulário e funcionários autorizados internamente; PDF/imagens/documentos comuns, prévia compatível, vínculo a agendamento/contato, validações de segurança, permissões de acesso/exclusão, consumo de armazenamento do plano e histórico de envio/substituição/exclusão.

- **Lista de presença e controle de comparecimento (aprovado):** check-in manual autorizado ou pelo cliente no portal com validação, check-out, presença individual em grupos, registro de atrasos e ausências, histórico de horários/responsáveis e relatórios de comparecimento; integrado aos estados da agenda sem duplicações.

- **Painel de capacidade e ocupação da agenda (aprovado):** percentuais de disponibilidade/reservas, carga por profissional, utilização de salas e recursos, alertas de capacidade, comparativos diários/semanais/mensais e por unidade, filtros por serviço/profissional/sala/período; cálculo automático com agendamentos e bloqueios, respeitando permissões.

- **Agendamentos ilimitados (aprovado):** planos Essencial, Profissional e Empresarial sem limite comercial de reservas mensais, sujeitos a proteção técnica contra abuso.

- **Confirmação automática de agendamentos (aprovado):** confirmação por link seguro via e-mail/WhatsApp integrado, antecedência e reenvio configuráveis, estados pendente/confirmado/não confirmado, alertas à equipe e histórico de envios/respostas; ausência de resposta não cancela reserva por padrão, salvo regra específica configurada.

- **Cancelamento por falta de confirmação (aprovado):** manter reservas ativas por padrão; cancelamento automático opcional e desligado inicialmente, ativado expressamente por administrador autorizado, com antecedência configurável, aviso prévio, exceções por serviço/cliente, liberação apenas após cancelamento, integração com lista de espera e histórico de motivo/regra/horário.

- **Reagendamento de clientes (aprovado):** pelo portal ou funcionário autorizado, antecedência mínima por serviço, limite opcional de alterações (padrão ilimitado), autorização após prazo, validação de profissional/sala/horário, notificação, histórico e sincronização com agenda, chatbot e calendários externos.

- **Clientes recorrentes (aprovado):** identificação segura, reutilização de cadastro, sugestões de preferências, reservas simplificadas, histórico protegido e prevenção de contatos duplicados.

- **Agendamentos para dependentes e terceiros (aprovado):** titular responsável pode reservar para pessoa vinculada, com dados necessários, escolha do atendido, histórico separado, notificações ao responsável autorizado, habilitação por serviço, vínculo no CRM e proteção de dados/representação conforme LGPD.

- **Agendamento de múltiplos serviços (aprovado):** reserva conjunta de serviços em sequência, com duração e intervalos, profissionais distintos, validação integrada de recursos/horários e confirmação sem reservas parciais; resumo único, alterações individuais ou em conjunto e histórico vinculado por atendimento.

- **Agendamentos com profissionais alternativos (aprovado):** permitir escolha nominal ou 'Qualquer profissional disponível'; sugerir alternativas habilitadas com horários livres, respeitar serviço/unidade e conflitos, equilibrar distribuição, identificar profissional e exigir confirmação do cliente antes da reserva.

- **Substituição de profissional em agendamento existente (aprovado):** trocar responsável sem recriar reserva, validar habilitação/disponibilidade, preservar horário quando possível, exigir permissão, avisar cliente e solicitar aceite quando serviço exigir, registrar antes/depois/responsável, evitar conflitos e atualizar integrações.

- **Agendamentos prioritários e encaixes (aprovado):** marcação normal/prioritário, encaixes excepcionais com permissão especial, justificativa e alertas de sobreposição; não deslocar reservas confirmadas nem exceder capacidade física, enviar confirmação, auditar e incluir em relatórios.

- **Agendamentos com aprovação prévia (aprovado):** configuração por serviço, solicitação pendente sem confirmação imediata, aprovação/recusa por funcionário ou gestor autorizado, prazo configurável, retenção temporária do horário, confirmação ou liberação após decisão, expiração da retenção e histórico auditável.

- **Agendamentos com aprovação prévia (aprovado):** empresa define serviços sujeitos à aprovação; solicitação pendente com bloqueio temporário do horário e prazo configurável; gestor/funcionário autorizado aprova ou recusa, com confirmação/notificação ou liberação, expiração de pendências e histórico de decisão, responsável e justificativa.

- **Fila de aprovação de agendamentos (aprovado):** painel central de solicitações com dados e filtros por serviço/unidade/responsável/status, aprovação individual ou em lote com validação por reserva, recusa justificada, alertas e destaque de vencimentos, expiração automática com liberação do horário e histórico de decisões.

- **Aprovação de agendamentos por múltiplas etapas (rejeitada):** não implementar fluxo sequencial de múltiplos aprovadores; manter aprovação simples por funcionário ou gestor autorizado e fila de aprovação já aprovadas.

- **Notificações internas da agenda (aprovado):** central de avisos no CRM para novos agendamentos, cancelamentos, reagendamentos, aprovações pendentes e vagas da lista de espera; marcar como lida, preferências individuais, controle de permissões e prevenção de duplicidades.

- **Agenda compartilhada da equipe (aprovado):** calendário diário/semanal/mensal com agendas lado a lado, filtros por profissional/equipe/serviço/unidade, disponibilidade e cores, privacidade dos compromissos, edição autorizada e atualização sobre os mesmos registros da agenda principal.

- **Horários individuais por profissional (aprovado):** jornada semanal por dia, intervalos e folgas, exceções por data, horários por unidade e serviço, edição autorizada, disponibilidade recalculada com bloqueios/feriados e histórico de alterações.

- **Antecedência e janela de agendamentos (aprovado):** mínimos e máximos configuráveis por serviço, respeitando expediente/jornada; clientes visualizam somente horários elegíveis no portal/página pública/chatbot; exceções por funcionários autorizados com histórico; sem valores fixos de 2 horas ou 90 dias.

- **Intervalos entre atendimentos (aprovado):** configuração em minutos por serviço de preparação antes e limpeza/descanso depois; bloquear tempo de profissionais/salas/recursos, diferenciar visualmente, impedir conflitos em todos os canais e permitir exceções auditadas apenas a usuários autorizados.

- **Duração personalizada dos atendimentos (aprovado):** funcionário autorizado ajusta duração de reserva individual sem mudar duração padrão do serviço, com justificativa, recálculo de horários/intervalos e disponibilidade de profissional/sala, notificação ao cliente quando mudar início/fim, histórico antes/depois e bloqueio de conflitos.

- **Serviços com duração variável escolhida pelo cliente (rejeitado):** não oferecer escolha de múltiplas durações na reserva; manter duração padrão por serviço e ajuste excepcional individual somente por funcionário autorizado, com justificativa e validação de disponibilidade.

- **Confirmação digital de chegada pelo cliente (rejeitada):** não implementar check-in autônomo pelo portal. Manter check-in/check-out por funcionário autorizado e indicadores de comparecimento; esta decisão substitui a previsão anterior de check-in pelo próprio cliente.

- **Observações internas dos agendamentos (aprovado):** anotações operacionais vinculadas à reserva, autor/data/hora, histórico de edição, consulta nos detalhes, acesso somente a funcionários autorizados e invisibilidade ao cliente; separadas das mensagens e documentos do cliente.

- **Prioridade na lista de espera (rejeitada):** manter ordem cronológica FIFO, sem antecipação manual ou categorias de prioridade. Esta decisão revoga exceções de prioridade previstas anteriormente; manter prazo de resposta, notificações e confirmação após aceite.

- **Agendamentos duplicados por cliente (aprovado):** detectar sobreposição de reservas do mesmo cliente, alertar e bloquear pelo portal/chatbot, permitir exceção apenas por funcionário autorizado com justificativa sem violar recursos, registrar tentativas/exceções e validar no backend.

- **Status de atendimento (aprovado):** Agendado, Aguardando confirmação, Confirmado, Aguardando atendimento, Em atendimento, Concluído, Não compareceu e Cancelado; transições automáticas quando cabíveis ou manuais por funcionário autorizado, com histórico. Chegada registrada por funcionário, sem check-in digital do cliente.

- **Histórico de atendimentos por cliente (aprovado):** no cadastro do contato, consolidar agendamentos passados/futuros, status, serviço, profissional e unidade; filtros por período/serviço/profissional/status, observações internas restritas e atualização automática a partir da agenda existente.

- **Pesquisa de clientes na agenda (aprovado):** localizar por nome, telefone ou e-mail; retornar agendamentos passados/futuros com filtros por período/status/serviço/unidade, acesso direto ao detalhe, respeito às permissões e desempenho adequado em grandes volumes.

- **Participantes individuais em agendamentos de grupo (aprovado):** sessão única com lista de participantes, inclusão até capacidade, cancelamento e presença individuais, notificações individuais, histórico de mudanças, vagas atualizadas automaticamente e privacidade entre clientes; check-in registrado por funcionário autorizado.

- **Conflitos de edição simultânea na agenda (aprovado):** detectar alterações concorrentes, avisar e mostrar versão atual, impedir sobrescrita de dados desatualizados, revalidar disponibilidade ao salvar, registrar alterações e aplicar proteção a funcionários, portal, chatbot e API.

- **Cancelamento de agendamentos pela equipe (aprovado):** somente funcionários autorizados; motivo obrigatório e observação opcional; notificar cliente/profissional, liberar horários e recursos, acionar lista de espera FIFO, sincronizar calendários e preservar registro cancelado com histórico de responsável/data/motivo.

- **Reagendamento pela equipe (aprovado):** funcionário autorizado altera data/horário de reserva existente com motivo obrigatório, valida novamente profissional/sala/recursos e conflitos, notifica cliente, sincroniza calendários e preserva identificador e histórico com antes/depois e responsável.

- **Motivos de cancelamento personalizáveis por empresa (aprovado):** catálogo inicial de desistência/imprevisto/indisponibilidade/duplicidade/outros; criação/edição por administradores autorizados, motivo obrigatório no cancelamento, observação opcional, relatórios por motivo e preservação dos motivos históricos.

- **Motivos de ausência/no-show (aprovado):** catálogo inicial esquecimento/imprevisto/transporte/sem justificativa/outros, personalizável por empresa; falta pode ser registrada sem motivo conhecido, complementado depois por funcionário autorizado com histórico; relatórios por motivo/serviço/profissional.

- **Encerramento adicional de atendimentos (rejeitado):** não criar alertas ou fila específica de atendimentos ainda abertos após o horário previsto; manter status e procedimentos normais, sem conclusão automática pelo horário.

- **Agendamento rápido pela equipe (aprovado):** formulário simplificado diretamente no calendário para funcionários autorizados; pesquisar/cadastrar cliente, selecionar serviço/profissional/data/horário/unidade, criar reserva e notificar cliente, com todas as validações da agenda principal e sem reservas paralelas.

- **Duplicação manual de agendamentos pela equipe (aprovado):** funcionário autorizado cria nova reserva reaproveitando cliente/serviço/profissional/unidade, escolhe nova data/horário, revisa resumo, valida disponibilidade/conflitos, notifica cliente e mantém registros independentes; não é recorrência automática.

- **Agendamentos favoritos (rejeitado):** não criar atalhos pessoais de combinações serviço/profissional/unidade; manter agendamento rápido e duplicação manual existentes.

- **Bloqueio de horários em lote (aprovado):** selecionar múltiplos dias/períodos por profissional/equipe/unidade/recurso, informar motivo, pré-visualizar reservas afetadas, aplicar bloqueios sem cancelar reservas automaticamente, manter histórico e permitir reversão autorizada.

- **Feriados automáticos na agenda (aprovado):** carregar feriados nacionais, estaduais e municipais por localização de cada unidade, atualizar anualmente, permitir que empresa decida atendimento/bloqueio e exceções por data/unidade, manter histórico; feriado identificado não bloqueia automaticamente sem configuração.

- **Horários especiais de funcionamento (aprovado):** abertura antecipada, fechamento tardio ou expediente reduzido em uma ou várias datas, por unidade; exceção prevalece sobre horário regular, respeita disponibilidade dos profissionais/recursos, alerta sobre reservas conflitantes sem cancelamento automático e mantém histórico de alterações.

- **Limite de agendamentos futuros por cliente (rejeitado):** sem limite quantitativo global ou por serviço; manter apenas regras existentes de disponibilidade, capacidade e prevenção de sobreposição.

- **Cadastro rápido de clientes diretamente na agenda (aprovado):** funcionário autorizado cadastra contato com nome/telefone/e-mail e campos configuráveis sem sair do calendário; valida duplicidade, reutiliza base única de contatos, vincula à reserva e registra responsável/data/hora.

- **Confirmação manual de agendamentos pela equipe (aprovado):** funcionário autorizado marca Confirmado quando cliente confirma por telefone, presencialmente, WhatsApp ou outro canal; registra origem, responsável e data/hora, preserva histórico e evita novos pedidos automáticos de confirmação, usando o mesmo fluxo da confirmação por link.

- **Envio manual de lembretes de agendamento (aprovado):** botão na agenda para funcionário autorizado enviar lembrete por e-mail ou WhatsApp integrado, usando modelo configurado e prévia, prevenção de duplicidades em curto intervalo, histórico de responsável/data/canal e respeito às regras da Meta.

- **Preferências de notificações da agenda (aprovado):** funcionário escolhe tipos e canais (notificação interna/e-mail) para novos agendamentos, cancelamentos, reagendamentos, trocas de profissional e confirmações; avisos operacionais essenciais não podem ser desativados; manter histórico de envios e permissões.

- **Painel de agenda do dia (aprovado):** visão operacional diária para recepção/gestores com lista cronológica, totais/confirmados/aguardando/cancelados, filtros por profissional e unidade, ações rápidas de confirmar/reagendar/cancelar, atualização automática e permissões, usando registros da agenda principal.

- **Agenda individual do profissional (aprovado):** tela Minha agenda com atendimentos atribuídos em ordem cronológica, cliente e serviço conforme permissões, horário/duração/status, navegação entre datas, detalhes e atualização de status autorizada, sincronizada com agenda geral sem duplicação de dados.

- **Isolamento multiempresa PostgreSQL RLS (aprovado):** `tenant_id` em dados por empresa, RLS no banco e validação no Laravel; usuário multiempresa só acessa vínculos autorizados; relatórios/exportações também isolados; acesso administrativo excepcional auditado; testes automatizados contra vazamentos entre empresas.

- **Armazenamento privado de arquivos S3 (aprovado):** documentos, propostas, imagens e anexos em serviço privado compatível com S3, separados por empresa, autorização no Laravel, downloads temporários, compartilhamento externo específico e revogável, quotas por plano, retenção/auditoria e backup/recuperação; fornecedor e limites exatos pendentes.

- **Segurança dos uploads (aprovado):** validação automática de tipo real/extensão/tamanho/integridade e compactados, ClamAV em fila assíncrona, quarentena privada S3 até aprovação, bloqueio em falhas do antivírus, rejeição de executáveis/conteúdo perigoso, sem execução no servidor e auditoria de resultados; limites exatos pendentes.

- **Limite de upload (aprovado):** máximo de **5 MB por arquivo**, em todos os formatos permitidos e planos, inclusive integrações; rejeitar maiores que o limite, mantendo validação antivírus e quotas totais de armazenamento separadas. Proposta anterior de 10/25/50 MB não aprovada.

- **Otimização automática de imagens (aprovada com ajuste):** otimizar fotos, produtos, documentos digitalizados, comprovantes, assinaturas e imagens com texto, sem prejudicar nitidez, legibilidade ou fidelidade; fotos comuns até ~1920 px e qualidade inicial 80–85%, WebP quando vantajoso; documentos com compressão conservadora/sem perda quando necessário; preservar original se redução segura impossível e não invalidar PDFs assinados; evitar versões duplicadas e cobrar quota pelo tamanho armazenado. Upload máximo permanece 5 MB.

- **Quotas de armazenamento por plano (aprovado):** Essencial **1 GB**, Profissional **5 GB**, Empresarial **10 GB** por empresa; quota compartilhada, contabilizada pelo armazenamento efetivo após otimização; novos uploads bloqueados ao atingir limite, arquivos existentes preservados. Limite de 5 MB por arquivo permanece. Proposta de 20 GB Empresarial substituída por 10 GB.

- **Direção comercial do CRM Imagine (aprovada):** mensalidade fixa por empresa com usuários incluídos; três planos Essencial/Profissional/Empresarial; contatos ilimitados nos planos pagos sujeitos a uso justo; usuários extras e serviços de IA/WhatsApp como adicionais; trial de 14 dias sem cartão e sem freemium permanente inicial; aquisição por parceiros, produto e campanhas segmentadas. Preços R$ 69/149/299 e usuários incluídos 2/5/10 são **hipóteses para validação, não preços aprovados**. Quotas técnicas de armazenamento 1/5/10 GB aguardam revisão comercial explícita.

- **Fase de MVP comercial (aprovada):** próxima etapa será definir e priorizar a primeira versão vendável do CRM Imagine, visando conquistar os primeiros clientes pagantes e validar valor de mercado; divisão exata de funcionalidades entre MVP e versões futuras ainda depende de aprovação.

- **MVP Web responsivo (aprovado):** lançamento inicial do CRM Imagine em aplicação Web responsiva para desktop, tablet e celular; sem aplicativos nativos Android/iOS na primeira versão, sujeitos a avaliação futura conforme demanda e retorno.

- **Diretriz de diferenciação do MVP (solicitada pelo responsável):** IA e automações devem ser prioridades desde a primeira versão comercial, não apenas funcionalidades futuras. Escopo mínimo de cada agente, automações e integrações ainda depende de definição e aprovação.

- **MVP com IA (aprovado):** incluir IA Prospector, IA Comercial e automações comerciais essenciais no lançamento.
- **WhatsApp como diferencial de lançamento (requisito do responsável):** integrar WhatsApp com automações e envio automático de mensagens pelos agentes de IA e pelas regras do CRM; detalhamento de canais, autonomia, consentimento e preços ainda pendente.

- **WhatsApp e atendimento omniorigem no MVP (aprovado):** API oficial Meta; central compartilhada no CRM com histórico unificado e autoria identificada (humano, IA, automação, cliente); envio/recebimento e disparos automáticos por automações e agentes de IA; IA autônoma sob limites; humano visualiza e assume conversa com contexto preservado. Regra de pausa automática da IA na tomada por humano proposta, aguardando aprovação.

- **Transferência humano ↔ IA no WhatsApp (aprovada):** quando humano assume conversa, agente de IA pausa respostas naquela conversa; só retoma após devolução humana explícita. Preservar histórico, contexto e trilha de auditoria; bloquear mensagens da IA pendentes após mudança de posse.

- **Pausa de automações WhatsApp no atendimento humano (aprovada):** quando humano assume, suspender todos os envios automáticos ao cliente na conversa, inclusive follow-ups e lembretes; automações internas continuam. Ao devolver à IA, reavaliar cada disparo, sem envio em massa automático das mensagens acumuladas.

- **WhatsApp com múltiplos números por empresa (aprovado):** contratar integração com 1 número incluído e cobrar por números adicionais (valores pendentes); números podem ter equipes, filas, automações e agentes específicos; central única com permissões e identificação clara do número de cada conversa/mensagem; custos variáveis Meta/BSP a definir separadamente.

- **Histórico único do cliente entre números WhatsApp (aprovado):** cadastro único no CRM por empresa/tenant com linha do tempo integrada, mas conversas operacionais individualizadas por número oficial, com origem/autoria explícitas e respeito a permissões por equipe e usuário; regras de identificação e vinculação de cadastros ainda pendentes.

- **Identificação de clientes entre linhas WhatsApp (aprovado):** vincular automaticamente mensagens ao contato quando houver correspondência inequívoca pelo mesmo telefone na mesma empresa; para números diferentes, apenas sugerir correspondências e exigir confirmação manual por funcionário autorizado. Ambiguidades não são resolvidas automaticamente; manter auditoria e mecanismo de correção.

- **Transferência entre números WhatsApp (aprovada):** funcionário autorizado pode transferir atendimento de uma linha oficial a outra na mesma empresa (ex.: Vendas → Suporte), registrando motivo e auditoria, mantendo conversas separadas e vinculadas ao cadastro único do cliente. Não equivale a transportar a conversa nativamente no WhatsApp; início de mensagens pela nova linha depende das regras Meta, consentimento e templates quando exigidos. Transferência autônoma por IA e notificações específicas pendentes.

- **Transferência automática entre números WhatsApp por IA (aprovada):** agentes de IA podem encaminhar atendimentos autonomamente para outros números oficiais da mesma empresa, desde que destinos e condições sejam previamente autorizados pelo administrador; preservar cadastro/histórico, manter conversas separadas por linha e auditar transferências. Não permitir envios que violem regras da Meta nem perder atendimento em falhas de roteamento. Fluxo de notificação e confirmação ainda pendente.

- **Notificação automática de transferência entre números WhatsApp (aprovado):** avisar cliente quando atendimento mudar de linha, seja por funcionário ou IA, usando mensagem personalizada por empresa/setor e registrando envio e autoria; quando destino não puder iniciar mensagem conforme regras Meta, orientar cliente com link para começar conversa no novo número a partir do canal permitido. Não confundir transferência interna com transferência nativa da thread; respeitar consentimento, janela e templates. Política de falha/repetição e conclusão pendentes.

- **Transferência WhatsApp pendente com responsabilidade na origem (aprovada):** quando uma transferência entre números é solicitada por humano ou IA, o atendimento de origem permanece responsável até que o destino tenha efetivamente iniciado ou aceitado o atendimento. Aviso enviado ou link fornecido não conclui transferência. Se cliente não prossegue, destino não aceita ou operação falha, manter controle e sinalização da pendência ao responsável de origem; registrar estados e auditoria. Prazos, tentativas e escalonamentos a definir.

- **Alerta por transferência WhatsApp pendente (aprovado):** prazo de aceite configurável por empresa, com 10 minutos como valor inicial sugerido; após o prazo, alerta interno ao responsável de origem e destaque da pendência na Central de Atendimento. A origem permanece responsável; vencimento não encerra nem conclui transferência. Funcionário autorizado pode cancelar/redirecionar, com registro auditável. Recorrência e escalonamento ainda pendentes.

- **Horários por equipe e prazos de transferência WhatsApp (aprovado):** configurar expediente específico por equipe/setor, inclusive 24/7. Contar prazo de aceite/início apenas durante horários de funcionamento da equipe de destino; suspender fora do expediente e retomar no próximo período, respeitando fuso horário. Origem continua responsável enquanto pendente. Detalhamento de feriados e alterações de configuração durante transferências ainda pendente.
