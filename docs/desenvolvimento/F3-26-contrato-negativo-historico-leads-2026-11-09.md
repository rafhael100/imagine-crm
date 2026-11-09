# F3-26 — Fronteira sintética para leitura de histórico e proveniência de leads

**Data:** 09/11/2026. **Status:** experimento DEV com dados inventados, nenhum histórico de cliente. **Origem:** [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35) e [CRM-REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), especialmente DAT-R20, DAT-R04, DAT-R08, DAT-R09 e pendências DAT-D/P. Não constitui aceite de schema de eventos nem autoriza consulta real.

## Entrega

- `SyntheticLeadHistoryClaim`: afirmações **não autenticadas** de tenant, lead, evento, ator opcional, posição e tipo. Aceita apenas referências UUID sintaticamente válidas e seis rótulos fictícios: `created`, `assigned`, `note_added`, `stage_changed`, `archived`, `restored`. Não aceita notas, conteúdo, campos de contato, consents, tokens ou payload de cliente. Não é inventário oficial de eventos.
- `SyntheticLeadHistoryInspector`: classificador puro para **lista de 1 até 32 afirmações** (fatias de laboratório). Identifica escopo de tenant/lead divergente, evento repetido, ausência de referência do ator, lacunas/reordenação da posição declarada. Não tenta consertar sequência, mesclar identidades nem concluir origem.
- `SyntheticLeadHistoryAssessment`: expõe apenas um código fixo em `signal()`; `canReadHistory`, `canExportHistory`, `canWriteHistory` e `canProveActorOrChronology` são **sempre false**.
- Testes sintéticos cobrem ordem, duplicidade, troca de tenant ou lead, evento forjado, ator ausente, amostras grandes/malformadas e não divulgação dos UUIDs no sinal.

## Contrato negativo e limites

| Resultado | Significado limitado |
|---|---|
| `CONTRACT_INVALID` | Estrutura/identificador esperado fora do conjunto de laboratório |
| `HISTORY_EVIDENCE_MISSING` | Nenhuma afirmação entregue |
| `HISTORY_SCOPE_MISMATCH` | Referência declarada não coincide com o escopo esperado |
| `EVENT_REFERENCE_REUSED` | Reuso de identificador de evento na mesma fatia |
| `ACTOR_REFERENCE_MISSING` | Não houve referência ao autor declarada |
| `HISTORY_ORDER_UNVERIFIED` | Ordem ou continuidade declaradas inconsistentes |
| `PROVENANCE_UNVERIFIED` | Até uma lista sintaticamente coerente **não** é prova de existência, autor, origem ou integridade |

As posições são **alegações de sequência** e não timestamps ou registros oficiais. A faixa de 32 não é limite comercial nem de retenção; serve apenas a testes em memória. Um UUID bem formado, vínculo de tenant coincidente ou evento com autor alegado não autentica ninguém e nunca produz `ALLOW`.

## Próximo gate verdadeiro

Para histórico **real** ainda são exigidos: catálogo DAT de tipos/campos e responsáveis; separação entre atividade comercial, auditoria, relatórios, IA e logs; proveniência confiável de eventos; escopo e field-level authorization CORE02; política de retenção/LGPD; leitura paginada segura; PostgreSQL e isolamento entre tenants com revisão independente SEG/DBA; testes concorrentes e homologação de owners. O histórico comercial jamais deve herdar a janela de 18 dias das filas temporárias de Sites.

**Não altera** schemas, migrations, SQL, ORM, API, grants, papéis, `main`, VPS, produção ou banco com clientes. Somente DEV e CI; gates CORE, DAT, SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), INT, LGPD e FIN/CON continuam abertos.
