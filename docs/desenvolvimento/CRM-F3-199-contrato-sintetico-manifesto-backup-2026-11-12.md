# CRM F3-199 — contrato sintético versionado de manifesto de backup

**Data:** 12/11/2026. **Referência:** [IN-118](../planejamento/IN-118-central-backup-portabilidade-dados.md), F3-198, issue #35. **Estágio:** Core PHP puro com validação de metadados fictícios, sem execução de backup ou restauração.

## Entrega
`SyntheticBackupManifestContractInspector` valida estrutura fechada com nove chaves: `format_version`, `target_reader_version` (1..3 como marcadores **sintáticos**, não versões operacionais aprovadas), `package_kind` (`table` ou `complete`), `parts`, `declared_digest_algorithm`, `digest_present_claimed`, `encryption_claimed`, `secret_fields_claimed` e `claimed_archive_bytes`.

Os componentes declarados podem usar apenas sete nomes enumerados (`leads`, `contacts`, `opportunities`, `activities`, `histories`, `attachments`, `settings`). Cada um tem apenas contagem inteira fictícia até 10 mil e tamanho declarado até um bilhão de bytes. Não aceita nomes de arquivo, registros, conteúdo, IDs, PII, chaves, assinaturas, paths, hashes reais ou segredos. Chaves extras, listas esparsas, duplicatas e tipos incorretos causam erro genérico.

## Regras de revisão
- `table` **não** equivale a `complete` para backup. Mesmo `complete` não comprova relacionamentos, histórico, anexos, consistência temporal nem manifesto completo.
- Versões >1 do formato ou leitor ficam marcadas para avaliação; versão de leitor menor do que a versão de origem recebe alerta específico, sem conversão automática.
- `sha256` é apenas um nome de algoritmo declarado. `digest_present_claimed=true` **não verifica integridade**. O código jamais recebe ou calcula digest real.
- Declaração de criptografia não comprova confidencialidade. Arquivo sem criptografia declarada recebe alerta, e segredo declarado requer exclusão antes de qualquer eventual migração entre contas.
- Arquivo de tamanho declarado zero contendo seções com bytes declarados positivos recebe alerta de contradição, sem inferir taxas de compressão ou calcular espaço real.
- Contagens e totais de bytes na resposta são sempre marcados como **declarados**, jamais verificados.

**Nenhuma capacidade operacional:** `authorized=false`, todos `can_* = false`, todos `*_verified = false`, `informational_only=true`. Nenhum controlador, rota, migration, storage, API, arquivo, chave de criptografia ou acesso real.

## Testes e saída do escopo
Testes cobrem compatibilidade de versão, hash somente alegado, criptografia, sete seções, limites numéricos, formatos incompletos, listas malformadas, campos com PII e zero autoridade. A matriz de laboratório `SyntheticCommercialCapabilityDenyMatrixTest` foi ampliada de **42 para 43** contratos que nunca podem liberar efeitos operacionais. Exigir F0/Laravel, PostgreSQL/RLS, Sites e AIA na HEAD exata para merge na DEV.

**Não é a especificação definitiva do IN-118.** Schema real, formato versionado definitivo, algoritmo de envelope criptográfico, retenção, manifesto assinado, integridade de arquivos, consentimentos, restore, testes multiempresa e LGPD aguardam decisões e revisões independentes das issues #27/#259. `main` e produção continuam bloqueadas.
