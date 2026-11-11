# CRM F3-53 — parser CSV limitado para prévia offline e sintética

**Data:** 11/11/2026. **Escopo:** componente PHP puro, sem rota, arquivo, upload, sessão, banco, importação ou exportação operacional.

## Evolução funcional

Antes, `SyntheticImportPreviewInspector` recebia cabeçalhos e linhas **já decodificados**. O novo `app/Core/Portability/SyntheticCsvImportPreview.php` aceita uma string CSV fictícia, interpreta as regras básicas de aspas duplas (inclusive `""`), delimitador vírgula, quebras `LF`/`CRLF`, linha entre aspas e BOM UTF-8 inicial e a encaminha ao validador existente.

O limite físico evita acumulação arbitrária: até **32 KiB** de texto, **8 colunas**, **50 linhas de dados** e **512 bytes por célula**, com UTF-8 obrigatório e rejeição de bytes NUL, `CR` isolado, aspas malformadas, cabeçalhos repetidos e linhas de largura irregular. A checagem de caracteres potencialmente executáveis em planilhas continua sendo feita pelo `SpreadsheetTextRiskGuard` (F3-52).

O retorno é exclusivamente `SyntheticImportPreviewSummary`: contagem de linhas, colunas, células vazias e coordenadas de fórmulas suspeitas. **Nunca retorna dados das células, e-mail, nome, nome da coluna nem CSV original.** `canImport()` permanece `false` mesmo com prévia válida.

## Evidência

`tests/Unit/SyntheticCsvImportPreviewTest.php` cobre aspas/escapes, multiline, CRLF, BOM, 50 linhas, linhas irregulares, caracteres invisíveis antes de fórmula, UTF-8 inválido, NUL, aspas fora de posição, limites e ausência de vazamento no resumo/erros. As suítes Laravel F0 e PostgreSQL/RLS existentes devem continuar verdes na SHA exata do PR.

## Restrições

O código **não deve** receber upload real ou dados de cliente antes de IAM, isolamento multiempresa, classificação de dados, política LGPD, streaming com quotas, trilha de auditoria e revisão de segurança. Prévia válida não homologa mapeamento, deduplicação, persistência, consentimento ou exportação segura. Nenhuma migration, endpoint, permissão `ALLOW`, chave, VPS, deploy, gasto ou mudança de `main`.

Após revisão técnica e CI completa, o pacote pode ser integrado somente à branch DEV. O fluxo comercial continua NO-GO até os gates CORE/SEG/DBA/AUT/LGPD.
