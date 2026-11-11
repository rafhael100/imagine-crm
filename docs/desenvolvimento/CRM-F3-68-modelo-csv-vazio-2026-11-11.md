# CRM F3-68 — Modelo CSV vazio de cabeçalhos técnicos

**Data:** 11/11/2026. **Escopo:** classe PHP Core isolada que gera somente uma linha de cabeçalhos; sem dados, download operacional ou armazenamento.

## Uso futuro e funcionalidade

O novo `SyntheticCsvHeaderTemplate::render($technicalLabels, $delimiter = ',', $withBom = false)` gera um CSV de **cabeçalhos técnicos**, separado por vírgula ou ponto e vírgula, terminado em CRLF, com opção de BOM UTF-8. O resultado nunca inclui linhas de dados, identidades, contatos, informações pessoais, senha, tenant ou mensagens comerciais.

Para evitar dois validadores diferentes, o gerador **reutiliza `SyntheticColumnMappingInspector::suggestExactMatches`** com os próprios rótulos como origem e destino. O gerador só aceita um conjunto não vazio de até oito nomes técnicos únicos, minúsculos, de sintaxe já homologada no laboratório. Com isso, entradas com vírgula, ponto e vírgula, aspas, caractere de controle, fórmula, Unicode estranho, repetição, tipo inválido ou mais de oito campos são recusadas com mensagem genérica, sem ecoar os rótulos.

O arquivo sintético resultante é compreendido pelo único parser `SyntheticCsvImportPreview` (F3-55/65), inclusive BOM + `;`; `rowsInspected=0` e o número de colunas corresponde ao conjunto gerado. O mapeamento posicional F3-56 e o detector de repetições F3-66 funcionam com esse cabeçalho vazio e sempre retornam `canImport=false`. `canExportContacts=false` e `canAuthorizeImport=false` no gerador.

## Testes

`tests/Unit/SyntheticCsvHeaderTemplateTest.php` cobre o roundtrip header→parser→mapeamento, um e oito campos, delimitadores `,`/`;`, BOM, CRLF, ausência de linhas, ausência de conteúdo de cliente e rejeição de tipos/rótulos/delimitadores inválidos. Workflows F0, PostgreSQL/RLS, Sites/Agents exigidos verdes na SHA exata antes de merge somente DEV.

## Gates

Esta classe **não define quais campos reais o CRM disponibilizará**; tais contratos dependem de CORE/IAM/LGPD e de autorização específica. Não existe exportação ou importação operacional, leitura de banco, rota web, upload, geração de arquivos de clientes, grants, migration, assinatura, despesas, VPS, deploy ou alteração em `main`. A revisão SEG/DBA, LGPD e autenticação multiempresa continuam pendentes.
