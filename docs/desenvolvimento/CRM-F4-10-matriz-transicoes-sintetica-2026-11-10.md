# CRM F4-10 — Matriz de transições de follow-ups sintéticos

## Contrato

`FollowUpBucketTransitions::compare` recebe dois snapshots **sintéticos** produzidos por F4-09. Valida rigorosamente chaves, contagens e unicidade de identificadores em cada snapshot e calcula:

- Matriz 3 × 3 de movimentos entre `overdue`, `due_24h` e `due_later` para IDs presentes nas duas observações.
- Listas de IDs que surgiram (`entered`) ou desapareceram (`exited`), preservando a ordem de varredura por faixa.
- Contagens de IDs no mesmo grupo (`unchanged`) e de IDs que mudaram de grupo (`moved`).

**Não inferir motivo:** `exited` não significa atendimento, exclusão real, perda, conversão ou fechamento. A matriz não substitui auditoria nem provas de eventos; datas e filtros podem ter mudado.

## Segurança

A função não faz consultas, não acessa relógio, storage ou rede, não determina titularidade, entitlement, permissão ou acesso. Dados de clientes, API, operações reais, migrações e `ALLOW` permanecem proibidos; `main`, VPS e produção intocadas. Integração somente à DEV após os dois workflows de CI bem-sucedidos na SHA de origem. A revisão independente SEG/DBA #27 e demais gates permanecem abertos.
