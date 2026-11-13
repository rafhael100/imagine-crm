# CRM F3-215 — Revisão agregada de CSV em memória

Continuação empilhada sobre F3-214 e F3-213. O componente `PortableCsvImportReadinessSummary` combina os resultados de `PortableCsvImportPreview` e `PortableCsvHeaderRiskSummary`, verifica coerência de quantidade de linhas/colunas e retorna apenas contadores/códigos. Não devolve título de coluna, dado de linha, tenant, consentimento, nome ou contato.

A agregação não autoriza operações: `can_import`, `can_export`, `can_merge`, `can_persist` e `authorization_verified` permanecem false. Não há rotas, arquivos, banco, rede nem uso de dados reais. Erros são genéricos e não reproduzem valores fornecidos.

Suíte `PortableCsvImportReadinessSummaryTest.php` cobre riscos simultâneos, cenário sem cabeçalho, negação e ausência de vazamento. A F3-214 foi integrada somente na DEV (PR #443). Esta PR agora mira diretamente a DEV, sem importação operacional; não integrar sem CI da HEAD exata, revisão do diff e confirmação de base atualizada. Issues #27/#259/#416 continuam gates.
