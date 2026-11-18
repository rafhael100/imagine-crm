# Inspeção do destino privado para rascunhos owner

## Objetivo e autorização

O fluxo de owner criar/consultar foi aprovado e verificado na CRM #658
(`e390c56af2a544d8191cce86752637efe6a35e43`) contra o P67 original
(`0c722eee6bd53a7ee46c4ba68ab70200fc191b78`). A continuidade agora inclui
inspecionar a homologação privada existente e preparar a instalação específica
do addon. Esta entrega produz observações do destino; não instala, ativa,
migra, publica, troca imagens, altera grants ou autoriza deploy.

O código é `scripts/ops/inspect-private-crm-owner-drafts.py`, um módulo Python
stdlib independente. A base documental é a #661
(`dc601be113b213dfd35fae61d00ff710b4f286e1`). Nenhum instalador, preflight,
schema, workflow histórico ou contrato de backup é ampliado pelo coletor.

## Alvo padrão e execução

O CLI não aceita argumentos nem substituição de caminhos, projeto ou SQL.
Seus alvos vêm dos fontes existentes:

| Elemento | Identidade registrada |
| --- | --- |
| Checkout | `/srv/imagine/crm-private-candidate` |
| Estado protegido | `/srv/imagine/private-crm-journey` |
| Projeto Docker Compose | `imagine-crm-private-journey` |
| Banco CRM | `imagine_crm_private_homologation` |
| Configuração CRM | `/etc/nginx/sites-available/imagine-crm-private-journey` |
| Configuração compartilhada | `/etc/nginx/sites-available/imagine-dev` |
| Link de ingresso CRM | `/etc/nginx/sites-enabled/imagine-crm-private-journey` |

Essas identidades delimitam a inspeção; não afirmam que os recursos estejam
presentes ou configurados assim neste momento. As portas são lidas dos
containers observados. A porta 18086 documentada na base não é presumida livre
nem atribuída ao destino sem observação.

Um operador autorizado pode fornecer os bytes do script da SHA revisada por
stdin a `python3 -B -`. Isso evita instalar arquivos ou gerar `__pycache__` no
alvo. Não importar nem executar código do checkout da VPS para fazer a coleta.
Nenhum segredo é argumento, saída ou requisito de colagem no chat. A sessão
de acesso ao servidor e a revisão da fonte são verificações distintas.

## Observações produzidas

O JSON usa `CRM_OWNER_PRIVATE_TARGET_OBSERVATION_V1`. Cada seção informa
`OBSERVED`, `NOT_OBSERVED` ou `UNKNOWN`; uma falha não se transforma em ausência
nem em inventário vazio seguro.

- **Fonte:** HEAD Git e resultado resumido de mudanças rastreadas. A opção
  `core.worktree` não pode desviar a raiz: `--show-toplevel` deve corresponder
  ao checkout esperado antes de consultar mudanças. Configuração
  global/system, fsmonitor, hooks e locks opcionais são desativados. Antes de
  consultar status, somente os nomes das opções locais são examinados; filtros
  `clean/process`, includes ou `extensions.worktreeConfig` tornam a fonte
  `UNKNOWN` antes da consulta de status, sem executar filtros nem emitir
  valores de configuração.
  Nomes de arquivos alterados e valores de configuração não são emitidos.
- **Caminhos protegidos:** somente tipo, UID/GID e modo via metadados. Não são
  abertos `runtime.env`, cofres de acesso, fixtures, chaves ou o marcador de
  publicação. Symlinks nos diretórios intermediários não são seguidos.
- **Ingresso:** hashes dos dois arquivos conhecidos e correspondência do link
  esperado. Não há HTTP, DNS, Nginx reload/test, leitura de páginas ou login.
- **Containers:** seleção pelo projeto exato no socket Docker local fixo.
  `--config /proc/self/fd` impede a busca de credenciais/contextos no diretório
  do usuário; o diretório do kernel não contém nem permite criar `config.json`.
  Labels projetados limitam-se a projeto/serviço. IDs reais de imagem ficam
  separados dos tags; coincidência de tag com HEAD não prova a origem da imagem.
  Portas observadas, redes conhecidas e mounts são projetados. Nomes/caminhos
  desconhecidos não são refletidos; os campos ficam não reconhecidos.
