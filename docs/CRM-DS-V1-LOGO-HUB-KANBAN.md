# Imagine CRM — PNG original no catálogo e Kanban (F3-195-B)

Extensão em PR filho da implementação base de identidade #418. Logomarca idêntica à original Node: `prototypes/assets/logo-imagine.png`, blob Git `678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77`.

- Catálogo offline `prototypes/index.html`: logo real em `assets/logo-imagine.png`, CSP `img-src 'self'`.
- Kanban offline `prototypes/f3-59-offline-kanban/index.html`: logo em `../assets/logo-imagine.png`, CSP `img-src 'self' data:` para teste isolado.
- Os dois documentos continuam com `connect-src 'none'` e sem importações externas. Nenhum formulário operacional, sessão ou dado de cliente foi adicionado.
- Teste unitário valida origem local, alt e SHA original. Chromium valida logo carregado em launcher e Kanban, neste último com data URI exclusivamente em arquivo temporário gerado após verificação de integridade. O HTML versionado preserva orçamento 50KB.
- Páginas independentes restantes precisam de migração por tela. PR rascunho, sem merge/deploy ou avanço dos gates #27/#259.
