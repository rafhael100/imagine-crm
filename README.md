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
- [CRM-REV-09 — Infraestrutura, segurança, auditoria e operações](docs/planejamento/CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md) — **base documental aprovada**, com bloqueios P0 ainda abertos.
- [CRM-DEC — Aprovação da CRM-REV-09](docs/planejamento/CRM-DEC-aprovacao-REV-09-base.md).
- [P0-ARQ-01 — Topologia, isolamento e plano de fechamento](docs/planejamento/P0-ARQ-01-topologia-fronteiras-plano-fechamento.md) — **instrumento documental aprovado**, sem fechar ARQ-01.
- [CRM-DEC — Aceite do instrumento P0-ARQ-01](docs/planejamento/CRM-DEC-aprovacao-P0-ARQ-01-instrumento.md).
- [ARQ01-F01 — Inventário preliminar de infraestrutura](docs/planejamento/P0-ARQ01-F01-inventario-factual-ativos-e-servicos.md) — **minuta não homologada**; 13 ativos/domínios, declarações históricas e lacunas de verificação real.
- [ARQ01-F02 — Alocação por fases, custos, proteção do banco e contingência](docs/planejamento/P0-ARQ01-F02-alocacao-fisica-fases-custos-e-contingencia.md) — **aprovada como base documental**; [registro de aceite](docs/planejamento/CRM-DEC-aprovacao-ARQ01-F02-base-2026-11-03.md), sem homologação de produção.
- [ARQ01-F03 — PostgreSQL, armazenamento e backups](docs/planejamento/P0-ARQ01-F03-postgresql-backups-dimensionamento.md) — **aprovada como base de planejamento**, sem homologar fornecedor, custo, RPO/RTO nem equipamentos.
- [ARQ01-F04 — Orçamento de Object Storage, backups e continuidade](docs/planejamento/P0-ARQ01-F04-orcamento-object-storage-backup-recuperacao.md) — **aprovada como base documental**, com candidatos R2/Leaseweb/pgBackRest e preços de referência; [registro de aceite](docs/planejamento/CRM-DEC-aprovacao-ARQ01-F04-base-2026-11-03.md).
- [ARQ01-F05 — Continuidade, metas RPO/RTO e custos por serviço](docs/planejamento/P0-ARQ01-F05-continuidade-rpo-rto-e-custos-por-servico.md) — **aprovada apenas como base documental**, com níveis E0/E1/E2; [registro de aceite](docs/planejamento/CRM-DEC-aprovacao-ARQ01-F05-base-2026-11-03.md), sem metas numéricas homologadas.
- [ARQ01-F06 — Metas candidatas RPO/RTO, custos e riscos](docs/planejamento/P0-ARQ01-F06-proposta-metas-rpo-rto-custos-e-riscos.md) — **aprovada como base documental**, sem homologação de metas nem escolha produtiva; [registro de aceite](docs/planejamento/CRM-DEC-aprovacao-ARQ01-F06-base-2026-11-03.md).
- [ARQ01-F07 — Ficha de fechamento: orçamento, riscos e recuperação](docs/planejamento/P0-ARQ01-F07-fechamento-orcamento-riscos-e-recuperacao.md) — **aprovada como guia documental de fechamento**; [registro de aceite](docs/planejamento/CRM-DEC-aprovacao-ARQ01-F07-base-2026-11-03.md). Permanecem pendentes E1B vs E2, orçamento, região, RPO/RTO e aceitação de riscos; ARQ-01 ainda P0 parcial.
- [CRM-DEC — Estratégia econômica escalável Leaseweb (decisão mais recente de 03/11)](docs/planejamento/CRM-DEC-estrategia-infra-economica-escalavel-leaseweb-2026-11-03.md) — **faseamento aprovado**: sem nova contratação no planejamento, VPS 1 candidata para desenvolvimento após autorização, VPS 2 + banco independente como hipótese de primeira produção; Cloudflare/proteções/storage/backups separados; sem homologação física nem programação.
- [CRM-DEC — Novo servidor CRM, histórico da recomendação Vultr](docs/planejamento/CRM-DEC-infraestrutura-novo-servidor-e-recomendacao-provedor.md) — decisão preservada de servidor CRM independente, mas **preferência de fornecedor Vultr substituída por estudo prioritário Leaseweb** conforme documento de estratégia mais recente.
- [Infraestrutura e agentes técnicos: pendências IN-168](docs/planejamento/IN-168-matriz-pendencias-fechamento-arquitetura.md).
- [Integrações INT-01 a INT-12: índice de documentação](docs/planejamento/integracoes/README.md).
- [Integrações: painel de pendências e gates G01–G06](docs/planejamento/integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md).
- [Portal/Sites: pendências e relações com o CRM](docs/planejamento/PORTAL-01-matriz-pendencias-plataforma-sites.md).

## Histórico que precisa ser reconciliado

- Branch `docs/integracao-site-crm-basico`: documentação inicial de visão geral, planos, trial, empresas/usuários, IA, infraestrutura, integrações, Básico, agenda, permissões e decisões históricas.
- Branch `docs/especificacao-inicial-2026-10-31`: especificação anterior de módulos iniciais.
- Branch `main`: consolidação recente de INT, IN, Portal e agentes. **Não presumir** que a ausência de documento na main significa ausência de decisão em conversa ou branch histórica.

**Regra de precedência:** decisões aprovadas mais recentes devem ser conciliadas documentalmente antes de unificar versões. Não copiar propostas históricas como se fossem decisões vigentes.
