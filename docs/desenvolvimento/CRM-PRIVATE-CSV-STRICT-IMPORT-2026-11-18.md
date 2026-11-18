# Importação CSV estrita na jornada privada

Data: 18/11/2026. Candidata de desenvolvimento sintético, **DRAFT /
NO-MERGE / NO-DEPLOY**.

## Falha tratada e recorte

A rota autenticada `/importar` usava `fgetcsv` para transformar o arquivo
recebido em linhas. A validação posterior conferia os cabeçalhos, quantidade,
título fictício, valor e estágio, mas não exigia que a sintaxe de aspas do
arquivo fosse estrita. Uma leitura permissiva pode produzir valores válidos
para essas regras mesmo quando a representação CSV está malformada.

O projeto já contém `PortableCsvTableCodec`, um leitor limitado em memória
que recusa aspas abertas, texto após aspas de fechamento, aspas em campo não
citado, NUL, UTF-8 inválido, BOM inicial e terminadores de registros
inconsistentes. Esta candidata reutiliza esse código na rota existente,
antes de entregar as linhas ao `Workspace`.

A mudança mantém o desenho da jornada privada e seus dados fictícios. A
Central de backup e portabilidade IN-118 tem uma proposta própria na
[#434](https://github.com/rafhael100/imagine-crm/pull/434); este lote não
ratifica suas decisões nem cria um novo produto de importação ou restore.

## Contrato de entrada e persistência

| Elemento | Regra preservada ou explicitada |
| --- | --- |
| Arquivo | Upload válido, no máximo 20 KiB; leitura limitada antes do decode |
| Delimitador | Vírgula explícita; sem inferência de dialeto |
| Codificação | UTF-8 válido, sem NUL nem BOM inicial |
| Célula | Até 4.096 bytes, limite estrutural do codec reutilizado |
| Aspas | Campos podem ser citados; aspas internas são duplicadas; fechamento obrigatório, sem caracteres extras após ele |
| Registros | LF ou CRLF consistente no arquivo; último registro pode terminar no fim do arquivo |
| Cabeçalho de duas colunas | Exatamente `title,value_cents`; estágio `new` |
| Cabeçalho de três colunas | Exatamente `title,stage,value_cents`; estágio validado pelo Workspace |
| Quantidade | 1–20 linhas de dados, com limite existente de 20 leads no trial |
| Título | Identificador fictício `LEAD_TESTE_` seguido do sufixo aceito pelo Workspace |
| Valor e estágio | Mesmos limites e conjunto de estágios da implementação anterior |
| Autoridade e escrita | Mesma sessão/membership, ação `leads.create`, escopo de empresa e transação |
| Erros de estrutura | Recusa antes de chamar `createLeads`; sem texto do arquivo na resposta |

A nova leitura restringe a representação aceita, incluindo o limite de
4.096 bytes por célula. Não se afirma equivalência com todo texto tolerado
anteriormente por `fgetcsv`; por exemplo, um campo numérico citado com espaços
excessivos pode passar a ser recusado. Os valores de negócio permitidos e o
CSV exportado continuam compatíveis. A leitura não infere cabeçalhos,
normaliza dados ou deduplica registros.
Os erros de campos de negócio continuam seguindo o comportamento anterior
no `Workspace`, incluindo a validação de todas as linhas antes da escrita.
A auditoria de uma importação válida continua `lead.imported`, e o formato
do export não muda. O arquivo exportado deve continuar sendo reimportável
com seus estágios preservados.

A autorização de criação continua no `Workspace`. Conseguir decodificar um
CSV não concede permissão de importação. Os middlewares, rotas, grants,
RLS, cadastro, quota e esquema SQL não são alterados por esta correção.

## Fonte histórica e prova delimitada

Pai da candidata:
[#664](https://github.com/rafhael100/imagine-crm/pull/664),
commit `7e1b00a0c173bcfcac3a9051ef6cad3aededcdb8`, árvore
`1ca57c0fa3197e83a36d9c6024bf13e77f10ad23`.

| Fonte | Blob Git |
| --- | --- |
| Controller histórico completo | `380e8437749f050ee24b9be77b0eacb3241626d8` |
| Codec reutilizado sem alteração | `7afc2ebed85f3f668ce0d5e2ea2767efd1e778f7` |
| Workspace preservado | `d758424978c5832617d9c7a569b86b98e434f026` |

O workflow usa um segundo checkout sparse, fixado nessa HEAD, para obter
`app/Homologation/Controller.php`. Confere HEAD, árvore, modo `100644` e blob
antes do teste. O teste exige o SHA-256 dos bytes históricos:
`f59471983a992f4b50c2461a2dcc77f033a1148bac32505acde116a1c7380803`.

`tests/Isolated/PrivateCsvHistoricalParserTest.php` é chamado explicitamente
no job PHPUnit da jornada. Fica fora das suítes padrão e não ignora sua
execução quando o checkout está ausente. Exige PHP 8.4 e arquivo regular sem
symlink; carrega o Controller histórico completo em processo separado, sem
estado global herdado. Usa `Request`, upload e validação Laravel, substituindo
somente o destino `Workspace` por um capturador de linhas.

Essa contraprova observa o resultado do parsing histórico e seu encaminhamento
com `historicalImport=true`. Não executa o `Workspace` histórico, não passa
pela cadeia HTTP histórica de autorização e não afirma gravação no banco
pela versão anterior. A prova de recusa e ausência de alterações da candidata
é feita separadamente pela rota HTTP real.

## Verificação da candidata

O harness da jornada conserva os cenários existentes e acrescenta entradas
CSV malformadas como bytes, para cobrir também erros de codificação. A
recusa deve produzir o status esperado e preservar o snapshot de dados da
empresa solicitante e da empresa independente. A comparação cobre as linhas
completas de `leads`, `tasks`, `events`, `preferences`, `orders` e
`automation_runs` das duas organizações, além dos respectivos exports;
não se limita à quantidade de leads. O caso com 20 linhas válidas deve chegar
à quota existente e receber 409 na conta já povoada; não declara importação
bem-sucedida de 20 linhas numa conta vazia. O caso com 21 linhas deve receber
422 antes da escrita.

O positivo de duas colunas usa CRLF e campos citados, reutilizando exatamente
os dois leads do caso nominal anterior, com valores e estágio `new`
conferidos. O round trip do export de três colunas continua em LF, mantendo
estágios e as duplicatas previstas no ensaio original. A negação atômica de
um lote com linha de negócio inválida permanece e passa a conferir também
o snapshot das duas empresas. Nenhum desses casos acrescenta leads líquidos
ao roteiro anterior nem altera sua prova de quota.

O workflow conserva integralmente o job HTTP e os comandos anteriores de
`PrivateAutomationSettingsTest` e `PrivateUiTest`. Acrescenta somente o
checkout e a prova histórica no job PHPUnit, além dos filtros de paths do
codec e do teste isolado para manter essa dependência coberta em mudanças
futuras. O teste de parsing histórico não substitui a CI de HTTP/PG/browser.

O guard owner passa a admitir a sexta tupla fechada:
base DEV `d7c7aacc766c7ade4c65dcd211ed4569442144a9`, ref
`dev/fast-track-crm-2026-11-04`, head ref
`fix/crm-private-csv-strict-decoding-2026-11-18`. Exige sete ancestrais,
incluindo a #664, preservando os cinco contextos anteriores. Os 19 métodos
locais passaram, com 18 combinações inválidas recusadas antes de ler Git e
recusa quando qualquer ancestral exigido está ausente. Os seis testes de
fronteira de sessão anteriores permanecem no conjunto.

## Evidência e limites de aceitação

A revisão local confere os diffs e fontes, sintaxe Python e YAML, modos,
preimages e reconstrução do patch. PHP 8.4, Laravel, PostgreSQL, Chromium e
os cenários reais devem ser confirmados na CI da HEAD publicada. Os resultados
terminais e links serão registrados na PR; nenhum PASS é herdado da #664.

A aceitação técnica requer a contraprova histórica delimitada, as recusas
HTTP com dados preservados, os positivos CSV e as regressões próprias da
candidata. Os gates F251/F252 continuam classificando seu escopo original e
não são alterados para liberar este lote. O resultado de laboratório não
substitui inventário real, revisão nominal ou autorização operacional.
