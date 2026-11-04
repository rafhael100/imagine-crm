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


## 9. Comparativo atualizado de ofertas — 03/11/2026

**Natureza:** pesquisa de mercado, NAO cotacao comercial, decisao de contratacao, escolha definitiva de SKU, fornecedor ou regiao. **Referência anteriormente aprovada continua 4 vCPU/8 GB/~160 GB SSD**; uma oferta superior pode ser candidata por melhor custo/beneficio, mas exige homologacao. [Registro da referencia](CRM-DEC-aprovacao-dimensionamento-vps-agentes-4vCPU-8GB-160GB-2026-11-03.md).

| Opcao para agentes tecnicos | CPU e RAM | Disco | Preco mensal de referencia | Evidencia / ressalva |
|---|---|---|---|---|
| **Leaseweb VPS 1 — EUA** | 4 vCPU **6 GB** | **100 GB NVMe** | **US$6,29** | Fonte comparadora; **nao atende** a referencia de 8 GB/160 GB; nao comparar como equivalencia |
| **Leaseweb VPS 2 — EUA** | **6 vCPU, 16 GB** | **200 GB NVMe** | **US$9,89** | Fonte comparadora consultada, referencia verificada 01/11/2026; **mais recursos** que referencia aprovada; **sem proposta oficial em checkout e sem comprovacao de prazo/renovacao** |
| **DigitalOcean Basic Regular** | **4 vCPU, 8 GB** | **160 GB SSD** | **US$48** | Valor publicado em pagina oficial, CPU compartilhada, preco mensal base |
| **Hetzner CPX32 — Europa** | **4 vCPU, 8 GB** | **160 GB NVMe** | **US$41,99** | Tabela oficial de reajuste junho/2026; sem IPv4 e impostos, disponibilidade a confirmar |
| **Hetzner CPX31 — EUA** | **4 vCPU, 8 GB** | **160 GB NVMe** | **US$73,49** | Tabela oficial EUA, sem IPv4/impostos, disponibilidade a confirmar |

Fontes e links originais:
- Leaseweb pagina oficial (caracteristicas/seguranca, mas preco final no portal): https://www.leaseweb.com/products-services/cloud/virtual-private-server
- Leaseweb VPS2, **comparador nao vinculante**: https://kuraberu.app/vps/offer/leaseweb-vps-2-us (01/11/2026; EUA, inclusive Washington D.C.)
- Leaseweb VPS1, **comparador nao vinculante**: https://kuraberu.app/vps/providers/leaseweb
- DigitalOcean oficial: https://www.digitalocean.com/pricing/droplets
- Hetzner oficial: https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/
- Hetzner planilha de recursos (EU e EUA): https://www.hetzner.com/european-cloud/

**Recomendacao de estudo:** solicitar/verificar **Leaseweb VPS 2** na regiao US/Washington, porque a tabela de terceiro sugere **6 vCPU/16 GB/200 GB** a preço reduzido. **Nao presumir US$9,89 contratavel**: capturar oferta oficial, modalidade mensal/12/24 meses, pagamento inicial, reajustes, NVMe e IOPS, disponibilidade, impostos e seguro/suporte. A VPS2 e **nova candidata de SKU, nao decisao homologada**, e as 4vCPU/8GB/160GB continuam referencia minima de estudo.

### 9.1. Compatibilidade com a infraestrutura ja declarada

- VPS n8n historica: Leaseweb 6 vCPU/16 GB/200 GB, Ubuntu 24.04/Docker/PostgreSQL; inventario e consumo ainda nao confirmados. **Nao instalar agentes nessa mesma VPS**.
- VPS de agentes: independente do n8n. Se contratar futuramente VPS2 6/16/200, isso pode oferecer margem para picos e scanners, mas CPU compartilhada e IOPS precisam ser medidos; **mais RAM nao garante disponibilidade ou throughput**.
- **Montreal/Canadá:** catálogo externo HostingCompass de 03/11/2026 passou a listar VPS 2 de 6 vCPU/16 GB/200 GB por CA$13,49/mês, com referência de contrato de 12 meses e renovação desconhecida; portanto, existe **evidência de oferta publicada por terceiro**, mas **não comprovação da disponibilidade/valor final no checkout oficial**. Comparar Washington e Montreal com cotação, tributação, LGPD, latência e suporte.
- A Leaseweb informa **protecao DDoS padrao, firewall basico e um snapshot gratuito**, alem de expansao **somente para cima**, conforme pagina oficial. Snapshot do host **nao substitui** backup externo, restauracao isolada, auditoria e monitoring externo.
- Verificar se as tarefas exigem navegadores headless, ingestao pesada de documentos, processamento local ou varias ferramentas concorrentes; se exigirem, dimensionar por medições e alocar recursos independentemente do custo baixo da VPS.

