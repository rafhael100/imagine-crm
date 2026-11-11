# CRM UX F3-74 — Conclusão em lote dos acompanhamentos fictícios visíveis

**Data:** 11/11/2026. **Escopo:** apenas protótipo offline `prototypes/f3-59-offline-kanban/index.html`.

## Comportamento implementado

O Kanban fictício F3-73 recebe o botão **Concluir pendentes visíveis (simulação)**. O comando age exclusivamente sobre cartões que **atendem simultaneamente aos filtros atuais de prioridade e situação de acompanhamento**; não modifica qualquer ficha oculta por um filtro. O botão fica desabilitado quando não há pendências visíveis e anuncia a quantidade afetada com rótulo acessível.

O conjunto de mudanças é registrado como **uma única operação reversível** de histórico, `kind=batch-followup`, com a lista exata de IDs fictícios e estados anteriores. Um único clique em **Desfazer ação** restaura os acompanhamentos daquele lote; a atualização das métricas é imediata. A operação aparece no histórico textual resumido, limitado a seis mensagens locais.

Se a conclusão fizer desaparecer todas as fichas visíveis (filtro `Pendentes` ativo) ou desabilitar o botão, o foco se move para um filtro acessível e existente na tela, não para um controle desaparecido. Reiniciar restaura os oito exemplos fixos e os filtros. O limite total da simulação continua 12 oportunidades inventadas.

## Segurança e QA

Todas as operações ocorrem apenas em memória JavaScript: **sem formulário, exportação, API, notificação, rede, upload, storage ou dado real**. A CSP permanece `connect-src 'none'`. A suíte `OfflineKanbanFunnelTest` inclui novo contrato para seleção por visibilidade, undo atômico e ausência de efeito externo. O browser smoke existente em **Chromium real desktop + viewport 320 CSS px** verifica: filtro alta prioridade (4 oportunidades), conclusão só dessas quatro, preservação de 2 pendências normais ocultas, desfazer integral, conclusão com filtro Pendentes que esvazia a tela, foco seguro, undo e reinício sem overflow.

Exigir F0/Chromium, PostgreSQL/RLS, Sites e Agents verdes na SHA exata e `behind_by=0` antes de merge **somente DEV**.

## Limites

Não equivale à execução de atividades de CRM, autorização de operação em lote, cancelamento de compromissos, envio de contato ou auditoria. Qualquer ação em massa com dados reais exige controles próprios por organização, consentimento, acesso, confirmação e registros: **CORE/IAM/SEG/DBA/LGPD permanecem NO-GO**. `main`, VPS, produção e despesas inalteradas.
