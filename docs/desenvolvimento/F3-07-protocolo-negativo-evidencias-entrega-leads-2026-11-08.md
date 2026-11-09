# CRM Imagine — F3-07 — Evidências negativas de entrega de leads

**Data:** 08/11/2026. **Escopo:** código PHP puro de laboratório, testes sintéticos e documentação, somente DEV. Vinculado à [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [IN-124](../planejamento/IN-124-integracao-inteligente-formularios-leads.md), [IN-148](../planejamento/IN-148-reconciliacao-inteligente-leads.md) e [IN-149](../planejamento/IN-149-protocolo-entrega-confirmacao-leads.md).

## Objetivo

Evitar confundir **resposta HTTP da API**, **resposta HTTP do CRM** e **prova confiável de persistência**. `SyntheticDeliveryEvidenceInspector` examina apenas identificadores **sintéticos já construídos** e alegações de status/registro, produzindo um motivo neutro. Ele **não valida origem, assinatura, banco, API ou identidade real**, logo `canAcknowledgeVisitor()`, `canMarkDeliveredToCrm()` e `canRetryAutomatically()` são **sempre falsos**.

- A API principal e a de contingência são rotas descritivas; 200/201 recebidos sem prova durável **não autorizam afirmar recebimento**.
- HTTP 202 significa aceitação/intermediação, **não** persistência final no CRM. Timeout ou resposta perdida ficam incertos, sem reenvio cego.
- Uma referência UUID de registro CRM apenas passa por validação de formato, **não constitui consulta ou evidência de gravação**.
- O contexto da submissão é tripla `organização/vínculo/envio`. Trocar tenant, vínculo CRM ou ID do envio torna a alegação incompatível. Duas submissões com conteúdo idêntico não são mescladas pelo conteúdo.
- Mesmo quando todas as alegações parecem favoráveis, o resultado exige **provas técnicas independentes**; nunca cria um estado `entregue`.

## Controles e pendências de implementação real

Faltam contrato versionado de API, autenticação/assinatura entre serviços, origem validada, vínculo tenant legítimo resolvido no servidor, armazenamento durável independente em API principal e secundária, mecanismo de confirmação atômica no CRM, ledger idempotente, concorrência/failover, logs minimizados, erros de rede, processamento/retentativa, rate limit, LGPD e revisão independente SEG/DBA.

Os prazos aprovados (3 + 15 dias para entregas pendentes, 24h para limpeza de entregues e 90 dias para auditoria minimizada) **não são implementados** neste recorte.

**Nenhuma alteração** em Laravel funcional, rotas, banco, filas, migration, `ALLOW`, `main`, VPS, produção ou despesa. A CI sintética não concede GO para leads de clientes ou homologação dos 67 gates CORE.
