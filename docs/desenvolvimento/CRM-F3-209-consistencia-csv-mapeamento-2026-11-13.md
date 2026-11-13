# CRM F3-209 — conferência cruzada entre amostra CSV e mapeamento declarado

**Data:** 13/11/2026. **Origem:** [IN-118](../planejamento/IN-118-central-backup-portabilidade-dados.md), [issue #35](https://github.com/rafhael100/imagine-crm/issues/35) (F3-02). **Estado:** laboratório PHP inerte, sem dados reais ou conexão com interface, API, banco, fila ou storage.

## Por que este incremento é diferente dos anteriores

A F3-208 verifica estrutura de um **pequeno exemplo CSV**; F3-203 verifica a intenção de importação e o mapeamento **alegado** em metadados. Nenhuma das duas, isolada, compara a quantidade de linhas/colunas calculada da amostra com as quantidades declaradas no mapeamento.

`SyntheticCsvImportCrossCheck` valida ambas as camadas **integralmente**, antes da comparação. A operação deve ser **exatamente `import_csv`**: uma intenção `import_xlsx` não pode ser declarada verificada por um parser CSV. Recebe também escolha estrita de separador `comma`/`semicolon` e marcador booleano declarativo `headerClaimed` para determinar se deve subtrair a primeira linha da contagem de registros de dados.

## Alertas de inconsistência

- `HEADER_CLAIMED_WITHOUT_RECORD`: um cabeçalho foi alegado, mas nenhum registro existe na amostra.
- `SAMPLE_HAS_IRREGULAR_RECORD_WIDTH`: a amostra apresenta linhas com larguras diferentes.
- `SAMPLE_COLUMN_COUNT_DISAGREES_WITH_MAPPING`: largura da amostra ≠ número de posições de coluna do mapeamento.
- `SAMPLE_ROW_COUNT_DISAGREES_WITH_MAPPING`: linhas fictícias da amostra ≠ quantidade declarada de linhas importáveis, respeitando o cabeçalho alegado.
- `SAMPLE_CELL_RISK_REQUIRES_SEPARATE_REVIEW`: riscos de célula capturados pelo analisador F3-205, incluindo fórmulas, Unicode invisível e multiline; **essa contagem não é diretamente comparável** ao número declarado de fórmulas de arquivo completo.
- Todos os avisos sintáticos da F3-208 e de intenção/mapeamento da F3-203 são preservados com prefixos `CSV_` e `IMPORT_`.

Mesmo quando as amostras e contagens concordam, isso não prova o esquema real, a titularidade, a origem do dado, consentimentos, a integridade de arquivo completo ou o direito à importação.

## Controles e provas

Recebe amostra limitada a 8.192 bytes, até 20 registros, 20 células e 512 bytes por célula (conforme F3-208), contratos F3-198 e F3-202 estritos e booleano de cabeçalho. Resultado **somente com contagens agregadas e códigos fixos**, sem valores de colunas, registros, nomes, e-mails, caminhos, IDs ou credenciais. Entrada inválida retorna erro constante sem ecoar valores.

Sempre `informational_only=true`, `authorized=false`, `can_import/can_convert_xlsx/can_merge/can_confirm_consent/can_write_file/can_persist=false` e `*_verified=false`. Não cria autorização IAM, parser XLSX, upload, export, arquivo real, banco, migração, endpoint ou deploy.

PHPUnit: cenário alinhado mas sem autorização, conflito de largura/linha, cabeçalho inexistente, modo sem cabeçalho, fórmula citada, CSV ≠ XLSX, dados malformados/PII extra, saída sem vazamento. Matriz comercial de **48→49** componentes sem efeitos e inexistência de rota pública. Quatro pipelines obrigatórios F0/PostgreSQL-RLS/Sites/Agentes devem ficar `success` na HEAD exata antes de merge DEV.

**Não resolvidos:** schema, parser/serializer homologados, planilhas clientes reais, segregação por empresa, credenciais, LGPD/consentimento, idempotência, auditoria, reversão e revisão independente #27/#259. Visual #426/#429 e arquitetura IN-118 #434 permanecem DRAFT; não avançar produção, `main` ou VPS.
