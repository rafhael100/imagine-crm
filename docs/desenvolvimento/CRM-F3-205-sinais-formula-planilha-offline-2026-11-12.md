# CRM F3-205 — sinais de injeção de fórmulas em planilhas (offline)

**Data:** 12/11/2026. **Origem:** IN-118, issue #35, risco de `CSV formula injection` nos fluxos CSV/XLSX. **Escopo:** biblioteca PHP pura com dados de laboratório exclusivamente fictícios, sem leitura de arquivos e sem operação com clientes.

## Entrega
`SyntheticSpreadsheetFormulaSignalInspector::inspect` inspeciona uma lista estritamente contígua de até **20 células fictícias**, cada uma com no máximo **512 bytes de UTF-8 válido**. Não recebe cabeçalhos, schema, nomes de pessoas, chaves, tenant ou identificadores e **não retorna o texto de nenhuma célula**.

A revisão conservadora detecta:
- prefixos de fórmula `=`, `+`, `-`, `@` — inclusive números negativos, que intencionalmente geram falso positivo para não prometer segurança;
- whitespace, tab, CR/LF, BOM, NBSP, caracteres invisíveis, marcas de direção Unicode e formatadores de prefixo que podem ocultar sinais de fórmula;
- caracteres Unicode de compatibilidade visual `＝`, `＋`, `－`, `＠`, sem pressupor conversão segura;
- alegações de escape com apóstrofo inicial, cujo significado varia por cliente de planilha, e células multilinha.

Saída agregada com códigos fixos e contagens (sem posição, texto, amostra, hash ou normalização do campo). Strings binárias, UTF-8 inválido, byte NUL, tipos estranhos, 21+ células e comprimento 513+ bytes são rejeitados por erro constante, sem ecoar valores.

## Limitações e testes
Esta é apenas uma **triagem de risco**, não um `CSV writer`, parser, neutralizador de fórmulas, escape seguro universal, validação de payload real, verificação de formato XLSX ou homologação em Excel/LibreOffice/Google Sheets. A ausência de alerta **não equivale** a dados seguros; diferenças de interpretação, delimitador, aspas, locale, normalização, importadores e conversões continuam pendentes para revisão.

`informational_only=true`, `authorized=false`, todas as flags `*_verified=false` e todas `can_*=false`, incluindo `can_sanitize`, `can_export`, `can_import`, `can_download`, `can_write_file` e `can_persist`. Nenhum arquivo, endpoint, storage, fila, banco, rede ou permissão é criado.

PHPUnit prova sinais em fórmulas, tabs, BOM, Unicode invisível/direcional, negativos, multiline, apóstrofo, limites e rejeições; a matriz integrada `SyntheticCommercialCapabilityDenyMatrixTest` cresce de **46 para 47** laboratórios sem efeitos. Merge na DEV apenas com quatro workflows F0/PostgreSQL-RLS/Sites/Agentes `success` no SHA exato.

O fluxo de exportação/importação real não pode consumir essa revisão antes da especificação técnica, revisão SEG/DBA independente (#27), LGPD e autorização da integração (#259). `main`, VPS e produção permanecem intocadas.
