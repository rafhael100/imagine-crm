# F3-27 — decisões simuladas de revisão CSV, sem confirmação operacional

**Data:** 10/11/2026. **Contexto:** continuação F3-25/F3-26 e [F3 #35](https://github.com/rafhael100/imagine-crm/issues/35), documentação experimental em DEV. Não é autorização de importação, parecer de segurança ou decisão normativa sobre política de dados.

## Objetivo

Exercitar o futuro fluxo de tratamento humano de alertas em ambiente **sintético** sem fazer parecer que o sistema já possui autenticação, comprovantes de revisão, aceitação de riscos ou capacidade de gravar leads.

`App\Support\Synthetic\CsvRowDispositionPreview::summarize($rows, $mapping, $decisions)` recebe somente strings fictícias em uma matriz já dividida, atribuições `fixture_*` e seleções **declaradas pelo chamador**, não verificadas. A classe reexecuta `CsvRowReviewPreview::inspect`, herdando limites de 100 linhas incluindo cabeçalho, até 25 colunas, 4096 bytes/célula e até 25 mapeamentos fictícios.

Cada seleção é uma estrutura exata `['row' => int, 'status' => string]` para linha de dados original (2..N+1), em lista até 99 posições; índices fora de faixa, duplicados, campos adicionais e nomes de status não previstos são rejeitados. Os valores reconhecidos são exclusivamente `fixture_reviewed_no_findings` e `fixture_needs_correction`. Ausência de seleção = `fixture_pending`; nenhuma seleção significa autenticação.

## Regras preventivas da simulação

- Linhas com `column_count_mismatch` ou `risky_cells` não podem ser classificadas como `fixture_reviewed_no_findings`. Devem permanecer pendentes ou ser marcadas ficticiamente como `fixture_needs_correction`.
- Linhas **sem alertas detectados** podem ser classificadas ficticiamente como revisadas, mas isso não aprova suas informações nem significa qualidade real.
- Erros globais (cabeçalho, mapeamento, riscos) continuam expostos em `global_issue_codes` independentemente das seleções por linha; a simulação nunca os limpa.
- O relatório publica apenas posições, status de vocabulário fechado, contagens agregadas e flag `has_detected_findings`. **Nunca** publica valores de células, nomes de cabeçalho ou nomes de destino fictício.
- `human_verified=false`, `review_required=true` e `import_permitted=false` **sempre**, mesmo com zero pendências e sem alertas. Esses três campos não são alteráveis pelo chamador.

### Exemplo inerte

```php
CsvRowDispositionPreview::summarize(
    [['id', 'note'], ['invented-a', 'safe'], ['invented-b', '=1+1']],
    [['column' => 1, 'target' => 'fixture_id']],
    [
        ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
        ['row' => 3, 'status' => 'fixture_needs_correction'],
    ],
);
// human_verified=false; review_required=true; import_permitted=false
```

## Evidência e limites

`tests/Unit/CsvRowDispositionPreviewTest.php` exercita estados ausentes e preenchidos, resultados globais, falha para seleção limpa em linha sinalizada, decisão de correção em linha sem alertas, duplicações, índices, campos e status forjados, limite de 99, ausência de vazamento de valores e nenhuma capacidade de importação. `FollowUpRuntimeSeparationTripwireTest` bloqueia referência direta à nova classe nos entrypoints Laravel. Esses testes são verificações sintéticas, **não** validam identidade humana, persistência de revisão ou direitos por empresa.

**Não criar, mesclar, descartar, importar, escrever, autorizar ou exportar contatos/leads.** Ainda faltam contrato da confirmação real por humano autenticado, trilha imutável, snapshot confiável para evitar revisão sobre lote alterado, tratamento seguro de correções, idempotência, fonte de dados, autorização por empresa/recurso/campo, consentimentos/privacidade, backup/restore e homologação de produção. A Central IN-118, CORE/S01/LGPD e [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) seguem NO-GO; [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) permanece DRAFT sem revisor independente. Nenhuma API, SQL, `ALLOW`, conta real, custo, VPS, `main` ou deploy é ativado neste recorte. Merge somente DEV após ambas suítes verdes na SHA exata.
