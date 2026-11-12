# CRM UX F3-194 — prévia de impacto de lote no Kanban offline

Data: 12/11/2026. Escopo: tela de demonstração offline, exclusivamente oito a doze oportunidades fictícias F01–F12 em memória.

## Função

Acrescenta uma área separada para simular o destino escolhido entre as quatro etapas, sem mudar cartões: exibe quantidade de cartões selecionados e visíveis, quantos mudariam de etapa, quantos já estão nela e quantidade de selecionados ocultos pelos filtros. Sem seleção, a prévia considera todos os visíveis, em alinhamento ao botão de avanço em lote já existente. Selecionados ocultos **nunca** são contados como candidatos.

A área recalcula automaticamente ao alterar filtros, busca, seleção, movimentação local, desfazer/refazer e reset. O reset limpa a etapa-alvo anterior, prevenindo que uma prévia antiga seja confundida com o estado inicial.

## Limite de segurança

Trata-se de uma previsão numérica e inteiramente fictícia na página, **não** uma ligação ao PHP F3-192/F3-193, um parecer de campos obrigatórios, uma política de vendas, autorização, importação/exportação, transação em banco ou confirmação real. Nenhuma API, rede, armazenamento, `localStorage`, dados pessoais, emissão de leads, rota, migration, `main`, VPS ou implantação.

## Verificação

Tests PHPUnit inspecionam botão, estados ARIA, CSP e ausência de persistência. Testes Chromium existentes cobrem 1100px e 320px, revisão sem mutações, seleção filtrada, cartões ocultos, reset, teclado e overflow. Limite explícito de HTML estático atualizado de 46 para 48 KiB após a inclusão sem abrir comunicação externa.

Os bloqueios SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27) e CORE/SEG/DBA/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259) permanecem NO-GO; a prévia NÃO habilita o CRM operacional.
