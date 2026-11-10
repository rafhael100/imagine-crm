# SEC-UX F4-31 — Tripwire de recursos da worklist offline

**Data:** 10/11/2026. **Escopo:** arquivo fictício `prototypes/f4-28-followup-worklist/index.html`. Testes inertes, sem mudança na execução do CRM ou em controles de autorização.

## Problema

O contrato estático anterior verificava a presença de diretivas CSP e a ausência de algumas strings de APIs de rede, mas não inspecionava de forma estruturada links e atributos HTML que poderiam introduzir navegação externa, formulários ou recursos de terceiros após uma edição do protótipo.

## Testes adicionados

`tests/Unit/OfflineFollowUpWorklistResourceBoundaryTest.php` usa `DOMDocument`/XPath com `LIBXML_NONET` para:

- exigir exatamente uma meta CSP com `default-src`, `connect-src`, `form-action`, `img-src`, `font-src`, `object-src` e `base-uri` em `'none'`;
- rejeitar `form`, `iframe`, `object`, `embed`, `base`, `link`, `img`, `picture`, `video`, `audio`, `source`, `track` e `svg`;
- rejeitar atributos `src`, `srcset`, `srcdoc`, `action`, `formaction`, `poster`, `ping`, `data`, `background` e manipuladores de evento inline `on*`;
- permitir apenas os dois links locais já esperados (`#main` e retorno ao painel F4-15); recusar rotas extras, externas, `target` e `download`;
- recusar `@import` e `url(...)` em CSS inline;
- comprovar por testes negativos deliberados que alterações para URL externa, imagem remota, manipulador de evento, CSP relaxada ou `@import` geram detecção.

## Limitações e gates

Um tripwire sintático, mesmo com mutações de regressão, **não é uma prova de isolamento de rede em runtime nem auditoria de segurança geral**. Ele não analisa todas as APIs JavaScript possíveis, regras de CSS geradas dinamicamente, comportamento de extensões de navegador, direitos de leitura ou fluxos de dados reais. O teste Chromium cobre a interação do protótipo, mas não autoriza usuários.

Este PR não modifica a página, as regras de negócio, `main`, banco, VPS ou produção. Contratos CORE, S01, LGPD e parecer independente SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27) permanecem abertos; o [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) continua DRAFT sem parecer. Integração só DEV e com os workflows F0 e PostgreSQL/RLS verdes na HEAD exata.
