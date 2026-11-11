# CRM F3-69 — Prévia de campos técnicos obrigatórios vazios

**Data:** 11/11/2026. **Escopo:** Core PHP, apenas fixtures sintéticas; sem upload nem importação operacional.

## Funcionalidade entregue

A prévia CSV passa a identificar **cabeçalhos técnicos requisitados que não aparecem** no arquivo e **células em branco nessas colunas**, sem divulgar seus rótulos ou conteúdo. O método `SyntheticCsvImportPreview::inspectRequiredCells(csv, requiredTechnicalLabels, delimiter)` usa o mesmo parser único, com os limites já vigentes de 32 KiB, 8 colunas, 50 linhas e 512 bytes/célula, com delimitador explícito `,` ou `;`.

Os nomes requisitados obedecem à validação de `SyntheticColumnMappingInspector::suggestExactMatches`, sem campos duplicados, alias, capitalização normalizada ou inferência por similaridade. A saída `SyntheticCsvRequiredCellsSummary` contém apenas contagens e posições 1-based: `requiredSourcePositions`, `missingRequiredTargetPositions`, `emptyRequiredCellPositions`, além do resumo já existente de fórmulas e estrutura. Cabeçalho ausente é advertência posicional, não tentativa de preencher ou renomear campo; uma célula é vazia quando `trim` do texto decodificado é vazio, exatamente como na contagem original de vazios.

A fachada antiga `SyntheticCsvPreviewDecoder::previewRequiredCells` preserva comportamento idêntico de análise e exceção genérica sem ecoar entradas. Nenhuma das saídas concede importação ou autorização: `canImport=false` e `canPersistLead=false`. A lista de colunas é puramente técnica de laboratório; **não** é uma política de campos reais ou de consentimento do CRM.

## Testes

`SyntheticCsvRequiredCellsPreviewTest` prova casos de colunas não encontradas, todas ausentes, dados em branco, colunas reordenadas, zero linhas, `,` e `;`, paridade com a fachada legada, risco de fórmula independente e negativa sem vazamento de nomes, payload, contatos ou autorização. Exige F0 Laravel, PostgreSQL/RLS, Sites e Agents na SHA exata para integração DEV.

## Limites

Sem formulário, download, upload, SQL, token, rotas, dados reais, serviço, persistência, IAM/ALLOW, deduplicação de contatos, tratamento LGPD, gasto ou deploy. `main` e VPS inalteradas. CORE/SEG/DBA/LGPD e revisão independente são requisitos futuros para qualquer importação de clientes.
