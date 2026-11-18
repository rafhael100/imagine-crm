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
FORCE RLS, policies, definições de coluna e os 27 conjuntos de dados. A comparação
das constraints admite exclusivamente os pares de representação descritos abaixo;
todos os seus demais campos continuam exatos.
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

## Quinze representações observadas no PostgreSQL 16

A [execução de diagnóstico 44235805157](https://github.com/rafhael100/imagine-crm/actions/runs/44235805157),
job `134571533998`, HEAD `f79ac6704679683113ef0c67c3177f9ad83eb421`,
passou novamente a jornada HTTP (407 verificações, 52 requisições) e recusou a
recuperação na comparação textual do catálogo. O diagnóstico mascarado identificou
15 diferenças, todas apenas no campo `definition` de CHECKs sobre `varchar`.
As colunas e os demais metadados não divergiram; os hashes dos tokens literais
foram iguais em cada par. O restante da prova de recuperação e o Chromium foram
pulados nessa execução. Esse diagnóstico não é um PASS de recuperação.

O ciclo de dump/restauro mudou o cast aplicado ao array para casts aplicados aos
elementos, com as duas formas observadas abaixo:

```sql
-- Forma da fonte; LITERAL representa cada valor mascarado.
CHECK (((campo)::text = ANY ((ARRAY['LITERAL'::character varying, ...])::text[])))
-- Forma restaurada.
CHECK (((campo)::text = ANY (ARRAY[('LITERAL'::character varying)::text, ...])))
```

A regra `CRM_PG16_VARCHAR_LITERAL_ARRAY_CAST_V1` lista explicitamente as quinze
identidades de constraints, suas colunas, tipos, valores e ordem do schema:

| Tabela | CHECKs admitidos pelo par observado | Tipo da coluna |
| --- | --- | --- |
| `crm_memberships` | `status` | `varchar(16)` |
| `crm_private_accounts` | `desired_plan`, `role`, `status`, `team_role` | `varchar(16)` |
| `crm_private_automation_runs` | `state`; `template` | `varchar(16)`; `varchar(32)` |
| `crm_private_invitations` | `state` | `varchar(16)` |
| `crm_private_leads` | `stage` | `varchar(16)` |
| `crm_private_operator_events` | `action` | `varchar(32)` |
| `crm_private_orders` | `payment_method`, `plan`, `state` | `varchar(16)` |
| `crm_private_preferences` | `language` | `varchar(2)` |
| `crm_private_tasks` | `state` | `varchar(16)` |

O comparador exige a forma completa da fonte e a forma completa do destino para
cada identidade, com os valores literais e sua ordem fixados no código. Não remove
casts ou parênteses, não aceita aliases SQL alternativos e não reescreve SQL
genérico. Constraints não listadas permanecem sob igualdade exata. As listas
ordenadas originais são comparadas integralmente, preservando entradas, ordem e
eventuais duplicatas, sem colapsá-las em mapas.

Cada coluna elegível deve conservar seu tipo exato, NOT NULL e collation
`pg_catalog.default`. Todas as definições de coluna são comparadas integralmente.
Como a collation default depende do banco, o catálogo também inclui e compara
exatamente encoding, provedor de locale, `datcollate`, `datctype`, locale ICU,
regras ICU (`daticurules`) e versão da collation de
[`pg_database` no PostgreSQL 16](https://www.postgresql.org/docs/16/catalog-pg-database.html).
Nenhum owner, senha ou parâmetro de conexão é lido por essa projeção.

O ensaio aplica contraprovas de mutação aos próprios catálogos reais capturados,
sem alterar os bancos. Muda um literal, a ordem dos literais, o operador, a forma
SQL, tipo/collation de coluna, collation e regras ICU do banco, flags, identidade do objeto,
campo ou constraint não relacionados; remove, duplica ou reordena uma constraint.
Quando há equivalência, também altera igualmente nos dois lados literal, tipo e
collation: igualdade entre as mutações não pode ampliar a lista aprovada. O
comparador precisa recusar cada uma. Os hashes dos catálogos originais devem
permanecer intactos após todas as mutações em memória.

O resultado registra separadamente os hashes **brutos** dos catálogos fonte e
restaurado, `catalog_raw_equal`, a regra e o número de equivalências. Quando esse
par de representações ocorre, os hashes brutos diferem; não se declara igualdade
byte a byte das definições. Dados e demais metadados continuam exigindo igualdade
exata. A regra de equivalência não altera schema, grant ou runtime.

## Vínculo entre segurança e catálogo na integração do monitor

O helper compartilhado também passou a incluir constraints e triggers no manifesto
de segurança. As definições de constraints usam `pg_get_constraintdef(...,false)`,
a mesma representação canônica do catálogo owner. Essa opção de formatação não
reescreve SQL nem amplia os pares de equivalência aceitos.

Antes de aplicar a regra dos quinze pares, o ensaio exige, em cada banco, que a
lista de constraints do manifesto de segurança seja exatamente a projeção da
lista do respectivo catálogo sobre `table`, `name`, `type`, `validated` e
`definition`. Entradas, ordem, duplicatas e todos esses campos participam do
vínculo. Depois da comparação integral do catálogo, somente essa chave já
verificada pode ser tratada como equivalente entre os manifestos. Todos os
outros campos, chaves e listas de segurança continuam sob igualdade exata,
incluindo triggers, owners, ACLs e metadados das funções.

Três negativas adicionais usam cópias dos manifestos reais, sem alterar o banco:
uma definição modificada somente no manifesto de segurança; a mesma definição
modificada no manifesto e no catálogo, fora da regra fechada; e um metadado de
segurança distinto. A segunda fixture precisa primeiro satisfazer o vínculo
exato, para que sua recusa comprove a aplicação da regra de catálogo. Os hashes
das quatro estruturas originais permanecem intactos.

`security_manifest_sha256` continua sendo o hash **bruto da fonte**.
`restored_security_manifest_sha256` registra o hash bruto restaurado, e
`security_raw_equal` informa a igualdade bruta observada. Esses hashes podem
diferir pelos mesmos pares permitidos de constraints; o resultado não declara
igualdade bruta quando houve apenas equivalência verificada. Também registra
`security_catalog_bindings_exact` e `security_mutation_denials`.

Esta adaptação preserva o perfil owner de 27 tabelas e a recusa do addon pelo
backup legado. O perfil monitor isolado tem 33 tabelas e uma sequence. A soma
dos dois addons no mesmo banco teria 35 tabelas e uma sequence, e continua fora
deste contrato: exige uma prova sintética própria de convivência e recuperação,
sem reutilizar os resultados dos dois perfis isolados como prova da combinação.

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
