# CRM-DEC — Estratégia de infraestrutura econômica e escalável do CRM Imagine

**Data:** 03/11/2026.
**Decisão do responsável:** “ok, vamos fazer dessa forma”, em resposta à proposta de infraestrutura por etapas, priorizando qualidade e segurança com menor investimento durante a fase sem clientes.
**Status:** **DIRETRIZ ESTRATÉGICA DE FASEAMENTO APROVADA**; fornecedor, localidade, SKUs, preços e arquitetura física de produção **NÃO HOMOLOGADOS**.
**Âmbito:** somente CRM Imagine SaaS. Não autoriza programação, contratação, provisionamento, migração, configurações em servidores ou testes reais enquanto não houver aprovação global do planejamento.

## 1. Estratégia aprovada

1. **Planejamento atual (zero clientes):** não contratar novos servidores; finalizar especificações, pendências P0, revisão global e versão aprovada no GitHub antes de desenvolvimento.
2. **Desenvolvimento e homologação após autorização expressa:** preferir uma **Leaseweb VPS 1** nova ou aproveitar infraestrutura já disponível apenas se isolamento, segurança e capacidade forem verificados. Ambiente de desenvolvimento/homologação segregado de produção, com dados fictícios ou anonimizados e credenciais próprias. VPS 1 é referência de porte, não SKU contratado.
3. **Piloto e primeira produção com clientes reais:** como candidato econômico, **Leaseweb VPS 2 exclusiva para Laravel/API/processadores** e **uma máquina independente para PostgreSQL** (VPS 1 como hipótese inicial a dimensionar; VPS 2 ou serviço gerenciado como alternativas). Nenhum único servidor de produção deve ser confundido com alta disponibilidade. Produção depende de homologação de segurança, isolamento, backups, contratos de recuperação e aceitação do risco de pontos únicos de falha.
4. **Cloudflare:** camada frontal para DNS/SSL/WAF/CDN e proteção, conforme elegibilidade, configuração e custos; não se presume hospedar integralmente Laravel/PostgreSQL no Cloudflare.
5. **Arquivos e anexos:** Object Storage privado compatível S3, separado do disco da aplicação; Leaseweb Object Storage e Cloudflare R2 são candidatos. Escolha depende de localização, LGPD, preços, operações, permissões e requisitos de retenção. As quotas funcionais já aprovadas permanecem.
6. **Backups:** cópias externas criptografadas, independentes do servidor principal e testáveis, com restauração segura; PostgreSQL com backup e, quando homologado, PITR; anexos com replicação assíncrona e retenção técnica conforme regras vigentes. Snapshot de VPS, RAID e sincronização simples não substituem backup recuperável.
7. **Crescimento por métricas e demanda:** considerar Leaseweb Public Cloud, dedicados ou arquitetura híbrida apenas quando custos, capacidade medida, latência, concorrência, RPO/RTO e disponibilidade justificarem. Sem gatilho arbitrário baseado apenas no número de clientes.
8. **Separação de sistemas já aprovada preservada:** CRM independente do Portal Imagine, do n8n e dos agentes técnicos/Supervisor; captação de leads com APIs principal e de contingência e persistências duráveis independentes; monitoramento/alerta independente da própria IA e do painel.
9. **Banco e conectividade:** aplicação e banco na mesma região lógica sempre que possível para reduzir latência; conexões TLS, proteção e autorização multiempresa/tenant_id/RLS; se for VPS sem rede privada nativa, estudar VPN/túnel seguro, rotação de credenciais e pontos de falha. A escolha efetiva fica pendente.
10. **Preferência comercial:** Leaseweb é o **provedor prioritário para cotação/estudo**, levando em conta relacionamento existente do responsável com a empresa desde 2012. Comparar Washington (EUA), Montreal (Canadá) e regiões europeias com medições de latência para usuários brasileiros, localização de dados, LGPD, disponibilidade, custo total e requisitos técnicos. Washington é hipótese de estudo, não região aprovada.

## 2. Referências NÃO homologadas

| Etapa | Referência de porte | Compromisso |
|---|---|---|
| Planejamento | Sem infraestrutura nova | **Diretriz aprovada** |
| Desenvolvimento | VPS 1 (ou infraestrutura comprovadamente isolada) | **Porte candidato; não contratado** |
| Primeiro ambiente real | VPS 2 para backend + banco independente | **Arquitetura candidata; não aprovada para produção** |
| Crescimento | Public Cloud / dedicado / híbrido | **Opção futura dependente de medição** |

Não fixar como obrigação valores observados em promoções, conversas ou comparadores (por exemplo US$ 6,29 / US$ 9,89, € 86,88 ou meta agregada US$ 30–60). Tais números são **ilustrações não vinculantes**, podem depender de localização, fidelidade e adicionais e **não representam TCO**. Não considerar gratuitos: Object Storage, PITR, backup externo, VPN, logs, monitoramento, IPs, suporte, tráfego excedente, agentes, IA, WhatsApp, filas e ambientes adicionais.

## 3. O que esta aprovação substitui — e o que não substitui

- **Substitui a preferência técnica anterior por Vultr São Paulo como primeira candidata** no documento [CRM-DEC de novo servidor](CRM-DEC-infraestrutura-novo-servidor-e-recomendacao-provedor.md). Vultr permanece alternativa comparativa, não fornecedora escolhida. A decisão de servidor **novo e exclusivo para a aplicação CRM** permanece.
- **Não substitui** a topologia lógica aprovada, isolamento CRM/Portal/n8n/agentes, proteção das APIs de leads, regras do PostgreSQL/arquivos/backups, segurança, LGPD, políticas de acesso, monitoramento ou aprovações necessárias para execução.
- **Não encerra ARQ-01** nem os P0 ARQ-02, ARQ-03, SEG-01, SEG-02, AGT-01; não resolve CORE/INT ou gates G01–G06.
- **Não libera** desenvolvimento com Codex, testes em ambiente, compras, uso de servidores produtivos ou implantação. A autorização final exige planejamento global consolidado e aprovado.

## 4. Pendências para fechamento posterior de ARQ-01

- Confirmar catálogos, preço final e SLA de Leaseweb VPS 1/VPS 2 por região, prazo e tráfego, incluindo proteção de acesso e custos totais.
- Medir latência real Brasil↔regiões candidatas e avaliar transferência internacional de dados pessoais.
- Dimensionar cargas/concorrência e PostgreSQL (CPU, RAM, IOPS, espaço, evolução); avaliar banco VPS vs gerenciado, RPO/RTO por serviço, PITR, restauração e contingência.
- Definir armazenamento privado, backups externos/imutáveis, monitoramento independente, cofre, filas, isolamento de homologação, política de atualização, incidentes e retenção.
- Fazer matriz de riscos de VPS não redundante e de ausência de rede privada nativa, e definir requisitos mínimos de produção antes de aceitar primeiros dados reais.
- Cotejar com [P0-ARQ-01](P0-ARQ-01-topologia-fronteiras-plano-fechamento.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md), [CRM-REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md) e [STATUS](STATUS-PRONTIDAO-INICIO-DESENVOLVIMENTO-2026-11-03.md).

**Resultado:** estratégia econômica progressiva aprovada; escolha física, preços e contratação pendentes. **Nenhuma programação ou alteração de infraestrutura autorizada.**
