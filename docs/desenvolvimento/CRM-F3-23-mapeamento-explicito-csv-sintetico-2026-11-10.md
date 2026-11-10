# F3-23 — Plano explícito de mapeamento de colunas CSV fictícias

**Data:** 10/11/2026. **Âmbito:** laboratório PHP inerte, somente DEV. Referências: [F3 #35](https://github.com/rafhael100/imagine-crm/issues/35), [CRM-REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), F3-20/F3-21 e [Central IN-118](../planejamento/IN-118-central-backup-portabilidade-dados.md).

## Incremento

A classe `App\Support\Synthetic\CsvMappingPlanPreview::inspect` recebe **uma matriz inventada de strings já divididas** e **uma proposta explícita de atribuições coluna → destino fictício**. Não lê CSV/XLSX, arquivo, URL, banco, sessão ou identidade.

Exemplo puramente sintético:

```php
CsvMappingPlanPreview::inspect(
    [
        ['example_a', 'example_b'],
        ['invented-01', 'invented-value'],
    ],
    [
        ['column' => 1, 'target' => 'fixture_alpha'],
        ['column' => 2, 'target' => 'fixture_beta'],
    ],
);
```

Os únicos nomes permitidos começam com `fixture_`, seguidos de letra minúscula e até 31 caracteres minúsculos/números/`_`. Nomes de campos operacionais como `lead.email` são rejeitados. As colunas são **índices 1-based**, fornecidos expressamente pelo chamador. Não existe correspondência automática por nome do cabeçalho nem coercion de tipos. A entrada é validada contra os limites estritos do F3-20/F3-21: até 100 linhas, 25 células/linha, 4096 bytes/célula, e 25 atribuições.

O resultado é somente um relatório de metadados:

| Chave | Conteúdo |
|---|---|
| `header_columns`, `data_rows`, `mapping_count` | Quantidades; não contêm registros |
| `unmapped_columns` | Posicionais 1-based não selecionadas; **informativo**, não descarte permitido |
| `duplicate_source_columns` | Índices de origem usados em mais de uma atribuição |
| `duplicate_target_mapping_positions` | Grupos de **posições das atribuições**, nunca nomes de destinos |
| `issue_codes` | Códigos fechados: `missing_header`, `empty_header`, `duplicate_header`, `ragged_data_rows`, `risky_cells`, `duplicate_source_mapping`, `duplicate_target_mapping` |
| `cell_warning_count` | Total de células sinalizadas no laboratório F3-20 |
| `review_required` | Sempre `true`, inclusive quando `issue_codes` é vazio |

Nenhum cabeçalho bruto, conteúdo de células, destino declarado, token, dado pessoal, identificador comercial ou registro transformado é devolvido. A proposta não confirma que colunas não mapeadas podem ser ignoradas ou descartadas, nem aprova uma substituição, mesclagem ou criação de lead.

## Testes e fronteiras

`tests/Unit/CsvMappingPlanPreviewTest.php` exercita planos limpos (mas revisão obrigatória), não mapeados, duplicações de origem e destino, combinações de conflito, problemas estruturais/células suspeitas, privacidade da saída, estruturas inesperadas, limites e rejeição de alvos não fictícios.

`FollowUpRuntimeSeparationTripwireTest` também verifica que o novo componente não seja conectado diretamente a routes/HTTP/providers/jobs/console/bootstrap. Essa verificação é de fonte estática, não garante ausência de chamadas indiretas.

## Decisões abertas e NO-GO

A futura importação exige política de dialeto CSV, encoding, normalização, transformação de tipos, minimização e direitos por campo/organização, prévia de conflito de registros, revisão anti-fórmulas, confirmação humana, trilha e operação idempotente com persistência durável. As decisões devem ser formalizadas por owners competentes; `issue_codes=[]` **não significa seguro, apto ou aprovado**.

O módulo não implementa upload, leitura de arquivo, armazenamento, exportação, download, banco, API, autenticação, `ALLOW`, alteração em lead, migração, VPS, despesas, `main` ou produção. Os gates CORE/S01/LGPD e parecer independente [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) continuam abertos; [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) permanece DRAFT, sem parecer. Integração exclusivamente DEV após F0 e PostgreSQL RLS F1 verdes na HEAD exata e diff conferido.
