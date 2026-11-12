# CRM F3-118 — Unicidade de eventos fictícios de venda

**Data:** 11/11/2026. **Ambiente:** DEV, sem acesso a registros reais. **Rastreabilidade:** CRM-REV-06 VEN-R23/R24, VEN-D04/VEN-P07.

## Entrega de código

`SyntheticSalesEventUniquenessInspector` recebe até 20 alegações **inteiramente fabricadas** de evento de venda, cada uma com um `event_reference` com formato UUID (somente referência sintética) e `amount_cents` BRL inteiro. Rejeita entradas fora da lista canônica, UUID nulo/malformado, campos adicionais, dinheiro fracionário/negativo, somas que excedem `Number.MAX_SAFE_INTEGER` e **qualquer UUID repetido, inclusive com diferenças de maiúsculas/minúsculas**. Não tenta "consertar" duplicatas apagando uma delas: falha de forma genérica.

O relatório contém somente contagem de alegações, contagem de referências distintas e soma declarada em centavos, **sem expor qualquer UUID, valor unitário, empresa, usuário, lead ou oportunidade**.

## Proteções contra confusão financeira

A soma declarada **não é faturamento, receita reconhecida, venda efetiva ou meta atingida**. Verificar que duas strings UUID são distintas não prova que representam vendas diferentes, que uma venda ocorreu ou que não foi reaberta/cancelada. O resultado informa permanentemente `recognized_revenue_cents=0`, `verified_event_count=0`, `provenance_verified=false`, `tenant_verified=false` e nega reconhecimento de receita, atualização de metas, emissão fiscal ou gravação.

Testes adversariais cobrem duplicação com diferença de caixa, UUID nulo, 20+1 entradas, valor máximo seguro, soma excessiva, mistura de tipos, extras e PII, além de ausência de referências UUID na saída.

## Gate de operação

A deduplicação real exige identificação de oportunidade/evento, tenant autenticado, origem transacional, idempotência, ciclo ganho/reaberto/perdido, compensação, controle de concorrência, trilha de auditoria e parecer de VEN-P07, CORE/SEG/DBA/LGPD.

Nenhuma API, migration, consulta SQL, histórico persistido, transição de negócio, emissão, cobrança, n8n, despesa, VPS, `main` ou produção.
