# F3-223 — Pacote V1 de revisão agregada CSV

`PortableCsvReviewBundleV1` combina duas análises puras e reutilizáveis do Core: o contrato estável `PortableCsvReviewReportV1` (F3-221) e o diagnóstico de controles em células `PortableCsvControlRiskReview` (F3-222). **Não modifica o protocolo V1 original.** O novo protocolo é `imagine.crm.portable_csv_review_bundle`, com versão 1 e `status=REVIEW_ONLY_DENIED`.

O pacote transmite somente contadores de riscos ASCII, separadores de linha, marcas Unicode e controles bidi, além da estrutura de F3-221. A saída é reconstruída por whitelist e verificação cruzada de dimensões, contagens e flags NEGAR. Nenhum CSV, nome, campo, id, hash, caminho, linha, célula ou referência de cliente é enviado. JSON determinístico e limitado a 12 KiB. Os sinais não demonstram comportamento real de Excel/Sheets/LibreOffice nem validam exportação.

A futura interface local de revisão poderá consumir esse contrato separado; **não** há endpoint, importação, exportação, persistência, autenticação real, tenant, acesso de cliente, arquivo nem deploy. Dados de teste são exclusivamente fictícios. Gates #27 SEG/DBA, #259 CORE/LGPD, #416 UX e IN-118 permanecem NO-GO.

`PortableCsvReviewBundleV1Test` verifica composição sem mutar V1, omissões, quebras de linha, campos declarados e não vazamento. F3-222 foi integrada na DEV por PR #459. A PR F3-223 foi realinhada diretamente à DEV; CI F0, PG16/RLS, Sites e AIA em SHA exata e revisão de diff exigidas antes de qualquer merge DEV.
