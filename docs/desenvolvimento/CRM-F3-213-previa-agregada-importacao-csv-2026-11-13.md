# CRM F3-213 — prévia agregada real de CSV em memória, sem retorno de dados

**Data:** 13/11/2026. **Escopo:** IN-118, F3-02. **Estado:** código funcional de análise de pequenos exemplos em memória, **não conectado a banco, HTTP, storage, API, jobs ou autorização de clientes**.

## O que foi entregue

`PortableCsvImportPreview::inspect($csv, $delimiter, $bindings, $hasHeader)` compõe diretamente os três módulos funcionais anteriores:

1. `PortableCsvTableCodec::decode` interpreta texto UTF-8 delimitado e validado, até 100 linhas/32 colunas e limites de bytes do codec.
2. `PortableCsvFieldMapping::map` aplica seleção explícita de campos em memória, separando um cabeçalho **apenas alegado** e preservando todas as linhas, sem deduplicação.
3. A mesma regra estrita de recusa de fórmulas da função `PortableCsvTableCodec::encode` é aplicada **individualmente e apenas em memória** a células de dados de origem e de destino mapeado, para informar contagens de riscos no cenário local. Não há uma segunda regex cuja interpretação pudesse divergir do codec de saída.

**Resultado:** somente contagens agregadas (colunas, linhas, campos selecionados e omitidos, campos mapeados vazios, células que o codec recusaria na origem e entre as selecionadas), códigos de avisos enumerados e seis gates operacionais não cumpridos. **Nenhum conteúdo de célula, valor de contato, nome de pessoa, header, arquivo, token ou identificador é devolvido.**

Ao detectar qualquer coluna `consent`, o relatório registra que uma célula `true` **não comprova consentimento legal**. Avisos distinguem uma fórmula no campo omitido de uma fórmula em campo selecionado. Um cenário sem alertas de conteúdo continua sem permissão ou verificação de arquivos reais.

## Proibições

Sempre `authorized=false`, todas as flags `*_verified=false`, `can_import/can_export/can_merge/can_confirm_consent/can_download/can_write_file/can_persist=false` e `informational_only=true`. Código sem filesystem, DB, rede, sessões, LGPD real ou API. Não define a política de normalização, CSV formula injection em planilhas externas, cabeçalhos, limites/quotas contratuais, escopo por cliente ou transação de importação.

## Testes

`PortableCsvImportPreviewTest.php`: tabela limpa mas sem autorização, colunas omitidas, fórmula em coluna omitida versus selecionada, cabeçalho alegado e inexistente, valores vazios, separador ponto e vírgula, consentimento ainda não comprovado, dataset sem cabeçalho, entrada inválida e **zero vazamento dos valores de teste**. Não há contato com dados reais, VPS ou produção.

**Merge DEV somente após 4 workflows obrigatórios F0/PostgreSQL-RLS/Sites/Agentes SUCCESS na HEAD exata, diff limitado e ausência de exposição pública.** A interface de importação #429 e arquitetura IN-118 #434 seguem DRAFT; reviews de segurança/DBA #27, integração/LGPD #259 e UX #416 não foram liberadas.
