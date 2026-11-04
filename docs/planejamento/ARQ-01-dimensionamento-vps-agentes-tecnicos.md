# ARQ-01 — Estudo de Dimensionamento e Custos da VPS dos Agentes Técnicos

**Estado:** REFERÊNCIA INICIAL DE DIMENSIONAMENTO **4 vCPU/8 GB/~160 GB SSD APROVADA PARA PLANEJAMENTO** em 03/11/2026 ([registro de decisão](CRM-DEC-aprovacao-dimensionamento-vps-agentes-4vCPU-8GB-160GB-2026-11-03.md)); cenários, fornecedor, SKU, custo e desempenho efetivo permanecem em estudo, **sem compra ou homologação técnica**.  
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

**Encerramento do estudo (atualização 03/11/2026):** referência inicial **4 vCPU/8 GB/~160 GB aprovada para planejamento**; dimensionamento físico/SKU, provedor, custos totais, contratação, RTO/RPO e implementação **não aprovados**. **Programação não iniciada.**


## 8. Atualizacao transversal — dimensionamento n8n e agentes para todos os produtos (03/11/2026)

**Estado desta seção:** aprovada **somente** a referência de VPS de agentes **4 vCPU/8 GB/~160 GB** ([registro](CRM-DEC-aprovacao-dimensionamento-vps-agentes-4vCPU-8GB-160GB-2026-11-03.md)); os demais itens de dimensionamento, custos, licença n8n, configuração, retenção e homologação seguem **proposta para revisão**, sem alterar a Opção B ou a [arquitetura transversal aprovada](CRM-DEC-arquitetura-central-agentes-n8n-ecossistema-imagine-2026-11-03.md).

### 8.1. Confronto com inventario e fronteiras

- A [F01](P0-ARQ01-F01-inventario-factual-ativos-e-servicos.md) registra **referencia historica**, nao comprovacao operacional, de n8n em VPS Leaseweb Ubuntu 24.04 + Docker/PostgreSQL, **6 vCPU/16 GB/200 GB NVMe**. **Prioridade: conferir se a VPS existe/esta ativa, seu custo, versao n8n, limites e carga real** antes de propor nova compra. Nao presumir capacidade livre.
- Os sete especialistas tecnicos + Supervisor sao **papeis logicos**, sem GPU nem inferencia pesada local por padrao. Candidato base permanece **4 vCPU, 8 GB RAM e ~160 GB SSD**, fisicamente/virtualmente **separado da VPS n8n**, com API de modelos externos e ferramentas escopadas.
- **Nao confundir VPS n8n + VPS agentes tecnicos com toda infraestrutura de IA.** CRM tem motores funcionais/limites por empresa; Portal e Sites operam APIs proprias; **Performance AI tem motor de IA, campanhas e processamento independentes**; Universidade futura tem tutor educacional e acesso restrito a clientes elegiveis. Nao colocar todos os runtimes dos produtos na VPS tecnica.
- Regras de separacao seguem IN-167/168; agentes nao fazem persistencia unica de leads nem fornecem canal unico de alertas criticos; as APIs receptoras principal/contingencia persistem independentemente e n8n nao e o registro financeiro/comercial oficial.

### 8.2. Sizing comparativo candidato

