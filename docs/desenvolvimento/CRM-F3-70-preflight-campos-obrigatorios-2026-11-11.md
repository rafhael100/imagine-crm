# CRM F3-70 — Indicadores de campos obrigatórios na pré-validação completa

**Data:** 11/11/2026. **Escopo:** Core PHP; somente dados de laboratório, sem processamento operacional.

## Composição funcional

A pré-validação `SyntheticCsvImportPreflight::inspect` já retorna análise CSV, sugestão de colunas, aviso de fórmulas, repetição exata de linhas e decisão `AuthorizationDecision` sempre negativa. Agora ela pode receber, como **último parâmetro opcional**, `?array $requiredTechnicalLabels = null`: a lista de **campos técnicos sintéticos** cujo preenchimento se deseja revisar.

Quando a lista é fornecida, `SyntheticCsvImportPreview::inspectRequiredCells` valida cada rótulo com a mesma gramática estrita e devolve somente posições 1-based de cabeçalhos não encontrados e células vazias em colunas efetivamente existentes. O resultado passa a integrar `SyntheticCsvImportPreflightSummary::requiredCellsPreview`, com duas consultas convenientes: `hasMissingRequiredHeaders()` e `hasEmptyRequiredCells()`. O parâmetro é opcional para **não quebrar chamadas anteriores**; quando omitido, o atributo é null e os sinais são falsos.

O parser canônico é compartilhado por todas as análises. A implementação faz passagens independentes em memória sobre o mesmo texto limitado a 32 KiB, 8 colunas e 50 linhas, sem filesystem, estado persistente ou rede. O comportamento de `allowed=false`, `canImport=false`, `canPersistLead=false` e `canEnableImportRoute=false` permanece inalterado, independentemente de headers/células/tenant alegado.

## QA

`SyntheticCsvImportPreflightTest` cobre integração de avisos de cabeçalho ausente, células vazias e duplicidade, paridade de resumo de contagens/risco entre passagens, modo `;` do CSV brasileiro, texto não devolvido em JSON, chamada legada sem lista requerida, lista completa sem aviso e entrada inválida com erro genérico. F0 PHP/Laravel, PostgreSQL/RLS e CI de paridade Sites e Agentes são obrigatórios na SHA exata antes de eventual merge DEV.

## Limites

A escolha de campos obrigatórios é apenas um **exemplo estrutural informado pelo chamador**, não uma política real de CRM ou consentimento. A presença dos campos não significa identidade, autorização, completude comercial, deduplicação nem liberação operacional. Não há API, upload, leads reais, SQL, migrações, papéis IAM, credenciais, dados pessoais, despesa, produção, VPS ou alteração em `main`. CORE/SEG/DBA/LGPD e revisão independente seguem como NO-GO.
