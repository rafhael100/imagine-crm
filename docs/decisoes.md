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
