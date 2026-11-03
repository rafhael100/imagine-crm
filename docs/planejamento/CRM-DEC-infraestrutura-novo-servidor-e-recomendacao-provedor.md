# CRM-DEC — Novo servidor para o CRM e recomendação de hospedagem

> **ATUALIZAÇÃO DE PRECEDÊNCIA EM 03/11/2026:** a [estratégia econômica escalável com prioridade de estudo da Leaseweb](CRM-DEC-estrategia-infra-economica-escalavel-leaseweb-2026-11-03.md) foi aprovada posteriormente. **A indicação Vultr São Paulo abaixo é referência técnica histórica, não mais a opção preferencial vigente.** Segue válida a decisão de CRM em servidor independente e novo. Não há fornecedor, SKU, preço ou contratação homologados.

**Registro:** 2026-11-03.
**Manifestação literal do responsável:** “iremos colocar o projeto em outro servidor. iremos colocar no que voce recomendar”.
**Contexto:** resposta ao inventário ARQ01-F01 que mencionava a VPS histórica Leaseweb dedicada ao n8n. O objetivo é não vincular a nova aplicação **CRM Imagine SaaS** à infraestrutura antiga do n8n.
**Status:** **DECISÃO DE PROJETO CONFIRMADA: CRM em servidor próprio, novo e independente**. **PROPOSTA TÉCNICA PREFERENCIAL:** Vultr Cloud Compute High Performance, região São Paulo, para hospedar a aplicação do CRM; **sem compra, contrato, implantação, pré-aceite financeiro ou fechamento físico ARQ-01**.

## Alcance da orientação do responsável

- O **CRM Imagine** será executado em um **servidor/VPS novo e exclusivo da aplicação CRM**, independente do servidor de n8n, do Portal Imagine e da VPS de agentes técnicos, preservando as diretrizes anteriores aprovadas.
- A resposta delega uma **recomendação técnica de escolha**, mas não implica autorização de pagamento, aquisição de uma VPS ou confirmação de que uma oferta está disponível no momento da contratação.
- O servidor legado dedicado/WHM-cPanel **não** será usado para os novos sites Imagine, que permanecem Cloudflare Workers Static Assets + GitHub conforme IN-096.
- Para produção SaaS, a **separação de servidor não resolve isoladamente alta disponibilidade**, e o banco PostgreSQL não deve compartilhar automaticamente host/limites de recursos com a aplicação sem avaliação do risco.

## Recomendação preferencial — aplicação CRM

| Campo | Proposta para validação (não homologada nem contratada) |
|---|---|
| Provedor/region | **Vultr, São Paulo (Brasil)**, para proximidade dos usuários brasileiros e opção de região doméstica |
| Produto | **Cloud Compute High Performance AMD**, instância independente |
| Porte inicial candidato | **4 vCPU compartilhadas, 8 GB RAM, 180 GB NVMe** |
| Preço público de referência | **US$ 48/mês** de servidor, antes de impostos, câmbio, backups, banco gerenciado, armazenamento de objetos, excedentes e ferramentas |
| Sistema operacional | **Ubuntu 24.04 LTS** como hipótese-base a homologar (não pressupõe instalação) |
| Aplicação | Laravel, PHP-FPM, Nginx, workers de fila autorizados, tarefas agendadas, métricas e Redis/Valkey conforme sizing |
| Banco de dados | **PostgreSQL separado do servidor de aplicação**, preferindo serviço gerenciado na mesma região e conectado por rede privada, **sujeito à disponibilidade e cotação**; caso indisponível, avaliar VPS separada de banco ou outro provedor |
| Anexos | Object Storage privado compatível com S3, não dependente apenas do disco local de aplicação; localidade e custo a confirmar |
| Backups | Cópias externas criptografadas, PITR conforme plano do PostgreSQL, teste futuro de restauração e políticas próprias CRM |
| Condições prévias | Estimativa de empresas, usuários simultâneos, volume de dados/leads, carga das filas, tráfego, custo total e integração n8n/Portal/IA |

**Alternativa de expansão (não contratar agora):** Vultr High Performance AMD 8 vCPU/16 GB/350 GB NVMe em São Paulo, preço público de referência **US$ 96/mês**. O upgrade depende de medição de CPU/memória, consultas PostgreSQL, concorrência e consumo de I/O. São especificações com **CPU compartilhada**, não garantia de núcleos dedicados.

**Alternativa de orçamento:** Vultr Cloud Compute Regular 4 vCPU/8 GB/160 GB SSD em São Paulo por **US$ 40/mês** como referência; menor custo com disco SSD regular. A recomendação para o CRM, salvo evidência posterior, é o plano **High Performance 4/8/180 NVMe** de US$ 48/mês.

## Fontes de preços e disponibilidade examinadas em 03/11/2026

