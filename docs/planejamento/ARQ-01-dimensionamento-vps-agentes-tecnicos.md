# ARQ-01 — Estudo de Dimensionamento e Custos da VPS dos Agentes Técnicos

**Estado:** ESTUDO TÉCNICO E FINANCEIRO PRELIMINAR — cenários comparados em **02/11/2026**, **sem escolha definitiva de capacidade ou provedor**.  
**Autorização recebida:** responsável respondeu "ok" para prosseguir à etapa de **dimensionamento comparativo** após aprovar, no ARQ-01, **VPS/servidor exclusivo para os sete agentes técnicos, o Supervisor e o orquestrador, separado do n8n**.  
**Fase:** **planejamento e documentação**; não iniciar programação, configurar ambientes de produção, contratar VPS, alterar serviços existentes ou criar despesas.  
**Referências:** [ARQ-01 — Escolha da arquitetura](ARQ-01-comparativo-infraestrutura-agentes-tecnicos.md), [IN-168 — Matriz de pendências](IN-168-matriz-pendencias-fechamento-arquitetura.md), [IN-167 — Arquitetura consolidada](IN-167-consolidacao-arquitetura-agentes-tecnicos.md), IN-132/137/138/142/151/153/155/156/157/158/159/160/162/165/166 e IN-096.

## 1. Premissas fundamentais

1. Os agentes técnicos são **sete especialistas e um Supervisor Técnico**, como papéis lógicos; seu número não define automaticamente oito processos de inferência simultânea nem oito servidores.
2. **Hipótese de dimensionamento:** modelos de linguagem externos utilizados **por API**, sem GPU, inferência de modelo grande ou treinamento local. Se houver mudança nessa premissa, a capacidade precisa ser reavaliada.
3. A VPS dos agentes executará componentes de **orquestração, roteamento de tarefas, gateway autorizado para ferramentas, processamento de trabalhos, integrações e armazenamento estritamente necessário**, conforme arquitetura técnica posterior; não hospedará o CRM SaaS, o Portal Imagine nem o n8n.
4. **n8n** possui VPS independente planejada (referência histórica de 6 vCPU, 16 GB RAM e 200 GB NVMe, Ubuntu 24.04/Docker/PostgreSQL). Esses recursos não constituem capacidade disponível para os agentes.
5. **Sites de clientes Onepage/Landing Pages** ficarão em **Cloudflare Workers Static Assets**, com GitHub e publicação automatizada, não em WHM/cPanel (IN-096).
6. **Portal Imagine e CRM Imagine SaaS** usarão servidores distintos entre si e da VPS de agentes.
7. **APIs de ingestão de leads principal e contingência** mantêm suas infraestruturas de recepção e persistência durável independentes; falha da VPS de agentes não deve impedir recepção, persistência ou alertas críticos determinísticos.
8. O número de tarefas simultâneas, frequência de incidentes, volume de histórico, tecnologias de persistência, carga real, região, latência, uptime desejado, orçamento e RTO/RPO **ainda não foram medidos/escolhidos**. Toda configuração abaixo é **proposta de avaliação**, não garantia de capacidade.

## 2. Três cenários referenciais

| Cenário | vCPU | RAM | SSD local de referência | Adequação prevista |
|---|---:|---:|---:|---|
| **A — Econômico** | 2 | 4 GB | 80 GB | Desenvolvimento/homologação ou produção muito leve, dependendo de validação; menor margem para picos de tarefas |
| **B — Intermediário / recomendado como hipótese-base** | **4** | **8 GB** | **160 GB** | Orquestração e integrações com tarefas paralelas moderadas, modelos acessados por API e persistência estritamente dimensionada |
| **C — Expansão** | 8 | 16 GB | 320 GB | Concorrência, processamento de documentos, logs e ferramentas mais exigentes, caso métricas justifiquem |

**Recomendação técnica preliminar:** estudar **4 vCPU, 8 GB RAM e ~160 GB SSD** como tamanho de referência inicial; evitar contratar cenário C antes de conhecer as cargas. A configuração **não está aprovada para compra/implantação**. Se serviços de observabilidade, banco de dados de agentes, execução de navegadores, indexação pesada ou processamento local de documentos rodarem nessa VPS, o consumo poderá ser maior que o previsto: ajustar arquitetura e recursos.

