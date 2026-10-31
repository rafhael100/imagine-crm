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

## Permissões específicas da agenda — APROVADO
- Permissões granulares para visualizar agenda própria, de equipe, de unidade ou de toda a empresa, conforme perfil autorizado.
- Separar permissões para criar, editar e cancelar agendamentos próprios ou de terceiros.
- Controlar transferência de agendamentos entre profissionais ou unidades e gerenciamento de bloqueios/indisponibilidades.
- Restringir configuração de serviços, horários, recursos e acesso a relatórios conforme perfil.
- Registrar em auditoria alterações importantes, com responsável, data/hora e detalhes pertinentes.
- Aplicar verificações no backend e integrações, respeitando escopos de funcionário/equipe/unidade e isolamento entre empresas.

## Auditoria e histórico da agenda — APROVADO
- Registrar criação de agendamento com responsável, data/hora e canal de origem.
- Registrar alterações com valores anteriores e novos; reagendamentos com data/hora anteriores e novos; cancelamentos com responsável e motivo.
- Registrar transferências com unidade e/ou profissional anterior e novo, confirmações com origem e horário, check-in e faltas com responsável.
- Identificar alterações originadas por integrações Google Calendar ou Microsoft Outlook.
- Histórico de auditoria protegido contra edição e exclusão por usuários da empresa, com acesso conforme permissões.
- Reter registros por 12 meses durante a assinatura ativa, observadas obrigações legais de retenção e políticas de proteção de dados; aplicar isolamento por empresa.

## Portal do cliente para agendamentos — APROVADO
- Disponibilizar portal responsivo, com identidade visual da empresa, acessível por link seguro enviado por e-mail ou WhatsApp, sem exigir senha específica do cliente.
- Permitir consultar próximos agendamentos e histórico de atendimentos anteriores do próprio cliente.
- Permitir confirmar presença, reagendar e cancelar atendimentos dentro dos prazos e regras configurados para cada serviço, validando disponibilidade e conflitos.
- Disponibilizar links de reuniões online Google Meet, Microsoft Teams ou Zoom quando vinculados ao agendamento e autorizados.
- Proteger o acesso com links individuais de escopo restrito, expiração e possibilidade de revogação; não expor agendamentos de outros clientes ou empresas.
- Registrar ações do portal no histórico e respeitar políticas de privacidade e segurança.

## Pesquisa de satisfação após agendamentos — APROVADO
- Enviar pesquisa automaticamente após atendimento marcado como realizado, com prazo de envio configurável pela empresa.
- Envio por e-mail ou WhatsApp quando integrado, observando consentimentos e políticas da Meta.
- Coletar nota de 1 a 5 e comentário opcional; permitir personalização da mensagem.
- Vincular avaliação ao agendamento, serviço, profissional e unidade, respeitando escopo de acesso.
- Alertar gestores autorizados sobre avaliações baixas conforme limiar configurável.
- Oferecer relatórios de notas médias por serviço, profissional e unidade, respeitando permissões e privacidade.
- Esta pesquisa é independente da pesquisa CSAT das conversas de atendimento no WhatsApp já aprovada.

## Formulários prévios aos atendimentos — APROVADO
- Cada empresa pode criar formulários específicos por serviço para coletar informações antes do atendimento.
- Campos suportados: texto, seleção, data, número e anexos, com obrigatoriedade configurável e validação no backend.
- Enviar link individual seguro por e-mail ou WhatsApp integrado; definir prazo-limite de resposta por formulário/serviço.
- Vincular respostas ao agendamento e ao contato correspondente no CRM.
- Notificar responsável autorizado após conclusão do formulário.
- Restringir visualização e acesso às respostas por permissões e isolamento por empresa, protegendo dados pessoais e anexos.
- Registrar envio e preenchimento com data/hora no histórico; interface responsiva.

## Documentos e anexos dos agendamentos — APROVADO
- Permitir envio de arquivos pelos clientes no portal do cliente ou formulário prévio e por funcionários internos autorizados.
- Suportar PDF, imagens e formatos comuns de documentos, com prévia apenas quando tecnicamente compatível.
- Vincular arquivos ao agendamento e ao contato correspondente, com isolamento por empresa.
- Validar tamanho, tipo real de arquivo e conteúdo, com proteção contra arquivos maliciosos; downloads com controle de acesso.
- Permissões granulares para visualizar, enviar, substituir e excluir anexos; não disponibilizar arquivos a usuários não autorizados.
- Contabilizar armazenamento nos limites do plano contratado.
- Registrar envio, substituição e exclusão no histórico, com responsável e data/hora; aplicar proteção de dados pessoais e LGPD.

