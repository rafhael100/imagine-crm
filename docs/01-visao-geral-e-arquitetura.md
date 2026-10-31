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

## Próxima fase do projeto — Definição do MVP comercial (APROVADA)
- O responsável pelo CRM Imagine aprovou priorizar a definição de um MVP orientado à conquista dos primeiros clientes pagantes, evitando tentar lançar simultaneamente todas as funcionalidades especificadas.
- Objetivo: validar proposta de valor, ativação de usuários, disposição a pagar, conversão de trial para assinatura e retenção antes de ampliar escopo.
- O escopo exato, a ordem das entregas, o cronograma e as versões posteriores **ainda precisam de aprovação específica**; funcionalidades já aprovadas permanecem no roadmap e não devem ser tratadas como excluídas.
- Durante a priorização, avaliar valor ao cliente, dependências técnicas, esforço, custo operacional, diferenciação, risco e impacto na aquisição/retenção.
- Estratégia de negócio: acompanhar indicadores de aquisição, conversão, receita recorrente, churn e margem para orientar evolução do produto.

## Plataforma inicial do MVP — Web responsiva (APROVADO)
- Lançar a primeira versão comercial do CRM Imagine como aplicação **Web responsiva**, acessível via navegadores em computadores, tablets e smartphones.
- Não desenvolver nem publicar aplicativos nativos para Android e iOS na primeira versão; considerar apps móveis em fase posterior conforme demanda, adoção e retorno esperado.
- Priorizar experiência móvel utilizável no navegador, segurança, estabilidade, desempenho, recursos comerciais essenciais e validação com clientes pagantes.
- Esta decisão define o canal de acesso do MVP, não aprova nem exclui por si só outras funcionalidades do roadmap.

## Diretriz de diferenciação do MVP — IA e automações
- O responsável pelo projeto explicitou que **agentes de IA e automações comerciais são importantes desde o lançamento**, para diferenciar o CRM Imagine de concorrentes; não devem ser simplesmente adiados para versões posteriores.
- Ajustar a proposta de escopo do MVP para contemplar automações comerciais e agentes de IA em uma primeira versão enxuta, a ser detalhada e aprovada; manter arquitetura extensível para evolução.
- Proposta em discussão (ainda não aprovada em detalhes): automações essenciais (distribuição de leads, tarefas e alertas), IA Prospector (priorização e análise de leads) e IA Comercial (sugestões de abordagem, resumos e próximos passos), com revisão humana antes de enviar mensagens.
- Manter a monetização dos agentes como serviços opcionais e limites/custos controlados conforme decisões anteriores; definir escopo exato, integrações e disponibilidade no lançamento antes de fechar roadmap.
