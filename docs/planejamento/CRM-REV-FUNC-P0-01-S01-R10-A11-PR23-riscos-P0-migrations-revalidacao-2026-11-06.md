# CRM Imagine — PR #23 / R10-A11 — Revalidação da mitigação P0 e compatibilidade de migrations

**Data:** 06/11/2026 — America/Sao_Paulo. **Estado:** revisão técnica estática do segundo incremento **em laboratório**, sem parecer independente de SEG/DBA e sem autorização de merge.

## 1. Aprovação, escopo e precedência

- A direção respondeu **“ok aptovado”** à recomendação de corrigir/mitigar risco P0 e verificar compatibilidade de migrations em **banco sintético**, com alteração somente na branch filha. O aceite não abrange merge, produção, VPS ou mudança de grants comerciais.
- [Parecer anterior R10-A11](CRM-REV-FUNC-P0-01-S01-R10-A11-PR23-revisao-estatica-seguranca-2026-11-06.md) apontou R-01 (alvo de DDL indevido), R-02 (migration ledger antigo), R-03 (comandos destrutivos globais) e R-04 (revisão independente).
- [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) continua OPEN/DRAFT, base `dev/fast-track-crm-2026-11-04` no SHA `8c9683acfd8decf98544402acda360d7e0f0a489`; branch filha `dev/f1-11-migration-isolation-2026-11-06` com HEAD `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558`.
- Revisões e alterações foram realizadas diretamente pelas ferramentas GitHub do assistente; **não pelo executor Codex** e não em outro banco. Este parecer do mesmo executor **não é independente**.

## 2. Alterações efetivamente publicadas

| Arquivo | Alteração |
|---|---|
| `database/experimental-migrations/tenant/2026_11_04_000001_create_crm_identity_organizations_memberships.php` | `guard()` agora recusa DDL se o hostname do config não for loopback, se URL de DB estiver configurada, se o nome esperado não obedecer à classe de laboratório (`imagine_crm_ci`, `imagine_crm_f0`, `imagine_crm_lab_*`), se não corresponder à conexão e se `SELECT current_database()` divergir. Mantém guarda de flag, `local/testing`, driver `pgsql` em `up()` e `down()`. |
| `config/identity.php` | Campo `tenant_schema_lab_database` por variável de ambiente, valor vazio por padrão (nega). |
| `.env.example` | Exige seleção explícita `CRM_TENANT_SCHEMA_LAB_DATABASE`; não contém conexão real ou segredo. |
| `.github/workflows/ci-postgresql-rls.yml` | Define nome esperado do serviço descartável CI `imagine_crm_ci` sem alterar papéis ou políticas de negócio. |
| `tests/Feature/AuthMigrationIsolationTest.php` | Testes negativos de DB divergente e host não-loopback; cenário de histórico de migration em schema aleatório descartável: executa opt-in, verifica ledger existente, release padrão não reexecuta, opt-in repetido não duplica e rollback explícito em laboratório. |

Commits adicionais: `71d5b28fc6d8e769f913125b1e800bf0c4130c64` (proteções/novos testes) e `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558` (correção de namespace de exceção nos testes). O segundo commit é a **HEAD válida** da avaliação.

## 3. Evidência verificável de testes da HEAD

| Pipeline | Execução | Resultado | Escopo observado |
|---|---|---|---|
| F0 / Laravel, SQLite | [42252193082](https://github.com/rafhael100/imagine-crm/actions/runs/42252193082) | **completed/success** | 384 passados / 66 ignorados (testes PostgreSQL requerem serviço CI próprio) |
| PostgreSQL 16 / RLS / PHP 8.4 | [42252193108](https://github.com/rafhael100/imagine-crm/actions/runs/42252193108) | **completed/success** | 449 passados / 1 ignorado; suite específica RLS 18/18; `AuthMigrationIsolationTest` com teste do DB/host e ledger histórico passou |

**Sem declaração falsa:** os testes foram executados pelo **GitHub Actions** em serviços sintéticos, não pelo assistente em VPS ou banco externo. Falhas em uma HEAD intermediária antecederam o último commit; o status válido é o da HEAD final citada.

## 4. Status dos quatro achados anteriores

| ID | Resultado atual | Limite residual e encaminhamento |
|---|---|---|
| **R-01 — alvo indevido (P0)** | **Mitigação laboratorial aplicada e testada** | Host loopback e banco esperado/real alinhados reduzem erro acidental. Ainda exigem credenciais isoladas, infraestrutura descartável, proteção de release e parecer SEG/DBA. Flags ou nome de banco não provam impossibilidade de erro de operador. |
| **R-02 — histórico anterior (P1)** | **Compatibilidade de ledger básico comprovada em banco sintético** | Mesmo basename em histórico evita reinserção e DDL duplicada; rollback opt-in em schema descartável passou. Histórico diverso, drift de schema, configurações persistentes e operações de clientes seguem não examinados. |
| **R-03 — comandos destrutivos (P1)** | **Pendente como risco de processo** | `migrate:fresh`, `db:wipe` e credenciais DDL continuam perigosos se dirigidos a banco inadequado. Precisa desenho futuro de release/segregação; não inferir proteção desses comandos a partir da guarda de `up/down`. |
| **R-04 — revisão independente (P0 para homologação)** | **Pendente** | Revisão feita pelo mesmo assistente que alterou código e contas GitHub. Revisor competente/independente quando exigido ainda não comprovado. |

## 5. Revisão e decisão recomendada

**Parecer técnico limitado:** o PR atende ao recorte de redução de risco e evidencia os testes sintéticos de compatibilidade. É **candidato a avaliação de integração na DEV**, porém **não está aprovado para merge** e não se declara homologação de segurança. A direção deve obter ou formalmente tratar as revisões SEG/DBA exigidas, aceitar/documentar riscos residuais quando cabível, reconfirmar SHA e checks e então emitir **ato separado** se desejar o merge exclusivamente em DEV. É vedado interpretar eventual merge DEV como liberação de produção.

**Preservações:** `main`, PRs #2/#21, DEV de base e VPS inalterados pela tarefa. `DenyByDefaultAuthorizer` sem `ALLOW`; C01..C08 PENDENTES e os 67 CORE, SEG, AUT, ARQ/AGT, INT/G, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS.