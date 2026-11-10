# F4-08 — Variação sintética de acompanhamentos

Compara exclusivamente dois snapshots sintéticos previamente agregados pelo F4-07 e retorna a diferença assinada por faixa de vencimento e total. As somas, chaves e contagens são validadas antes de calcular a diferença.

## Não inferir significado comercial

Uma diminuição do grupo vencido **não** demonstra atendimento efetivo, cumprimento de SLA, resolução, conversão ou melhoria de produtividade. Itens podem trocar de faixa, ser excluídos, migrar ou sair do filtro; a composição dos snapshots não é validada aqui. Os arrays não incluem dados de clientes, tenant ou usuário.

## Gates

PHP puro, sem persistência, banco, APIs, rotas, jobs, integração real, permissão ou `ALLOW`. Os testes são sintéticos e não homologam SEG/DBA, CORE, LGPD ou produção. Integrar somente à DEV após CI F0/PostgreSQL com sucesso na mesma SHA; `main` e VPS não devem sofrer alterações.
