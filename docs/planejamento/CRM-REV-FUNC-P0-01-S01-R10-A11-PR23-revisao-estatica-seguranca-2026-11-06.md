# CRM Imagine — Parecer de revisão estática PR #23 / R10-A11 (06/11/2026)

**Natureza:** revisão técnica documental e estática solicitada pela direção em resposta 'ok aprovado' à **próxima etapa recomendada de revisão de segurança do PR #23**, **não autorização de merge**.

**Resultado:** correção específica da descoberta automática **tecnicamente coerente e verde em CI sintético**, **mas NÃO LIBERADA PARA MERGE** até análise/aceite dos riscos R-01..R-04 e autorização separada. Não há aqui homologação independente de SEG/DBA.

## 1. Referências fixas e evidências

- Repositório privado `rafhael100/imagine-crm`; [PR #23](https://github.com/rafhael100/imagine-crm/pull/23): `open`, `draft=true`, `merged=false`, base `dev/fast-track-crm-2026-11-04` no SHA `8c9683acfd8decf98544402acda360d7e0f0a489`.
- Branch filha `dev/f1-11-migration-isolation-2026-11-06`, HEAD `cb0c1791290e570c46bebb6dd88d704fd0e75617`; commits `de922d61`, `b8970905` e `cb0c1791`.
- [CI F0 Laravel](https://github.com/rafhael100/imagine-crm/actions/runs/42248524419): `completed/success`.
- [CI PostgreSQL 16/RLS F1](https://github.com/rafhael100/imagine-crm/actions/runs/42248524416): `completed/success`. Etapas de discovery, migrate tenant explícito, IAM/async e testes de isolamento e suíte completa em `success`.
- [Briefing R10-A11](CRM-FUNC-P0-01-S01-R10-A11-briefing-codex-seguranca-migrations-proposta-v0.1-2026-11-06.md) e [registro do GO pontual e CI](CRM-DEC-FUNC-P0-01-S01-R10-A11-aprovacao-implementacao-controlada-2026-11-06.md).
- A revisão decorre da leitura de patches/arquivos e metadados do GitHub; **não houve nova execução de PHP, SQL, testes, deploy ou acesso à VPS nesta tarefa**.

## 2. Resultado técnico por controle

| Controle revisado | Evidência no PR | Status |
|---|---|---|
| Descoberta automática | Move migration principal para `database/experimental-migrations/tenant/` | Atendido no laboratório |
| Opt-in e bloqueio de ambiente | `guard()` em `up()` e `down()`; flag desabilitada por padrão; `local/testing` e `pgsql` | Atendido parcialmente: alvo DB não autenticado como descartável |
| Preservação RLS e FKs | Migration principal conserva schema e política `ENABLE/FORCE RLS`, `USING/WITH CHECK` | Atendido no laboratório |
| Compatibilidade CI | `.github/workflows/ci-postgresql-rls.yml` passa a executar tenant explicitamente antes de IAM | Atendido no CI |
| Regressões por expectativa antiga | `AuthMigrationIsolationTest`, `FastTrackSecurityBaselineTest`, `PostgresAsyncLedgerTest` atualizados após logs de falhas | Atendido para cenários sintéticos existentes |
| Segurança do app | Nenhum ALLOW de negócio; `DenyByDefaultAuthorizer` não foi modificado | Preservada |
| Operação e migração de bancos persistentes | Nenhum alvo de DB/ledger persistente inspecionado | Não homologado |

## 3. Achados e tratamento proposto — não foram corrigidos nesta revisão

| ID | Prioridade | Achado | Fundamentação | Próxima ação exigida/proposta |
|---|---|---|---|---|
| **R-01** | P0 para GO de laboratório mais amplo | Guarda não comprova banco descartável | O método `guard()` só verifica `identity.tenant_schema_experimental`, APP_ENV `local/testing` e driver PostgreSQL. Se a aplicação laboratorial apontar acidentalmente a um banco não-descartável e tiver privilégios DDL, a guarda não distingue o alvo. | Limitar credenciais a bases/schemas efêmeros por controle operacional e, se escolhido, introduzir validação forte do alvo (conexão/DB/schema allowlist) com teste negativo; SEG/DBA deve decidir contramedida, sem assumir proteção total por flag. |
| **R-02** | P1 para integração em DEV persistente | Compatibilidade de histórico de migrations ainda não demonstrada | O arquivo foi movido mantendo o basename `2026_11_04_000001_...`; schemas de laboratório pré-existentes podem registrar a migration no ledger antigo. O CI atual parte de banco novo e não comprova `migrate`, `status`, rollback ou atualização em banco que já possui esse registro. | Antes de merge, preparar política de migração/retirada de schema anterior somente sintética e testar upgrade em banco descartável pré-povoado, sem consultar/alterar a VPS. |
| **R-03** | P1 de processo/implantação | Proteção da migration não protege todos os comandos destrutivos | A retirada da descoberta padrão bloqueia aplicação automática desta migration; não significa que `migrate:fresh`, `db:wipe` ou operações privilegiadas contra um banco incorreto sejam seguras. O comando `migrate:fresh` pode descartar tabelas antes da descoberta de arquivos. | Distinguir oficialmente segurança de descoberta da migration de proteção do banco/credenciais; exigir controles de implantação e treinamento, testes sintéticos destrutivos apenas em schema descartável quando autorizados. |
| **R-04** | P0 para homologação, não necessariamente bloqueio de revisão documental | Parecer técnico independente não apresentado | A revisão estática atual é realizada pelo assistente que também operou os commits via GitHub. Suítes CI positivas verificam contrato sintético, não certificam independência nem infraestrutura produtiva. | Solicitar a revisão competente de segurança/DBA conforme gates vigentes; registrar escopo/autor/risco residual. A manifestação do titular sobre merge será ato distinto. |

**Distinção essencial:** as observações R-01/R-02/R-03 são riscos de configuração/ciclo de vida e lacunas de comprovação, **não declaração de exploração nem de incidente real**. O conjunto de testes em banco efêmero funciona, mas não simula necessariamente todos os processos de release ou configurações de credenciais.

## 4. Aferição de critérios AC01..AC12 (somente o que as evidências permitem)

| AC | Situação | Evidência ou limite |
|---|---|---|
| AC01 | Confirmado em laboratório | Migration tenant não é descoberta no caminho padrão; testes `AuthMigrationIsolationTest` e `FastTrackSecurityBaselineTest` verificam |
| AC02 | Parcial | Env/flag/driver verificados; sem evidência de banco descartável como pré-condição operacional obrigatória; risco R-01 |
| AC03 | Parcial | Guarda `up/down` existe; rollback real via CLI/caminhos destrutivos e histórico anterior não estão completamente cobertos; R-02/R-03 |
| AC04 | Confirmado em CI | Workflow PostgreSQL executa tenant experimental explicitamente antes de IAM e async; etapas concluíram sucesso |
| AC05 | Confirmado em testes | Exigência anterior de schema tenant em `production` simulado foi trocada por teste de exclusão e opt-in explícito |
| AC06 | Parcial | Testes negativos do método `up/down` impedem operação antes de DDL em condição inválida; auditoria de todos os caminhos CLI ainda pendente |
| AC07 | Confirmado em laboratório | DDL, FK, FORCE RLS e USING/WITH CHECK preservados; testes PostgreSQL 16/RLS passaram |
| AC08 | Confirmado por diff | Nenhuma alteração ao `DenyByDefaultAuthorizer`, routes ou grants de negócio |
| AC09 | Confirmado em CI | F0 Laravel e PostgreSQL/RLS concluiram `success` na HEAD `cb0c1791`, sem homologação de produção |
| AC10 | Confirmado por diff | Sete caminhos no PR: config, `.env.example`, workflow, migration movida e três testes |
| AC11 | Não verificado em infraestrutura; escopo respeitado | Sem acesso VPS e dados reais nesta revisão; defesa em profundidade por credencial de ambiente não demonstrada |
| AC12 | Confirmado | PR #23 aberto, DRAFT e com base na DEV; sem merge |

## 5. Encaminhamento proposto e alçadas

1. **Não realizar merge nesta etapa.** Manter PR #23 em DRAFT na branch DEV filha; não tocar `main`, PRs #2/#21 ou VPS.
2. Preparar, após autorização futura para alterações, testes adicionais para conexão/banco não descartável e histórico de migration já registrada em schema sintético, com plano de restauração seguro. Não executar contra banco de produção.
3. Definir responsabilidade e salvaguardas para `migrate:fresh`/`db:wipe` e rotinas de release: proteção do DDL não equivale a proteção genérica de comandos administrativos.
4. Solicitar parecer SEG/DBA tecnicamente competente, com independência quando os critérios normativos exigirem. Esta revisão feita pelo mesmo assistente que produziu código **não conta como revisão independente**.
5. Só após resolução/aceitação documentada dos riscos, checagem de HEAD/CI e **nova autorização expressa da direção** considerar merge **exclusivamente à branch DEV**. Mesmo esse merge não habilitaria produção.

## 6. Barreiras finais

**C01..C08 PENDENTES, 67 CORE técnicos abertos, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS.** Nenhum ALLOW/GRANT, serviço comercial, dados pessoais, credencial de cliente, teste de homologação S01 ou implantação está aprovado por este parecer.

Este arquivo é o único objeto criado/alterado na branch documental durante a presente etapa de revisão; **não alterar código** nem emitir um `APPROVE` de PR a partir deste documento.