## Lista de presença e controle de comparecimento — APROVADO
- Permitir check-in manual por funcionário autorizado e check-in pelo próprio cliente via portal seguro, mediante validação do agendamento e identidade/escopo do acesso.
- Permitir check-out para registrar término do atendimento, com data e horário.
- Para sessões coletivas, registrar presença individual de cada participante, vinculada à mesma sessão, sem duplicar o agendamento coletivo.
- Registrar atrasos pela comparação entre horário previsto e horário efetivo de chegada; identificar ausências conforme regras de encerramento/no-show já aprovadas.
- Manter histórico de horários, responsáveis e alterações de presença, respeitando permissões e isolamento por empresa.
- Disponibilizar relatórios de presença, atrasos e faltas por período, serviço, profissional e unidade, conforme permissões.
- Integrar com os estados de confirmação e no-show existentes, evitando registros conflitantes ou duplicados.

## Painel de capacidade e ocupação da agenda — APROVADO
- Exibir percentual de horários disponíveis e reservados, calculado a partir da capacidade agendável e dos horários efetivamente reservados, considerando expediente e bloqueios.
- Mostrar carga de atendimentos por profissional, taxa de utilização de salas e disponibilidade de equipamentos/recursos compartilhados.
- Alertar sobre horários e recursos próximos do limite de capacidade, sem permitir sobreposição de reservas.
- Oferecer comparativos por dia, semana, mês e unidade, com filtros de serviço, profissional, sala e período.
- Calcular automaticamente os indicadores com base em agendamentos, horários e bloqueios já cadastrados, sem lançamentos manuais adicionais.
- Restringir acesso ao painel e seus dados conforme permissões por funcionário, equipe, unidade e empresa; garantir isolamento entre empresas.

## Agendamentos ilimitados em todos os planos — APROVADO
- Agendamentos mensais ilimitados nos planos Essencial, Profissional e Empresarial, sem cobrança adicional por quantidade de reservas.
- Manter verificações de disponibilidade, conflitos, permissões e capacidade em cada agendamento.
- Aplicar proteção técnica contra abuso e excesso de requisições, sem converter controles de segurança em limite comercial.

## Confirmação automática de agendamentos — APROVADO
- Permitir confirmação pelo cliente através de link individual seguro enviado por e-mail ou WhatsApp oficial integrado, observando políticas do canal.
- Antecedência de envio configurável por empresa/serviço, incluindo opções como 24h ou 48h, sem fixar um padrão obrigatório.
- Reenviar lembrete configurável quando o cliente não responder, com prevenção de disparos repetitivos.
- Manter estado de confirmação pendente, confirmado ou não confirmado, separado do estado operacional do agendamento (realizado, cancelado, falta etc.).
- Alertar equipe autorizada sobre ausência de resposta, respeitando escopos de acesso.
- Cancelamento automático por falta de confirmação desativado por padrão; ausência de resposta não libera a vaga. Eventual regra explícita de cancelamento configurada pela empresa deverá observar prazos, notificações, auditoria e prevenção de conflitos.
- Registrar envios, tentativas, respostas, canal e horários no histórico, com isolamento por empresa.

## Política de cancelamento por falta de confirmação — APROVADO
- Comportamento padrão: agendamentos sem confirmação permanecem ativos e a vaga não é liberada.
- Cancelamento automático por falta de confirmação é opcional, inicialmente desativado, e só pode ser habilitado expressamente por administrador autorizado da empresa.
- A empresa pode configurar a antecedência mínima para efetivar cancelamentos e deve avisar o cliente antes do cancelamento, conforme canais habilitados e políticas aplicáveis.
- Permitir exceções para serviços e clientes específicos protegidos da regra.
- Liberar a vaga somente depois de o cancelamento ser efetivado de forma consistente, sem criar conflitos ou reservas duplicadas.
- Integrar vagas liberadas à lista de espera aprovada, observando suas regras de oferta e confirmação.
- Registrar no histórico motivo, regra aplicada, responsável pela configuração, data/hora de execução e notificações relevantes; respeitar permissões e isolamento por empresa.

