# CRM Imagine — FUNC-P0-01/S01-R10-A11 — Briefing de tarefa técnica Codex: blindagem de migrations experimentais (PROPOSTA v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo).
**Estado:** TAREFA PREPARADA DOCUMENTALMENTE; **NÃO AUTORIZADA PARA EXECUÇÃO PELO CODEX**.
**Origem da autorização:** a direção respondeu 'aprovado' à recomendação de **preparar uma tarefa técnica delimitada para o Codex sobre a proteção das migrations, com critérios de segurança e testes sintéticos previamente definidos**. A mesma proposta explicitou que a implementação depende de autorização específica. Portanto **este ato não constitui GO de código, CI, SQL, testes nem criação de PR de implementação**.

## 1. Problema concreto, escopo e base congelada

Conforme [auditoria estática R10-A10](CRM-FUNC-P0-01-S01-R10-A10-matriz-lacunas-estaticas-por-arquivo-C03-2026-11-06.md), lacuna **LAC-C03-01 / P0**, a migration candidata de tenant/RLS está em `database/migrations/` e seu método `up` somente exige PostgreSQL. O comentário `isolated development` **não bloqueia** execução em outro ambiente. Também é importante proteger `down` contra rollback não autorizado. Não há evidência de execução indevida nem autorização para mexer no ambiente real.

- Repositório: `rafhael100/imagine-crm` (privado).
- Base de código inspecionada: branch `dev/fast-track-crm-2026-11-04`, SHA `8c9683acfd8decf98544402acda360d7e0f0a489`. Conferir HEAD novamente antes de eventual tarefa Codex e reconciliar commits concorrentes; SHA é referência factual, não instrução de voltar branch.
- Documento de planejamento: branch isolada `docs/func-p0-01-s01-r01-r02-2026-11-06`, não alterar `main`.
- Recorte único pretendido: blindar descoberta e aplicação de **migrations experimentais de tenant/IAM** em comandos padrão e operações indevidas, preservando estudo em ambiente sintético.

## 2. Fontes verificadas por arquivo

| Evidência | Descrição | Arquivo — congelado por SHA DEV | Blob SHA | Fato observado |
|---|---|---|---|---|
| E01 | Migration de tenant em caminho padrão | [`database/migrations/2026_11_04_000001_create_crm_identity_organizations_memberships.php`](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/database/migrations/2026_11_04_000001_create_crm_identity_organizations_memberships.php) | `c99ff53c34c3fb5ba717c76665f135cb14ec04a9` | Exige pgsql, mas não bloqueia ambientes não laboratoriais; `up` cria três entidades e policy de `crm_memberships`; `down` pode apagá-las |
| E02 | Migrações IAM opt-in | [`database/experimental-migrations/auth/2026_11_04_000002_create_candidate_crm_auth_registry.php`](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/database/experimental-migrations/auth/2026_11_04_000002_create_candidate_crm_auth_registry.php) | `af381d34dbe8fed3eff8ecbfe099ccfd0b30a955` | Excluídas do caminho padrão; exigem identity.experimental e ambiente local/testing |
| E03 | Teste de release que espera schema base | [`tests/Feature/AuthMigrationIsolationTest.php`](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/tests/Feature/AuthMigrationIsolationTest.php) | `7adca426b22b374636ab80f0e491de41661e6ac2` | `test_standard_release_migrate_on_fresh_postgres_schema_does_not_install_auth` simula production em schema descartável e espera `crm_memberships`; deverá ser deliberadamente reconciliado com a nova política |
| E04 | Workflow PostgreSQL de laboratório | [`.github/workflows/ci-postgresql-rls.yml`](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/.github/workflows/ci-postgresql-rls.yml) | `e68a2babcc7be885731cfa893cd5fea07d420689` | Executa `php artisan migrate --force --no-interaction` antes de path IAM explícito; depende da migração de tenant padrão para preparar o banco descartável |
| E05 | CI F0/SQLite | [`.github/workflows/ci-f0.yml`](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/.github/workflows/ci-f0.yml) | `c125358f3b56d10525e714efe338a579855275d2` | Executa suíte de testes sintéticos sem migrations de negócio; deve continuar sem ativar SQL/PG externa |
| E06 | Teste de isolamento real de membership no laboratório | [`tests/Feature/PostgresMembershipRlsTest.php`](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/tests/Feature/PostgresMembershipRlsTest.php) | `2e807ff25285caba6b1f381daf9b31a1e5bc38dd` | Requer PG+roles restritas; cobre ausência de tenant, A/B, rollback, GUC mutável, BYPASSRLS, revogação |
| E07 | Serviços ainda deny-only | [`app/Core/Authorization/DenyByDefaultAuthorizer.php`](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/app/Core/Authorization/DenyByDefaultAuthorizer.php) | `daa13599de92bd3d5fe120909f724f0f41d40dac` | Não deve ser alterado por proteção de migrations |
| E08 | Linhas de segurança do projeto | [`AGENTS.md`](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/AGENTS.md) | `ec27415186786961ee920a51869c1fce74bfa2f0` | Codex exige branch filha, PR DRAFT à DEV, testes sintéticos e autorização específica; não atuar sem instrução explícita |

