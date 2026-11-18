# Recuperação inerte dos rascunhos comerciais do owner

## Escopo

Este incremento acrescenta uma prova de recuperação à CI da integração privada
CRM → Agents. Usa exclusivamente a base fictícia criada pelo job da candidata,
o evento real da PR e os pins de Agents/Central já verificados pelo helper
`private_crm_owner_drafts_ci.py`. Não modifica o backup operacional, o runtime
comercial, os contratos IAM nem a política de recuperação de contas.

O script `scripts/ops/test-private-crm-owner-draft-recovery.py` importa somente os
helpers existentes; não chama seus programas principais nem simula variáveis de
um workflow antigo. O resultado efetivo pertence à SHA e ao log da execução.
Revisão de código e sintaxe local não equivalem à execução PostgreSQL/HTTP/Docker.

## Snapshot durante reconciliação real

O ensaio inicia uma nova sessão humana do owner A pelo login e MFA reais. Usa o
código original de recuperação de índice 1, ainda livre na fixture de cinco
códigos; o ensaio HTTP usa o índice 0 e o Chromium usa o índice 4. Através dos
formulários do produto cria um lead fictício e o avança para `qualified`, deixando
o motor CRM persistir seu próprio planejamento `followup_qualified` pendente.

A criação do rascunho usa o transporte mTLS original. Um atraso controlado no
gateway ocorre depois do commit do receiver: Agents contém o `DRAFT`, enquanto
o CRM conserva a intenção `pending`, sem confirmação. O script exige ambos os
estados observados e preservação de leads, tarefas, planejamentos e preferências.

Nesse ponto abre um único snapshot exportado PostgreSQL, pelo helper original
`private_crm_backup_snapshot.py`. Inventário, dados, owners, ACLs, privilégios por
coluna, FORCE RLS, expressões das policies e definições de constraints/colunas são
lidos no mesmo snapshot. Enquanto ele permanece aberto, um GET real e autorizado
reconcilia a intenção na fonte para `confirmed`, sem reenviar CREATE ou modificar
o rascunho remoto. A leitura no snapshot continua `pending` e mantém todos os
hashes. O `pg_dump -Fc` importa esse mesmo snapshot. O exportador é fechado antes
da criptografia e da restauração.

A comparação cobre as 25 tabelas da base original e as duas tabelas do addon,
`crm_private_owner_draft_grants` e `crm_private_owner_draft_intents`. O SHA-256 de
cada conjunto inclui todas as colunas e linhas, ordenadas de forma determinística;
contagens isoladas não bastam para aprovar o restauro. Também se exige preservar
por inteiro uma intenção previamente confirmada e a nova intenção pendente,
incluindo autoria histórica, referência, origem e projeção de negócio.

## Backup legado permanece fechado

O classificador `detect_profile` é chamado na fonte e na cópia restaurada com a
lista **original** de tabelas, extraída do schema base. Em ambos os casos deve
recusar precisamente o perfil de relações desconhecidas. A prova falha se o
classificador legado aceitar o addon ou negar por uma causa diferente.

Não se amplia sua allowlist, não se fornece a ele uma lista aumentada e não se
altera `scoped_counts`, `verify_restored_security` ou o comando de backup legado.
O inventário adicional existe apenas no novo ensaio CI. O helper de cluster
isolado é reutilizado com `BASE_ROLES`, sem novas roles ou permissões.

## Criptografia e destino inerte

O arquivo custom é cifrado e decifrado em memória pelo
`private-backup-crypt.php` original e pela chave independente já gerada para o
job. O script exige igualdade byte a byte do round-trip. A contraprova altera um
byte do ciphertext dentro de um envelope JSON/base64 ainda válido, conserva o MAC
antigo e exige rejeição sem plaintext na saída. Não se limita a fornecer base64
inválido ao decifrador.

O destino é um PostgreSQL 16 novo, sem rede, sem portas publicadas, com dados em
tmpfs e nome aleatório controlado pelo helper original. As cinco roles conhecidas
são criadas sem LOGIN, senha, herança, superuser, BYPASSRLS, criação de banco/role
ou replicação. Não se usa `--no-owner`: a comparação deve preservar ownership e
ACLs do dump. Nenhum processo CRM, worker, receiver, browser ou emissor é conectado
ao banco restaurado. O contêiner é removido ao final, inclusive em falha.

Os bytes históricos das tabelas IAM e de sessões participam da comparação das
27 tabelas. Isso verifica fidelidade do arquivo em um banco inerte; não restaura
autoridade vigente. Nenhuma sessão restaurada é reutilizada, nenhum cookie é
emitido e nenhuma role recebe credencial de login. Segredos de serviço, arquivos
de chave e certificados não são parte desse arquivo PostgreSQL.

Dump, plaintext, ciphertext e valores privados permanecem em memória. O resumo
publicável contém apenas contagens, hashes dos manifestos, identidade da candidata
e limites da prova. Não há artefato de dump/chave, corpo HTTP, credencial, SQL bruto
ou diagnóstico do driver com conteúdo de dados nos logs.

## Contraprovas de privilégio e isolamento

Após restaurar, o ensaio compara exatamente owners, grants, privilégios por coluna,
FORCE RLS, policies, constraints, definições de coluna e os 27 conjuntos de dados.
Verifica também que as roles continuam inertes, sem senha e sem memberships.

As consultas sob `SET LOCAL ROLE` e o contexto de tenant são sondas SQL do
administrador do laboratório, não autenticação humana. O contexto vazio deve ver
zero grants e intenções; A e B devem reproduzir seus conjuntos distintos da
fonte. Uma tentativa de inserir uma intenção de A sob o contexto de B deve falhar
por RLS com SQLSTATE `42501`.

Outras doze negativas reais exigem o mesmo SQLSTATE: alterações de negócio,
hash, autor, empresa e origem de uma intenção; exclusão da intenção; alteração do
grant pelo app; alteração de status, papel e empresa pelo IAM; leitura das
credenciais e sessões pelo app. Todos os conjuntos são novamente comparados após
as negativas para comprovar ausência de escrita parcial.

## Resultado e limites

Apenas a execução completa emite `CRM_OWNER_DRAFT_INERT_RECOVERY_PASS_NO_GO`.
O marcador exige observar `pending` no snapshot, `confirmed` na fonte viva e
`pending` na cópia restaurada, preservando o rascunho remoto original. Não se
antecipa PASS neste documento.

Este recorte não comprova recuperação distribuída CRM/Agents, atualidade de
grants/revogações depois do snapshot, religamento de serviço ou recuperação de
credenciais. Reconciliação operacional após restauração exigiria tratar esses
estados atuais de forma própria. O backup legado continua recusando o addon.
Permanecem DRAFT, NO-MERGE e NO-DEPLOY, com dados exclusivamente fictícios.
