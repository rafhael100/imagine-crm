# CRM UX-F4-28 — Worklist offline dos acompanhamentos fictícios

**Data:** 10/11/2026. **Branch alvo:** `dev/fast-track-crm-2026-11-04`. **Referências:** F4-27 PR #176; painel offline PRs #164–#172; gates SEG/DBA #27 / CORE / LGPD.

## Implementação

Nova página `prototypes/f4-28-followup-worklist/index.html` ligada por links locais bidirecionais ao painel F4-15, sem alteração do protótipo principal S01 nem das superfícies Laravel. O usuário de teste pode trocar duas amostras fictícias, aplicar quatro filtros de prazo e paginar a lista. Os IDs e as datas são inventados; a lista não representa dados reais.

A página contém uma tabela sem campos editáveis, paginação de 2 registros, contagem por filtro e total da amostra, rótulos claros para estado vazio, CSP `connect-src 'none'` e `form-action 'none'` e ausência de código de rede, persistência ou inserção de HTML executável.

## Verificações

1. `tests/Unit/OfflineFollowUpWorklistParityTest.php` reconcilia os registros explícitos em JS com `FollowUpFixtureWorklistPage` do PHP para três páginas, quatro filtros, ordenação, contagens e cenário vazio, além das fronteiras estáticas de recursos.
2. `tests/Unit/OfflineFollowUpNavigationContractTest.php` verifica caminhos exclusivamente locais entre protótipos.
3. `scripts/ci/check-followup-dashboard-browser.php` passa a verificar também o novo link no painel anterior.
4. `scripts/ci/check-followup-worklist-browser.php` executa Chromium desktop e 320 CSS px com seleção, paginação, foco, estados vazios e ausência de overflow na página.
5. `.github/workflows/ci-f0.yml` incorpora o novo smoke como etapa obrigatória, sem dependências adicionais.

## Não implementado / NO-GO

Nenhum endpoint de leitura nem escrita; ausência de um usuário autenticado verificável, vínculo/tenant, política por campos, consultas por RLS, revogação, armazenamento, autorização de IDs ou observabilidade real. O F4-27 é um serviço de laboratório PHP e o F4-28 é demonstração HTML/JS **independente**; concordância dos dados sintéticos não fornece autorização.

Sem `ALLOW`, SQL, migração, dados pessoais, deploy, alteração da `main`, custo externo ou mudança de VPS/produção. Revisão humana de acessibilidade ainda é necessária; parecer independente SEG/DBA [issue #27](https://github.com/rafhael100/imagine-crm/issues/27) não foi concluído. O PR #175 permanece DRAFT separado e não foi modificado.
