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
