# Migração candidata do monitor33 para owner+monitor35 — 18/11/2026

## Lacuna e recorte

A base é a [CRM #660](https://github.com/rafhael100/imagine-crm/pull/660),
commit `96dbf1aed2d1cf3237bc3631c4594540a0168d2a`, árvore
`d76f2cc02ce483242b3cd5ccf4b043b0f25aa8e6`. O bootstrap owner existente
instala a coluna técnica e duas tabelas em uma base nova. Ele não fornece
um contrato de reaplicação sobre um perfil já instalado nem distingue uma
instalação completa de estados parciais ou divergentes.

A branch `feat/crm-owner-monitor-incremental-migration-2026-11-18` acrescenta
uma API e um ensaio exclusivamente sintéticos para **monitor33 → owner+monitor35**.
O SQL de bootstrap permanece literal. Não há matriz adicional de migração
owner27, atualização de instalação existente na VPS ou ativação de runtime.
Os detectores legados de backup, F251/F252 e as provas atuais são preservados.

A prova da base pertence à
[CI owner 44246202205](https://github.com/rafhael100/imagine-crm/actions/runs/44246202205),
job `134605651011`, que passou a jornada owner, recuperação27, convivência,
recuperação35, Chromium e desligamento do perfil owner. Essa evidência não
aprova antecipadamente a migração desta filha.

## Fontes e referências independentes

O módulo `scripts/ops/private_crm_owner_monitor_migration.py` lê os sete
inputs pelo commit base, usando `git show` e verificando modo e blob. Todos
têm modo Git `100644`:

| Fonte | Blob fixo |
| --- | --- |
| `infra/homologation/schema.sql` | `9dde1bb86d496083ccd163225d8dd0c79b391cdb` |
| `infra/homologation/20-runtime-roles.sh` | `ae5fb2f964cc672d681f4108c1aff7fff5ea5682` |
| `infra/homologation/s2s/install-candidate.sql` | `3cafbf27be3e3968d401dbf98beac0e63fd27bea` |
| `infra/homologation/s2s/outbound-candidate.sql` | `4166eeb7d8dd2eabec9392e94dff4b392650de52` |
| `infra/homologation/s2s/execution-feedback-candidate.sql` | `4804870196ad820007f616d260efb8ea9b64dc0c` |
| `infra/homologation/s2s/execution-monitor-candidate.sql` | `d6dd92bee6ae35ded0aa6655dbdbe858b0d9f181` |
| `infra/homologation/s2s/owner-drafts.sql` | `a1f93eb85fdca3041f9dd16c21aa58448351a952` |

O ensaio cria dois clusters de referência independentes e cinco clusters
alvo, todos PostgreSQL16 novos, sem rede, portas publicadas, binds ou processo
CRM. Os dados ficam em tmpfs. O mesmo ID de imagem PostgreSQL observado é
usado em todos eles; nomes e labels vinculam os recursos à HEAD, run e
tentativa reais da CI.

A referência33 e cada alvo33 são construídos diretamente pelos seis inputs
base/S2S/monitor. A referência35 acrescenta o bootstrap owner original inteiro.
Ela não chama o migrador. Nenhum perfil33 é produzido pela remoção de objetos
de um banco35, e nenhum catálogo esperado é derivado do alvo depois de uma
mutação.

`references(query33, query35, source_files)` sela as duas referências em
um `ReferencePair`. Esse objeto é uma **API interna do builder confiável
e pinado**, não um mecanismo para autenticar JSON recebido de um usuário.
O ensaio fornece callbacks dos clusters que acabou de construir; não existe
entrada de manifesto externo no CLI.

## API e transação

As APIs principais são `pinned_sources()`, `capture_inventory(query)`,
`references(...)` e `migrate(source_command, reference_pair, ...)`.
O comando de origem aceita somente `docker exec -i` com o ID completo de
um contêiner admitido. As verificações de CI, origem, isolamento e imagem
antecedem o acesso de migração.

O migrador abre uma única sessão PostgreSQL limitada por timeouts, obtém o
lock de coordenação e o lock da tabela de contas e, na mesma transação:

1. Compara o inventário do alvo com a referência33 e a referência35.
2. Recusa qualquer outro estado antes de aplicar o delta.
3. No perfil33 exato, executa o corpo literal de `owner-drafts.sql`, removendo
   somente seu envelope externo `BEGIN/COMMIT`.
4. No perfil35 exato, preserva o estado instalado sem repetir o DDL.
5. Exige o inventário final35 exato, fingerprints preservados e a sequência
   inalterada antes do commit.

A comparação inclui catálogo, segurança, owners e ACLs do banco, schemas,
tabelas, colunas e funções; RLS, políticas, constraints e triggers; roles,
memberships, configurações por banco/role; índices, tipos, regras, extensões
e parâmetros da sequência. O owner do banco precisa ser observado
explicitamente: a presença de `pg_database_owner` como owner de `public`
não identifica, por si, qual role controla o banco.

`last_value` e `is_called` são observados separadamente antes/depois.
Eles não são tratados como metadados imutáveis nem como valores congelados
pelo snapshot. As falhas anteriores ao COMMIT devem encerrar a sessão e
reverter a transação; os diagnósticos públicos usam códigos fechados e
SQLSTATE permitido. Uma perda de confirmação depois de um COMMIT efetivado
pode deixar o resultado indeterminado para o cliente. Não se afirma que esse
erro desfaz o commit. Uma nova chamada explícita pode conferir o estado35
exato e devolver no-op; não existe retry automático.

O delta esperado acrescenta somente `owner_draft_lock_marker` a contas e
as tabelas de grants/intents owner, com seus índices, constraints, políticas
e privilégios já definidos no bootstrap. O conjunto continua com nove
roles, nove funções S2S e uma sequência. Não há nova função de execução
owner.

## Dados representativos e preservação

O alvo33 recebe fixtures SQL relacionadas por FKs: duas empresas e
identidades, contas, memberships, funis, leads, tarefa, trial, preferências,
uma automação pendente, evento, outbox, histórico e projeção do monitor.
As oito tabelas S2S têm dados representativos, incluindo nonce e auditoria.

São **20 tabelas com linhas e 13 vazias**. Os fingerprints cobrem as 33
tabelas, incluindo o conjunto vazio. Isso comprova preservação dos dados
sintéticos exercitados; não representa cobertura comercial integral das
tabelas vazias ou execução das regras funcionais de todos os módulos.
Histórico e recibos aqui são amostras SQL declaradas: nenhum HTTP, verificação
criptográfica de recibo ou n8n é executado por este ensaio.

Para `crm_private_accounts`, a comparação entre antes/depois usa a projeção
explícita das oito colunas originais:

`identity_id, organization_id, label, role, status, created_at, team_role, desired_plan`.

O teste também exige valor zero da coluna nova nas duas contas existentes.
Depois do primeiro apply, são inseridos dois grants e uma intenção pendente
owner, e o marker de uma conta passa a nove. A reaplicação deve preservar
todos os fingerprints35, esses valores e o estado da sequência; não pode
reinicializar o marker nem apagar dados owner.

## Matriz focal da prova

| Cenário | Critério |
| --- | --- |
| Monitor33 exato e povoado | `APPLIED`, target35 exato, dados33 preservados, marker zero e duas tabelas novas inicialmente vazias |
| Owner+monitor35 exato com grants/intents e marker não zero | `ALREADY_PRESENT`, todas as 35 tabelas e a sequência preservadas |
| Instalação parcial canônica | Prefixo pinado até a primeira tabela owner produz 34 tabelas; recusar sem completar ou reparar |
| CHECK de tarefas substituído por `CHECK(true)` no33 | Recusar a divergência e manter integralmente o estado da contraprova |
| Grant extra de DELETE nos intents do35 | Recusar mesmo no caminho de reaplicação; não retirar o grant nem alterar os dados |
| Owner do database alterado para a role app no33 | Recusar e preservar o estado divergente; não restaurar o owner automaticamente |
| Lock concorrente de contas antes do delta | Observar espera no `LOCK TABLE`, receber SQLSTATE `55P03` e manter o perfil33 integral |
| Lock concorrente no FK depois do primeiro ALTER | Observar o CREATE bloqueado e o lock de contas; receber `55P03`, exigir rollback da coluna e ausência das tabelas owner |

As quatro negativas de drift/parcial usam inventário, dados e sequência
capturados **depois da mutação intencional e antes da tentativa**. A recusa
deve deixar exatamente esse estado inalterado. Essa comparação não promove
o estado divergente a referência aceita pelo migrador.

## Contraprova do rollback tardio

Uma sessão real mantém `SHARE` em `crm_organizations`. O migrador pode
ler o inventário e executar o primeiro `ALTER TABLE` de contas, mas a
criação de `crm_private_owner_draft_grants` precisa do lock do FK.

O ensaio observa o backend e a instrução em `pg_stat_activity`, a espera
`ShareRowExclusiveLock` na organização e o `AccessExclusiveLock` já
concedido em contas. Só então aceita o erro `55P03`. Depois do encerramento
do backend, confere catálogo, dados e sequência originais e ausência da
coluna/tabelas acrescentadas. Um exit code isolado não satisfaz essa prova.

A base técnica está na documentação primária de
[CREATE TABLE / REFERENCES do PostgreSQL16](https://www.postgresql.org/docs/16/sql-createtable.html)
e nos [modos de lock explícitos](https://www.postgresql.org/docs/16/explicit-locking.html).
O teste exige a observação real na CI; a documentação não substitui o ensaio.

Essa prova não demonstra migração online, ausência de indisponibilidade,
reaplicações concorrentes bem-sucedidas ou compatibilidade com carga
operacional. Os únicos concorrentes do laboratório são os blockers
controlados dessas contraprovas.

## Integração com a CI existente

O workflow `ci-crm-owner-agents-drafts.yml` conserva trigger, pins, jobs,
timeout global de 30 minutos e todos os estágios anteriores. A admissão
acrescenta somente o tuple completo da nova head ref na DEV fixada e exige
a #660 pinada como ancestral; não mistura campos dos contextos históricos.

O ensaio novo roda **depois** das provas atuais e do desligamento owner.
Não duplica jornadas HTTP, MFA, Chromium, execução n8n ou restore. A etapa
focal tem limite próprio de oito minutos. Um `finally` remove os recursos,
e uma etapa `always()` adicional repete o cleanup restrito quando o ensaio
foi tentado. A limpeza confere nome, labels, imagem e isolamento antes de
remover apenas contêineres desta HEAD/run/tentativa.

As regressões do guard continuam no arquivo de teste já chamado pelo
workflow. O novo módulo e o ensaio entram no `py_compile` existente.
Nenhum script de bootstrap, perfil legado ou gate F251/F252 é reescrito.

## Validação e limites

A revisão local pode comprovar sintaxe Python/YAML/Bash, os sete pins,
preservação dos passos existentes, protocolo de subprocesso e casos de
admissão. **Não há PASS local de PostgreSQL/Docker desta candidata.**
O resultado real deverá ser registrado a partir da CI da HEAD publicada,
com run/job/tentativa, contagens, hashes, SQLSTATEs, backends observados e
cleanup.

Esta é uma candidata **DRAFT / NO-MERGE / NO-DEPLOY / NO-GO operacional**.
Não ativa conta humana, grants operacionais, perfil owner em instalação
existente ou execução de rascunhos. Migração persistente, plano de janela,
autoridade atual, backup externo e recuperação distribuída continuam fora
da prova. A API permanece limitada ao laboratório descartável autorizado.
