# ARQ-01 — Comparação de infraestrutura dos agentes técnicos Imagine

**Status:** **OPÇÃO B APROVADA COMO DIRETRIZ ARQUITETURAL em 02/11/2026**: VPS/servidor exclusivo para agentes técnicos e orquestrador, **separado da VPS do n8n**. **Sem aprovação de contratação, implantação, orçamento, tamanho ou provedor**.  
**Motivação:** responsável autorizou **iniciar a comparação** de arquiteturas para fechar o item ARQ-01 da [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md).  
**Fase:** planejamento/documentação; **não iniciar programação, provisionar servidores, instalar serviços, conceder acessos ou contratar recursos**.  
**Referências:** [IN-167](IN-167-consolidacao-arquitetura-agentes-tecnicos.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md), [IN-124](IN-124-integracao-inteligente-formularios-leads.md), [IN-143](IN-143-continuidade-operacional-recuperacao-inteligente-desastres.md), [IN-153](IN-153-inventario-inteligente-infraestrutura-dependencias.md), [IN-156](IN-156-autodiagnostico-recuperacao-agentes-tecnicos.md), [IN-157](IN-157-gestao-inteligente-modelos-ia-contingencia.md), [IN-158](IN-158-monitoramento-inteligente-custos-consumo-agentes.md), [IN-159](IN-159-central-inteligente-observabilidade-saude-operacional.md), [IN-166](IN-166-gestao-inteligente-ferramentas-permissoes-agentes.md).

## 1. Premissas documentadas e verificações pendentes

- **Hospedagem dos novos sites Onepage/Landing Pages Imagine: Cloudflare Workers Static Assets**, conforme **IN-096 aprovada em 01/11/2026**, com GitHub para versionamento e publicação automatizada por integrações/APIs e agentes autorizados. A proposta anterior de **AlmaLinux + WHM/cPanel para esses sites foi substituída** e não deve reaparecer como arquitetura atual. **Workers for Platforms** é somente evolução futura, sujeita a nova análise/aprovação. A **VPS Leaseweb prevista, Ubuntu 24.04 LTS + Docker, 6 vCPU, 16 GB RAM e 200 GB NVMe**, permanece referência para n8n/PostgreSQL. Estes são dados de planejamento, não comprovação de recursos já instalados ou de capacidade ociosa.
- Duas APIs de captação de formulários, **principal e contingência**, com persistência durável independente, separação efetiva de infraestrutura e preferência por fornecedores distintos, conforme IN-124. Não usar orquestrador, servidor dos agentes, n8n ou painel administrativo como único caminho obrigatório de aceitação durável de leads.
- Sete agentes especializados e um Supervisor **são papéis lógicos**; não equivalem a oito processos continuamente executando nem a oito servidores separados.
- Agentes podem utilizar modelos de IA remotos contratados mediante aprovação; **nenhum requisito de GPU ou modelo local foi aprovado**, portanto não dimensionar GPU por suposição.
- Controles de risco, permissões, orçamento e alertas devem ser independentes das decisões geradas pelo modelo. A operação essencial de APIs/filas deve continuar mesmo com falha dos agentes.
- Não há medições reais aprovadas de carga simultânea, CPU/RAM efetivamente livres, volume do banco n8n, picos de workflow, modelo de filas, custo de VPS adicional, RTO/RPO nem orçamento de IA.

## 2. Alternativas sob comparação

### Opção A — Reaproveitar a VPS do n8n (isolamento lógico)

**Conceito:** executar n8n/PostgreSQL e serviços de agentes técnicos/orquestração no mesmo host VPS, em containers/processos e redes separados, com credenciais e papéis distintos.

**Vantagens:** sem contratação imediata de outro servidor; menos infraestrutura física/virtual para administrar; comunicação local com n8n quando adequada; potencial economia se a carga medida couber com folga.

**Riscos/limites:** compartilhamento de CPU, RAM, IOPS, disco, rede e manutenção; saturação de agente pode degradar n8n e banco, e vice-versa; falha da VPS interrompe simultaneamente os dois subsistemas; risco operacional maior em caso de comprometimento do host; backups, logs e dados disputam o armazenamento disponível. Contêineres separados **não são isolamento entre hosts nem garantia de alta disponibilidade**.

