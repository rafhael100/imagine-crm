# P0-ARQ01-F01 — Inventário documental preliminar dos ativos e serviços Imagine

**Data:** 2026-11-03. **Status:** **LEVANTAMENTO DOCUMENTAL PARA CONFERÊNCIA — NÃO HOMOLOGADO**.
**Antecedente:** a [matriz P0-ARQ-01 foi aprovada como instrumento documental](CRM-DEC-aprovacao-P0-ARQ-01-instrumento.md). Essa aprovação **autoriza prosseguir na documentação de ARQ01-F01**, não comprova infraestrutura real, não encerra ARQ-01 e não autoriza execução.
**Fontes:** documentos no GitHub e informações de infraestrutura previamente relatadas pelo responsável em conversas de outubro/novembro de 2026, identificadas explicitamente como **históricas, não verificadas hoje**.
**Não foi feito:** acesso SSH/RDP, descoberta de IP, listagem de contas/contratos, leitura de credenciais, inspeção do painel do provedor, medição, instalação, teste, migração, contratação ou alteração de produção.

## 1. Legenda de evidência e confiança

| Classe | Fonte | Interpretação |
|---|---|---|
| EVD-01 | Fato declarado em conversa / memória de planejamento | Utilizar como hipótese informada pelo responsável, sem carimbo de comprovação atual |
| EVD-02 | Decisão funcional no GitHub | Aprovada como arquitetura/limite; não comprova contratação, instalação ou versão do host |
| EVD-03 | Documento de estudo técnico com custo referencial | Serve a comparação preliminar; não confirmar preço atual, capacidade contratada ou gasto |
| EVD-04 | Evidência efetiva de console/contrato/fatura ou inventário read-only fornecido/validado | **Não coletada neste trabalho**; necessária para declarar status atual, custo contratado, versões e carga |

**Nunca converter planejamento em inventário confirmado.** Em particular, a existência de um serviço lógico aprovado não demonstra que ele já está hospedado, nem que uma VPS referida como “escolhida/candidata” tenha sido contratada.

## 2. Inventário de trabalho — ativos declarados, domínios aprovados e lacunas

