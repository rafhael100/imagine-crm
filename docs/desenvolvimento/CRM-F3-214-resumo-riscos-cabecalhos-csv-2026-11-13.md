# CRM F3-214 — Resumo de riscos de cabeçalhos CSV

Incremento de código PHP puro, apenas para análise local de CSV fictício. Complementa F3-213 e IN-118, sem importação operacional.

## Entrega

`PortableCsvHeaderRiskSummary::inspect` reutiliza o parser limitado F3-210, conta cabeçalhos vazios, duplicados **exatamente por bytes** e células recusadas pelo encoder CSV conservador. Distingue arquivos declarados sem cabeçalho de arquivos com cabeçalho alegado. A saída fornece somente contadores e códigos fixos, sem títulos ou linhas.

## Limitações deliberadas

Não identifica equivalências Unicode/casefold, não valida nomes de campos, schema, legalidade de consentimento, autorização, usuário, tenant nem destino. Uma contagem zero não significa seguro para Excel. Não lê arquivos, cria rotas, concede acesso, executa SQL ou persiste dados. `can_import`, `can_merge` e `can_persist` permanecem `false`.

## QA

Teste PHPUnit `tests/Unit/PortableCsvHeaderRiskSummaryTest.php` inclui caso válido, cabeçalho duplicado/vazio/fórmula, ausência de cabeçalho, somente cabeçalho, erros sem vazamento. Exigir CI F0, PostgreSQL/RLS e paridades integradas na SHA exata antes de considerar merge DEV. Gates SEG/DBA #27, CORE/LGPD #259 e interface visual #416 continuam condicionados.
