# CRM F3-193 — prévia de lote de movimentação Kanban (12/11/2026)

**Escopo:** core PHP puro que compõe F3-192 com a seleção explícita de até 12 cartões 100% inventados (F01–F12). Não conecta ao protótipo web, banco ou API.

## Regras da prévia
- Lote 1–12, IDs sintéticos únicos, exatamente sete campos por cartão, cinco flags booleanas e quatro listas de requisitos de etapa, histórico até 24 eventos.
- Drag de cartão **selecionado** considera somente cartões simultaneamente selecionados e visíveis; drag de **não selecionado** considera só o próprio cartão. Cartão oculto não entra no conjunto.
- Valida **todos os cartões**, inclusive os ocultos e fora da seleção: não permite PII ou valores malformados escondidos atrás de filtro.
- Combina revisão atual, encerramento, mesma etapa, limite de histórico e campos obrigatórios para cada posição.
- Retorna posições numéricas e códigos allowlist de impedimento; nunca devolve os IDs do cartão, eventos, PII ou dados do cliente. Não muta os parâmetros.

## Autoridade
A interface offline e o serviço PHP compartilham **apenas semântica de simulação**: seleção/visibilidade são alegações de entrada. O resultado `criteria_positions` significa apenas campos aparentemente completos, **não** autorização, CAS, privilégio de tenant ou permissão de alterar o CRM. `authorized`, `can_bulk_move`, `can_move`, `can_persist`, `identity_verified`, `tenant_verified` e `concurrency_verified` ficam sempre `false`. Não habilitar endpoint, migration, fila, download, rede, cadastro nem produção.

## Gates
QA PHPUnit inclui lote misto, cartões ocultos, selecionados/não selecionados, conflito de revisão, oportunidade encerrada, limites e insumos malformados. Independentemente de CI verde, P0 SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27) e CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259) continuam NO-GO para operações reais.
