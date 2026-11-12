# CRM F3-127 — Prévia de descontos e revisão gerencial de propostas

**Data:** 11/11/2026 · **Escopo:** PHP Core e PHPUnit, DEV somente · **Rastreabilidade:** CRM-REV-06 VEN-R08/R09/R12/R13, VEN-T05/T06, VEN-P04/P06.

## Entrega técnica

`SyntheticCommercialDiscountReview` avalia até 12 **linhas fictícias de orçamento**, contendo somente quantidade inteira, preço unitário BRL em centavos e taxa de desconto inteira em pontos-base (0..10000). Usa `SyntheticLineItemSubtotalPreview` já integrado para validar a estrutura de quantidade e preço, calcular o subtotal bruto sem `float` e impor o limite `Number.MAX_SAFE_INTEGER`.

Cada desconto é calculado por linha em centavos, com arredondamento **metade para cima**, divisão do valor por 10000 antes da multiplicação e proteção de somas. O resultado devolve somente total bruto, desconto por posição, desconto total, total com desconto, posições fora do limite de política **inventada** e indicador de que uma revisão gerencial seria necessária segundo essa hipótese.

### Exemplo puramente ilustrativo

Três linhas com total bruto de R$ 339,80 e descontos individuais em centavos de 1.299, 1.600 e 1 produzem R$ 29,00 de desconto e **R$ 310,80 de total após desconto**, sem impostos ou taxas. Não são preços do CRM Imagine, valores de cliente ou proposta oficial.

## Controle de aprovação

A política máxima recebida é também um número **não verificado de laboratório**. A flag de entrada `managerApprovalClaimed=true` significa apenas que a amostra **alega** uma aprovação; em hipótese alguma comprova identidade ou direito de aprovação.

Mesmo com descontos dentro da política aparente ou com `managerApprovalClaimed=true`, o retorno mantém `manager_approval_verified=false`, `discount_policy_verified=false`, `authorized=false`, `can_apply_discount=false`, `can_issue_proposal=false`, `can_charge=false` e `can_persist=false`.

`taxes_calculated=false` significa que o imposto **não foi calculado**, e não que a carga fiscal seja zero. Valores de desconto não compõem venda confirmada, receita nem cobrança.

## Provas e gates

`SyntheticCommercialDiscountReviewTest` cobre múltiplos itens, diferenciação por posição, desconto no limite, desconto fora de política, alegação de aprovação sem direito, arredondamento de meio centavo, inteiro seguro de 2^53−1, lote vazio, limite de 12, entradas malformadas, PII e consistência aritmética.

VEN-P04/P06 e CORE/SEG/DBA/LGPD permanecem NO-GO para catálogo oficial, política por empresa, identidade gerencial, aceite, emissão de proposta, assinatura ou pagamento. Nenhuma rota, SQL, IAM/ALLOW, cliente real, despesa, VPS, `main` ou produção.
