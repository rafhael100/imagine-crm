# CRM F3-222 — Sinais de controles invisíveis em CSV local

`PortableCsvControlRiskReview` analisa as células já validadas estruturalmente pelo codec F3-210 e o mapeamento explícito F3-212. Conta separadamente, na origem e nas colunas selecionadas: células contendo controles ASCII (inclusive `\r`, `\n` ou TAB), separadores de linha Unicode NEL/U+2028/U+2029 ou CR/LF, marcas de formatação Unicode `Cf`, e controles de direção bidirecional. Uma célula pode contribuir para várias contagens; os números **não** são categorias mutuamente exclusivas.

**Benefício:** detectar conteúdo invisível/multilinha que a validação de prefixos de fórmulas sozinha não cobre, sem alterar `encode`/`decode` nem descartar conteúdo silenciosamente. O analisador não confirma execução de fórmulas, não define política de escaping para Excel/Sheets/LibreOffice e não autoriza importação/exportação. Só retorna contadores e códigos estáticos; nunca retorna conteúdo, cabeçalho, posição, hash, identidade ou campo de destino. Sem I/O, DB, rotas, dados reais, cobrança ou produção.

QA: testes para células CSV com quebra de linha entre aspas, controles bidi e zero-width, coluna omitida, amostra limpa/sem dados, entradas inválidas e ausência de vazamento. Gates #27 SEG/DBA, #259 CORE/LGPD, #416 UX e IN-118 continuam NO-GO.
