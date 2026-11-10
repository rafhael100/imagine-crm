# CRM Imagine — OPS-23: selagem de rotas e laboratório de integridade de cópia

**Levantamento:** 10/11/2026 · **Destino:** somente `dev/fast-track-crm-2026-11-04` · **Status:** testes e documentação de segurança, NÃO liberação funcional.

## 1. SEC-ROUTE-01 — superfície HTTP bloqueada por inventário

[PR #138](https://github.com/rafhael100/imagine-crm/pull/138), DEV `944552a6e51c4bb622c44e89902a8c07b445fdac`.

O teste `tests/Feature/PublicRouteSurfaceSealTest.php` falha se qualquer rota além de **`GET|HEAD /up`** estiver presente no inventário Laravel, inclusive uma rota GET com nome fora do regex de negócios anterior. Também verifica que doze nomes de leitura não publicados respondem 404. O teste complementa o `LeadIntakeReleaseGateTest`, que bloqueia operações mutadoras e nomes conhecidos, e `PortabilityReleaseBoundaryTest`, que bloqueia rotas de importação/exportação/backup.

**Evidências CI pós-merge DEV:** [F0 #42748710143](https://github.com/rafhael100/imagine-crm/actions/runs/42748710143) e [PostgreSQL/RLS #42748709903](https://github.com/rafhael100/imagine-crm/actions/runs/42748709903) — ambos `success` na SHA `944552a6`.

**Limite:** este é um tripwire de CI, não um firewall ou autenticador em produção. Futuro endpoint só pode ser permitido mediante mudança específica revisada após aceite CORE/R01/SEG/LGPD; jamais remover a trava sem substituir por uma política de rotas aprovada, testes por método, recurso, tenant e campo e evidências de revogação.

## 2. REC-LAB-04 — confronto de manifesto e bytes apenas fictícios

[PR #139](https://github.com/rafhael100/imagine-crm/pull/139), DEV `5e823bc66883f507e6f0357c2323e6097944d5b1`.

- `SyntheticBackupIntegrityInspector`: aceita **somente** uma amostra local fictícia de até 32 KiB, sem arquivo ou I/O. Checa formato `synthetic-blob-v1`, versão implícita no identificador, tamanho inteiro, horário UTC canônico, SHA-256 minúsculo e ausência de campos extras como `tenant_id` ou `source_proof`. Não registra nem devolve bytes/hash/manifesto.
- `SyntheticBackupIntegrityAssessment`: estados fechados `SELF_CLAIM_MATCH_UNTRUSTED` e `SELF_CLAIM_MISMATCH`; `canRestore()`, `canAcceptAsVerifiedBackup()` e `canDeleteOriginal()` são **sempre falsos**.
- Testes negativos: bytes alterados, tamanho/digest divergentes, formulário falso/extra, data inválida, binário no limite e manifestação maliciosa em que o agressor recalcula o hash junto com o conteúdo. Um hash autodeclarado correspondente **não autentica a origem** nem prova backup correto.

**Evidências CI pós-merge DEV:** [F0 #42749280802](https://github.com/rafhael100/imagine-crm/actions/runs/42749280802) e [PostgreSQL/RLS #42749281497](https://github.com/rafhael100/imagine-crm/actions/runs/42749281497) — ambos `success` na SHA `5e823bc`.

## 3. UX-REC-LAB-04 — cenários offline acessíveis

[PR #140](https://github.com/rafhael100/imagine-crm/pull/140), DEV `50a25b0c464b19e5421512083ef74b8ec08fd662`.

A tela já existente de **prévia de importação** exibe três estados fixos, selecionados por botões sem formulário ou entrada de dados: (1) conteúdo e SHA coincidentes mas de origem não comprovada, (2) alteração de bytes, (3) atacante recalcula bytes e SHA. DOM é gerado apenas com texto e `replaceChildren`. Não há upload/download, relógio, API, restore, persistência ou leitura de backup. PHPUnit confere a correspondência dos estados com a classe PHP; Chromium exerce cenários e controles a 320px. O layout mantém as nove telas do protótipo e a CSP sem conexão.

**CI pré-merge:** [F0 #42749708930](https://github.com/rafhael100/imagine-crm/actions/runs/42749708930) e [PostgreSQL/RLS #42749709245](https://github.com/rafhael100/imagine-crm/actions/runs/42749709245) — `success` na HEAD do PR. **CI pós-merge:** [F0 #42749836340](https://github.com/rafhael100/imagine-crm/actions/runs/42749836340) e [PG/RLS #42749836326](https://github.com/rafhael100/imagine-crm/actions/runs/42749836326) — consultar estado final no GitHub, não presumir aceite de release.

## 4. O que falta para backups e restauros reais

As verificações acima não executam `pg_basebackup`, WAL/PITR, criação de snapshot, download, criptografia de mídia, custódia de chaves, autenticação, upload, recuperação, consistência transacional de tabelas, recuperação de anexos, planejamento de RPO/RTO ou restauração de clientes.

**Requisitos para futura especificação e revisão independente:**

1. Escopo físico da cópia (PostgreSQL, objetos/anexos, segredos e dependências), consistência de snapshots, logs/WAL, periodicidade, retenção, exclusão, versionamento e hipóteses de RPO/RTO, todos ratificados por responsáveis.
2. Criptografia em trânsito/em repouso, gestão e rotação de chaves fora do artefato, proveniência independente do digest, prevenção de adulteração, acesso mínimo e auditoria sem conteúdo pessoal.
3. Separação de contas e ambientes, identidade forte, controle por função e tenant, sem `SUPERUSER`/`BYPASSRLS` para acesso operacional e sem aceitar `app.tenant_id` como identidade.
4. **Teste real de restauração somente em ambiente descartável autorizado**, com validação de consistência, revogação, isolamento entre organizações, recuperação após falha e verificação de que o resultado não expõe clientes.
5. Parecer SEG/DBA independente, aceite LGPD/CORE/AUT, critérios de erro, rastreabilidade, aprovações formais de implementação/deploy e procedimentos documentados antes de qualquer uso com dados reais.

**Gates preservados:** [R01-D01..D05 / issue #22](https://github.com/rafhael100/imagine-crm/issues/22) sem aprovação operacional; [SEG/DBA / issue #27](https://github.com/rafhael100/imagine-crm/issues/27) sem parecer técnico independente itemizado; CORE, AUT, LGPD, backup/restore físico e produção não homologados. Nada neste lote habilitou `ALLOW`, `main`, VPS, credenciais, clientes reais ou despesas.
