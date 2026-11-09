# UX-F3-27 — Eventos fictícios na ficha visual de leads

**Data:** 09/11/2026. **Natureza:** interface offline para revisão de UX, **não é histórico transacional nem auditoria**. Complementa [UX-F3-26](UX-F3-26-ficha-ficticia-leads-2026-11-09.md) e [F3-26](F3-26-contrato-negativo-historico-leads-2026-11-09.md); rastreamento [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35).

## Entrega

Na ficha de cada um dos **quatro leads inventados**, há uma **lista ordenada** de duas ou três frases predefinidas sobre etapas ilustrativas. As frases não apresentam data, autor real, contato, comentário, consentimento, histórico persistido ou fato de cliente. Nenhum registro é carregado de PHP: o contrato F3-26 **não é chamado** pelo protótipo.

- `events:Object.freeze([...])` vincula uma lista fixa a cada identificador fictício da tabela; a interface adiciona elementos `li` com `textContent`, nunca `innerHTML`.
- A ficha preserva o comportamento de uma seleção por vez e fecha quando ocultada por filtro ou ao trocar de tela. Alternar entre leads **descarta o histórico visual anterior**.
- A lista usa elemento semântico `ol` com `aria-label`; permanece dentro do painel de status de somente leitura `aria-live=polite`.
- A mensagem de segurança explicita: os eventos não têm **proveniência verificada**; visualizá-los não autoriza exportação, alteração, importação ou qualquer ato comercial.

## Evidências previstas na CI

`OfflinePrototypeLeadHistoryTest` exige quatro conjuntos literais, renderização textual e ausência de rede, formulários e armazenamentos. Os scripts de Chromium 520 CSS px e 320 CSS px verificam alternância A→C, contagens de itens, descarte de frases obsoletas, limpeza após filtro e ausência de overflow na tela pequena.

Ainda exige revisão humana de acessibilidade/WCAG e do futuro conteúdo; strings fixas **não** são comprovação temporal, assinatura, auditoria, leitor do banco ou trilha oficial. Os limites de 2–3 eventos por ficha são apenas tamanho do protótipo, não limite histórico do CRM.

**Proibições:** nenhum endpoint, API, autenticação, dado pessoal, consentimento, persistência, `ALLOW`, autorização de leitura, exportação, schema, migration, RLS, webhook, `main`, VPS ou deploy. Continuam abertos CORE/DAT, SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), INT, LGPD e FIN/CON. Integração exclusivamente DEV após revisar diff/SHAs e duas CIs verdes.