## Regras para reagendamento de clientes — APROVADO
- Permitir reagendamento pelo portal seguro do cliente ou por funcionário autorizado, respeitando escopo de permissões.
- Antecedência mínima configurável por serviço; após o prazo, exigir autorização de funcionário com permissão apropriada.
- Quantidade de reagendamentos por atendimento configurável, com padrão sem limite de quantidade, sujeito às demais regras de disponibilidade e antecedência.
- Antes de confirmar, validar disponibilidade do profissional, sala, recursos e horário de destino, impedindo conflitos ou reservas duplicadas.
- Notificar cliente após alteração pelos canais habilitados e registrar histórico completo de datas e horários anteriores e novos, autor/canal e data da mudança.
- Sincronizar alterações com agenda interna, chatbot e integrações externas conectadas (Google Calendar, Microsoft Outlook), observando erros e políticas de sincronização.
- Respeitar isolamento por empresa e regras específicas de serviço/unidade.

## Agendamentos para clientes recorrentes — APROVADO
- Reconhecer contatos já cadastrados por telefone ou e-mail, com verificação de identidade antes de revelar dados pessoais ou histórico.
- Reutilizar informações cadastrais existentes em novas reservas, evitando exigir preenchimento repetitivo e prevenindo contatos duplicados.
- Permitir ao cliente autenticado consultar seus atendimentos anteriores e sugerir serviço, profissional e unidade habituais, sem impor essas escolhas.
- Solicitar apenas informações novas ou desatualizadas em formulários prévios, sem dispensar campos necessários ao serviço ou consentimentos aplicáveis.
- Vincular novo agendamento ao contato existente no CRM e aplicar o mesmo comportamento em portal, página pública e chatbot quando houver identificação segura.
- Respeitar permissões, isolamento por empresa e proteção de dados pessoais; identificação apenas por telefone/e-mail não concede acesso a informações privadas sem verificação adicional.

## Agendamentos para dependentes e terceiros — APROVADO
- Permitir que o titular/responsável faça reservas para dependentes, familiares ou terceiros sob sua responsabilidade, conforme regras do serviço.
- Cadastrar o atendido como pessoa vinculada ao titular, coletando apenas os dados necessários ao atendimento.
- Permitir selecionar a pessoa que receberá o serviço ao agendar, mantendo histórico de atendimentos individualizado por atendido, sem misturar históricos.
- Enviar notificações ao responsável autorizado, conforme vínculo e permissões, evitando exposição indevida de dados pessoais.
- Configurar por serviço se são aceitos agendamentos para terceiros; manter opção desativada onde não permitida.
- Vincular titular e atendido no CRM com relações e autorizações apropriadas, sem presumir automaticamente poderes de representação para qualquer terceiro.
- Aplicar controles de privacidade, verificação de autorização e regras da LGPD, com especial cuidado para crianças e adolescentes, acesso mínimo necessário e isolamento por empresa.

## Agendamento de múltiplos serviços — APROVADO
- Permitir ao cliente selecionar dois ou mais serviços em uma única solicitação de reserva.
- Organizar serviços em sequência, calcular duração total com intervalos/buffers e permitir profissionais diferentes em cada etapa.
- Validar em conjunto a disponibilidade de profissionais, salas, recursos e horários para todos os serviços antes de confirmar.
- Confirmar o conjunto de forma consistente e atômica, evitando reservas parciais e duplicadas caso qualquer etapa apresente conflito.
- Enviar ao cliente resumo único da reserva com todos os serviços, horários e profissionais pertinentes.
- Permitir reagendar ou cancelar serviço individual ou o conjunto, conforme regras de cada serviço, atualizando disponibilidade e notificações.
- Manter registro e histórico individual de cada atendimento, vinculados a uma reserva composta e ao contato, respeitando permissões e isolamento por empresa.

## Agendamentos com profissionais alternativos — APROVADO
- Cliente pode escolher profissional específico; se indisponível, sistema sugere profissionais alternativos habilitados para o serviço e unidade.
- Exibir horários realmente livres dos profissionais alternativos, validando duração, bloqueios, recursos e demais conflitos.
- Disponibilizar opção 'Qualquer profissional disponível' com distribuição equilibrada entre profissionais elegíveis, respeitando regras e disponibilidade da empresa.
- Identificar claramente o profissional que realizará o atendimento antes de concluir a reserva.
- Exigir confirmação do cliente sobre a alternativa antes de efetivar o agendamento, inclusive nos fluxos de portal, página pública e chatbot.
- Respeitar permissões por unidade e empresa e manter histórico de escolha/atribuição quando aplicável.

