# CRM F3-86 — Prévia CSV sintética do Kanban validada também em PHP

**Data:** 11/11/2026. **Escopo:** Core PHP puro + testes, sem operação real.

## Entrega funcional

Foi criado `SyntheticKanbanCsvPreviewBuilder::render($records)`, serializador determinístico e estrito para **até 12 registros fictícios do Kanban** em formato CSV separado por ponto e vírgula. Sua saída replica exatamente a representação visual offline da [UX F3-85](UX-F3-85-previa-csv-tecnico-kanban-2026-11-11.md), preservando a ordem dos registros e exibindo apenas a estrutura controlada:

`id;etapa;prioridade;acompanhamento;valor_ficticio;pontuacao`

Aceita somente lista de estruturas com **exatamente seis campos técnicos** (`id, stage, priority, followup, value, score`), IDs no intervalo `F01..F12`, sem repetição, etapas inteiras `0..3`, prioridade `high/normal`, acompanhamento `pending/done`, valores inteiros não negativos e score `0..100`. Qualquer dado anômalo, inclusive campo extra de nome, e-mail, consentimento, índice não sequencial, identificador que pareça fórmula, duplicidade ou valor inválido, faz o método rejeitar **todo o lote** com erro genérico, sem vazar identificadores, payload ou produzir conteúdo parcial.

A saída contém apenas o cabeçalho quando o subconjunto está vazio e possui quebras de linha LF; não inclui BOM, nome de cliente, texto livre nem células capazes de se tornar fórmulas. O componente **não consulta banco, escreve arquivo, acessa a rede ou cria importação**. Os métodos `canExportContacts()`, `canAuthorizeImport()` e `canPersistLead()` retornam sempre `false`.

## Paridade comprovada em testes

`SyntheticKanbanCsvPreviewBuilderTest` lê as **oito fixtures reais do protótipo HTML** por regex estrita, compara o CSV PHP com oito linhas *golden* e a projeção em filtros de prioridade Alta. Verifica a inclusão do cartão fictício F09 e o cabeçalho de seleção vazia.

O mesmo CSV gerado pelo PHP é analisado pelo **parser canônico existente** `SyntheticCsvImportPreview` no modo `;`: seis colunas, oito linhas (ou nove com F09), zero células vazias, zero fórmulas, **`canImport=false`**. Testes adversariais cobrem ID formula, PII extra, duplicações, registros fora do limite, campos faltantes, tipos incorretos e valores fora do domínio, sempre falha fechada.

## Gates

A existência de um serializador interno **não é autorização de exportação de clientes, backup real, importação operacional, consentimento ou vinculação multiempresa**. O código só conhece cartões fictícios F01..F12 e não possui API/serviço/ORM/file storage. Nenhuma migração, rota, IAM/ALLOW, segredo, PII, gasto, VPS, produção ou `main` foi alterada. Exigir F0, PostgreSQL/RLS, Sites e Agents `success` na SHA exata, diff e `behind_by=0` para merge **somente DEV**. CORE/SEG/DBA/LGPD permanecem NO-GO.
