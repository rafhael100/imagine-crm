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
