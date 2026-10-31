# 04 — Empresas, usuários, equipes e auditoria

## Administração por empresa — APROVADO
- Cada empresa possui um administrador principal.
- Esse administrador convida funcionários, atribui perfis e administra acessos conforme assentos contratados.
- Pode designar administradores adicionais **da própria empresa**, sem acesso à Administração Geral da Imagine.
- Um login pode participar de várias empresas, com identidade compartilhada e isolamento dos dados/permissões por empresa.

## Perfis de acesso
- Empresas criam perfis próprios com permissão por módulo e ação.
- Exemplos de perfis sugeridos (não são uma matriz obrigatória aprovada): administrador, gestor comercial, vendedor e financeiro.
- Escopos de visibilidade para leads, contatos e oportunidades:
  1. **Próprios:** atribuídos ao usuário.
  2. **Equipe:** próprios e de integrantes de suas equipes.
  3. **Empresa:** todos os registros comerciais da empresa.
- Editar, excluir e transferir dependem de permissões próprias, além da visibilidade.

## Equipes
- Cada empresa pode criar equipes comerciais, com gestor responsável e vários integrantes.
- Um funcionário pode participar de múltiplas equipes na mesma empresa.
- Visibilidade segue seus vínculos de equipe e permissões atribuídas.

## Transferência de registros
- Leads e oportunidades transferíveis individualmente ou em lote por usuários autorizados.
- Auditoria: responsável anterior, novo responsável, executor e data/hora.
- Histórico comercial, interações, tarefas e movimentações preservados integralmente.

## Desativação e reativação
- Administrador autorizado pode desativar funcionários na empresa.
- Desativação revoga imediatamente acesso e sessões **da empresa**, preservando histórico e acesso a outras empresas.
- Leads e oportunidades podem ser redistribuídos.
- Reativação por autorizado, respeitando quantidade de usuários contratada.

## Convites
- E-mail com link seguro e validade de **7 dias**.
- Administrador define e-mail e perfil; convidado cria conta ou utiliza conta existente.
- Cancelamento/reenvio de convites pendentes.
- Bloquear excesso de assentos e duplicidade de vínculo na empresa.

## Autenticação do cliente
- MFA opcional por padrão para funcionários; cada empresa pode torná-lo obrigatório para todos.
- MFA **obrigatório para administradores da empresa**.
- Recuperação de senha por e-mail verificado com link temporário.
- Encerramento de sessões em outros dispositivos e histórico de acessos/tentativas de login.

## Auditoria comercial
- Registrar criação, edição e exclusão de leads, mudanças em oportunidades, transferências, alterações de permissão e exportações.
- Campos: usuário, data/hora, ação, registro afetado, valores antes/depois onde relevantes; proteger dados sensíveis.
- Consulta restrita a autorizados. Administradores da empresa não podem alterar/apagar registros de auditoria.
- Retenção padrão **12 meses enquanto a empresa estiver ativa**, observando retenção/exclusão legal.
- Separada da auditoria da Administração Geral (padrão 5 anos).

## Exportação de dados — APROVADO
- Exportação de **leads, contatos, empresas cadastradas, oportunidades e atividades** em **CSV e Excel (XLSX)**.
- Apenas funcionários com permissão específica podem exportar.
- Exportação respeita a visibilidade autorizada: próprios registros, registros da equipe ou todos os registros da empresa.
- Permitir exportar registros filtrados ou todos os registros autorizados.
- Registrar em auditoria o usuário, a data/hora e o tipo de exportação.
- Grandes volumes são processados em segundo plano, com link temporário e download protegido.
- **Não incluir senhas, tokens nem outras credenciais** nos arquivos.
- Observar requisitos aplicáveis de proteção e portabilidade de dados (LGPD).

## Importação de leads e contatos — APROVADO
- Importar arquivos **CSV e Excel (XLSX)**.
- Mapear colunas de origem para campos do CRM.
- Exibir pré-visualização antes de confirmar a importação.
- Identificar possíveis duplicidades por **e-mail, telefone ou identificador**.
- Permitir ignorar duplicados ou atualizar registros existentes, respeitando permissões.
- Gerar relatório com registros importados e erros.
- Processar arquivos grandes em segundo plano.
- Respeitar limites do plano contratado e registrar auditoria da operação.

## Campos personalizados — APROVADO
- Cada empresa pode criar campos personalizados para **leads, contatos e oportunidades**, isolados por empresa.
- Tipos aprovados: **texto, número, moeda, data, seleção única, múltipla seleção e verdadeiro/falso**.
- Campos podem ser utilizados em formulários, filtros, relatórios, importações e exportações, respeitando permissões.
- Detalhes como obrigatoriedade, alteração de tipo, limites por plano, validações e arquivamento/exclusão permanecem pendentes.

## Proteção e exclusão de campos personalizados — APROVADO
- Permitir **desativar ou arquivar** campos personalizados, preservando os dados existentes.
- Bloquear exclusão definitiva de campos que contenham dados até que estes sejam tratados adequadamente.
- Exclusão definitiva requer permissão específica e confirmação.
- Registrar alterações e exclusões em auditoria.
- Antes de excluir, identificar e alertar sobre dependências em filtros, relatórios e automações, evitando quebra silenciosa de funcionalidades.

## Configuração e validação de campos personalizados — APROVADO
- Configurar **obrigatoriedade** de preenchimento na criação e edição de registros.
- Definir **valor padrão** opcional.
- Aplicar **validações compatíveis com o tipo** (por exemplo, limites numéricos e datas válidas).
- Configurar opções dos campos de seleção.
- Ordenar a apresentação dos campos nos formulários.
- Proteger campos essenciais do sistema, como identificadores internos e vínculos, contra remoção ou alteração de regras fundamentais.
- Aplicar as validações no backend também para operações via importação e integrações, não apenas na interface.

## Limites de campos personalizados por plano — APROVADO
| Plano | Limite em cada módulo (leads, contatos, oportunidades) |
|---|---:|
| Essencial | 10 |
| Profissional | 25 |
| Empresarial | 50 |

- Cada módulo tem limite independente, por empresa.
- No downgrade, preservar campos existentes, mesmo acima do limite novo, mas bloquear criação de novos campos enquanto o limite estiver excedido.

## Alteração do tipo de campos personalizados — APROVADO
- Campos **sem dados** podem ter o tipo alterado por usuário com permissão específica.
- Campos **com dados** admitem somente conversões compatíveis, mediante validação e pré-visualização.
- Conversões incompatíveis são bloqueadas para evitar perda de dados.
- Todas as alterações devem ser auditadas.

