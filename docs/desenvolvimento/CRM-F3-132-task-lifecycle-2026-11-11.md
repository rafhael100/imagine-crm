# CRM F3-132 — Ciclo de tarefas comerciais e prioridade, com prazos UTC

**Data:** 11/11/2026. **Escopo:** PHP Core puro e PHPUnit na DEV, sem tarefas reais nem background jobs. **Requisitos:** CRM-REV-06 VEN-R20/R21; critérios de permissão e calendário ainda a homologar.

## Implementação de produto

\`SyntheticTaskLifecycleInspector\` é um redutor determinístico que avalia até **24 eventos inventados de uma tarefa sem identidade**. Cada evento inclui apenas revisão sequencial iniciando em 1, ação tipada, timestamp UTC exato e, conforme a ação, prazo, prioridade ou motivo codificado.

- create: inicia pending, exige prazo UTC e prioridade low, normal, high ou urgent.
- start: permite passar de pending a in_progress.
- complete: permite passar somente de in_progress a completed.
- cancel: permite cancelar uma tarefa aberta/em andamento com motivo duplicate ou not_needed.
- reopen: permite reabrir completed ou canceled com justificativa correction ou new_followup, retornando a pending.
- reschedule e reprioritize: alteram, somente no cenário local, prazo e prioridade de tarefa não encerrada. Exigem motivos técnicos explícitos schedule_change e priority_change e rejeitam alteração para o mesmo valor.

Eventos fora de ordem, timestamps repetidos, revisões faltantes/repetidas, transições de tarefas encerradas, campos extras ou dados pessoais são recusados por erro genérico. A saída **não contém eventos originais, identificadores, descrições, prazos recebidos, nomes ou conteúdo de clientes**.

## Integração interna

O cálculo da situação do prazo (upcoming, due_now, overdue) usa diretamente \`SyntheticFollowUpQueue\`, preservando a semântica já testada para follow-ups. Tarefas declaradas concluídas ou canceladas mostram fase closed e não geram alerta. Uma suíte também recebe a ocorrência de fevereiro do componente \`SyntheticRecurringTaskSchedule\` e verifica o prazo da tarefa no instante exato.

## QA e controles

\`SyntheticTaskLifecycleInspectorTest\` cobre início, conclusão, cancelamento, reabertura, alteração de prioridade e prazo, UTC no segundo exato, 24 revisões, integração com recorrência e follow-up, entradas malformadas, alterações indevidas e saída sem PII.

As propriedades history_verified, task_identity_verified, tenant_verified, priority_policy_verified, due_policy_verified, authorized, can_create_task, can_update_task, can_complete_task, can_notify e can_persist permanecem **falsas**. Um evento complete é apenas alegação de laboratório, não trabalho confirmado ou instrução autorizada.

Não há tabela, fila, endpoint, agendador, associação a cliente, calendário local/feriados, integração, lembrete, WhatsApp, assinatura ou cobrança. CORE/SEG/DBA/LGPD permanecem NO-GO para implantação operacional. Sem dados reais, IAM/ALLOW, despesas, VPS, main ou produção.
