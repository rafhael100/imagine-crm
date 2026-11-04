# P0-ARQ01-F07 — Ficha de fechamento: investimento, disponibilidade e riscos do CRM Imagine

**Data:** 03/11/2026.
**Status:** **APROVADA COMO BASE DOCUMENTAL DE FECHAMENTO** em 03/11/2026, conforme [registro de aceite](CRM-DEC-aprovacao-ARQ01-F07-base-2026-11-03.md). **Não** homologa E1B para produção, preços, infraestrutura física ou RPO/RTO.
**Origem:** [ARQ01-F06 aprovada como base documental](CRM-DEC-aprovacao-ARQ01-F06-base-2026-11-03.md), F01-F05, [P0-ARQ-01](P0-ARQ-01-topologia-fronteiras-plano-fechamento.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md).
**Escopo:** consolidar escolhas mínimas e evidências faltantes para selecionar o modelo de início da produção. Não substitui ARQ-02, ARQ-03, SEG-01/02, CON-01 ou outros P0; não autoriza contratar nem programar.
**Atualização posterior (03/11/2026):** [decisão de três VPS independentes e possibilidade de Cloudflare Pro](CRM-DEC-cloudflare-pro-condicional-tres-vps-independentes-2026-11-03.md) **substitui a hipótese anterior de converter a VPS de desenvolvimento em PostgreSQL produtivo**. Pro será considerado quando seus diferenciais forem relevantes; nenhuma contratação está autorizada.

## 1. Proposta de decisão econômica

**F0 — planejamento:** zero gasto novo com hospedagem.

**F1 — desenvolvimento (apenas após liberação do desenvolvimento):** VPS de testes segregada, banco com dados fictícios/anonimizados, backup de homologação; Leaseweb VPS 1 é opção prioritária para orçamento, não contrato.

**F2 — primeira produção:** **E1B como candidato preferencial de orçamento**, condicionado a validação de recuperabilidade. Configuração candidata: Leaseweb VPS 2 exclusiva de backend Laravel/filas; PostgreSQL em máquina separada dimensionada; cache não substitui fila durável; R2 Standard para anexos privados e segunda cópia independente em Leaseweb Object Storage On Demand; banco com backup externo base + WAL/PITR, recuperação e monitoramento fora das VPS; Cloudflare na borda; APIs de recepção de leads separadas e realmente independentes, Portal/n8n/agentes em seus domínios.

**F3 — crescimento:** comparar migração para Public Cloud, dedicado, réplica/HA e aplicação horizontal conforme custo, usuários simultâneos, IOPS, tempo de restauração e demanda. Não fixar número de clientes como gatilho automático.

A proposta **não equivale** a aprovação do E1B para clientes reais: há pontos únicos de falha na aplicação e no banco. A escolha depende de aceitação formal e evidências da fase de homologação.

## 2. Custos a cotar: não chamar mensalidade de VPS de custo total

| Linha de custo | E1B início | Alternativa E2 redundante | Evidência para fechar |
|---|---|---|---|
| CPU/RAM do Laravel | 1 VPS candidata | Pelo menos 2 instâncias + balanceador | Oferta oficial por região, prazo, preço e renovação |
| PostgreSQL/IOPS | Banco em host independente | Banco com réplica/failover ou gerenciado HA | Disco, IOPS, memória, backup e SLA |
| Anexos | Object Storage principal | Igual, com maior tolerância a falhas | GB, solicitações, saída, jurisdição |
| Segunda cópia e retenção | Storage independente, 30d técnicos de anexos | Idem, com verificação de recuperação | GB real com versões, replicação, operações |
| PostgreSQL base + WAL/PITR | Destino externo isolado | Destino externo + replica adicional | Espaço de WAL, frequência, retenção, restauração |
| Rede e proteção | Cloudflare, firewall, túnel/VPN, cofre | Rede privada/HA conforme desenho | Fornecedor, custo, isolamento, LGPD |
| Observabilidade | Sonda externa + canal de alerta independente | Mais verificações de HA e plantão | Alertas, logs, retenção e responsável |
| Operação e reposição | Responsável pelo incidente, reposição de VPS | Failover monitorado, suporte maior | Tempo humano, custo de trabalho, suporte |
| Outros do ecossistema | Portal, n8n, agentes, API principal/contingência e mensageria separados | Mesmos domínios com expansão | Quadro global de custos; evitar omissões |

