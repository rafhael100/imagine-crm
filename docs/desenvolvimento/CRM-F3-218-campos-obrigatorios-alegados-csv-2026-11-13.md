# CRM F3-218 — Verificação local de campos alegados como obrigatórios

Componente `PortableCsvRequiredFieldsReview` no Core, apenas em memória e com dados fictícios. Aceita uma lista **explícita** de códigos candidatos (`name`, `email`, `phone`, `origin`, `consent`, `notes`, `status`) que o chamador deseja analisar como obrigatórios. **Isso não aprova o esquema comercial nem o caráter obrigatório desses campos.**

Usa o codec CSV e o mapeamento já validados para distinguir a quantidade de campos alegados não mapeados, células mapeadas vazias e linhas que contêm alguma célula vazia dentre os campos selecionados. Não conta um campo não mapeado como célula vazia. Não retorna títulos, campos alegados, valores, linhas, hashes nem arquivos. Uma coluna `consent` preenchida não valida consentimento LGPD.

Os sinalizadores de esquema, consentimento, tenant, autorização, importação, mesclagem e persistência permanecem `false`. Testes cobrem caso misto, completo, não mapeado, sem cabeçalho, sem dados, entradas inválidas e não vazamento. Gates #27 SEG/DBA, #259 CORE/LGPD, #416 UX e arquitetura operacional IN-118 continuam NO-GO.