### 9.2. Checklist antes de autorizar gasto

1. Confirmar **cotacao oficial** Leaseweb VPS 2: Washington e eventual alternativa com baixa latencia Brasil; mensal sem fidelidade vs 12/24 meses, setup, impostos, reajuste e limite de CPU.
2. Confirmar de fato a VPS n8n existente, inclusive se os recursos atuais sao 6vCPU/16GB/200GB, faturamento, backups e ocupacao; separar custo **existente** e **incremental**.
3. Confirmar tecnologia de isolamento, cofre de segredos e autorizacoes de ferramentas dos agentes, incluido plano de reversao e procedimentos de falha.
4. Orçar backup externo, monitor externo, rede segura, logging, operacao, suporte, tarifas de modelos de IA/tokens/ferramentas e reserva de crescimento.
5. Preservar independência do CRM, Portal, Sites, Performance AI e universidade futura: o servidor tecnico transversal **nao aloja todos os runtimes funcionais**.
6. Resolver licenciamento do n8n para qualquer automacao do CRM configurada por clientes antes de integracao SaaS efetiva.

**ARQ-01 P0 parcial; IA-01/SEG-01/SEG-02/AGT-01 e demais gates tecnicos ainda pendentes.** Somente pesquisa/documentacao; **nenhum servidor foi contratado, nenhum teste real autorizado, nenhuma programacao iniciada**.


### 9.3. Atualização 03/11/2026 — priorizar proposta Leaseweb VPS 2 (aceite "ok")

**Decisão de pesquisa:** responsável respondeu "ok" à recomendação de **priorizar a cotação da Leaseweb VPS 2** para os agentes técnicos. **Aprovação restrita à prioridade da avaliação comercial; não constitui seleção definitiva, pedido de compra, aceitação de prazo contratual, homologação de custo ou instalação.**

- **Candidato principal:** Leaseweb VPS 2 **6 vCPU compartilhadas / 16 GB RAM / 200 GB NVMe**, superior à **referência mínima já aprovada 4 vCPU / 8 GB / aproximadamente 160 GB**.
- **Washington, EUA:** comparador Kuraberu registra **US$9,89/mês, sem tributos, verificado em 01/11/2026**; não é cotação oficial no checkout e pode exigir prazo/condições específicas.
- **Montreal, Canadá:** catálogo HostingCompass atualizado em 03/11/2026 lista a VPS 2 por **CA$13,49/mês**, apontando compromisso de 12 meses, mas detalhes completos de contratação, valor à vista, disponibilidade final e renovação não comprovados no site oficial; as comparações de médias de 24 meses **não equivalem a pagamento mensal sem fidelidade**.
- **Site oficial Leaseweb:** descreve características da linha VPS, porém a consulta pública não confirmou um checkout completo na conta/mercado do projeto. Fonte: https://www.leaseweb.com/products-services/cloud/virtual-private-server .
- Fontes de preços **não vinculantes**: https://kuraberu.app/vps/offer/leaseweb-vps-2-us e https://hostingcompass.com/providers/leaseweb/vps .
- **Pendências para avançar:** preço final em Washington e Montreal (mensal sem fidelidade, 12/24 meses, impostos, taxas e renovação); NVMe/IOPS efetivos, limites CPU compartilhada, snapshots/backups externos, disponibilidade, suporte e condições de saída; latência real ao Brasil e aos demais serviços; inventário e carga da VPS n8n já existente; orçamento separado para consumo API de IA, segurança, alerta externo, redes e operação.
- **Critério de decisão:** só escolher SKU físico depois de cotação verificável e confirmação técnica; se a oferta for inadequada ou indisponível, DigitalOcean/Hetzner continuam opções. Manter a VPS dos agentes isolada da VPS n8n, do CRM e dos motores próprios dos produtos Imagine.
- **Regra geral:** nenhuma contratação, acesso ao painel, provisionamento, alteração de infraestrutura, programação ou testes reais foi autorizada. ARQ-01 continua P0 parcial.