Sem preços verificáveis e cotação total não há TCO oficial para aprovar. Comparar valores em **moedas de origem** e depois converter usando premissa cambial e impostos identificados. Ofertas promocionais de conversa não comprovam disponibilidade contratável.

## 3. Metas de recuperação: estudo e risco

| Processo | Referência a avaliar | Evidência / impedimento |
|---|---|---|
| PostgreSQL | RPO 15 min, RTO 4 h, **ainda não aprovados** | Somente comprováveis com base+WAL externo, alertas, restauração e tempo de reposição medidos |
| Backend CRM | RTO 4 h candidato, **não aprovado** | Imagem/repositório/segredos/redeploy com verificação real |
| Anexos | Replicação alvo 15 min aprovada; **não equivale a RPO comprovado** | Medição de atraso, integridade, retenção e restore |
| Filas | Durabilidade, idempotência e reconciliação exigidas | Definir contrato no ARQ-02/INT, sem sucesso falso |
| Captação de leads | Duas APIs receptoras com persistência própria | Sem compartilhar a dependência única da VPS CRM; definir RPO/RTO em ARQ-02 |
| Monitoramento | Alertar mesmo com CRM/n8n/IA indisponíveis | Canal externo e cobertura humana a decidir |

**Falha do banco em E1B:** até restaurar em outro host, o CRM pode ficar indisponível e o RTO superar 4h. Sem comprovação, não vender disponibilidade ou tempo de recuperação contratualmente.

## 4. Riscos que exigem aceite consciente antes de produção

| Risco | Mitigação exigida | Aceite pendente |
|---|---|---|
| Uma VPS app é ponto único | Redeploy reproduzível, backup de configuração e saúde externa | E1B admite indisponibilidade |
| Uma VPS PostgreSQL é ponto único | PITR, WAL externo, plano de reposição e restore validado | Recuperação pode superar meta |
| VPS não tem Private Network nativa | VPN/TLS/firewall sem acesso aberto ao PostgreSQL | Conectividade e controles verificados |
| Falha do mesmo provedor ou região | Cópias offsite, provedores/domínios separados para fluxos críticos | Risco residual explícito |
| Cópia de anexos diverge do banco | Identificadores, manifests, reconciliação | Proteção de integridade |
| Falha/compromisso do storage primário | Conta/segredos separados, lock/retention e segunda cópia | Testes de recuperação |
| Sem operador disponível | Alertas externos e escalação definida | Horários e cobertura escolhidos |
| Custos invisíveis | Matriz de TCO + limiares de escala e revisão | Limite financeiro formal |
| Transferência internacional de dados | Avaliação LGPD, localidade e mecanismo adequado | Aprovação jurídica/técnica |

## 5. Critérios objetivos para fechar o ARQ-01 documentalmente

1. Inventário real ARQ01-F01 confirmado (ativos existentes e o que ainda não foi contratado), sem confundir infraestrutura antiga com novo CRM.
2. Comparar ofertas oficiais de Washington, Montreal e alternativa europeia, incluindo preços de renovação, tráfego e suporte.
3. Selecionar F1 e F2 condicionais, banco em host independente, conexões, storage, backups, monitoramento e fluxos de leads, com custos totais por estágio.
4. Registrar riscos residuais e decisão explícita do responsável sobre E1B versus E2, sem promessa de SLA antes de testes.
5. Registrar RPO/RTO candidatos por serviço e o contrato de aceitação operacional; **homologação prática será na fase autorizada**, não como pré-requisito para redigir o plano.
6. Distinguir explicitamente fornecedor preferido, solução planejada, decisão de compra e serviço provisionado.
7. Respeitar os demais P0 e o gate de aprovação global antes do início da programação.

