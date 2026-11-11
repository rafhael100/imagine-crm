# UX F3-59 — Quadro Kanban interativo de oportunidades fictícias

**Data:** 11/11/2026. **Escopo:** frontend HTML/CSS/JavaScript independente, navegável localmente em `prototypes/f3-59-offline-kanban/index.html`.

## Funcionalidade implementada

Quadro de quatro etapas — Entrada, Em contato, Qualificação e Proposta — com oito oportunidades **totalmente inventadas**, valores e pontuações também fictícios. O usuário pode filtrar por prioridade, avançar ou voltar a etapa de uma oportunidade, desfazer vários movimentos sequenciais e reiniciar o quadro, com contagem de oportunidades, valores e movimentos atualizada imediatamente. Controles são botões nativos, compatíveis com teclado; o foco acompanha o cartão após a movimentação e os estados são anunciados por `role=status aria-live=polite`.

Todos os eventos atualizam somente objetos JavaScript na memória da aba. **Não há** formulário, input de contato, upload, rede, API, cookie, storage, persistência ou acesso a dados do CRM. CSP bloqueia conexões e carregamentos externos. Layout adapta quatro colunas para duas e uma coluna em 320 px.

## QA objetiva

- `tests/Unit/OfflineKanbanFunnelTest.php` verifica oito fixtures, quatro etapas, CSP, acessibilidade, ações em memória e ausência de captura e de recursos externos.
- `scripts/ci/check-offline-kanban-browser.php` executa **Chromium real** no desktop e em um iframe de 320 CSS px: filtros, contagens, movimentação, foco, desfazer, reinício e ausência de overflow horizontal. Falta de Chromium ou de PASS executado reprova o teste.
- A suíte Laravel F0 incorpora o smoke do navegador. PostgreSQL/RLS e CI cruzada Sites/Agents devem permanecer verdes na HEAD da PR.

## Evitar conflito com UX em paralelo

A PR #246 contém outro recorte de UI offline (conferência Portal→CRM e links de CSV), com checks verdes, mas menciona revisão independente UX e proibição de merge automático. A F3-59 **não modifica** `prototypes/s01-prot-01/index.html` nem arquivos da PR #246. O Kanban tem link de retorno para o protótipo principal; um link na direção oposta pode ser feito após a revisão/reconciliação da PR de UX, evitando disputa sobre a página principal.

## Gates

A funcionalidade visual não fornece backend, gerenciamento de oportunidade real, operação persistente, tenant, consentimento, cobrança, login, grants, migration, dados de clientes, deploy nem liberação de produção. A `main` e a VPS permanecem inalteradas. Pode ir à DEV somente após revisão do diff, testes Laravel, PostgreSQL/RLS e CI Sites/Agents verdes na SHA exata, com `behind_by=0`. O fluxo comercial e revisão SEG/DBA/LGPD seguem NO-GO.