### 9.4. Diligência de preços e custos obrigatórios (03/11/2026)

**Natureza:** evolução do estudo anterior após aceite para **prosseguir com verificação comercial**, sem aprovar plano, prazo, fatura, provedor ou despesa. O checkout **não foi acessado como cliente**, portanto **nenhuma oferta foi validada como cotação contratável**.

#### Preços observados em fontes externas e períodos diferentes

| Região / referência | Valor publicado | Escopo e restrição |
|---|---:|---|
| **Washington DC, EUA — HostingCompass** | **US$8,79 por mês equivalente** | Referência de período **24 meses**, com compromisso indicado; preço final de renovação, cobrança inicial e disponibilidade desconhecidos |
| **Washington DC, EUA — Kuraberu** | **US$9,89/mês** | Comparador verificado em outubro/novembro de 2026; **prazo e checkout oficial desconhecidos**, não equivale automaticamente a mensal sem fidelidade |
| **Montreal, Canadá — HostingCompass** | **CA$13,49/mês anunciado** | Catalogado com compromisso **de 12 meses**; preço final de renovação e cobrança inicial desconhecidos. A conversão USD de comparador não substitui a cotação original em CAD |

As três referências correspondem à **Leaseweb VPS 2 — 6 vCPU compartilhadas, 16 GB RAM, 200 GB NVMe**. A **VPS 1 — 4 vCPU/6 GB/100 GB** não é equivalente à referência de dimensionamento **4 vCPU/8 GB/~160 GB** aprovada.

Fontes (preços de terceiros, **não vinculantes**):
- https://hostingcompass.com/providers/leaseweb/vps
- https://kuraberu.app/vps/offer/leaseweb-vps-2-us

A **página oficial da Leaseweb** confirma características da **linha VPS**, não estes preços regionais de checkout: 30 TB de tráfego de saída incluído por VPS (sujeito a contrato final), entrada grátis, 10 Gbps de uplink, proteção DDoS padrão, firewall de rede, **um snapshot incluído**, Basic SLA e possibilidade de **upgrade sem downgrade**. Snapshot isolado não substitui cópia offsite/restauração. A Leaseweb pode operar recursos de CPU compartilhados; o número de vCPU não determina sozinho throughput. Fonte: https://www.leaseweb.com/products-services/cloud/virtual-private-server

**Conclusão intermediária:** usar Washington e Montreal **como alternativas abertas** e **priorizar cotação de preço mensal sem fidelidade**. Só avaliar desconto de 12/24 meses quando a permanência, riscos, renovação e penalidades forem justificados. Não há elementos suficientes para escolher a região apenas pelo menor preço em moedas diferentes; a latência para o Brasil/serviços integrados e LGPD devem ser avaliadas.

#### Custos obrigatórios e facultativos do subsistema de agentes (sem orçamento aprovado)

| Item | Referência oficial / custo | Necessidade para a produção |
|---|---|---|
| VPS dos agentes técnicos | Preço Leaseweb **pendente do checkout** | Obrigatória, separada da VPS n8n |
| Snapshot incluído | 1 por VPS segundo Leaseweb | Útil para ponto de manutenção, mas **não** satisfaz backup offsite |
| Backup offsite das configurações/estado/auditoria protegidos | Leaseweb Acronis lista **25 GB por €3,75/mês, 50 GB €5,50, 100 GB €10,00**, incluindo licença, capacidade e workloads; preço regional/contrato a confirmar | **Obrigatório ter estratégia de restauração comprovada**, mas a escolha **Acronis não está aprovada**; quota depende de dados e retenção reais |
| Object Storage alternativo/complementar | Leaseweb Object Storage On Demand **€0,01733/GB-mês** + PUT/GET/egress, sem pacote mínimo contratado, segundo página oficial | Pode armazenar backup de dados e artefatos com credenciais dedicadas; **não é sozinho um produto de backup** nem garante restauração/snapshot |
| Monitoramento e alerta fora do subsistema | Reutilização de observabilidade transversal se homologada; preços extras pendentes | Alertas críticos **não dependentes** de n8n, agentes ou Portal; frequência, canal e operador a definir |
| Segredos, VPN/TLS, auditoria, logs e antimalware quando aplicável | Software gratuito possível, mas horas, custos e capacidade não nulos | Controles de segurança **não podem ser eliminados** para caber em teto |
| IA por API, execução de ferramentas e tokens | Tarifa de provedor/modelo e consumo **a definir separadamente** | Limites por projeto/operação, orçamento, rastreabilidade e segregação de custo das franquias IA dos clientes |
| n8n existente | Sem nova contratação apenas **se o inventário comprovar a VPS ativa e adequada** | **Mensalidade legada deve aparecer no TCO**, nunca assumir custo operacional zero |

