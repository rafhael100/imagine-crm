# CRM DS-V1 — integração visual consolidada para revisão (12/11/2026)

**Status:** DRAFT / NO AUTO MERGE / NO DEPLOY. **Base:** commit DEV `f19e0323b36d9fe5670c4157ab45f7737a6155c4`, após F3-198. **Escopo:** apenas UI sintética offline, QA e documentação. [Issue #416](https://github.com/rafhael100/imagine-crm/issues/416) continua exigindo aceite visual humano.

## Proveniência exata
- [PR #418](https://github.com/rafhael100/imagine-crm/pull/418), HEAD `ebf2d56c78e7588148a646327c25748e82932cc2`: tokens originais Node, banner principal, dashboard, Kanban, hub, logo e testes de recursos seguros.
- [PR #420](https://github.com/rafhael100/imagine-crm/pull/420), HEAD `1896e3a8f405d103623be640499206ef3a8a0dd2`: logo também em Kanban e hub; é filha da #418. Copiadas as versões de arquivos da branch filha para não perder as mudanças.
- [PR #421](https://github.com/rafhael100/imagine-crm/pull/421), HEAD `2b42b63c8d599b940269368c5be5047f294a0096`: shell do protótipo principal com **9 telas em 3 grupos**, breadcrumb ativo e alvo móvel 44px; prevalece apenas nos arquivos concorrentes do protótipo principal e seus testes de Chromium.
- [PR #419](https://github.com/rafhael100/imagine-crm/pull/419), HEAD `2e15bee147b4ded7edf9a9a72ec5606d424e3d69`: identidade nas outras **10 páginas independentes** com testes e capturas comparativas.

**Regras de composição:** preservar a `.github/workflows/ci-f0.yml` atual da DEV, incluindo o QA Chromium do novo protótipo F3-197, e acrescentar somente o teste de identidade das dez telas. Não trazer versões antigas de Laravel, Core, banco, quotas, endpoints ou políticas de acesso dos branches visuais. O PNG `prototypes/assets/logo-imagine.png` usa a mesma blob SHA original `678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77`.

## Evidências exigidas
1. Quatro pipelines da HEAD exata: F0/Laravel, PostgreSQL/RLS, paridade Sites, paridade Agentes.
2. QA Chromium de logomarca original no hub/Kanban; shell desktop, mobile 320px, menus e foco; 10 telas adicionais no Chrome 1100/320px; F3-197 da DEV preservado.
3. **66 PNGs de comparação com DEV anterior à identidade visual**, 11 superfícies × antes/depois × 3 larguras (1440, 768, 320), com manifestos SHA-256 por página. São evidências técnicas, não aceite humano.
4. Revisão humana de contraste, zoom 200%, estados de erro/vazio e navegação/teclado/touch real por página antes de integrar a DEV; o ambiente autenticado de produção ainda não possui telas homologadas.

## Autorizações ausentes e não inferidas
Não ativar qualquer rota, sessão real, identidade interserviços, cliente, tenant, migrations, RLS operacional, acesso de produtos ou custos. As issues [#27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27) e [#259 CORE/SEG/DBA/LGPD](https://github.com/rafhael100/imagine-crm/issues/259) permanecem NO-GO. Design System separado de autorização operacional.
