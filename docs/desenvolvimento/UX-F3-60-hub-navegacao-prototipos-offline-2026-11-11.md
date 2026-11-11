# UX F3-60 — entrada única para três experiências offline do Imagine CRM

**Data:** 11/11/2026. **Entrega:** `prototypes/index.html`.

## Resultado navegável

Foi criada uma página inicial única, totalmente estática, para abrir três experiências que **já existiam de verdade no repositório**: a navegação principal de nove telas (`s01-prot-01`), o quadro Kanban interativo e fictício (F3-59) e a prévia dos riscos CSV (F3-22). Os links são relativos, locais e verificados por teste, para que abrir o arquivo HTML diretamente no computador funcione sem servidor ou login.

A página é responsiva em desktop e 320 px e contém skip link, rótulos acessíveis e aviso inequívoco de que se trata de exemplos fictícios. A CSP bloqueia scripts, rede, formulários, imagens/fontes externas e iframes. Não existe JavaScript nem informação pessoal coletada nesta entrada.

## QA

- PHPUnit `OfflinePrototypeHubTest` exige **exatamente os três links permitidos**, destinos existentes e nenhuma referência HTTP/external, input, storage, formulário ou script.
- `scripts/ci/check-offline-prototype-hub-browser.php` abre um driver temporário de teste, usa **Chromium real** para renderizar a página de CSP estrita em 1100 e 320 CSS px, verifica links, navegação por foco e ausência de overflow. A ausência de Chromium ou de marcador PASS executado falha explicitamente.
- CI F0 recebe o novo smoke; PostgreSQL/RLS e paridade Sites/Agents são executados sem afrouxamento.

## Convivência

Não modifica `prototypes/s01-prot-01/index.html`, que a PR #246 também usa enquanto aguarda revisão específica, nem inclui código da cadeia experimental Portal↔CRM. O link de volta do Kanban ao protótipo principal continua funcionando. A nova entrada pode ser usada imediatamente para revisar três superfícies locais, enquanto integrações reais permanecem suspensas.

## Gates

Protótipos **não são API**, não autenticam usuários nem processam leads. Sem alteração em `main`, VPS, produção, SQL, dados reais, gastos ou autorização `ALLOW`. F70/CORE/SEG/DBA/LGPD continuam NO-GO. Merge possível apenas em DEV com revisão de código e workflows aprovados na SHA exata.
