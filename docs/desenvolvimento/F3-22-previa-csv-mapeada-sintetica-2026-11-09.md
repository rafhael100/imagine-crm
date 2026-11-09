# F3-22 — Prévia CSV e mapeamento de colunas no mesmo fluxo sintético

**Data:** 09/11/2026. **Mandato:** continuidade de [F3-21 / PR #74](https://github.com/rafhael100/imagine-crm/pull/74) e [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35). **Apenas DEV experimental.**

## O que muda

O decodificador limitado de CSV sintético `SyntheticCsvPreviewDecoder` mantém seu contrato original `preview()` e passa a oferecer `previewMapped()` com duas entradas adicionais: nomes técnicos sintéticos de campos de destino e pares de posições 1-based.

A **mesma leitura** extrai o cabeçalho real do CSV de laboratório, valida os limites existentes e, em memória, envia esse cabeçalho diretamente ao `SyntheticColumnMappingInspector` para revisão do mapa. Isso evita que o chamador apresente um cabeçalho declarado diferente daquele realmente decodificado. A estrutura devolvida `SyntheticImportMappedPreviewSummary` contém **somente dois resumos agregados**, sem células nem nomes de colunas.

- Permanecem os limites F3-20: **32.768 bytes UTF-8, 50 linhas de dados, 8 colunas e 512 bytes/célula**; sem arquivos enviados por cliente, servidor HTTP ou disco.
- A estrutura do mapa permite pares parciais para identificar lacunas e usa índices 1-based, origem/destino exclusivos e ordenação estável.
- Posições com risco de fórmula são sinalizadas, nunca executadas nem automaticamente corrigidas; nenhum registro é importado.
- Tanto mapeamento completo como incompleto continuam com `canImport() === false`.
- Erros retornam texto genérico sem refletir nomes ou conteúdo das colunas.

## O que ainda falta para importação real

Este laboratório **não** recebe arquivos de cliente, interpreta XLSX, cria tarefas, converte tipos, valida finalidade LGPD, identifica autenticação/organização ou autoriza gravações. Ele não faz matching semântico com um schema CRM homologado nem resolve duplicatas/mesclagem de pessoas. Futuro fluxo real exige escolha confirmada do usuário, direitos por tenant/campo, controle de arquivo, proteção contra formula injection, auditoria e parecer SEG/DBA, além dos gates CORE/AUT/INT/LGPD.

## Verificação

`SyntheticImportMappedPreviewTest`: integração dos dois contratos, mapa parcial, linhas zero, cabeçalho inválido, rejeição de índices forjados/duplicados, retorno sem conteúdo sensível e ausência de ações. Regressões anteriores de `SyntheticCsvPreviewDecoderTest` e `SyntheticColumnMappingInspectorTest` permanecem obrigatórias.

**Somente DEV**, sem `main`, migrations, grants, API, VPS, produção, custos ou dados reais. Merge condicionado a revisão integral e CI F0/PostgreSQL verde na SHA exata.
