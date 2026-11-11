# UX F3-62 — Totais fictícios por etapa no Kanban

**Data:** 11/11/2026. **Escopo:** interface `prototypes/f3-59-offline-kanban/index.html`, exclusivamente offline.

## Funcionalidade

Cada uma das quatro etapas do funil agora exibe seu **valor fictício total**, formatado em reais, calculado a partir das oportunidades que estão visíveis no filtro. O total muda imediatamente ao filtrar por prioridade, mover um cartão, desfazer a ação, criar oportunidades fictícias e reiniciar a simulação. Esses valores são totalmente inventados; não são contratos, previsões comerciais, preços ou receitas reais.

A regra utiliza `reduce` nos objetos que já estão em memória, mantendo um único estado da interface e atualizando os valores junto com as contagens existentes. Não há API, armazenamento, planilha exportada, sincronização, transação ou novos dados de contato.

## Testes

- `OfflineKanbanFunnelTest` exige os quatro indicadores de etapa, formato e função de atualização.
- O teste **Chromium real desktop/320 CSS px** verifica os totais iniciais, filtros de alta prioridade, movimentação, adições fictícias e reset, incluindo a ausência de overflow.
- F0/Laravel, PostgreSQL/RLS e CI cruzada Sites/Agents permanecem obrigatórias na SHA exata do PR antes de integração DEV.

## Restrições

A composição de valores é um indicador **de demonstração**, não a aprovação de estágio comercial, classificação financeira, scoring homologado, política multiempresa ou autorização de ações. Não há alteração em `main`, VPS, production, IAM, LGPD, dados pessoais, implantação ou custos. O gate de revisão independente permanece inalterado.
