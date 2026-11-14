# CRM UX-02 — interface principal de nove telas com identidade Imagine

**Objetivo:** avançar a experiência visual do protótipo principal do CRM em `prototypes/s01-prot-01/index.html`, mantendo a programação das nove áreas, dados fictícios e segurança offline.

Esta alteração aplica o padrão original do Portal Node aprovado em UX #416, adaptado ao CRM: **laranja #F9941D**, **grafite #171B19**, **branco #FFFFFF** e **fundo #F7F7F8**. Sidebar escura com seleção laranja, cabeçalhos claros, cartões leves, botões de contraste alto, destaques dourados e foco de teclado preservado. Estados semânticos positivos, alertas e negativos permanecem distinguíveis.

As **nove telas** do protótipo (boas-vindas, empresa, dashboard, leads, funil, monitoramento, importação, estados e informações) continuam presentes com a navegação, IDs, lógica JS e dados de demonstração originais. Esta revisão não liga backend, banco real, autenticação, persistência, importação ou captura.

O teste PHP `OfflinePrototypeNineScreenImagineIdentityTest` verifica os tokens e a continuidade das nove telas e da CSP offline. A CI dedicada usa Chromium em 1440/768/390/320 px, navega por todas as telas e captura o dashboard, sem qualquer requisição externa. PNGs são protegidos por hashes SHA-256 e retidos por sete dias na CI se aprovados.

**Limites de revisão UX:** o protótipo ainda usa marca tipográfica de demonstração; o logo binário original do Portal requer incorporação revisada no fluxo UX #416. O teste Chromium não substitui avaliação humana de contraste e leitura ou revisão das demais interfaces. **PR isolado, DRAFT/NO-MERGE/NO-DEPLOY**. Nenhuma mudança na main, VPS, IAM, cadastro ou cobrança.
