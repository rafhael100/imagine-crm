# CRM UX F3-88 — Histórico de desfazer limitado a 64 ações fictícias

**Data:** 11/11/2026. **Escopo:** somente JavaScript em memória do Kanban local, sem persistência nem dados reais.

## Problema e correção

O Kanban de demonstração registra um *snapshot de desfazer* para cada movimentação individual, conclusão/reabertura de acompanhamento, avanço/retorno em lote, conclusão em lote e criação fictícia. Sem limite, uma aba aberta por muito tempo ou automações de UI poderiam acumular uma quantidade indefinida de snapshots em memória.

Foi introduzido **`MAX_UNDO_ACTIONS=64`** e a função `trimUndoHistory()`, chamada **imediatamente após as cinco operações `history.push()`** já existentes. Se o histórico exceder 64, os eventos mais antigos são descartados. As **últimas 64 ações** continuam integralmente reversíveis por `Desfazer ação`, incluindo lotes com vários cartões, e o controle desabilita quando o histórico acaba. A apresentação dos **seis eventos recentes** permanece separada e a interface agora esclarece a janela de 64 ações. O botão Reiniciar continua limpando todo o histórico e retornando ao estado original.

## QA

- `OfflineKanbanFunnelTest` exige o limite, a rotina de descarte, **cinco** pontos de registro seguidos do ajuste, ausência de rede/storage e preservação de `history.pop`.
- `check-offline-kanban-browser.php` usa **Chromium real**: executa **69 alternâncias fictícias** do acompanhamento F01, depois exatamente **64 desfazeres**, verifica que o histórico foi exaurido e as cinco ações antigas não são mais reversíveis, sem extrapolar memória, e que Reiniciar restaura estado/contagem. No viewport 320 CSS px, verifica cinco ações, cinco desfazeres, estado restaurado, foco e ausência de overflow horizontal.

## Limites e gates

Esta é uma proteção de memória do **laboratório**, não retenção oficial de CRM, auditoria, trilha jurídica, SLA, backup ou política LGPD. Os seis eventos visíveis e as 64 ações de undo **não** representam retenção do produto. Nenhuma API, serviço, dados pessoais, SQL, migration, IAM/ALLOW, `main`, VPS, produção ou custo foram alterados. Merge somente DEV após F0/PG-RLS/Sites/Agents success na HEAD exata, diff e `behind_by=0`. CORE/SEG/DBA/LGPD permanecem NO-GO para operações reais.
