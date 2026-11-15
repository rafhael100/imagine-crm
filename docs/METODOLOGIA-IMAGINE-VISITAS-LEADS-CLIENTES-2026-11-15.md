# Imagine · Visitas → Leads → Clientes — alinhamento imagine-crm

**Diretriz estratégica de 15/11/2026 — planejamento e comunicação, sem implementação operacional.** Referência central: [Comercial F330 / PR #416](https://github.com/rafhael100/imagine-comercial/pull/416). Trilha de conteúdos: [Universidade / PR #3](https://github.com/rafhael100/imagine-universidade/pull/3).

## Papel deste projeto
Ser a fonte operacional dos **Leads** e das oportunidades que podem virar **Clientes**, com funis, follow-up, qualificação e automação supervisionada.

## Como contribui em cada pilar
- **Visitas:** Registrar origem e contexto do contato **apenas quando recebidos de forma autorizada**; não capturar tráfego do site diretamente sem contrato e consentimento.
- **Leads:** Captar e deduplicar leads com fonte, data, consentimento e dono empresarial definidos; qualificação e SLA; estados e regras de RLS/tenant.
- **Clientes:** Distinguir lead, negócio em negociação, negócio ganho e cliente confirmado; acompanhar relacionamento, retenção e satisfação com definições auditáveis.

A ordem ajuda a contar a história, mas negócios reais podem começar em qualquer ponto. **Visita não é cliente e lead não é venda.** Não prometer resultados nem transformar conceitos de marketing em métricas sem evidências.

## Aplicação discreta na experiência
No painel do CRM, a assinatura 'Visitas → Leads → Clientes' deve servir de orientação, mas o KPI principal do CRM fica em **Leads** e **Clientes**. 'Visitas' deve ser um indicador externo e opcional, nunca inferido da quantidade de leads.

### Indicadores candidatos
Leads novos, qualificados, oportunidades por estágio, tempo de primeiro contato, negócios ganhos, origem confiável, taxa de conversão por coorte. Evitar taxas com denominadores incompatíveis.

### Barreiras e autorizações
Sem alterar schema, RLS, automações, APIs, LGPD ou ligar fontes produtivas por esta diretriz.

A diretriz é transversal e editorial: **nenhuma** captura, envio de mensagens, criação de campanhas, alteração de negócio, contrato financeiro ou deploy foi autorizada apenas por sua aprovação. Conservam-se limites existentes de RLS, LGPD, MFA, consentimento, auditoria, backup e aprovação independente.

## Próximas ações propostas (não executadas)
1. Revisão de UX, inclusão discreta da assinatura quando realmente relevante, sem poluição visual nem mudança de cores do Portal.
2. Definição de contratos de eventos/dados, dicionário de métricas e fontes autorizadas.
3. Testes com dados sintéticos e revisão independente de segurança e privacidade.
4. Aprovação própria antes de qualquer integração operacional ou publicação.

**Status: documento em PR DRAFT, sem merge/deploy, sem dados reais ou cobrança.**
