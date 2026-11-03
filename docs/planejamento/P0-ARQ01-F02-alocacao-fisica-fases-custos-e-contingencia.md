# P0-ARQ01-F02 — Alocação física por fases, custos, riscos e contingência | CRM Imagine

**Data:** 03/11/2026
**Estado:** **MINUTA TÉCNICA PARA REVISÃO — NÃO APROVADA NEM HOMOLOGADA**.
**Decisão de origem aprovada:** [CRM-DEC — estratégia de infraestrutura econômica e escalável Leaseweb](CRM-DEC-estrategia-infra-economica-escalavel-leaseweb-2026-11-03.md).
**Instrumentos de controle:** [P0-ARQ-01](P0-ARQ-01-topologia-fronteiras-plano-fechamento.md), [ARQ01-F01](P0-ARQ01-F01-inventario-factual-ativos-e-servicos.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md) e [CRM-REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md).
**Limite:** sem programação, contratação, provisionamento, SSH, mudanças de infraestrutura, testes reais ou início do CRM. F02 propõe distribuição e critérios para decisões posteriores; **ARQ-01 permanece P0 PARCIAL**.

## 1. Objetivo e premissas

Escolher a menor infraestrutura segura em cada fase, com possibilidade de crescimento sem reconstrução de domínio, banco ou anexos. O foco é o **CRM SaaS**; Portal Imagine, sites, n8n e agentes técnicos têm domínios e custos próprios. A aprovação vigente é da **estratégia escalonada** (não de fornecedores físicos, regiões, SKUs e orçamentos).

- Laravel/PHP e PostgreSQL como base; SaaS multiempresa com `tenant_id`, RBAC e RLS; sem banco separado por empresa como regra geral.
- CRM em servidor/ambiente diferente do Portal, do host n8n e do servidor dos agentes técnicos.
- Sites em Cloudflare Workers Static Assets; captação de leads tem API principal + outra contingência, ambas com persistência durável e domínios de falha independentes, e confirmação final somente pelo CRM.
- Cloudflare como camada frontal de DNS/SSL/WAF/CDN; não é local primário de dados do CRM nem substitui disponibilidade do backend.
- Anexos privados em object storage S3 compatível e **fora do disco local da aplicação**; acesso via backend/autorização por tenant; backups do CRM separados dos backups Sites.
- Planejamento com zero cliente: não comprar infraestrutura.

## 2. Topologia por fase — candidatos NÃO aprovados fisicamente

| Fase | Aplicação | Banco PostgreSQL | Arquivos e backups | Uso e limite |
|---|---|---|---|---|
| F0: planejamento agora | Nenhuma instância nova | Nenhum banco novo | GitHub de documentação; nenhuma estrutura de produção necessária | Decisões e critérios, **custo de novos servidores = zero** |
| F1: desenvolvimento, só após aprovação global para programar | **Leaseweb VPS 1 candidata** nova/exclusiva; possível estrutura existente somente se isolamento e capacidade forem demonstrados | PostgreSQL local **exclusivamente de desenvolvimento**, em ambiente segregado; dados fictícios/anonimizados | Backup de ambiente de desenvolvimento, sem dados pessoais reais | Não oferecer CRM público pago, não simular alta disponibilidade de produção |
| F2: piloto com dados reais / primeira produção | **Leaseweb VPS 2 candidata** exclusiva do backend Laravel, API CRM, web e workers de filas | **Instância independente**, preferencialmente na mesma cidade/região; Leaseweb VPS 1 candidata **condicional a dimensionamento**, VPS 2 ou banco gerenciado alternativas | Object Storage privado S3 compatível; backup de banco, cópia externa, restauração planejada, replicas de anexos conforme política | Só liberar clientes após isolamento, segredos, autorização, segurança, backups/restauração, monitoramento independente e risco de indisponibilidade aprovados |
| F3: crescimento | Horizontalizar app em Cloud/VMs ou dedicado se justificável | Replica/HA, compute e storage dimensionados por métricas | Ampliar buckets, backups, monitoramento e DR | Decisão orientada por carga/receita/SLA, não apenas número de clientes |

**Nota de leitura:** indicação de VPS 1/2 define apenas **porte candidato**. Não é compra, capacidade garantida, SKU fixado ou permissão para hospedar PostgreSQL produtivo na mesma VPS do Laravel. Não presumir que produção F2 seja HA: aplicação e PostgreSQL com uma instância cada são **pontos únicos de falha**.