## 6. Deliberações pendentes para a próxima aprovação

- **F07-D01 — DECIDIDO (03/11/2026):** **E1B aprovado como arquitetura-alvo do primeiro lançamento**, sujeito à homologação de segurança, custo, recuperação, aceitação de risco e disponibilidade. **Se o E1B não cumprir os requisitos, reavaliar e elevar para E2 antes de clientes reais.** [Registro expresso](CRM-DEC-aprovacao-E1B-alvo-primeiro-lancamento-2026-11-03.md).
- **F07-D02**: estabelecer limite mensal de investimento para a **infraestrutura do CRM** e outro limite distinto para o **ecossistema completo** (Portal, n8n, agentes, duas APIs e segurança).
- **F07-D03**: confirmar região preferencial após cotação e latência, sem assumir que Washington é mais rápida.
- **F07-D04**: definir orçamento e metas condicionais de reposição/recuperação; se requisitos de RPO/RTO não forem cumpridos, revisar para E2 antes de admitir clientes.
- **F07-D05**: aceitar esta ficha como guia de fechamento documental ARQ-01 após conciliação com F01 e decisões específicas, sem declarar pronta a produção.

**Resultado:** F07 aceita como **guia documental** de fechamento; F07-D01 está decidido como direção arquitetural; F07-D02..D04 permanecem pendentes de decisão específica, orçamento, evidência e homologação. **ARQ-01, CON-01 e demais gates P0 permanecem abertos.** Não há software, contrato, instalação ou testes iniciados. Evitar criação de novas fichas sucessivas sem resolução de pendências concretas.


## Atualização 03/11/2026 — decisão de arquitetura-alvo E1B

O responsável aprovou expressamente o **E1B como modelo inicial de referência**, sujeito à homologação. [CRM-DEC específica](CRM-DEC-aprovacao-E1B-alvo-primeiro-lancamento-2026-11-03.md). Isso fecha **somente F07-D01** em nível de **direção de projeto**, não a infraestrutura real. E1B não permite clientes reais sem validações e aceite de riscos; quando inviável técnica ou financeiramente, avaliar E2. F07-D02 orçamento, D03 região e D04 metas/risco ficam abertos. ARQ-01 continua P0 parcial e não há liberação de programação.


## Anexo A — Estimativa financeira incremental preliminar (03/11/2026)

**Estado:** estudo/cotação referencial, **não é orçamento aprovado nem custo completo homologado**. Não altera F07-D01 (E1B como arquitetura-alvo), não encerra F07-D02, D03 ou D04. Os valores abaixo são referências públicas de terceiros ou fornecedores, sujeitos a confirmação no checkout/contrato, localização, renovação, impostos e câmbio.

### A.1. Computação por etapa

| Etapa | Cenário de computação | Preço-base de referência |
|---|---|---|
| F0 planejamento | Não contratar servidores novos | US$0 de incremento |
| F1 desenvolvimento autorizado | Leaseweb VPS 1, 4 vCPU compartilhadas, 6 GiB, 100 GB NVMe | US$6,29/mês nos EUA em comparador; precisa confirmar contrato |
| F2 primeiro lançamento E1B | Leaseweb VPS 2 para Laravel, 6 vCPU/16 GiB/200 GB + VPS 1 independente para PostgreSQL, sujeita a sizing | US$9,89 + US$6,29 = **US$16,18/mês apenas computação** em referências dos EUA |
| F2 E2 redundante | Duplicar app, replicar/failover PostgreSQL, balanceamento | Não cotado; **não inferir preço** por multiplicar servidores |

A VPS não inclui Private Network nativa; proteger aplicação↔banco com solução homologada, possivelmente túnel/VPN+TLS e políticas de firewall. Public Cloud/dedicado são alternativas se requisitos de isolamento, disponibilidade ou tempo de recuperação do E1B não couberem no preço. As referências de preço podem exigir fidelidade; não contratar VPS de 12 meses enquanto não houver necessidade real de uso.