| ID | Ativo/domínio | Fonte conhecida | Situação/evidência disponível | Validação a fazer |
|---|---|---|---|---|
| INV-01 | Servidor dedicado legado / WHM-cPanel | **Relato histórico do responsável** (31/10/2026): servidor dedicado com WHM/cPanel; diagnóstico anterior relatou CentOS Linux 7, ~125 GB RAM, 48 CPUs lógicas, ~10 TB, cPanel 110.0.154 | **CONFLITO de data/versão**: outra referência histórica descreve AlmaLinux 10 + WHM/cPanel como ambiente planejado/atualizado; não há prova de migração nem de SO efetivamente em execução em 03/11 | Não considerar esse host como servidor obrigatório dos novos Sites Imagine; verificar hardware/SO real e uso dos legados sem SSH/alteração nesta etapa |
| INV-02 | VPS de n8n / PostgreSQL / Docker | **Informação declarada em conversas e reproduzida no estudo ARQ-01**: Leaseweb, Ubuntu 24.04, referência 6 vCPU / 16 GB RAM / 200 GB NVMe; Docker Compose, n8n e PostgreSQL planejados/descritos | **Existência/instalação/serviços em execução não confirmados em console nesta revisão**; referência de capacidade não equivale a CPU livre ou contratação confirmada | Confirmar se VPS está contratada e em operação, versão n8n, base/volumes, limites e custo real; jamais mover agentes para essa VPS |
| INV-03 | CRM Imagine SaaS / Laravel + PostgreSQL | Arquitetura SaaS multiempresa com Laravel, PostgreSQL, tenant_id/RLS e servidor separado do Portal aprovada em CRM-REV01, ARQ-01 | **ALVO APROVADO, SEM PROVA DE IMPLANTAÇÃO** | Determinar host/provedor/banco/ambientes e contrato, sem presumir instalação |
| INV-04 | Portal Imagine / backend e administração | Separação em servidor próprio de CRM aprovada em IN-168/ARQ-01 | **ALVO APROVADO, SEM PROVA DE IMPLANTAÇÃO** | Determinar host, banco, interface com CRM/FIN, backups, orçamento e disponibilidade |
| INV-05 | Agentes técnicos + Supervisor + orquestrador | **Opção B aprovada**: VPS/servidor próprio separado da VPS n8n; sete agentes e supervisor são papéis, não hosts | **TOPOLOGIA DECIDIDA, TAMANHO NÃO HOMOLOGADO**; estudo ARQ-01 sugere 4 vCPU/8GB/160GB como hipótese, não compra | Dimensionar tarefas/IOPS/observabilidade, negociar custo, escolher provedor/região e controles |
| INV-06 | Sites Imagine Onepage/Landing / Cloudflare | **IN-096 aprovada**: Workers Static Assets + GitHub, publicação/autorização por APIs e agentes; sem WHM/cPanel nem DB local | **ARQUITETURA APROVADA, CONTAS/WORKERS/DNS ATIVOS NÃO AUDITADOS** | Inventariar contas/zonas/rotas/versionamento por site, política de titularidade DNS do cliente |
| INV-07 | API principal de formulários | Serviço independente do painel Portal e persistência durável própria aprovado em IN-124 | **SERVIÇO LÓGICO APROVADO, HOST E ESTADO DE DEPLOY NÃO VERIFICADOS** | Host/DB/fila, endpoint protegido, domínio de falha, capacidade e custo |
| INV-08 | API de contingência + persistência | IN-124 aprova **outro servidor** independente de principal, persistência própria; outro provedor é preferência condicionada | **SERVIÇO LÓGICO APROVADO, HOST E DEPLOY NÃO VERIFICADOS** | Fornecedor/região, dependências compartilhadas de DNS/cert/chaves, backup e failover |
| INV-09 | Processadores de fila + reconciliador | Persistência durável e idempotência, recebimento ≠ registro oficial; 3+15 dias para não entregues | **RESPONSABILIDADE APROVADA, TOPOLOGIA NÃO DEFINIDA** | Inventário de workers/processos, fila, retries, responsabilidade por entrega e controle 18d |
| INV-10 | Cofre de segredos/identidades de serviço | IN-151/166 aprovadas capacidades, menor privilégio e autorização independente da IA | **PRODUTO/HOST NÃO SELECIONADO** | Modelo IAM/secrets, rotação, recuperação, custo, limites do administrador global |
| INV-11 | Observabilidade, alertas e incidentes | IN-159..162 aprovam central e políticas técnicas; alerta precisa ser independente da UI, IA e Supervisor | **TECNOLOGIA/HOST/CANAIS NÃO DEFINIDOS** | Coleta, armazenamento logs/métricas, alerta independente, custo/retention |
| INV-12 | Armazenamento de anexos/backup CRM | LEG06/CRM-REV09 aprovam objetos privados, backups e proteção por tenant; sites têm backups separados | **SERVIÇOS FÍSICOS, RTO/RPO E PROVEDORES NÃO VERIFICADOS** | Provedor S3, réplica 15min de anexos, restore, PITR, custo/isolamento |
| INV-13 | GitHub, CI/CD e publicador | Repositório de planejamento GitHub verificado; GitHub para Sites versionados; Publicador Imagine futuro responsável por deploy dos Sites | **PLANEJAMENTO GITHUB REAL; CI/CD/PIPELINE PUBLICAÇÃO NÃO VERIFICADOS** | Separar versão documental, pipelines aprovados, credenciais, ambiente de homologação, autoridade de deploy |

### 2.1 Conflitos históricos e precedência

**Dedicated WHM/cPanel:** há menção histórica a um **dedicado com CentOS 7** e especificações aproximadas 125 GB RAM, 48 CPUs lógicas, 10 TB e cPanel; há também planejamento posterior que se refere a **AlmaLinux 10 + WHM/cPanel**. Sem evidência de console atual não declarar que ocorreu migração, nem que CentOS 7 ainda esteja em produção. A arquitetura **posteriormente aprovada IN-096** substituiu a hipótese de hospedar os novos Sites Imagine nesse dedicado: novos Sites serão Cloudflare Workers Static Assets. O dedicado pode continuar hospedando outros sites legados, o que **deve ser confirmado**, sem alteração agora.

