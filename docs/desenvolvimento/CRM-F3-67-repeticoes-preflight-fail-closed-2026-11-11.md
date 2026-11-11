# CRM F3-67 — sinalização de repetições na pré-validação completa do CSV

**Data:** 11/11/2026. **Status:** código de Core com dados sintéticos; nenhuma importação operacional liberada.

## Integração de funcionalidades existentes

O CRM já tinha o pipeline `SyntheticCsvImportPreflight` (leitura CSV → sugestões de colunas → `SyntheticPortabilityDenyGate`, sempre bloqueando) e a identificação independente de linhas exatamente repetidas F3-66.

Esta entrega conecta as duas verificações: `SyntheticCsvImportPreflightSummary` agora contém **opcionalmente** o diagnóstico `repeatPreview` e `hasRepeatWarnings()`. O método `SyntheticCsvImportPreflight::inspect` utiliza **o mesmo parser canônico** tanto para o mapeamento posicional quanto para detectar repetições, com o mesmo delimitador explícito `,` ou `;`. São duas passagens independentes em memória sobre uma string limitada a 32 KiB e 50 linhas; não há leitura de arquivos externos nem cópia persistente.

O resultado agrega somente contagens, posições de fórmulas, índices de colunas sugeridas, posições de linhas repetidas e decisão `AuthorizationDecision` **negativa**. Não devolve nome do cabeçalho, texto da célula, hash, tenant real ou qualquer identificador de contato. Duplicidade de linha é **aviso estrutural**, não deduplicação de pessoas ou campo comercial. Mesmo sem aviso e com mapeamento 100% completo, `allowed=false`, `canImport=false`, `canPersistLead=false`, `canEnableImportRoute=false` e `canDeduplicateContacts=false`.

## QA

O teste `SyntheticCsvImportPreflightTest` agora cobre CSV com linha repetida, igualdade dos resumos das duas passagens, campos sintéticos escondidos, divergência de empresa em modo `;` com vírgula decimal, e caso de duas linhas com o mesmo nome fictício e fases diferentes (não são repetição estrutural nem permissão para vincular pessoas). Exigir suites F0, PostgreSQL/RLS e cross-repo Sites/Agents na SHA exata do PR.

## Gates

Não cria upload, formulário, API, exportador, worker, integração com bancos, autenticação, grants, ALLOW, migrações, armazenamento, tratamento LGPD, despesa ou deploy. `main`, VPS e produção inalteradas. Contratos de deduplicação real, política de campo/tenant e parecer independente SEG/DBA/LGPD continuam NO-GO.
