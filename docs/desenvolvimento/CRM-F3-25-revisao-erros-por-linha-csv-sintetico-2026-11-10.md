# F3-25 — Revisão dos diagnósticos por linha de CSV fictício

**Data:** 10/11/2026. **Estado:** experimento PHP puro para DEV; não é importação ou confirmação operacional. **Referências:** [F3 #35](https://github.com/rafhael100/imagine-crm/issues/35), [CRM-REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), F3-20/21/23 e [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27).

## Entrega

`App\Support\Synthetic\CsvRowReviewPreview::inspect($rows, $mapping = [])` examina apenas matriz de strings **fictícias já segmentadas**. Reutiliza o validador F3-23 para propostas de mapeamento `fixture_*` e o analisador F3-20 de riscos por célula. Adota os limites existentes: 100 linhas no total (inclusive cabeçalho), 25 células por linha, 4096 bytes por célula e 25 associações. Não se conecta a armazenamento, banco, arquivos ou usuários.

O resultado inclui:

| Campo | Interpretação |
|---|---|
| `data_rows`, `expected_columns` | Linhas de dados e largura declarada no cabeçalho fictício |
| `rows_with_findings` | Quantidade de linhas de dados com **problemas detectados**, não quantidade rejeitada |
| `rows_without_detected_findings` | Linhas sem problema detectado neste teste **limitado**, nunca aprovadas |
| `header_cell_warning_count` | Contagem separada de alertas de células na linha de cabeçalho; não mistura esses alertas com contatos |
| `global_issue_codes`, `unmapped_columns` | Problemas globais de estrutura/mapeamento F3-23 e índices de colunas sem atribuição |
| `row_reviews` | Lista de metadados por linha de dados, com posição original `row` 1-based, `actual_columns`, `issue_codes`, `cell_findings` apenas com `column` e `code` |
| `review_required`, `import_permitted` | **Sempre `true` e `false`, respectivamente**, independentemente dos resultados |

`row_reviews[*].issue_codes` contém os códigos fechados `column_count_mismatch` (número de colunas diferente do cabeçalho) e/ou `risky_cells` (células sinalizadas). Cada linha recebe no máximo um contador em `rows_with_findings`, mesmo que possua múltiplos problemas. Alertas de cabeçalho e colisões de mapeamento permanecem globais. Linhas sem alertas não são declaradas prontas para importar.

## Exemplo sem efeito de negócio

```php
$report = CsvRowReviewPreview::inspect(
    [
        ['id', 'notes'],
        ['fiction-001', 'safe'],
        ['fiction-002', '=1+1'],
        ['fiction-003'],
    ],
    [
        ['column' => 1, 'target' => 'fixture_identifier'],
        ['column' => 2, 'target' => 'fixture_notes'],
    ],
);
// data_rows = 3; rows_with_findings = 2; review_required = true
// import_permitted = false; no return value includes a cell or field name
```

## Evidência e limites

`tests/Unit/CsvRowReviewPreviewTest.php` cobre separação cabeçalho/linhas, linhas corretas mas não autorizadas, múltiplos alertas na mesma linha, ordem original, última linha no limite, saída sem dados, colisões globais de mapeamento, estrutura malformada e rejeição de entradas inválidas. O tripwire `FollowUpRuntimeSeparationTripwireTest` agora proíbe referência direta desta classe a `routes/`, `app/Http`, `app/Providers`, `app/Jobs`, `app/Console` e `bootstrap/`. É um contrato estático de fonte, não uma prova completa de isolamento em execução.

**O módulo não lê, interpreta, gera, valida definitivamente ou importa arquivos CSV/XLSX.** Não seleciona encoding, converte texto, limpa planilha, autoriza campos reais, infere identidade, decide descartar linhas ou resolve duplicidades. A visualização futura só deve apresentar resultados inventados até a revisão formal dos contratos de privacidade, direitos de leitura/escrita por usuário/empresa/campo, deduplicação, confirmação humana, backup/restore, auditoria e política CSV da Central IN-118.

**NO-GO:** nenhuma confirmação de importação, `ALLOW`, SQL, rota de cliente, arquivo real, API, job, migração, `main`, VPS, serviço externo pago ou produção. Os gates CORE/IAM, S01, LGPD e parecer independente [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) permanecem abertos; [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) continua DRAFT sem parecer. Integração permitida somente DEV após revisão do diff e F0/PostgreSQL RLS verdes **na SHA exata**.
