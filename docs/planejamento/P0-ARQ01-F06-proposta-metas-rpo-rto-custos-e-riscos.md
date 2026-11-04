# P0-ARQ01-F06 — Metas candidatas de RPO/RTO, níveis de proteção, custos e aceite de riscos

**Data:** 03/11/2026.
**Estado:** **APROVADA COMO BASE DOCUMENTAL** em 03/11/2026, conforme [CRM-DEC de aceite](CRM-DEC-aprovacao-ARQ01-F06-base-2026-11-03.md). **Níveis de produção, metas RPO/RTO, riscos e custos não homologados.**
**Origem:** [F05 aceita como base](CRM-DEC-aprovacao-ARQ01-F05-base-2026-11-03.md), [IN-143](IN-143-continuidade-operacional-recuperacao-inteligente-desastres.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md), [F04](P0-ARQ01-F04-orcamento-object-storage-backup-recuperacao.md).
**Objetivo:** converter o diagnóstico de E0/E1/E2 em opções verificáveis para recuperação, considerando ausência de clientes e caixa limitado.
**Proibição:** nenhum número abaixo é objetivo/SLA aprovado; sem implantação, programação, contratação, configuração nem teste real.

## 1. Alternativas para o início de produção (apenas critérios de comparação)

| Opção | Estrutura adicional | Benefício | Limite e risco | Faixa de investimento |
|---|---|---|---|---|
| **E0 — desenvolvimento** | VPS dev isolada, PostgreSQL com dados fictícios, cópia dev | Investimento mínimo sem dados reais | Sem garantia de disponibilidade ou recuperação produtiva | Custear apenas desenvolvimento |
| **E1A — recuperação econômica** | VPS app e VPS PostgreSQL, anexos privados e cópia externa, PITR com WAL, alertas externos | Dados restauráveis mesmo após perda do host, sem réplica ativa | Pode levar muitas horas para restaurar; exige operador e servidores disponíveis; pontos únicos de falha | Baixo investimento infra, alto risco de downtime |
| **E1B — recuperação reforçada** | E1A + imagens prontas/procedimentos testados, provisão de servidor de substituição e monitoramento mais rigoroso | Recuperação potencialmente mais rápida sem segunda instância ativa | RTO 4h ainda não garantido; capacidade de intervenção é pré-requisito | Investimento intermediário, especialmente operacional |
| **E2 — redundância** | App duplicado com balanceador, PostgreSQL replicado/failover, observabilidade/identidades independentes, backups externos | Menor indisponibilidade esperada por falha única | Mais custo e complexidade; correlação de falhas e corrupção continuam possíveis | Maior gasto fixo e operação |

**Recomendação para estudo:** E0 enquanto sem clientes. Antes de entrada real, comparar E1B com E2 pelo custo total e pelo RTO realisticamente alcançável. E1A só pode ser considerado com risco explícito de indisponibilidade prolongada e sem compromisso contratual incompatível.

## 2. Metas candidatas a simular, nunca prometer antes de evidência

| Serviço | Meta candidata de planejamento (E1B) | Condição técnica necessária | Estado |
|---|---|---|---|
| **PostgreSQL** | **RPO <=15 min; RTO <=4 h**, se houver evidência | Backup íntegro + WAL arquivado em destino externo; frequência e processo de restauração/operador que sustentem esses limites, inclusive troca de host | **Apenas hipótese histórica; alto risco de não cumprir em VPS econômica** |
| **Laravel/API CRM** | **RTO <=4 h**, se viável; RPO de dados persistidos não aplicável ao processo stateless | Infraestrutura substituível, configuração e segredos recuperáveis, deploy reproduzível e validações pós-recuperação | **Apenas meta candidata** |
| **Anexos** | **RPO <=15 min a estudar**; RTO depende de volume e da integridade da réplica | Replicação incremental de 15 min já aprovada como rotina, mas atrasos/falhas devem ser detectados; testes de volume real | **Não inferir garantia de RPO pela periodicidade** |
| **Fila durável do CRM** | Sem perda silenciosa e recuperação idempotente, RPO/RTO específicos pendentes | Persistência e reconciliação; separar fila de cache; evitar duplicar efeitos | **Dependente dos contratos ARQ-02/INT** |
| **APIs receptoras de leads** | Recebimento redundante com persistências independentes, sem sucesso falso | Dois domínios de falha, entrega idempotente ao CRM, monitor de falha de ambas | **Conceito funcional aprovado; metas numéricas pendentes ARQ-02** |
| **Monitoramento** | Alertas críticos externos mesmo se CRM/n8n/IA falharem | Sondas e canais independentes com política de escalonamento | **Especificação ARQ-03 pendente** |