## Linha do tempo de atividades comerciais — APROVADO
- Leads, contatos e oportunidades possuem linha do tempo operacional para acompanhamento comercial.
- Registrar criação do cadastro, alterações de campos, mudanças de responsável, movimentações de etapa do funil, tarefas e interações comerciais.
- Cada evento deve identificar usuário responsável, data e horário, com detalhes relevantes do evento (incluindo valores anterior/novo quando apropriado).
- A visualização respeita permissões de acesso aos registros.
- O histórico operacional é **distinto da auditoria técnica** de segurança e rastreabilidade.

## Anotações e comentários internos — APROVADO
- Usuários autorizados podem criar anotações internas em leads, contatos e oportunidades, com texto, menções a colegas (@usuário) e anexos.
- Visualização limitada a funcionários com acesso ao respectivo registro, conforme permissões.
- Menções podem gerar notificações internas.
- Edições e exclusões de anotações são registradas no histórico.
- Anotações são exibidas na linha do tempo comercial e **não são enviadas automaticamente aos clientes**.

## Tarefas e lembretes comerciais — APROVADO
- Tarefas vinculadas a leads, contatos e oportunidades, com título, descrição, responsável, prioridade e prazo.
- Status: pendente, em andamento, concluída ou cancelada.
- Lembretes e notificações de vencimento.
- Tarefas recorrentes, com periodicidade configurável (detalhes de regras ainda a especificar).
- Visualização em lista e calendário.
- Registrar criação, alterações e conclusão na linha do tempo comercial.
- Respeitar isolamento por empresa e permissões de acesso.

## Registro de atividades comerciais — APROVADO
- Registrar atividades realizadas sem exigir tarefa prévia, vinculadas a leads, contatos ou oportunidades.
- Tipos: ligação, WhatsApp, e-mail, reunião, visita e outro (atividade personalizada).
- Cada atividade pode conter data, horário, responsável, descrição e resultado.
- Exibir na linha do tempo comercial, respeitando permissões e isolamento por empresa.

## Funis de vendas (Kanban) — APROVADO
- Cada empresa pode criar e administrar múltiplos funis de vendas, isolados por tenant.
- Etapas personalizadas: criar, renomear e reordenar.
- Visualização Kanban com movimentação de oportunidades entre etapas.
- Oportunidades podem ter vendedor responsável, valor e previsão de fechamento.
- Registrar todas as mudanças de etapa no histórico comercial.
- Permissões específicas para visualização e alteração dos funis e oportunidades.
- Estados finais **Ganha** e **Perdida**, com motivos de perda configuráveis por empresa.

## Regras de movimentação de oportunidades no funil — APROVADO
- Cada empresa pode configurar, opcionalmente por funil, campos obrigatórios por etapa e permissões para movimentação de oportunidades.
- Impedir movimentações quando requisitos obrigatórios não forem atendidos.
- Permitir retorno a etapas anteriores, registrando a movimentação no histórico.
- Exigir confirmação ao marcar oportunidade como **Ganha** ou **Perdida**; ao marcar **Perdida**, exigir motivo da perda.
- Aplicar regras e validações no backend, inclusive em movimentações por integrações e automações.

## Probabilidade de fechamento e previsão ponderada — APROVADO
- Cada etapa de cada funil pode ter probabilidade estimada de fechamento configurada pela empresa.
- Permitir ajuste manual da probabilidade por oportunidade somente a usuários autorizados.
- Oportunidades **Ganhas** têm probabilidade de 100%; **Perdidas**, de 0%.
- Calcular valor ponderado = valor da oportunidade × probabilidade de fechamento / 100.
- Utilizar valores ponderados em relatórios e previsões comerciais.
- Percentuais apresentados como exemplos na discussão não são valores padrão aprovados.

## Catálogo de produtos e serviços nas oportunidades — APROVADO
- Cada empresa mantém catálogo próprio de produtos e serviços, com nome, código, descrição, preço e status.
- Cada oportunidade pode conter múltiplos itens, com quantidade e preço unitário.
- Descontos por item ou no total sujeitos a permissões.
- Valor total calculado automaticamente; valor dos itens pode alimentar o valor da oportunidade e a previsão ponderada.
- Registrar alterações de itens e valores no histórico comercial.
- Controle de estoque fora do escopo inicial.

## Propostas comerciais — APROVADO
- Gerar propostas comerciais diretamente de oportunidades, usando modelos personalizados por empresa.
- Incluir dados do cliente, produtos, serviços, valores e descontos.
- Gerar PDF para download e atribuir numeração única por empresa.
- Permitir data de validade e preservar histórico de versões/revisões.
- Status: **Rascunho, Enviada, Aceita, Recusada e Expirada**.
- Compartilhar por e-mail ou link seguro, respeitando permissões de acesso.
- Na primeira etapa, registrar aceitação no CRM sem integração de assinatura eletrônica.

## Aprovação de propostas comerciais — APROVADO
- Fluxo de aprovação **opcional e configurável por empresa**.
- Propostas dentro da política comercial podem ser enviadas diretamente por usuários autorizados.
- Descontos acima do limite permitido e alterações de preço fora da política exigem aprovação de gestor autorizado.
- Propostas pendentes de aprovação não podem ser enviadas.
- Registrar aprovação ou rejeição com responsável, data e justificativa.
- Empresa pode desativar a exigência do fluxo de aprovação.
- Regras devem ser aplicadas no backend, inclusive em envios por integrações e automações.

## Compartilhamento e acompanhamento de propostas — APROVADO
- Gerar link seguro exclusivo para visualização de cada proposta, vinculado à versão correta.
- Validade do link configurável por empresa e possibilidade de exigir código de verificação para acesso.
- Registrar visualizações e permitir notificar o vendedor quando a proposta for aberta.
- Permitir revogar/desativar o link a qualquer momento.
- A visualização **não** equivale à aceitação da proposta.
- Verificações de acesso e segurança devem ocorrer no backend.

## Aceitação e recusa de propostas comerciais — APROVADO
- Ao aceitar proposta, registrar data, responsável e versão aceita.
- Ao recusar, registrar o motivo da recusa.
- Propostas expiradas não podem receber novo aceite até que sua validade seja renovada.
- Alterações/revisões de proposta exigem nova aceitação da versão revisada.
- Aceitação e recusa atualizam automaticamente o histórico da oportunidade vinculada.
- Configuração por empresa: aceitar proposta pode marcar oportunidade como **Ganha** automaticamente ou exigir confirmação do vendedor.
- Aceite é confirmação comercial no CRM e **não equivale a assinatura eletrônica formal**.