## Substituição de profissionais em agendamentos existentes — APROVADO
- Permitir a usuário autorizado substituir o profissional de agendamento existente sem cancelar e recriar a reserva, preservando vínculo com cliente, serviço e histórico.
- Validar habilitação do profissional substituto para serviço/unidade e disponibilidade real, considerando horário, bloqueios, salas e recursos; impedir conflitos e sobreposições.
- Preservar horário original sempre que possível; caso seja necessário alterar o horário, aplicar as regras de reagendamento pertinentes.
- Exigir permissão específica para substituição e notificar o cliente sobre a mudança.
- Solicitar aceite do cliente quando exigido pela configuração do serviço, antes de concluir a substituição que dependa desse aceite.
- Registrar profissional anterior, substituto, responsável pela alteração e data/hora no histórico e auditoria.
- Atualizar calendários conectados, convites e demais integrações relacionadas, monitorando falhas de sincronização.

## Agendamentos prioritários e encaixes — APROVADO
- Classificar agendamentos como normais ou prioritários, com identificação nos painéis e relatórios.
- Permitir encaixes excepcionais fora dos horários normalmente disponíveis apenas por usuários com permissão específica.
- Exigir justificativa para encaixes e apresentar alerta explícito de possíveis sobreposições antes da confirmação.
- Preservar reservas existentes: encaixe não cancela, desloca ou compromete automaticamente agendamentos confirmados.
- Respeitar obrigatoriamente limites físicos e simultâneos de salas, equipamentos e demais recursos, sem criar reserva impossível de atender.
- Enviar confirmação ao cliente pelos canais habilitados.
- Registrar responsável, motivo, data/hora, natureza excepcional e alterações no histórico/auditoria.
- Disponibilizar filtros e indicadores de encaixes e atendimentos prioritários em relatórios, conforme permissões e isolamento por empresa.

## Agendamentos com aprovação prévia — APROVADO
- Permitir configurar por serviço se uma solicitação de agendamento precisa de aprovação de funcionário ou gestor autorizado.
- Solicitação inicial não equivale a reserva confirmada; utilizar estados de aprovação aguardando, aprovado ou recusado, distintos dos estados de comparecimento.
- Configurar prazo máximo de análise; reservar temporariamente a capacidade/horário durante esse prazo, impedindo conflito com outras solicitações e reservas.
- Ao aprovar, confirmar o agendamento e notificar o cliente; ao recusar, comunicar o cliente e liberar o horário.
- Ao expirar o prazo sem aprovação, encerrar a retenção temporária do horário e registrar expiração, evitando bloqueios indefinidos; eventual decisão posterior exige nova validação de disponibilidade.
- Registrar decisão, responsável, justificativa, horários e notificações no histórico, respeitando permissões e isolamento por empresa.

## Agendamentos com aprovação prévia — APROVADO
- Permitir configurar por serviço a exigência de aprovação de funcionário ou gestor autorizado antes de confirmar o agendamento.
- Solicitação inicial permanece com status 'aguardando aprovação', sem confirmação definitiva ao cliente; após análise, registrar 'aprovado' ou 'recusado'.
- Definir prazo máximo configurável para análise e responsável autorizado por aprovar/recusar.
- Enquanto pendente, manter bloqueio temporário da disponibilidade do horário, evitando reservas conflitantes, com expiração automática e liberação se o prazo terminar sem aprovação.
- Na aprovação, confirmar o agendamento de maneira consistente e notificar o cliente; na recusa, informar o cliente e liberar o horário.
- Registrar no histórico/auditoria decisão, responsável, justificativa, horários e notificações; respeitar permissões por serviço/unidade e isolamento por empresa.
- Evitar bloqueios indefinidos e impedir dupla confirmação do mesmo horário.

