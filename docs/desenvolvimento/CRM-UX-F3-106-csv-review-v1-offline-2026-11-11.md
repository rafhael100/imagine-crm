# CRM UX F3-106 — Relatório CSV v1 de laboratório

**Data:** 11/11/2026. **Escopo:** HTML offline em `prototypes/f3-106-csv-review-v1/`, sem operação de importação real.

## Entrega

Disponibiliza uma nova tela navegável a partir do checklist CSV (`f3-72-csv-checklist`) para conferir o relatório estrito de metadados do Core (`SyntheticCsvReviewReportV1` / encoder JSON F3-101).

A interface alterna entre três exemplos de relatórios inventados:

1. Estrutura sem alertas;
2. Dois alertas de fórmula, uma linha repetida, um cabeçalho requerido ausente e duas células obrigatórias vazias;
3. Mesmos indicadores estruturais, mas com negativa de contrato/escopo divergente.

Cada exemplo mostra `schema_version=1`, protocolo interno fixo, contagens de linhas/colunas/células e um código **allowlist** de negativa. As advertências são apresentadas apenas por posições de linha/coluna, com `textContent` seguro; a tela nunca precisa receber conteúdo bruto de células, cabeçalhos ou identificadores da empresa. `allowed=false`, `can_import=false`, `can_persist_lead=false` e `can_enable_import_route=false` em todos os relatórios.

## QA integrado

O teste PHPUnit `OfflineCsvReviewV1ParityTest` extrai as três estruturas JSON do HTML, gera as mesmas prévias por `SyntheticCsvImportPreflight` com dados fictícios e compara-as **integralmente** ao resultado PHP de `SyntheticCsvReviewReportV1::build()` e à saída do `SyntheticCsvReviewReportV1JsonEncoder`. Comprova também que os relatórios não incluem conteúdo de células, identificadores de empresa, principal ou correlação.

`check-offline-csv-review-v1-browser.php` abre os arquivos locais em **Chromium real** nos tamanhos desktop (1100px) e mobile (320px). Testa os três botões, foco, `aria-pressed`, indicadores e alertas, avisos de ausência de permissão, zero capturas e ausência de overflow horizontal. A CI F0 inclui o smoke como etapa obrigatória.

## Gates e limites

Esta tela não representa autorização de importação ou exportação CSV/XLSX/backup, não permite upload nem concede identidade ou permissões. Não existe endpoint, storage, SQL, migration, dado pessoal real, `main`, VPS, custo ou deploy. Revisões CORE/SEG/DBA/LGPD seguem NO-GO. Uma futura experiência operacional exigirá política de dados e autorização própria por empresa, além de testes em staging autorizado.
