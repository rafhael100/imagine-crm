# CRM F3-54 — controles embutidos em células de planilha

**Data:** 11/11/2026. **Tipo:** correção real de pré-validação de texto, não liberação de exportação.

## Lacuna identificada

O `SpreadsheetTextRiskGuard` do CRM já bloqueava TAB, CR, LF, DEL e outros bytes de controle **no primeiro caractere**. Mas um conteúdo com letras seguidas por TAB/LF poderia passar na verificação de risco, apesar de merecer tratamento específico por aplicativos de planilha. O parser CSV sintético F3-53 aceita corretamente células entre aspas com quebras de linha, mas a compatibilidade sintática do CSV não equivale à segurança do conteúdo como célula de planilha.

## Correção

O guard passa a classificar **qualquer** ASCII control byte (`U+0000..U+001F` e `U+007F`) mesmo se estiver no meio do texto como risco de planilha. Mantém a política anterior de fórmulas precedidas por Unicode invisível e UTF-8 inválido. Não sanitiza nem reescreve o valor.

Testes negativos de TAB/LF/CR/DEL embutidos e combinações Unicode foram acrescentados. O teste F3-53 demonstra agora o comportamento completo: uma célula CSV quoted com `LF` embutido é **CSV válido**, mas a prévia retorna sua coordenada como risco de exportação, sem expor seu conteúdo.

## Limites

Conservador: isso pode marcar alguns textos multilinha perfeitamente benignos. É preflight, não prevenção universal de CSV injection, nem solução de formatação/quoting/escaping para exportador de produção. Ainda não há API, upload, exportação/importação de clientes, permissão ou armazenamento; não ocorreu alteração de `main`, VPS, produção nem custo. CORE/SEG/DBA, IAM, LGPD e gates de exportação permanecem NO-GO.
