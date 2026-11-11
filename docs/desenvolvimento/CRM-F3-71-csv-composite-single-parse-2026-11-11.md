# CRM F3-71 — Uma leitura do CSV para todos os avisos da pré-validação

**Data:** 11/11/2026. **Escopo:** otimização real do Core sintético, sem operação de clientes.

## Melhoria implementada

Antes, `SyntheticCsvImportPreflight` chamava o parser canônico **duas vezes** (prévia/mapeamento e duplicidades), ou **três vezes** quando eram solicitados campos técnicos obrigatórios. Embora cada leitura fosse limitada a 32 KiB, a repetição desperdiçava trabalho e dificultava garantir que todos os avisos fossem derivados da mesma estrutura decodificada.

Agora `SyntheticCsvImportPreview::inspectComposite(csv, targetLabels, requiredTechnicalLabels?, delimiter)` realiza **uma única chamada ao decodificador canônico** e calcula, sobre o mesmo array temporário estritamente validado, três resultados somente de metadados:

- `SyntheticImportMappedPreviewSummary`: estrutura, fórmulas e sugestão posicional exata de colunas.
- `SyntheticCsvRowRepeatSummary`: pares de linhas com conteúdo exatamente repetido, **sem hash** nem inferência de identidade.
- `SyntheticCsvRequiredCellsSummary` opcional: cabeçalhos técnicos necessários ausentes e células vazias por posição.

O novo `SyntheticCsvCompositeSummary` contém os três relatórios sem reter texto, cabeçalho, célula ou contexto de cliente. Os algoritmos privados `repeatedPositions` e `requiredCellSummary` são compartilhados com as APIs antigas `inspectRepeatedRows` e `inspectRequiredCells`, de modo que **não existem duas definições concorrentes das regras**. `SyntheticCsvImportPreflight` usa a composição única e continua aplicando `SyntheticPortabilityDenyGate` invariavelmente.

O comportamento dos métodos antigos `inspect`, `inspectSuggested`, `inspectMapped`, `inspectRepeatedRows`, `inspectRequiredCells` e da fachada `SyntheticCsvPreviewDecoder` é preservado, para não quebrar as chamadas existentes.

## Testes

`SyntheticCsvCompositePreviewTest` compara profundamente os resultados da composição com todas as APIs anteriores, em CSV `,` e `;`, BOM, fórmulas, células multiline, colunas reordenadas, ausência de cabeçalho, CSV sem linhas e 50 linhas repetidas. Exige ausência de dados originais nas respostas e negativa incondicional de importação. Entrada malformada, delimitador não permitido e nomes técnicos inválidos falham fechados com erro genérico. A suíte existente de `SyntheticCsvImportPreflightTest` verifica a paridade funcional do pipeline.

Integrar DEV somente após F0 Laravel/Chromium, PostgreSQL/RLS, Sites e Agents aprovados na SHA exata, revisão de diff e branch atualizada.

## Gates

Otimização de CPU/consistência **não** é homologação de esquema comercial, validação de dados pessoais, deduplicação de pessoas, liberação LGPD nem concessão de direitos. Sem API, upload, SQL, IAM, ALLOW, migração, PII, custo, deploy, VPS, `main` ou produção. CORE/SEG/DBA/LGPD e parecer independente permanecem bloqueios.