**RPO/RTO são alvos e precisam de janela de medição, testes e responsabilidade humana.** Uma falha de uma VPS sem estoque de substituição ou com perda de acesso do provedor pode ultrapassar quatro horas. Sem réplica, o banco continua indisponível até recuperação.

## 3. Contratos mínimos de continuidade antes do lançamento com clientes

- Fixar **quem responde** por alerta e recuperação, em que horário, e limites de assunção definidos na IN-168, sem presumir plantão humano 24x7.
- Definir situações que interrompem atendimento ou captação; a recepção pública de leads em serviços independentes não pode compartilhar o ponto de falha da VPS CRM.
- Identificar atraso máximo de WAL arquivado, último backup íntegro, erro na replicação de anexos, health-checks, SSL/DNS, credenciais e custo de reposição.
- Proibir reativar processos que reproduzam cobranças/mensagens/ações externas sem reconciliação e autorização.
- Definir em projeto critérios verificáveis: perda da VPS app, perda da VPS banco, perda de credenciais, perda dos dois buckets, falha de região, corrupção lógica, acesso indevido e recuperação após restauração.
- Para produção, aceitar formalmente os pontos únicos de falha se E1 for escolhido; clientes não recebem SLA de alta disponibilidade não ofertado.
- Só permitir testes de continuidade e execução depois da aprovação global do planejamento e da abertura formal da etapa de desenvolvimento/homologação.

## 4. Como estimar custo sem preços inventados

**Custo total E1B**: compute app + compute banco + armazenamento dos dados (R2) + réplica de anexos externa + backup base/WAL PostgreSQL + tráfego/requests + alerta externo + VPN/TLS/rede + manutenção/plantão + reserva para reposição emergencial + impostos.

**Custo incremental E2 sobre E1B**: segunda instância app + balanceador/proxy HA + instância de réplica PostgreSQL + componentes de failover/rede + aumento de monitoramento, operações, backups e testes.

Não atribuir percentual de disponibilidade fictício, nem prometer que E2 tem failover sem interrupção. Sem cotação de VPS/Cloud, object storage, equipe e ferramentas, nenhum dos dois tem preço integral auditado. O preço promocional da VPS **não é custo total do CRM**.

## 5. Decisões específicas para submeter

- **F06-P01:** aceitar E1B como *candidato preferencial* para entrar em produção somente após homologação, ou preferir E2 desde o lançamento?
- **F06-P02:** validar ou ajustar metas numéricas candidatas de PostgreSQL (RPO 15 min / RTO 4 h), observando custo e risco.
- **F06-P03:** definir RTO do Laravel e dos anexos, inclusive reindexação/reconciliação de metadados.
- **F06-P04:** aprovar calendário de backup PostgreSQL (backup de base, WAL, retenções, chaves, simulações) e orçamento.
- **F06-P05:** definir requisitos reais de equipe, horários e acionamento, com contratação/plantão apenas se deliberado.
- **F06-P06:** aprovar custo máximo inicial e limite que obriga reavaliar E2.
- **F06-P07:** submeter política de recepção de leads e filas ao ARQ-02; não herdar indisponibilidade do CRM.
- **F06-P08:** homologar localização/LGPD, contratos, ferramentas e riscos após aprovação do plano global.

**ARQ-01 e CON-01 permanecem abertos.** O aceite documental não escolhe E1B, não fixa metas numéricas nem autoriza operação. Próxima ficha consolidada de decisão: [ARQ01-F07](P0-ARQ01-F07-fechamento-orcamento-riscos-e-recuperacao.md). Não libera código/infra/compra.
