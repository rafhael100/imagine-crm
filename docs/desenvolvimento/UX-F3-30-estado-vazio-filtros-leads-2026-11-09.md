# UX-F3-30 — Estado vazio e restauração de filtros de leads fictícios

**Data:** 09/11/2026. **Escopo:** somente protótipo HTML offline, com quatro exemplos inventados. Continua [UX-F3-29](UX-F3-29-filtros-combinados-leads-ficticios-2026-11-09.md), [F3-29](F3-29-contrato-negativo-consulta-leads-sinteticos-2026-11-09.md) e [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35).

## Comportamento

Ao combinar etapa e faixa descritiva de modo que nenhum dos quatro exemplos corresponda, a tela **Leads fictícios** apresenta um painel de estado vazio:

> Nenhum lead fictício corresponde à combinação selecionada. Não foi realizada consulta ao CRM.

A mensagem é anunciada por região `role="status"`, `aria-live="polite"` e `aria-atomic="true"`. O painel inicia com `hidden`, aparece somente quando a contagem de linhas visíveis é zero e desaparece quando qualquer combinação volta a mostrar resultados.

No estado vazio, o botão nativo `Limpar filtros fictícios` limpa **os dois grupos independentes** (etapa e faixa), mostra novamente os quatro exemplos, mantém a ordenação que já estivesse ativa e transfere o foco para o botão visível **Todos** do filtro de etapa. Nenhuma ação de formulário, API, banco ou armazenamento é realizada. A limpeza de ficha/histórico quando um item fica oculto permanece implementada no predicado de filtragem já existente.

## Validação

- `OfflinePrototypeLeadEmptyStateTest`: estrutura acessível do painel inicialmente oculto, vínculo com o botão, nove telas e quatro linhas estáticas, ausência de API, formulário, storage e novas permissões.
- `check-offline-prototype-browser.php`: Chromium a 520 CSS px verifica estado sem resultados, status, reset dos dois filtros, foco e contagem.
- `check-offline-prototype-320.php`: Chromium a 320 CSS px cobre reset, foco, limpeza do estado vazio e ausência de overflow horizontal.
- As duas suítes F0 e PostgreSQL/RLS devem ter jobs realmente executados e aprovação na SHA exata do PR e pós-merge. Avaliação humana de WCAG/leitores de tela continua pendente.

## Fronteiras

O estado vazio não representa resultado consultado no CRM, ausência de registros empresariais ou autorização de acesso. O contrato PHP F3-29 **não é invocado**. Nenhum dado de cliente, tenant autenticado, entitlement, ação comercial, `ALLOW`, grant, persistência, exportação, serviço pago, publicação, `main` ou VPS foi incluído.

Seguem abertos CORE02-D04..D10, CORE01/03/06/07, a revisão independente [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), INT/DAT, LGPD e FIN/CON. Merge somente DEV após revisão de diff, HEAD/base e ambas as CIs verdes.