**Fontes oficiais adicionais:**
- https://www.leaseweb.com/en/products-services/back-up/acronis
- https://www.leaseweb.com/products-services/object-storage

A referência de **€3,75 por 25 GB** não autoriza concluir que 25 GB bastam ou que o Acronis sozinho assegura todas as exigências de recuperação, imutabilidade, trilha de auditoria e isolamento. Escolher armazenamento por **capacidade protegida, frequência, retenção, independência de credenciais, testes de restore e RPO/RTO**. Separar orçamento dos **agentes técnicos transversais** daquele do **CRM E1B**, Portal, Performance AI e das **APIs de captação**.

#### Portas documentais para decisão posterior

1. **Cotação Leaseweb efetiva** com conta/entidade de cobrança: US/Washington WDC-02 e CA/Montreal MTL-02; preço **1 mês, 12 meses e 24 meses**, entrada, impostos, egress extra, renovação, SLA/suporte e possibilidade de cancelamento; não há autorização para contratar.
2. **Inventário n8n:** servidor ativo realmente 6 vCPU/16 GB/200 GB? mensalidade atual, RAM/CPU/disco usados, backups, versão n8n, Postgres, credenciais e segurança. Sem acesso técnico operacional na fase de planejamento.
3. **Restauração e capacidade:** estimar GB protegidos, backups de configuração, retenção, testes e tempo de reposição; concorrência real dos agentes e tolerância à indisponibilidade, sem confundir modelos via API com execução local.
4. **Critérios de região:** comparar distância de API/modelo, CRM, n8n e público, jurisdição/LGPD, latência aferida em fase homologada, disponibilidade regional e custo completo, não apenas convertendo CAD para USD.
5. **Licenciamento n8n:** avaliar Community/SUL para workflows internos e licença comercial se clientes do CRM puderem criar/configurar workflows baseados no n8n, ainda que por tela própria; não instalar fluxo SaaS sem essa definição.
6. Submeter **tabela financeira completa e decisão expressa** antes de ativar serviços. Os preços de fontes externas não podem ser tratados como mensalidade contratada.

**Estado após diligência:** Leaseweb VPS 2 segue **candidata prioritária de cotação**, mas **sem preço final oficial, escolha de região, aprovação financeira, homologação de produção ou liberação de programação.**


## 10. Revisão de licenciamento e fronteira do motor de automações do CRM (04/11/2026)

**Estado:** **análise técnica e de licenciamento para decisão posterior, NÃO aprovada como nova decisão comercial**. Continuidade da pesquisa autorizada pelo "ok" do responsável. Não há compra, comunicação enviada ao fornecedor, programação ou implantação.

### 10.1. Critérios verificados em fontes oficiais

A página oficial de ajuda do n8n, **atualizada em 06/10/2026**, diferencia:
1. **Workflows criadas e mantidas internamente pela equipe Imagine**, sem que clientes possam alterar sua lógica ou acessar o editor, podem se enquadrar na **Community Edition / Sustainable Use License**; avaliar limites específicos, especialmente credenciais e processamento por conta de clientes.
2. **Clientes do SaaS construindo ou configurando automações em n8n**, por interface própria do CRM, API, MCP ou agente atuando a pedido deles: **licença comercial necessária**; a ajuda oficial menciona **Enterprise** para esses cenários.
3. A oferta **OEM** é distinta: expõe/incorpora o editor n8n ao cliente; o uso como backend de produto com editor invisível tem condições específicas e pode exigir **Enterprise**.
4. A licença Community **não deve ser considerada autorização automática** para processar credenciais de tenants ou embutir o n8n como infraestrutura de recurso vendido. Consultar o fornecedor por escrito sobre o caso concreto.

Fontes:
- FAQ oficial (atualizada 06/10/2026): https://support.n8n.io/article/can-i-use-your-license-for-my-use-case
- Modalidades Enterprise vs OEM: https://n8n.io/oem/
- Termos EULA: https://n8n.io/legal/eula/

### 10.2. Matriz de funções e diretriz de menor risco

