# UX-F3-28 — Navegação do funil fictício à ficha de lead

**Data:** 09/11/2026. **Escopo:** protótipo HTML offline `S01-PROT-01`, issue [F3 #35](https://github.com/rafhael100/imagine-crm/issues/35); sequência de UX-F3-26 e UX-F3-27. **Natureza:** experiência demonstrativa, não movimentação comercial nem autorização.

## Comportamento

Os quatro cartões estáticos do funil existente apresentam um botão nativo **"Revisar ficha ilustrativa"**, com `aria-label` incluindo o identificador fictício de cada lead. O acionamento identifica somente os códigos fechados `a`, `b`, `c` e `d`, previamente presentes na tabela de exemplos. Se um código não for encontrado, a operação termina sem navegação.

Para um código conhecido, a interface:
1. restaura o filtro visual `Todos`, inclusive se havia uma etapa previamente selecionada;
2. navega à tela local `Leads fictícios`, mantém somente uma tela visível e o indicador de navegação correto;
3. expande **somente** a ficha de leitura do exemplo correspondente, conservando seus textos de eventos não verificados;
4. move o foco para o botão nativo daquele lead, de forma que o usuário que utiliza teclado encontre o item de destino;
5. elimina a seleção anterior ao retornar ao funil; abrir outro cartão não herda detalhes ou eventos anteriores.

Não há drag-and-drop, criação, transferência de etapa, validação de perfil, leitura de banco, envio de dados ou acesso a arquivos. O funil mantém exatamente as três colunas e os quatro cartões fictícios existentes.

## Evidências técnicas

- `OfflinePrototypePipelineReviewTest` valida a correspondência exata dos quatro botões `a,d,b,c` com as fichas, `type=button`, identificação acessível, manutenção de nove telas, ausência de formulários/entradas, APIs, storage e rede.
- `check-offline-prototype-browser.php` (Chromium a 520 CSS px): navegação a partir do funil, filtro restaurado, foco no destino, expansão única, fechamento ao sair e ausência de conteúdo obsoleto.
- `check-offline-prototype-320.php` (Chromium em 320 CSS px): mesmos controles, foco, filtro, troca para outro lead e ausência de overflow horizontal.
- A página mantém CSP `connect-src 'none'` e `form-action 'none'`. Inspeção humana com leitores de tela e avaliação WCAG ainda serão necessárias.

## Bloqueios preservados

Sem aplicação backend, endpoint, IAM funcional, dados reais, edição, importação/exportação, autorização `ALLOW`, migrations, RLS/grants, `main`, VPS, produção ou despesa. A revisão independente SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE02-D04..D10, INT, LGPD e FIN/CON continuam abertas.

**CI:** a recuperação da infraestrutura GitHub Actions está acompanhada em [Issue #99](https://github.com/rafhael100/imagine-crm/issues/99). Um PR desta entrega **não será integrado** enquanto F0 e PostgreSQL não executarem realmente e terminarem SUCCESS na mesma SHA do PR, com revisão da base e diff.
