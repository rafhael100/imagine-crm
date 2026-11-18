# Validação de data de tarefa compatível com PostgreSQL

Data: 18/11/2026. Candidata de desenvolvimento sintético,
**DRAFT / NO-MERGE / NO-DEPLOY**.

## Divergência concreta

A rota `/tarefas` exige `date_format:Y-m-d`. O `Workspace::createTask`
revalidava a entrada com `DateTimeImmutable::createFromFormat('!Y-m-d', ...)`
e igualdade da formatação. Essas verificações de PHP admitem `0000-01-01`,
mas a coluna `crm_private_tasks.due_on`, do tipo PostgreSQL `date`, não admite
essa representação de ano AD.

O [manual de datas do PHP](https://www.php.net/manual/en/book.datetime.php)
descreve o calendário ISO que inclui o ano zero. O
[manual de entrada de datas do PostgreSQL16](https://www.postgresql.org/docs/16/datetime-input-rules.html)
distingue a convenção de entrada sem ano zero e os anos AD de 1 a 99 com
quatro dígitos. A dependência Laravel está fixada no `composer.lock` em
`v13.40.0`, commit `1449bf241017087fbd38c118ecaabef3f6797fdc`; seu
[validateDateFormat](https://github.com/laravel/framework/blob/1449bf241017087fbd38c118ecaabef3f6797fdc/src/Illuminate/Validation/Concerns/ValidatesAttributes.php)
usa parsing e comparação da formatação.

Pelo caminho examinado, o valor zero passaria as verificações de formato e
chegaria ao INSERT, onde ocorreria uma exceção SQL. O handler privado
transforma falhas de banco em 503. **Esse resultado anterior é uma inferência
das fontes, não uma observação de uma solicitação HTTP histórica.** O ensaio
desta candidata observa separadamente as primitivas PHP/PG e a recusa HTTP
atual, sem construir ou executar outra aplicação histórica.

## Alteração mínima

O `Workspace::createTask` acrescenta a condição de ano positivo depois do
parsing e da igualdade da data, antes de `run('tasks.create', ...)`.
Permanecem a validação do UUID e título, a presença do lead na própria
empresa, a quota de tarefas, autorização, transação, estado, versão e auditoria.
O Controller, as rotas, o schema e os dados de fixtures ficam preservados.

Esta condição trata a representação de entrada do tipo SQL já usado. As
regras sobre prazos, datas passadas ou futuras permanecem como estavam;
a candidata não introduz uma regra de vencimento relativa ao dia atual.

## Fonte e linhagem

A base imediata é a [#665](https://github.com/rafhael100/imagine-crm/pull/665),
commit `c073cfeb8d29fc6cd0a9f33642ccc1275b8b6f59`, árvore
`83a2c474f4dbd715f8d533117b2391b419d9561b`. O Workspace anterior tem blob
`d758424978c5832617d9c7a569b86b98e434f026`.

A branch `fix/crm-private-task-date-validation-2026-11-18` usa a base DEV
`d7c7aacc766c7ade4c65dcd211ed4569442144a9`, ref
`dev/fast-track-crm-2026-11-04`. O guard owner admite somente a nova tupla
completa, exige também a ancestralidade da #665 e conserva os seis contextos
anteriores. São oito ancestrais exigidos; a regra é ancestralidade, não
igualdade com o pai imediato de todo commit futuro da branch.

## Provas no runtime da candidata

O novo recorte do harness usa o mesmo ambiente descartável da jornada:

| Camada | Observação exigida | Limite da conclusão |
| --- | --- | --- |
| PHP do container app | PHP 8.4; `Factory`/`Translator` Laravel e `DateTimeImmutable` aceitam `0000-01-01` pelos critérios de formato anteriores | Apenas autoload e primitivas; sem bootstrap da aplicação, identidade, conexão ou Workspace histórico |
| PostgreSQL do ambiente | PG16; transação `READ ONLY`; `pg_input_is_valid` recusa ano zero e `pg_input_error_info` informa SQLSTATE 22008; ano 1 é entrada válida | Consulta de tipo, sem INSERT, e com ROLLBACK; não prova criação HTTP do ano 1 |
| Rota `/tarefas` atual | Lead próprio válido, título fictício válido e ano zero produzem 422 | Execução da candidata; não execução do handler histórico |
| Estado depois da recusa | Igualdade das linhas completas de leads, tasks, events, preferences, orders e automation_runs das duas empresas | Preservação de dados de negócio e auditoria; não afirma imutabilidade de metadados de sessão |

A saída dos processos é capturada e verificada por campos e tipos fechados;
não é repassada como diagnóstico bruto. A versão PHP e o número de versão
PG observados são incluídos somente no marcador saneado depois do PASS.
A consulta SQL exige `transaction_read_only=on` e termina em ROLLBACK.

O teste ocorre depois de a tarefa nominal `TASK_TESTE_MANUAL` já ter sido
persistida e concluída. Essa precondição confirma que o caminho positivo
original foi exercitado. Não se acrescenta outra tarefa positiva ao roteiro
nem se altera a quantidade de fixtures. O ano 1 é verificado somente como
entrada válida do PostgreSQL; a tarefa nominal mantém seu vencimento original.

## Validação e aceitação

Vinte testes locais de admissão passaram. A matriz possui sete tuplas
válidas entre 28 combinações e recusa as 21 combinações inválidas antes de
qualquer consulta Git; também recusa a ausência de qualquer um dos oito
ancestrais. Os contextos e testes de fronteira de sessão anteriores permanecem.

A revisão local confere o pequeno delta de validação, o harness, as
precondições, AST Python, preimages, modos e reconstrução do patch. Os
novos ensaios PHP 8.4, PG16 e HTTP devem passar na CI da HEAD publicada,
junto dos regressivos herdados. Os resultados terminais serão registrados
na PR; a validação da base não é atribuída automaticamente à candidata.

F251/F252 permanecem intactos e continuam decidindo sobre seu próprio
escopo. Nenhum resultado de laboratório concede acesso a clientes,
instalação, merge, deploy ou aceite operacional.
