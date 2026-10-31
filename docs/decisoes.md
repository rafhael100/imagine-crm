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
