# F4-07 — Resumo sintético de volume de follow-ups

## Entrega
- `FollowUpWorkloadSummary` recebe os três grupos já classificados em F4-06 e retorna quantidades inteiras e total; rejeita duplicidade entre grupos, IDs inválidos e schemas inesperados.
- F4-06 passa a comparar instantes completos, inclusive frações de segundo: instantes antes do relógio são vencidos; exatamente agora e até 24 horas inclusive são `due_24h`; posteriores são `due_later`.
- Testes unitários sintéticos cobrem contagens, vazio, entradas incompatíveis, duplicação e limites subsegundo.

## Limites
Uso estritamente local e sintético, sem buscar lead, consulta a tenant, SLA de negócio, API, banco de dados, fila, cobrança, notificação ou autorização. Não produz `ALLOW`. Nenhuma mudança em `main`, VPS ou produção. Não homologar SEG/DBA ou qualquer CORE por causa dos testes.
