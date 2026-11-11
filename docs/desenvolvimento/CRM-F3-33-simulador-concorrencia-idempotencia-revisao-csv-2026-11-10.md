# F3-33 — Simulador em memória de concorrência e idempotência para revisão CSV fictícia

**Data:** 10/11/2026. **Classificação:** laboratório PHP puro para DEV; sem efeitos externos. **Contrato:** derivado dos riscos ilustrados por F3-29/F3-30 e da [proposta F3-32](CRM-F3-32-contrato-proposto-revisao-autenticada-versao-confiavel-lote-2026-11-10.md), que continua **não aprovada**. Relacionado a [F3 #35](https://github.com/rafhael100/imagine-crm/issues/35) e [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27).

## Resultado implementado exclusivamente em memória

`App\Support\Synthetic\CsvReviewRaceFixture` é um modelo isolado, com estado em um objeto PHP **que pode ser inteiramente forjado pelo chamador**. Recebe somente listas fictícias de células já segmentadas e destinos `fixture_*` previamente validados. Ele **não** identifica o revisor, não determina tenant, não grava auditoria/SQL/arquivo, não bloqueia processos concorrentes e não executa importação.

- A instância começa em `fixture_version=1`; `marker()` retorna o checksum público F3-29 somente para testes. **Não** é um segredo ou token de acesso.
- `submit(expectedVersion, expectedFingerprint, idempotencyKey, decisions)` aceita apenas chaves `fixture_key_...` estritamente limitadas e lista fechada de decisões inventadas por linha. Rejeita linhas duplicadas, status de aprovação, campos extras, versão zero e decisões vazias. Recalcula os alertas reais da fixture via `CsvReviewSnapshotPreview::reconcile`.
- Um registro fictício com versão atual e payload válido incrementa o contador local: `fixture_recorded_only`. Não significa que um humano aprovou algo nem persiste a operação.
- Dois comandos diferentes que observaram a mesma versão: **somente o primeiro** incrementa o contador local; o segundo retorna `fixture_stale_no_op`. Esta verificação ocorre **em memória, sequencialmente**, sem transação ou exclusão mútua.
- Repetir a **mesma** chave, versão, fingerprint e decisões logicamente iguais, mesmo em ordem diferente, devolve `fixture_idempotent_replay_no_op` sem um segundo evento. Reutilizar a chave com decisões ou versão diferentes devolve `fixture_idempotency_conflict_no_op`.
- `replaceFixture(expectedVersion, rows, mapping)` valida outra matriz fictícia; se os bytes ou destinos mudarem, incrementa a versão e **descarta todas as escolhas/associações locais anteriores**, inclusive o histórico de idempotência. Ao tentar submeter a revisão antiga, um fingerprint divergente devolve `fixture_stale_no_op`, mesmo que a chave antiga já tivesse sido vista.
- Substituir por **conteúdo idêntico** não atualiza a versão. Repor depois os bytes de uma versão antiga não decrementa a contagem monotônica deste objeto, de modo que o comando original continua obsoleto.
- Histórico de até 99 comandos únicos por instância; a tentativa seguinte retorna `fixture_history_limit_no_op`, sem crescimento não limitado na simulação.
- Todo resultado inclui **sempre** `human_verified=false`, `review_required=true` e `import_permitted=false`, inclusive quando houve `fixture_recorded_only`. A saída não devolve texto das células, destinos, chaves de idempotência, marcador ou identidade.

### Cenário fictício sem autorização

```php
$fixture = new CsvReviewRaceFixture(
    [['id', 'note'], ['fiction-1', 'safe']],
);
$marker = $fixture->marker();

$first = $fixture->submit(1, $marker, 'fixture_key_a', [
    ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
]);
// fixture_recorded_only; fixture_version=2; import_permitted=false

$retry = $fixture->submit(1, $marker, 'fixture_key_a', [
    ['row' => 2, 'status' => 'fixture_reviewed_no_findings'],
]);
// fixture_idempotent_replay_no_op; recorded_review_events=1

$competing = $fixture->submit(1, $marker, 'fixture_key_b', [
    ['row' => 2, 'status' => 'fixture_needs_correction'],
]);
// fixture_stale_no_op; no new review event
```

## Evidências e limitações críticas

`tests/Unit/CsvReviewRaceFixtureTest.php` exercita resultados deny-all, comando normal, replay idempotente, payload divergente sob chave igual, concorrência sequencial na mesma versão, troca de matriz/destino, restore de bytes antigos com versão nova, proibição de limpar alertas, mensagens sem dados e limites. `FollowUpRuntimeSeparationTripwireTest` é ampliado para rejeitar referência direta da nova classe nos entrypoints Laravel. O pipeline F0/PostgreSQL RLS executa **testes da simulação**, não um teste de concorrência transacional em banco.

**O objeto não é uma implementação de compare-and-swap real.** Dois processos diferentes podem instanciar `CsvReviewRaceFixture` e cada um acreditar ter registrado o mesmo evento; não existe mutex, row lock, constraint única, isolamento transacional, commit durável, ledger, recuperação após falha, assinatura ou contexto de tenant. Uma simples reconstrução do objeto apaga o histórico; a versão monotônica só existe durante a vida daquela instância. Nem os testes verdes nem a inexistência de conflito na simulação provam segurança da futura API ou integridade em produção.

Chaves `fixture_key_...` não são credenciais: qualquer pessoa pode construí-las; não há escopo por principal/empresa nem garantia criptográfica de origem. O checksum F3-29 também é público e **não autentica dados ou pessoas**. Proibições de conteúdo real são essenciais para evitar exposição indireta por hashes.

**Gates NO-GO:** proposta [F3-32](CRM-F3-32-contrato-proposto-revisao-autenticada-versao-confiavel-lote-2026-11-10.md) ainda depende de F3-32-D01..D10 e teste futuro de concorrência transacional, autenticação, membership, revogação/MFA, política por campo/empresa, revisor independente SEG/DBA, LGPD e Central IN-118. [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) segue DRAFT sem parecer. Não usar dados reais, arquivo, API, route, SQL/migração, IAM, `ALLOW`, VPS, custo, `main`, importação real ou deploy nesta etapa. Merge somente DEV com F0/PostgreSQL RLS verdes na HEAD exata e revisão do diff.
