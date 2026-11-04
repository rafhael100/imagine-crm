# CRM-DEC — Arquitetura compartilhada de orquestração e agentes do ecossistema Imagine

**Data:** 03/11/2026.
**Manifestação do responsável:** “ok, aprovado” em resposta à proposta de consolidar a arquitetura dos agentes do ecossistema Imagine, preservando o n8n em servidor separado dos agentes técnicos.
**Estado:** **DIRETRIZ FUNCIONAL/ARQUITETURAL APROVADA PARA O PLANEJAMENTO**, sem homologação de capacidade, localização, fornecedor, custos, ferramentas ou liberação de programação.

## 1. Decisão principal — governança central, execução isolada quando necessário

- O ecossistema Imagine pode ter **governança central dos agentes de IA**, catálogo, políticas, custos, modelos homologados, auditoria, observabilidade e autorização, com **serviços compartilhados onde a segregação e os requisitos de continuidade o permitirem**.
- **Não reunir indiscriminadamente todos os agentes e o n8n em uma única VPS.** Preservar a decisão **Opção B** já aprovada no ARQ-01: VPS/servidor exclusivo dos **sete agentes técnicos especializados, Supervisor Técnico e orquestrador técnico**, **fisicamente/virtualmente separado do servidor/VPS do n8n**.
- **Um n8n central compartilhável** poderá orquestrar workflows e integrações comuns a vários produtos, com segregação lógica de workflows, credenciais, logs, responsáveis, ambiente e permissões, **desde que um evento de falha ou saturação do n8n não quebre fluxos críticos que exigem independência**.
- **Agentes funcionais de cada produto são distintos dos agentes técnicos transversais.** Um agente de atendimento/vendas do CRM, um agente de produção dos Sites, um agente de Performance e um tutor da Universidade têm ferramentas, dados, políticas, limites e ciclo de vida próprios. **A aprovação não determina que todos os seus processos/motores de execução residirão na VPS dos agentes técnicos**; a hospedagem lógica/física de cada família ainda será dimensionada e homologada.

## 2. Produtos incluídos no planejamento

| Produto | Integração com serviços centrais | Autonomia e limites preservados |
|---|---|---|
| **CRM Imagine** | n8n para workflows/integradores autorizados e serviços técnicos transversais | Motor de atendimento, vendas, IA de clientes, créditos, tenant_id, RBAC/MFA, dados, filas e SLA próprios; acesso trans-tenant proibido |
| **Portal Imagine** | Integrações, orquestração de serviços e agentes técnicos comuns quando autorizados | Ambiente/banco próprios, independente de falha do CRM/n8n |
| **Imagine Sites** | Publicação, manutenção e recuperação por APIs, GitHub e n8n/agentes homologados | Sites em Cloudflare Workers Static Assets, sem dependência de n8n para servir a página; APIs principais/contingência de leads independentes |
| **Imagine Performance AI** | Pode consumir APIs e componentes transversais autorizados | **Motor de IA, execução de campanhas, integração e processamento técnicos independentes**, como aprovado em PERF-ARQ-01; não deslocar operação central ao n8n compartilhado; ciclo comercial/contratual próprio |
| **Universidade Imagine** | Tutor educacional futuro e integração autorizada ao Portal/CRM | Desenvolvimento posterior ao Portal e CRM; benefício **somente para clientes com serviço Imagine elegível contratado**, não público nem produto isolado, ciclo de acesso ligado ao serviço; tutor sem privilégios operacionais dos outros produtos |

O planejamento da Universidade não implica criação imediata de recursos computacionais ou de tutor; a integração comercial/física dos produtos deve continuar sujeita aos seus próprios documentos.

## 3. Topologia lógica de referência