**Mitigações mínimas se escolhida futuramente:** limites técnicos reais de CPU/RAM/concorrência, cotas de armazenamento, redes e credenciais independentes, bancos/esquemas e contas isolados, monitoramento externo, alarmes de saturação, backups e restauração, priorização dos serviços existentes, e limites para execução de ferramentas dos agentes. Definir limites **por medição**, não assumir que 16 GB de RAM sustentem qualquer número de tarefas.

**Não recomendar:** para execução de agentes com amplos privilégios em produção, processos intensivos/imprevisíveis, orquestração cuja indisponibilidade comprometa operação de n8n, ou ambiente sem capacidade comprovada e controles adequados.

### Opção B — Servidor/VPS separado para agentes e orquestrador

**Conceito:** reservar infraestrutura própria aos serviços de orquestração, agentes, políticas/autorizador e armazenamento estritamente necessário; manter n8n em sua VPS e CRM SaaS em infraestrutura segregada.

**Vantagens:** recursos e manutenção independentes; melhor fronteira de segurança; escalabilidade dos agentes sem pressionar a base n8n/PostgreSQL; políticas e rotinas de backup/monitoramento separadas; falha da VPS n8n não necessariamente derruba os agentes e vice-versa.

**Limites/riscos:** nova despesa fixa/variável e administração adicional; integrações pela rede exigem autenticação, criptografia e tratamento de indisponibilidade; uma VPS própria para agentes **não significa alta disponibilidade automática**, sobretudo com banco, observabilidade e autorizador alojados no mesmo host ou no mesmo provedor. O monitoramento de falhas do próprio host ainda requer verificador independente.

**Mitigações mínimas se escolhida futuramente:** identidade técnica de menor privilégio para chamadas ao n8n/CRM/APIs; integração restrita por endpoints e ações; não compartilhar credenciais administrativas; backups e telemetria seguros; planejamento de contingência do orquestrador; análise de custo total antes da contratação.

### Comparativo

| Critério | Opção A — VPS n8n compartilhada | Opção B — Infraestrutura independente |
|---|---|---|
| Novo custo fixo | Tendência menor, desde que não exija upgrade | Tendência maior; depende do provedor/capacidade |
| Recursos | Disputados com n8n/PostgreSQL | Dedicados aos agentes |
| Falha do host | Afeta n8n e agentes conjuntamente | Pode afetar apenas um deles |
| Isolamento | Processos/containers no mesmo host | Fronteira de host separada |
| Manutenção | Menos hosts, maior acoplamento operacional | Mais hosts, manutenção autônoma |
| Crescimento | Concorre com carga das automações e banco | Dimensionamento próprio |
| Segurança | Compartilha riscos de kernel/host/infra | Fronteira adicional, com cuidados de rede |
| Alta disponibilidade | **Não garantida** | **Também não garantida** apenas por adicionar um host |
| Adequação preliminar | Baixa carga, homologação e custo mínimo | Agentes ativos contínuos e menor acoplamento de produção |
| Validação | Medição da capacidade livre, testes de contenção e risco | Custo, acessos, continuidade e dimensionamento |

## 3. Recomendação técnica para decisão

**A Opção B está aprovada como arquitetura-alvo para operação produtiva dos agentes técnicos**, principalmente devido a: separação de falhas entre orquestrador e n8n, independência de manutenção, menor disputa de recursos e segurança proporcional à capacidade dos agentes de atuar sobre infraestrutura/integrações.

**A aprovação arquitetural não é aprovação de compra, tamanho da VPS, provedor, orçamento ou implantação.** A Opção A fica preservada apenas como alternativa analisada historicamente, **não escolhida** para o ambiente planejado; não adotá-la como solução padrão sem nova decisão expressa.

Possibilidade de **transição planejada**: preservar os limites arquiteturais por serviços mesmo se uma avaliação econômica futura escolher hospedagem compartilhada temporária. Migração precisa de plano próprio; **não foi aprovada estratégia de implantação em fases**.

## 4. Separação proposta por domínio (aplicável às duas opções)

