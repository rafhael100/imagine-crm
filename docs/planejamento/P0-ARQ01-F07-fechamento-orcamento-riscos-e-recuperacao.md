# P0-ARQ01-F07 — Ficha de fechamento: investimento, disponibilidade e riscos do CRM Imagine

**Data:** 03/11/2026.
**Status:** **APROVADA COMO BASE DOCUMENTAL DE FECHAMENTO** em 03/11/2026, conforme [registro de aceite](CRM-DEC-aprovacao-ARQ01-F07-base-2026-11-03.md). **Não** homologa E1B para produção, preços, infraestrutura física ou RPO/RTO.
**Origem:** [ARQ01-F06 aprovada como base documental](CRM-DEC-aprovacao-ARQ01-F06-base-2026-11-03.md), F01-F05, [P0-ARQ-01](P0-ARQ-01-topologia-fronteiras-plano-fechamento.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md).
**Escopo:** consolidar escolhas mínimas e evidências faltantes para selecionar o modelo de início da produção. Não substitui ARQ-02, ARQ-03, SEG-01/02, CON-01 ou outros P0; não autoriza contratar nem programar.

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