## 3. Alocação proposta dos serviços e limites de separação

| ID | Componente | F1 desenvolvimento | F2 primeira produção — proposta | Independência / proteção exigidas |
|---|---|---|---|---|
| F02-01 | Entrada HTTPS CRM | Cloudflare + DNS de testes, se autorizado | Cloudflare e origem restrita para evitar bypass; regras de WAF/caching adequadas para sessão/API | Sessões e endpoints sensíveis nunca cacheados publicamente; não depender de acesso à UI para receber alertas |
| F02-02 | Frontend e Laravel web/API CRM | VPS desenvolvimento | VPS 2 CRM exclusiva | Deploy reproduzível e versionado, segredos não persistidos no GitHub |
| F02-03 | Workers de fila / scheduler | Processos locais segregados | Podem compartilhar VPS da aplicação no piloto; escalar serviço depois | Processos supervisionados; idempotência, limite de concorrência; não deixar tarefas críticas só em memória |
| F02-04 | Fila durável de eventos/tarefas CRM | Local em dev | **Escolha P0**: fila apoiada em persistência adequada, monitorada e recuperável; Redis isolado pode atender cache, mas não presumir que Redis cache seja fila durável sem política de persistência | Critérios de entrega, retry, DLQ e deduplicação por evento; preservar dono do efeito |
| F02-05 | Redis/Valkey (cache, locks) | Opcional dev | Na VPS da aplicação apenas se recursos/segurança permitirem; migrável para serviço próprio | Cache não é sistema de registro; chaves e isolamento definidos |
| F02-06 | PostgreSQL principal | Banco de testes | **VPS independente** na mesma região, sem acesso público direto; VPN segura ou overlay mTLS/TLS, firewall e least privilege | Criptografia em trânsito; RLS; PITR e restauração. Na linha Leaseweb VPS, Private Network nativa não está disponível; verificar túnel/VPN e dependência de rede |
| F02-07 | Anexos privados | Bucket de teste sem dados reais | Object Storage externo à VPS; Leaseweb S3 e Cloudflare R2 candidatos, com proteção por backend, criptografia, antimalware e controles de quota | Dados 1/5/20/50 GB por plano são quotas comerciais; não reservar fisicamente a capacidade de todos de antemão |
| F02-08 | Banco backup/WAL | Dev: backup proporcional ao ambiente | Backup lógico/físico e WAL/PITR quando homologado; destino externo isolado, criptografado e protegido contra exclusão | Política de retenção, frequência e credenciais independentes; restore documentado |
| F02-09 | Replica/backup anexos | Teste de políticas | Replicação assíncrona de anexos com alvo de 15 min em servidor/destino separado, retenção técnica 30 dias | Prova de integridade e testes posteriores; não é cópia síncrona nem garantia de perda zero |
| F02-10 | Segurança/IAM/cofre | Segredos de teste isolados | Segredos protegidos e escopados por serviço/ambiente; rotação/revogação; administração MFA | Fornecedor e tolerância a falha do cofre permanecem SEG-02 |
| F02-11 | Monitoramento e alerta | Monitoramento de dev | Coleta de logs/métricas, sondas e **alerta externo independente** da VPS, IA, Supervisor e painel CRM | Estados desconhecidos não são saudáveis; políticas de retenção de IN-162 |
| F02-12 | API principal e contingência de leads dos Sites | Contrato documental apenas | **Fora da topologia de VPS CRM**; host e persistências próprios independentes um do outro e da indisponibilidade do CRM | Bloqueio ARQ-02; não colocar ambas ou fila única no CRM |
| F02-13 | Portal Imagine | Fora da VPS CRM | Host/ambiente próprio, banco e segurança isolados; integração via API autenticada | Queda do Portal não bloqueia operações essenciais do CRM |
| F02-14 | n8n e agentes técnicos | Ambientes próprios conforme inventário | n8n em VPS distinta dos **7 agentes + Supervisor em VPS própria**, conforme Opção B | Não extrapolar credenciais root; manter IA N1/N2/N3 aprovada |

## 4. Região: quadro de avaliação, sem escolha final

