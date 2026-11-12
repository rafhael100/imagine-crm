# CRM UX F3-115 — Painel offline de previsão ponderada de vendas

**Data:** 11/11/2026. **Escopo:** HTML local e PHP puro em DEV; sem acesso a oportunidades reais.

## Entrega funcional

Nova experiência `prototypes/f3-115-weighted-forecast/index.html`, como **sétimo cartão** do hub de protótipos. Exibe quatro cenários inteiramente fictícios: três oportunidades (total bruto R$ 809,89 e ponderado R$ 534,95), conservador, otimista e vazio. Ao trocar de cenário, o estado é reinicializado. Em cada linha do cenário selecionado, botões permitem aumentar/diminuir as probabilidades em passos de **5 pontos percentuais** no intervalo de 0 a 100%, com atualização imediata dos totais.

A interface apresenta apenas rótulos inventados, valores BRL e probabilidades fictícias. Usa `BigInt` no cálculo ponderado para preservar a paridade com PHP inclusive perto de `Number.MAX_SAFE_INTEGER`. Nenhum conteúdo é enviado, armazenado ou lido de um cliente. Alterações preservam foco de teclado e `aria-pressed`. Layout mobile usa viewport de 320 px, com tabela rolável internamente.

## Evidências em CI

- `OfflineWeightedForecastParityTest`: extrai o próprio JSON do HTML, compara cada exemplo diretamente ao Core PHP `SyntheticWeightedSalesForecast` e exercita **todas as probabilidades de 0 a 100% em passos de 5** para cada linha; verifica valores, limites e flags de negação.
- `check-offline-weighted-forecast-browser.php`: executa **Chromium real** em 1100 e 320 px, interage com os controles de probabilidade, verifica aumento/decréscimo em centavos exatos, exemplos, foco, estado vazio, indicação permanente de negação, privacidade e ausência de overflow horizontal.
- O hub e seus testes PHPUnit/Chromium são atualizados para sete rotas locais; o workflow F0 passa a exigir o novo smoke.

## Limites de autoridade

Esta prévia **não é uma previsão estatística, relatório financeiro, receita reconhecida, venda concluída, comissão ou obrigação de pagamento**. As probabilidades são números inventados sem calibração e não correspondem a estágios de clientes reais. A permissão de marcar ganho, faturar, cobrar ou persistir permanece sempre **NÃO**.

VEN-P04/P11, políticas por tenant e revisões CORE/SEG/DBA/LGPD permanecem NO-GO. Sem endpoint, SQL, pagamentos, chaves, clientes, `main`, VPS, despesas ou produção.
