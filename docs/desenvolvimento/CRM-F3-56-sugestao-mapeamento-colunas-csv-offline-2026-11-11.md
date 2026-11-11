# CRM F3-56 — sugestão conservadora de colunas em prévia CSV sintética

**Data:** 11/11/2026. **Status:** código Core + testes, apenas DEV/laboratório.

## Evolução funcional

Antes, a prévia mapeada F3-21/F3-55 precisava receber manualmente os pares de posições entre colunas. Agora `SyntheticColumnMappingInspector::suggestExactMatches` propõe automaticamente apenas correspondências de **rótulos técnicos idênticos**, sensíveis a maiúsculas, um-para-um, mantendo o resultado restrito a índices numéricos (base 1).

`SyntheticCsvImportPreview::inspectSuggested(csv, targets)` executa **uma única leitura pelo parser canônico**, compara o cabeçalho validado com os alvos técnicos e devolve `SyntheticImportMappedPreviewSummary`, com colunas sem correspondência, contagem de linhas e posições de fórmula perigosa. A fachada de compatibilidade `SyntheticCsvPreviewDecoder::previewSuggested` entrega a mesma semântica.

Sem rótulos comuns, o relatório simplesmente traz zero posições e todos os índices sem mapeamento: o sistema **não inventa** conversões por semelhança, apelido, capitalização, IA nem conteúdo das linhas. Com todos os rótulos correspondentes, `isMappingComplete()` ainda não significa que exista autorização. Todas as três camadas continuam com `canImport=false`.

## Testes

`SyntheticCsvAutoMappingTest` cobre sugestões reordenadas, parciais e vazias; nenhuma correspondência por nome similar; repetição/má-formação de rótulos, objetos e dados de fórmula; BOM, valores sintéticos, paridade entre interfaces, sinalização de fórmulas e negativa de importação mesmo quando o mapeamento de posições é completo.

## Limites

É uma recomendação **estrutural de posições**, não um esquema comercial de campos do CRM. Não cria mapeamento de dados pessoais, pipeline operacional, consentimento, token, API, upload, leitura de cliente, gravação, migração, permissões ou deploy. Gates IAM/CORE/SEG/DBA/LGPD/FIN continuam pendentes. `main` e VPS não devem ser modificados; merge possível apenas em DEV após diff e CI verde na SHA exata.
