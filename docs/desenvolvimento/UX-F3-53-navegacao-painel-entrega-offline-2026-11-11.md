# CRM UX F3-53 — navegação integrada até o painel fictício de conferência

**Mudança funcional:** na seção de `Leads fictícios` do protótipo principal de nove telas, após os quatro estados de entrega da F3-50, há um link visível **Abrir painel fictício de conferência Portal–CRM** que abre o novo painel da F3-52. O painel possui link de retorno ao protótipo principal, formando uma navegação bidirecional por arquivos locais `file://`.

Foi intencionalmente mantida a página principal **sem novas telas**, preservando a navegação de nove itens, filtros, métricas, tabela e regras de acessibilidade já testadas. Os dois destinos são HTML locais verificados; nenhum hyperlink externo, requisição de rede, `target`, download, form, login ou dado real foi adicionado.

A política de destinos permitidos em `OfflinePrototypeResourceBoundaryTest` agora reconhece exatamente o novo arquivo local; `OfflineFollowUpNavigationContractTest` reconhece três links na página principal (pular para conteúdo, painel de acompanhamento e painel de conferência). `OfflineDeliveryReconciliationNavigationTest` valida posições dos links, destinos recíprocos, a ausência de alvos externos e CSP de ambas as páginas.

O CI F3-53 executa as regressões de DOM, links e isolamento do backend, mais **três scripts de navegador Chromium**: protótipo principal desktop, principal mobile 320px e painel de conferência com desktop e 320px. Os scripts não falseiam sucesso se Chrome não estiver disponível.

Este é um incremento de UX offline, **não uma tela CRM com dados reais**. F70 Comercial, CORE/IAM, SEG/DBA/LGPD, identidade Portal, consentimento e operação de fila/recibo continuam pendentes. Nenhum merge ou deploy de produção.
