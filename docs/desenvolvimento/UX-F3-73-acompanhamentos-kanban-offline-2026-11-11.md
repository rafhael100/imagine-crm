# CRM UX F3-73 — Acompanhamentos fictícios reversíveis no Kanban

**Data:** 11/11/2026. **Escopo:** interface local `prototypes/f3-59-offline-kanban/index.html`, somente fixtures inventadas.

## Entrega funcional

Cada oportunidade fictícia do Kanban agora possui **um acompanhamento simulado**, inicialmente `Pendente` ou `Concluído` (seis pendentes, dois concluídos). O usuário pode marcar como concluído ou reabrir usando um botão acessível dentro do cartão. Não há agenda, horário, tarefa enviada, mensagem, telefone, notificação ou identidade de cliente.

O filtro de acompanhamento (**Todos / Pendentes / Concluídos**) funciona em conjunto com o filtro já existente por prioridade, ordenação por pontuação e quatro etapas. Contagens, valores por etapa e novo indicador de acompanhamentos pendentes visíveis se atualizam no mesmo render. **Desfazer** restaura a situação anterior, inclusive depois de criar um cartão fictício; **Reiniciar** restaura todas as fixtures, filtros, histórico e indicadores. O foco acompanha a ação no cartão, e o estado é anunciado por `aria-live`.

A simulação é exclusivamente **em memória**: dados e identificadores `F01..F12` determinísticos, CSP `connect-src 'none'`, nenhum upload, API, formulário, localStorage, cookie, e-mail, agenda ou persistência. Nenhuma alteração em `prototypes/index.html` ou na PR #255 Portal↔CRM.

## Testes e critérios de merge

- `OfflineKanbanFunnelTest`: seis estados pendentes/dois concluídos, botões, filtros combinados, histórico com `kind=followup`, undo/reset, CSP e ausência de captura.
- `check-offline-kanban-browser.php`: Chrome/Chromium real desktop e 320 CSS px; verifica filtros de acompanhamento + prioridade, contador, status, alteração, foco, reabertura, desfazer, criação de cartão, reinício e ausência de overflow.
- A suíte F0 executa os mesmos testes Chromium; PostgreSQL/RLS e paridade Sites/Agents devem terminar **success** na HEAD exata. Revisar o diff e garantir `behind_by=0` para eventual merge **somente DEV**.

## Gate

É protótipo de acompanhamento, **não** confirmação de contato, atendimento realizado, pipeline de eventos ou tarefa real. CORE/IAM/SEG/DBA/LGPD, autenticação e autorização multiempresa permanecem NO-GO. Não altera `main`, produção, VPS, custos, dados pessoais, rotas, migrações, grants ou exportações.
