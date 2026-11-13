# F3-220 — proteção conservadora de prefixos Unicode em CSV

Melhoria defensiva do `PortableCsvTableCodec::encode` existente (sem alterar `decode`, APIs ou a assinatura do codec). Antes, apenas grupos específicos de espaços/marcadores invisíveis eram considerados para recusar prefixos de fórmula. Agora o analisador também cobre separadores Unicode `Z`, formatos `Cf`, marcas combinantes `M`, controles `U+0001..U+0020/U+007F..U+009F`, operadores ASCII/fullwidth e apóstrofos repetidos. Essa política continua **deliberadamente restritiva** e pode rejeitar valores textuais legítimos; a string original nunca é automaticamente "corrigida".

O novo teste `PortableCsvUnicodePrefixHardeningTest.php` verifica rejeição de oito variantes fictícias, preservação de texto comum com Unicode e propagação da contagem de recusa para o relatório F3-213 quando a coluna arriscada é omitida. Não assume que a ausência de alerta valida Excel, Sheets ou LibreOffice. Não valida identidade, tenant, LGPD, direito de exportar nem operação com dados reais. Sem rota, migração, file I/O, SaaS operacional ou produção.

Gates #27, #259, #416 e IN-118 permanecem abertos. Integrar só na DEV após revisão de diff e quatro workflows CI na HEAD exata.

**Retrocompatibilidade:** texto neutro contendo separadores ou marcas Unicode continua serializado sem modificação; rejeições acontecem apenas quando uma sequência inicial de marcadores é seguida de operadores de fórmula. O teste de round-trip cobre esse caso.
