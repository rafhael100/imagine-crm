# CRM UX F3-84 — Retorno reversível em lote e operação bidirecional no Kanban fictício

**Data:** 11/11/2026. **Escopo:** somente interface HTML/JS offline e testes, com cartões fictícios.

## Entrega funcional

O Kanban ganhou **Retornar etapas visíveis (simulação)**, operação complementar à F3-83. Ela retorna uma única etapa para as oportunidades que satisfaçam **todos** os filtros atuais (prioridade, situação de acompanhamento e etapa focada), **exceto** as já na etapa inicial **Entrada**. A ação nunca movimenta cartões ocultos e não passa da primeira etapa. O botão é automaticamente desabilitado caso não haja elegíveis visíveis, informando a quantidade em `aria-label`.

Para evitar comportamentos divergentes entre avanço e retorno, ambos usam **uma única função `moveVisibleStage(direction)`**, que valida `direction` como `+1` ou `-1`, aplica idênticos filtros e limita qualquer etapa ao intervalo `0..3`. Cada lote acrescenta **um único snapshot** `kind=batch-move` de IDs e etapas anteriores; **Desfazer ação** restaura o conjunto integralmente. Com foco em uma etapa, cartões deslocados para fora dela desaparecem apenas da visualização, preservando o array fictício e realocando o foco ao seletor da etapa. Totais, pontuação, fila de acompanhamento e contagens são recalculados sem estado duplicado.

## Testes

- `OfflineKanbanFunnelTest` verifica a função comum de avanço/retorno, filtros idênticos, limites dos extremos do funil, desabilitação sem elegíveis, rollback do mesmo `kind=batch-move`, CSP/sem rede.
- Smoke em **Chromium real** desktop e 320 CSS px: seis cartões não iniciais retornam em uma ação, dois da entrada permanecem, undo restabelece duas fichas por etapa; foco em Proposta + prioridade Normal afeta somente **F08**, preservando F07 oculto, botão fica desabilitado quando a etapa filtrada se esvazia e foco retorna ao seletor; filtro Acompanhamento Concluído afeta somente F04; undo e reset restauram tudo.
- O tamanho HTML permanece estritamente limitado a **30.000 bytes UTF-8** no smoke, ampliado do teto anterior de 28.000 apenas para os novos controles e mensagens; nenhuma exigência de CSP, browser real, formato, fluxo de captura, rede ou testes foi removida.
- F0, PostgreSQL/RLS, Sites e Agents precisam passar na **SHA exata** da PR. Verificar diff, `behind_by=0` e merge somente DEV.

## Gates

É demonstração in-memory, não uma operação real de leads, CRM ou autorização multiempresa. Não há formulário, armazenamento, serviços, rede, SQL, migração, credencial, dado pessoal, IAM/ALLOW, custo, VPS, produção ou alterações em `main`. CORE/SEG/DBA/LGPD continuam NO-GO para ativar fluxo operacional.