## Motivos de perda de oportunidades — APROVADO
- Lista inicial por empresa: **Preço, Concorrência, Sem orçamento, Sem interesse, Prazo, Sem retorno e Outro**.
- Cada empresa pode personalizar seus motivos de perda.
- Ao marcar oportunidade como **Perdida**, selecionar um motivo é obrigatório; observação complementar é opcional.
- Utilizar motivos de perda em relatórios comerciais para análise das causas das perdas.

## Reabertura de oportunidades ganhas ou perdidas — APROVADO
- Usuários autorizados podem reabrir oportunidades com status **Ganha** ou **Perdida**, com justificativa obrigatória.
- Preservar encerramento anterior e histórico integral da negociação.
- Na reabertura, selecionar etapa de retorno no funil.
- Recalcular valores ponderados e probabilidade conforme etapa selecionada, respeitando regras de ajuste manual autorizado.
- Relatórios devem preservar histórico de ganhos/perdas sem contagem duplicada.

## Metas comerciais — APROVADO
- Metas de vendas individuais por vendedor, coletivas por equipe comercial e gerais por empresa.
- Períodos: mensal, trimestral e anual.
- Indicadores: valor vendido, quantidade de vendas e oportunidades ganhas.
- Acompanhar meta definida, resultado alcançado, percentual de atingimento e evolução.
- Gestores autorizados configuram metas; vendedores visualizam apenas as metas autorizadas.
- Apuração baseada em oportunidades efetivamente ganhas, sem duplicar vendas em caso de reabertura.

## Dashboard comercial — APROVADO
- Painel por empresa com indicadores atualizados automaticamente, respeitando isolamento de dados e permissões de visualização.
- Indicadores: leads totais/novos/convertidos; oportunidades abertas/ganhas/perdidas; quantidade e valor por etapa do funil; previsão de vendas total e ponderada; taxas de conversão entre etapas; metas, resultados e percentual atingido; tarefas pendentes/vencidas/concluídas; desempenho por vendedor e equipe; principais motivos de perda.
- Exibir gráficos e indicadores resumidos.
- Filtros por período, funil, vendedor e equipe, conforme autorização.

## Relatórios comerciais — APROVADO
- Relatórios iniciais: vendas por período, conversão de leads, desempenho por vendedor, desempenho por equipe, funil de vendas, motivos de perda, atividades comerciais, metas e previsão de vendas (valores previstos e ponderados).
- Filtros por período, funil, vendedor e equipe.
- Exportação nos formatos CSV, Excel (XLSX) e PDF.
- Respeitar permissões de cada usuário e isolamento por empresa.
- Valores de vendas referem-se a dados comerciais registrados no CRM, sem implicar integração contábil ou fiscal.

## Relatórios personalizados — APROVADO
- Cada empresa pode criar e salvar relatórios personalizados a partir dos módulos e campos disponíveis, inclusive campos personalizados.
- Permitir seleção e reordenação de colunas, combinação de filtros, agrupamentos por vendedor, equipe, período ou etapa, e agregações de contagem, soma e média.
- Salvar configurações para reutilização e compartilhar relatórios de modo privado ou com usuários autorizados.
- Exportar relatórios em CSV, XLSX e PDF.
- Respeitar isolamento por empresa, permissões de visualização de registros e campos e permissões de exportação; não expor dados não autorizados.

## Agendamento e envio automático de relatórios — APROVADO
- Permitir agendamento de relatórios com frequência diária, semanal ou mensal.
- Destinatários limitados a usuários autorizados da própria empresa.
- Formatos de geração: PDF, XLSX e CSV, usando filtros e configurações salvos do relatório.
- Enviar por e-mail link seguro para download, em vez de expor dados diretamente.
- Validar permissões e isolamento por empresa no momento da geração e entrega.
- Permitir pausar, editar ou cancelar agendamentos.
- Registrar histórico de execuções, entregas e falhas.
- Respeitar limites e permissões da empresa, sem compartilhamento com destinatários não autorizados.

## Automações comerciais — APROVADO
- Cada empresa pode configurar automações sem programação, baseadas em eventos e condições.
- Gatilhos iniciais: novo lead, mudança de etapa, tarefa vencida, proposta aceita ou recusada.
- Condições: responsável, etapa, valor e campos personalizados.
- Ações: criar tarefa, atribuir responsável, enviar notificação ou atualizar campo.
- Execução imediata ou após prazo configurado, processada em segundo plano.
- Histórico de execuções, resultados e erros.
- Respeitar permissões, regras de negócio, isolamento por empresa e limites do plano.
- Permitir ativar, pausar e desativar automações.
- Proteger contra loops e execuções duplicadas.

## Limites de automações por plano — APROVADO
- Limite de automações **ativas simultaneamente por empresa**: Essencial **5**, Profissional **20**, Empresarial **50**.
- Automações em rascunho não consomem esse limite e não há limite de quantidade de rascunhos definido nesta decisão.
- Em downgrade, preservar configurações e históricos, pausar automações excedentes e permitir que administrador escolha quais permanecerão ativas dentro do novo limite.

## Automações por tempo e inatividade — APROVADO
- Permitir gatilhos de inatividade configuráveis em horas ou dias para leads, oportunidades e propostas sem resposta.
- Lead sem contato: criar tarefa de acompanhamento; oportunidade parada: notificar vendedor ou gestor; proposta sem resposta: criar lembrete.
- Uma nova interação reinicia a contagem de inatividade.
- Evitar disparos duplicados ou notificações repetidas para a mesma ocorrência.
- Respeitar isolamento por empresa, permissões, regras de segurança e limite de automações ativas do plano.

## Distribuição automática de leads — APROVADO
- Cada empresa pode configurar modalidades de atribuição de novos leads: rodízio (round-robin), menor carga de leads ativos, equipe ou origem/campanha, responsável fixo e distribuição manual por gestor.
- Distribuição automática considera apenas vendedores ativos e habilitados a receber leads.
- Atribuições respeitam permissões, isolamento por empresa e são registradas no histórico comercial.

## Redistribuição automática de leads sem atendimento — APROVADO
- Recurso opcional e configurável por empresa.
- Prazo de primeiro atendimento configurável em minutos ou horas; notificar o vendedor responsável por lead ainda não atendido.
- Ao exceder o prazo, permitir redistribuição automática a outro vendedor ativo e habilitado, também em caso de indisponibilidade do responsável.
- Limite de tentativas de redistribuição configurável, evitando ciclos infinitos.
- Registrar responsáveis anteriores, datas e motivos no histórico comercial.
- Permitir exceções para bloquear a redistribuição de leads estratégicos.
- Não redistribuir leads que já receberam atendimento válido.
- Respeitar permissões e regras de distribuição previamente definidas.

