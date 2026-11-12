# CRM F3-114 — Previsão ponderada fictícia de vendas

**Data:** 11/11/2026. **Escopo:** apenas código PHP + PHPUnit na DEV, sem consulta a dados reais.

## Entrega funcional

`SyntheticWeightedSalesForecast` calcula o valor **bruto não ponderado** e o valor **ponderado por probabilidade** de até 20 oportunidades exclusivamente inventadas. Cada linha de laboratório tem somente `amount_cents` (inteiro de centavos BRL, entre 0 e `9007199254740991`) e `win_probability_bp` (inteiro de 0 a 10.000 pontos-base, equivalente a 0–100%). Não recebe IDs, nomes, cliente, segmento, conta ou estágio oficial.

Exemplo: R$ 129,90 a 50%, R$ 79,99 a 25% e R$ 600,00 a 75% produzem R$ 809,89 brutos e **R$ 534,95 ponderados**. Esse número é um cálculo matemático sobre probabilidades *arbitrárias* do teste, não previsão validada por histórico, dados financeiros ou IA.

### Contrato numérico

- **Nunca converter dinheiro para `float`**. O valor ponderado de cada linha é arredondado para o centavo mais próximo com metade para cima (`half_up_per_line`), depois os centavos de cada linha são somados.
- Evitar multiplicação direta `amount_cents × bp` quando ela possa estourar PHP 64 bits: decompor o valor por 10.000 antes de aplicar a probabilidade.
- Limitar soma de valores brutos e ponderados a `Number.MAX_SAFE_INTEGER`, para futura igualdade com JavaScript, e recusar negativos, strings, decimais e qualquer campo extra.
- Retornar somente número de linhas, subtotais ponderados por **posição**, soma bruta, soma ponderada, código BRL e negações fixas.

### Segurança, produto e aprovação

Todos os campos de verificação de probabilidade ou previsão são `false`; marcar como ganha, emitir nota, cobrar e persistir são sempre `false`. Esse resultado **não é faturamento, receita reconhecida, pagamento, oportunidade com ganho confirmado, meta ou orçamento aceito**.

A probabilidade de fechamento real, política de conversão, ponderação por etapa, autorização por empresa e definição de receita dependem de VEN-P04/P11 e pareceres CORE/SEG/DBA/LGPD. Não há consulta SQL, endpoint, automação, n8n, contato, serviço de terceiros, custo, `main` ou deploy.

`SyntheticWeightedSalesForecastTest` verifica arredondamento por linha, 0%/100%, limite inteiro seguro, teste adversarial de mais de 20 entradas, soma além do limite, tipos inapropriados e ausência de vazamento de dados privados.