| Domínio | Diretriz |
|---|---|
| Sites Onepage/Landing Pages dos clientes | **Cloudflare Workers Static Assets**, CDN/HTTPS, GitHub para versões e publicação via APIs/automação controlada; sem WHM/cPanel nem banco local de leads. |
| CRM SaaS | Infraestrutura de aplicação e banco segregada da hospedagem de sites e do ambiente de ferramentas. |
| API principal de leads | Recepção e persistência durável independentes, separadas do Portal e agentes. |
| API de contingência de leads | Outra infraestrutura e armazenamento durável independente da API principal. |
| n8n e sua base | VPS de automações, sem exigir agentes para receber/confirmar leads. |
| Orquestrador/agentes | **Opção B aprovada:** servidor/VPS exclusiva para agentes e orquestrador, **distinta do n8n**; dimensionamento e aquisição pendentes. |
| Autorizador/credenciais | Política e segredo fora do controle textual do modelo; detalhamento SEG-01/SEG-02 pendente. |
| Central de observabilidade | Única visão integrada; coletor e alertas críticos independentes da UI e dos próprios agentes. |
| Armazenamento do conhecimento dos agentes | Segregação por cliente/ambiente e ciclos de retenção IN-163/164; tecnologia pendente. |

## 5. Requisitos de segurança e continuidade antes da escolha

- **Mensurar** picos de CPU, RAM, IOPS, armazenamento, tráfego, quantidade e simultaneidade de workflows e chamadas a ferramentas.
- Identificar **correlações de falha**: mesmo host, zona de disponibilidade, provedor, banco, credencial, rede, backup ou controle administrativo podem anular a suposta independência.
- Definir contas/ambientes de desenvolvimento, homologação e produção e controle de credenciais por serviço.
- Garantir mecanismo independente para bloquear chamadas a ferramentas sem permissão e sem homologação; **um único aceite humano em ação crítica quando necessário**, sem dupla aprovação.
- Manter operações essenciais determinísticas e alertas prioritários disponíveis independentemente de IA, orquestração e painel.
- Não inserir n8n ou agentes como dependência obrigatória da **primeira confirmação de persistência durável** de um lead.
- Separar armazenamento de n8n, dados do CRM e conhecimentos/auditorias dos agentes; dimensionar backup e retenção.
- Verificar se alterações de provedores exigem novas despesas, aprovações e implicações de LGPD/transferência de dados.

## 6. Informações ainda necessárias para fechar ARQ-01

1. Estado real **confirmado** das contas/projetos Cloudflare Workers Static Assets e GitHub, do Portal Imagine, da VPS n8n, PostgreSQL, CRM SaaS e das duas APIs (planejado, contratado, instalado, em produção), sem presumir uso de WHM/cPanel para os sites.
2. Medições de capacidade disponível, picos, tamanho de bancos/disco e utilização do n8n; sem inventar headroom.
3. Estimativa de volume de incidentes, usuários/empresas e ações simultâneas dos agentes; necessidades de ferramentas.
4. Alternativas reais de infraestrutura independente, custos mensais, custo de backups, tráfego, monitoramento e manutenção.
5. Risco aceitável de parada conjunta n8n/agentes e avaliação da necessidade de isolamento por provedor/zona.
6. Definição posterior dos RTO/RPO por serviço e custos de atendê-los (IN-144), sem usar os prazos de assunção de incidentes IN-135 como substitutos.
7. **Resolvido em 02/11/2026:** a alternativa **B foi expressamente aprovada** para agentes. Resta dimensionar/configurar a infraestrutura, validar dependências e aprovar eventual contratação/despesa antes de considerar todo o ARQ-01 tecnicamente fechado.

## 7. Fontes técnicas de referência (não decisões de compra)

- Documentação Docker, limites de recursos CPU/RAM e riscos de OOM: https://docs.docker.com/engine/containers/resource_constraints/
- Documentação n8n, modo de filas e workers: https://docs.n8n.io/deploy/host-n8n/configure-n8n/scaling/enable-queue-mode.md
- Documentação n8n, problemas de memória em ambientes self-hosted: https://docs.n8n.io/deploy/host-n8n/configure-n8n/scaling/fix-memory-issues.md

**Resultado da análise:** **comparação concluída e Opção B aprovada como diretriz arquitetural**. Provedor, capacidade, custos, contratos e implantação continuam pendentes.  
**ARQ-01 na IN-168:** **separação dos agentes aprovada**; especificação de infraestrutura, capacidade, custos e verificação do ambiente real permanecem pendentes.  
**Programação:** **não iniciada**.