**VPS n8n:** o estudo [ARQ-01 — sizing](ARQ-01-dimensionamento-vps-agentes-tecnicos.md) cita Ubuntu 24.04, 6 vCPU, 16 GB e 200 GB NVMe como **referência histórica** da VPS n8n/Docker/PostgreSQL. O responsável também relatou o provedor **Leaseweb** em planejamento. Isso **não é atestado de capacidade livre, instalação final, versão atual do n8n ou valor do contrato**. A Opção B **proíbe** hospedar agentes técnicos nessa mesma VPS/host.

**VPS de agentes:** a configuração 4 vCPU/8 GB/160 GB SSD é **hipótese recomendada no estudo**, **não** ativo comprado; alternativas 2/4/80 e 8/16/320 do estudo são cenários, não três novos servidores.

**CRM, Portal e APIs:** a separação de infraestruturas é **decisão arquitetural aprovada** e deve continuar sendo respeitada, mas o estado atual de criação de recursos, ambientes, bancos e custos **não foi inspecionado** neste levantamento.

## 3. Decisões já aprovadas que não precisam nova votação

| ID | Questão | Estado atual |
|---|---|---|
| CON-01 | CRM e Portal no mesmo host? | **Não** — servidores próprios e separados; decisão já aprovada, não reabrir |
| CON-02 | Agentes técnicos podem ser implantados na VPS n8n? | **Não** — Opção B separada aprovada; inventário da VPS n8n não altera isolamento |
| CON-03 | Novos Sites Imagine hospedados em WHM/cPanel? | **Não** — Cloudflare Workers Static Assets é arquitetura aprovada; dedicado histórico pode servir outros sistemas/legados |
| CON-04 | API secundária pode depender do banco/fila da API principal? | **Não** — persistência durável e host independentes; provedor distinto ainda preferência |
| CON-05 | Quais servidores/provedores efetivos são utilizados hoje? | **Em validação documental** — relatos antigos não são prova de estado atual |
| CON-06 | Quantas novas VPS precisam ser contratadas? | **Não decidido** — 12 domínios lógicos não significam 12 VPS; quantificar após topologia/serviços gerenciados |

## 4. Checklist factual ARQ01-F01 — falta evidência verificável

| ID | Dado a confirmar | Domínio |
|---|---|---|
| F01-P01 | Verificar com fonte atual SO/versão do dedicado legado; resolver CentOS 7 × AlmaLinux 10 e eventual migração | INV-01 |
| F01-P02 | Confirmar qual é o provedor, número e status real da VPS n8n, planos contratados e custo recorrente | INV-02 |
| F01-P03 | Obter capacidade **medida** n8n/PostgreSQL: CPU/RAM/IOPS/volume ocupado, backups, peak workflows e latência | INV-02 |
| F01-P04 | Confirmar estado da infraestrutura CRM e Portal (não instalado / em homologação / produção), hosts e bancos separados | INV-03; INV-04 |
| F01-P05 | Confirmar infraestrutura existente/prevista de agentes Opção B, sem confundir estudo 4/8/160 com compra | INV-05 |
| F01-P06 | Inventariar contas Cloudflare/GitHub e se há Sites novos ativos no Workers, sem inventar zonas DNS | INV-06; INV-13 |
| F01-P07 | Confirmar existência de rotas independentes das duas APIs e respectivos bancos/fila/hosts | INV-07; INV-08; INV-09 |
| F01-P08 | Levantar ativos de credenciais/cofre e responsáveis de acesso, sem coletar senhas/tokens em texto | INV-10 |
| F01-P09 | Levantar monitoramento/alertador externo e onde estão logs, backups e provas de restore | INV-11; INV-12 |
| F01-P10 | Registrar região/provedor e custo mensal efetivamente contratado para cada recurso, com fonte e data | INV-01..13 |
| F01-P11 | Conferir domínios de falha compartilhados (provedor, DNS, credenciais, storage e rede) entre APIs, Portal, CRM | INV-03..12 |
| F01-P12 | Produzir desenho físico final, sizing, custos e aceite explícito antes de reclassificar P0 ARQ-01 | Todos |

**Forma de conferência futura:** inventário **somente leitura** de painel do provedor ou relatório sanitizado enviado pelo responsável, preferindo identificador interno de ativo, região, tipo de recurso, data de evidência, capacidade contratada, ocupação e custo. **Não solicitar credenciais, senhas, chaves de acesso, IPs públicos completos ou backups de dados de clientes para esta revisão.**

## 5. Campos mínimos do inventário final (sem valores presumidos)

