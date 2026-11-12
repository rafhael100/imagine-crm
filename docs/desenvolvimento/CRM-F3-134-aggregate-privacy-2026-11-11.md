# CRM F3-134 — Relatório comercial fictício com supressão de grupos pequenos

**Data:** 11/11/2026 · **Ambiente:** Core PHP e PHPUnit na DEV, sem leitura de dados reais. **Rastreabilidade:** CRM-REV-06 VEN-R25/R26/R27, VEN-T12/T13/T18, VEN-P12.

## Regra implementada

\`SyntheticSalesAggregatePrivacyPreview\` lê no máximo **20 linhas inventadas**, cada uma com apenas código de etapa e valor alegado em centavos BRL. Etapas técnicas fechadas: new, qualified, proposal, won e lost.

Agrupa por etapa, mas **suprime quantidade e valor** quando uma etapa tem entre 1 e k-1 registros, para um limite ilustrativo k entre 2 e 5 (padrão 3). Nesse caso devolve count=null, claimed_sum_cents=null e suppressed=true. Grupos sem registros devolvem contagem e valor zero. Grupos com pelo menos k registros podem expor somente o subtotal e a contagem agregados da etapa.

O relatório **não apresenta o total de todos os registros**, nem número total de linhas, pois essa combinação permitiria deduzir por subtração o valor de um único grupo oculto. A soma \`visible_claimed_sum_cents\` considera exclusivamente os grupos visíveis. O Core valida **todas** as linhas e o limite exato \`Number.MAX_SAFE_INTEGER\` antes de ocultar grupos, impedindo que uma linha inválida seja omitida pela política de supressão.

## Interpretação e privacidade

Essa regra é uma **prévia matemática de laboratório, não comprovação de k-anonimato em dados reais**. A supressão de células pequenas, isoladamente, não garante privacidade contra consultas repetidas, cruzamento de filtros, differencing attacks, informação prévia sobre um vendedor ou consultas entre empresas. O resultado jamais confere direito de consultar um cliente ou enviar um relatório.

Os valores da etapa won são **alegações fictícias**; \`recognized_revenue_cents=0\` sempre. \`privacy_policy_verified\`, \`tenant_scope_verified\`, \`report_permissions_verified\`, \`claims_verified\`, \`authorized\`, \`can_read_customers\`, \`can_export\`, \`can_schedule_report\`, \`can_notify\`, \`can_persist\` permanecem falsos.

## Testes e escopo

\`SyntheticSalesAggregatePrivacyPreviewTest\` cobre grupo pequeno de uma ou duas linhas, grupos visíveis, supressão sem total global, limite ilustrativo k=2/3/5, estado vazio, alegações won sem receita reconhecida, somas dentro/próximas do limite 2^53−1, estouro, tipos incorretos, campo pessoal em grupo oculto e ausência de autorização.

Para relatórios de dados reais ainda faltam escopo e permissões por empresa, RLS, filtro por campo, limites de consultas combinadas, privacidade, retenção, destinatário autenticado e revisão independente VEN-P12, CORE/SEG/DBA/LGPD. Não há SQL, API, CSV/PDF, leitura real, agendamento, envio, cliente, despesa, VPS, main ou produção.
