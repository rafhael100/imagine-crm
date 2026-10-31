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
