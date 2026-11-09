# UX-F3-26 — Ficha de revisão de lead fictício, somente leitura

**Data:** 09/11/2026. **Escopo:** [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), protótipo HTML autocontido `S01-PROT-01`, sem conexão com IAM, dados reais, Laravel ou PostgreSQL.

## Experiência

Na tela **Leads fictícios**, as quatro identificações estáticas passam a ser botões nativos de abertura da ficha ilustrativa. A ficha mostra apenas o identificador, a etapa, a pontuação e a faixa descritiva **já conhecidos do próprio exemplo**, sem nomes, telefones, e-mails, campos personalizados, históricos, consentimentos ou permissões de clientes.

- Apenas **uma ficha** pode estar expandida. O segundo acionamento do mesmo botão a recolhe. Cada controle expõe `aria-controls="lead-detail-preview"` e `aria-expanded`; o painel usa `role=status`, `aria-live=polite`, `aria-atomic=true`, começa com `hidden` e é preenchido apenas por `textContent`.
- Ao **filtrar** a etapa de modo que o item selecionado saia da lista visível, a ficha é automaticamente fechada e seu `aria-expanded` volta a `false`. Se o item continuar visível, mantém a ficha.
- A **ordenação** existente move os mesmos quatro elementos da tabela e **não altera a seleção**.
- Ao navegar para outra tela, a ficha é recolhida. Nenhum estado de permissão ou dado persiste.
- A ficha informa expressamente que **visualizar não autoriza exportar, alterar ou executar ato comercial**. Nenhuma validação de direito é simulada como concluída.

## QA

`OfflinePrototypeLeadDetailTest` cobre cardinalidade das quatro referências, marcação de acessibilidade, painel único inicialmente oculto, exclusão de rede/armazenamento/formulários e presença de lógica que limpa a seleção escondida. `check-offline-prototype-browser.php` e `check-offline-prototype-320.php` exercitam abertura, fechamento, filtro, ordenação, `aria-expanded` e painel em Chromium de 520 e 320 CSS px. O smoke de 320 verifica também overflow horizontal.

A página mantém CSP offline (`connect-src 'none'`, `form-action 'none'`), nove telas e quatro itens fictícios. A inspeção manual de usabilidade, leitores de tela/WCAG e contraste ainda não foi substituída por testes automatizados.

## Fronteiras e pendências

Não há API, sessão, tenant, perfil, registro real, alteração, importação, download ou exportação. Os números **não são scoring de pessoas reais**, e a ficha não equivale ao futuro detalhamento funcional de leads do CRM. CORE02-D04..D10, CORE01, SEG/DBA [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27), LGPD, AUT e INT continuam **abertos**; nenhuma permissão, `ALLOW`, grant, migração, `main`, VPS ou publicação autorizada. Merge possível **somente DEV**, após revisão do diff e das duas suítes na SHA exata.