## Lead Scoring — Qualificação e pontuação de leads — APROVADO
- Pontuação de leads na escala de 0 a 100, calculada inicialmente por regras configuráveis por empresa, sem exigir inteligência artificial.
- Critérios: origem (campanha ou canal), perfil (segmento, região e porte da empresa), engajamento (respostas e interações) e redução de pontos por inatividade.
- Classificação dos leads como frio, morno ou quente, com critérios personalizados por empresa.
- Permitir uso da pontuação em automações de atribuição de responsável, criação de tarefas e notificações.
- Respeitar isolamento por empresa e permissões aplicáveis.
- Limiares de classificação e pesos exatos ainda não foram definidos.

## Faixas de classificação do Lead Scoring — APROVADO
- Faixas padrão iniciais: **Frio = 0 a 39 pontos**, **Morno = 40 a 69 pontos**, **Quente = 70 a 100 pontos**.
- Cada empresa pode personalizar os limites das três faixas, sem sobreposição e cobrindo a escala 0–100.
- A classificação do lead deve ser recalculada automaticamente quando sua pontuação mudar.

## Histórico e gráfico de evolução do Lead Scoring — APROVADO
- Registrar cada alteração de pontuação do lead, com valor anterior, novo valor, regra/motivo, data e horário e origem do evento ou automação.
- Disponibilizar histórico no cadastro do lead, separado da auditoria técnica.
- Exibir gráfico da evolução da pontuação ao longo do tempo.
- Respeitar permissões de visualização do lead e isolamento por empresa.

## Identificação e mesclagem de leads duplicados — APROVADO
- Identificar possíveis duplicidades por telefone, e-mail e outros identificadores no cadastro manual, importação e integrações.
- Alertar e permitir consultar o registro existente no cadastro manual.
- Na importação, permitir ignorar ou atualizar duplicados de acordo com permissões.
- Nas integrações, evitar criar repetidamente o mesmo lead.
- Permitir mesclagem mediante autorização, com escolha do registro principal e prévia dos conflitos de dados antes da consolidação.
- Preservar atividades, histórico de responsáveis e origens dos registros, sem perda de informações comerciais.
- Respeitar isolamento entre empresas, permissões e auditoria da mesclagem.

## Formulários públicos de captação de leads — APROVADO
- Cada empresa poderá criar formulários personalizados sem programação para sites, landing pages e campanhas.
- Suportar campos padrão (nome, telefone, e-mail) e campos personalizados da empresa.
- Publicação por link público ou incorporação no site.
- Registrar origem do lead (formulário e campanha), permitindo distribuição automática a vendedor/equipe.
- Proteção antispam e limites de envio; consentimento LGPD configurável com registro da autorização.
- Integrar captação com identificação de duplicidades, Lead Scoring e automações comerciais.
- Disponibilizar métricas de visualizações, envios e taxa de conversão.
- Isolamento de formulários, submissões e leads por empresa, respeitando permissões.

## Personalização visual dos formulários de captação — APROVADO
- Cada empresa pode personalizar seus formulários sem programação, incluindo logotipo, cores de botões/textos/elementos e ordem dos campos.
- Permitir configurar campos obrigatórios, mensagem de sucesso e redirecionamento para URL após envio.
- Layout responsivo para celulares e computadores, com pré-visualização antes da publicação.
- Atualizações de formulário publicado devem preservar o mesmo link público ou código de incorporação.
- Manter isolamento por empresa e respeitar permissões de edição/publicação.

## Integração de formulários com WhatsApp — APROVADO
- Após o envio do formulário, permitir exibir botão para iniciar conversa via link oficial do WhatsApp, sem exigir inicialmente a API do WhatsApp Business.
- Mensagem inicial personalizável com referência ao cadastro; número de destino configurável por empresa ou formulário.
- Vincular ao lead o evento de intenção de contato e registrar cliques no botão para métricas, sem tratar clique como confirmação de envio ou recebimento de mensagem.
- Permitir automações relacionadas, como criar tarefa ou notificar vendedor.
- Evitar expor dados pessoais desnecessários em parâmetros da URL; respeitar permissões e isolamento por empresa.

## Integração oficial com WhatsApp Business API — APROVADO
- Integração opcional com API oficial do WhatsApp Business, condicionada à habilitação da empresa e às políticas da Meta.
- Vincular número empresarial; disponibilizar caixa de entrada no CRM para receber e responder conversas.
- Atribuir conversas a vendedores/equipes; associar mensagens ao histórico de leads/contatos.
- Suportar automações para tarefas e notificações a partir de mensagens.
- Permitir templates aprovados pela Meta, respeitando janelas de atendimento e regras aplicáveis.
- Aplicar permissões, auditoria, privacidade/consentimento pertinente e isolamento por empresa.
- Custos cobrados pela Meta separados da assinatura do CRM; modelo de repasse/faturamento ainda a definir.

## Caixa de entrada compartilhada do WhatsApp — APROVADO
- Permitir atendimento simultâneo de vários vendedores pelo mesmo número empresarial integrado à API oficial do WhatsApp Business.
- Cada conversa possui vendedor responsável, com possibilidade de transferência para outro vendedor ou equipe.
- Status da conversa: aguardando, em atendimento e resolvida.
- Notas internas visíveis apenas a colaboradores autorizados, nunca enviadas ao cliente.
- Preservar histórico de mensagens e transferências.
- Gestores podem supervisionar atendimentos para os quais tenham permissão.
- Prevenir respostas simultâneas conflitantes; aplicar permissões e isolamento de dados entre empresas.

## Distribuição automática de conversas do WhatsApp — APROVADO
- Cada empresa poderá configurar distribuição de novas conversas por rodízio entre atendentes, menor quantidade de conversas abertas ou equipe/departamento/assunto.
- Considerar horário de atendimento e disponibilidade, excluindo atendentes ausentes ou inativos.
- No retorno do cliente, priorizar o último responsável quando disponível.
- Manter conversas em fila de espera quando não houver atendente disponível.
- Registrar atribuições e transferências no histórico da conversa.
- Respeitar permissões por equipe e isolamento de dados entre empresas.

## Horários de atendimento e mensagens automáticas do WhatsApp — APROVADO
- Cada empresa configura dias e horários de atendimento por dia da semana, incluindo feriados, horários especiais e dias sem expediente.
- Permitir mensagens automáticas de saudação, ausência fora do expediente e aviso de fila de espera, personalizadas por número ou equipe.
- Quando não houver atendimento disponível, manter a conversa na fila e retomar a distribuição no próximo expediente.
- Prevenir respostas repetitivas e observar políticas, templates e janelas de envio da API oficial do WhatsApp Business da Meta.
- Aplicar permissões e isolamento por empresa.

