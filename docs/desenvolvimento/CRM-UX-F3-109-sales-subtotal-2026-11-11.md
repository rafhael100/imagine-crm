# CRM UX F3-109 — Pré-orçamento fictício e quantidades locais

**Data:** 11/11/2026 · **Ambiente:** laboratório HTML offline · **Dependência:** F3-108 já integrado à DEV

## Entrega

Nova experiência interativa em `prototypes/f3-109-sales-quote-subtotal/index.html`, navegável pelo **quinto cartão** de `prototypes/index.html`.

Exibe três conjuntos de itens inventados (A: três itens; B: dois itens; C: nenhum item) com rótulos e preços exclusivamente de demonstração. Em A/B, botões permitem **aumentar/diminuir quantidades locais de 1 a 10**, recalculando o subtotal por item e o total da prévia em centavos inteiros. Os botões recompõem a tabela com APIs seguras do DOM (`textContent` e `replaceChildren`), e o foco retorna ao controle recriado após a alteração. Trocar de amostra reinicia o estado inventado, sem gravação.

A função JavaScript valida multiplicação e soma contra `Number.MAX_SAFE_INTEGER` antes de produzir valores. A formatação divide em centavos por aritmética inteira; não confunde resultado demonstrativo com proposta enviada, preço final, desconto ou imposto calculado.

## Validação cruzada e navegador real

`OfflineSalesQuoteSubtotalParityTest` extrai os três casos JSON embutidos no próprio HTML e verifica os subtotais iniciais com **o Core PHP real** `SyntheticLineItemSubtotalPreview`. Para cada linha, percorre quantidades de 1 a 10, confrontando 50 ou mais projeções sem conversão de moeda. Testa negações e ausência de canais de rede, upload, campos editáveis ou persistência.

`check-offline-sales-subtotal-browser.php` executa Chromium **real** em desktop (1100px) e viewport CSS de 320px; alterna os três exemplos, incrementa/decrementa quantidade, verifica subtotal exato em reais, foco de teclado, aviso permanente de operação proibida e ausência de rolagem horizontal. O smoke foi adicionado ao workflow F0. O hub tem seus contratos PHPUnit e Chromium atualizados de quatro para cinco rotas locais.

## Limites de produto

O valor mostrado é **subtotal ilustrativo**, não preço final, proposta comercial formal, boleto, nota fiscal ou autorização para cobrança. Não há impostos, taxas, descontos, frete, validade, assinatura, meta ou catálogo de cliente. `can_issue_proposal`, `can_charge` e `can_persist` permanecem falsos no Core, e a interface nunca tenta invocá-los.

A futura proposta VEN/FIN exige cotação real, controle de tenant, direitos por ação, auditoria, alçada, política de descontos, tributação, aceite de versão exata e revisão independente. CORE/SEG/DBA/LGPD seguem NO-GO.
