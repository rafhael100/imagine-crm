# CRM F3-131 — Meta comercial demonstrativa com eventos únicos

**Data:** 11/11/2026 · **Ambiente:** DEV, PHP puro e PHPUnit, sem clientes reais · **Rastreabilidade:** CRM-REV-06 VEN-R23/R24, VEN-T04/T14, VEN-P07/P11.

## Funcionalidade

`SyntheticSalesGoalProgressPreview` compara uma meta **inteiramente fictícia** (BRL em centavos inteiros, valor positivo até `Number.MAX_SAFE_INTEGER`) com até 20 alegações artificiais de eventos de venda. O componente usa `SyntheticSalesEventUniquenessInspector` já na DEV para validar todos os itens e recusar qualquer UUID repetido, inclusive com diferença de maiúsculas/minúsculas.

A saída apresenta a meta ilustrativa, soma dos valores alegados, quantidade de referências únicas, percentual alegado em pontos-base limitado a 0..10000, diferença até a meta ou excedente declarado, além de flags fixas que impedem reconhecimento de receita.

## Aritmética segura e limites

O percentual fictício é calculado por aritmética **exclusivamente inteira**, com metade para cima. Para evitar o produto inseguro `centavos × 10000` próximo do limite de 2^53, o cálculo utiliza duas divisões sucessivas por 100, com resto e arredondamento exato. Um valor declarado de R$ 400,00 para meta ilustrativa de R$ 600,00 produz `6667` pontos-base, isto é 66,67% **alegados**, nunca meta comercial realizada.

Quando soma de eventos supera meta, o percentual ilustrativo é limitado a 100%, mas o excedente fica visível em centavos. Quando não há eventos, a soma e o percentual são zero; **não há venda confirmada**.

## Regressão e negação permanente

`SyntheticSalesGoalProgressPreviewTest` verifica percentual de 2/3, ausência de eventos, meta exata, excesso, valores próximos de 2^53−1, centavo muito pequeno, valores inválidos, soma excessiva, referência duplicada e UUID alterado só por caixa, ausência de IDs e campos pessoais no retorno.

Mesmo com alegações que atingem 100%, os campos `recognized_sales_count`, `recognized_revenue_cents` e `recognized_progress_bp` permanecem **zero**; `goal_policy_verified`, `event_provenance_verified`, `period_verified`, `authorized`, `can_update_targets`, `can_pay_commissions`, `can_notify`, `can_export` e `can_persist` permanecem falsos.

A comparação **não comprova vendas, meta atingida, comissões, pagamento ou desempenho de vendedor**. Um UUID único não substitui transação autenticada, deduplicação por oportunidade real, exclusão de reaberturas, perímetros de período, RLS e auditoria.

VEN-P07/P11 e CORE/SEG/DBA/LGPD continuam NO-GO para relatórios de produção. Sem rota, SQL, IAM/ALLOW, cliente real, notificação, despesas, VPS, `main` ou deploy.
