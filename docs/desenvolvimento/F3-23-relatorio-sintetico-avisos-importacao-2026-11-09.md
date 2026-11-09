# F3-23 — Consolidação defensiva de avisos da prévia de importação

**Data:** 09/11/2026. **Mandato:** [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), continuidade de F3-20/21/22 na DEV. Não altera as decisões comerciais ou os gates CORE/SEG/LGPD.

## Objetivo

O relatório `SyntheticImportReviewInspector::assess()` recebe **somente** um `SyntheticImportMappedPreviewSummary` com dados sintéticos. Antes de produzir qualquer indicação para uma futura interface, confere a **consistência interna** dos resumos (que possuem construtores públicos e, portanto, podem ser forjados):

- Linhas de 0 a 50; colunas de 1 a 8; células vazias no intervalo 0..linhas×colunas.
- Lista de posições suspeitas 1-based, sem duplicatas, além dos limites; não aceita avisos de células em arquivo só com cabeçalho.
- Colunas de origem do mapa iguais àquelas do resumo CSV; colunas de destino entre 1 e 8.
- Pares `{source,target}` exclusivos e no intervalo, com as posições sem mapeamento completando exatamente a partição de cada eixo. Não preenche lacunas nem aceita `allow`.
- Toda inconsistência falha com uma exceção fixa sem refletir identificadores, rótulos ou células.

O resultado `SyntheticImportReviewSummary` é **readonly** e apenas agrega seis contagens e códigos do catálogo fechado, na ordem: `EMPTY_DATASET`, `UNMAPPED_SOURCE_COLUMNS`, `UNMAPPED_TARGET_COLUMNS`, `POTENTIAL_FORMULA_CELLS`, `EMPTY_CELLS`. O array vazio significa **ausência desses cinco sinais no laboratório**, não validação semântica, segurança, direito ou confirmação. `canImport()` permanece sempre `false`.

## Validação

`SyntheticImportReviewInspectorTest`: prévias obtidas do decodificador, aviso de possível fórmula, células vazias e lacunas; zero linhas; mapa completo sem autorização; dezenove variações de relatório forjado/inconsistente; ausência de métodos com efeitos e de dados originais no JSON. Todos os testes existentes de CSV, mapeamento, RLS, autorização negativa e navegador devem continuar verdes.

## Limitações explícitas

A avaliação é **apenas de metadados sintéticos**, não repete a leitura real do CSV nem comprova a autenticidade de snapshots recebidos de terceiros. Não recebe credenciais, CPF/CNPJ, contatos, upload, XLSX, webhooks, arquivos, banco, APIs ou rotas. Não deduz consentimento, schema de campos, direitos por tenant/atributo, disponibilidade, quota, retenção, ausência de duplicidades, ou possibilidade de concluir a importação.

A integração com fluxo real, políticas por empresa/campo, confirmação humana, trilha de auditoria, revisão de Segurança/DBA e LGPD continua bloqueada. **Não alterar `main`, VPS, produção, custos nem dados de clientes.** Somente merge supervisionado em DEV com diff revisto e ambas as CIs verdes.
