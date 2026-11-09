# UX-F3-27 — Histórico ilustrativo de leads sem proveniência comprovada

**Data-base:** 09/11/2026. **Escopo:** protótipo HTML offline com quatro leads fictícios, continuidade [UX-F3-26](UX-F3-26-ficha-ficticia-leads-2026-11-09.md), e referência ao contrato independente [F3-26](F3-26-contrato-negativo-historico-leads-2026-11-09.md). [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35).

## Comportamento entregue

As quatro fichas fictícias de leads apresentam uma **sequência de dois ou três eventos ilustrativos**. Os rótulos pré-definidos exemplificam criação, atribuição ou etapa comercial, **sem datas, pessoas, identificação de quem executou, textos de notas, dados de contato nem histórico de clientes**. A comunicação informa explicitamente que os eventos, autores e a ordem não foram comprovados nem persistidos.

A lista é produzida por `document.createElement('ol')` e elementos `li` com `textContent`. Não há HTML injetado, entradas de usuário, arquivos ou comunicação externa. A seleção continua sendo a mesma de UX-F3-26; quando o painel é recolhido por clique, filtro ou mudança de tela, `replaceChildren()` apaga também os eventos anteriores. Alternar entre leads reconstrói os eventos correspondentes, sem reutilizar conteúdo da ficha anterior. Ordenar a tabela não altera os eventos do lead selecionado.

## Testes

- `OfflinePrototypeLeadHistoryTest`: demonstração isolada dentro do painel oculto, quatro controles existentes, quatro catálogos locais e linguagem de inexistência de autoridade; ausência de formulários, armazenamento e rede.
- `check-offline-prototype-browser.php`: Chromium a 520 CSS px, cardinalidade por lead, troca de seleção sem resíduo, recolhimento sem eventos remanescentes.
- `check-offline-prototype-320.php`: Chromium/iframe a 320 CSS px, ordem fictícia, fechamento/reinicialização e regressões de filtro e overflow.
- `PHPUnit` integral e `PostgreSQL/RLS` são executados pelos dois workflows usuais, sem alteração de código de produção.

## Fronteiras obrigatórias

**Não há integração com** `SyntheticLeadHistoryInspector`, pois esse componente **sempre nega prova de proveniência** e não é fonte de eventos oficiais. Nenhuma sequência é apresentada como histórico real ou auditado; a UI não concede leitura de histórico, exportação ou edição e não acessa dados empresariais ou pessoais. Sem `ALLOW`, rotas, IAM, migrations, grants, storage, VPS, `main`, produção, custos nem serviços externos.

CORE02, CORE01, SEG/DBA independente [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27), CORE06, LGPD, INT, FIN/CON e homologação de fatos de negócio permanecem **pendentes**. A revisão humana WCAG/leitores de tela continua necessária; o teste automatizado não a substitui.
