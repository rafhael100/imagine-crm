# CRM Imagine — FUNC-P0-01/S01-R10-A10 — Matriz factual de lacunas C03 por arquivo e requisito (V2 estática, v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo). **Classificação:** RELATÓRIO DOCUMENTAL DE AUDITORIA ESTÁTICA SOMENTE LEITURA. **Não é homologação de segurança, não autoriza execução e não fecha gates.**

## 1. Aprovação e escopo factual

**Autorização:** resposta expressa do usuário 'aprovado' à pergunta sobre auditoria estática **exclusivamente de leitura do repositório**, sem executar código, SQL, testes, merges ou acessar VPS. Após relatar seus resultados, a nova manifestação 'aprovado' autoriza consolidar a matriz documental ora registrada, **não autoriza mudar código/CI/SQL ou executar testes**.

**Referência de código examinada:** branch `dev/fast-track-crm-2026-11-04`, HEAD exata `8c9683acfd8decf98544402acda360d7e0f0a489` na inspeção; árvore recursiva consultada sem truncamento (444 entradas, incluindo diretórios). **`main`**: SHA `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`, árvore de 259 entradas só README e docs. Branch de documentação separada.

- [A09 — plano de 14 verificações](CRM-FUNC-P0-01-S01-R10-A09-plano-verificacao-c03-p01-p02-v0.1-2026-11-06.md), blob `142041d81af4269b729d398fd6cfd893ac88b9b0`.
- [A08 — inventário conceitual P01/P02](CRM-FUNC-P0-01-S01-R10-A08-inventario-roles-contexto-conexoes-C03-P01-P02-proposta-v0.1-2026-11-06.md), aprovado apenas documentalmente no [ato A08](CRM-DEC-FUNC-P0-01-S01-R10-A08-P01-P02-alternativas-A-2026-11-06.md).
- Este A10 promove **somente o estado de evidência estática por fonte**, substituindo os 'não verificados no código' da A09 quanto ao que efetivamente foi lido. **Não** promove resultados a homologação física do PostgreSQL, readiness de produção, nem revisão independente.

## 2. Classificações utilizadas

- **Presente no código:** implementação identificável no commit, sem declarar que esteja implantada ou correta sob todas as ameaças.
- **Parcial/experimental:** existe implementação/caso sintético, mas falta escopo, contrato físico ou integração real.
- **Não demonstrado na abrangência examinada:** nenhum artefato suficiente foi identificado no conjunto de código inspecionado; não alegar inexistência fora dele nem na VPS.
- **Pendente:** exige decisão formal, evidência operacional ou parecer posterior. P0/P1 aqui indica **priorização interna de segurança e dependências**, não aprovação de backlog, prazo ou gate.

## 3. Matriz A09 I01..I14: resultado estático, lacuna e owner