- Catálogo de terceiros atualizado de configurações de Vultr São Paulo, com origem atribuída à API pública de catálogo do provedor: https://www.rackcensus.com/vps-providers/vultr-benchmarks/locations/sao . **Não é cotação comercial vinculante; não há benchmark medido desse plano nessa região**.
- Documentação oficial Vultr de provisionamento de Cloud Compute e perfis High Performance: https://docs.vultr.com/products/compute/instances/cloud-compute/provisioning .
- Vultr informa que os valores podem variar por região e devem ser conferidos na página de preços/console antes da compra: https://docs.vultr.com/support/platform/billing/is-pricing-the-same-in-all-data-center-locations .
- Vultr PostgreSQL gerenciado, com PostgreSQL/PgBouncer/backup/PITR conforme plano, mas **a existência de um SKU elegível em São Paulo e seu custo total não foram confirmados por cotação**: https://docs.vultr.com/product-overviews/postgresql e https://docs.vultr.com/products/storage/databases/postgresql/provisioning .

## Orçamento: custo público do servidor não é custo total da plataforma

| Despesa | Status de orçamento |
|---|---|
| VPS principal CRM High Performance 4/8/180 | **US$ 48/mês como preço de referência**, sujeito a cotação real |
| PostgreSQL gerenciado ou VPS separada de banco | **A cotar** por capacidade, PITR, failover, suporte e disponibilidade na região |
| Armazenamento de anexos privado / backup externo | **A cotar** por GB, transações, retenção e tráfego |
| Redis/Valkey, observabilidade, firewall, alertas e tráfego | **A dimensionar**; não presumir tudo gratuito |
| n8n, Portal, API principal, API contingência e agentes técnicos | **Custos e hosts separados**; não incluídos na VPS CRM |
| Impostos, câmbio e eventuais serviços adicionais | **Não incluídos** |

**Estimativa de capacidade:** configuração 4 vCPU/8 GB da aplicação é **hipótese de partida**, não garantia para quantidade X de empresas/clientes. O CRM inclui atendimento WhatsApp, funis, agenda, IA, anexos e fluxos; volume por tenant e simultaneidade podem alterar radicalmente os recursos. Serviços de IA com modelo externo devem manter o processamento intensivo fora do host de aplicação, conforme arquitetura.

## Falhas e controles obrigatórios

- Aplicação e PostgreSQL devem falar por **rede privada + TLS e acesso restrito**, com backups externos, rotação de segredos e isolamento dos ambientes de homologação/produção.
- **Tenant_id e RLS** no PostgreSQL continuam obrigatórios conforme CRM-REV-01; servidor exclusivo não substitui segurança multiempresa.
- A API principal/contingência para leads deve ser **independente da indisponibilidade do CRM/Portal/agents** para aceite durável; somente confirmar entrega final quando o CRM atestar a gravação.
- Um **único servidor de aplicação** não entrega alta disponibilidade; produção com exigência de SLA alto poderá precisar de múltiplas instâncias, load balancer, banco HA, observabilidade externa e recuperação independente. Isso será dimensionado com custo e RPO/RTO aprovados.
- Administração central, benefício de planos gratuitos e faturamento pertencem ao CRM/FIN/CON autorizados, não ao provedor nem aos agentes técnicos.

## Pendências para homologação ARQ01-F02

| ID | Decisão/levantamento pendente |
|---|---|
| HOST-P01 | Confirmar que o projeto a hospedar neste servidor é especificamente **CRM Imagine SaaS**; não mover a infraestrutura n8n existente só por referência histórica |
| HOST-P02 | Cotar instância High Performance 4/8/180 em São Paulo, impostos, disponibilidade e plano de escala |
| HOST-P03 | Confirmar oferta, custo e SLA de PostgreSQL gerenciado em São Paulo; se indisponível, avaliar DB em VPS isolada |
| HOST-P04 | Estimar cargas e conexões dos primeiros clientes, filas/Redis e picos para homologar porte inicial |
| HOST-P05 | Definir backups, PITR, armazenamento de anexos e retenção do CRM separada dos Sites |
| HOST-P06 | Custos mensais completos + falhas comuns entre Vultr, n8n, Portal, APIs de leads e provedores externos |
| HOST-P07 | Aprovar RPO/RTO por serviço, topologia física, segurança, IAM, cofre, homologação e CI/CD |
| HOST-P08 | Registrar aprovação expressa da escolha física e do orçamento global antes de provisionar ou contratar |

**Próximo passo documental:** vincular esta preferência à [ARQ01-F01 — Inventário](P0-ARQ01-F01-inventario-factual-ativos-e-servicos.md) e à futura especificação **ARQ01-F02 — alocação física e orçamento por serviço**. A [P0-ARQ-01](P0-ARQ-01-topologia-fronteiras-plano-fechamento.md) **continua P0 parcial**, assim como ARQ-02/03, SEG-01/02, AGT-01, CORE-P0, INT-P0 e G01–G06.

**Não fazer nesta fase:** contratar, provisionar, acessar servidores, instalar, programar, realizar testes de produção, faturar ou mudar infraestrutura. A liberação do desenvolvimento depende do planejamento completo, versionado e aprovado globalmente.