### Reservas de capacidade sugeridas para validação futura

- Não destinar todo o recurso contratado a workers; preservar margem para sistema operacional, agentes de segurança, logs, manutenção e picos.
- Impor limites de concorrência por agente e por ferramenta, com backpressure, orçamento e bloqueio de loops.
- Prever filas com persistência e mecanismos de recuperação, dimensionados por carga e requisitos de falha; a tecnologia de fila/banco ainda não está escolhida.
- Diferenciar disco de sistema/artefatos e dados históricos com retenção específica; o SSD da VPS não substitui backup externo.
- Monitoramento, alertas e mecanismos de emergência não devem depender exclusivamente de um processo nessa mesma VPS.

## 3. Preços públicos de referência de provedores (pesquisa de 02/11/2026)

**As linhas abaixo são preços anunciados de referência, NÃO cotações vinculantes nem custo total de operação**. Localidade, disponibilidade, impostos, câmbio, backups, IPv4, snapshots, banda excedente, logs, observabilidade e modelos de IA podem acrescentar custos. Não converter para reais sem cotação atual e condições fiscais.

| Cenário | DigitalOcean — Basic Regular (USD/mês) | Hetzner — CPX em Europa (USD/mês, sem IPv4) |
|---|---:|---:|
| A: 2 vCPU / 4 GB / 80 GB | **US$ 24** | **US$ 22,99** (CPX22) |
| B: 4 vCPU / 8 GB / 160 GB | **US$ 48** | **US$ 41,99** (CPX32) |
| C: 8 vCPU / 16 GB / 320 GB | **US$ 96** | **US$ 81,99** (CPX42) |

**Fonte DigitalOcean:** tabela pública oficial de Droplets Basic Regular, https://www.digitalocean.com/pricing/droplets (consultada em 02/11/2026). A empresa caracteriza Basic como **CPU compartilhada**, apropriada a cargas variáveis; se forem necessários recursos consistentes sob saturação, avaliar opção com CPU dedicada e custos próprios.

**Fonte Hetzner:** tabela oficial de reajuste efetivo em **15/06/2026**, Europa (FSN/NBG/HEL), https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/ (consultada em 02/11/2026). Os preços acima **excluem IVA e IPv4**; IPv4 primário custa **US$ 0,60/mês** se contratado, conforme https://docs.hetzner.com/cloud/servers/primary-ips/overview/. Localização e disponibilidade do produto precisam ser verificadas antes de uma contratação.

**Fonte Leaseweb:** https://www.leaseweb.com/en/products-services/cloud/virtual-private-server/ (consultada em 02/11/2026). A Leaseweb oferece VPS, NVMe, opções de backup/firewall e recursos configuráveis, mas **a pesquisa não obteve tabela pública comparável para os três tamanhos**. Solicitar preço efetivo para a região e condições desejadas; **não inventar cotação Leaseweb**.

**Não incluído:** tarifas das APIs de IA, backups externos, armazenamento adicional, suporte gerenciado, licenças, impostos aplicáveis no Brasil, câmbio, tráfego excedente, monitoramento e tratamento de dados pessoais.

## 4. Comparação de fornecedores e critérios de escolha

| Critério | DigitalOcean | Hetzner | Leaseweb |
|---|---|---|---|
| Transparência da referência de preço | Planos e valores públicos | Valores públicos regionais, atenção ao IPv4 | Cotação do pacote/localidade a obter |
| Cenário B, sem extras | US$ 48/mês | US$ 41,99/mês em Europa, sem IPv4 | Pendente de cotação |
| Localidade e latência | Escolher região real suportada | Preço de tabela acima é **Europa**; avaliar latência e jurisdição | Avaliar região contratável |
| Continuidade | VPS isolada do n8n; **não é HA automática** | VPS isolada do n8n; **não é HA automática** | VPS isolada do n8n; **não é HA automática** |
| Comparação justa | Considerar CPU compartilhada/dedicada, backup, tráfego e suporte | Considerar CPU compartilhada, IPv4 e localidade | Considerar mesma capacidade, backup, tráfego, contrato e impostos |

