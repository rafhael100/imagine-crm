# CRM F3-65 — Pré-visualização CSV com delimitador ponto e vírgula explícito

**Data:** 11/11/2026. **Escopo:** código PHP Core isolado para **prévia sintética**, sem upload, API, banco ou importação real.

## Funcionalidade

O CRM tinha um parser CSV único, com proteção de tamanho, estrutura e fórmulas, mas aceitava apenas vírgula como delimitador. Arquivos exportados por ferramentas que usam configurações brasileiras podem vir com **ponto e vírgula (;)** como separador de colunas e **vírgula decimal (2,50)** como texto de célula.

O parser canônico `SyntheticCsvImportPreview` agora aceita um argumento opcional `$delimiter`, exclusivamente **`,` ou `;`**, nos métodos `inspect`, `inspectMapped` e `inspectSuggested`. O padrão é **vírgula** para manter compatibilidade com todas as chamadas existentes. O próprio código rejeita outros símbolos e não tenta inferir automaticamente o formato a partir do conteúdo, pois isso pode mascarar erro de coluna ou fazer um campo parecer outro.

A fachada de compatibilidade `SyntheticCsvPreviewDecoder` recebe o mesmo parâmetro opcional em `preview`, `previewMapped` e `previewSuggested`; o pipeline sintético `SyntheticCsvImportPreflight::inspect` também aceita o parâmetro **apenas como última opção**, mantendo a operação `import` com autorização sempre negada.

A gramática de aspas duplas, escapes `""`, BOM inicial, CRLF/LF, limite de **32 KiB/8 colunas/50 linhas/512 bytes por célula**, validação de cabeçalhos, bloqueio de CSV malformado e pré-aviso de caracteres perigosos de planilha é a mesma em ambos os modos. Vírgula decimal dentro de célula `;` não vira nova coluna; ponto e vírgula entre aspas não vira separador. O resultado mantém somente **contagens e posições de risco**, nunca conteúdo de células ou cabeçalhos.

## Testes novos

`tests/Unit/SyntheticCsvSemicolonDelimiterTest.php` verifica BOM, vírgula decimal, ponto e vírgula entre aspas, fórmula perigosa, multiline, contagens e ausência de vazamento; equivalência do parser canônico com a fachada histórica; mapeamento posicional manual e sugerido; o gate obrigatório `canImport=false` mesmo com mapeamento completo e empresa alegada consistente; delimitador ausente e inválido, linhas malformadas, aspas, largura irregular, duplicação de coluna e NUL. A CI F0 completa, PostgreSQL/RLS, Sites e Agents deve ficar verde na SHA do PR.

## Limites

A opção semicolon é uma **melhoria de interoperabilidade de prévia**, não autorização para importar dados reais. Não cria formulário, importador, exportador, endpoint, coluna de banco, serviço IAM, consentimento, contato, tenant membership, fila, assinatura, custo ou deploy. `main`, VPS e produção permanecem inalteradas. Revisão SEG/DBA, LGPD, CORE e gates de acesso por empresa são obrigatórios para a funcionalidade operacional.
