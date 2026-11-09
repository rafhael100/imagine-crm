# F3-21 — Mapeamento de colunas em memória, com fixtures sintéticas

**Data:** 09/11/2026. **Mandato:** [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35); continuidade DEV controlada e [CRM-REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), cujas pendências DAT-P01..12 seguem abertas.

## Objetivo

Preparar a fase de revisão de estrutura de uma importação sem receber arquivos de clientes, reescrever dados ou presumir campos de um schema real. A classe `SyntheticColumnMappingInspector` inspeciona até oito **nomes técnicos fictícios** de origem, até oito nomes técnicos fictícios de destino e correspondências explícitas entre posições **1-based**.

- Retorna `SyntheticColumnMappingSummary`, imutável, apenas com número de colunas, pares numéricos canonizados e posições não mapeadas; nunca retorna nomes de campos ou valores.
- Exige listas densas, rótulos técnicos estritos e únicos e pares `{source,target}` com inteiros no intervalo válido. Uma origem não pode ter dois destinos e um destino não pode receber duas origens.
- Não completa lacunas por suposição, não faz deduplicação de pessoas e não valida semântica de campos, conteúdos CSV, LGPD, consentimento, tenant ou quotas. Mesmo `isCompleteOneToOne() === true`, `canImport() === false`.
- O teto de oito colunas é **limite defensivo do laboratório**, não um limite comercial de importações ou campos personalizados.
- Exceções têm mensagem genérica, sem refletir nomes recebidos.

## Testes

`SyntheticColumnMappingInspectorTest` cobre mapeamento completo e incompleto, ordenação canônica, limite exato de oito posições, duplicações, referências fora do intervalo, índices não inteiros, pares malformados e tentativas de injetar `allow`. Requer CI F0 e PostgreSQL/RLS verde na SHA exata para eventual integração **somente DEV**.

## Bloqueios para fluxo real

Ficam **fora do escopo**: upload, parsing de arquivos, XLSX, conversão de tipo, prévia de dados reais, confirmação humana, gravação/atualização de leads, mesclagem, authorization/entitlement, políticas LGPD, integração IN-118/INT-08, links de download e fila de importação. São necessários os gates CORE/SEG/AUT/INT/LGPD, revisão dos esquemas e permissões por tenant/recurso/campo.

**Nenhuma alteração em `main`, VPS ou produção.** Este contrato não é prova de que uma operação de importação está pronta.