Não escolher provedor somente pelo menor preço: considerar operação no Brasil, distância de APIs, privacidade/LGPD, tempo de suporte, limites de CPU, snapshots, qualidade da infraestrutura, isolamento por provedor e custo total.

## 5. Modelo de custos a preencher antes da aprovação financeira

**Custo mensal completo da VPS dos agentes =** preço da VPS + IPv4 (se aplicável) + backup externo e restauração + armazenamento/tráfego extra + observabilidade + segurança/manutenção + custos dos **modelos externos e ferramentas**.

Os custos de IA dependem de volume de tarefas, tokens, modelos, provedores e política de execução; **não estão embutidos no preço do servidor**. Os custos internos dos agentes técnicos **não devem consumir franquias/créditos de IA dos clientes do CRM** (IN-158).

Os serviços das APIs principal/contingência, do CRM SaaS, do Portal Imagine e do n8n serão dimensionados em **itens separados** do orçamento global para evitar misturar custos de plataformas e gastos dos agentes.

## 6. Segurança, continuidade e operação segura

1. Hospedar agentes em **VPS exclusiva**, diferente da VPS n8n, conforme Opção B já aprovada. A separação de hosts reduz falhas correlacionadas, mas uma única VPS para agentes ainda pode ser ponto único de falha **do subsistema de agentes**.
2. Aplicar menor privilégio, acesso por operação e escopo, credenciais em cofre, ferramentas com autorização determinística independente da IA, ambientes distintos e auditoria (IN-151/166).
3. **Uma aprovação humana quando exigida para operações críticas, sem dupla aprovação**. Procedimentos seguros homologados podem executar automaticamente apenas no escopo autorizado (IN-137/138/155).
4. Não instalar os agentes como dependência síncrona de confirmação durável das duas APIs de leads, da persistência do CRM ou dos alertas essenciais.
5. Verificar backups externos, recuperação, retenção de logs e conhecimento, failover do modelo (não confundir com failover da VPS), inspeção de vulnerabilidades e limites de custo.
6. Avaliar latência da região do servidor com os serviços/fornecedores efetivamente usados; evitar supor região e preço idênticos para todos os provedores.
7. Recuperação e alta disponibilidade **dependem de arquitetura, redundância, testes e RTO/RPO próprios**, ainda pendentes.

## 7. Informações que faltam e decisões separadas

**Pendências de medição:** número de incidentes/dia, tarefas/hora, picos de simultaneidade, duração e consumo por tipo de tarefa, uso de ferramentas de navegador/arquivos, retenção e tamanho dos bancos/logs, capacidade desejada de recuperação.

**Pendências de infraestrutura:** qual provedor e região, disponibilidade dos planos, CPU compartilhada versus dedicada, sistema operacional (Ubuntu 24.04 LTS é opção consistente com infraestrutura n8n existente, mas **ainda não fixado**), runtime/orquestrador, banco, fila, observabilidade, autenticação entre servidores e estratégias de backup.

**Pendências financeiras:** teto mensal de servidor, orçamento separado de consumo das APIs de IA, despesas adicionais de armazenamento/IPv4/logs, moeda/câmbio/impostos, contratos e custos de escalabilidade.

**Próximo ponto de decisão proposto:** aprovar, se desejado, **4 vCPU, 8 GB RAM e aproximadamente 160 GB SSD como referência de dimensionamento da VPS exclusiva dos agentes**, sem decidir provedor/compra. Depois comparar **cotações reais** da Leaseweb e de alternativas, conforme região e custos globais. Não confundir aprovação dessa referência com implantação ou garantia de desempenho.

**Encerramento do estudo:** comparação e **recomendação preliminar** concluídas; dimensionamento final, provedor, custos totais, contratação, RTO/RPO e implementação **não aprovados**. **Programação não iniciada.**