## Fila de aprovação de agendamentos — APROVADO
- Disponibilizar painel centralizado de solicitações de agendamento que dependem de aprovação prévia, com cliente, serviço, unidade, horário, prazo e responsável.
- Filtrar solicitações por serviço, unidade, responsável e status, respeitando escopos de permissão.
- Permitir aprovação individual e em lote por usuário autorizado, validando cada solicitação de forma independente quanto a prazo, disponibilidade, conflitos e permissões; não aprovar solicitações expiradas.
- Exigir justificativa para recusa, notificar cliente conforme canais habilitados e liberar a reserva temporária.
- Alertar responsáveis sobre pendências e destacar solicitações próximas do vencimento.
- Expirar automaticamente solicitações vencidas e liberar horários temporariamente bloqueados, sem criar reservas duplicadas.
- Registrar decisões, autor, justificativa, data/hora e alterações no histórico/auditoria; garantir isolamento entre empresas.

## Notificações internas da agenda — APROVADO
- Centralizar no CRM notificações internas para profissionais e gestores autorizados.
- Notificar o responsável por novos agendamentos, cancelamentos e reagendamentos, incluindo alterações de data ou horário.
- Alertar responsáveis por solicitações de agendamento pendentes de aprovação e por vagas liberadas na lista de espera quando pertinente.
- Disponibilizar central de notificações com estados de leitura e ação de marcar como lida.
- Permitir preferências por usuário para tipos de avisos recebidos, sem comprometer alertas operacionais obrigatórios que venham a ser definidos.
- Evitar notificações duplicadas, proteger informações conforme permissões por funcionário, equipe e unidade e garantir isolamento entre empresas.

## Agenda compartilhada da equipe — APROVADO
- Oferecer calendário compartilhado com visualização diária, semanal e mensal, mostrando agendas de profissionais lado a lado.
- Permitir filtros por profissional, equipe, serviço e unidade, dentro do escopo autorizado.
- Exibir disponibilidade livre/ocupada e diferenciar profissionais e tipos de atendimento por cores.
- Ocultar detalhes de compromissos privados, especialmente eventos externos sincronizados, exibindo apenas ocupação a quem não tem acesso.
- Permitir edição somente de agendamentos sobre os quais o usuário possua permissão específica.
- Refletir alterações para os usuários autorizados e utilizar os mesmos registros da agenda principal, sem criar agenda paralela ou reservas duplicadas.
- Respeitar isolamento entre empresas e permissões por funcionário, equipe e unidade.

## Configuração de horários individuais por profissional — APROVADO
- Permitir jornada semanal própria por profissional, com horários distintos por dia, intervalos de almoço/pausas e dias de folga.
- Suportar exceções de horários em datas específicas e jornadas diferentes conforme unidade.
- Permitir definir janelas de atendimento específicas por serviço para profissionais habilitados.
- Funcionário ou gestor pode alterar jornadas somente conforme permissões de acesso.
- Recalcular automaticamente disponibilidade na agenda interna, página pública, portal e chatbot, considerando também feriados, bloqueios, recursos e horários da unidade.
- Alertar sobre conflitos com reservas existentes antes de aplicar alterações, sem cancelar automaticamente agendamentos confirmados.
- Registrar histórico de alterações, responsável, data/hora e valores anteriores/novos, com isolamento por empresa.

## Antecedência e janela de agendamentos — APROVADO
- Configurar por empresa e serviço antecedência mínima entre a solicitação e o atendimento, bem como antecedência máxima para reservas futuras; valores exemplificativos (2 horas, 90 dias) não são padrões fixos.
- Respeitar horários de funcionamento da unidade, jornada do profissional, bloqueios, capacidade e disponibilidade real.
- Exibir aos clientes somente horários elegíveis no portal, página pública e chatbot, aplicando as mesmas regras no backend.
- Permitir que funcionários com permissão específica criem reservas fora da janela de antecedência, mantendo validações de conflitos e capacidade.
- Registrar exceções, responsável, justificativa quando aplicável e data/hora no histórico/auditoria.
- Garantir isolamento por empresa e consistência das regras entre canais.

## Intervalos entre atendimentos — APROVADO
- Configurar por serviço, em minutos, intervalos de preparação anteriores e de limpeza, organização ou descanso posteriores ao atendimento.
- Bloquear na disponibilidade os períodos necessários de profissionais, salas e recursos envolvidos, considerando a ocupação real de cada recurso.
- Diferenciar visualmente no calendário o tempo do atendimento e os intervalos associados.
- Aplicar a validação de conflitos na agenda interna, portal, página pública, chatbot e integrações, impedindo reservas que invadam intervalos obrigatórios.
- Permitir exceções somente por usuários autorizados, registrando responsável, alteração e data/hora, sem violar capacidade física ou reservas confirmadas.
- Calcular os intervalos automaticamente, respeitando regras de serviço, unidade e isolamento por empresa.

