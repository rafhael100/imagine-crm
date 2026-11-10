# UX F4-29 — Resumo por prazo e paginação da worklist offline

**Escopo:** melhoria exclusiva da interface fictícia F4-28 integrada via DEV, sem dados reais.

## Entrega

O protótipo `prototypes/f4-28-followup-worklist/index.html` apresenta quatro contadores — vencidos, até 24 horas, após 24 horas e total da amostra. Eles refletem **toda a amostra selecionada**, independentemente da faixa filtrada ou página ativa. Alternar entre amostra A (cinco registros fictícios) e amostra B (sem registros) recalcula os valores; a amostra B mostra zeros e mensagem de estado vazio, sem presumir conclusão de atendimentos.

O usuário pode escolher **2 ou 5 registros por página** mediante botões com `aria-pressed`. A mudança do tamanho reinicia a paginação para a primeira página do filtro atual e recalcula o total de páginas e os botões de navegação, mantendo a ordenação por prazo absoluto.

## Evidência / limites

- `tests/Unit/OfflineFollowUpWorklistParityTest.php` compara ambos os tamanhos e as quatro contagens dos dados estáticos com `FollowUpFixtureWorklistPage` PHP F4-27.
- `scripts/ci/check-followup-worklist-browser.php` exercita contadores, filtros, troca de amostras, tamanhos 2/5, reset de página, botões e ausência de overflow em Chromium desktop e 320 CSS px.
- O smoke e testes estáticos são provas de regressão do **protótipo**, não uma homologação WCAG nem de autorização/segurança de clientes.

Sem novas dependências, rotas, SQL, uploads, storage, contas reais, autenticação, `ALLOW`, exportação, jobs, migrações, custos, VPS ou produção. A `main` permanece intacta. SEG/DBA #27, S01 e os gates CORE/LGPD continuam abertos. O PR #175 permanece DRAFT, sem parecer independente.
