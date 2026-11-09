# CRM Imagine — F3-20 — Decodificação CSV sintética para prévia limitada

**Data:** 09/11/2026. **Escopo:** componente PHP sem conexão com a web, disco de clientes, banco ou endpoints. Somente *fixtures* fictícias/testes em DEV. Referências: [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [F3-03](F3-03-previa-offline-sintetica-importacao-2026-11-08.md), [IN-118](../planejamento/IN-118-central-inteligente-importacao-exportacao-backup.md) e a política de portabilidade CRM-REV-03.

## Mudança concreta

- `SyntheticCsvPreviewDecoder` aceita **somente uma string CSV de demonstração em memória**, limitada a **32.768 bytes** com UTF-8 válido e sem NUL, interpretada pelo mecanismo PHP `fgetcsv` padrão (delimitador `,`, aspas `"`, sem escape proprietário).
- Exige cabeçalho não vazio e estrutura compatível com os limites do `SyntheticImportPreviewInspector` já existente: **1–8 colunas**, identificadores de cabeçalho ASCII `[a-z][a-z0-9_]{0,31}` únicos, **0–50 linhas**, matriz retangular e **até 512 bytes/célula**.
- Suporta aspas, vírgulas dentro de aspas, quebras de linha citadas, CRLF e células vazias; a inspeção de risco de fórmulas CSV permanece conservadora via `SpreadsheetTextRiskGuard`.
- O resultado é o mesmo `SyntheticImportPreviewSummary`: apenas contagens e **posições de risco 1-based**, **nenhum nome, contato, valor de célula, cabeçalho ou conteúdo de arquivo**.
- `canImport()` permanece **sempre falso**. CSV inválido/excessivo é rejeitado com erro neutro que não reproduz valores.

## Limites deliberados

**Não** é importador ou validação completa do padrão CSV para clientes; não autentica usuário, identifica tenant, resolve campo/mapping, deduplica pessoa, cria lead, converte planilha XLSX, impõe entitlement, atribui direito de exportação, aceita upload nem gera arquivos persistentes.

O stream `php://temp` permanece em memória porque a entrada está limitada a menos de 64 KiB; não há arquivo de cliente, rede, URL nem armazenamento de longo prazo. O código pode temporariamente manter linhas sintéticas em memória apenas para calcular o resumo, sem reter dados no resultado.

**Políticas ainda pendentes de homologação:** encoding/importação real, BOM, CSV com escape não padrão, delimitador regional, quebras de linha inválidas, separação de dados e metadados, mapeamento de campos e tratamento final de células executáveis. Não fazer correções silenciosas de dados. A restrição CSV sintética não determina automaticamente políticas definitivas da portabilidade.

**Governança:** o novo componente não altera o backend público, rotas, autorização `ALLOW`, banco, filas, migrações, credenciais, `main`, VPS, produção ou custos. Todos os 67 CORE, SEG/DBA Issue #27, LGPD/INT e demais gates continuam abertos. CI verde significa apenas sucesso dos testes com *fixtures* descritas, não homologação da importação de clientes.