## Duração personalizada dos atendimentos — APROVADO
- Cada serviço mantém duração padrão no cadastro, mas funcionário com permissão específica pode ajustar a duração de um agendamento individual sem modificar a configuração geral.
- Exigir justificativa para cada ajuste e registrar duração anterior, nova duração, responsável e data/hora no histórico e auditoria.
- Recalcular início/fim, intervalos anteriores e posteriores e disponibilidade dos profissionais, salas e recursos envolvidos.
- Validar conflitos e impedir alterações que provoquem sobreposição com outras reservas ou excedam capacidade física.
- Notificar o cliente quando houver mudança no horário de início ou término, pelos canais habilitados.
- Propagar alterações às visualizações da agenda e integrações de calendário pertinentes, respeitando permissões e isolamento por empresa.

## Revisão — check-in pelo cliente REJEITADO
- Decisão posterior substitui a previsão anterior de check-in pelo próprio cliente no portal: não implementar confirmação digital autônoma de chegada.
- O registro de check-in e check-out permanece disponível somente para funcionários autorizados, mantendo presença individual em grupos, atrasos, faltas, histórico e relatórios.

## Observações internas dos agendamentos — APROVADO
- Permitir anotações operacionais internas vinculadas a cada agendamento, consultáveis diretamente em seus detalhes.
- Identificar autor, data e hora de cada registro; manter histórico de alterações nas observações.
- Restringir visualização e edição aos funcionários com permissões adequadas, respeitando empresa, equipe e unidade.
- Nunca exibir observações internas ao cliente em portal, página pública, chatbot, mensagens de confirmação ou documentos compartilhados.
- Manter observações separadas das mensagens e documentos enviados pelo cliente, com controles de acesso e auditoria.

## Revisão — prioridade na lista de espera REJEITADA
- A lista de espera deve seguir estritamente a ordem cronológica de inscrição (FIFO), sem mecanismo de prioridade ou antecipação manual por funcionários.
- Esta decisão substitui qualquer previsão anterior de exceções de prioridade autorizadas na fila.
- Permanecem notificação de vaga, prazo de resposta configurável, passagem ao próximo elegível após expiração e confirmação somente após aceite, com prevenção de dupla reserva.

## Controle de agendamentos duplicados por cliente — APROVADO
- Identificar cliente pelo registro vinculado e comparar data, início e término de reservas ativas para detectar sobreposição de horários, inclusive em serviços/profissionais/unidades diferentes dentro da mesma empresa.
- Alertar quando já existir agendamento do mesmo cliente no período.
- Bloquear novas reservas sobrepostas pelo portal do cliente e chatbot; aplicar validação no backend para impedir concorrência e duplicações.
- Permitir exceção apenas por funcionário com permissão específica e justificativa obrigatória, desde que não viole disponibilidade física de profissionais, salas e recursos.
- Registrar tentativas bloqueadas e exceções concedidas com usuário, data/hora e justificativa; respeitar isolamento entre empresas e privacidade.

## Status operacionais de atendimento — APROVADO
- Padronizar os status de agendamento: **Agendado** (reserva criada), **Aguardando confirmação** (cliente ainda não confirmou), **Confirmado** (cliente confirmou presença), **Aguardando atendimento** (funcionário registrou a chegada), **Em atendimento** (atendimento iniciado), **Concluído** (finalizado), **Não compareceu** (falta) e **Cancelado** (reserva cancelada).
- Atualizar status automaticamente quando houver evento correspondente, ou manualmente por funcionário autorizado, com validação de transições e histórico de responsável, data/hora e status anterior/novo.
- O status 'Aguardando atendimento' depende de registro por funcionário autorizado; **não** oferecer check-in digital autônomo pelo cliente, conforme rejeição anterior.
- Integrar status a notificações, relatórios, controle de presença e auditoria, respeitando permissões e isolamento entre empresas.

