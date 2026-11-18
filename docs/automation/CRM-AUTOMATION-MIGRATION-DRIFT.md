# Ledger de automações: recusa de alterações em RLS, permissões e política

Somente desenvolvimento e CI com PostgreSQL 16 descartável. DRAFT / NO-MERGE / NO-DEPLOY.
Este documento descreve o contrato da candidata e os ensaios que a CI deve executar;
os resultados de execução pertencem aos logs da SHA e da tentativa efetivamente testadas.

## Fontes dos problemas e contraprovas

O histórico de ACL/política continua preso à CRM #639,
`403a2b735e77bd72b529e296b20e63eab1c00859`. Essa migration verificava a quantidade
de políticas, mas não seu comando, papéis ou modo permissivo. Um `tenant_scope`
com `TO crm_homologation_owner`, por exemplo, permanecia restrito ao owner após
o `ALTER POLICY`; a aplicação perdia a visibilidade do próprio tenant enquanto
o script emitia `READY`.

O ajuste de grants revogava `UPDATE` e concedia apenas as operações necessárias,
mas não recusava `DELETE`, `TRUNCATE`, `REFERENCES` ou `TRIGGER` preexistentes.
Uma permissão adicional de exclusão podia sobreviver à reaplicação. Também não
havia comparação completa de destinatários e opções de delegação dos grants.

