# CRM F3-66 — Diagnóstico de linhas CSV repetidas por posição

**Data:** 11/11/2026. **Escopo:** PHP Core isolado; apenas CSV sintético limitado para prévia.

## Funcionalidade

A prévia CSV já detectava estrutura e fórmulas perigosas, porém não apontava **linhas exatamente repetidas**. O novo `SyntheticCsvImportPreview::inspectRepeatedRows($csv, $delimiter)` utiliza o parser único F3-55/F3-65 e compara sequências já decodificadas, identificando por **posição** onde uma linha se repete.

O retorno `SyntheticCsvRowRepeatSummary` contém somente o `SyntheticImportPreviewSummary` de contagens/riscos e uma lista de pares de **índices base 1** (`firstRow`, `repeatedRow`). Nenhum texto, campo, e-mail, hash, identificador de contato ou conteúdo do arquivo é devolvido. Uma linha repetida três vezes tem a primeira posição como referência para as outras duas. Aspas que mudam apenas a representação do mesmo texto **não** escondem duplicidade; a comparação é exata em bytes Unicode UTF-8 após parsing, **sem** correção de caixa, trim ou equivalência semântica. Assim, `SYNTHETIC_A` e `synthetic_a` não são considerados iguais.

O algoritmo compara diretamente as linhas em memória, no máximo 50 linhas e 8 colunas, sem criar hashes de dados pessoais ou índices persistentes. A fachada `SyntheticCsvPreviewDecoder::previewRepeatedRows` delega ao mesmo parser e preserva erros genéricos, sem divulgar conteúdo. Um CSV com duplicidades e fórmulas suspeitas gera **ambos** os avisos; nenhum aviso significa aprovação para importar.

## QA

`tests/Unit/SyntheticCsvRowRepeatPreviewTest.php` cobre os formatos `,` e `;`, aspas e semicolon entre aspas, BOM/UTF-8 em casos anteriores, conteúdo multiline, células vazias, repetições tríplices, diferença por caixa/espaço, fórmulas, cabeçalho sem registros, 50 linhas idênticas e negativos de estrutura/delimitador. Exige `canImport() === false` e `canDeduplicateContacts() === false` em todos os casos. F0 Laravel, PostgreSQL/RLS e Sites/Agents exigidos na SHA exata antes de eventual merge DEV.

## Limites

**É diagnóstico estrutural, não deduplicação de contatos.** Um contato pode aparecer em linhas diferentes e duas linhas idênticas não demonstram identidade, consentimento ou propriedade. Dados de clientes e importação real, API, upload, armazenamento, tratamento LGPD, grants, IAM, transações e operações de leads continuam NO-GO. Não há alteração em `main`, VPS, produção ou custos.
