# CRM UX F3-83 — Avanço reversível de etapas em lote (simulação)

**Data:** 11/11/2026. **Escopo:** exclusivamente `prototypes/f3-59-offline-kanban/index.html`, em memória, com oportunidades fictícias.

## Funcionalidade

O Kanban de laboratório passa a oferecer o botão **Avançar etapas visíveis (simulação)**. Uma única ação avança uma etapa para cada oportunidade que **atenda simultaneamente** aos filtros ativos de prioridade, acompanhamento e etapa **e** esteja em uma das três etapas não finais. Cartões ocultos pelos filtros e os já presentes em **Proposta** permanecem intactos. A contagem de cartões elegíveis aparece no rótulo acessível do botão; o botão é desabilitado quando não há oportunidade elegível.

Cada clique registra **um único histórico reversível** (`kind=batch-move`), com os IDs fictícios e as etapas anteriores. O clique em **Desfazer ação** restaura todas as etapas afetadas num único passo, inclusive se os filtros mudarem após o avanço. Nenhuma entidade é excluída do estado: sob foco em uma etapa, cartões movidos para a etapa seguinte desaparecem apenas da *visão filtrada*, com foco realocado ao seletor de etapa. Totais de oportunidades e valores por etapa, fila de pendências e painel de atividades são recalculados a partir do mesmo array local. O reinício restaura as oito fixtures iniciais.

## Testes

`OfflineKanbanFunnelTest` valida que as três condições de filtro e o limite da etapa final aparecem **tanto** na renderização **quanto** no cálculo do lote, com rollback atômico, sem APIs ou persistência. `scripts/ci/check-offline-kanban-browser.php` executa **Chromium real** no desktop e viewport 320 CSS px, verificando:

- Avanço de seis oportunidades elegíveis numa única ação, enquanto duas em Proposta permanecem intactas; desfazer devolve duas oportunidades por etapa.
- Foco em Entrada + prioridade Alta: somente o cartão correspondente avança; outra oportunidade oculta permanece em Entrada; foco retorna ao filtro após a coluna se esvaziar, sem perder dados; desfazer restaura.
- Etapa final Proposta: botão desabilitado; prioridade Alta em todas etapas: avança apenas elegíveis, preservando cartões ocultos e terminais, com undo integral.
- Regressão móvel de movimentação, isolamento, foco e ausência de overflow.

HTML final mantém menos de **28.000 bytes UTF-8**, o limite preexistente do smoke; não foram enfraquecidas as verificações de CSP, conexão, armazenamento ou inputs. CI F0, PostgreSQL/RLS, Sites e Agents na SHA exata e `behind_by=0` são obrigatórios para merge somente DEV.

## Limites

Não é uma operação real sobre leads, não confere identidade, autorização, auditoria, consentimento, evento comercial ou tenant. Nenhum formulário, dado pessoal, API, banco, rede, exportação, IAM/ALLOW, SQL, migração, `main`, VPS, custo ou produção foi alterado. CORE/SEG/DBA/LGPD permanecem NO-GO para operação real do CRM.
