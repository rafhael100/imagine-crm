# UX-F3-29 — Dois filtros combinados na lista de leads fictícios

**Data:** 09/11/2026. **Escopo:** protótipo HTML offline (nove telas, quatro registros inventados) sem dados de clientes, autenticação, API ou consulta SQL. [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35). Continuação de [UX-F3-26](UX-F3-26-ficha-ficticia-leads-2026-11-09.md), [UX-F3-28](UX-F3-28-navegacao-funil-ficha-offline-2026-11-09.md) e contrato PHP experimental [F3-29](F3-29-contrato-negativo-consulta-leads-sinteticos-2026-11-09.md).

## Comportamento demonstrativo

A tela **Leads fictícios** conserva o filtro por etapa e acrescenta um segundo grupo nativo de quatro botões **Todas as faixas / Frio / Morno / Quente**. A classificação é um atributo literal dos quatro registros de exemplo: A=Frio, B=Morno, C/D=Quente. **Não é algoritmo de scoring, avaliação de pessoas, permissão ou filtro de servidor**.

- Etapa e faixa são **independentes**, combinadas por interseção (AND). Exemplos: Todos+Quente = 2; Entrada+Quente = 1; Qualificação+Morno = 0. Retirar o filtro de etapa preserva a faixa ativa, e vice-versa.
- Ambos os grupos mantêm exatamente um `aria-pressed=true` e a contagem existente em `role=status / aria-live=polite` descreve 0..4 resultados. O estado é transitório em memória, sem armazenamento.
- A ordenação preexistente de quatro linhas não altera os dois filtros. O painel de revisão de lead continua sendo **somente leitura**; se algum dos filtros oculta o selecionado, o painel e os eventos fictícios são completamente apagados, e `aria-expanded` retorna a `false`.
- A navegação a partir do **funil ilustrativo** restaura explicitamente ambos os filtros para `all` antes de abrir a ficha correspondente. Não há criação, movimentação de etapas, edição, exportação nem chamada de backend.
- O protótipo conserva CSP com `connect-src 'none'`, `form-action 'none'`; zero formulários, inputs ou storage.

## Testes

`OfflinePrototypeCombinedLeadFiltersTest` verifica DOM dos quatro botões e dos quatro registros, faixas estáticas, interseção dos predicados, ausência de dados reais e manutenção do isolamento. A regressão anterior `OfflinePrototypeLeadFilterTest` foi adaptada para o predicado conjunto.

Os scripts existentes executam **Chromium real em 520 e 320 CSS px**: contagens 4/2/1/0, estado pressionado, ausência de overflow, limpeza de ficha e histórico quando filtro oculta o item, retorno entre funil e lista com filtros restaurados. Exige-se ainda avaliação humana com teclado/leitor de tela/WCAG no futuro.

## Gatilhos que permanecem fechados

O contrato F3-29 de PHP **não é chamado por essa UX**. Uma seleção de botões ou `lab.lead` não equivale a filtro obrigatório de tenant no banco, política efetiva por campo, perfil, finalidade/consentimento ou direito de consultar/exportar leads reais.

CORE02-D04..D10, CORE01/03/06/07, [SEG/DBA independente #27](https://github.com/rafhael100/imagine-crm/issues/27), INT, DAT, LGPD e FIN/CON continuam abertos. Sem `ALLOW`, grants RLS, migrations, APIs, credenciais, `main`, VPS, produção ou custos. Integrar somente em DEV após revisão de diff/SHAs e ambas as CIs F0 e PostgreSQL/RLS verdes na exata revisão.