**Dependência importante:** [AuthMigrationIsolationTest](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/tests/Feature/AuthMigrationIsolationTest.php) contém teste que simula `production` em schema descartável e espera `crm_memberships` após migração padrão. Uma blindagem que mude o comportamento é **incompatível** com essa expectativa antiga e precisa de atualização consciente do contrato de teste, não de ajuste silencioso. O workflow PostgreSQL também depende atualmente de `migrate` padrão; migração segregada requer adaptar sua preparação.

## 3. Deliberação arquitetural ainda requerida — NÃO APROVADA

**Alternativa A (recomendada para futura ratificação e análise técnica):** **separar a migration candidata do caminho de descoberta padrão**, em diretório experimental específico; exigir opt-in explícito validado em `up` e `down`, com ambiente local/testing e driver compatível; ajustar laboratório/CI e testes existentes para preparação explícita do schema candidato. Com isso, um `migrate` de release convencional não carrega o schema ainda não aprovado. A execução futura continua sendo experimental, não release de produção.

**Alternativa B (menos robusta e não recomendada):** manter migration no caminho normal e confiar unicamente em validação de `APP_ENV` e flag na execução. Embora negar fora do laboratório seja defesa adicional, o comando de release ainda descobriria a migration experimental, podendo provocar falha no deploy ou incentivar desativação perigosa da guarda. Não introduzir no-op silencioso que marca como aplicada uma migration não instalada.

**Alternativa C (só análise alternativa):** criar pipeline de migrations separado por módulos/estágios mediante arquitetura e gates próprios, caso a solução A conflite com o modelo de release do CRM. Não antecipar essa ampliação nesta tarefa.

**Ponto de decisão:** o uso de environment name/feature flag é uma barreira lógica; ambiente pode ser configurado incorretamente. Exigir defesas em camadas: descoberta explícita, credenciais/bancos de teste isolados, fronteiras de CI/release e revisão SEG/DBA. A minuta não afirma inviolabilidade completa.

## 4. Critérios de aceite futuros AC01..AC12 — NENHUM EXECUTADO

| ID | Área | Critério verificável para eventual autorização técnica |
|---|---|---|
| AC01 | Descoberta padrão | Em `production` e em comando padrão de release, schema **experimental** de tenancy não aparece e não é aplicado; não depender só de comentário no PHP nem de boa vontade do operador |
| AC02 | Opt-in verificável | Em local/testing e base descartável, só o caminho explícito autorizado da migration candidata opera; falhar fechado se flag, ambiente, conexão ou tipo de driver não forem válidos |
| AC03 | Up/down coerentes | Tanto `up` quanto `down` exigem condições de laboratório; rollback acidental fora do escopo é negado antes de afetar tabelas; `migrate:fresh`/`refresh` e outros caminhos são avaliados |
| AC04 | Compatibilidade de dependências | CI de PostgreSQL passa por trilha explícita de schema de tenant antes de IAM experimental; executar apenas mediante GO técnico, com uso de dados sintéticos |
| AC05 | Revisão do teste conflitante | Reconciliar explicitamente `AuthMigrationIsolationTest` que hoje espera membership em simulação `production`, para provar recusa de migrar em profile não autorizado; não retirar o teste silenciosamente |
| AC06 | Falha observável | Se uma migration protegida for invocada sem autorização, retornar erro verificável, sem efeitos parciais, sem vazar segredos, sem marcar sucesso ou inserir registro falso em tabela de migrations |
| AC07 | RLS preservada no laboratório | Após opt-in correto, manter semântica das FKs e da política `ENABLE/FORCE ROW LEVEL SECURITY`, `USING/WITH CHECK` de `crm_memberships` e comportamento fail-closed |
| AC08 | Autorizações inalteradas | Não adicionar ALLOW, endpoint, login, dado de cliente, policy de negócio ou permission grant; `DenyByDefaultAuthorizer` continua negando efeitos |
| AC09 | Não regressão | Suítes de teste sintéticas F0 e PostgreSQL RLS, inclusive negativos de tenant A/B, GUC, pool, rollback, role sem bypass, reportadas com logs e SHA; não presumir green antes da execução |
| AC10 | Superfície mínima | Diff restrito aos arquivos diretamente necessários (migration, CI, testes, documentação de execução); novo comando apenas com justificativa técnica e aprovação separada de ampliar escopo |
| AC11 | Segredos e infraestrutura | Sem VPS, CI secrets reais, backups/dados pessoais, produção, alteração em GitHub Actions secrets ou criação de role real; fixtures e contas efêmeras de CI somente |
| AC12 | Registro de entrega | PR rascunho futuro apontará para DEV, com SHA, arquivos alterados, casos executados/não executados, impacto esperado, limitações e blockers CORE/SEG abertos; sem merge automático |

## 5. Cenários negativos futuros — preparar, NÃO EXECUTAR

