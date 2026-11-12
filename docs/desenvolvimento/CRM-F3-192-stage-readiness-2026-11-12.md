# CRM F3-192 — prévia combinada de mudança de etapa (12/11/2026)

**Escopo:** contrato PHP puro, com histórico de oportunidades e presença de campos 100% fictícios. Sem migrations, APIs, banco, armazenamento, autorização ou serviço externo.

## Lacuna atendida
Os componentes existentes `SyntheticOpportunityTransitionLedger` (F3-123) e `SyntheticStageFieldRequirementsInspector` (F3-124) validavam separadamente o histórico e a presença ilustrativa de campos. A F3-192 os compõe em `SyntheticStageMoveReadinessInspector`, sem criar outro modelo de status. Verifica versão esperada e atual (apenas informativa), oportunidade inexistente/encerrada, ausência de mudança, limite de 24 eventos e campos obrigatórios da etapa de destino. Inputs malformados são negados mesmo quando a revisão está desatualizada.

## Contrato e segurança
- Entrada: lista até 24 eventos sintéticos, revisão numérica, etapa-alvo 0–3, cinco flags booleanas e quatro listas de códigos de campo; sem valor pessoal, identificação de oportunidade ou organização.
- Saída: código de razão na allowlist, números, etapa de origem e destino, códigos de campos faltantes (somente quando revisão atual e oportunidade aberta), e indicadores **sempre falsos** `authorized`, `can_move`, `can_persist`, `tenant_verified`, `concurrency_verified`, `policy_verified`, `authorization_verified`.
- Mesmo `FIELDS_PRESENT_UNVERIFIED` com `fields_present_claimed=true` nunca representa permissão de negócio ou CAS em PostgreSQL.
- Testes positivos/negativos incluem revisão desatualizada, fechamento, ausência de etapa, limite de eventos, payload pessoal em campos, não vazamento e não mutação.

## Bloqueios não substituídos
O código NÃO autentica usuário, não lê empresa ou contato, não aplica `ALLOW`, não prova RLS, não aplica versão em banco, não conclui venda, não grava valores e não altera o Kanban existente. Nenhuma autorização para `main` ou VPS. P0 SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27) e CORE/SEG/DBA/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259) seguem NO-GO, sem revisor independente designado.
