# UX-F3-24 — Avisos de prévia fictícia em três cenários offline

**Data:** 09/11/2026. **Escopo:** protótipo HTML autocontido, com continuidade do laboratório [F3-23](F3-23-relatorio-sintetico-avisos-importacao-2026-11-09.md), sem interligação ao PHP, sem dados de clientes.

## Entrega

A tela existente **Prévia de importação** apresenta três cenários textuais fixos e ilustrativos: ausência de avisos de estrutura, célula com possível fórmula/célula vazia e cabeçalho sem linhas de dados. Cada seleção alterna apenas textos dentro de uma região `role=status`, `aria-live=polite`, `aria-atomic=true`; o botão ativo utiliza `aria-pressed`.

O caso sem avisos destaca que a ausência dos cinco sinais de revisão F3-23 **não atesta arquivo seguro, completo ou permitido**. Todos os casos terminam com a informação de que nenhuma importação foi autorizada ou realizada. Nenhum conteúdo de células é exibido ou processado.

## QA

- `OfflinePrototypeImportReviewTest` verifica exatamente três controles, live-region acessível, linguagem de não autorização, manutenção das nove telas e ausência de formulários, inputs, rede ou armazenamento.
- `check-offline-prototype-browser.php`: testa cenários e `aria-pressed` em Chromium real 520 px, além das regressões existentes.
- `check-offline-prototype-320.php`: testa os mesmos estados essenciais em viewport 320 CSS px, sem overflow.
- Ainda requer inspeção manual de UX/WCAG; CI não a substitui.

## Fronteira

**A demonstração não chama o F3-23 PHP**, não interpreta CSV, não acessa upload, não solicita permissões, não identifica tenant nem grava, exporta ou importa dados. Não estabelece política aprovada de importação. A eventual integração real depende de CORE/SEG/DBA/AUT/INT/LGPD, revisão de campos e aprovações específicas.

Permitido somente merge controlado na DEV com revisão integral do diff, base e testes verdes na SHA exata. **Sem `main`, VPS, deploy, custos, credenciais ou produção.**
