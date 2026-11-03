# CRM-DEC — Aceite da ARQ01-F02 como base documental

**Data:** 03/11/2026.
**Manifestação do responsável:** “ok”, em resposta à proposta de aprovar F02 como base e prosseguir para PostgreSQL e backups.
**Status:** APROVADA COMO BASE DOCUMENTAL. Escolha física, local, preço, contratos técnicos e infraestrutura de produção não homologados.

Fica aceita a [P0-ARQ01-F02](P0-ARQ01-F02-alocacao-fisica-fases-custos-e-contingencia.md) como referência de planejamento econômico por etapas. Mantêm-se: nenhuma contratação durante planejamento; VPS 1 para desenvolvimento apenas após autorização global; VPS 2 como hipótese inicial para Laravel em produção; PostgreSQL em máquina separada; Cloudflare na entrada; anexos privados externos e backups independentes; Portal, n8n, agentes e APIs de leads com separações já decididas.

A aceitação não fecha ARQ-01 nem os demais P0. Custos, região, conexão segura, durabilidade de filas, RPO/RTO, banco, armazenamento, backup, segurança e monitoramento permanecem pendentes de especificação e homologação. O avanço para [F03](P0-ARQ01-F03-postgresql-backups-dimensionamento.md) é exclusivamente documental.

**Não autorizado:** programação, contratação, provisionamento, SSH, alteração de infraestrutura, testes em produção ou implantação.