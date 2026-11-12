# CRM F3-123 — Transições de oportunidade e reabertura com justificativa

**Data:** 11/11/2026 · **Ambiente:** DEV, Core PHP sem armazenamento · **Rastreabilidade:** CRM-REV-06 VEN-R02/R03/R04/R05/R06, VEN-T01..04 e VEN-P03/P07 (homologação pendente).

## O que o código faz

`SyntheticOpportunityTransitionLedger` avalia sequências fictícias de até 24 eventos `create`, `move`, `win`, `lose` e `reopen` sobre **uma única oportunidade inventada sem identidade**. Todo evento exige revisão inteira consecutiva a partir de 1, timestamp UTC válido e estritamente crescente, etapa de laboratório de 0 a 3, um bit de confirmação e os campos de motivo de perda ou reabertura (quando aplicáveis). Não admite campos extras nem PII.

- Criar inicia em `open` e numa etapa informada.
- Mover exige `open` e etapa diferente; qualquer deslocamento entre as quatro etapas de teste é descritivo, sem permissões de etapa homologadas.
- Ganhar/perder exige confirmação fictícia `true`, status aberto e mesma etapa. Uma perda exige motivo codificado: `price`, `timing`, `competition`, `no_response` ou `other`.
- Reabrir exige status `won` ou `lost`, etapa inalterada e justificativa codificada `correction`, `customer_returned` ou `new_context`. Não pode reabrir oportunidade já aberta, nem alterar etapa em uma reabertura.
- Eventos duplicados, revisões repetidas, salto de revisão, eventos invertidos, alterações após encerramento e argumentos privados são recusados sem refletir os dados.

O relatório agrega etapas, mudanças, ganhos alegados, perdas alegadas, reaberturas e motivos de perda. O histórico fornecido **não é devolvido**, nem timestamps, identificadores ou texto livre. Após ganhar, reabrir e ganhar novamente, os dois eventos continuam sendo alegações; `recognized_wins` e `recognized_revenue_cents` permanecem **zero**.

## Distinção fundamental de concorrência

O número sequencial de revisão **não é** bloqueio transacional, compare-and-swap em PostgreSQL, autoria, permissão por tenant nem trilha auditável. A implementação apenas detecta incoerências *dentro da lista inventada*. Nenhum módulo deve deduzir de `confirmed=true` que houve confirmação de usuário real.

`history_verified=false`, `tenant_policy_verified=false`, `concurrency_verified=false`, `confirmation_verified=false`, `authorized=false` e **todas** as flags `can_*` continuam falsas. Não libera movimentação, reconhecimento de receita, metas, emissão ou persistência.

## QA e gates

`SyntheticOpportunityTransitionLedgerTest` cobre ciclo ganhar → reabrir → perder → reabrir → ganhar, limites de 24 eventos, contraexemplos de revisões fora de sequência, motivo ausente ou conflitante, justificativa, alteração de etapa indevida, UTC inválido e PII. As proteções de renda são testadas mesmo com dois ganhos alegados.

VEN-P03/P07 e CORE/SEG/DBA/LGPD continuam NO-GO para implantação operacional. Nenhuma API, SQL, IAM/ALLOW, evento de cliente, VPS, gasto, `main` ou produção.
