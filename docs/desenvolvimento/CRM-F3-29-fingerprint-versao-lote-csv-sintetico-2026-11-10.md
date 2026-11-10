# F3-29 — vínculo de revisão à versão de um lote CSV fictício

**Data:** 10/11/2026. **Categoria:** contrato de laboratório PHP puro em DEV; não é storage imutável, assinatura eletrônica, autenticador, auditoria independente ou importação.

## Risco reduzido no laboratório

Uma decisão simulada sobre a linha 2 de um lote fictício **não deve ser reaproveitada** se houver alteração posterior de célula, cabeçalho, posição de linha, ordem de atribuições ou destino do mapeamento. O componente `App\Support\Synthetic\CsvReviewSnapshotPreview` adiciona uma verificação estrita de versão **somente para essa simulação**.

- `fingerprint($rows, $mapping)` valida todos os limites existentes por meio de `CsvRowReviewPreview`; calcula `fixture-sha256-v1:<64 caracteres hex minúsculos>` usando SHA-256 e codificação binária de comprimento prefixado para o número de linhas, número de células por linha, bytes exatos de cada célula e posições/destinos `fixture_*` **na ordem informada**.
- O prefixo de domínio separado e a codificação binária evitam colisões triviais de concatenação (`ab`+`c` versus `a`+`bc`) e preservam bytes NUL, espaços e offsets da amostra, sem converter encoding ou apresentar campos/valores no relatório.
- `reconcile($rows, $mapping, $expectedFingerprint, $decisions)` exige formato exato, refaz o cálculo com os dados disponíveis e usa `hash_equals`. Se a amostra ou o mapeamento tiverem sido alterados, lança exceção genérica e **não produz relatório de revisão**. Só depois dessa verificação invoca `CsvRowDispositionPreview::summarize`, que continua bloqueando decisões incompatíveis.
- Com marcador idêntico, o retorno adiciona `snapshot_version='fixture-sha256-v1'` e `snapshot_matches=true` à simulação pré-existente, que **sempre** declara `human_verified=false`, `review_required=true` e `import_permitted=false`. Não retorna células, cabeçalhos, mapeamento, PII, ator nem o marcador em si.

## Exemplo fictício sem efeito de negócio

```php
$rows = [['id', 'note'], ['invented', 'safe']];
$mapping = [['column' => 1, 'target' => 'fixture_alpha']];
$marker = CsvReviewSnapshotPreview::fingerprint($rows, $mapping);

// Apenas simulação de decisão; nunca comprova identidade humana.
$preview = CsvReviewSnapshotPreview::reconcile($rows, $mapping, $marker, [
    ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
]);
// snapshot_matches=true; human_verified=false; import_permitted=false

$rows[1][1] = 'changed';
// A tentativa seguinte com $marker lança exceção de lote alterado.
```

## Limites de segurança

**Este SHA-256 é um checksum público não autenticado.** Como todo chamador consegue recalcular um novo checksum para dados alterados, ele **não prova origem, autoria, identidade, integridade de armazenamento, consentimento, revisão independente ou não repúdio**. É possível reutilizar marcador de conteúdo idêntico (incluindo replay de dados antigos); a proteção **não** fornece contador monotônico, assinatura, segredo, âncora no servidor, lote imutável persistido ou prevenção de rollback. Em produção seria necessário um identificador confiável com versionamento transacional armazenado no servidor autorizado, comparação atômica, trilha imutável, revisão por humano autenticado e competências por empresa, objeto, campo e ação.

**Não usar em dados reais ou como token de autorização.** Checksum de dado de baixa entropia pode permitir correlação/dicionário e se tornar identificador pessoal. A função recebe **exclusivamente matrizes inventadas**, limitadas aos tetos F3-20/F3-21, sem leitura de arquivo e sem transferência em rede. Exceções não ecoam dados. Uma verificação de SHA correta não equivale a consentimento LGPD ou segurança de CSV.

`tests/Unit/CsvReviewSnapshotPreviewTest.php` cobre versão determinística, limites, edição/inclusão/exclusão/ordem, cabeçalho e mapeamento alterados, rejeição de marcador forjado ou não canônico, bytes binários, prevenção de concatenação ambígua, não vazamento de valores e preservação de `import_permitted=false`. O tripwire de entrypoints Laravel foi ampliado para impedir referências diretas à classe.

**NO-GO:** sem CSV real, upload, importação/exportação, SQL, API, autenticação, `ALLOW`, migrations, VPS, novos custos, `main` ou deploy. Contratos CORE/IAM, S01, Central IN-118, LGPD e revisão independente [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) permanecem pendentes; [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) continua DRAFT. Integração permitida somente na DEV após F0 e PostgreSQL/RLS verdes na HEAD exata.
