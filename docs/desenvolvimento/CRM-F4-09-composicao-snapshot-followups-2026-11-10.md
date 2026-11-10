# F4-09 — Composição offline do snapshot de follow-ups

A composição `FollowUpSnapshot::build` combina o classificador sintético de prazos (F4-06) com o resumo de volume (F4-07) para manter contagens e grupos consistentes no mesmo instante explícito de referência. Recebe somente dados construídos pelo chamador e não acessa o relógio do sistema, bancos, clientes, rede ou arquivos.

**Evidência unitária proposta:** snapshot com três faixas, entrada vazia, identificador repetido, ausência de offset e precisão subsegundo. Os testes precisam rodar no CI antes de merge.

**Não autorizado:** entidade persistente, tenant access, controlador, endpoint, fila, notificação, autoatribuição, `ALLOW`, deploy, VPS, produção e mudança na `main`. A projeção não indica atendimento efetivo, SLA ou encerramento de tarefa. Gates CORE/SEG/DBA continuam pendentes.