## Histórico de atendimentos por cliente — APROVADO
- Exibir no cadastro de cada contato/cliente do CRM uma visão consolidada dos agendamentos passados e futuros vinculados ao mesmo cliente.
- Apresentar status operacionais, incluindo concluídos, cancelados, não comparecimentos e demais situações; serviço, profissional responsável, unidade e data/hora.
- Permitir filtrar histórico por período, serviço, profissional e status.
- Disponibilizar observações internas associadas somente a funcionários com autorização específica, nunca no portal público do cliente.
- Atualizar o histórico automaticamente a partir dos registros da agenda principal, sem duplicar agendamentos; respeitar permissões por empresa, equipe e unidade e o isolamento entre empresas.

## Pesquisa de clientes diretamente na agenda — APROVADO
- Disponibilizar pesquisa rápida na interface da agenda por nome, telefone ou e-mail do cliente, sem necessidade de abrir seu cadastro.
- Exibir agendamentos anteriores e futuros associados ao contato, com filtros combináveis por período, status, serviço e unidade.
- Permitir abrir diretamente o detalhe de cada agendamento encontrado.
- Aplicar no backend permissões por empresa, equipe, unidade e registro, mostrando somente agendamentos autorizados, sem expor dados pessoais em resultados indevidos.
- Otimizar a pesquisa para volumes elevados, com indexação/paginação conforme arquitetura; utilizar registros existentes da agenda, sem duplicação de dados.

## Gerenciamento individual de participantes em agendamentos de grupo — APROVADO
- Manter um único agendamento/sessão de grupo com lista de participantes vinculados individualmente aos respectivos contatos.
- Permitir incluir participantes enquanto houver capacidade, com validação concorrente de vagas e sem ultrapassar limite da sessão.
- Permitir cancelamento/remoção individual de participação sem cancelar a sessão ou afetar outros participantes.
- Registrar confirmação, presença, falta e check-in por participante por funcionário autorizado, conforme rejeição do check-in digital autônomo do cliente.
- Enviar confirmações, lembretes e avisos individuais pelos canais habilitados, respeitando consentimentos e regras de mensagens.
- Recalcular vagas disponíveis quando participantes ingressarem, saírem ou cancelarem, integrando com lista de espera já aprovada.
- Preservar histórico de inclusão, cancelamento e alterações com autor/data/hora, respeitando permissões e isolamento por empresa.
- No portal, cada cliente vê somente seus próprios dados de participação, sem expor dados dos demais.

## Controle de conflitos de edição simultânea na agenda — APROVADO
- Detectar alterações concorrentes sobre o mesmo agendamento, inclusive entre funcionários, portal, chatbot e API.
- Empregar controle de versão ou equivalente no backend para impedir que uma atualização baseada em dados antigos sobrescreva alterações mais recentes.
- Quando detectar conflito, informar o usuário, exibir os dados atuais e solicitar revisão antes de tentar salvar novamente; não mesclar silenciosamente alterações incompatíveis.
- Revalidar disponibilidade de profissionais, salas, recursos, intervalos e capacidade no momento da gravação, usando operações atômicas para evitar reservas duplicadas.
- Atualizar a agenda dos usuários autorizados após mudanças e registrar autor, data/hora e campos alterados no histórico/auditoria, respeitando isolamento por empresa e permissões.

## Cancelamento de agendamentos pela equipe — APROVADO
- Somente funcionários com permissão específica podem cancelar agendamentos de clientes.
- Exigir seleção de motivo de cancelamento, com campo complementar de observação opcional.
- Após cancelamento confirmado, notificar cliente e profissional pelos canais habilitados e liberar horário, sala e recursos correspondentes, respeitando consistência transacional.
- Acionar a lista de espera para oferecer vaga liberada conforme ordem cronológica FIFO e prazo de resposta configurável, sem confirmar automaticamente antes do aceite.
- Atualizar calendários externos conectados e demais visualizações da agenda, registrando falhas de sincronização para tratamento.
- Preservar o agendamento com status Cancelado no histórico do cliente e nos relatórios; registrar autor, data/hora, motivo e alterações na auditoria, respeitando permissões e isolamento por empresa.

