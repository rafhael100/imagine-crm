# UX-F3-23 — Estados ilustrativos de mapeamento CSV

**Data:** 09/11/2026. **Fonte:** fluxo experimental F3-21/F3-22, sem execução de PHP pelo navegador. Branch filha exclusivamente DEV.

## Entrega

A tela existente **Prévia de importação** no protótipo offline recebe três opções de visualização: **3/3 correspondências**, **mapeamento parcial (1/3)** e **estrutura inválida**. Todos os dados e descrições são fixos, inventados e locais no HTML. A troca de cenário apenas substitui elementos de texto em um `role=status` com `aria-live=polite`; a seleção ativa é refletida em `aria-pressed`.

Os rótulos demonstram diferenças de apresentação e não implementam parse, importação, validação de permissões ou leitura de arquivo. Mesmo no cenário completo, a prévia informa expressamente que **nenhuma importação foi autorizada ou realizada**.

## Verificações

- `OfflinePrototypeImportMappingTest` confere os três controles, `role=status`, conteúdo inteiramente fictício, invariância das nove telas e ausência de entrada de dados, formulários, armazenamento e comunicação de rede.
- Os scripts `check-offline-prototype-browser.php` e `check-offline-prototype-320.php` exercitam a troca entre três estados em Chromium real, preservando navegação, filtros e ordenação dos leads, medidores estáticos e proteção contra overflow de tela.
- Nenhuma conexão com F3-21/F3-22 é estabelecida; não existe schema/tenant/papel real nem download/upload.

**Gates:** SEG/DBA, CORE, IAM/entitlement, privacidade, revisão WCAG e homologação de política de importação permanecem em aberto. **Somente DEV**, sem `main`, VPS, produção, despesas ou dados de clientes. Integração condicionada ao diff revisado e aos dois workflows verdes na HEAD exata.
