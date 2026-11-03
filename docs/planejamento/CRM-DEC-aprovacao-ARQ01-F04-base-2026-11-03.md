# CRM-DEC — Aceite da ARQ01-F04 como base documental

**Data:** 03/11/2026.
**Manifestação:** “ok” após apresentação de F04 e da recomendação de priorizar R2 Standard para anexos, Leaseweb Object Storage On Demand como segunda cópia e PostgreSQL com backup orientado a PITR.
**Estado:** APROVADA SOMENTE COMO BASE DOCUMENTAL E DIREÇÃO DE ESTUDO; não homologada.

## Alcance do aceite

- Aceita [ARQ01-F04](P0-ARQ01-F04-orcamento-object-storage-backup-recuperacao.md) como referência de análise de custos e recuperação, sem alterar os requisitos funcionais e de segurança já aprovados.
- Prioriza **para avaliação** R2 Standard (objetos vivos privados), Leaseweb Object Storage On Demand (destino independente de réplica) e pgBackRest ou equivalente (backup PostgreSQL com WAL/PITR), sujeitos a cotação, segurança, transferência internacional, compatibilidade e teste na fase autorizada.
- Mantém anexos com replicação incremental assíncrona em intervalo-alvo de 15 minutos e retenção técnica de 30 dias; **não transfere automaticamente** esses números à política do PostgreSQL.
- Preços são apenas referências públicas e não representam custo total ou disponibilidade comercial confirmada.
- Mantêm-se abertas as pendências F04-P01 a F04-P07, RPO/RTO numéricos, isolamento do banco, contingência, credenciais/imutabilidade, localização, LGPD, monitoramento e ARQ-01 P0 parcial.

**Aprovação documental não autoriza** programação, compra, contratação, instalação, configuração, migração ou testes reais. Planejamento global e homologação técnica continuam obrigatórios.
