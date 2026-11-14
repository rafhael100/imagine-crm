# CRM — F3-AIA: rejeição de JSON ambíguo na fronteira mock de leitura

**14/11/2026 · laboratório DEV, NO-GO.**

`AiaReadOnlyMockBoundary` tinha validação de arrays sintéticos, mas a CI Agents F219 já cobria bytes JSON canônicos. A assimetria permitia que as duas aplicações deixassem de perceber deriva de parser (chaves JSON duplicadas, aliases de escape, espaços, ordem de chaves e limite de tamanho).

O novo `hasCanonicalFixtureShape(string)` valida até 1.024 bytes e profundidade 4, em ordem exata e contrato fechado, comparando o JSON original à codificação canônica da estrutura `hasFixtureShape`. Não é HTTP, não serve como autenticação e não define o wire operacional futuro. `inspectCanonicalJson(string)` sempre retorna a mesma negação independentemente da estrutura, não expondo o resultado do parser em resposta pública.

Os testes PHPUnit exercitam payloads válidos e **14 mutações hostis**, e o runner bilateral `scripts/ci/check-aia-op01-json-exchange.php` passa a verificar as duas implementações PHP com os códigos originais de Agents em SHA revisada.

Sem rota, token, API, autorização, migração, dado de cliente, escrita, VPS, gasto ou `main`. A [CRM PR #57](https://github.com/rafhael100/imagine-crm/pull/57) segue **OPEN/DRAFT** e **14 gates / 13 cenários reais** permanecem **NO-GO**. O parser é uma prova negativa de compatibilidade, não aceite de produção.