No PostgreSQL 16, revogar `UPDATE` da tabela também revoga os grants correspondentes
das colunas. Portanto grants diretos de `UPDATE` em colunas imutáveis **não** são
apresentados como permissões que sobreviviam ao código anterior. Eles entram
na regra de recusa explícita de alterações não revisadas, antes da normalização.
Referência: [PostgreSQL 16 — REVOKE](https://www.postgresql.org/docs/16/sql-revoke.html).

A nova contraprova usa a migration conservada no commit
`96dbf1aed2d1cf3237bc3631c4594540a0168d2a`, caminho
`infra/homologation/migrations/001_automation_ledger_candidate.sql`, blob Git
`2b67bc3b707c552c1508f90045ea7f0718ae668a`, modo `100644`.
Esse SQL já recusa as alterações de ACL e definição de política acima. A leitura
do código, porém, identifica três reparos implícitos quando o ledger já existe:
reativar RLS, voltar a forçar RLS e recriar `tenant_scope` ausente. Esses reparos
normalizam os três estados sem uma admissão prévia que os recuse e não atendem
ao contrato de preservar uma instalação divergente para diagnóstico.

Esses três comportamentos históricos são hipóteses executáveis derivadas do
código até que a nova CI os reproduza. O harness só registra `REPRODUCED` depois
de executar os bytes históricos, observar `READY` e comparar o estado resultante
com o estado nominal. O checkout histórico, sua associação commit/caminho/blob
e os bytes do arquivo são verificados antes dessa execução.

## Comportamento da candidata

Uma tabela ausente continua admitindo a criação inicial do ledger e dos seus
controles. Se a tabela já existe, a candidata exige os três pré-requisitos antes
de normalizar grants ou políticas:

| Desvio isolado na tabela existente | Diagnóstico obrigatório |
| --- | --- |
| RLS desabilitado | `UNEXPECTED_AUTOMATION_RLS_DISABLED` |
| RLS habilitado, sem `FORCE` | `UNEXPECTED_AUTOMATION_RLS_NOT_FORCED` |
| Política `tenant_scope` ausente, com as duas flags presentes | `UNEXPECTED_AUTOMATION_POLICY_MISSING` |

A criação inicial usa `CREATE TABLE` estrito. O caminho da relação existente
exige tabela ordinária, obtém `ACCESS EXCLUSIVE` com `LOCK TABLE ONLY` e confere
novamente o OID observado antes da espera. A transação declara `READ COMMITTED`
explicitamente para que a leitura de catálogo após o lock não dependa de um
nível de isolamento padrão mais restritivo. Estas decisões são verificáveis no
código; a matriz desta entrega não executa uma corrida entre sessões e não
estabelece garantia de atualização online ou de reaplicações concorrentes.

A comparação posterior da política mantém a referência temporária: comando,
modo permissivo, destinatários, `USING` e `WITH CHECK` devem coincidir. A
reaplicação de uma política alterada falha dentro da transação, preservando o
estado encontrado para diagnóstico.

As ACLs aceitam somente o owner e os direitos previstos da aplicação: `SELECT`
e `INSERT` na tabela, `UPDATE` nas colunas `state`, `attempts` e `due_at`, sem
opção de delegação. Grants para `PUBLIC`, outro papel ou uma coluna imutável
são recusados. A checagem final de direitos efetivos verifica todas as colunas
imutáveis e as operações adicionais de tabela, incluindo efeitos de memberships
herdados.

Uma instalação com grants intencionais diferentes continua exigindo revisão
própria; o script não os apaga nem declara compatibilidade por inferência.
Não há alteração no autorizador CORE, criação de papéis operacionais ou
ampliação de direitos de negócio.

## Evidência executável

O workflow `central-auto-incremental-migration.yml` mantém dois jobs independentes
de PostgreSQL 16, com os mesmos limites de tempo e permissões de leitura. O job
de atualização começa pelo schema histórico anterior ao ledger. Uma asserção
explícita exige que `to_regclass('public.crm_private_automation_runs')` seja nulo
antes da primeira aplicação. A prova existente insere sob RLS, recusa o tenant
estrangeiro, reaplica a migration e preserva o lead e a linha do ledger. Assim,
o caminho de criação inicial não é fabricado removendo controles de uma tabela
atual. O segundo job cria o schema atual em um PostgreSQL separado e aplica a
migration duas vezes, mantendo a compatibilidade da instalação nova.

O harness `scripts/ci/test_automation_incremental_drift.py` mantém as duas
contraprovas da #639: exclusão efetivamente permitida à aplicação e política
destinada ao owner que oculta seu próprio tenant. A exclusão é revertida dentro
da transação de teste; cada fixture retorna ao estado nominal antes de avançar.

A sequência adicional executa os três reparos históricos da versão `96db`
antes das três recusas da candidata. Cada fixture modifica somente uma condição.
A captura antes da tentativa deve ser idêntica ao estado nominal, salvo a flag
específica ou a ausência da política. Os reparos históricos precisam retornar
exatamente ao estado nominal observado. As recusas precisam apresentar o código
específico, não emitir `READY` e conservar exatamente a captura anterior.
A limpeza explícita da fixture, seguida da reaplicação nominal, ocorre em
`finally`; ela é responsabilidade do ensaio e não uma reparação feita pela
migration candidata.

Os 28 casos distintos de ACL/política e seus diagnósticos originais permanecem.
Eles mantêm RLS e `FORCE` habilitados durante a tentativa: retirar essas flags
como mutação acessória esconderia o erro de ACL ou política que cada caso deve
exercitar. Cada tentativa continua comparando registros, flags, políticas e ACLs
antes/depois, restaurando a fixture e repetindo a migration nominal. A etapa
final executa uma atualização permitida e verifica 13 recusas reais com o papel
restrito da aplicação: `DELETE`, `TRUNCATE` e `UPDATE` das 11 colunas imutáveis.
Também permanecem as recusas existentes por banco incorreto e divergências de
constraints, nullability, defaults e colunas.

A captura compara o owner e as flags/ACLs da tabela, ACLs de colunas, definições
de políticas e os registros sintéticos do ledger e de leads. Igualdade dessa
captura não é uma auditoria de todos os objetos do cluster. As contagens exigidas
são três reparos históricos, três recusas isoladas, 28 casos de ACL/política e
13 recusas de direitos efetivos. O resumo JSON
`CRM_INCREMENTAL_RLS_REAPPLY_DRIFT_PASS` inclui a HEAD real do checkout, run e
attempt do job, commit/blob histórico e `operational_apply=false`; ele só é
emitido após todas essas verificações. Os marcadores anteriores continuam
presentes para manter a leitura da evidência existente.

Os resultados efetivos ficam registrados na PR e nos logs da SHA candidata;
este documento não antecipa sucesso da nova CI. A verificação local desta
entrega limita-se à sintaxe Python/Bash, à estrutura YAML, aos bytes pinados e
à preservação das matrizes existentes. Ela não simula identidades de CI nem
substitui a execução em PostgreSQL.

## Limites preservados

Nenhuma migration desta entrega é aplicada à VPS. Atualização operacional ainda
depende de inventário real, backup e restauração aprovados, comparação de
schema/permissões, revisões independentes e janela autorizada. A migration
permanece transacional, sem `down` destrutivo e sem remoção dos registros existentes.
As provas de recusa cobrem falhas antes de `COMMIT`; perda de confirmação depois
de um commit efetivo não é uma garantia de rollback. Não há retry automático,
autorização de execução owner, teste humano no CRM ou restauração distribuída
adicionados por esta entrega.
