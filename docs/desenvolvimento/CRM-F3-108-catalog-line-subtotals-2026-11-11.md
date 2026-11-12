# CRM F3-108 — Subtotais ilustrativos de itens comerciais

**Data:** 11/11/2026 · **Ambiente:** apenas DEV · **Referência de produto:** CRM-REV-06 VEN-R08, VEN-P04

## Capacidade implementada

`SyntheticLineItemSubtotalPreview` calcula a soma exata **quantidade × preço unitário** para uma lista de 0 a 12 itens *inventados*, com quantidades de 1 a 100 e preços em centavos inteiros de BRL. Retorna um resumo de subtotais por posição e subtotal agregado, sempre limitado a `Number.MAX_SAFE_INTEGER = 9007199254740991` centavos para paridade futura com JavaScript.

O PHP valida primeiro o formato fechado de cada linha (`quantity`, `unit_price_cents`), recusa campos adicionais, valores decimais, strings, preços negativos e totais que estourariam o limite de precisão. A multiplicação e a soma são precedidas de guardas inteiras, sem float, cálculos fiscais nem arredondamento implícito.

## Não é uma proposta aprovada

Os números **não são preços de planos Imagine**, nem representam catálogo, contrato, aceite de proposta, cobrança ou preço final devido. Não foram aplicados descontos, impostos, frete, aprovação gerencial, moeda estrangeira, validade ou cláusulas. `taxes_calculated=false` e `discounts_calculated=false` significam regras **não calculadas**, não valor zero devido. Direitos de emissão, aceite, cobrança e persistência são todos `false`.

Os limites de 12 linhas e 100 unidades são **somente do laboratório**, não limites de um plano comercial. Os dados são inventados, sem produto, CNPJ, cliente, vendedor, oportunidade ou identificador.

## Evidências

`SyntheticLineItemSubtotalPreviewTest` verifica múltiplas linhas, item com preço zero, chave de campos em ordem alternativa, carrinho vazio, limite exato do inteiro seguro e negativas de multiplicação/soma com overflow, campo extra suspeito e tipos incorretos. Nenhum novo endpoint, migration, SQL, armazenamento, IAM/ALLOW, envio ou geração de PDF.

**Gates:** VEN-P01..16, CORE/SEG/DBA/LGPD continuam pendentes para qualquer proposta ou operação real. Esta entrega é cálculo puro em memória, testável e não operacional.
