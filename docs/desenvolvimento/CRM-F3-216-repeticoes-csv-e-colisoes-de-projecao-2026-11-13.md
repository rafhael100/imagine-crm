# F3-216 — Repetições CSV e colisões após mapeamento

Incremento IN-118 sem I/O, SQL, rotas, tenants ou operações reais. O PHP `PortableCsvDuplicateReview` usa o codec F3-210 e mapeamento F3-212 para contar (1) linhas de origem **exatamente repetidas por valores e ordem**; (2) linhas repetidas após selecionar colunas; (3) repetições adicionais criadas pela projeção que omitiu colunas. O resultado é apenas estatístico, sem nomes, fingerprints, hashes, títulos ou linhas.

**Limite:** igualdade de linhas não prova duplicidade de pessoas, consentimento, permissão, contato ou oportunidade. A implementação mantém negação explícita a importação, deduplicação, mesclagem e persistência; não faz normalização, fuzzy matching nem I/O. Nenhum identificador ou digest de valores sai da execução.

Teste `PortableCsvDuplicateReviewTest`: cópias idênticas, colisões por colunas omitidas, reordenação, ausência de cabeçalho, cabeçalho sem dados, entradas inválidas e ausência de vazamento. Gates #27 SEG/DBA, #259 CORE/LGPD, #416 visual e arquitetura IN-118 continuam condicionados; somente DEV é candidata a merge após CI integral e revisão.
