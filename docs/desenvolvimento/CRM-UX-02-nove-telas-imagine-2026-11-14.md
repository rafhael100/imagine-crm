# CRM UX-02 — interface principal de nove telas com identidade Imagine

**Objetivo:** avançar a experiência visual do protótipo principal do CRM em `prototypes/s01-prot-01/index.html`, mantendo a programação das nove áreas, dados fictícios e segurança offline.

Esta alteração aplica o padrão original do Portal Node aprovado em UX #416, adaptado ao CRM: **laranja #F9941D**, **grafite #171B19**, **branco #FFFFFF** e **fundo #F7F7F8**. Sidebar escura com seleção laranja, cabeçalhos claros, cartões leves, botões de contraste alto, destaques dourados e foco de teclado preservado. Estados semânticos positivos, alertas e negativos permanecem distinguíveis.

As **nove telas** do protótipo (boas-vindas, empresa, dashboard, leads, funil, monitoramento, importação, estados e informações) continuam presentes com a navegação, IDs, lógica JS e dados de demonstração originais. Esta revisão não liga backend, banco real, autenticação, persistência, importação ou captura.

O teste PHP `OfflinePrototypeNineScreenImagineIdentityTest` verifica os tokens e a continuidade das nove telas e da CSP offline. A CI dedicada usa Chromium em 1440/768/390/320 px, navega por todas as telas e captura o dashboard, sem qualquer requisição externa. PNGs são protegidos por hashes SHA-256 e retidos por sete dias na CI se aprovados.

**Logo original agora incluído:** o PNG original 200×55 foi reutilizado, sem re-encode, do blob já verificado na prévia comercial do CRM (#520), SHA Git `678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77`, em `prototypes/assets/logo-imagine.png`. A sidebar usa apenas arquivo local, com CSP `img-src 'self' data:`, e PHPUnit/Chromium verificam conteúdo e visibilidade. **Limites de revisão UX:** o teste Chromium não substitui avaliação humana de contraste e leitura ou revisão das demais interfaces do CRM em Laravel. **PR isolado, DRAFT/NO-MERGE/NO-DEPLOY**. Nenhuma mudança na main, VPS, IAM, cadastro ou cobrança.
