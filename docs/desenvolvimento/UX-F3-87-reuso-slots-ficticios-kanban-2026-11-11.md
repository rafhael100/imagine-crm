# CRM UX F3-87 — Correção de IDs fictícios reaproveitados após desfazer

**Data:** 11/11/2026. **Escopo:** Kanban offline + testes Chrome e Core CSV sintético.

## Defeito identificado

No protótipo `f3-59-offline-kanban`, a criação de oportunidades usava um contador `nextGenerated` incrementado continuamente. O comando **Desfazer criação** removia o cartão, mas não restaurava o contador. Após ciclos de criação/desfazer, isso podia gerar `F13` ou superior mesmo quando havia menos de 12 oportunidades, violando o contrato estrito de IDs `F01..F12` da nova prévia CSV de laboratório (F3-85) e do serializador PHP (F3-86).

## Correção funcional

A criação agora escolhe de maneira **determinística o primeiro slot livre entre F09, F10, F11 e F12**, consultando o array in-memory existente, sem contador persistente ou índice duplicado. Assim, toda sequência de **criar → desfazer → criar** reutiliza o slot liberado; após criar F09, F10, F11 e F12, o limite de 12 cartões é mantido, e desfazer F12 permite criar F12 novamente. Nunca surge F13. A correção não introduz API, formulário, storage, rede ou mudanças em dados reais.

A proteção vale inclusive quando houver ações posteriores a uma criação, pois o histórico de desfazer é LIFO: mudanças em um cartão são revertidas antes de desfazer sua criação. Os botões, filtros, movimentos, acompanhamento, históricos, foco e reset mantêm as funções existentes.

## Testes

- `OfflineKanbanFunnelTest`: exige alocação por slot livre, sem `nextGenerated`, com IDs dentro da whitelist e restrições CSP.
- `check-offline-kanban-browser.php`: Chromium real desktop testa **25 sequências de criar/desfazer F09**, preenchimento dos quatro slots, desfazer F12, recriação usando F12, validade de 12 linhas técnicas no CSV e reinício. Em viewport 320 CSS px: oito ciclos criar/desfazer, reaproveitamento de F12, não emissão de F13 e ausência de overflow.
- `SyntheticKanbanCsvPreviewBuilderTest`: verifica que **12 cartões estritamente fictícios** produzem CSV sem campos inválidos, com parser canônico reportando 12 linhas, seis colunas, zero fórmulas, `canImport=false` e nenhuma autorização de exportação.

## Gates

A alteração resolve um bug real **do laboratório**, mas não concede capacidade de criação de leads reais, importação/exportação de contatos, permissões, APIs, jobs ou persistência operacional. Sem dados pessoais, SQL, migração, IAM/ALLOW, `main`, deploy, VPS, segredo ou gasto. Integração somente DEV após F0, PG/RLS, Sites e Agents `success` na HEAD exata, revisão do diff e `behind_by=0`. Revisões CORE/SEG/DBA/LGPD permanecem NO-GO.