1. **n8n / VPS dedicada**: workflows compartilháveis, conectores homologados, execução orientada a eventos, observabilidade própria, credenciais particionadas; sem usar permissões globais para os produtos.
2. **Agentes técnicos / VPS diferente do n8n**: sete especialistas + Supervisor Técnico + orquestrador/autorizador, conforme IN-167/IN-168; ferramentas tipadas, autonomia N1/N2/N3 e aprovações humanas para ações críticas.
3. **Runtimes funcionais de IA específicos dos produtos**: executados nos motores/servidores dos respectivos produtos ou serviços isolados dimensionados separadamente; podem compartilhar APIs de modelos e componentes de governança sem compartilhar dados ou segredos indevidamente.
4. **CRM e Portal**: servidores/bancos independentes; sites hospedados na Cloudflare conforme IN-096; Performance AI possui núcleo próprio. Univers. futura sem disponibilidade pública.
5. **APIs de recepção de leads principal e contingência**: infraestruturas e persistência durável realmente independentes do n8n, CRM, Portal, agentes e de cada outra; confirmação somente após gravação durável, reenvio/reconciliação idempotentes.
6. **Monitoramento e alertas críticos**: caminho independente dos agentes, n8n, Portal e CRM; evitar que falha do supervisor suprima incidentes.
7. **Gestão de identidades/segredos e autorizações**: permissões mínimas por produto, ambiente e tenant; nenhum agente recebe root global, credenciais compartilhadas irrestritas ou acesso ao banco de outros projetos.

**Dois servidores para n8n e agentes técnicos não são o número total de servidores do ecossistema**, nem impõem servidor por agente. Runtimes específicos, bancos, filas, mensageria, armazenamento, APIs e monitoramento precisam de estimativa física própria.

## 4. Condições de segurança e continuidade

- Contratos de API/eventos com autenticação, correlação, idempotência, validação de origem e autorização por empresa/produto/ação; segregação de ambientes dev/homologação/produção.
- Controles de consumo e custos por produto/cliente/operador; distinguir despesas de agentes técnicos centrais dos créditos/limites de IA consumidos no CRM e nos demais produtos.
- Filas duráveis e entrega auditável quando houver operações críticas; n8n não é repositório único de leads ou única fonte do resultado oficial de integrações, cobrança e CRM.
- Circuit breaker, timeouts, cotas, prioridades e limites de concorrência entre produtos; evitar saturação de Performance AI impactando CRM/Sites.
- Central de Aprovações, trilhas de auditoria, playbooks homologados e revisão humana nas ações críticas; segredos em cofre, segregação e rotação.
- Analisar cenários: indisponibilidade de n8n, de VPS de agentes, de serviço de modelo IA, de produto individual, vazamento de credencial, congestionamento por produto e incidente regional; serviço essencial deve continuar ou degradar de forma declarada e segura.

## 5. Pendências sem aprovação automática

- Escolher provedor/instância/região, recursos CPU/RAM/IOPS, nº de workers e filas, estratégia de dados e versão n8n, custos mensais e crescimento.
- Decidir quais workflows são realmente compartilháveis, quais módulos/agents funcionais permanecem em runtime dedicado e quais precisam de instância n8n separada por capacidade/risco.
- Definir SLAs, RPO/RTO, resiliência, volume de jobs, mensageria, dados armazenados e observabilidade; **n8n central não pode se tornar ponto único dos fluxos críticos**.
- Revisar custos de todos os produtos em orçamento global separado do custo de infraestrutura do CRM E1B.
- Conciliar especificações dos outros produtos em suas fontes oficiais quando identificadas; esta decisão transversal no repo CRM **não afirma automaticamente que repositórios de outros produtos foram atualizados**.
- Completar ARQ-01, ARQ-02/03, SEG-01/02, AGT-01, INT e demais P0; a aprovação documental **não fecha** a arquitetura física.

**Regra final:** nenhuma programação, provisionamento, contratação, alteração de DNS/Cloudflare ou servidor, instalação ou execução de testes reais. Aprovação registrada exclusivamente como diretriz de arquitetura e planejamento.
