# P0-ARQ01-F05 — Continuidade operacional: metas RPO/RTO, riscos e orçamento por serviço

**Data:** 03/11/2026.
**Estado:** MINUTA PARA REVISÃO — **NÃO APROVADA**.
**Referências:** [F04 aceita como base](CRM-DEC-aprovacao-ARQ01-F04-base-2026-11-03.md), [IN-143](IN-143-continuidade-operacional-recuperacao-inteligente-desastres.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md), [P0-ARQ-01](P0-ARQ-01-topologia-fronteiras-plano-fechamento.md).
**Limites:** não iniciar programação, provisionamento, contratação, migração, testes reais nem mudar infraestrutura.

## 1. Conceitos e princípios

- **RPO** = intervalo máximo tolerado de dados perdidos após um incidente grave; **RTO** = tempo-alvo para restaurar a funcionalidade. São metas de projeto, não SLAs garantidos sem homologação e orçamento.
- **15 minutos de replicação de anexos** é periodicidade-alvo funcional aprovada; não é prova de RPO<=15 min em todos os incidentes.
- **RPO de banco 15 min / RTO 4h** foram hipóteses anteriores, **ainda não aprovadas nem tecnicamente demonstradas**. Primeiro validar se a arquitetura de VPS autogerida pode efetivamente entregar esses números.
- Com uma VPS para Laravel e outra para PostgreSQL, sem réplicas ativas/failover, há pontos únicos de falha e restauração pode demorar mais que a meta. Nenhuma meta pode ser vendida como garantia contratual prematuramente.
- Políticas de fila de captação de leads permanecem independentes: API principal e contingência têm persistência durável própria, 3 dias em fila normal + 15 dias em fila de falhas, reconciliação e idempotência, conforme decisões anteriores.

## 2. Quadro comparativo preliminar de criticidade

| Serviço | Proteção prioritária | Diretriz de continuidade | Meta numérica |
|---|---|---|---|
| Entrada de leads / APIs principal e contingência | Persistência independente e confirmação só após gravação durável | Operar fallback com falhas independentes; alertar se ambas indisponíveis | **Pendente ARQ-02 / CON-01** |
| CRM Laravel / interface e APIs | Deploy reproduzível, infra e código versionados, recuperação por novo host | Aceitar limitação de uma VPS no começo apenas mediante risco expresso | **Pendente** |
| PostgreSQL CRM | Backup base+WAL/PITR externo, integridade, restore controlado | Minimizar perda de transações e recuperar em VPS substituta; estudo de réplica futura | **Candidata 15 min / 4h, NÃO APROVADA** |
| Anexos CRM | Objeto principal privado, cópia em outro domínio de falha, integridade e versões | Replicação-alvo a cada 15 min; retenção técnica 30d | **RPO/RTO não homologados** |
| Filas/worker CRM | Persistência durável/idempotência; DLQ/reconciliação | Retomar sem duplo efeito e sem descarte silencioso | **Pendente** |
| Portal Imagine | Servidor e banco segregados | Falha do Portal não bloquear acesso ao CRM nem durabilidade de leads | Fora da meta do CRM; definir no plano Portal |
| n8n e agentes técnicos | Ambientes separados, credenciais e versionamento independentes | Falhas não autorizam ações irrestritas; funções críticas de captação persistem | **Pendente no seu domínio** |
| Observabilidade/alerta | Caminho de alerta externo à UI, IA e VPS monitorada | Detectar indisponibilidade do CRM e falha de backup | **Pendente ARQ-03 / OBS-01** |

## 3. Três níveis econômicos de continuidade — sem equivalência de qualidade

**Nível E0 — Desenvolvimento (sem cliente):** uma VPS isolada, dados sintéticos, backup de desenvolvimento; mínimo de custo e sem SLA produtivo.