## SLA de atendimento pelo WhatsApp — APROVADO
- Cada empresa pode configurar prazo máximo para primeira resposta e prazo específico para respostas subsequentes após nova mensagem do cliente.
- Contagem do SLA considera somente horários de atendimento configurados, com exceções/pausas parametrizáveis.
- Alertar vendedor antes do vencimento e escalar ao gestor quando o prazo for excedido.
- Painel deve identificar conversas dentro/fora do SLA; relatórios de tempo médio de resposta e percentual de cumprimento.
- Integrar à caixa de entrada compartilhada e respeitar permissões, equipes e isolamento por empresa.
- Prazos padrão, antecedência dos alertas e condições exatas de pausa ainda a definir.

## Avaliação de satisfação do atendimento WhatsApp (CSAT) — APROVADO
- Recurso opcional por empresa, para solicitar avaliação após a conclusão da conversa pelo WhatsApp.
- Escala de 1 a 5 e comentário opcional do cliente.
- Envio automático ou manual conforme configuração da empresa e regras da API oficial da Meta.
- Vincular avaliação à conversa e ao atendente responsável.
- Relatórios com médias de satisfação por vendedor e equipe; alertar gestor sobre avaliações baixas, com limiar configurável.
- Respeitar consentimento aplicável, privacidade, permissões, isolamento por empresa e regras de envio do WhatsApp.

## Respostas rápidas e biblioteca de mensagens do WhatsApp — APROVADO
- Disponibilizar respostas rápidas reutilizáveis durante conversas na caixa de entrada compartilhada.
- Permitir atalhos de inserção (ex.: `/precos`) e variáveis personalizáveis como nome do cliente, empresa e vendedor.
- Biblioteca de modelos compartilhados por equipe ou empresa; criação/edição de modelos compartilhados restrita a usuários autorizados.
- Pré-visualização e possibilidade de personalização antes do envio; registrar mensagem efetivamente enviada no histórico da conversa.
- Distinguir respostas livres dentro da janela permitida de atendimento dos templates oficiais sujeitos a aprovação e políticas da Meta.
- Aplicar permissões e isolamento por empresa.

## Etiquetas e categorização de conversas do WhatsApp — APROVADO
- Cada empresa poderá criar etiquetas personalizadas com nome e cor para categorizar conversas do WhatsApp.
- Uma conversa poderá ter múltiplas etiquetas, aplicadas manualmente ou por regras de automação.
- Permitir filtrar conversas por etiqueta e gerar relatórios de quantidade e distribuição por categoria.
- Controlar por permissão quem pode criar, editar ou excluir etiquetas.
- Registrar no histórico das conversas a aplicação, remoção e alterações de etiquetas.
- Garantir isolamento das etiquetas e conversas entre empresas.

## Anexos e arquivos nas conversas do WhatsApp — APROVADO
- Permitir enviar, receber e visualizar imagens, documentos (incluindo PDF) e vídeos compatíveis com a API oficial do WhatsApp Business; receber e reproduzir mensagens de áudio/voz.
- Associar anexos ao histórico da conversa e ao contato ou lead relacionado.
- Disponibilizar busca/filtros por tipo de arquivo e data.
- Validar formatos, tamanho, segurança dos arquivos e permissões de acesso, respeitando restrições vigentes da API da Meta.
- Contabilizar armazenamento dos anexos na franquia do plano contratado, com isolamento por empresa.

## Encerramento e reabertura de conversas do WhatsApp — APROVADO
- Atendente autorizado pode encerrar uma conversa, marcando-a como resolvida e selecionando motivo de encerramento configurável por empresa.
- Nova mensagem do cliente reabre automaticamente a conversa; priorizar o último atendente responsável, se disponível, respeitando regras de distribuição.
- Preservar integralmente mensagens, histórico de atendimentos, transferências e encerramentos anteriores.
- Na reabertura, iniciar nova contagem de SLA de resposta conforme horário e regras configurados.
- Permitir pesquisa CSAT após cada atendimento concluído, observando regras de envio da Meta.
- Relatórios devem medir quantidades de conversas resolvidas e reabertas.
- Respeitar permissões e isolamento por empresa.

## Chatbot de atendimento automático no WhatsApp — APROVADO
- Criar fluxos visuais de atendimento por regras e menus, sem programação e sem dependência inicial de inteligência artificial.
- Suportar saudação automática, menu de assuntos/departamentos, perguntas para coletar nome, interesse e informações básicas, etapas e condições configuráveis.
- Encaminhar a conversa para vendedor ou equipe conforme escolhas e regras de distribuição.
- Respeitar horário de expediente e disponibilidade dos atendentes.
- Registrar respostas e escolhas no histórico do CRM.
- Permitir intervenção humana e tomada da conversa a qualquer momento por atendente autorizado.
- Prevenir loops e cumprir políticas, janelas e restrições da API oficial do WhatsApp Business da Meta.
- Integração com IA poderá ser oferecida posteriormente como recurso opcional, sem fazer parte da obrigatoriedade do chatbot inicial.

## Transferência do chatbot para atendimento humano — APROVADO
- Disponibilizar ao cliente opção de falar com atendente humano.
- Transferir após tentativas configuradas de compreensão sem resposta válida ou imediatamente em assuntos prioritários para equipe especializada.
- Entregar ao atendente o contexto/resumo das respostas e informações coletadas pelo chatbot.
- Pausar respostas automáticas do bot após a transferência para evitar conflito com atendimento humano; permitir retorno ao chatbot por atendente autorizado.
- Quando não houver atendente disponível, manter conversa em fila de espera.
- Registrar motivo, data/hora e responsável pela transferência no histórico da conversa.
- Respeitar distribuição, disponibilidade, permissões, SLA, isolamento por empresa e políticas da Meta.

## Editor visual de fluxos do chatbot WhatsApp — APROVADO
- Cada empresa poderá montar fluxos de atendimento por editor visual sem programação, conectando blocos de ações e decisões.
- Blocos aprovados: mensagem (texto/saudação), pergunta (coletar resposta), menu (opções), condição (ramificação por resposta), espera (prazo configurável), ação no CRM (criar lead, atualizar campo ou adicionar etiqueta), transferência (vendedor/equipe) e encerramento do fluxo.
- Suportar rascunhos, testes antes da publicação e versionamento para evitar alterações inesperadas em fluxos ativos.
- Aplicar permissões de edição/publicação, isolamento por empresa e regras de envio da Meta.

