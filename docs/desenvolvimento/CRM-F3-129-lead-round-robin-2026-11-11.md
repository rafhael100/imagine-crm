# CRM F3-129 — Distribuição circular de leads fictícios com capacidade e proteção de atendimento

**Data:** 11/11/2026. **Escopo:** Core PHP e PHPUnit, sem servidor de tarefas ou dados reais. **Requisitos:** CRM-REV-06 VEN-R32, VEN-T16/T17, VEN-P09.

## Componente funcional

`SyntheticLeadRoundRobinPreview` calcula como **até 20 entradas anônimas** poderiam ser distribuídas entre **até oito posições de agentes artificiais**, de forma circular e determinística. Cada posição de agente tem somente `enabled` (booleano), `has_required_skill` (booleano), `open_leads` (inteiro) e `capacity` (inteiro). Nenhum nome, identificador, tenant, senha, telefone, e-mail ou lead comercial é recebido.

A seleção começa em `startCursor` e percorre os agentes em ordem circular, pulando os indisponíveis, sem habilidade ilustrativa, ou no limite de capacidade. Depois de atribuir uma posição, o cursor avança para a posição seguinte; as cargas são atualizadas somente em memória. Se não há agente elegível, o índice de saída é `null` e a entrada continua pendente; a fila **não escolhe um agente acima da capacidade**.

Cada entrada de lead é apenas um estado fechado (`unassigned`, `assigned`, `engaged`, `closed`). **Somente `unassigned` é elegível:** entradas declaradas atribuídas, já atendidas ou encerradas não são redistribuídas. O relatório devolve exclusivamente índices locais de posição, cargas simuladas, contadores e cursor de próxima posição. Não identifica ninguém.

## Testes

`SyntheticLeadRoundRobinPreviewTest` verifica distribuição justa iniciando em um cursor arbitrário, preservação de entradas, proteção contra redistribuição de leads já atendidos, exclusão de agentes desabilitados/sem habilidade/sem capacidade, pendências por falta de capacidade, conjunto vazio de agentes, limite de oito agentes e vinte entradas, tipos inválidos, campos extras/PII e flags de negação permanentes.

## Limites operacionais

O algoritmo **não verifica que um lead pertence à empresa**, não observa o atendimento real, não escolhe responsáveis existentes, não assegura exclusão mútua ou compare-and-swap em PostgreSQL e **não define política de prioridade, SLA, desempenho, redistribuição entre equipes ou habilidades homologadas**. Os índices de posição não são IDs de vendedores.

`assignment_policy_verified`, `staff_identity_verified`, `tenant_verified`, `concurrency_verified`, `lead_state_verified`, `authorized` e `can_assign/can_redistribute/can_notify/can_persist` são sempre `false`.

VEN-P09 e CORE/SEG/DBA/LGPD permanecem NO-GO para atribuição real. Sem API, SQL, IAM/ALLOW, dados de clientes, tarefas, worker, n8n, custos, VPS, `main` ou produção.
