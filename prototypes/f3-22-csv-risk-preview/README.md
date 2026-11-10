# UX F3-22 — Prévia de alertas CSV, exclusivamente fictícia

Abra `index.html` diretamente via `file://`. Não há servidor web, conta, dados pessoais, importação, download, parser CSV, XLSX, armazenamento ou rede.

## O que a tela mostra

Duas amostras de **relatórios inventados**:
- **A**: estrutura com três colunas, duas linhas de dados e nenhum alerta. O aviso informa que isso **não certifica** segurança ou permissão para importar.
- **B**: estrutura com quatro colunas, três linhas de dados e cinco alertas: um cabeçalho vazio, um cabeçalho duplicado (colunas 1 e 3), uma linha de largura diferente (linha 3), um prefixo de fórmula (linha 2, coluna 3) e um apóstrofo ambíguo (linha 3, coluna 2).

A tela exibe apenas quantidades, posições e motivos, sem reproduzir conteúdo de uma célula, contato ou cabeçalho. Botões nativos `aria-pressed`, legenda `aria-live`, tabela com contêiner rolável e CSS para 320 px permitem revisar o fluxo sem capturar informações.

## Evidência técnica

O PHPUnit `OfflineCsvRiskPreviewParityTest` extrai as duas estruturas JSON fixas do script inline e compara seus **metadados** com `CsvStructurePreview::summarize`, que por sua vez reutiliza `CsvCellRiskPreview` F3-20. Os dois serviços PHP processam somente matrizes sintéticas do teste, independentes do navegador. Isso evita divergência entre os números ilustrados e o algoritmo de laboratório; **não** implementa integração de importação do navegador para PHP.

`scripts/ci/check-offline-csv-risk-preview-browser.php` usa Chromium real para alternância, focos, renderização dos cinco alertas, limpeza de linhas ao retornar à amostra A e ausência de transbordamento de página em viewport de 320 CSS px. O workflow `ci-f0.yml` exige esse smoke, sem dependência nova.

**Limitação:** a prévia é uma demonstração de apresentação dos diagnósticos, não uma inspeção de arquivo real, XLSX, proteção contra todas as variantes de fórmula, decisão de encoding, mapeamento de colunas, deduplicação, autorização de exportação ou conformidade LGPD. Não há chamada a banco, API, tenant, `ALLOW`, migrações, VPS, produção, custo ou alteração de `main`. Os gates de [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) e do [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) permanecem abertos; o segundo continua em rascunho.
