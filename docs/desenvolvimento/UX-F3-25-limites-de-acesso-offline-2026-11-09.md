# UX-F3-25 — Três cenários de limites de acesso no protótipo offline

**Data:** 09/11/2026. **Escopo:** demonstração local e isolada de texto, sem usuário, tenant, login, backend, IAM, API ou dados reais. **Origem:** [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), continuidade de UX-F3-24 e diretrizes [CORE02-R02](CORE02-R02-pacote-decisao-matriz-autoridade-2026-11-09.md).

## Entrega

A tela existente **Prévia de importação** mantém as nove telas de navegação e recebe um grupo adicional denominado **Limites de acesso — simulação**, com três estados ilustrativos:

1. **Ler não é exportar** — leitura exibida na interface não comprova direito de exportação nem visibilidade dos campos protegidos.
2. **Outra organização** — membership declarada em uma empresa não transfere direitos a outra; a identidade/tenant precisam ser comprovados.
3. **Vínculo alterado** — sessão ou papel anteriormente válidos não bastam; a situação vigente precisa ser revalidada antes do efeito.

São apenas mensagens **predefinidas**, não conclusões de uma consulta ao CRM. Cada botão atualiza uma região `role="status"` com `aria-live="polite"`, `aria-atomic="true"` e `aria-pressed` no botão selecionado. Todos os estados apresentam **"Nenhuma exportação foi autorizada ou realizada."** O grupo está na tela de importação já existente para reforçar a distinção entre prévia, autorização e efeito.

## QA e limites

- `OfflinePrototypeAccessBoundaryTest`: estrutura, exatamente três modos, seleção inicial única, região acessível, nove telas, linguagem negativa e proibição de formulários, entradas, armazenamento e rede.
- `check-offline-prototype-browser.php`: interações reais dos três estados no Chromium a 520 CSS px.
- `check-offline-prototype-320.php`: mesmos estados em Chromium/iframe com 320 CSS px e ausência de overflow horizontal.
- A CSP mantém `connect-src 'none'` e `form-action 'none'`. Não há nova navegação, biblioteca, arquivo de cliente, exportação, permissões, créditos, persistência nem download.
- Inspeção humana de acessibilidade/WCAG permanece necessária. As mensagens de UI não são políticas executáveis nem prova de direito efetivo.

**Travas:** CORE02-D04..D10, CORE01, CORE03/04/05/07, SEG/DBA independente [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27), FIN/CON, INT e LGPD continuam pendentes. Nenhum `ALLOW`, grant, migração operacional, `main`, VPS, cliente ou produção. Merge exclusivamente DEV após revisão de diff/SHAs e os dois workflows verdes.
