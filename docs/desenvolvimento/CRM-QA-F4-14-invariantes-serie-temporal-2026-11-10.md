# QA F4-14 — Invariantes da série temporal sintética

Cobertura adicional para F4-13:
- Aceite exato do limite de 31 observações cronológicas.
- Rejeição de instantes decrescentes, listas associativas, horário sem offset e campos imprevistos.
- Rejeição de identificador repetido entre faixas de um único snapshot.
- Preservação do contrato sem comparação para o primeiro ponto da série.

**Apenas QA inerte/sintética.** Não valida acesso autorizado a dados de clientes, RLS produtiva, LGPD, indicadores comerciais, endpoint ou lançamento. Permanecem os gates CORE e o parecer independente SEG/DBA #27; `main`, VPS e produção não são alteradas.
