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

- **F07-D01**: adotar E1B como **alvo de projeto para primeira produção**, sujeito aos critérios de qualidade/recuperação, ou escolher E2 de imediato?
- **F07-D02**: estabelecer limite mensal de investimento para a **infraestrutura do CRM** e outro limite distinto para o **ecossistema completo** (Portal, n8n, agentes, duas APIs e segurança).
- **F07-D03**: confirmar região preferencial após cotação e latência, sem assumir que Washington é mais rápida.
- **F07-D04**: definir orçamento e metas condicionais de reposição/recuperação; se requisitos de RPO/RTO não forem cumpridos, revisar para E2 antes de admitir clientes.
- **F07-D05**: aceitar esta ficha como guia de fechamento documental ARQ-01 após conciliação com F01 e decisões específicas, sem declarar pronta a produção.

**Resultado:** F07 aceita como **guia documental** de fechamento; as deliberações F07-D01..D04 permanecem pendentes de decisão específica, orçamento, evidência e homologação. **ARQ-01, CON-01 e demais gates P0 permanecem abertos.** Não há software, contrato, instalação ou testes iniciados. Evitar criação de novas fichas sucessivas sem resolução de pendências concretas.
