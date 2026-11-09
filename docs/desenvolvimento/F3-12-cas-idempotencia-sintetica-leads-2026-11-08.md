# CRM Imagine — F3-12 — Pré-condições de CAS de idempotência (laboratório)

**Data:** 08/11/2026. **Fase:** código PHP puro, testes sintéticos e documentação; sem persistência. Referências: [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [IN-124](../planejamento/IN-124-integracao-inteligente-formularios-leads.md), [IN-148](../planejamento/IN-148-reconciliacao-inteligente-leads.md), [IN-149](../planejamento/IN-149-protocolo-entrega-confirmacao-leads.md).

## Modelo investigado

- A chave lógica proposta é o par **organização + submissão**, não nome, telefone, e-mail ou conteúdo. O `syntheticLedgerFingerprint()` é somente um hash determinístico para comparar fixtures; **não é token de segurança**.
- A primeira reserva deve congelar **vínculo, site, formulário e destino CRM**. Uma tentativa subsequente com a mesma submissão e outro destino é um conflito de contexto, não uma nova entrega.
- `SyntheticIdempotencySnapshot` modela uma cópia hipotética de registro com fase e versão. `SyntheticIdempotencyCasInspector` compara a versão esperada e rejeita snapshot desatualizado.
- Fase `open` e versão coincidente produzem código **`ATOMIC_STORE_REQUIRED`** e um número de versão proposto. **Isso NÃO reserva nada**; duas chamadas concorrentes sem banco podem propor o mesmo número. Somente unique constraint, CAS transacional, isolamento adequado, bloqueio concorrente e provas de durabilidade podem selecionar uma vencedora real.
- Fases `reserved`, `outcome_unknown`, `delivered_claimed` e `expired_claimed` não autorizam reprocessamento cego nem alteração de estado.
- Retorno sempre com `canCommitReservation()=false`, `canRetryDelivery()=false` e `canCreateLead()=false`.

## Requisitos de um ledger persistente futuro — NÃO IMPLEMENTADOS

1. Entidade/escopo estritamente multiempresa, com tenant resolvido pelo backend e vínculo assinado/validado.
2. Unique key adequada ao **envio lógico**, imutabilidade das referências de vínculo/site/form/destino e restrições físicas contra escrita cross-tenant.
3. Operação CAS indivisível com versão/fencing token e posse de lease, recuperação após crash e disputa por workers de APIs independentes.
4. Consulta confiável do estado CRM por ID idempotente antes de nova tentativa em resultado incerto, controle de duplicidade na chegada ao destino.
5. Reconciliação de duas infraestruturas independentes com partição de rede, latência, respostas tardias e restauração de backups.
6. Descarte sujeito a estado verificado, retenção legal, cronologia e auditoria, com 3+15 dias/24h/90 dias já definidos normativamente.
7. Execução real de testes de contenção no PostgreSQL 16 com roles sem BYPASSRLS, observabilidade e revisão independente da [Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27).

Não há SQL, migrations, banco, API, jobs, armazenamento, `ALLOW`, deploy, custos, main ou dados reais neste lote. **Simulação de duas leituras não equivale a teste real de concorrência ou idempotência persistente**. Todos os 67 gates CORE permanecem abertos.