## Reagendamento de atendimentos pela equipe — APROVADO
- Funcionários com permissão específica podem alterar data e/ou horário de um agendamento existente, mantendo o mesmo identificador e histórico, sem cancelar e criar outra reserva.
- Exigir justificativa do reagendamento e registrar data/hora anterior e nova, responsável, motivo e momento da alteração.
- Selecionar nova data e horário conforme disponibilidade, revalidando profissional, sala, equipamentos, unidade, intervalos, capacidade e conflitos, com proteção contra edições simultâneas.
- Notificar cliente e profissional sobre a nova data/horário pelos canais habilitados; sincronizar calendários conectados e registrar eventuais falhas de integração.
- Aplicar regras de permissões, isolamento por empresa e auditoria. Liberar o horário anterior somente quando o novo agendamento estiver validado e salvo com sucesso, de modo atômico.

## Motivos de cancelamento personalizáveis por empresa — APROVADO
- Cada empresa terá catálogo próprio de motivos de cancelamento de agendamentos, com sugestões iniciais: desistência, imprevisto, indisponibilidade, duplicidade e outros.
- Administradores da empresa com permissão específica podem criar e editar os motivos disponíveis, respeitando isolamento entre empresas.
- A seleção de um motivo é obrigatória ao cancelar um agendamento; permitir observação complementar opcional.
- Preservar no histórico do agendamento o motivo efetivamente registrado no momento do cancelamento, mesmo após alterações no catálogo; não reescrever registros históricos.
- Permitir relatórios de quantidade de cancelamentos agrupados por motivo, com filtros e permissões já definidos para a agenda.
- Registrar alterações do catálogo em auditoria, com responsável, data/hora e valores anteriores/novos.

## Motivos de ausência (no-show) dos clientes — APROVADO
- Oferecer catálogo inicial de motivos de falta: esquecimento, imprevisto, problema de transporte, sem justificativa e outros, personalizável por empresa por usuários autorizados.
- Permitir marcar o status **Não compareceu** sem conhecer o motivo; o registro da falta não depende de justificativa do cliente.
- Permitir complementar ou corrigir posteriormente o motivo por funcionário autorizado, preservando valor anterior, novo valor, autor e data/hora no histórico/auditoria.
- Registrar motivo e responsável pela classificação da ausência, respeitando privacidade e isolamento entre empresas.
- Incluir análises de faltas por motivo, serviço e profissional nos relatórios da agenda, observando filtros e permissões.

## Regras adicionais de encerramento de atendimentos — REJEITADAS
- Não implementar fluxo específico de identificação/alerta de agendamentos cujo horário previsto passou e ainda estejam abertos, nem painel próprio de pendências de encerramento.
- Não encerrar automaticamente atendimentos pelo simples término do horário previsto.
- Preservar status operacionais e os procedimentos normais de atualização manual/autorizada já aprovados, incluindo Concluído e Não compareceu.

## Agendamento rápido pela equipe — APROVADO
- Disponibilizar formulário simplificado diretamente no calendário para recepcionistas e funcionários com permissão de criação de agendamentos.
- Permitir pesquisar e selecionar contato existente ou cadastrar novo cliente, com prevenção de duplicidades e respeito às permissões.
- Selecionar serviço, profissional disponível, data/horário e unidade quando houver mais de uma.
- Ao confirmar, criar reserva na agenda principal e enviar notificação ao cliente pelos canais habilitados.
- Aplicar integralmente no backend as regras existentes de jornadas, antecedência, intervalos, salas/recursos, capacidade, conflito de horários, duplicidade por cliente, permissões e isolamento entre empresas.
- Evitar agenda paralela; preservar histórico/auditoria e atualização da agenda compartilhada.

## Duplicação manual de agendamentos pela equipe — APROVADO
- Permitir que funcionários com permissão específica criem um novo agendamento a partir de um existente, reaproveitando cliente, serviço, profissional e unidade.
- Exigir seleção de nova data e horário e apresentar resumo para confirmação individual antes de salvar; não gerar recorrência automática.
- Revalidar disponibilidade do profissional, salas, equipamentos, intervalos, capacidade e conflitos, incluindo sobreposição de reservas do mesmo cliente e demais regras vigentes.
- Após confirmação e criação bem-sucedida, notificar o cliente pelos canais habilitados.
- Manter agendamento original e novo agendamento como registros independentes, com identificadores e históricos próprios; registrar origem da duplicação para rastreabilidade sem confundir as reservas.
- Respeitar permissões, isolamento por empresa e auditoria; não copiar automaticamente observações internas ou documentos sensíveis sem decisão específica.