- **Washington WDC-02 (EUA):** primeira hipótese de comparação pela possibilidade de conectividade com usuários brasileiros; **não presumir latência superior** sem medições de rotas a partir de várias regiões brasileiras.
- **Montreal MTL-02 (Canadá):** alternativa natural, dada experiência de infraestrutura do responsável na Leaseweb desde 2012; a existência de servidores do usuário não comprova capacidade ou elegibilidade para reutilização.
- **Europa (por exemplo AMS/FRA):** comparar preço, oferta, latência, proteção de dados e dependência de rede.
- Colocar **Laravel e PostgreSQL da produção na mesma metro/região** sempre que possível; o backup pode ser armazenado em outra região/provedor se houver justificativa de segurança, LGPD e custo.
- Homologar localização e mecanismos jurídicos aplicáveis para transferência internacional de dados pessoais antes de armazenar dados de clientes reais.

## 5. Proteção do banco em duas VPS sem Private Network nativa

A [matriz oficial de rede privada da Leaseweb](https://kb.leaseweb.com/kb/private-network/private-network-overview/) indica suporte para Public Cloud e Dedicated, **não para VPS**. Logo, conexões Laravel→PostgreSQL em duas VPS não podem ser tratadas como rede privada nativa automaticamente.

**Projeto candidato de mitigação:** overlay WireGuard/VPN ou túnel autenticado com chaves por serviço e rotação, firewall restringindo a origem, PostgreSQL configurado para aceitar apenas interfaces autorizadas, TLS quando apropriado, monitor da conectividade e fail-closed de acessos não autorizados. Validar custos, MTU/latência, reconexão e perda de túnel. Isso **não elimina** os pontos únicos de falha.

**Alternativa mais robusta para rever na homologação:** banco em Public Cloud com rede privada, desde que o backend também tenha rota privada operacionalmente verificável; ou ambos em Public Cloud, se o custo adicional reduzir risco e carga operacional. Não adicionar produtos só para dizer que existe rede privada se a conexão real continuar pública.

## 6. Requisitos mínimos antes de dados reais (gate de F2)

**Não aprovar primeira produção somente com 2 VPS baratas.** Exigir documentação e homologação futura de:
1. Controle de acesso multiempresa aplicado ao Laravel, PostgreSQL RLS, arquivos, filas, relatórios, integrações, agentes e tarefas assíncronas.
2. Segredos protegidos, MFA de administradores, restrições da origem do CRM, firewall, atualizações e segregação de dev/prod.
3. Backup de PostgreSQL/restauração com integridade verificada; cópias de anexos independentes; políticas de retenção aprovadas e custos.
4. Monitor/alerta independente, saúde de backend/banco/filas/storage; falha silenciosa identificável.
5. Filas duráveis, duplicidade/idempotência e recuperação; sites/captação de leads com caminho duplo independente do CRM.
6. Inventário de pontos únicos de falha, aceitação expressa do risco e plano de resposta e restauração com objetivos RPO/RTO por serviço e orçamento.
7. Conexão criptografada sem exposição aberta do banco e sem privilégio universal.
8. Condições de serviço, tratamento de dados pessoais e transferência internacional compatíveis com operações nos EUA/Canadá/Europa.
9. Mecanismo de atualização/rollback seguro da aplicação e contratos versionados sem reverter efeitos financeiros/oficiais externos.
10. Critérios de homologação definidos durante planejamento e efetivamente executados **somente após autorização de desenvolvimento**.

## 7. Modelo de custo total (TCO): não confundir VPS com plataforma

**Custo novo F0 de hospedagem = 0** por decisão de não contratar durante planejamento; custos legados já existentes ficam fora desse *incremento*.

**F1** = VPS dev (ou custo marginal real do host isolado existente) + backup de desenvolvimento + domínios/demais serviços efetivamente necessários.

**F2** = VPS app + máquina/banco PostgreSQL independente + object storage (GB, PUT, GET e saída) + backups do banco/PITR/WAL + replicação de anexos + logs/monitor independente + custos de rede/túnel, IP, proteção, impostos, operação/suporte e eventuais serviços de filas/antimalware. n8n, Portal, APIs de leads e agentes têm centros de custo próprios; incluir no **TCO total do ecossistema**, não imputar tudo apenas à VPS do CRM.

Preços vistos em conversa (ex.: US$6,29/US$9,89 ou ofertas promocionais) **não estão homologados**. A [Leaseweb informa API de catálogo que expõe preços por produto/local](https://kb.leaseweb.com/kb/leaseweb-vps/ordering-api-for-vps/), com ofertas sujeitas à organização comercial e condições do pedido. Cotações devem documentar plano, moeda, país, prazo, setup, VAT, renovação, capacidade e benefícios.

[Leaseweb Object Storage](https://www.leaseweb.com/products-services/object-storage) informa tarifa sob demanda publicada de €0,01733/GB-mês, mais saídas e operações, sujeita à região e contrato. Não apresentar o valor como custo integral de backups nem inferir localização de bucket sem confirmar oferta.

**Matriz para solicitar cotação atual (a ser preenchida com valores oficiais):**

| Custo por mês | F1 dev | F2 produção econômica | F2 alternativa robusta |
|---|---|---|---|
| Aplicação | VPS 1 candidata | VPS 2 candidata | Public Cloud general purpose |
| Banco | Dev local | VPS independente com túnel | Public Cloud separado/serviço gerenciado, se disponível |
| Objetos e anexos | Sandbox reduzido | Storage S3 compatível | Mesmo requisito, com requisitos de resiliência e localidade |
| Backup/PITR + offsite | Backup dev | DB + anexos externo independente | Idem + replica/failover se aprovado |
| Rede/monitor/cofre/segurança | Simples, segregado | Orçamento explícito | Orçamento explícito |
| Disponibilidade | Sem SLA de produção | Sem HA, dependente de recuperação | Alta disponibilidade possível **somente se desenhada e implantada** |

**Não inventar orçamento de produção completo sem preço validado**, e não considerar metas genéricas de US$30-60 como gasto comprovado.

## 8. Critérios de evolução, sem gatilho de número de clientes arbitrário

Monitorar depois do lançamento autorizado: p95/p99 da API, CPU/RAM sustentados, IOPS/latência de PostgreSQL, lock/conexões, consumo/atraso das filas, incidentes, storage, custos e usuários simultâneos. Crescer quando métricas e risco/SLA exigirem e o orçamento justificar. Primeiro otimizar consultas, filas e cache, depois dimensionar instâncias e banco; não fazer upgrade automático sem alçada.

## 9. Pendências F02 para aprovação futura

| ID | Tema | Estado / documento dependente |
|---|---|---|
| F02-P01 | Aprovar F1 candidato, capacidade e isolamento real de homologação | Ainda em revisão; F01 inventário factual |
| F02-P02 | Selecionar região e custo cotado, com latência medida e LGPD | Aberto; ARQ-01 |
| F02-P03 | Escolher PostgreSQL em VPS ou gerenciado/Cloud e especificar VPN/TLS/rede/segurança | Aberto; CORE/SEG-02 |
| F02-P04 | Selecionar storage, política de localização e acesso privado | Aberto; anexos/LGPD |
| F02-P05 | Especificar backup DB, PITR, retenção e restauração; custos | Aberto; CON-01 |
| F02-P06 | Confirmar replicação anexos 15 min, retenção 30d, antimalware e custo | Política funcional existente, execução aberta |
| F02-P07 | Definir durabilidade de filas, cache e processo de recuperação | Aberto; ARQ-02/INT |
| F02-P08 | Definir observabilidade independente e cofre/identidades | Aberto; ARQ-03/SEG-02 |
| F02-P09 | Propor RPO/RTO por serviço, orçamento total, risco de SPOF e aceite consciente | Aberto; CON-01/OPS-01 |
| F02-P10 | Aprovar documento final de topologia/custos e atualizar ARQ-01, sem provisionamento | Aberto; responsável |

## 10. Resultado deste documento

- **Aprovada previamente:** estratégia econômica em fases e preservação de separações e políticas já existentes.
- **Proposta agora, sem aprovação presumida:** alocação lógica/física dos serviços, responsabilidades, gates de segurança e matriz de orçamento.
- **Não concluídos:** fornecedor e localidade finais, SKU/precificação, isolamento físico comprovado, contingência, riscos, RTO/RPO, homologação de produção e demais bloqueios.
- **Não realizado:** compra, código, deploy, teste real, acesso ao servidor, mudança de DNS ou configuração Cloudflare.

**Próximo encaminhamento recomendado:** revisar e eventualmente aceitar F02 **como base documental**, sem declarar ARQ-01 resolvida; depois preparar comparativo de orçamento F02-P02..P05, F02-P07..P09 e submeter decisões específicas para fechamento P0.