| Cenário | Resultado exigido após implementação autorizada |
|---|---|
| S-MIG-01 — `migrate` padrão em profile `production` e schema descartável | Não descobre nem cria tabelas experimentais do CRM; não registra migration experimental como aplicada |
| S-MIG-02 — invocação explícita experimental em `production` | Erro seguro antes de qualquer DDL, inclusive por `up` e `down` |
| S-MIG-03 — invocação explícita em local/testing sem opt-in | Falha segura antes de criar/apagar objetos |
| S-MIG-04 — opt-in em local/testing com PostgreSQL descartável | Executa schema candidato, aplica RLS e respeita política existente; apenas synthetic fixtures |
| S-MIG-05 — opt-in com driver incompatível fora de adapter de teste | Recusa com erro sem efeito parcial |
| S-MIG-06 — `migrate:rollback`, `refresh` ou `fresh` indevidos | Proteção equivalente para operações destrutivas; não permitir `down` não autorizado |
| S-MIG-07 — pipeline PostgreSQL CI | Prepara explicitamente tenant antes de IAM e executa testes de RLS com role sem bypass; logs provam execução |
| S-MIG-08 — suíte SQLite/F0 | Continua isolada de PostgreSQL/migrations reais; sem falso positivo de cobertura RLS |
| S-MIG-09 — reuso de conexão com A/B após erro | Testes existentes de RLS, GUC mutável, rollback, contexto contaminado e revogação mantidos |
| S-MIG-10 — `ALLOW` e superfície HTTP | Authorizer continua deny-only; `routes/web.php` permanece sem rotas comerciais |

**Não criar testes redundantes** quando os existentes em `AuthMigrationIsolationTest`, `PostgresMembershipRlsTest`, `F0SurfaceTest` e `PostgresSecurityBaselineTest` cobrem o mesmo risco: ajustar o menor conjunto e demonstrar continuidade.

## 6. Fronteira de arquivos para um eventual Codex autorizado

**Arquivos a examinar e possivelmente modificar após GO separado:** migration principal `database/migrations/2026_11_04_000001_create_crm_identity_organizations_memberships.php`, desenho do destino `database/experimental-migrations/tenant/` (nome apenas candidato), `tests/Feature/AuthMigrationIsolationTest.php`, `.github/workflows/ci-postgresql-rls.yml`, testes de RLS afetados e documentação de laboratório realmente necessária.

**Arquivos/camadas proibidos salvo autorização adicional:** `app/Core/Authorization/*`, `app/Core/Tenancy/*` exceto necessidade mínima justificada posteriormente, contratos comerciais, módulos de negócio, `routes/*`, CI secrets, `main`, PR #2/#21, VPS, ambientes de clientes, produção ou `ALLOW`/GRANT. Não trocar a política de RLS para habilitar comportamento de negócio.

**Se a implementação demandar esquema global novo, permissões comerciais ou alterações de política CORE02, interromper e apresentar desvio para aprovação.** Não abrir dois desenvolvimentos paralelos concorrendo na mesma migration.

## 7. Fluxo de trabalho FUTURO (não autorizado aqui)

Quando existir um **GO expresso de implementação** e requisitos técnicos aplicáveis forem tratados: conferir HEAD/PR/checks; criar branch filha de `dev/fast-track-crm-2026-11-04` com identificação específica; implementar correção mínima; executar somente testes sintéticos autorizados; abrir PR **DRAFT** direcionado à DEV; registrar link, diff, SHAs, CI e riscos; não fazer merge sem novo aceite. Nenhuma dessas ações foi executada ou autorizada por esta minuta.

### Texto de passagem futura para o Codex — BLOQUEADO até GO expresso

> **NÃO EXECUTAR AGORA.** Após autorização específica da direção, ler o briefing A11, `AGENTS.md`, `STATUS-ATUAL.md`, o código da migration principal, os testes e o workflow CI na HEAD vigente da DEV. Propor mudança mínima para impedir descoberta/execução acidental das migrations experimentais em release e preservar laboratório PostgreSQL isolado. Primeiro apresentar diff planejado, riscos e incompatibilidade com `AuthMigrationIsolationTest`. Só implementar/testar quando explicitamente autorizado, preservando deny-by-default, todos os gates e sem VPS/main/merges.

## 8. Condições que permanecem abertas

- C03 não está homologado; esta especificação cobre somente LAC-C03-01, não saneia LAC-C03-02 (RLS de dados de negócio), LAC-C03-04 (pool operacional) ou C01/C02/C04..C08.
- Os 67 CORE, SEG01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates seguem ABERTOS. Os oito contratos R02-C01..C08 continuam PENDENTES.
- Funções internas G01..G06 acumuladas pelo titular não substituem revisão competente/independente quando exigida. CI sintético não homologa produção.
- **Este documento foi criado somente em Markdown na branch documental.** Nenhum código, migration, SQL, teste, CI workflow, segredo, produção, branch DEV, PR ou VPS foi alterado pela presente tarefa.