### A.2. Componentes variáveis publicados

| Serviço | Preço de referência | Observação |
|---|---|---|
| Cloudflare Free | US$0/mês | DNS, SSL, mitigação e WAF básico; não presume suficiência para todos os riscos |
| Cloudflare Pro opcional | US$20/mês faturado anualmente OU US$25/mês pagamento mensal | Só se requisitos comprovados justificarem |
| Cloudflare R2 Standard | US$0,015/GB-mês acima de franquia de 10 GB-mês; operações conforme volume; sem cobrança de saída | Franquia é por conta e compartilhada; não necessariamente por bucket |
| Leaseweb Object Storage On Demand | €0,01733/GB-mês + PUT/GET e transferência de saída | Disponibilidade e localidade concretas a cotar |
| UptimeRobot Free | US$0, até 50 sondas, checagem a cada 5 min | Não substitui alertas críticos de PostgreSQL, filas, backup e rota independente; avaliar serviço pago/combinação |
| UptimeRobot Solo | A partir de US$144/ano (equiv. US$12/mês) | Plano opcional com checagens mais frequentes; pagamento anual |
| pgBackRest | Sem licença de software; custo de storage/operação/rede permanece | Backup base, incremental/diferencial, WAL, PITR e S3 |

Fontes consultadas: 
- https://kuraberu.app/vps/offer/leaseweb-vps-1-us
- https://kuraberu.app/vps/providers/leaseweb
- https://www.leaseweb.com/products-services/cloud/virtual-private-server
- https://www.cloudflare.com/plans/
- https://developers.cloudflare.com/r2/pricing/
- https://www.leaseweb.com/products-services/object-storage
- https://uptimerobot.com/pricing/
- https://pgbackrest.org/index.html

### A.3. Simulação de objeto/backup para carga pequena

Hipótese ilustrativa, **não projetada a partir de clientes reais**: 50 GB-mês de anexos vivos no R2; 100 GB-mês ocupados pela réplica histórica de anexos em Leaseweb (retenções/versões podem ampliar), mais 30 GB-mês de backup PostgreSQL em Leaseweb. Supõe-se que a franquia de R2 de 10 GB-mês esteja disponível integralmente.

- R2 Standard, apenas anexos: (50-10)×US$0,015 = **US$0,60/mês** de espaço.
- Leaseweb, replica de anexos + banco: (100+30)×€0,01733 = **€2,25/mês** de espaço.
- VPS aplicação e VPS banco: **US$16,18/mês**, referência de terceiros para US.
- **Subtotal mínimo de itens quantificados: US$16,78 + €2,25/mês**, moedas separadas, **não** custo total ou orçamento contratável. Exclui operações/egress, contingência adicional para PostgreSQL, rede/VPN, monitoramento interno/externo, antimalware, cofre, backup imutável, restore, impostos, suporte, plantão e operações humanas.

### A.4. Provisões gerenciais (sem cotação ou aprovação)

| Uso | Meta provisória de reserva, sem compromisso | O que não cobre automaticamente |
|---|---|---|
| Planejamento F0 | **US$0** de novas contratações | Custos de infraestrutura legada já ativa |
| Desenvolvimento F1 | **Até US$30/mês** para nova infraestrutura dev após autorização | Horas de trabalho, IA, serviços legados, contratação anual obrigatória |
| Primeira produção E1B do CRM | **Teto de estudo US$120/mês equivalente**, a refinar após cotação em USD/CAD/EUR | Custos humanos, Portal, n8n, agentes, duas APIs de leads, provedores de mensageria/IA; não garante todos os requisitos nesse teto |
| E2 | Sem meta definida | Exige cotação de HA, balanceador, réplica e suporte |

