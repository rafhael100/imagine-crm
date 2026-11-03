# CRM-MASTER-01 — Inventário de módulos, fontes históricas e situação de consolidação

**Data de levantamento:** 2026-11-03.
**Estado:** **INVENTÁRIO DOCUMENTAL PROPOSTO PARA REVISÃO**, não aprovação global de módulos, cargos, funcionalidades, custos ou prontidão para programação.
**Objetivo:** consolidar o CRM Imagine completo antes de começar Codex, distinguindo **aprovação funcional histórica**, **base documental aceita**, **especificação executável faltante** e **evidência original integral não localizada**.

## 1. Regra de proveniência e precedência

1. `main` é a branch de trabalho atual, **mas não contém sozinha toda a história funcional**. A branch `docs/integracao-site-crm-basico` contém 19 arquivos de especificação anteriores, com capítulos longos e registros históricos; `docs/especificacao-inicial-2026-10-31` contém 10 arquivos anteriores.
2. O rótulo **APROVADO** em capítulo histórico é um indício de decisão funcional documentada; antes de código, conferir **escopo, atualizações posteriores e origem**. Não considerar automaticamente final/superado, tampouco descartar sem prova.
3. As **aprovações de base** de CT-01..11 e OP24 são bases documentais aceitas, sem schemas/roles/parâmetros executáveis aprovados.
4. Não misturar **Portal Imagine/Sites** com o SaaS CRM; separar contratos e cobranças, mantendo a interface. A Universidade é projeto posterior e **não bloqueia**.
5. Somente requisitos P0 críticos fechados e versão global revisada e aprovada expressamente liberam programação. **Não executar testes reais nesta fase**.
6. Um item sem capítulo específico na `main` **não é um item não planejado**: pode estar em capítulos históricos ou decisões conversacionais. A classificação é sobre **evidência e consolidação**, não sobre implementação real.
7. **Nenhum percentual total de conclusão** pode ser derivado do número de arquivos, decisões ou páginas. Esta matriz identifica lacunas, não dá nota arbitrária.

## 2. Índice mestre de módulos e dependências

