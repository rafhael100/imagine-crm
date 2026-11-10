# F3-30 — diferenças por posição entre versões fictícias de lote CSV

**Data:** 10/11/2026. **Ambiente:** laboratório PHP puro na DEV; sem banco, rede, login ou dados reais. **Dependências:** F3-29 (checksum de versão de fixture), F3-27 (revisão simulada), [F3 #35](https://github.com/rafhael100/imagine-crm/issues/35).

## Problema

O F3-29 recusa a reconciliação de decisões fictícias com uma versão diferente do lote. Para um futuro fluxo de UX ser explicável, o laboratório precisa também indicar **quais posições** mudaram entre duas matrizes, sem expor células, nomes de campos, identificação de pessoas ou hashes públicos na saída.

## Contrato restrito

`CsvSnapshotRevisionDeltaPreview::compare($beforeRows, $beforeMapping, $afterRows, $afterMapping)` recebe **duas matrizes de strings inventadas já separadas** e seus mapeamentos explícitos `fixture_*` e valida cada versão com `CsvReviewSnapshotPreview::fingerprint`. Isso herda os limites existentes (até 100 linhas, 25 células/linha, 4096 bytes/célula e 25 atribuições).

O relatório inclui:

| Campo | Significado |
|---|---|
| `same_snapshot`, `review_invalidated` | Comparação dos fingerprints estritamente na simulação; a diferença invalida as decisões fictícias anteriores |
| `before_row_count`, `after_row_count` | Contagens totais incluindo o cabeçalho |
| `changed_row_positions`, `added_row_positions`, `removed_row_positions` | Números **1-based** de posições alteradas/adicionadas/removidas; posição 1 é cabeçalho |
| `before_mapping_count`, `after_mapping_count` | Contagens de associações na lista |
| `changed_mapping_positions`, `added_mapping_positions`, `removed_mapping_positions` | Posições **1-based das associações** alteradas/adicionadas/removidas, sem nomes de destinos |
| `human_verified`, `review_required`, `import_permitted` | **Sempre `false`, `true`, `false`**, respectivamente |

**Pontos de atenção:** posições não são IDs estáveis de lead. Inserir/remover uma linha no meio pode aparecer como alterações de várias posições e um acréscimo/remoção na cauda, não como uma correspondência semântica por identidade. O relatório não decide se linhas podem ser ignoradas, mescladas, tratadas como duplicatas ou importadas. A igualdade de checksum também não confirma que houve revisão humana.

### Exemplo totalmente inventado

```php
$before = [['id', 'note'], ['ficticio-01', 'safe']];
$after = [['id', 'note'], ['ficticio-01', 'edited']];
$mapping = [['column' => 1, 'target' => 'fixture_id']];
$report = CsvSnapshotRevisionDeltaPreview::compare($before, $mapping, $after, $mapping);
// review_invalidated=true; changed_row_positions=[2]
// human_verified=false; review_required=true; import_permitted=false
```

`tests/Unit/CsvSnapshotRevisionDeltaPreviewTest.php` cobre igualdade, mudanças em células e cabeçalhos, adições e remoções, reordenação, bytes binários, alterações do mapeamento, limites de laboratório, entrada inválida, ordenação de chaves e ausência de divulgação. O tripwire de entrypoints Laravel também bloqueia referência direta à nova classe. **Não é auditoria externa nem controle de autorização em runtime.**

## Segurança e gates

O F3-29 utiliza SHA-256 público, sem segredo, assinatura, snapshot persistido ou contador monotônico. Um chamador pode fabricar ambas as versões e seus checksums. O F3-30 **não autentica origem ou identidade, não detecta replay de versões antigas mantidas por terceiro, não prova revisão real e não confere integridade de armazenamento**. Não registrar hashes de dados pessoais reais sem revisão de privacidade: mesmo um digest pode ser correlacionável. Os dois componentes são exclusivos de fixtures sintéticas.

Não criar API, importador, parser CSV, upload, exportação, SQL, `ALLOW`, permissões, escrita, migração, conexões reais, VPS, custos, `main` ou deploy. CORE/IAM, S01, LGPD, Central IN-118 e revisão independente [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) seguem pendentes; [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) permanece DRAFT. Merge somente DEV com F0 CI e PostgreSQL/RLS verdes na HEAD exata e revisão do diff.
