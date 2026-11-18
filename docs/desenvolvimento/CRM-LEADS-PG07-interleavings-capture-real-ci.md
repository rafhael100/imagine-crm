# CRM LEADS PG07 — captura real concorrente no CI descartável

Este recorte acrescenta três contraprovas executáveis aos testes de revogação
do vínculo Sites → CRM. O código de captura, os grants, as políticas RLS, as
APIs e as migrações operacionais permanecem iguais à base da PR #646
(`f719b9dcdeebbc5b07a6472f9b86fb9b31028332`).

A [issue #564](https://github.com/rafhael100/imagine-crm/issues/564) e seu
[comentário de cobertura parcial](https://github.com/rafhael100/imagine-crm/issues/564#issuecomment-6879911096)
pedem a ordem de revogação primeiro e os caminhos de falha, timeout e rollback.
O teste anterior em `PostgresLabLeadsRlsTest` mantinha manualmente o advisory
lock da captura, iniciava uma revogação concorrente e só chamava `captureSigned`
depois da revogação. Ele continua sendo uma prova mais estreita do protocolo
de lock. Os três casos novos executam `LaboratorySignedSiteCapture::captureSigned`
inteiro em outro processo PHP e observam a disputa real no PostgreSQL 16.

Este documento descreve o teste e seus critérios. O resultado da execução deve
ser associado ao run, job e SHA da candidata na PR; não é uma declaração de CI
aprovado antes da execução.

## Casos e efeitos observados

| Caso | Ordem controlada e observada | Resultado exigido |
| --- | --- | --- |
| Revogação primeiro | A conexão owner executa o `UPDATE` real do vínculo em uma transação aberta. O trigger adquire o advisory lock. O processo de captura chega ao mesmo lock e aparece como waiter do owner. O owner faz `COMMIT`. | A captura retorna `synthetic_conflict_denied`; lead, claim, nonce e auditoria não mudam. O vínculo permanece revogado na versão 2. |
| Timeout da captura e rollback da revogação | O owner mantém a revogação sem commit. A captura real espera pelo advisory lock e encontra `lock_timeout` de 3 segundos. Só depois de observar a falha o parent faz `ROLLBACK` da revogação. | O driver retorna `55P03` na chamada advisory. A transação da captura está encerrada e não deixa efeitos. O mesmo processo, conexão e PID voltam a capturar o mesmo envelope/nonce: `synthetic_captured`, depois `synthetic_replayed`, com exatamente um lead, claim e evento. |
| Falha tardia de auditoria | Uma conexão separada segura `ACCESS EXCLUSIVE` apenas na tabela de eventos. A captura real adquire o advisory lock, insere claim e lead e espera no `INSERT` de auditoria. Um terceiro backend inicia o `UPDATE` real de revogação e espera pelo advisory lock da captura. O locker da auditoria faz `ROLLBACK`. | Um `CHECK` temporário, restrito ao lead sintético A, recusa o evento com `23514`. O rollback remove claim, nonce e lead já inseridos. A revogação que esperava prossegue e pode commitar. Uma nova tentativa no mesmo child é recusada pelo vínculo revogado. |

As conexões que travam a tabela de eventos e que revogam o vínculo são
distintas. Usar um único backend para ambos os papéis criaria um ciclo
artificial: esse backend manteria o lock da auditoria e esperaria pelo advisory
que a captura só poderia liberar depois de inserir na auditoria.

Cada espera é confirmada por `pg_blocking_pids` **e** por locks incompatíveis
em `pg_locks`, com PID, `backend_start`, `application_name`, usuário e banco
dos dois participantes. Na falha tardia também se exige o `INSERT` da auditoria
em `pg_stat_activity`. Um atraso de tempo, por si só, não conta como prova de
concorrência.

Os três testes preservam um lead e seu evento da empresa B. As comparações
usam todas as colunas das tabelas de leads, claims e auditoria, ordenadas por
identidade. `signed_nonce` pertence à linha do claim e é comparado junto com
ela. A recusa não pode consumir o nonce, mudar uma linha existente ou criar
um evento parcial.

## Conexão reutilizada e observação dos erros

O helper `tests/Labs/leads/signed-capture-race-worker.php` cria uma única conexão
`crm_app_ci`. O protocolo por pipes aceita apenas captura, leitura da outra
empresa e encerramento. O parent não envia SQL nem destino de conexão ao
child. Os envelopes usam os identificadores e a chave HMAC fictícia já
existentes no laboratório PG07. Este job não executa o emissor original Sites
F398 nem o provisionador F399; suas jornadas dedicadas continuam separadas.

O ledger deliberadamente converte falhas de persistência em uma exceção
genérica. Para observar o SQLSTATE sem mudar esse contrato, somente o child
CI instala uma subclasse de `PDOStatement` por `PDO::ATTR_STATEMENT_CLASS`.
Seu `execute(?array $params = null): bool` chama o método original, devolve o
mesmo resultado e relança a mesma `PDOException`. Ele registra apenas o
SQLSTATE e o tipo da instrução, sem texto SQL, parâmetros ou detalhes do
driver. Também registra quais INSERTs terminaram com sucesso antes da falha
de auditoria. Isso distingue uma falha tardia de uma recusa anterior aos writes.

Depois de cada tentativa, **antes** de desconectar, executar `RESET` ou tentar
novamente, o child informa:

- o mesmo PID PostgreSQL da inicialização;
- `transactionLevel() = 0` e `PDO::inTransaction() = false`;
- marcador `app.tenant_id` vazio;
- nenhum advisory lock do próprio PID;
- nenhuma linha de lead ou claim visível sem escopo de empresa.

Uma leitura legítima da empresa B na mesma conexão confirma que ela continua
utilizável sem herdar o escopo A. Em retornos de negócio, inclusive captura e
replay, `can_publish`, `can_contact` e `can_activate` continuam `false`.

## Execução e critérios obrigatórios

Workflow: `.github/workflows/ci-crm-leads-rls.yml`, perfil
`CRM_LEADS_PG07_CONCURRENCY_CI=1`. O ambiente precisa passar pela verificação
SEC-CI-02, usar o PostgreSQL 16 descartável em `127.0.0.1:5432`, banco
`imagine_crm_ci`, fixtures sintéticas e o papel restrito já definido no job.
O helper também exige o repositório exato, `GITHUB_ACTIONS=true`, `CI=true`,
`APP_ENV=testing` e `CRM_LEADS_LAB=synthetic-only` antes de conectar.

O passo obrigatório seleciona apenas os três métodos novos:

```sh
php artisan test --filter='PostgresLabLeadsRlsTest::test_pg07_' \
  --no-ansi --fail-on-skipped --fail-on-incomplete
```

A suíte PG07 anterior é executada em um passo separado. O teste antigo de
emissor original F398 exige outro checkout e opt-in; seu skip nesse job não
deve ser apresentado como execução daquele emissor. Os três métodos novos
não podem obter um resultado verde por skip.

Marcadores emitidos somente depois dos respectivos asserts:

| Marcador | Evidência que deve acompanhá-lo |
| --- | --- |
| `PG07_REVOKE_FIRST_PASS` | captura real esperando, commit da revogação, recusa, delta zero nas três tabelas e empresa B preservada |
| `PG07_LOCK_TIMEOUT_PASS` | erro real `55P03`, rollback limpo e captura/replay com o mesmo PID e nonce |
| `PG07_LATE_AUDIT_ROLLBACK_PASS` | erro real `23514`, ambas as esperas observadas, claim/lead revertidos e constraint temporária removida |

Os waits do coordenador têm limites de 3 a 10 segundos. O timeout de statement
e de transação ociosa nos participantes é de 15 segundos. O protocolo do child
tem prazo de 15 segundos por comando e orçamento fechado de oito comandos.
O encerramento normal verifica a saída do processo e o desaparecimento do
backend. Em falha, o cleanup encerra somente o processo que criou e sessões
com seu marcador aleatório, papel, banco e identidade PID/`backend_start`.
As conexões owner são fechadas em `finally`; a constraint de falha é removida
com um `lock_timeout` próprio, também em `finally`.

## Limites que continuam abertos

As contraprovas usam transações e bloqueios reais, mas dados e autoridade
exclusivamente sintéticos. Elas não são uma prova exaustiva de ausência de
deadlocks, starvation, todas as ordens de alterações de vínculo ou todos os
modos de falha de infraestrutura. O corte de revogação continua baseado no
advisory lock transacional da candidata e precisa da avaliação independente
prevista em [CRM-LEADS-PG07](CRM-LEADS-PG07-revogacao-concorrente-advisory-ci.md).

A issue #564 permanece aberta. Este recorte não substitui SEG/DBA #27,
CORE/LGPD #259, autenticação e credenciais operacionais, consentimento,
pagamento, autorização de negócio ou revisão humana. Não instala grants,
rotas, serviços ou dados de cliente; não realiza merge, deploy ou acesso à VPS.