## Atualização de arquitetura — Portal e CRM (02/11/2026)

**Decisão aprovada:** hospedar o **Portal Imagine** e o **CRM Imagine SaaS** em **servidores separados**, com aplicações e dados isolados. A integração entre os dois ocorrerá por APIs autenticadas e com permissões restritas. A indisponibilidade do Portal não deverá interromper o funcionamento do CRM, e a recepção durável de formulários continuará independente do Portal, preservando a separação entre API principal e contingência da IN-124.

**Distribuição lógica prevista (revisada pela IN-096):** (1) sites dos clientes em **Cloudflare Workers Static Assets** (plataforma, não VPS dedicada para sites), (2) Portal Imagine em servidor próprio, (3) CRM Imagine SaaS em servidor distinto, (4) n8n em VPS própria, (5) agentes técnicos e orquestrador — **VPS/servidor independente APROVADO como diretriz**, (6) API principal com persistência durável própria, (7) API de contingência com persistência durável própria e infraestrutura independente da principal. **São sete grupos de serviços, não sete servidores necessariamente contratados.** Os provedores e custos de cada bloco ainda dependem de dimensionamento. **Programação não iniciada.**


## Correção de arquitetura aprovada — hospedagem Imagine (02/11/2026)

O responsável reafirmou a **IN-096**, já aprovada em 01/11/2026: os novos sites Onepage/Landing Pages Imagine serão publicados em **Cloudflare Workers Static Assets**, com **GitHub** para código/versões, deploy e operação automatizados por **APIs, n8n e agentes de IA sob autorização**, sem **WHM/cPanel** como hospedagem padrão dos sites dos clientes. **Cloudflare Workers for Platforms** permanece opção futura para escala, dependente de análise e aprovação. Sites estáticos não guardam leads localmente; captação ocorre pelas **duas APIs independentes** do IN-124. A infraestrutura dos **sites dos clientes** não deve ser confundida com os servidores separados do Portal Imagine, CRM Imagine SaaS, n8n ou agentes técnicos. A decisão não autoriza migração, publicação, contratação ou programação.


## Aprovação expressa posterior — Opção B / agentes separados do n8n (02/11/2026)

O responsável respondeu **"sim"** à proposta de adotar **uma VPS exclusiva para os agentes técnicos de IA e o orquestrador, em servidor diferente da VPS de n8n**. A **Opção B está formalmente escolhida para o planejamento**. A Opção A (compartilhar a mesma VPS do n8n) deixa de ser alternativa ativa. Permanece válida a separação anteriormente aprovada entre **Portal Imagine** e **CRM SaaS**; hospedagem de **sites Onepage/Landing Pages em Cloudflare Workers Static Assets (IN-096)**; **API principal e API de contingência de leads** com persistência e infraestrutura próprias e independentes (IN-124).

**Pendente:** provedor e localização, dimensionamento CPU/RAM/armazenamento, sistema operacional e runtime, isolamento de serviços e segredos, alta disponibilidade ou contingência, custos fixos/variáveis, orçamento e contratação; inventário real e RTO/RPO. **Não provisionar, não contratar e não programar enquanto o planejamento não estiver concluído e aprovado**.


## Estudo preliminar de tamanho e custo da VPS de agentes (02/11/2026)

O responsável autorizou a **comparação de capacidade e custos**, não a compra ou a escolha definitiva do porte. Foi concluído o [estudo de dimensionamento e custos de VPS exclusiva dos agentes](ARQ-01-dimensionamento-vps-agentes-tecnicos.md), com três referências: **2 vCPU/4 GB/80 GB (econômica), 4 vCPU/8 GB/160 GB (recomendada como hipótese-base) e 8 vCPU/16 GB/320 GB (expansão)**. Foram levantados preços de tabela de **DigitalOcean** e **Hetzner Europa**; cotação **Leaseweb ainda pendente**, assim como região, capacidade real, backups e custos das APIs de IA.

**ARQ-01:** isolamento dos agentes na VPS própria **já aprovado**, mas **capacidade definitiva, provedor, preço contratado, custo total e implantação continuam pendentes**. Valores e configurações do estudo **não equivalem a orçamento aprovado ou autorização de programação**.
