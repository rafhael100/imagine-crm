# UX F3-61 — Simular criação de oportunidades e desfazer no Kanban

**Data:** 11/11/2026. **Produto afetado:** `prototypes/f3-59-offline-kanban/index.html`.

## Experiência implementada

O Kanban offline passou a ter o controle **Adicionar oportunidade fictícia**. Ao selecionar o botão, a interface gera uma ficha com identificador, nome, valor e pontuação determinísticos e totalmente inventados, na etapa Entrada. Não há formulário ou digitação: os dados são exclusivamente de laboratório.

A simulação começa com oito oportunidades e permite **no máximo doze cartões na memória da aba**. Ao atingir o limite, o botão fica desabilitado e seu rótulo acessível explica a restrição. A criação muda o filtro para Todas e posiciona o foco no cartão gerado, facilitando a revisão por teclado. As contagens e valores são recalculados instantaneamente.

O histórico existente de desfazer passou a distinguir os tipos de ação: **movimentação de etapa** e **criação fictícia**. Desfazer uma criação remove somente a ficha simulada mais recente; desfazer uma movimentação devolve a ficha à etapa anterior. O comando de reinício elimina todos os exemplos criados e restaura os oito exemplos originais, inclusive IDs, prioridade, filtros e contagem de ações.

## Validação verificável

- PHPUnit `OfflineKanbanFunnelTest` agora exige os controles de criação, limite, tipos explícitos de histórico, reinicialização e ausência de formulário/API/storage.
- O browser smoke real `check-offline-kanban-browser.php` valida criação de quatro fichas fictícias, bloqueio da décima terceira, desfazer e reutilização da capacidade; em 320 CSS px, valida criação/desfazer/reinício e ausência de overflow. A CI F0 já executa esse script com Chromium real.
- F0, PostgreSQL/RLS, Sites/Agents permanecem gates de integração na SHA exata.

## Limites

Somente estado em memória de uma página offline. **Não** é formulário de cadastro, criação de lead real, alteração persistente, permissão, identidade, tenant, fatura, integração externa, API ou banco de dados. `main`, VPS, produção e dados de clientes permanecem preservados; CORE/SEG/DBA/LGPD seguem sem aprovação operacional.
