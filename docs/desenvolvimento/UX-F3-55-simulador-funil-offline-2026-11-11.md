# CRM UX F3-55 — Simulador reversível de funil comercial (somente offline)

**Propósito de avaliação de produto:** a tela de Funil ilustrativo do CRM aponta para um simulador separado em `prototypes/f3-55-sales-pipeline-lab/index.html`, que permite movimentar **quatro leads inteiramente fictícios** entre Entrada, Em contato e Qualificação, verificar o impacto nas contagens, desfazer o último movimento e restaurar o exemplo completo. Não existem entradas para pessoas reais, contas, sites, oportunidades, sessões, fila, histórico de CRM ou autorização.

## Comportamento validável

- Quatro exemplos fixos imutáveis têm pontuações 30, 55, 85 e 70, as mesmas do protótipo principal. A distribuição inicial é `2/1/1`. A estrutura da fixture corresponde ao classificador `LeadScoringBands` e ao `SyntheticPipelineSummary` do núcleo PHP existente.
- Apenas botões nativos selecionam cards e avançam/retornam uma etapa; controles fora das bordas ficam desativados. **Nenhum drag-and-drop obrigatório** ou gesto dependente de mouse.
- Uma pilha em memória permite desfazer movimentos; limita-se às últimas 20 ações. O histórico visual mantém até 40 entradas, exibindo as 8 mais recentes. O botão Restaurar limpa estados, contadores, seleção, histórico e undo, sem persistência.
- O botão do card selecionado recebe `aria-pressed=true`; ao reconstruir os cards, o foco retorna ao botão selecionado para preservar navegação por teclado. A seleção, resultados e histórico são anunciados por regiões `aria-live`.
- O HTML tem CSP local `default-src 'none'`, `connect-src 'none'`, `form-action 'none'`, sem recursos externos, formulários, `fetch`, WebSocket, cookies, storage, exportação ou ações de negócio. Tudo fica em `file://` e é descartado ao recarregar. A transição feita na página **não corresponde à mudança real de estado de um lead**.

## QA e documentação

`tests/Unit/OfflineSalesPipelineSimulatorTest.php` cobre metadados e paridade dos quatro exemplos com o resumo PHP, distribuição por etapa e faixa, controles acessíveis e proibições de rede/dados. `OfflineSalesPipelineNavigationTest.php` comprova o link local de ida e volta. `scripts/ci/check-offline-sales-pipeline-browser.php` executa **Chromium real em desktop e 320 CSS pixels**, com seleção, foco, avanço, retorno, tentativa de ir além da última etapa, desfazer, restauração, contadores e ausência de overflow. O workflow da PR F3-54 executa essas verificações, os protótipos de reconciliação/CSV e os gates existentes F0 e PostgreSQL/RLS.

**Nenhuma regra de movimentação, permissão, scoring, automação, SLA ou histórico produtivo é alterada.** Essa entrega não constitui a implementação funcional do módulo de funil ou um sistema de auditoria. Persistência de leads, tenant autenticado, escopo, consentimento, trilha real, proteção LGPD e homologação SEG/DBA/CORE permanecem obrigatórios. F70 Imagine Comercial não foi liberada.

**Ramo e PR:** `dev/f3-54-reconciliation-ui-focused-dev-2026-11-11`, PR draft #246 contra DEV, sem merge, sem deploy, VPS, DNS ou dados reais.
