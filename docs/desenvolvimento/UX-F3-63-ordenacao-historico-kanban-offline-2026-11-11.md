# UX F3-63 — Ordenação por pontuação e histórico local de ações no Kanban

**Data:** 11/11/2026. **Implementação:** `prototypes/f3-59-offline-kanban/index.html`, quadro offline de oportunidades fictícias.

## Funcionalidades

O Kanban agora oferece duas opções de ordenação em cada etapa do funil: **Ordem original** (ordem estável dos exemplos) e **Maior pontuação** (pontuação fictícia decrescente, desempate determinístico por identificador). A ordenação atua somente na renderização, sem mudar o estado de etapas nem impedir os filtros, movimentações, totais e ações reversíveis já presentes. A seleção mantém `aria-pressed` consistente e o foco do teclado.

A página também tem **Atividade simulada nesta aba**, com até seis descrições recentes de movimentação de etapa, criação fictícia e desfazer. Os itens são produzidos exclusivamente com textos internos das oito fixtures e oportunidades artificiais geradas pela própria página. Nenhum evento chega ao backend, vira auditoria, cria notificações, data/hora, usuário real ou trilha persistente. O botão de reinício limpa todo o histórico visível e restaura a ordenação original.

## QA

- O PHPUnit de contrato DOM/CSP verifica duas opções de ordenação, atualização de `aria-pressed`, uso de `textContent`, cap de seis mensagens e ausência de storage/inputs.
- O smoke Chromium real desktop/320px agora move uma oportunidade de pontuação inferior para a coluna Em contato, comprova que a opção `Maior pontuação` altera a ordem, restaura a inserção, verifica logs, limites e reinício; também confere ausência de overflow no celular.
- CI F0, PostgreSQL/RLS, Sites e Agents deve estar verde na SHA exata antes de merge DEV.

## Limites

Ordenação e histórico são **representação de uma simulação**, não regra de scoring homologada, operação de CRM, auditoria LGPD, alteração de stage persistente nem prova de autorização. Não há dados pessoais, sessão, API, banco, grants, migração, deploy, custo ou modificação de `main`/VPS. CORE/SEG/DBA/LGPD e revisão independente continuam sem GO.
