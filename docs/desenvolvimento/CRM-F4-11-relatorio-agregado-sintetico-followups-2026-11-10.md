# F4-11 — Relatório agregado sintético de follow-ups

`FollowUpAggregateReport::build` compõe os contratos F4-08/F4-10 a partir de dois snapshots F4-09. Os resultados contêm somente números inteiros, sem os IDs dos registros de origem.

## Saída
- `previous` e `current`: contagem por faixa e total.
- `delta`: diferenças assinadas.
- `continuity`: quantos identificadores entraram, saíram, permaneceram ou mudaram de faixa.

A implementação valida os snapshots antes de construir a saída e tem testes para consistência, entradas indevidas e ausência de identificadores na serialização. **A ausência de IDs não significa anonimização suficiente nem autoriza exposição dos números reais.**

## Limitações
Somente fixtures sintéticas e processamento PHP puro; nada de consultas, tenant, API, filas, cron, avisos, exportação real, permissões positivas ou `ALLOW`. Nenhuma inferência sobre atendimento concluído, SLA, produtividade ou conversão. Sem alterações em `main`, VPS ou produção. Gates CORE e parecer independente SEG/DBA #27 permanecem pendentes; merge apenas DEV com CI verde na mesma SHA.
