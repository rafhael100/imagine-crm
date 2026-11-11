# CRM UX F3-75 — Fila de acompanhamentos fictícios pendentes

**Data:** 11/11/2026. **Escopo:** interface local `prototypes/f3-59-offline-kanban/index.html`, apenas dados inventados.

## Funcionalidade

O Kanban recebe uma **fila de acompanhamentos fictícios pendentes**, apresentada abaixo do funil. A fila não tem banco, serviço, cache ou estado próprio: é derivada exatamente dos **mesmos cartões visíveis** nos filtros combinados de prioridade e acompanhamento. São incluídas apenas oportunidades com situação `pending`, ordenadas por pontuação **fictícia** decrescente, com desempate determinístico pelo ID inventado. Mostra oportunidade, etapa, pontuação e um botão **Localizar cartão**.

Selecionar Localizar leva o foco ao controle de acompanhamento no cartão correspondente do próprio Kanban e o traz à área visível, sem alterar sua etapa, situação ou histórico. Como a fila é derivada da mesma coleção em memória, criação de oportunidades, conclusão individual ou em lote, desfazer, filtros e reinício atualizam a lista imediatamente, sem inconsistência de contagens. Quando não existem pendências visíveis, exibe uma mensagem explícita em vez de linhas inventadas.

O layout usa tabela semântica, botões nativos e um contêiner com rolagem horizontal **interna**, para preservar a largura da página em 320 CSS px. O HTML foi mantido **abaixo do limite existente de 25.000 bytes** da CI, sem enfraquecer o teste de tamanho ou a CSP. Sem captura, formulários, API, chamadas externas, cookies, armazenamento, dados de clientes ou notificações.

## QA

- `OfflineKanbanFunnelTest`: verifica origem dos dados no mesmo subconjunto filtrado, ordenação, botões e foco, CSP e ausência de rede/persistência.
- `check-offline-kanban-browser.php`: Chrome/Chromium **real** desktop/320 CSS px; valida ordenação inicial por pontuação, navegação com foco, filtros de prioridade e acompanhamento, novos cartões, conclusão individual, lote, desfazer, reset e ausência de overflow.
- Integração DEV exige F0 (Laravel + browser), PostgreSQL/RLS, Sites e Agents verdes na HEAD exata, revisão de diff e `behind_by=0`.

## Limites

A fila é um **protótipo de trabalho**, não agenda operacional, SLA, CRM real, autorização de contato ou revisão de segurança. CORE, IAM, SEG/DBA e LGPD permanecem NO-GO para dados reais e ações por empresa. Sem alteração em `main`, VPS, produção, custos, SQL, API, grants ou segredos.