| Checkpoint | Prioridade proposta | Situação de evidência | Principal arquivo/fonte | O que foi observado | O que ainda falta | Competência owner |
|---|---|---|---|---|---|---|
| I01 | P0 | Parcial / versão DEV | `database/migrations/2026_11_04_000001_create_crm_identity_organizations_memberships.php` | Três entidades: identidades, organizações, memberships; escopo multitenant limitado a esse núcleo | Inventário físico das entidades de negócio e correspondência tenant-bound; não inferir ausência na VPS | CORE01 / donos dos módulos |
| I02 | P1 | Parcial / versão DEV | Migration principal e migrations experimentais de autenticação | Identidade global separada, dados IAM de laboratório e organização CRM identificados | Catalogar objetos globais/híbridos, exceptions e política mínima de consulta | CORE01/02/SEG |
| I03 | P0 | Não demonstrado no conjunto analisado | Migration principal, arquivos `app/Core/*`, CI e árvore DEV | RLS explícita sobre membership; não foi produzida lista completa de views/funções/definer rights | Inspeção completa de DDL/objetos reais e políticas de execução privilegiada, quando autorizada | DBA/SEG |
| I04 | P1 | Parcial / versão DEV | Migration principal | FKs organização→membership e identidade→membership, mais unicidade org/identidade | Revisão de relações com entidades de negócio e FKs tenant-aware, teste futuro CORE01-T09 | CORE01/DBA |
| I05 | P0 | Parcial / laboratório | `.github/workflows/ci-postgresql-rls.yml`, `app/Core/Tenancy/PostgresActiveMembershipStore.php` | CI cria roles restritas NOBYPASSRLS; adaptador confere owner, superuser e bypass | Inspecionar role de runtime real, owners, GRANTs, herança, service accounts e separação de migration | DBA/SEG |
| I06 | P0 | Parcial / código candidato | Migration principal, testes `PostgresMembershipRlsTest.php` | RLS ENABLE+FORCE, política USING/WITH CHECK em `crm_memberships`; testes para contextos A/B e bypass | Homologar cobertura em todos os objetos tenant-bound e garantir papel de runtime sem bypass em ambiente aprovado | CORE01/SEG/DBA |
| I07 | P0 | Base deny-only presente | `app/Core/Authorization/DenyByDefaultAuthorizer.php`, `app/Providers/AppServiceProvider.php`, `app/Core/Tenancy/TenantAccessBoundary.php` | Authorizer sempre DENY; boundary consulta principal, membership e autorizar antes de efeito | Definir e homologar contrato positivo ação/recurso/campo/escopo; jamais trocar por ALLOW genérico | CORE02/SEG |
| I08 | P0 | Parcial / experimental | `app/Core/Identity/DatabaseSessionAuthority.php`, `app/Core/Identity/GuardPrincipalSource.php`, `PostgresActiveMembershipStore.php` | Fonte de sessão explícita, vínculo ativo, versão >0 e contexto transacional IAM/membership | Revalidar autoridade por estado, tenant e papel no fluxo de negócio; desambiguar relógios/revogação/tenant selection | CORE01/04/07/SEG |
| I09 | P0 | Parcial / laboratório | `PostgresActiveMembershipStore.php`, `config/database.php`, testes RLS | Rejeita contexto herdado, usa `set_config(..., true)` na transação e rollback; acesso com sessão GUC contaminada é recusado | Definir pooler real, uso de conexões em todos os módulos e protocolo de isolamento/reset/rollback sob SEG/DBA | CORE01/03/SEG/DBA |
| I10 | P1 | Laboratório sem efeito comercial | `app/Core/Async/AsyncAuthorityBridge.php`, migrations `database/experimental-migrations/async/` | Bridge só inspeciona/nega; migrations/ledger sintéticos têm isolamento por feature flag | Mapear classes de execução e mandato técnico real por job, retry, eventos e destinos sem personificar humano | CORE03/INT/SEG |
| I11 | P1 | Não comprovado para fluxos comerciais | `config/cache.php`, `config/filesystems.php`, `tests/Feature/*` | Estrutura básica de infraestrutura; não se constatou catálogo completo de cache/exports/arquivos por tenant | Inventário com path/SHA de chaves, expiração, ACL de objeto e autorização para download/export | CORE02/06/SEG/LGPD |
| I12 | P1 | Parcial / experimental | `AuthorizationDecision.php`, `AsyncDiagnostic.php`, `DatabaseSessionAuthority.php`, testes negativos | Negativas sem payload, sinal sanitizado e exceções tratadas; CI de laboratório | Revisar falhas de dependências reais, logging, alertas, retenções, não enumeração e resposta por canal | CORE06/SEG/QA |
| I13 | P1 | Suítes presentes; CI histórico success | `tests/Feature/PostgresMembershipRlsTest.php`, `.github/workflows/ci-postgresql-rls.yml` | Há testes de contexto ausente, GUC mutável, cross-tenant, rollback e role; duas execuções no head DEV com success | Confirmar logs/artefatos completos quando for homologar e executar casos S01 específicos apenas após autorização | QA/SEG/DBA |
| I14 | P0 | Pendente | `docs/planejamento/` (decisões), `STATUS-ATUAL.md` DEV | Designações G01..G06 documentais; nenhum parecer independente foi comprovado nesta auditoria | Parecer competente/independente quando exigido, risk acceptance, evidência real e decisão específica de gate | Direção/SEG/DBA/LGPD/QA |

## 4. Manifesto de evidências por arquivo (branch DEV/SHA fixos)