**US$120 não foi aprovado como limite financeiro**; serve de referência para avaliar se E1B oferece recuperação com custo razoável. Se segurança/durabilidade/recuperação exigirem ultrapassar esta meta, **não eliminar controles**: documentar o custo necessário e submeter a aprovação. Não presumir que baixo custo de disco equivale a baixo custo de operação.

### A.5. Separar o custo do CRM do custo de toda a Imagine

**Custo CRM E1B:** VPS Laravel, host PostgreSQL, filas/cache CRM, anexos, backup DB/anexos, observabilidade, rede, suporte, incidentes e segurança.

**Custo do ecossistema adicional a medir:** Portal Imagine com infraestrutura própria, Sites/Workers/versões, API de recepção de leads principal, API independente de contingência, filas com persistências independentes, n8n em host próprio, sete agentes técnicos + Supervisor em servidor próprio, monitoramento transversal e cofre, IA/LLM, WhatsApp/mensageria, gateways, domínio/DNS e serviços legados. **Não afirmar custo total do ecossistema sem inventário real e faturas.**

### A.6. Pendências de cotação e fechamento, vinculadas à F07

- **F07-D02:** obter preços oficiais Leaseweb VPS 1/VPS 2 em WDC-02 e MTL-02 (mensal, anual, descontos, renovação e moeda), além de opções Public Cloud e demais custos para caso de escalada.
- Confirmar se recursos VPS 1 são adequados a PostgreSQL em produção sob IOPS/latência/carga; VPS 2 e gerenciado são alternativas e podem aumentar TCO.
- Modelar armazenamento com versões reais, backups WAL, 30 dias de cópia técnica de anexos, requests/egress e recuperação fora da VPS; definir retenção PostgreSQL separadamente.
- Preencher **duas colunas financeiras distintas**: novo investimento mensal CRM e gasto mensal incremental do ecossistema mais custo já existente.
- Com preços e latência real, concluir **F07-D03 (região)** e **F07-D04 (RPO/RTO, reposição e aceite de riscos)**; não declará-los resolvidos antecipadamente.
- Nenhuma compra, configuração, programação, teste real ou implantação autorizada.


## Anexo B — Proposta de otimizacao do lancamento sem reduzir seguranca (03/11/2026)

**Estado:** ESTUDO NOVO DO RESPONSAVEL PARA REVISAO, **NAO APROVADO COMO ORCAMENTO**. Solicitacao: reduzir custo de lancamento, preservando qualidade e seguranca como requisitos indispensaveis. **E1B continua arquitetura-alvo**; nao substituir decisoes de rede, DB separado, dois receptores de leads, copia de anexos, backup e seguranca por atalhos baratos.

### B.1 Meta financeira e fronteiras

- **Meta revisada de estudo:** US$40–60/mes **somente para nova infraestrutura tecnica incremental do CRM E1B**, sem constituir teto aprovado, SLA ou cotacao. A meta anterior US$120/mes era **provisao gerencial nao aprovada**, e nao limite obrigatorio.
- **F0 planejamento:** zero gasto novo com servidores.
- **F1 desenvolvimento aprovado futuramente:** VPS de teste sob demanda, infraestrutura propria do desenvolvimento, com dados sinteticos.
- **F2 lancamento (decisão mais recente):** preservar a VPS 1 de desenvolvimento/homologacao e contratar, quando autorizado, **duas VPS adicionais e independentes para producao**: VPS 2 candidata para Laravel e outra VPS candidata para PostgreSQL. **Nao converter a VPS de desenvolvimento em host produtivo**. Validar capacidade, seguranca e custos antes da homologacao. Ver [CRM-DEC posterior](CRM-DEC-cloudflare-pro-condicional-tres-vps-independentes-2026-11-03.md).
- **Desenvolvimento e homologacao permanecem em VPS separada**, com dados e credenciais independentes. Testes locais e CI efemero podem complementar esse ambiente, mas nao substituem a segregacao decidida e nunca podem usar dados reais ou acesso root de producao.
- **Separar o custo integral do ecossistema:** Portal, n8n, agentes tecnicos, Sites e APIs principal/contingencia com filas independentes continuam fora deste valor de US$40–60. Nao combinar domínios fisicamente para cumprir a meta.

