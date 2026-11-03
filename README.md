# imagine-crm

> **Situação em 03/11/2026:** planejamento do CRM Imagine em consolidação. **Não iniciar programação** antes de revisão global e aprovação expressa da documentação final. Este README é um índice de navegação, não uma declaração de conclusão.

## Ponto de partida para acompanhar o projeto

- [Diagnóstico de prontidão para iniciar desenvolvimento](docs/planejamento/STATUS-PRONTIDAO-INICIO-DESENVOLVIMENTO-2026-11-03.md) — fontes verificadas, lacunas do CRM completo, critérios de início.
- [CRM-MASTER-01 — Inventário das áreas do CRM e fontes por branch](docs/planejamento/CRM-MASTER-01-indice-modulos-fontes-status.md) — 20 áreas CRM e 2 relacionadas, com status e lacunas.
- [CRM-REV-01 — Núcleo multiempresa, usuários, MFA e permissões](docs/planejamento/CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) — base documental aprovada; dez lacunas técnicas abertas.
- [CRM-DEC — Aprovação da CRM-REV-01](docs/planejamento/CRM-DEC-aprovacao-REV-01-base.md).
- [CRM-REV-02 — Planos, trial, cobranças e benefício Básico](docs/planejamento/CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) — base documental aprovada; preços finais ainda em revisão.
- [CRM-DEC — Aprovação da CRM-REV-02](docs/planejamento/CRM-DEC-aprovacao-REV-02-base.md).
- [CRM-REV-03 — Leads, contatos, campos e portabilidade](docs/planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md) — base documental aprovada; pendências DAT-P01..12 abertas.
- [CRM-DEC — Aprovação da CRM-REV-03](docs/planejamento/CRM-DEC-aprovacao-REV-03-base.md).
- [CRM-REV-04 — Atendimento, WhatsApp e gestão de conversas](docs/planejamento/CRM-REV-04-atendimento-whatsapp-conversas.md) — base documental aprovada, pendências ATD-P01..14 abertas.
- [CRM-DEC — Aprovação CRM-REV-04](docs/planejamento/CRM-DEC-aprovacao-REV-04-base.md).
- [CRM-REV-05 — Chatbot, fluxos, gatilhos e automações](docs/planejamento/CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md) — base documental aprovada; pendências AUT-P01..14 abertas.
- [CRM-DEC — Aprovação da CRM-REV-05](docs/planejamento/CRM-DEC-aprovacao-REV-05-base.md).
- [CRM-REV-06 — Vendas, funis, propostas e catálogo](docs/planejamento/CRM-REV-06-vendas-funis-propostas-catalogo.md) — base documental aprovada; VEN-P01..16 abertos.
- [CRM-DEC — Aprovação da CRM-REV-06](docs/planejamento/CRM-DEC-aprovacao-REV-06-base.md).
- [CRM-REV-07 — Agenda, profissionais, unidades e agendamentos](docs/planejamento/CRM-REV-07-agenda-profissionais-unidades-agendamentos.md) — base documental aprovada, com seis rejeições e AGD-P01..18 ainda abertos.
- [CRM-DEC — Aprovação da CRM-REV-07](docs/planejamento/CRM-DEC-aprovacao-REV-07-base.md).
- [CRM-DEC — Gratuidade administrativa de qualquer plano CRM](docs/planejamento/CRM-DEC-gratuidade-administrativa-qualquer-plano.md) — **requisito funcional aprovado** para qualquer plano de empresa específica, sem site.
- [CRM-DEC — Gratuidade por prazo determinado ou indeterminado](docs/planejamento/CRM-DEC-gratuidade-vigencia-modalidades.md) — **ambas modalidades aprovadas**; sem cobrança automática no término, outras regras de vigência pendentes.
- [CRM-DEC — Gratuidade de adicionais específicos](docs/planejamento/CRM-DEC-gratuidade-administrativa-adicionais-especificos.md) — **requisito funcional aprovado** para concessão separada de usuários extras, armazenamento e pacotes de créditos de IA, sem liberar todos os adicionais automaticamente.
- [CRM-DEC — Vigência de extras e créditos IA quantificados](docs/planejamento/CRM-DEC-gratuidade-adicionais-vigencia-e-pacotes-ia.md) — **aprovado** prazo determinado/indeterminado para usuários e armazenamento; pacote gratuito de IA com quantidade definida e validade ainda a fechar.
- [CRM-REV-08 — Agentes de IA, créditos, conhecimento e supervisão](docs/planejamento/CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md) — **base documental aprovada**, 15 pendências IA e validade dos créditos ainda abertas.
- [CRM-DEC — Aprovação da CRM-REV-08](docs/planejamento/CRM-DEC-aprovacao-REV-08-base.md).
- [CRM-REV-09 — Infraestrutura, segurança, auditoria e operações](docs/planejamento/CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md) — **minuta para revisão** dos bloqueios reais P0 antes de programar.
- [Infraestrutura e agentes técnicos: pendências IN-168](docs/planejamento/IN-168-matriz-pendencias-fechamento-arquitetura.md).
- [Integrações INT-01 a INT-12: índice de documentação](docs/planejamento/integracoes/README.md).
- [Integrações: painel de pendências e gates G01–G06](docs/planejamento/integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md).
- [Portal/Sites: pendências e relações com o CRM](docs/planejamento/PORTAL-01-matriz-pendencias-plataforma-sites.md).

## Histórico que precisa ser reconciliado

- Branch `docs/integracao-site-crm-basico`: documentação inicial de visão geral, planos, trial, empresas/usuários, IA, infraestrutura, integrações, Básico, agenda, permissões e decisões históricas.
- Branch `docs/especificacao-inicial-2026-10-31`: especificação anterior de módulos iniciais.
- Branch `main`: consolidação recente de INT, IN, Portal e agentes. **Não presumir** que a ausência de documento na main significa ausência de decisão em conversa ou branch histórica.

**Regra de precedência:** decisões aprovadas mais recentes devem ser conciliadas documentalmente antes de unificar versões. Não copiar propostas históricas como se fossem decisões vigentes.
