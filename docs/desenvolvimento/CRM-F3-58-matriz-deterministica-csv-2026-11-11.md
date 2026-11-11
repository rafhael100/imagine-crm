# CRM F3-58 — matriz determinística de 128 cenários CSV e autorização negada

**Data:** 11/11/2026. **Escopo:** teste PHPUnit de código existente; fixtures inteiramente fictícias, sem infraestrutura nova.

A cadeia desenvolvida nas F3-53..F3-57 foi conectada, mas um parser de CSV com aspas e quebras de linha pode sofrer regressões difíceis de detectar em casos isolados. Esta etapa adiciona `tests/Unit/SyntheticCsvDeterministicMatrixTest.php`, uma matriz de 128 combinações **determinísticas**, sem dados pessoais, sem randomização não reproduzível e sem dependências externas.

Para cada caso, são gerados cabeçalhos técnicos validados, 0–16 linhas e 1–8 colunas com valores sintéticos que incluem vírgula, aspas duplas, LF/CRLF entre aspas, TAB interno, marcador Unicode invisível, fórmula iniciada com `@`/`-`, texto simples e célula vazia entre aspas. Compara as contagens e posições de fórmula esperadas com o único parser CSV canônico, a fachada legada, a sugestão de mapeamento completa e a decisão `SyntheticCsvImportPreflight`. Mesmo nos casos sem problema estrutural, `canImport=false` e `decision.allowed=false` são exigidos.

A segunda série rejeita aspas em campo aberto, terminação incorreta, CR isolado, quantidade extra de colunas, linha física vazia, UTF-8 inválido e NUL, sem ecoar o conteúdo no erro. O conjunto protege contra deriva entre as duas interfaces e mantém as novas regras de caracteres de controle F3-54.

**Limites:** suíte de laboratório, sem prova de streaming de arquivos grandes, proteção de todo software CSV, dados de clientes, importação de produção ou autorização de tenants. Não cria endpoint, consulta, credencial, migração, deploy, custos ou alterações em `main`. Gates CORE/IAM, SEG/DBA, LGPD e homologação de importação continuam abertos.
