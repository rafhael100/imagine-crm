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