| Caso Imagine | Fronteira recomendada para estudo | Status de licença |
|---|---|---|
| Atualização de sites feita internamente pela Imagine (publicação, integridade, backups) | n8n para fluxos internos, com ferramentas tipadas, autorização e auditoria | **Community POSSÍVEL**, requer confirmação para os casos reais |
| Gerenciamento técnico transversal (alertas, tarefas administrativas) | n8n e agentes técnicos em VPS separadas; privilégios restritos | **Community POSSÍVEL** para operações genuinamente internas |
| Cliente CRM configura Gatilhos, Condições, Ações e Fluxos | **Motor próprio do CRM**, reutilizando as estruturas planejadas; não gerar/configurar workflows n8n em nome do cliente | **Não transferir a obrigação comercial ao n8n** |
| Agente de IA do CRM edita uma automação solicitada pelo cliente | Motor de configuração e autorização **do CRM**, sem editar workflows n8n indiretamente | Se afetar workflows n8n, consultar licenciamento comercial |
| Cliente vincula sua conta externa/OAuth e pede ação automatizada | Ação autenticada por motor/backend próprio com credenciais do cliente sob cofre e escopo por tenant; n8n apenas se licença autorizar o processamento concreto | **Avaliação jurídica com o fornecedor obrigatória** antes de uso produtivo |
| Performance AI executa campanhas de clientes | **Motor independente** (PERF-ARQ-01); n8n apenas para fluxos internos permitidos | Não presumir licença gratuita para embed SaaS |
| Futuro tutor Universidade Imagine | Motor tutor do produto, sem acesso irrestrito a n8n ou CRM | Verificar licença se novos workflows forem expostos/embutidos |

**Recomendação de desenho (ainda para aprovar):** adotar **L1 — motor de automações de clientes no próprio CRM**, obedecendo decisões aprovadas de motores de Fluxos, Gatilhos, Condições e Ações, e **n8n restrito a workflows internos cuja licença seja validada**. Não duplicar os motores centrais do CRM; manter n8n como ferramenta secundária de orquestração. Isso reduz dependência de licença Enterprise/OEM e melhora separação por tenant, mas **não é parecer jurídico nem dispensa confirmação por escrito**.

### 10.3. Consulta escrita necessária ao n8n antes de homologação

Contatos do próprio n8n: **license@n8n.io** para uso da SUL, e **sales@n8n.io** para Enterprise ou outros acordos. **Ainda não enviadas**.

Submeter cenários concretos:
- A Imagine opera uma VPS n8n autogerida para workflows criadas e alteradas **apenas por empregados Imagine**, gerenciando sites, suporte técnico e saúde dos sistemas. O usuário final apenas consome resultados. Esse uso está coberto pela Community?
- O CRM permite que clientes criem automações em motor **próprio**, mas processos internos Imagine às vezes são disparados por eventos relacionados a clientes: em quais condições o n8n Community poderia executar ações? Como afeta autenticação por credenciais de terceiros?
- É permitida uma API interna que chama serviço próprio para manipular integrações e credenciais de tenants, sem que o n8n receba credenciais, configure workflows ou forneça o motor principal dessas integrações?
- Em quais circunstâncias os usos descritos exigem **Enterprise**, **OEM** ou outra modalidade? Há exigência de licença por instância, empresa, tenant, workflow ou volume de execuções?
- Confirmar por escrito possibilidades de automação administrada pela Imagine e de agente de IA operando apenas em nome da Imagine; pedir termos e preços atualizados.

### 10.4. Efeito no fechamento e próximo passo

- **Nenhuma alteração das decisões aprovadas**: n8n e agentes técnicos em VPS separadas; runtime Performance AI separado; APIs de leads independentes; servidor de agentes referência 4 vCPU/8 GB/~160 GB.
- **Gate de licença n8n** deve aparecer no orçamento global como risco de custo. Não considerar n8n comercial gratuito para SaaS até responder à consulta.
- Antes da programação, decidir expressamente se **L1 (CRM motor próprio; n8n somente operações internas compatíveis)** passa a ser regra definitiva do projeto. L2 (licença comercial para uso embutido) permanece alternativa de avaliação se requisito funcional exigir.
- Confirmar que a VPS n8n existente realmente está ativa e possui contrato, backups e capacidade; a atualização da licença não substitui a cotação Leaseweb e o inventário.
- **ARQ-01 continua P0 parcial**; nenhuma implementação/compra/deploy autorizados.