| ID | Módulo/área | Fontes principais no GitHub | Situação documental atual | Revisão |
|---|---|---|---|---|
| CRM-01 | Plataforma e arquitetura SaaS | [01 — visão/arquitetura](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md); [IN-168](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-168-matriz-pendencias-fechamento-arquitetura.md) | Fontes históricas robustas; sem capítulo final integrado à main | P0 |
| CRM-02 | Administração geral Imagine | [03 — administração](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md); [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | Histórico disponível; documento mestre atual não unificado | P0 |
| CRM-03 | Empresas, usuários, equipes e convites | [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [13 — permissões Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md) | Capítulo histórico detalhado; matriz executável atual ainda parcial | P0 |
| CRM-04 | Planos, trial, assinaturas e cortesia | [02 — planos/trial](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md); [IN-119/120/121](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-119-gestao-cortesia-crm-basico.md) | Aprovações históricas; preços/limites e elegibilidade devem passar por revisão comercial única | P0 |
| CRM-05 | Faturamento e operações financeiras | [02 — planos](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md); [PORTAL-01](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/PORTAL-01-matriz-pendencias-plataforma-sites.md) | Decisões dispersas CRM × Portal; PSP, exceções e custos pendentes | P0 |
| CRM-06 | Leads, contatos, empresas e histórico | [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [IN-118](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-118-central-backup-portabilidade-dados.md) | Funcional histórico extenso; falta esquema/contrato unificado por entidade | P0 |
| CRM-07 | Campos personalizados e validações | [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [02 — limites](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md) | Funcional histórico detalhado; modelo físico e migração pendentes | P0 |
| CRM-08 | Vendas, funis, propostas e catálogo comercial | [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | Muitos tópicos marcados aprovados no histórico; não estão em capítulos finais próprios na main | P0 |
| CRM-09 | Atividades, tarefas, equipes e desempenho | [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | Aprovações históricas presentes; modelos, limites e UX ainda a consolidar | P1 |
| CRM-10 | Atendimento omnicanal / WhatsApp | [01 — visão/atendimento](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md); [04 — atendimento](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | Fonte funcional histórica muito extensa; capítulo ATD único ainda pendente | P0 |
| CRM-11 | Chatbot, fluxos e automações | [04 — chatbot/automação](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [10 — biblioteca](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/10-biblioteca-automacoes-e-criterios-de-planos.md) | Fonte histórica extensa; contratos do Motor de Fluxos ainda não unificados | P0 |
| CRM-12 | Agenda, profissionais, unidades e recursos | [04 — agenda](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [11 — agenda Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/11-agenda-manual-basico-cortesia.md) | Decisões históricas expressas; limites e permissões por plano exigem revisão | P0 |
| CRM-13 | Agentes de IA de atendimento e vendas | [05 — IA](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/05-agentes-de-ia.md); [IN-167](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-167-consolidacao-arquitetura-agentes-tecnicos.md) | Fontes distintas para IA comercial e agentes técnicos; cotas/validade e papéis a reconciliar | P0 |
| CRM-14 | Integrações e contratos externos | [Índice INT](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/integracoes/README.md); [painel P0](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) | 11 CT e 24 OP aceitas como bases; 11 P0 INT e G01..G06 abertos | P0 |
| CRM-15 | Integração Sites↔CRM e benefício Básico | [IN-122](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-122-multiplos-sites-contas-crm-cortesias.md); [IN-124](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-124-integracao-inteligente-formularios-leads.md); [15 — IN-117](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/15-in117-escolha-crm-por-novo-site.md) | Conceito e muitos contratos funcionais documentados; integração crítica precisa contrato executável | P0 |
| CRM-16 | Arquivos, anexos, backup e portabilidade | [IN-118](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-118-central-backup-portabilidade-dados.md); [06 — infraestrutura](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/06-infraestrutura-e-operacoes.md) | Políticas e capacidades aprovadas em fases distintas; contratos/estimativas físicas pendentes | P0 |
| CRM-17 | Segurança, LGPD e auditoria | [03 — administração](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md); [04 — auditoria](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [IN-151](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-151-gestao-inteligente-credenciais-chaves-integracao.md) | Normas distribuídas; faltam esquemas de autorização por ação/campo e contrato de evidência | P0 |
| CRM-18 | Infraestrutura, observabilidade, agentes e continuidade | [IN-167](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-167-consolidacao-arquitetura-agentes-tecnicos.md); [IN-168](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-168-matriz-pendencias-fechamento-arquitetura.md) | Arquitetura conceitual avançada; ARQ-01/02/03, SEG-01/02, AGT-01 ainda P0 | P0 |
| CRM-19 | Suporte, implantação e governança operacional | [IN-130](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-130-central-incidentes-tecnicos-agentes-ia.md); [IN-137](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-137-central-aprovacoes-tecnicas.md) | Há documentos técnicos; fluxos de suporte do CRM cliente ainda devem ser localizados/consolidados | P1 |
| CRM-20 | Marketing, campanhas e CMS/Base de conhecimento | [04 — automação e relatórios](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [IN-163](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-163-ciclo-vida-conhecimento-agentes.md) | Documentação de CRM marketing/CMS pode estar em conversas e não está centralizada no índice atual | P1 |
| X-PORTAL | Portal Imagine/Sites (dependência externa ao CRM) | [PORTAL-01](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/PORTAL-01-matriz-pendencias-plataforma-sites.md) | Plano externo em curso; interfaces condicionam CRM, não exige implementar Portal inteiro primeiro | Dependência |
| X-UNI | Universidade Imagine (projeto posterior) | [UNI-001](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/UNI-001-diretrizes-universidade-imagine-metodologia-ensino.md) | Conceito aprovado; explicitamente não deve bloquear CRM | Não bloqueante |

**Total: 22 áreas de trabalho (20 do CRM + 2 interfaces/projetos relacionados).** Este agrupamento é índice proposto, não alteração do catálogo oficial ATD/VEN/AGD/INT/IN aprovado.

## 3. Situação de cada área e próxima ação de documentação

### CRM-01 — Plataforma e arquitetura SaaS

**Registros históricos localizados:** Visão geral Laravel/PostgreSQL, multiempresa, login multi-organização; isolamento RLS aprovado em seção posterior do mesmo capítulo.

**Fontes verificadas:** [01 — visão/arquitetura](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md); [IN-168](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-168-matriz-pendencias-fechamento-arquitetura.md).

**Situação de consolidação:** Fontes históricas robustas; sem capítulo final integrado à main. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Consolidar modelo de tenants, identidade/tenant ativo, isolamento de dados/arquivos/filas, ambientes e decisões de implementação.

### CRM-02 — Administração geral Imagine

**Registros históricos localizados:** Perfis administrativos, ações críticas com competência e auditoria, suporte excepcional, MFA e sem acesso universal aos clientes.

**Fontes verificadas:** [03 — administração](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md); [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md).

**Situação de consolidação:** Histórico disponível; documento mestre atual não unificado. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Reconciliar papéis administrativos, acesso excepcional, separação de dados e auditoria com política central de Segurança.

### CRM-03 — Empresas, usuários, equipes e convites

**Registros históricos localizados:** Admin principal, participação em várias empresas, equipes, perfil/ação/escopo e convite de sete dias.

**Fontes verificadas:** [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [13 — permissões Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md).

**Situação de consolidação:** Capítulo histórico detalhado; matriz executável atual ainda parcial. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Matriz efetiva perfil×recurso×ação×campo; administradores por empresa, MFA, convites, revogação/escopo.

### CRM-04 — Planos, trial, assinaturas e cortesia

**Registros históricos localizados:** Básico, Essencial, Profissional, Empresarial; trial 14 dias; plano Básico público e cortesia por vínculo elegível.

**Fontes verificadas:** [02 — planos/trial](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md); [IN-119/120/121](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-119-gestao-cortesia-crm-basico.md).

**Situação de consolidação:** Aprovações históricas; preços/limites e elegibilidade devem passar por revisão comercial única. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Consolidar matriz comercial vigente e suas regras de transição sem recair em valores históricos substituídos.

### CRM-05 — Faturamento e operações financeiras

**Registros históricos localizados:** Cobrança recorrente, meios de pagamento, cobrança por plano/site independente, pró-rata/estornos/créditos/NFS-e em planejamento.

**Fontes verificadas:** [02 — planos](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md); [PORTAL-01](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/PORTAL-01-matriz-pendencias-plataforma-sites.md).

**Situação de consolidação:** Decisões dispersas CRM × Portal; PSP, exceções e custos pendentes. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Separar assinatura CRM/Portal, Pix Automático 5% quando aprovado e cartão, encargos, conciliação, faturas, NFS-e e evento financeiro oficial.

### CRM-06 — Leads, contatos, empresas e histórico

**Registros históricos localizados:** Cadastros, origem, transferências, duplicados, importação/exportação e histórico comercial.

**Fontes verificadas:** [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [IN-118](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-118-central-backup-portabilidade-dados.md).

**Situação de consolidação:** Funcional histórico extenso; falta esquema/contrato unificado por entidade. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Dicionário de dados, fonte oficial, direitos de campo, histórico, exclusão/mesclagem e fronteiras INT.

### CRM-07 — Campos personalizados e validações

**Registros históricos localizados:** Tipos, versões, obrigatoriedade, máscaras, arquivamento, conversões e limites por plano.

**Fontes verificadas:** [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [02 — limites](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md).

**Situação de consolidação:** Funcional histórico detalhado; modelo físico e migração pendentes. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Consolidar schema/validação por tipo, compatibilidade, proteção de dados e integração com filtros/formulários.

### CRM-08 — Vendas, funis, propostas e catálogo comercial

**Registros históricos localizados:** Funis Kanban, oportunidades, probabilidade, propostas, aceite, metas e relatórios.

**Fontes verificadas:** [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md).

**Situação de consolidação:** Muitos tópicos marcados aprovados no histórico; não estão em capítulos finais próprios na main. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Dividir os grandes blocos VEN/CAT em contratos/fluxos oficiais, regras de aprovação e critérios de aceite.

### CRM-09 — Atividades, tarefas, equipes e desempenho

**Registros históricos localizados:** Atividades comerciais, anotações, tarefas, metas, dashboards e relatórios automáticos.

**Fontes verificadas:** [04 — empresas/usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md).

**Situação de consolidação:** Aprovações históricas presentes; modelos, limites e UX ainda a consolidar. **Prioridade para fechamento:** P1.

**Próximo trabalho:** Modelos de tarefa/atividade, calendário de eventos, permissões, indicadores e UX.

### CRM-10 — Atendimento omnicanal / WhatsApp

**Registros históricos localizados:** Central WhatsApp oficial, números múltiplos, atendimento humano/IA, filas, distribuição e transferência.

**Fontes verificadas:** [01 — visão/atendimento](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md); [04 — atendimento](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md).

**Situação de consolidação:** Fonte funcional histórica muito extensa; capítulo ATD único ainda pendente. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Sintetizar ATD-01..12, dono da conversa, SLA, consentimento, provedor oficial, segurança e estados de atendimento.

### CRM-11 — Chatbot, fluxos e automações

**Registros históricos localizados:** Editor visual, gatilhos, versão, publicação, inatividade, mensagens e modelos prontos.

**Fontes verificadas:** [04 — chatbot/automação](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [10 — biblioteca](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/10-biblioteca-automacoes-e-criterios-de-planos.md).

**Situação de consolidação:** Fonte histórica extensa; contratos do Motor de Fluxos ainda não unificados. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Motor de Eventos/Tarefas/Gatilhos/Condições/Ações com limites, aprovação e fronteiras sem duplicar n8n/INT.

### CRM-12 — Agenda, profissionais, unidades e recursos

**Registros históricos localizados:** Agenda integrada, horários, bloqueios, agendamentos/recorrência, profissionais, serviços, recursos e unidades.

**Fontes verificadas:** [04 — agenda](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [11 — agenda Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/11-agenda-manual-basico-cortesia.md).

**Situação de consolidação:** Decisões históricas expressas; limites e permissões por plano exigem revisão. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Consolidar AGD, agenda manual Básico vs autoagendamento pago, conflitos, transições, recursos e integrações externas.

### CRM-13 — Agentes de IA de atendimento e vendas

**Registros históricos localizados:** Prospector, Comercial e demais agentes, resumos/histórico, créditos, memória e permissões controladas.

**Fontes verificadas:** [05 — IA](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/05-agentes-de-ia.md); [IN-167](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-167-consolidacao-arquitetura-agentes-tecnicos.md).

**Situação de consolidação:** Fontes distintas para IA comercial e agentes técnicos; cotas/validade e papéis a reconciliar. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Separar IA cliente de agentes técnicos Imagine; consumo/cota/compra, papéis, fontes de conhecimento, autorização e logs.

### CRM-14 — Integrações e contratos externos

**Registros históricos localizados:** INT-01..12, INT-R01/02, CT-01..11, OP24, filas e reconciliação.

**Fontes verificadas:** [Índice INT](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/integracoes/README.md); [painel P0](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md).

**Situação de consolidação:** 11 CT e 24 OP aceitas como bases; 11 P0 INT e G01..G06 abertos. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Recuperar originais, fechar TR/OWN/SEC/CON/DEP/VER e revisão final; não executar HT agora.

### CRM-15 — Integração Sites↔CRM e benefício Básico

**Registros históricos localizados:** Vínculo com conta existente/nova, cortesia, formulários, fila durável e confirmação no CRM.

**Fontes verificadas:** [IN-122](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-122-multiplos-sites-contas-crm-cortesias.md); [IN-124](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-124-integracao-inteligente-formularios-leads.md); [15 — IN-117](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/15-in117-escolha-crm-por-novo-site.md).

**Situação de consolidação:** Conceito e muitos contratos funcionais documentados; integração crítica precisa contrato executável. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Contratos de ativação, tenant, benefício, idempotência, confirmação e 18 dias; sem migração implícita de históricos.

### CRM-16 — Arquivos, anexos, backup e portabilidade

**Registros históricos localizados:** Anexos, quotas, uploads seguros, exportação/importação, cópias e recuperação por empresa.

**Fontes verificadas:** [IN-118](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-118-central-backup-portabilidade-dados.md); [06 — infraestrutura](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/06-infraestrutura-e-operacoes.md).

**Situação de consolidação:** Políticas e capacidades aprovadas em fases distintas; contratos/estimativas físicas pendentes. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Unificar armazenamento por plano, segurança de upload, backup, retenção/replicação, exportação e separação de dados estruturados.

### CRM-17 — Segurança, LGPD e auditoria

**Registros históricos localizados:** RBAC multiempresa, MFA, acesso excepcional, isolamento, proteção de segredos, direitos LGPD, auditoria.

**Fontes verificadas:** [03 — administração](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md); [04 — auditoria](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [IN-151](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-151-gestao-inteligente-credenciais-chaves-integracao.md).

**Situação de consolidação:** Normas distribuídas; faltam esquemas de autorização por ação/campo e contrato de evidência. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Unificar regras de cliente vs equipe Imagine, LGPD, trilha e classes de retenção; evitar privilégios implícitos.

### CRM-18 — Infraestrutura, observabilidade, agentes e continuidade

**Registros históricos localizados:** Separação de servidores e n8n, serviços de monitoramento/segurança, agentes técnicos, cofre, backup e incidentes.

**Fontes verificadas:** [IN-167](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-167-consolidacao-arquitetura-agentes-tecnicos.md); [IN-168](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-168-matriz-pendencias-fechamento-arquitetura.md).

**Situação de consolidação:** Arquitetura conceitual avançada; ARQ-01/02/03, SEG-01/02, AGT-01 ainda P0. **Prioridade para fechamento:** P0.

**Próximo trabalho:** Topologia, contratos de APIs, cofre, identidades, monitoramento independente de IA, playbooks e custos.

### CRM-19 — Suporte, implantação e governança operacional

**Registros históricos localizados:** Suporte técnico, incidentes, aprovações, contratos de serviços e comunicação com clientes.

**Fontes verificadas:** [IN-130](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-130-central-incidentes-tecnicos-agentes-ia.md); [IN-137](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-137-central-aprovacoes-tecnicas.md).

**Situação de consolidação:** Há documentos técnicos; fluxos de suporte do CRM cliente ainda devem ser localizados/consolidados. **Prioridade para fechamento:** P1.

**Próximo trabalho:** Distinguir suporte a cliente vs operação técnica, SLA, identidades e histórico de chamados.

### CRM-20 — Marketing, campanhas e CMS/Base de conhecimento

**Registros históricos localizados:** Marketing/retencão comercial, conteúdos, modelos de base de conhecimento, fontes e automações.

**Fontes verificadas:** [04 — automação e relatórios](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md); [IN-163](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-163-ciclo-vida-conhecimento-agentes.md).

**Situação de consolidação:** Documentação de CRM marketing/CMS pode estar em conversas e não está centralizada no índice atual. **Prioridade para fechamento:** P1.

**Próximo trabalho:** Localizar decisões originais, separar escopo de marketing, conteúdo e conhecimento de agentes técnicos.

### X-PORTAL — Portal Imagine/Sites (dependência externa ao CRM)

**Registros históricos localizados:** Onepage/Landing, Cloudflare, DNS/SSL, cobrança separada, GA4 opcional e cortesia.

**Fontes verificadas:** [PORTAL-01](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/PORTAL-01-matriz-pendencias-plataforma-sites.md).

**Situação de consolidação:** Plano externo em curso; interfaces condicionam CRM, não exige implementar Portal inteiro primeiro. **Prioridade para fechamento:** Dependência.

**Próximo trabalho:** Fechar somente contratos de interface que bloquearem o CRM; demais decisões ficam no planejamento Portal.

### X-UNI — Universidade Imagine (projeto posterior)

**Registros históricos localizados:** Treinamento de usuários após conclusão Portal e CRM.

**Fontes verificadas:** [UNI-001](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/UNI-001-diretrizes-universidade-imagine-metodologia-ensino.md).

**Situação de consolidação:** Conceito aprovado; explicitamente não deve bloquear CRM. **Prioridade para fechamento:** Não bloqueante.

**Próximo trabalho:** Planejar no seu próprio ciclo, implementar somente após os sistemas principais.


## 4. Cinco riscos práticos de reconciliação já observados

| ID | Evidência concreta | Tratamento conservador proposto |
|---|---|---|
| RC-01 — Tenancy/RLS | O capítulo histórico [01](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md) contém uma nota inicial de RLS “não ratificado”, **mas possui seção posterior “Isolamento multiempresa no PostgreSQL — APROVADO”** com `tenant_id` + PostgreSQL RLS | **Respeitar a aprovação posterior no próprio capítulo**; conferir data/procedência e regras de contexto Laravel/filas, sem reabrir como se estivesse só em estudo |
| RC-02 — Básico/planos | O capítulo histórico de [planos](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md) contém preços e variantes antigas, com atualização de Básico 69 e Essencial 89 e revisão final planejada | Consolidar tabela de preços/benefícios mais recente antes de aprovação final, separando Base, Essencial, Profissional, Empresarial e cortesia |
| RC-03 — MVP | A seção “Definição do MVP comercial — APROVADA” em [01](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md) aprova **priorizar um MVP orientado a clientes pagantes**, mas expressamente diz que **escopo e sequência exatos ainda precisam de aprovação específica** | Não assumir que todas as funcionalidades históricas serão MVP v1, nem excluir funções aprovadas do roadmap; preparar delimitação de versões para escolha expressa |
| RC-04 — Condições comerciais | [PORTAL-01](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/PORTAL-01-matriz-pendencias-plataforma-sites.md) e documentos PORTAL-COM contêm histórico/propostas de preço e parcelamento; decisões posteriores do Pix/cartão prevalecem quando comprovadas | Não converter **propostas históricas** de descontos/Pix parcelado em política comercial vigente; confirmar 5% Pix à vista e Pix Automático e até 3x no cartão conforme aprovação posterior, e harmonizar o Portal sem misturar cobranças do CRM |
| RC-05 — Cobertura do código futuro | Os módulos funcionais de atendimento, vendas, agenda e automações estão agregados em capítulos históricos muito extensos, enquanto a `main` tem foco recente em INT/IN | Criar capítulos canônicos separados e critérios de aceite por módulo; não pedir ao Codex para inferir decisões a partir de uma coleção dispersa de arquivos |

## 5. Como concluir a documentação sem retrabalho

**Fase A — Índice e decisões vigentes:** localizar e mapear todas as aprovações dos capítulos históricos, `main` e conversas; resolver prevalência da versão expressamente posterior. **Sem novas condições comerciais presumidas**.

**Fase B — P0 de módulos e arquitetura:** núcleo multiempresa/RBAC, dados comerciais, atendimento/WhatsApp, agenda, automações, IA e consumo, cobrança, APIs, arquivos, INT, segurança, integrações Sites↔CRM, infraestrutura. Fixar contratos/interfaces por fonte oficial e operação protegida.

**Fase C — Decisões P1/P2 e delimitação de versão:** identificar quais recursos entram no MVP inicial (já há diretriz de MVP aprovado) versus roadmap posterior, critérios de aceitação, viabilidade e dependências. Descartar cronogramas inventados e custos não homologados.

**Fase D — Revisão final e autorização:** revisão cruzada global de contratos, política de precedência, LGPD/segurança/financeiro, evidências P0, backlog e referências/commit imutáveis; **aprovação expressa do planejamento completo**. Codex só depois desse marco.

## 6. Revisões produzidas e seus limites (03/11/2026)

- [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md): núcleo multiempresa/RBAC **aceito como base documental**, conforme [CRM-DEC](CRM-DEC-aprovacao-REV-01-base.md); dez lacunas CORE-P0 ainda abertas.
- [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md): planos, trial, cobrança e cortesia **redigidos em minuta**, reunindo 20 regras funcionais, 12 pontos de precedência e 12 pendências propostas; preços atuais de planejamento preservados. **Ainda não recebeu aceite específico**.
- O índice mestre permanece ferramenta de reconciliação, não aprovação global de prontidão ou autorização de desenvolvimento.

## 7. CRM-REV-02 aceita e CRM-REV-03 preparada (03/11/2026)

- [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) foi **aceita como base documental**, conforme [CRM-DEC](CRM-DEC-aprovacao-REV-02-base.md). Preservar todos os preços de referência atuais e manter diferenças/pontos técnicos para revisão final.
- [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md) foi preparada **como minuta para revisão**, com 26 regras funcionais localizadas, 12 diferenças/riscos, 12 pendências e 14 cenários futuros. **Não está aprovada**.
- O Índice Mestre continua instrumento de reconciliação; nada autoriza iniciar programação.

## 8. Aceite CRM-REV-03 e preparação CRM-REV-04 (03/11/2026)

- [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md) foi **aceita como base documental**, conforme [CRM-DEC](CRM-DEC-aprovacao-REV-03-base.md). As divergências e pendências DAT permanecem abertas para especificação final.
- [CRM-REV-04](CRM-REV-04-atendimento-whatsapp-conversas.md) foi redigida como **minuta para revisão**, com 28 regras documentadas, 12 riscos de conciliação, 14 pendências e 16 cenários futuros. **Não recebeu aceite ainda**.
- O planejamento global e a primeira liberação do Codex permanecem bloqueados até fechamento transversal e aprovação final.

## 9. Aceite da CRM-REV-04 e elaboração da CRM-REV-05 (03/11/2026)

- [CRM-REV-04](CRM-REV-04-atendimento-whatsapp-conversas.md) foi **aceita como base documental**, conforme [CRM-DEC](CRM-DEC-aprovacao-REV-04-base.md). Pendências de WhatsApp/IA/posse/fila e provedores continuam abertas.
- [CRM-REV-05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md) reúne 28 regras de automações, 12 riscos de conciliação, 14 pendências e 14 cenários documentais; **minuta para revisão, ainda não aprovada**.
- Preservada a separação das operações de sistema, automações comerciais em slots e chatbot/canais/IA com direitos e custos próprios.
- Sem contratos de implementação fechados ou autorização para iniciar Codex.

## 10. Aceite da CRM-REV-05 e minuta CRM-REV-06 (03/11/2026)

- [CRM-REV-05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md): **aceita como base documental**, conforme [CRM-DEC](CRM-DEC-aprovacao-REV-05-base.md), preservando separação de operações de sistema/automações comerciais/chatbot/IA; 14 pendências AUT-P ainda abertas.
- [CRM-REV-06](CRM-REV-06-vendas-funis-propostas-catalogo.md): **minuta para revisão**, com 33 itens funcionais de cotejo, 14 riscos, 16 pendências e 18 cenários futuros; 22 seções históricas incorporadas para confronto. **Sem aprovação própria**.
- Próximo pacote depois da REV-06: Agenda e Agendamentos. O fechamento técnico/global e a revisão comercial final permanecem necessários antes do Codex.

## 11. Aceite CRM-REV-06 e minuta CRM-REV-07 (03/11/2026)

- [CRM-REV-06](CRM-REV-06-vendas-funis-propostas-catalogo.md): **aceita como base documental**, conforme [CRM-DEC](CRM-DEC-aprovacao-REV-06-base.md); contratos VEN/CAT e 16 lacunas permanecem abertos.
- [CRM-REV-07](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md): **minuta de revisão**, com 42 regras de Agenda, seis exclusões/rejeições expressas, 16 divergências, 18 pendências e 22 cenários futuros; **sem aceite específico**.
- O documento da Agenda preserva o Básico estritamente manual/interno, pagamentos de serviços fora do escopo, check-in somente por equipe e fila de espera FIFO sem exceção prioritária. Preços e parâmetros de agenda ainda pendentes.
- Próxima revisão após aprovação da Agenda: IA comercial, créditos, conhecimento e supervisão. Sem Codex até aprovação global.

## 12. Agenda aprovada e nova gratuidade administrativa (03/11/2026)

- [CRM-REV-07](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md) foi **aprovada como base documental**, conforme [CRM-DEC REV-07](CRM-DEC-aprovacao-REV-07-base.md). As 42 regras e seis rejeições foram preservadas, mantendo AGD-P01..18 em aberto.
- **Nova decisão expressa do responsável:** o **Administrador Geral Imagine** pode habilitar gratuitamente **qualquer plano do CRM** para uma empresa cliente específica, inclusive planos superiores. [CRM-DEC — Gratuidade administrativa](CRM-DEC-gratuidade-administrativa-qualquer-plano.md).
- Essa concessão **não depende do Site Imagine**, **não consome trial**, não altera o preço público do plano e é **separada da cortesia Básico por site**. Recursos incluídos seguem o plano escolhido; cobertura de extras e detalhes de vigência, revogação, concessão a cliente pagante, segurança e fiscal/financeiro dependem de GRAT-P01..08.
- Impactos documentais: **CRM-02 Administração Geral**, **CRM-03 Identidade/Permissões**, **CRM-04 Planos/Cortesia** e **CRM-05 Faturamento**, além de comunicação no painel central. Não gerar quinto plano “grátis” nem cobrança recorrente sem aceite do cliente.
- Próxima frente: CRM-REV-08 — agentes de IA e consumo; nenhuma programação até aprovação final global.

## 13. CRM-REV-08 preparada após aceite da Agenda e nova concessão administrativa (03/11/2026)

- [CRM-REV-08](CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md): **minuta para revisão**, com 28 itens funcionais cotejados, 12 riscos, 15 pendências e 16 cenários futuros. IA comercial dos clientes, consumo e Base de Conhecimento devem permanecer separados da operação técnica IN-157/158/163..167.
- Atenção ao conflito de fontes: a branch antiga informa créditos comprados até consumo, enquanto revisão posterior aponta validade **90 dias**. Resolver procedência literal, categoria e vigência antes de homologar faturamento/consumo, sem escolher unilateralmente.
- [Gratuidade administrativa](CRM-DEC-gratuidade-administrativa-qualquer-plano.md): plano de uma empresa grátis não implica créditos adicionais de IA grátis; GRAT-P05 ainda pendente.

## 14. Duas modalidades de gratuidade administrativa aprovadas (03/11/2026)

- [CRM-DEC — Vigência das concessões](CRM-DEC-gratuidade-vigencia-modalidades.md): **prazo determinado e prazo indeterminado aprovados**, sem prazo máximo ou padrão fixado. Exemplo de seis meses não é valor normativo.
- **Nenhuma cobrança recorrente é ativada automaticamente** após expiração ou encerramento. Concessões continuam por organização, com mensalidade base do plano escolhido isenta; extras não foram incluídos automaticamente.
- **GRAT-P01 e GRAT-P04 parcialmente resolvidos**; outros aspectos de início de vigência, transição, adicionais, alçadas, faturamento e precedência permanecem em revisão. CRM-REV-08 continua **minuta**, sem aceite.

## 15. Gratuidade administrativa de adicionais por concessão separada (03/11/2026)

- [CRM-DEC — Adicionais gratuitos](CRM-DEC-gratuidade-administrativa-adicionais-especificos.md): **requisito funcional aprovado** para que o Administrador Geral Imagine conceda **usuários extras, armazenamento e pacotes de créditos IA** a organização específica, por **ato distinto da concessão gratuita do plano**.
- O plano gratuito não torna extras automaticamente gratuitos; extras gratuitos não dão direitos ilimitados, não se estendem a outro tenant e não geram cobrança automática no encerramento. O catálogo final, as quantidades, vigência própria, regras para adicionais já pagos e custos de terceiros permanecem pendentes **GRAT-EX-P01..08**.
- Impacta **CRM-02/03 (autoridade administrativa)**, **CRM-04/05 (planos/FIN/CON)**, **CRM-13 (IA)** e **CRM-16 (armazenamento)**. **GRAT-P05 parcialmente resolvido**. CRM-REV-08 ainda **minuta não aprovada**.

## 16. Decisão sobre prazos dos extras e quantidade de créditos IA (03/11/2026)

- [CRM-DEC — Vigência dos adicionais e créditos IA](CRM-DEC-gratuidade-adicionais-vigencia-e-pacotes-ia.md): aprovado **prazo determinado ou indeterminado para usuários adicionais e armazenamento** gratuitos; aprovado **pacote de IA com quantidade definida**, sem fixar validade ou volume padrão.
- Direitos são **independentes por organização e por concessão**; não gerar isenção global dos adicionais ao conceder plano. Ao encerrar, **não iniciar cobrança automática** sem nova contratação expressa.
- **GRAT-EX-P02/P03/P05 parcialmente resolvidas**, não fechadas. Prazos padrão, quantidades, acumulação, regras de inadimplência/retorno ao limite, pacotes IA e custo continuam pendentes. **CRM-REV-08 permanece minuta não aprovada**.

## 17. Situação mais recente — IA aprovada e segurança/infraestrutura em revisão (03/11/2026)

- [CRM-REV-08](CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md) **aprovada como base documental**, conforme [CRM-DEC](CRM-DEC-aprovacao-REV-08-base.md). **IA-P01..15 e a validade dos pacotes de créditos continuam em aberto**.
- [CRM-REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md) **elaborada como minuta não aprovada**, com 34 regras cotejadas, 14 riscos, 18 questões para especificação/decisão e 16 cenários futuros. A revisão reconcilia os seis P0 IN-168 (ARQ-01/02/03, SEG-01/02, AGT-01), **sem duplicá-los**.
- Priorizar o **fechamento real dos contratos, permissões e arquitetura P0**, não aprovações infinitas de novas minutas; preservar revisão comercial final de valores e recursos já combinada. A CRM-REV-09 não libera Codex.
- Trechos cronológicos anteriores deste índice que descrevem REV-08 como “minuta” retratam **o estado da época em que foram redigidos**, e foram superados pelo aceite específico atual.

## 18. CRM-REV-09 aprovada e abertura do fechamento efetivo ARQ-01 (03/11/2026)

- [CRM-REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md) foi **aprovada como base documental**, conforme [CRM-DEC](CRM-DEC-aprovacao-REV-09-base.md); permanecem as 18 questões OPS-P01..18, incluindo seis P0 IN-168 já existentes.
- [P0-ARQ-01](P0-ARQ-01-topologia-fronteiras-plano-fechamento.md) foi preparada como **matriz/instrumento de fechamento, ainda NÃO APROVADA**, com 12 domínios, 10 relações, 12 itens de inventário/especificação e 10 cenários futuros. A diretriz Opção B já está aprovada; fornecedores, recursos, orçamento e confirmação de ativos reais continuam sem decisão.
- **ARQ-01 permanece P0 parcial**. O próximo trabalho é levantar o inventário factual ARQ01-F01 e fechar o desenho físico/sua análise de custo, sem provisionamento. ARQ-02/ARQ-03/SEG-01/SEG-02/AGT-01 e INT G01–G06 seguem abertos.
- As referências históricas anteriores deste índice refletem situações datadas; o estado atual é esta seção. **Sem programação**.

## 19. P0-ARQ-01 aprovada como instrumento e inventário preliminar F01 (03/11/2026)

- [P0-ARQ-01](P0-ARQ-01-topologia-fronteiras-plano-fechamento.md) foi **aceita como instrumento documental de fechamento**, conforme [CRM-DEC](CRM-DEC-aprovacao-P0-ARQ-01-instrumento.md), **sem homologar a topologia física nem resolver ARQ-01**.
- [ARQ01-F01](P0-ARQ01-F01-inventario-factual-ativos-e-servicos.md) foi **preparado como inventário preliminar não homologado**: 13 ativos e domínios, seis diretrizes consolidadas, 12 itens de conferência. Relatos anteriores de dedicado WHM/cPanel com CentOS 7 versus referência a AlmaLinux 10 e VPS n8n Leaseweb 6vCPU/16GB/200GB constam como **informações históricas sujeitas a conferência**, não estado comprovado.
- Próxima etapa: conferir inventário factual com fonte atualizada, dimensionar serviços/custos e completar ARQ01-F02 de alocação física. Não reabrir separações Portal×CRM, agentes×n8n, Sites Cloudflare ou APIs independentes. **ARQ-01 e demais P0/G01..06 continuam abertos**.
- Não executar programação, acesso a servidores, contratação ou testes reais nesta fase.

## 20. Próximo pacote de revisão proposto

**CRM-REV-01: núcleo de identidade multiempresa e permissões.** Confrontar [01](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md), [03](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md), [04](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md), [13](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md) e IN-151/167/168 de `main`; preservar `tenant_id` + RLS já aprovado no capítulo posterior, contextualizar administração Imagine vs administração da empresa, convites, MFA, auditores, poderes de suporte e contratos de autorização em filas/APIs/IA.

O próximo pacote será **documental para revisão**, não aprovação de schema ou programação. Após ele, seguir para **CRM-REV-02: planos, trial, faturamento e benefício CRM Básico**, incluindo revisão de preços/serviços apenas no fechamento comercial acordado.

**P0 do CRM global ainda não concluídos; [INT P0](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) e [IN-168](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-168-matriz-pendencias-fechamento-arquitetura.md) seguem independentes e abertos.** Nenhum teste ou programação realizada.