### B.2 Compromissos indispensaveis e como obter menor custo

| Elemento | Estrategia de baixo custo candidata | Condicao inegociavel antes de clientes reais |
|---|---|---|
| Backend Laravel | 1 VPS 2 sob carga homologada; PHP-FPM e workers/cron com limites; Redis/Valkey cache no host app se validado | Monitoramento e limites; filas **duraveis**, nao apenas cache volátil |
| Banco | VPS independente com PostgreSQL e tunel WireGuard + TLS/firewall | Sem exposicao publica DB; RLS/tenant_id, segregacao contas, restore e desempenho comprovados |
| Entrada publica | Cloudflare Free e WAF basico; avaliar Tunnel/origin restriction | Nao abrir origem para bypass nem usar cache para dados pessoais ou sessao; Free WAF nao cobre tudo |
| Arquivos | R2 Standard privado sob demanda | Quota, autorizacao por tenant, antimalware, quarentena, URLs controladas |
| Replica anexos | Leaseweb Object Storage On Demand, segunda copia e retencao conforme regras ja aprovadas | Meta de replicacao 15min, 30 dias tecnicos, integridade e independencia de credenciais |
| Backup DB | pgBackRest base+WAL/PITR em destino offsite | Retencao a decidir, verificacao automatica, criptografia, restauracao ensaiada |
| Antimalware | ClamAV ou equivalente gratuito, possivelmente em worker limitado do host app | Inspecao obrigatoria de uploads, fail-closed se indisponivel, consumo RAM/CPU homologado; se nao suportar, destinar recursos pagos |
| Monitor | Sondas externas baratas/gratuitas + checks internos e alerta fora do CRM | Alertas de DB/WAL/fila/backup/storage, canal independente e cobertura capaz de cumprir metas de assuncao; plano free com checagem a cada 5min pode nao bastar para criticos |
| Segredos | Ferramenta self-hosted/criptografia de configuracoes com chaves fora do repo; MFA e menor privilegio | Cofre/SEG-02, rotacao, recuperacao e auditabilidade devem ser homologados; nada em .env publicado ou Git |
| Operacao | CI/CD com ferramentas abertas, logs limitados pela retencao aprovada | Evidencia de backup/restore, auditoria e atualizacoes regulares; nao remover controles para baixar custo |

**Nao cortar:** separacao DB app, controles multiempresa, MFA administrativo, criptografia, offsite backup + replica anexo, testes de recuperacao antes de dados reais, anti-malware, durabilidade/idempotencia de filas, alerta externo e fluxo duplo independente de leads. Se uma ferramenta gratuita nao cumprir o requisito, **o custo pago vira indispensavel**.

### B.3 Custo ilustrativo por faixa, sem cotacao integral

| Categoria | Fonte/valor referencial | Escolha |
|---|---|---|
| VPS de aplicacao (Leaseweb VPS 2) | US$9,89/mes em comparador de oferta US, **nao oficial** | Cotar regiao, prazo e renovacao |
| VPS de banco (Leaseweb VPS 1) | US$6,29/mes em comparador de oferta US, **nao oficial** | Cotar; validar RAM/IOPS e exigir servidor independente |
| Computacao combinada | US$16,18/mes de referencia, sem IVA/extra | Nao equivale a infraestrutura pronta |
| R2 Standard | US$0,015/GB-mes apos franquia mensal 10 GB; requests sujeitos a limites | Uso sob demanda |
| Leaseweb Object Storage | €0,01733/GB-mes + requests/egress | Replica anexos e/ou backups conforme independencias |
| Cloudflare Free / Turnstile Free | Sem mensalidade em limites publicados | Protecao basica, nao substitui seguranca no app |
| Monitoramento externo | UptimeRobot Free com checagens 5min; 1min em plano pago segundo tabela de 2026 | Criticos podem exigir plano melhor/segunda sonda; nao considerar free suficiente sem validar |
| Backup e rede | pgBackRest/WireGuard sem licenca | Operacao, armazenamento, incidentes e volume custam |

