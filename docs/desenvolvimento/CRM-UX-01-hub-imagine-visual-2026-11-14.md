# CRM UX-01 — ponto de entrada dos 12 protótipos, identidade Imagine

**Escopo:** apenas `prototypes/index.html`, teste PHP estático e prova visual Chromium em `prototypes/ci_hub_visual.py` e `.github/workflows/crm-ux-01-offline-hub.yml`.

A versão anterior do hub utilizava azul/verde; esta revisão aplica os tokens aprovados para o ecossistema Imagine: laranja **#F9941D**, grafite **#171B19**, branco **#FFFFFF** e fundo **#F7F7F8**, com botões de contraste escuro, bordas leves, cartões arejados, teclado visível e redução de animação quando solicitada.

A lista de **12 links e suas respectivas páginas não mudou**, mantendo o comportamento estritamente offline. CSP continua proibindo scripts, formulários, imagens externas, rede, frames e armazenamento. A navegação é composta por links relativos sem dados pessoais ou credenciais.

A CI de revisão deve comprovar 1440, 768, 390 e 320px em **Chromium real**, com screenshots SHA256 e retenção de **7 dias**; seu resultado depende da execução na HEAD exata. O conteúdo é somente demonstração, não publica login ou CRM operacional.

**Logo oficial incorporado no hub:** foi reutilizado **sem re-encode** o Git blob `678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77` já presente no candidato CRM WEB-P0 #520, descrito e comparado ao original do Portal Node; agora está em `prototypes/assets/logo-imagine.png`, com dimensões PNG 200×55 e verificação de hash Git blob em PHPUnit. O HTML só autoriza imagens locais (`img-src 'self'`), sem chamadas externas. **Faltam para o aceite global UX #416:** revisão comparativa das demais telas, incorporação do logo nos demais pontos necessários e validação do frontend efetivamente servido por Laravel. Não é homologação integral de identidade. Nenhuma alteração foi feita à `main`, VPS, IAM, banco ou rotas públicas.

**Regra:** revisão visual isolada, sem merge automático, sem implantação e sem custo adicional. A equipe responsável pelo Design System deve registrar o aceite antes da incorporação à base principal.
