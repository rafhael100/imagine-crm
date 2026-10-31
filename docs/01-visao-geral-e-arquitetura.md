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