**Nível E1 — Produção enxuta e recuperável (candidato inicial):** VPS para Laravel, VPS separada para PostgreSQL, object storage privado, backups PostgreSQL externos + WAL/PITR, segunda cópia de anexos, monitor independente e procedimentos documentados. **Sem alta disponibilidade**; depende de restauração e da disponibilidade de pessoas. Liberação só após homologação, testes e aceite explícito de pontos únicos de falha.

**Nível E2 — Produção redundante:** múltiplas instâncias Laravel atrás de balanceador, PostgreSQL com réplica e failover validado, isolamento de falhas, storage e backups independentes, alertas/plantão adequados. Maior custo, mas menor exposição à indisponibilidade; não presumir que ter dois servidores seja automaticamente HA.

## 4. Recomendações técnicas que ainda precisam de decisão

1. Para **banco**, avaliar backup de base periódico + arquivamento contínuo WAL, retenção e chave isoladas; simulação de recuperação de VPS perdida sem reuso de segredos antigos. Comparar custos e riscos com gerenciado.
2. Para **anexos**, manter R2 Standard e Leaseweb On Demand como candidatos; segunda cópia tecnicamente independente, retenção e testes, validação de região/transferência internacional.
3. Para **reposição do host**, definir imagens/artefatos versionados, DNS/proxy, acesso administrativo escopado e processo de restauração; evitar intervenção automática destrutiva sem aprovação.
4. Para **alertas**, canal externo independente de CRM, n8n, agentes e painel; monitorar saúde, WAL atrasado, erro de backup, fila acumulada e replicação de objetos.
5. Para **economia**, selecionar E1 somente se as metas RPO/RTO pretendidas couberem nas evidências e no risco aceito; elevar para E2 quando a necessidade medida justificar.
6. Para **contratos**, não prometer RPO 15m/RTO 4h nem disponibilidade percentual antes de testar recuperação completa com participação humana e medir seus tempos.

## 5. Matriz de custos a preencher antes de fechar ARQ-01

| Categoria | E0 dev | E1 produção econômica | E2 redundante |
|---|---|---|---|
| Backend | 1 VPS dev candidata | 1 VPS aplicação | Pelo menos 2 instâncias + balanceamento |
| Banco | PostgreSQL de teste | 1 VPS separada + backup/WAL | Banco com réplica/failover homologados |
| Anexos | Bucket teste | R2 primário + backup secundário candidato | Igual, com requisitos adicionais de disponibilidade |
| Backups PostgreSQL | Cópia dev | Base+WAL/PITR offsite, imutabilidade/segregação | Mesmo padrão + testes e capacidade de recuperação reforçada |
| Observabilidade e operação | Desenvolvimento | Alertas externos + responsável de incidentes | Alertas + capacidade de intervenção e failover |
| Custo total | Cotar após autorização | Cotar antes de cliente real | Cotar para gatilho de escala |

Não usar referências promocionais de VPS nem tarifas por GB como **TCO completo**. Incluir impostos, câmbio, renovação, egress, requisições, VPN, tráfego, coleta de logs, backup WAL, mão de obra e custos do ecossistema quando cabíveis.

## 6. Pendências para decisão posterior

- **F05-P01**: definir RPO/RTO do PostgreSQL e aplicação CRM, com custos e evidências.
- **F05-P02**: definir RPO/RTO dos anexos, considerando replicação de 15 minutos sem prometer perda máxima.
- **F05-P03**: definir objetivos de recepção de leads, filas e reconciliação em ARQ-02.
- **F05-P04**: escolher E1 vs E2 para entrada em produção, com aceite formal de riscos residuais.
- **F05-P05**: aprovar janela de backup de PostgreSQL e política de retenção/WAL e isolamento.
- **F05-P06**: confirmar orçamento/plantão/monitoramento, dependências e testes de restauração documentados.
- **F05-P07**: aprovar plano de contingência por falha de host, banco, storage, rede, região e segurança.

**Status final:** F05 é minuta; **ARQ-01 permanece P0 parcial**, com CON-01, ARQ-02/03 e SEG-01/02 não encerrados. Sem programação, contratação, configuração ou testes reais.