A **meta US$40–60/mes** representa uma **reserva financeira de estudo**, e nao projecao verificavel do custo total. **Nao inclui** equipe/plantao, Portal, n8n, agentes, duas APIs externas de captacao, mensalidades de terceiros, IA, mensageria nem contabiliza os riscos de escalar para E2. Se a soma necessaria exceder US$60, **submeter o custo real**, nao eliminar o controle.

### B.4 Trigger para pagar mais sem atrasar seguranca

1. **Antes de cliente real:** se RPO/RTO propostos nao forem demonstrados ou risco de indisponibilidade for alto para o contrato, revisar E1B e subir para E2, sem prometer SLA de 15min/4h.
2. **Quando carga subir:** priorizar aumento de RAM/IOPS do DB ou VPS backend, apos benchmark e provas de consumo; nao automatizar upgrade por contagem de clientes.
3. **Quando alertas gratuitos falharem:** contratar monitor externo com frequencia/canais necessarios, conservando caminhos independentes e metas de assuncao humana.
4. **Quando scanner/backup causar saturacao:** deslocar worker/av para host isolado ou plano adequado, sem deixar uploads sem scanner ou WAL atrasado.
5. **Quando incidentes revelarem SPOF inaceitavel:** adicionar replica DB/failover, segunda instancia Laravel e balanceamento (E2), validado em projeto separado.

### B.5 Deliberacao

**Atualizado pela decisão posterior:** o modelo de **três VPS independentes** substitui a reutilizacao da VPS dev. Cloudflare **Pro** pode ser preferido quando suas protecoes adicionais forem relevantes; Free nao e uma obrigacao de economia. A antiga meta US$40–60/mes e somente referencia historica de estudo, sem teto aprovado, e deve ser reavaliada com VPS dev + app + banco, Pro, storage e backups.

**Ainda pendentes:** F07-D02 (orcamento CRM e ecossistema), D03 (regiao), D04 (metas/RPO/RTO/risco). Nao homologar nem provisionar. Esta otimizacao nao fecha ARQ-01 ou outros P0.


## Anexo C — Atualizacao aprovada: Cloudflare Pro e tres VPS independentes

**Data:** 03/11/2026. **Fonte de decisão:** [CRM-DEC Cloudflare Pro e tres VPS](CRM-DEC-cloudflare-pro-condicional-tres-vps-independentes-2026-11-03.md).

- **Tres VPS independentes planejadas** (dev/homologação, produção Laravel, produção PostgreSQL), sem reutilizar a VPS de desenvolvimento como banco produtivo. Os tamanhos VPS 1/2 continuam apenas candidatos.
- **Cloudflare Pro permitido e recomendado para avaliação de lançamento** quando Cloudflare Managed Ruleset, OWASP Core Ruleset, maior quantidade de regras customizadas e Super Bot Fight Mode/excecoes de integrações justificarem o custo. Cloudflare Free nao e uma regra fixa; tambem nao existe compra automatica do Pro.
- **Computação ilustrativa em preços de terceiros:** 2 x VPS 1 US$6,29 + 1 x VPS 2 US$9,89 = **US$22,47/mês**; Cloudflare Pro mensal US$25 implica **US$47,47/mês antes de storage, backups, monitoramento, seguranca e operacao**. Confirmar preco/planos/região e moeda.
- A antiga meta US$40–60/mês fica **sob revisão** e nao pode fundamentar corte de controles essenciais. F07-D02 continua pendente de cotacao TCO, tanto CRM como ecossistema.
- **Nao ha contratacao, despesa, programacao ou provisionamento autorizados.** E1B continua arquitetura-alvo condicionada a homologacao, com ARQ-01 P0 parcial.