| Campo | Conteúdo exigido para declarar confirmado |
|---|---|
| ID do ativo e serviço | Alias interno, propósito e proprietário administrativo autorizado |
| Tipo e região | VPS/dedicado/gerenciado/serverless, provedor e região física conforme contrato |
| Ambiente e estado real | Produção/homologação/desenvolvimento; planejado, contratado, provisionado, ativo ou desativado com evidência |
| Configuração | vCPU, RAM, tipo/capacidade de disco, limites CPU compartilhada/dedicada, dados de versão e data de coleta |
| Aplicações e dependências | Serviços/bancos/filas reais e mapeamento com T01..T12, dependências críticas e domínio de falha |
| Segurança e backups | Segregação, MFA administrativo, cofre/segredos (sem copiá-los), cópias e confirmação de restauração |
| Capacidade e custos | Métricas reais de uso/pico, política de alertas, custo mensal efetivo em moeda e data/base do valor |
| Proveniência | Quem validou, fonte sanitizada, data, confiança, decisão e commit do documento |

## 6. Próxima decisão e bloqueios que continuam

**A ARQ01-F01 ainda não é um inventário factual homologado.** Este documento está **em minuta de conferência** e não marca nenhum servidor ou serviço como efetivamente implantado sem evidência. A próxima atividade será confrontar os ativos com fontes atuais e produzir o **desenho físico/custos ARQ01-F02**, em nova proposta, usando as separações já aprovadas.

**ARQ-01 continua P0 PARCIAL**. ARQ-02, ARQ-03, SEG-01, SEG-02, AGT-01, CORE-P0, INT-P0 e G01–G06 continuam abertos. Não iniciar programação nem contratar/provisionar nenhum serviço antes do planejamento global aprovado.

## 7. Diretriz posterior — CRM em servidor novo e provedor recomendado (03/11/2026)

O responsável decidiu **hospedar o projeto CRM Imagine em um servidor novo, separado da VPS histórica do n8n**, solicitando a recomendação técnica da Imagine/assistente. Foi documentada a [CRM-DEC — novo servidor e recomendação de provedor](CRM-DEC-infraestrutura-novo-servidor-e-recomendacao-provedor.md).

**Recomendação técnica preferencial, ainda sem contratação:** **Vultr São Paulo, High Performance AMD, 4 vCPU compartilhadas, 8 GB RAM, 180 GB NVMe**, com preço de catálogo de referência **US$ 48/mês somente da VPS de aplicação** (cotação e disponibilidade a confirmar). **PostgreSQL deverá ficar separado da aplicação**, preferencialmente gerenciado e na mesma região, a confirmar, com armazenamento de anexos e backups externos e custos próprios.

**Atualização do estado INV-03:** a **destinação a novo servidor CRM é decisão do projeto**, enquanto **provedor, plano, carga, banco, operação e orçamento ainda são proposta de homologação**, não ativo implantado. A informação histórica INV-02 sobre Leaseweb/n8n não se converte em capacidade disponível para o CRM. **ARQ-01 segue P0 parcial**.

## 8. Fontes documentais de apoio

| Documento | Relação |
|---|---|
| [P0-ARQ-01 aprovada como instrumento](CRM-DEC-aprovacao-P0-ARQ-01-instrumento.md) | Autoriza inventário documental, não fechamento de ARQ-01 |
| [P0-ARQ-01 — domínios e interfaces](P0-ARQ-01-topologia-fronteiras-plano-fechamento.md) | Mapa de 12 domínios, aprovado apenas como matriz lógica |
| [IN-153 — inventário](IN-153-inventario-inteligente-infraestrutura-dependencias.md) | Escopo e classificação de ativos, sem descoberta ativa |
| [ARQ-01 — comparativo](ARQ-01-comparativo-infraestrutura-agentes-tecnicos.md) | Opção B aprovada; ambiente n8n referido historicamente |
| [ARQ-01 — sizing preliminar](ARQ-01-dimensionamento-vps-agentes-tecnicos.md) | VPS n8n de referência e 4/8/160 de agentes como hipótese, sem compra |
| [IN-124 — duas APIs](IN-124-integracao-inteligente-formularios-leads.md) | Servidores e persistência de captação independente |
| [IN-168 — bloqueios P0](IN-168-matriz-pendencias-fechamento-arquitetura.md) | ARQ-01 parcial e outras dependências não encerradas |