## Gatilhos de início dos fluxos do chatbot WhatsApp — APROVADO
- Gatilhos configuráveis por empresa: primeira mensagem do cliente; palavra-chave; fora do expediente; origem do lead (campanha/formulário); conversa reaberta; e início manual por atendente autorizado.
- Permitir definir prioridade entre gatilhos quando múltiplas condições coincidirem, selecionando o fluxo apropriado.
- Não reiniciar ou disparar chatbot durante atendimento humano ativo.
- Considerar histórico da conversa, permissões, isolamento por empresa e políticas da API oficial da Meta.

## Tratamento de erros e inatividade do chatbot WhatsApp — APROVADO
- Respostas inválidas: informar o erro e repetir a pergunta; após número configurável de tentativas, encaminhar para atendimento humano.
- Ausência de resposta: enviar lembrete após prazo configurável, respeitando as janelas e políticas de mensagens da Meta.
- Inatividade prolongada: pausar ou encerrar o fluxo conforme configuração por empresa.
- Retorno do cliente: retomar o fluxo do ponto anterior ou reiniciar, conforme configuração.
- Falha técnica: registrar erro e notificar responsável autorizado.
- Prevenir mensagens repetidas, reentradas e loops infinitos; registrar tentativas, pausas e encerramentos no histórico.
- Respeitar isolamento por empresa, permissões e regras de transferência ao atendimento humano.

## Métricas e relatórios do chatbot WhatsApp — APROVADO
- Painel por empresa com total de conversas iniciadas pelo bot, percentual de fluxos concluídos, quantidade e percentual de transferências para atendimento humano, abandono e identificação de etapas críticas de desistência.
- Exibir duração média dos fluxos, respostas inválidas, falhas técnicas e conversão em leads/oportunidades gerados pelo chatbot.
- Filtros por período, fluxo, número de WhatsApp e equipe.
- Exportar relatórios conforme permissões e regras de exportação já definidas.
- Respeitar isolamento entre empresas e visibilidade autorizada de dados comerciais.

## Limites de fluxos ativos do chatbot por plano — APROVADO
- Essencial: 3 fluxos ativos; Profissional: 10; Empresarial: 30, por empresa.
- Rascunhos não consomem limite; fluxos publicados e ativos contam.
- Upgrade disponibiliza a capacidade adicional conforme plano.
- Downgrade preserva configurações e fluxos, mas pausa os excedentes; administrador da empresa escolhe quais permanecem ativos.
- Limite de fluxos do chatbot é independente do limite de automações comerciais.

## Versionamento e publicação dos fluxos do chatbot — APROVADO
- Permitir editar rascunhos sem afetar a versão publicada de cada fluxo.
- Validar o fluxo antes de publicar e ativar uma nova versão.
- Conversas já iniciadas continuam na versão com a qual começaram; novas conversas usam a versão publicada mais recente.
- Manter histórico de versões com autor, data e alterações.
- Permitir reverter para uma versão anterior, preservando o histórico de versões e execuções.
- Desativar um fluxo impede novos inícios, preservando histórico das conversas.
- Publicação e reversão restritas a usuários autorizados, com isolamento por empresa.

## Testes e simulação dos fluxos do chatbot — APROVADO
- Disponibilizar simulador de conversas no editor visual, sem enviar mensagens reais pelo WhatsApp.
- Testar menus, perguntas, condições e caminhos do fluxo com variáveis e dados fictícios de clientes.
- Validar estrutura, incluindo blocos desconectados ou inválidos, e identificar a etapa exata das falhas.
- Simular ações no CRM sem criar leads, alterar registros reais ou executar automações externas.
- Bloquear publicação de versões que apresentem erros críticos de validação.
- Registrar histórico e resultado dos testes realizados, respeitando permissões e isolamento por empresa.

## Biblioteca de modelos prontos de chatbot WhatsApp — APROVADO
- Disponibilizar modelos iniciais de fluxos: atendimento inicial (saudação/encaminhamento), captação de leads, qualificação comercial, solicitação de orçamento, suporte, fora do expediente e pesquisa de satisfação.
- Cada empresa poderá copiar um modelo da biblioteca central e personalizar sua cópia no editor visual, sem alterar o modelo original.
- A administração do CRM Imagine poderá atualizar os modelos da biblioteca central sem sobrescrever fluxos já copiados ou personalizados pelos clientes.
- Respeitar isolamento por empresa, permissões e regras de publicação/versionamento e testes já aprovadas.

## Importação e exportação de fluxos do chatbot WhatsApp — APROVADO
- Exportar a configuração de fluxos em arquivos JSON, sem incluir senhas, tokens, segredos ou dados pessoais dos clientes.
- Importar arquivos JSON no editor visual, com validação de estrutura, blocos, compatibilidade e dependências.
- Permitir duplicar um fluxo sem alterar o original.
- Ao importar, solicitar o mapeamento ou reassociação de equipes, campos personalizados e etiquetas que não correspondam aos da empresa de destino.
- Toda importação inicia como rascunho, exigindo validação e publicação autorizada para entrar em operação.
- Registrar histórico de importações e exportações com usuário e data, respeitando permissões e isolamento entre empresas.
- Aplicar limites de fluxos ativos do plano somente na ativação/publicação, conforme regras aprovadas.

## Integração do chatbot WhatsApp com agendamentos — APROVADO
- Chatbot permite ao cliente solicitar agendamento informando serviço ou assunto e consultar horários disponíveis em agenda conectada.
- Registrar agendamento com data, horário e responsável, vinculando-o ao lead ou contato do CRM.
- Enviar confirmação pelo WhatsApp e permitir reagendamento ou cancelamento conforme regras configuradas pela empresa.
- Enviar lembretes antes do atendimento, respeitando janelas, templates e políticas de mensagens da Meta.
- Impedir reservas duplicadas e conflitos de horário, validando disponibilidade novamente na confirmação.
- Preservar histórico de agendamentos, alterações, confirmações e cancelamentos, com isolamento entre empresas e permissões adequadas.

## Agenda integrada do CRM Imagine — APROVADO
- Disponibilizar agenda interna por empresa com visualizações diária, semanal e mensal.
- Permitir criar, editar, reagendar e cancelar agendamentos, vinculando funcionários, equipes, leads, contatos e convidados conforme permissões.
- Configurar disponibilidade por responsável, horários de atendimento, folgas e bloqueios.
- Validar conflitos e impedir agendamentos simultâneos incompatíveis, inclusive quando originados pelo chatbot do WhatsApp.
- Compartilhar a mesma disponibilidade e os mesmos agendamentos entre chatbot e interface do CRM.
- Enviar notificações internas e lembretes aos participantes, respeitando regras e políticas do canal utilizado.
- Registrar histórico de alterações e cancelamentos.
- Agenda independente das tarefas comerciais, mas ambas podem aparecer em visualização de calendário; respeitar permissões e isolamento entre empresas.

