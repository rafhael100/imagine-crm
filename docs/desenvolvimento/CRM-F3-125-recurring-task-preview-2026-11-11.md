# CRM F3-125 — Recorrência ilustrativa de tarefas comerciais

**Data:** 11/11/2026 · **Escopo:** PHP puro e PHPUnit em DEV, sem agendadores ou tarefas reais · **Referências:** CRM-REV-06 VEN-R20/R21 e pendências de política por tenant.

## Funcionalidade implementada

`SyntheticRecurringTaskSchedule` calcula até **16 ocorrências futuras ou passadas inventadas** de uma tarefa, a partir de um instante UTC canônico, com cadência `daily`, `weekly` ou `monthly`. O resultado contém a lista ordenada de instantes UTC, contagem de ocorrências anteriores ao instante observado, contagem exatamente no instante observado, contagem futura e a próxima data futura.

A recorrência mensal preserva o **dia âncora original**: 31 de janeiro de 2026 → 28 de fevereiro → 31 de março → 30 de abril, em vez de deslocar todos os meses seguintes para o dia 28. Em ano bissexto, 31/01/2028 → 29/02/2028 → 31/03/2028. Rotinas diárias e semanais mantêm a hora UTC e atravessam meses/anos. Somente instantes válidos de 2000–2099 são aceitos, inclusive para todas as ocorrências calculadas.

## Evidências

`SyntheticRecurringTaskScheduleTest` cobre fim de mês, fevereiro bissexto, cadência diária atravessando o réveillon, semanal, calendário âncora sem deriva, 16 ocorrências, limite superior de ano, tipos inválidos, carimbos UTC não canônicos, e mensagens de erro sem vazamento.

O teste converte as ocorrências em **fixtures anônimos** de `SyntheticFollowUpQueue` já integrado e verifica que as contagens de atrasados / vencendo agora / futuros coincidem entre os dois módulos.

## Limites e ressalvas

Não existe tarefa real, identidade, empresa, proprietário, prioridade, exceção de feriado, fuso local, horário de verão, política de fim de semana, último dia útil ou regra de recorrência configurada por tenant. As regras de fim de mês aqui definem **somente a aritmética do laboratório**, não uma decisão comercial homologada.

`calendar_policy_verified=false`, `task_identity_verified=false`, `authorized=false` e `can_create_task/can_schedule_job/can_notify/can_persist=false` sempre. Nenhum cron, n8n, API, SQL, e-mail, WhatsApp, dados reais, gastos, VPS, `main` ou produção.