| Evidência | Arquivo exato no GitHub | Blob SHA | Constatação e limite |
|---|---|---|---|
| **F01** | [database/migrations/2026_11_04_000001_create_crm_identity_organizations_memberships.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/database/migrations/2026_11_04_000001_create_crm_identity_organizations_memberships.php) | `c99ff53c34c3fb5ba717c76665f135cb14ec04a9` | Existe no caminho normal de migrations; exige PostgreSQL, mas a guarda do método up não verifica explicitamente APP_ENV local/testing. Há risco de execução não intencional caso a migration seja disparada em ambiente não autorizado. Não prova que tenha sido executada em produção. |
| **F02** | [app/Core/Authorization/DenyByDefaultAuthorizer.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/app/Core/Authorization/DenyByDefaultAuthorizer.php) | `daa13599de92bd3d5fe120909f724f0f41d40dac` | Retorna sempre uma negativa e não apresenta caminho de ALLOW. Inexistência de autorização positiva é o comportamento deliberado na fase F0, não um bug a contornar. |
| **F03** | [app/Providers/AppServiceProvider.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/app/Providers/AppServiceProvider.php) | `15155e057dad0c56782a440c8327a3bc2a52540a` | Vincula Authorizer ao DenyByDefaultAuthorizer e limita configuração IAM experimental a local/testing. |
| **F04** | [app/Core/Tenancy/PostgresActiveMembershipStore.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/app/Core/Tenancy/PostgresActiveMembershipStore.php) | `245c73a8c7605d0b5bbff3f305876f5cc6f45dd6` | Checa PostgreSQL, transação dedicada, owner/BYPASSRLS/superuser, RLS/FORCE RLS e GUC prévia; usa contexto transacional e lock de membership. Não comprova configuração do banco da VPS. |
| **F05** | [app/Core/Tenancy/TenantAccessBoundary.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/app/Core/Tenancy/TenantAccessBoundary.php) | `ca7e61f34875c631b0a1022f48b5b7b31d9e4942` | Encadeia principal humano, vínculo, seleção de tenant e decisão de autorização; a execução do efeito depende de decisão allowed, que a implementação F0 sempre nega. |
| **F06** | [app/Core/Identity/DatabaseSessionAuthority.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/app/Core/Identity/DatabaseSessionAuthority.php) | `5b800424090e378fc14f10e004e56ce8c70dba27` | Garante transação IAM experimental e rejeição de credencial/sessão inválida; modelo físico e política comercial seguem pendentes. |
| **F07** | [routes/web.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/routes/web.php) | `8188ecec766a21853f04e4e0df64e5253fcfe32f` | Nenhuma rota web de negócio/login no arquivo; `/up` é configurada no bootstrap, não é prova de zero endpoints em outros sistemas externos. |
| **F08** | [.github/workflows/ci-postgresql-rls.yml](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/.github/workflows/ci-postgresql-rls.yml) | `e68a2babcc7be885731cfa893cd5fea07d420689` | CI sintético em PostgreSQL 16/Redis 7/PHP 8.4 cria roles isoladas e executa testes. Logs históricos do HEAD indicaram success, não equivalem a produção. |
| **F09** | [tests/Feature/PostgresMembershipRlsTest.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/tests/Feature/PostgresMembershipRlsTest.php) | `2e807ff25285caba6b1f381daf9b31a1e5bc38dd` | Casos automatizados para troca A/B, contexto de sessão, rollback, GUC mutável, RLS e revogação. Nenhum teste foi iniciado nesta inspeção. |
| **F10** | [app/Core/Async/AsyncAuthorityBridge.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/app/Core/Async/AsyncAuthorityBridge.php) | `797d08ea82f6b3b02e55ac610b52a6798a628be0` | Classe de inspeção de contratos assíncronos, sem dispatch/efeito comercial, explicitamente deny-only. |
| **F11** | [config/identity.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/config/identity.php) | `d52030989ce7091036d26142c05a3754ac001a95` | `CRM_AUTH_EXPERIMENTAL=false` por padrão; prazos e limites são de laboratório, não política de produção homologada. |
| **F12** | [database/experimental-migrations/auth/2026_11_04_000002_create_candidate_crm_auth_registry.php](https://github.com/rafhael100/imagine-crm/blob/8c9683acfd8decf98544402acda360d7e0f0a489/database/experimental-migrations/auth/2026_11_04_000002_create_candidate_crm_auth_registry.php) | `af381d34dbe8fed3eff8ecbfe099ccfd0b30a955` | Migração IAM fora de database/migrations padrão e com guarda local/testing. Contrasta com a migração principal, indicando necessidade de revisão de empacotamento. |

## 5. Achados que não podem ser diluídos em votos documentais

### LAC-C03-01 — Migration principal candidata no caminho padrão de migração (**P0, ação futura específica**)

A migration F01 reside em `database/migrations/` e só bloqueia driver diferente de PostgreSQL; a anotação 'isolated development' é comentário, **não guarda executável por ambiente**. Em contraste, as migrations IAM experimentais F12 têm verificação explícita de `identity.experimental` e ambiente `local/testing`. **Propor** revisão formal do desenho de guarda e segregação de migrations no fluxo de deploy futuro. **Não editar nem executar** a migration nesta auditoria. A existência do risco no código não significa que houve execução fora de ambiente autorizado.

### LAC-C03-02 — Cobertura RLS limitada ao núcleo/artefatos sintéticos (**P0**)

RLS explícita da migration principal protege `crm_memberships`. Há RLS também em ledger sintético de laboratório. Não foi demonstrada política em tabelas comerciais, relatórios, arquivos, views e demais fluxos finais do CRM. Completar a matriz objetos→tenants→roles→ações→políticas e excepcionar objetos globais de modo explícito **antes** de qualquer política positiva de negócio.

### LAC-C03-03 — Autorizações comerciais ainda negadas por padrão (**P0 de dependência, não defeito**)

`DenyByDefaultAuthorizer` e o binding do ServiceProvider tornam os efeitos comerciais negados nesta fase. **Preservar esse bloqueio** enquanto CORE02-D04..D10/SEG/owner de recurso não passarem por aceites específicos. Não usar ALLOW genérico apenas para criar tela S01.

### LAC-C03-04 — Contexto SQL e reuso de pool não homologados para operação real (**P0**)

O adaptador da membership faz transação dedicada, consulta privilégios efetivos e rejeita contexto prévio não vazio. Os testes ilustram que a GUC `app.tenant_id` é mutável pelo papel de runtime e **não autentica** usuário. Antes de operações comerciais, especificar modalidade de pooling, fronteira transacional, rollback, acesso via view/função e controles por serviço, com revisão SEG/DBA e provas futuras.

### LAC-C03-05 — Conjunto de testes em CI é evidência de laboratório, não de homologação (**P1**)

Existem testes de RLS, revogação, fallback e canais. O commit DEV `8c9683ac...` tem dois workflows associados com resultado `success` (PostgreSQL RLS F1 e F0 CI), constatados pelos metadados do GitHub nesta auditoria. **Não** foi executado ou reexecutado teste neste trabalho nem examinados logs completos para certificação de segurança; não inferir go-live.

### LAC-C03-06 — Revisores e owners de dados reais ainda não atestaram encerramento (**P0 para homologação**)

R08-G01..G06 estão coordenadas internamente pela mesma pessoa, o que não constitui revisão independente onde necessária. Exigir competência e, quando aplicável, revisão segregada SEG/DBA/LGPD/QA e decisão de risco residual, sem classificar como fechado por documentação.

## 6. Sequência de engenharia proposta (sem autorização de código)

| Ordem | Produto de engenharia a solicitar em futura autorização expressa | Evidência necessária para prosseguir | Situação |
|---|---|---|---|
| 1 | Revisar isolamento e proteção de execução das migrations, particularmente F01 | Critério por ambiente, planejamento CI/deploy e negação de execução indevida, com revisão | **NÃO AUTORIZADO** |
| 2 | Completar inventário físico de objetos, roles e relações efetivas em um ambiente liberado | Catálogo comprovado, owner, tenancy, views/funções, bypass e FKs | **NÃO AUTORIZADO** |
| 3 | Projetar contrato de conexão/pool/transação por execução | Política física revisada de contexto, cleanup, erro, rollback, workers | **NÃO AUTORIZADO** |
| 4 | Homologar política CORE02 de recurso/ação/campo/escopo antes de qualquer ALLOW | Política versionada, owner, risco, reversão e controles negativos | **NÃO AUTORIZADO** |
| 5 | Executar depois, sob autorização separada, testes e revisão SEG/DBA/QA | Casos S01/CORE e logs/evidências reais com critérios de aceite | **NÃO AUTORIZADO** |

**Próximo passo documental útil:** produzir a pauta específica para decidir **prioridade LAC-C03-01 (proteção de migrations)** e **LAC-C03-02/04 (cobertura RLS e conexão)**, em vez de pedir reaprovação de princípios. Se a direção desejar uma tarefa Codex, precisará autorizar por ato próprio **escopo mínimo de código e testes sintéticos** e reavaliar gates aplicáveis; esta autorização não decorre do presente relatório.

## 7. Controle e preservação

**C01..C08 PENDENTES; 67 IDs CORE, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS.** Funções R08-Gxx ≠ gates INT-Gxx.

Não foram tocados `main`, PRs #2/#21, branch de desenvolvimento, VPS, CI ou testes por esta atividade. O relatório é um **arquivo Markdown novo** na branch documental isolada; nenhuma alteração de código, SQL, `ALLOW`, `GRANT`, esquema, conexão, credencial ou dado real foi autorizada.