## Integração da agenda com Google Calendar e Microsoft Outlook — APROVADO
- Funcionários podem conectar sua conta Google Calendar ou Microsoft 365/Outlook à agenda interna do CRM, mediante autorização adequada.
- Sincronização bidirecional dos compromissos pertinentes entre CRM e calendário conectado, com tratamento de atualizações e cancelamentos.
- Considerar eventos externos ocupados no cálculo de disponibilidade e prevenção de conflitos, inclusive para agendamentos originados no chatbot.
- Preservar privacidade: compromissos pessoais externos devem ser mostrados aos demais apenas como “Ocupado”, sem título, participantes ou detalhes.
- Permitir ao funcionário revogar/desconectar a integração.
- Detectar e notificar falhas de sincronização, evitando apresentar como garantida uma disponibilidade que não tenha sido validada.
- Respeitar permissões individuais, isolamento por empresa e proteção de credenciais de integração.

## Página pública de agendamentos — APROVADO
- Cada empresa poderá publicar link exclusivo de agendamento, sem exigir login no CRM ou interação com chatbot.
- Personalização com logotipo, cores e informações da empresa; escolha do serviço e, quando permitido, do profissional responsável.
- Exibir somente horários disponíveis conforme agenda interna e compromissos sincronizados com Google Calendar/Outlook.
- Coletar nome, telefone, e-mail e campos adicionais configuráveis, respeitando LGPD e permissões.
- Confirmar agendamento por e-mail ou WhatsApp conforme integração disponível e políticas da Meta.
- Oferecer link seguro para reagendar/cancelar conforme regras da empresa.
- Criar ou vincular automaticamente lead/contato, com detecção de duplicados e histórico.
- Aplicar proteção contra spam, reservas duplicadas e conflitos, validando disponibilidade na confirmação.

## Configuração de serviços e regras de agendamento — APROVADO
- Cada empresa pode cadastrar serviços com nome e descrição, duração do atendimento e intervalo entre atendimentos.
- Configurar antecedência mínima e máxima para novas reservas, capacidade de participantes e profissionais habilitados para cada serviço.
- Definir modalidade presencial, online ou telefone e prazo mínimo para cancelamento.
- Configurar visibilidade pública ou somente interna por serviço.
- Aplicar as mesmas regras à agenda interna, página pública e chatbot WhatsApp, conforme permissões e validação de disponibilidade/conflitos.
- Respeitar isolamento entre empresas, histórico e permissões de configuração.

## Confirmação de presença e controle de faltas — APROVADO
- Permitir confirmação antecipada pelo WhatsApp ou link seguro.
- Status de agendamento: agendado, confirmado, realizado, não compareceu e cancelado.
- Funcionário autorizado registra check-in/chegada do cliente.
- Registrar falta automaticamente conforme regras de prazo ou manualmente por usuário autorizado, evitando marcar ausência antes de encerrada a janela de atendimento.
- Oferecer reagendamento após falta, consultando disponibilidade e regras do serviço.
- Manter histórico de confirmações, presenças, faltas e mudanças de status.
- Relatórios de comparecimento, cancelamentos e faltas por serviço e profissional.
- Permitir automações de tarefas e notificações baseadas nos status, respeitando permissões, privacidade e políticas dos canais.
- Integrar com agenda interna, chatbot e página pública, com isolamento entre empresas.

## Integração com videoconferências — APROVADO
- Integrar a agenda do CRM com Google Meet, Microsoft Teams e Zoom, mediante conexão e autorização das respectivas contas.
- Gerar links de reuniões online ao criar agendamentos, quando a plataforma conectada permitir.
- Enviar convites com link, data, horário e informações pertinentes aos participantes.
- Ao reagendar, atualizar eventos e convites; ao cancelar, cancelar evento remoto quando suportado e notificar participantes.
- Registrar link da reunião e histórico de alterações no CRM, respeitando visibilidade e permissões.
- Proteger credenciais/tokens de integração e aplicar isolamento entre empresas.
- Disponibilidade de recursos condicionada às APIs, permissões e políticas de cada plataforma; tratar e notificar falhas de integração.

## Agendamentos recorrentes — APROVADO
- Permitir agendamentos recorrentes com periodicidade diária, semanal, quinzenal ou mensal.
- Configurar encerramento por data final ou quantidade de ocorrências.
- Verificar disponibilidade e conflitos individualmente para cada ocorrência antes da confirmação, incluindo bloqueios e calendários conectados.
- Permitir editar ou cancelar uma ocorrência isolada ou a série inteira, preservando exceções e histórico.
- Gerar notificações e lembretes por ocorrência, respeitando canais e políticas aplicáveis.
- Sincronizar séries e alterações com Google Calendar e Microsoft Outlook quando suportado pelas integrações.
- Registrar alterações, cancelamentos e exceções no histórico; respeitar permissões, regras do serviço e isolamento entre empresas.

## Lista de espera para agendamentos — APROVADO
- Permitir registrar interesse de clientes em serviços sem horários disponíveis, com preferências de dias, horários e profissionais.
- Detectar cancelamentos e novas vagas compatíveis com as preferências e regras do serviço.
- Notificar clientes por WhatsApp ou e-mail, observando integrações disponíveis e políticas da Meta.
- Configurar prazo de resposta para cada oferta de vaga.
- Priorizar por ordem de entrada, admitindo exceções por usuários autorizados com registro de justificativa e histórico.
- Confirmar a reserva apenas após aceite dentro do prazo, revalidando disponibilidade de forma transacional para impedir dupla confirmação.
- Registrar ofertas, aceites, recusas e expiração no histórico.
- Integrar com agenda interna, página pública e chatbot WhatsApp, respeitando permissões e isolamento entre empresas.

## Gerenciamento de salas e recursos da agenda — APROVADO
- Cadastrar salas, equipamentos e outros recursos físicos por empresa.
- Definir capacidade de pessoas por sala, horários de disponibilidade e bloqueios de uso.
- Associar recursos aos serviços e reservar simultaneamente profissional e recurso quando necessário.
- Validar disponibilidade conjunta e impedir dupla reserva do mesmo recurso, inclusive via chatbot, página pública e agenda interna.
- Permitir bloqueios temporários por manutenção ou indisponibilidade.
- Oferecer visualização de calendário por sala ou equipamento.
- Registrar histórico de reservas, alterações e bloqueios, respeitando permissões e isolamento por empresa.

