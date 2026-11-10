# F3-21 — Prévia de estrutura de tabelas CSV sintéticas (sem ler arquivo)

**Data:** 10/11/2026. **Estado:** somente laboratório DEV do CRM. Referências: [F3 #35](https://github.com/rafhael100/imagine-crm/issues/35), [CRM-REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), F3-20 e Central IN-118.

## Entrega

A classe pura `App\Support\Synthetic\CsvStructurePreview::summarize` recebe uma matriz **inventada**, já separada em strings. Internamente chama `CsvCellRiskPreview::inspect`, herdando limites do laboratório: no máximo 100 linhas, 25 células/linha e 4096 bytes/célula. Não é uma quota de leads ou um limite do plano Básico.

Produz um relatório apenas com metadados:

- `row_count`, `data_row_count`, `header_columns`, `header_missing`;
- `ragged_data_rows`: números 1-based das linhas de dados cujo número de células difere do cabeçalho;
- `empty_header_columns`: posições 1-based com cabeçalho **exatamente igual à string vazia**;
- `duplicate_header_groups`: grupos de posições de cabeçalhos **idênticos byte a byte**, sem unificação automática;
- `flagged_cell_count`, `cell_findings`: apenas coordenadas e códigos do analisador F3-20, jamais valores.

Exemplo:

```php
CsvStructurePreview::summarize([
    ['id', 'email', 'id'],
    ['fiction-1', 'fake@example.invalid', '=1+1'],
    ['fiction-2', 'fake@example.invalid'],
]);
// duplicate_header_groups: [[1, 3]]
// ragged_data_rows: [3]
// flagged_cell_count: 1
// cell_findings: [['row' => 2, 'column' => 3, 'code' => 'formula_prefix']]
```

## Limitações do contrato

A análise **não faz parsing** de CSV (nem escolhe delimitador, encoding, regras de citação, cabeçalho, escape ou normalização). Uma string `Name` e outra `name` não são consideradas iguais; um cabeçalho contendo apenas espaços não é reescrito nem tratado como string vazia. São escolhas **deliberadamente não normativas** para evitar substituir decisões pendentes de importação.

O relatório não equivale a validação do esquema CRM, deduplicação de pessoas, confirmação de importação, detecção universal de formula injection, autorização de exportar campos ou consentimento LGPD. Não fornece nomes de colunas ou valores; a futura experiência de mapeamento de colunas exige avaliação de minimização, escopo por empresa, privacidade e política de encoding.

O teste `CsvStructurePreviewTest` cobre resultados vazios, contagens, cabeçalhos repetidos, linhas com comprimento divergente, dados com formato inválido e propagação dos alertas F3-20. O tripwire dos entrypoints Laravel impede referências diretas à classe em rotas/providers/jobs/bootstrap; não prova ausência de chamadas indiretas.

**Sem importação real, leitura de arquivos, exportação, upload, storage, credencial, API, SQL, grants, `ALLOW`, VPS, custos, deploy ou alteração de `main`.** Todos os gates CORE, S01, LGPD e parecer independente [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) permanecem abertos. O [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) continua DRAFT. Merge apenas DEV após ambos workflows verdes na SHA exata.