| Componente/etapa | Referencia economica | Referencia inicial de avaliacao | Gatilho de escala/limite |
|---|---|---|---|
| **n8n — VPS existente se homologada** | Reutilizar a VPS que ja e paga, **sem contratar outra** | **6 vCPU/16 GB/200 GB** como inventario historico; Postgres n8n e processos n8n restritos a esse ambiente | Disputa CPU/RAM, lentidao webhooks, tempo de execucao, disco/IOPS, perda de backups, indisponibilidade, workflows altos por produto |
| **Agentes tecnicos — servidor proprio** | **2 vCPU/4 GB/80 GB** apenas para homologacao ou baixissima carga, sem navegador/indexacao pesada | **4 vCPU/8 GB/~160 GB** para orquestracao tecnico-operacional, modelos por API, 7 papeis + Supervisor; nao requer 8 servidores | CPU/RAM, filas, jobs concorrentes, navegador headless, documentos, monitoramento, modelo local e custo por token |
| **Agentes tecnicos — expansao** | Nao contratar antes da necessidade | **8 vCPU/16 GB/~320 GB** como referencia anterior de escala, nao SKU especifica | Crescimento de tarefas ativas, memoria, ferramentas, tempo ate resposta e risco de afetar servicos |
| **Motores funcionais dos produtos** | Preferir APIs de modelos e servicos ja homologados, sem comprar VPS de IA para cada agente | Cada produto roda seu proprio motor/runtimes quando requerido por seguranca, consumo ou disponibilidade | Performance AI requer capacidade separada para campanhas; CRM nao herda custos nem restricoes de outro produto |

**Importante sobre produtos Leaseweb:** o plano VPS 1 antes cotado nos EUA como 4 vCPU/**6 GB**/100 GB **nao equivale** automaticamente ao candidato de agentes **4 vCPU/8 GB/160 GB**. Exigir proposta com RAM, IOPS, armazenamento e preco reais adequados; nem todas as ofertas possuem configuracao intermediaria. Se o fornecedor oferecer 6 vCPU/16 GB/200 GB com melhor relacao custo/seguranca, estudar como alternativa; nao aprovar so porque e barato.

**Nenhum limite fixo de execucoes simultaneas pode ser prometido com esses numeros sem provas de carga.** Para homologacao, testar ao menos perfis de tarefa leve (HTTP/webhook), intermediaria (agente com varias ferramentas), pesada (documentos ou navegador), falha de API/modelo e sobrecarga entre produtos. Coletar p50/p95, CPU/RAM, tempo de fila, erros, tokens e custo por tarefa; thresholds finais pendentes.

### 8.3. n8n: configuracao segura e plano de escalabilidade

1. **Preferir reaproveitar VPS existente** se configuracao, versao n8n, postura de seguranca, Postgres da propria instancia, espaco/backup e consumo real estiverem conformes. Ter banco n8n **nao significa** poder usar seu Postgres como banco CRM.
2. Separar execucoes de workflows internos por produto e ambiente, com identidade, credenciais, limites e auditoria; **verificar edicao/licenca real antes de afirmar que recursos nativos de projetos/RBAC estao disponiveis**. Quando a edicao nao der isolamento forte, **nao armazenar credenciais abrangentes multiempresa**: usar APIs gateway com tokens escopados, autorizacao por tenant e segredos fora dos workflows. Se isolamento exigido nao for atingivel, criar instancia/servidor separado ou adquirir recurso/licenca adequada.
3. Nao habilitar por reflexo **queue mode**, varios workers ou Redis. Primeiro medir carga e verificar suporte/licenciamento da edicao. No modo regular, n8n possui controle de concorrencia de execucoes de producao; isso **nao transforma n8n em fila duravel oficial de leads**. Se necessario, estudar Postgres+Redis/queue-mode+workers com chaves de criptografia partilhadas de maneira segura e monitoramento, respeitando condicoes atuais de licenciamento.
4. Fazer backup **dos workflows, PostgreSQL n8n, credenciais e da chave mestra de criptografia**, em dominios de falha distintos. Sem a chave, backup do banco pode nao permitir recuperar as credenciais. Estabelecer retencao de dados de execucoes e limpeza/pruning conforme politica de auditoria/LGPD; detalhes por versao.
5. Acesso administrativo protegido com MFA quando suportado, reverse proxy/TLS, firewall, rotacao de segredos, SSH minimo, controle de nodes/Code/Execute Command e SSRF, node community sob homologacao. Evitar publicar API administrativa e impedir comandos livres dos clientes.
6. Manter o processamento de campanha Performance AI **no motor tecnico independente aprovado**, com apenas chamadas autorizadas para workflows compartilhaveis; nao colocar cargas pesadas ou tenants externos na mesma fila do n8n interno.
7. Manter alertas essenciais por canal externo ao n8n e aos agentes, com caminhos de contingencia e registros de operacoes oficiais nos sistemas responsaveis.