## Agendamentos em grupo — APROVADO
- Serviços e sessões podem ser individuais ou coletivos, com capacidade máxima configurável de participantes.
- Uma mesma sessão pode receber inscrições de vários clientes até a capacidade definida; mostrar quantidade de vagas restantes.
- Ao esgotar vagas, permitir lista de espera integrada às regras já aprovadas.
- Registrar confirmação e presença individualmente por participante.
- Cancelamento individual libera somente a vaga do participante, sem cancelar a sessão dos demais.
- Manter histórico de inscrições, confirmações, alterações e cancelamentos e relatórios de ocupação, presença e cancelamentos.
- Validar conflitos de salas, equipamentos e profissionais no nível da sessão, sem tratar múltiplos participantes da mesma sessão como reservas conflitantes.
- Aplicar à agenda interna, chatbot e página pública, respeitando permissões e isolamento por empresa.

## Pagamentos vinculados a agendamentos — REJEITADO
- Decisão explícita do usuário: não incluir cobrança, sinal, checkout, Pix ou cartão para pagamento de serviços/agendamentos dos clientes das empresas no módulo de agenda.
- Manter agendamentos, confirmações, reagendamentos, cancelamentos e presença sem processamento de pagamento pelo CRM.
- Esta decisão não altera o faturamento e cobrança das assinaturas do SaaS CRM Imagine.

## Personalização das notificações de agendamento — APROVADO
- Permitir a cada empresa configurar mensagens automáticas de confirmação após agendar, lembretes com antecedência configurável (por exemplo, 24h ou 2h), reagendamento e cancelamento.
- Disponibilizar variáveis de personalização como nome do cliente, serviço, data, horário e profissional responsável.
- Suportar envio por e-mail e, quando a integração estiver ativa, WhatsApp Business API oficial.
- Permitir ativar/desativar cada tipo de notificação e configurar os prazos de lembrete.
- Registrar histórico de envios, tentativas e falhas, com acesso conforme permissões e isolamento entre empresas.
- Respeitar as janelas de atendimento, consentimentos aplicáveis, templates aprovados e políticas de mensagens da Meta; prevenir duplicidade de envios.

## Suporte a fusos horários na agenda — APROVADO
- Configurar fuso horário principal por empresa e, opcionalmente, fuso individual por funcionário.
- Exibir horários ao cliente conforme fuso identificado ou explicitamente selecionado, com indicação clara do fuso em páginas públicas, confirmações e lembretes.
- Armazenar instantes de agendamento de forma padronizada em UTC, preservando identificação de fuso IANA e horário local relevante para recorrências.
- Converter corretamente datas/horários nas integrações Google Calendar e Microsoft Outlook e nos relatórios conforme fuso selecionado.
- Considerar regras oficiais de horário de verão e transições de fuso, tratando horários locais inexistentes ou ambíguos.
- Validar conflitos de disponibilidade entre participantes, salas e recursos independentemente do fuso de exibição.
- Respeitar permissões e isolamento entre empresas.

## Relatórios e indicadores da agenda — APROVADO
- Painel com total de agendamentos por período, quantidade e taxa de comparecimento, cancelamentos e faltas.
- Indicadores de ocupação e utilização de profissionais e salas, serviços mais agendados, origem dos agendamentos (chatbot, página pública ou funcionários) e dias/horários de maior procura.
- Filtros por período, serviço, profissional, equipe e unidade, quando aplicável.
- Exportação autorizada em CSV, XLSX e PDF.
- Respeitar isolamento de dados por empresa, permissões dos usuários e regras de fuso horário da agenda.

## Múltiplas unidades de atendimento — APROVADO
- Uma empresa contratante pode cadastrar múltiplas unidades/filiais de atendimento, com nome, endereço e contato próprios, compartilhando a mesma assinatura do CRM.
- Cada unidade pode configurar expediente, feriados e disponibilidade próprios.
- Funcionários podem estar vinculados a uma ou mais unidades, com permissões e visibilidade de dados por unidade.
- Configurar serviços oferecidos em cada unidade, bem como salas, equipamentos e demais recursos físicos com disponibilidade específica.
- Na página pública, chatbot e agenda interna, permitir selecionar unidade antes de consultar horários e realizar agendamento, quando houver mais de uma opção.
- Relatórios devem permitir filtros e comparação de desempenho entre unidades, respeitando autorizações e isolamento entre empresas contratantes.
- Validar conflitos de profissionais e recursos compartilhados entre unidades para impedir reservas simultâneas incompatíveis.

## Limites de unidades de atendimento por plano — APROVADO
- Limites de unidades incluídas por empresa contratante: Essencial 1, Profissional 3, Empresarial 10.
- Permitir contratação de unidades adicionais mediante cobrança extra, sem exigir upgrade de plano; preço unitário ainda pendente de definição.
- Upgrade amplia quantidade de unidades incluídas no novo plano.
- Em downgrade, preservar unidades e suas configurações, mas tornar inativas as unidades excedentes até regularização, sem apagar histórico ou agendamentos anteriores.
- As unidades adicionais contratadas compõem a capacidade disponível, respeitando faturamento e permissões da empresa.

## Transferência de agendamentos entre unidades — APROVADO
- Usuários autorizados podem transferir agendamentos entre unidades da mesma empresa contratante.
- Antes de confirmar, validar disponibilidade do profissional, sala, equipamentos e horário na unidade de destino, bem como regras do serviço.
- Exigir confirmação explícita de usuário com permissão para a transferência.
- Notificar cliente sobre alteração de unidade/endereço e de horário, quando aplicável, pelos canais configurados e permitidos.
- Registrar no histórico unidade de origem, unidade de destino, responsável pela transferência, data e alterações realizadas.
- Impedir conflitos e duplicidades, efetuando liberação da reserva anterior e confirmação da nova de maneira consistente.
- Disponibilizar identificação e filtros de agendamentos transferidos em relatórios, respeitando permissões por unidade e isolamento por empresa.

## Bloqueios e indisponibilidades da agenda — APROVADO
- Funcionários autorizados podem bloquear a própria agenda para férias, almoço, consultas e compromissos; gestores autorizados gerenciam bloqueios das equipes.
- Permitir bloqueios por unidade (feriados, manutenção, fechamento) e por recurso (salas, equipamentos).
- Bloqueios podem ser únicos ou recorrentes e devem registrar motivo/justificativa.
- Antes de aplicar bloqueios que atinjam agendamentos existentes, alertar sobre conflitos para tratamento por usuário autorizado, sem cancelar automaticamente reservas já confirmadas.
- Registrar criação, alteração e remoção de bloqueios no histórico, com autor e data.
- Aplicar indisponibilidades à agenda interna, página pública, chatbot e cálculo de disponibilidade das integrações de calendário, impedindo novas reservas conflitantes.
- Respeitar permissões por funcionário/equipe/unidade e isolamento entre empresas.