- **PostgreSQL:** consulta no container `postgres` corrente, com projeto,
  serviço e família de imagem conferidos novamente. O processo `psql` não
  herda variáveis de autenticação, fixa `PGPASSFILE`/`PGSERVICEFILE` em
  `/dev/null` e usa o socket PostgreSQL local explícito. Falha de autenticação
  permanece `UNKNOWN`, sem procurar outra credencial.
  Uma transação `REPEATABLE READ READ ONLY`, com timeouts constantes e
  `search_path=pg_catalog`, observa exclusivamente os catálogos.

O campo `installation.structure_matches` informa se o catálogo identifica a
tabela heap ordinária esperada, sem herança ou RLS, com owner e dois tipos
nativos exatos. **O valor do marcador não é lido**: `installation.mode` fica
`NOT_READ`. Isso evita executar uma policy ou view substituída concorrente e
não atesta que o modo persistido seja fictício. Nenhuma função de aplicação,
linha de IAM/sessão/negócio/instalação, chave, grant comercial, nonce, senha de
role ou definição de função SQL é lida.

O inventário reconhece os nomes/tipos exatos de base com 25 tabelas, owner com
27 tabelas e base/S2S com 30 tabelas e uma sequência. Outras combinações ficam
`UNRECOGNIZED`. O campo `profile_verified=false` é permanente: contar relações
e observar roles/RLS não substitui a comparação completa de ACLs, constraints,
funções, autoria, backup, autoridade atual ou recuperação do respectivo recorte.

## Limites e interpretação

`deploy_approved=false`, `mutations_executed=false` e
`consistent_snapshot_verified=false` são invariantes. A coleta não é uma
transação entre filesystem, Git, Docker e banco. Uma transação PostgreSQL
consistente não prova que as outras fontes ficaram imóveis; a evidência deve
ser recolhida novamente antes de uma futura operação, sob o procedimento
específico que vier a ser aprovado.

O coletor não cria lockfile, diretório, arquivo, marcador ou permissão. Leituras
normais do sistema e SELECTs podem produzir efeitos operacionais como atime,
estatísticas/logs e locks de leitura; não há escrita de dados de aplicação,
schema, configuração ou estado de implantação pelo coletor.

Cada processo tem tempo e limite agregado de stdout+stderr, sem stderr bruto
na saída. O orçamento de processos da coleta é 45 segundos; o JSON final tem
limite de 64 KiB. Excesso, timeout, leitura negada ou formato inesperado resulta
em código fechado. Ausência de observação não demonstra ausência de VPS, R2,
MFA, credenciais ou outro recurso. Hashes/marcadores observados tampouco são
assinatura de autorização.

## Verificação desta entrega

Os testes usam `Target(..., scope='ci-fixture')` pela API Python com diretórios
e projeto descartáveis declarados. Esse caminho não exige nem simula evento
GitHub, não altera o CLI de destino e não reclassifica a fixture como VPS.

As verificações locais devem cobrir processos reais limitados, ocultação de
erros/segredos, symlinks, índice Git intacto, fsmonitor e filtros canário não
executados, seleção de projeto/serviço, fonte/tag/imagem distintos e tratamento
de observações desconhecidas. A CI própria acrescenta PostgreSQL 16 real com
os fontes originais de schema/roles e addon, confirmando catálogo base/owner,
negação de escrita e preservação dos dados. Resultados pertencem à SHA e à
execução registradas na PR; este documento não antecipa PASS.

Referências de interfaces: [Docker inspect](https://docs.docker.com/reference/cli/docker/inspect/),
[Git](https://git-scm.com/docs/git),
[transações PostgreSQL 16](https://www.postgresql.org/docs/16/sql-set-transaction.html).

## Continuidade após a coleta

Os resultados permitem fixar a revisão e o perfil realmente presentes para
preparar a mudança específica do addon. O F252 `plan` não é usado: ele abre um
lockfile, e sua política só cobre a manutenção anterior. O instalador base
chama o backup legado, que recusa corretamente o addon. O runner owner da CI
usa certificados/grants temporários e fixtures de falha, portanto não é um
instalador persistente. Esses limites ficam preservados.

O próximo procedimento precisa tratar a instalação correspondente ao estado
observado, os dados persistentes e a fonte vigente de revogações. A inspeção
não reabre a decisão de acesso do owner, não presume infraestrutura ausente e
não declara recuperação operacional distribuída CRM/Agents.
