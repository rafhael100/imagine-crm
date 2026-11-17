# Backup privado: restauração isolada com owners preservados

Desenvolvimento e CI exclusivamente sintéticos. DRAFT / NO-MERGE / NO-DEPLOY.
Nenhum acesso à VPS ou aplicação operacional de migrations integra este recorte.

## Falha corrigida

O backup anterior recriava no servidor de restauração somente cinco papéis do CRM
base. Depois da instalação S2S, o dump também inclui ACLs que referenciam
`crm_auto_reader_owner`, `crm_auto_transport` e `crm_auto_sender`; o restore em
outro cluster falhava porque esses papéis não existiam.

Adicionar os papéis sem alterar o comando também seria insuficiente. A opção
`pg_restore --no-owner` transferia os objetos restaurados ao superusuário de
restauração, inclusive as funções `SECURITY DEFINER` que dependem de um owner
restrito e sujeito a RLS. As provas anteriores P19/P21 restauravam em outro banco
do mesmo cluster e, por isso, não cobriam essa combinação de falhas.
O comportamento de `--no-owner` consta na
[documentação do PostgreSQL 16](https://www.postgresql.org/docs/16/app-pgrestore.html);
o owner inicial `pg_database_owner` do schema `public` é descrito nos
[papéis predefinidos](https://www.postgresql.org/docs/16/predefined-roles.html).

## Implementação

`private_crm_backup_contract.py` reconhece dois perfis fechados: CRM base e CRM com
S2S completo. O segundo exige as cinco tabelas de serviço e o conjunto conhecido
de funções; aceita explicitamente a variante com `receipt_candidate`. Schemas,
relações ou funções inesperadas, instalação S2S parcial, papéis não reconhecidos
nos grants, privilégios elevados nos papéis restritos e memberships não revisadas
bloqueiam a criação de um marcador de backup verificado.

O fluxo mantém a criptografia autenticada e a rejeição de adulteração. Na
restauração, recria somente os papéis previstos, todos sem senha, LOGIN,
SUPERUSER, BYPASSRLS, CREATEDB, CREATEROLE ou herança. `pg_restore` preserva os
owners; não copia credenciais nem membros de papéis da origem.

O servidor de restauração continua sendo um contêiner novo, descartável, com
`network=none` e dados em tmpfs. Ele usa o nome de banco exigido pelos contratos
S2S **dentro desse cluster separado**, permitindo testar as funções com suas
verificações de instalação. Não há alteração do banco ou conexão de origem.

A comparação entre origem e destino usa nomes de papéis, e não OIDs, que diferem
entre clusters. Ela cobre owners, ACLs de schemas/tabelas/colunas/funções,
definições e configuração das funções, políticas e flags RLS. O papel da aplicação
e o owner restrito S2S são exercitados sem tenant e nos tenants sintéticos da
amostra. A ausência de contexto deve ocultar todos os registros tenant-scoped.

O manifesto de arquivo passa a ter versão 2, mantendo as chaves de contagem das
tabelas base e acrescentando as tabelas S2S qualificadas por schema. Registra o
perfil, a presença da consulta de recibo, a comparação de owners, a quantidade de
funções verificadas e o digest do contrato de segurança. Nenhum dump em claro,
senha ou definição de função é gravado nesse manifesto.

## Consistência durante escritas concorrentes

`private_crm_backup_snapshot.py` abre uma sessão dedicada em `REPEATABLE READ READ
ONLY` e obtém um snapshot real por `pg_export_snapshot()`. Inventário, contagens,
amostra de tenants e provas de escopo importam exatamente esse snapshot em
transações read-only próprias. O `pg_dump` recebe o mesmo identificador por
`--snapshot`, enquanto a sessão exportadora continua aberta. O helper de escopos
possui modo explícito sem controle de transação para esses importadores; não há
`BEGIN` aninhado ou troca silenciosa do snapshot.

A janela de captura é limitada a 120 segundos, com limites adicionais para
inicialização, statements e espera de locks. O exportador é encerrado e sua saída
confirmada antes de começar a criptografia ou restauração. Exportador, cada
importador e `pg_dump` recebem marcadores `PGAPPNAME` próprios, registrados antes
da conexão. Um timeout do cliente Docker não é tratado como prova de saída do
backend: o cleanup encerra o grupo local de processos, consulta a identidade real
no PostgreSQL e, se necessário, termina somente os backends próprios por PID,
início da sessão, banco, owner e marcador aleatório. Confirma sua ausência depois.
As consultas de controle também são identificadas e têm limites de conexão e
statement inferiores ao timeout do cliente. Falha antes de receber metadata do
exportador usa seu marcador registrado previamente para descobrir e encerrar a
sessão; não depende apenas de EOF ou do timeout de idle.

O manifesto registra `consistent_source_snapshot=true`, protocolo e instante UTC
de exportação. Não guarda o identificador efêmero como se fosse um snapshot
reutilizável depois do encerramento. O mecanismo segue os contratos de
[exportação/importação de snapshots](https://www.postgresql.org/docs/16/functions-admin.html#FUNCTIONS-SNAPSHOT-SYNCHRONIZATION)
e de [pg_dump --snapshot](https://www.postgresql.org/docs/16/app-pgdump.html).

## Regressão executável

O workflow `ci-private-crm-backup-restore.yml` inicia somente o PostgreSQL de
origem da stack descartável e constrói a imagem usada pela ferramenta de
criptografia. Não inicia web, ingress ou aplicação HTTP. O harness:

- Cria registros sintéticos em dois tenants e executa o script de backup real no
  perfil base, incluindo criptografia, adulteração e restore em cluster separado.
- Instala S2S somente nessa origem CI, gera nonces/auditoria e outboxes pendente e
  aceita pelos papéis restritos e repete o backup real.
- Recusa schemas desconhecidos e uma instalação S2S incompleta.
- Reproduz em clones independentes a falha por papéis ausentes e a transferência
  indevida de owner causada por `--no-owner`; exige rejeição pelo novo verificador.
- Restaura corretamente em outro cluster sem rede e executa `read_state`, a
  negativa por grant revogado, leitura da confirmação durável e, quando presente,
  consulta da outbox pendente. Na fixture P22 atual, exige as quatro funções e a
  presença da reconciliação; um teste não pode passar por aceitar silenciosamente
  o perfil legado. Também confirma a outbox pendente por `ack_outbound`, observa
  estado/hash aceitos e comprova a volta ao recibo anterior após o rollback.
- Verifica que transportes e aplicação continuam sem acesso direto às tabelas de
  serviço e que as contagens da origem permanecem inalteradas.

Uma etapa adicional inicia um escritor em outra conexão **depois** da exportação:
ele insere um lead e altera um título com commit real. As contagens, o título lido
pelo snapshot e o arquivo restaurado devem permanecer no estado anterior, enquanto
a conexão live continua vendo as escritas novas. Essa prova inclui RLS e o
contrato completo S2S no clone. Uma contraprova executa também `pg_dump` sem
importar o snapshot e restaura esse arquivo em outro clone: o lead novo deve
aparecer, reproduzindo a divergência entre manifesto anterior e dump posterior.
Outros casos verificam a saída do backend em conclusão normal, exceção do
chamador, perda de metadata, escrita recusada com SQLSTATE `25006` e perda do
exportador. Importação do token real depois do encerramento deve falhar; nenhum
snapshot é inventado pelo teste.

Duas contraprovas adicionais observam o backend real de `pg_dump` esperando um
lock curto de fixture e um importador executando `pg_sleep`. Só depois dessa
observação o harness encurta seu orçamento local, exigindo que o timeout do cliente
ocorra antes dos limites nativos de lock/statement. O backend da operação deve
desaparecer, enquanto uma sessão sentinela de outro marcador continua viva. O
exportador também deve permanecer vivo até o fechamento do contexto. O lock de
escrita existe somente nessa contraprova CI; o fluxo de backup não o adquire.

O resultado efetivo depende da CI na SHA candidata e será registrado na PR;
este documento não antecipa aprovação. O ambiente local permite compilação
Python e validação YAML, mas não dispõe de PostgreSQL ou Docker.

## Limites

Este é um ensaio de restauração verificável, sem ativação das identidades
restauradas ou reintegração operacional. Chaves de criptografia, retenção,
armazenamento externo, restore autorizado de cliente e plano de desastre continuam
dependências próprias. A captura agora aceita escritas concorrentes nas tabelas
transacionais. Mudanças concorrentes de DDL/permissões, indisponibilidade e estouro
dos limites continuam podendo impedir o backup, sem gerar marcador de sucesso.
O snapshot não congela contadores de sequences, que não são transacionais; a cópia
preserva seus owners/grants e pode conter um contador adiantado em relação às
linhas arquivadas.
