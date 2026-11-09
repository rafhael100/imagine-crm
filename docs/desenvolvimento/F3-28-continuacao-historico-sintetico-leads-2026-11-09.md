# F3-28 — Continuidade de fatias de histórico sintético, sem autorização

**Data-base:** 09/11/2026. **Natureza:** contrato PHP puro inerte de **negação e classificação de inconsistências**, referência [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35) e [F3-26](F3-26-contrato-negativo-historico-leads-2026-11-09.md).

## Problema limitado

O `SyntheticLeadHistoryInspector` existente avalia uma única lista de até 32 *alegações* de eventos fictícios. Duas páginas vistas separadamente podem ter posições localmente consecutivas, mas apresentar **duplicação de identificadores** ou **lacuna/sobreposição** entre elas. Esses sinais precisam de inspeção específica sem promover nenhum dado a um histórico confiável.

## Contrato

`SyntheticLeadHistoryContinuationInspector::inspect(expectedTenant, expectedLead, earlierSlice, laterSlice)`:

1. Exige para **cada** fatia as validações existentes de identificadores UUID, referência de tenant/lead, ator alegado, listas não vazias, no máximo 32 eventos, referência de evento única, posição interna consecutiva e tipos sintéticos.
2. Caso alguma fatia falhe, preserva **somente o código de recusa restrito** de `SyntheticLeadHistoryInspector`, sem conteúdo, IDs, nomes ou razões arbitrárias.
3. Detecta **reutilização de referência de evento entre fatias**: `EVENT_REFERENCE_REUSED`.
4. Detecta salto, repetição ou recuo de posição na fronteira: `HISTORY_ORDER_UNVERIFIED`.
5. Mesmo com duas fatias aparentemente consistentes (até 64 alegações totais), retorna `PROVENANCE_UNVERIFIED`, com `canReadHistory()`, `canWriteHistory()`, `canExportHistory()` e `canProveActorOrChronology()` sempre **false**.

O sinal de saída é `['code' => ...]` somente. Nenhuma fatia é concatenada, persistida, reparada ou exportada por este contrato. O nome da classe refere-se à **checagem de continuidade alegada**, não a um cursor de produção nem a um serviço de paginação real.

## Testes e bloqueios

`SyntheticLeadHistoryContinuationInspectorTest` cobre continuidade sintética, duplicação em posições distintas, buracos e overlaps, limite de 32+32, entrada vazia ou adulterada, vazamento de tenant/ator, desvio de organização/lead, falta de ator e casos extremos de posição. Os workflows F0 e PostgreSQL/RLS rodam a suíte; **não** existem novas migrations, endpoints, tabelas ou consultas SQL.

Não há identidade autenticada, política de leitura de histórico, prova de ator, auditoria oficial, retenção LGPD, clock confiável, assinatura, ordem transacional, sessão, RLS físico do histórico, armazenamento, API nem conexão com a interface UX-F3-27. A inspeção não legitima qualquer evento real. São necessárias definições de owner DAT/CORE06, CORE02-D04..D10, segregação/SEG-DBA independente [#27](https://github.com/rafhael100/imagine-crm/issues/27), LGPD, INT e homologação antes de criar operações reais.

**DEV only:** não alterar `main`, VPS, produção, gastos, clientes, permissões, `ALLOW` ou grants; PR em rascunho, diff controlado e duas CIs verdes antes de integrar na DEV.
