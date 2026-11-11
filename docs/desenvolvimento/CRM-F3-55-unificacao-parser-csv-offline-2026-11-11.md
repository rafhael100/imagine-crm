# CRM F3-55 — unificar dois decodificadores CSV para prévia offline

**Data:** 11/11/2026. **Natureza:** refatoração de código executável; dados e testes sintéticos apenas.

## Problema corrigido

A evolução paralela do CRM gerou duas classes com interpretações parcialmente diferentes de CSV:

- `SyntheticCsvPreviewDecoder` (histórico F3-20), com `fgetcsv(php://temp)`, também oferecia `previewMapped` para F3-21.
- `SyntheticCsvImportPreview` (F3-53), parser de estado estrito e limitado, oferecia `inspect` sem mapeamento.

Manter dois parsers criava **deriva comportamental e custo de manutenção**, especialmente para aspas malformadas e BOM UTF-8.

## Refatoração

O método interno de parsing passa a existir **somente em `SyntheticCsvImportPreview`**. O antigo `SyntheticCsvPreviewDecoder` foi reduzido a uma fachada de compatibilidade que delega `preview` e `previewMapped` ao parser único, convertendo erros em mensagem genérica já usada pelos consumidores anteriores. O parser único agora também faz o mapeamento posicional F3-21, com o mesmo `SyntheticColumnMappingInspector` e `SyntheticImportMappedPreviewSummary`, sem devolver cabeçalhos ou células.

- Comportamento unificado: aspas não fechadas/aspas em campo aberto rejeitadas; header/estrutura limitada a 32 KiB, 8 colunas, 50 linhas, célula de 512 bytes e UTF-8; linha física vazia continua inválida.
- BOM UTF-8 inicial é aceito e descartado **somente no início do arquivo** antes da leitura do cabeçalho.
- Os dois pontos de entrada produzem resumos equivalentes. Mapeamento informa somente números de colunas/posições, sem valores de células, headers ou grants.

## Verificações

Testes de `SyntheticCsvPreviewDecoderTest` atualizados para BOM positivo e aspas malformadas negativas. `SyntheticImportMappedPreviewTest` compara entradas legado e canônica em CSV com fórmula suspeita, exigindo equivalência e `canImport=false`. Suite F0, PostgreSQL/RLS, CI Sites/CRM e Agents/CRM requeridas na SHA exata para merge.

## Restrições operacionais

Nenhum upload, rota, API, persistência, execução de importação, exportação, dados reais, autorização/tenant verificado ou permissão ALLOW foi criado. Nenhuma mudança de `main`, VPS, produção, gastos ou credenciais. CORE/SEG/DBA/LGPD seguem NO-GO. Revisão de segurança e contrato de importação devem ser homologados antes de aceitar dados de clientes.
