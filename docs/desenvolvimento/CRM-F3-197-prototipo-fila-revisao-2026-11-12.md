# CRM F3-197 — interface de revisão fictícia de possíveis duplicidades

**Data:** 12/11/2026. **Base:** F3-195 (evidência), F3-196 (priorização/paginação) e Experience System Imagine; issue #35. Artefato: `prototypes/f3-197-lead-collision-worklist/index.html`.

A demonstração oferece uma fila visual com oito comparações **inteiramente inventadas**, ordenadas como o Core F3-196: primeiro evidência contraditória em retry, depois possível coincidência de pessoa, reenvio não verificado e evidência insuficiente. Exibe somente números de posição local, contagens e códigos com legendas. Possui filtros por motivo, paginação de três casos, `aria-pressed`, status `aria-live`, link para conteúdo e foco, responsividade até 320px com controles de toque de pelo menos 44px e respeito a movimento reduzido.

A identidade aprovada foi aplicada localmente (laranja #F9941D, grafite #171B19, fundo #F7F7F8, cartões brancos), com **logomarca PNG original** da versão Node incorporada como data URI, sem download externo; o blob canônico é `678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77`, verificado por PHPUnit. Regressão em Chromium real desktop e 320 CSS px valida carregamento do logotipo, ordem/contagem, paginação, filtros, foco e ausência de overflow horizontal. Unit test compara a ordenação com a classe PHP F3-196.

**Delimitação:** arquivo HTML sem servidor; CSP `connect-src 'none'`, `form-action 'none'`, `frame-src 'none'`, sem persistência, cookies, storage, envio de dados ou rotas públicas; nenhuma possibilidade de confirmar entrega, deduplicar/mesclar, importar ou associar pessoa real. A tela não foi adicionada ao hub existente, que tem 12 cards sob contratos/testes próprios e migração visual #418/#419/#420 ainda sob revisão; linká-la requer fase de integração visual separada. Nenhum login de cliente, tenant, SQL, VPS, deploy ou `main` foi alterado.

**Aceite:** testes F0, PG/RLS, Sites e AIA verdes sobre HEAD da PR, além de revisão humana do protótipo e #27/#259 antes de uso operacional.
