# CRM UX F3-76 — Foco em uma etapa do funil fictício

**Data:** 11/11/2026. **Escopo:** protótipo HTML/JS offline `prototypes/f3-59-offline-kanban/index.html`.

## Funcionalidade implementada

A navegação do Kanban ganhou um seletor de **etapa focada** com cinco opções acessíveis: Todas etapas, Entrada, Em contato, Qualificação e Proposta. Selecionar uma etapa mostra apenas sua coluna e os cartões que correspondem simultaneamente aos filtros de prioridade e situação do acompanhamento. **Nenhum cartão das outras etapas é excluído do estado**; as colunas apenas ficam visualmente ocultas. Totais de valor, contagens e a fila de acompanhamentos fictícios pendentes acompanham a mesma seleção.

Ações existentes continuam coerentes: movimentar um cartão para fora da etapa focada remove-o da visão atual (não dos dados) e devolve o foco ao seletor ativo; **Desfazer** restaura seu estágio anterior. Criar uma nova oportunidade fictícia restaura automaticamente `Todas etapas` para exibir o cartão de Entrada recém-criado. Reiniciar restabelece as quatro etapas e todos os filtros.

**Proteção importante:** a conclusão fictícia em lote passou a aplicar explicitamente **o mesmo filtro de etapa** utilizado pela visualização. O usuário não altera acompanhamentos ocultos em outra etapa. A operação segue única, reversível e limitada a até 12 cartões artificiais em memória.

## Testes

- `OfflineKanbanFunnelTest` exige cinco opções, filtro de etapa em visão **e lote**, restauração, foco acessível e política sem rede.
- O smoke Chromium real verifica desktop e viewport móvel 320 CSS px: foco em Qualificação, combinação com prioridade Alta, conclusão apenas de cartões visíveis, preservação de outras etapas, desfazer, mudança de etapa que remove o cartão da visão, foco acessível, nova oportunidade e reset. Cenários móveis verificam uma coluna visível, fila sincronizada e ausência de overflow horizontal.
- O limite de tamanho da fixture no browser smoke passa de **25 KB para 28 KB**, mantendo o controle explícito de tamanho, sem flexibilizar CSP, conexões, captura, autorização ou dados. O HTML final continua abaixo de 28 KB.

## Gates

A etapa focada é apenas uma **visão demonstrativa**, não mudança de permissão comercial ou operação multiempresa. Sem dados reais, IAM/ALLOW, serviços, redes, envios, produção, SQL, migrações, custos ou alteração em `main`/VPS. CORE/SEG/DBA/LGPD permanecem NO-GO. Merge permitido apenas DEV, após revisar diff, base atualizada e quatro workflows aprovados na SHA exata.