### 8.4. PENDENCIA CRITICA — LICENCA n8n EM PRODUTO SAAS

Em **06/10/2026**, o proprio n8n esclareceu que a Community Edition sob Sustainable Use License permite workflows **criadas e geridas internamente pela equipe Imagine** para clientes; mas **se clientes configurarem/construirem seus workflows baseados no n8n**, ate por tela propria, API, MCP ou agente atuando pelo cliente, e necessaria **licenca comercial**. O FAQ cita Enterprise para esse caso. Isso e particularmente relevante ao **construtor de automacoes SaaS multiempresa** planejado para o CRM.

**Duas opcoes de arquitetura para avaliacao, sem decisao automatica:**
- **Opção L1 (recomendada para estudo):** motor de automacoes do CRM **proprio e independente do n8n**; o n8n fica como orquestrador de processos internos Imagine, cuja edicao Community pode ser elegivel se uso e limites estiverem nos termos; interfaces de cliente jamais configuram workflows n8n de forma indireta.
- **Opção L2:** usar n8n como motor de criacao/configuracao de automacoes pelo cliente **somente depois de consultar e orcar licenca comercial adequada**, mais isolamento, RBAC, segredos, volume e suporte. Pode inviabilizar o orcamento inicial; nao pressupor licenca gratuita ou direito de incorporacao.

**Nao tomar L1 como licenca juridicamente homologada** sem conferir desenho efetivo. Para caso concreto: solicitar validacao a **license@n8n.io** e cotacao a **sales@n8n.io** se houver embutimento/cliente configurando workflows.

Fonte oficial: https://support.n8n.io/article/can-i-use-your-license-for-my-use-case

### 8.5. Comparacao de custo incremental, sem inventar precos

| Linha | Inicio economico | Risco/custo adicional |
|---|---|---|
| VPS n8n | **R$ / US$ 0 de compra incremental** se ja contratada e aprovada para reutilizacao | Mensalidade **ja existente** precisa constar no TCO; sem auditoria nao dizer que e gratuita |
| VPS dos agentes tecnicos | **1 nova VPS candidata** (4 vCPU/8 GB/~160 GB, provedor e preco pendentes) | Storage, backup, monitoramento, seguranca, VPN e custo operacional |
| n8n licenca | Community **somente se uso coberto** pela SUL | Se CRM/Portal fornecerem edicao/configuracao de workflows n8n a clientes: Enterprise/comercial possivelmente custosa |
| IA por API | Pagar por uso de tokens e ferramentas por operacao, com limites e circuito de custos | Modelos, navegadores, ferramentas, documentos; separar custo tecnico Imagine de creditos de clientes |
| Escala | Aumentar worker, CPU/RAM/IOPS ou criar instancia separada **quando necessidade for medida** | Se servico exigir HA/redundancia, uma VPS por camada nao basta |

**Pontos de controle para posterior aprovacao:** INV-02 (verificar VPS n8n existente), capacidade/cotacao do servidor de agentes, separacao de credenciais/produtos, licenciamento n8n e motor do construtor de CRM, resiliencia e dimensionamento por metricas. **Nao contratacao, programacao, alteracao em VPS ou testes reais nesta fase de planejamento.**

**Fontes tecnicas:**
- n8n queue mode: https://docs.n8n.io/hosting/scaling/queue-mode/
- n8n concurrency regular: https://docs.n8n.io/hosting/scaling/concurrency-control/
- n8n security: https://docs.n8n.io/hosting/securing/overview/
- n8n pruning: https://docs.n8n.io/hosting/scaling/execution-data/
- n8n licenca: https://support.n8n.io/article/can-i-use-your-license-for-my-use-case
