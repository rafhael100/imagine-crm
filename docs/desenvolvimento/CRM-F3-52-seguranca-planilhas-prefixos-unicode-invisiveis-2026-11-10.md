# CRM F3-52 — pré-validação de texto em planilha contra prefixos Unicode invisíveis

**Data:** 10/11/2026. **Âmbito:** código já existente do Core de portabilidade, sem habilitar exportação ou importação de dados.

## Problema mitigado

A regra `SpreadsheetTextRiskGuard::isPotentiallyExecutable()` bloqueava fórmulas iniciadas por `=`, `+`, `-` ou `@`, inclusive após espaços e alguns caracteres Unicode. Mas a lista codificada de separadores não cobria diversas marcas invisíveis, como **U+2060 WORD JOINER**, **U+061C ARABIC LETTER MARK** e **U+034F COMBINING GRAPHEME JOINER**, que podem aparecer antes de uma fórmula ao se copiar texto de aplicativos.

## Mudança técnica

O detector conservador passa a reconhecer prefixos compostos por espaços, categoria Unicode separadora `\p{Z}`, marcas de formatação invisíveis `\p{Cf}` e U+034F, sem alterar valores de célula. A checagem anterior de UTF-8 inválido e bytes de controle permanece.

A suíte existente `SpreadsheetTextRiskGuardTest` foi ampliada com casos negativos de marcas isoladas e misturadas antes de fórmula, e casos positivos em que esses caracteres aparecem **no meio** de texto, sem prefixo executável.

## Limites de segurança

O guard detecta riscos e lança exceção. **Não** sanitiza, cita, escapa nem exporta CSV/XLSX. Não garante segurança em todo software de planilha, pois interpretações variam entre aplicativos e formatos. Números textuais iniciados por `-` continuam conservadoramente bloqueados. Permissões de campo/empresa, auditoria, serialização CSV, proteção de fórmulas em arquivo efetivo e controles LGPD permanecem pendentes.

**Gates:** nenhuma API, upload, base de cliente, importação real, exportação real, senha, migration, nova despesa, VPS, produção ou alteração de `main`. Integração permitida somente à DEV após revisão do diff e F0/PostgreSQL/RLS/Sites/AIA verdes na SHA exata.
