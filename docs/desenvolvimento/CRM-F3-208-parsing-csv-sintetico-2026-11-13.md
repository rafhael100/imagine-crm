# CRM F3-208 — validação sintática de CSV fictício em memória

**Data:** 13/11/2026. **Origem:** [IN-118](../planejamento/IN-118-central-backup-portabilidade-dados.md), [CRM-REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [issue #35](https://github.com/rafhael100/imagine-crm/issues/35). **Estágio:** código PHP isolado de laboratório; não é parser operacional nem importador real.

## Entrega técnica

`SyntheticCsvStructureInspector` recebe **somente uma pequena string de exemplo fictício** em UTF-8, com opção explícita de separador `comma` ou `semicolon`. Valida estrutura de campos entre aspas, aspas duplicadas, separadores internos à célula, CRLF ou LF entre registros, campos vazios, BOM UTF-8 e largura consistente de colunas. Rejeita aspas soltas, lixo depois do fechamento da aspa, campo com aspas não terminadas, CR fora de aspas sem LF, byte NUL, UTF-8 inválido, tipos não permitidos e limites excedidos.

**Limites explícitos:** até 8.192 bytes no texto recebido, 20 registros, 12 colunas por registro, **20 células no total** e 512 bytes por célula após parsing. O material é efêmero apenas durante a chamada; nenhuma string/linha/coluna processada é retornada ou persistida. A resposta contém contagens agregadas e códigos fixos. O componente chama o inspetor inerte de riscos de fórmula F3-205 em **todas as células**, para não confundir aspas de CSV ou apóstrofo com neutralização de fórmulas.

### Exemplos de sinais

- `INCONSISTENT_COLUMN_COUNT`: largura irregular entre registros do exemplo.
- `MIXED_RECORD_LINE_ENDINGS`: CRLF e LF combinados fora de campos citados.
- `EMPTY_RECORD_REVIEW_REQUIRED`: registro vazio.
- `UTF8_BOM_REVIEW_REQUIRED`: BOM de início de arquivo sintético.
- `CELL_FORMULA_PREFIX_SIGNAL`, `CELL_LEADING_WHITESPACE_OR_FORMAT_SIGNAL` etc.: avisos F3-205 de fórmulas, caracteres invisíveis, Unicode e quebras internas.

Texto sem alertas apenas significa que **este pequeno exemplo** passou pelo subconjunto estrutural implementado. Não atesta que um CSV real pode ser importado, que um arquivo é limpo ou que Excel/Sheets/LibreOffice usarão a mesma interpretação. Não define normalização, encoding compatível, delimitador padrão, escape para planilhas, política de cabeçalho, schema de leads, limites de produção ou tratamento LGPD.

### Restrições de segurança

`authorized=false`; `informational_only=true`; todas as flags `*_verified=false` e `can_import/can_export/can_download/can_sanitize/can_write_file/can_persist=false`. Nenhum arquivo, API, formulário, controller, fila, SQL, rede, segredo, identidade real, contrato comercial ou consentimento é acessado. Não usar dados reais nesses exemplos; o código não deve ser chamado por endpoint de produto sem autorização e revisão.

## Testes e entregas

- `SyntheticCsvStructureInspectorTest.php`: casos com aspas, CRLF, semicolon, Unicode, campos vazios, BOM, fórmulas, multiline, formatos incorretos, tamanho/colunas/linhas máximos, saídas sem conteúdo e proibição de ações.
- `SyntheticCommercialCapabilityDenyMatrixTest.php`: de **47 para 48** componentes inertes, todos sem capacidade operacional; guard de rotas inclui a classe nova.
- Aceite de integração **somente DEV**: quatro pipelines F0/Laravel, PostgreSQL/RLS, Sites e AIA com `success` para **HEAD exata**, sem divergência. PR visual/arquitetura #426/#429/#434 continuam DRAFT.

**Pendente:** formato produtivo CSV/XLSX, validação de upload/quarentena, normalização segura, política anti-formula injection homologada, autorização por tenant e campo, staging T01–T26 e homologação independente de #27/#259. `main` e VPS não foram alteradas.
