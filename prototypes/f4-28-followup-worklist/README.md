# UX F4-28 — Lista de acompanhamentos fictícios (offline)

Abra `index.html` em um navegador por meio de `file://`. Não exige instalação, Laravel, banco, VPS, autenticação, serviços externos nem conexão à internet. O painel fictício anterior `../f4-15-followup-dashboard/index.html` oferece um link local para esta tela e vice-versa.

## Comportamento demonstrativo

- Amostra A: cinco registros inteiramente inventados, relógio fixo `2026-11-10T10:00:00Z`. Os IDs `fixture-...` e as datas não identificam nenhuma empresa/cliente real.
- Amostra B: zero registros; a interface avisa que isso **não comprova tarefas concluídas**.
- Filtros `Todos`, `Vencidos`, `Até 24h` e `Após 24h`, com botões nativos, `aria-pressed`, foco visível e atualização de resumo `aria-live`.
- Ordenação por instante absoluto (datas com offsets explícitos), seguida do identificador fictício; opção de 2 ou 5 linhas por página, botões anterior/próximo com bloqueios de limite.
- Tabela rolável apenas horizontalmente no próprio contêiner, sem alargar a página no viewport de 320 CSS px; status e mensagens usam texto não executável.
- Nada importa, exporta, altera, salva, envia ou dispara operações.

## Evidência e limites

`OfflineFollowUpWorklistParityTest` extrai os cinco registros fixos do código do navegador e verifica a paridade de ordenação, filtros, contagens e paginação com `FollowUpFixtureWorklistPage` (PHP F4-27), além do CSP e da ausência de capturas, armazenamento e conexão.

`php scripts/ci/check-followup-worklist-browser.php` realiza um smoke **executado no Chromium** com interação entre filtros, páginas, amostra vazia e navegação/foco, também em iframe com viewport real de 320 CSS px. O workflow `ci-f0.yml` exige aprovação desse teste. Ele não é auditoria WCAG, teste de sistemas reais nem homologação de segurança de tenant.

**Não há integração JS/PHP operacional:** há duas implementações em ambientes distintos comparadas por fixtures estáticas em testes. `Date.parse` no protótipo serve apenas a horários fictícios com segundos inteiros; o backend continua responsável por seu contrato de microssegundos, que não foi replicado aqui.

**Não é autorização:** nenhum dado real, login, consulta SQL, controller, middleware, grant, `ALLOW`, migration, entitlement ou API foi introduzido. IDs fictícios seriam informações protegidas se correspondessem a registros reais; a aplicação futura exigirá origem confiável, membership ativo, direitos por campo e ação, política de minimização/LGPD e revisão [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27). O pacote [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) continua DRAFT, sem parecer e sem merge. Sem mudanças em `main`, VPS ou produção.

## UX F4-30 — Detalhes locais, somente leitura

Cada linha da amostra inventada agora apresenta o botão **Ver detalhes**. O botão revela uma região acessível com o mesmo identificador, prazo e faixa já mostrados na tabela; não há contatos, anotações privadas, histórico real ou qualquer mudança de status. A abertura transfere o foco para a região, e **Fechar detalhes** ou a tecla **Escape** fecham a região e recuperam o foco no botão de origem, quando ele ainda existe.

Ao mudar amostra, filtro, tamanho da página ou posição da paginação, a região é fechada e seu conteúdo é zerado para evitar dados residuais de um registro que saiu da tela. Os campos são inseridos exclusivamente por `textContent`, não por HTML interpretável.

**Precisão da data:** as datas exibidas mantêm o formato ISO 8601 e seu offset original, como `2026-11-10T08:00:00-03:00`; por isso o cabeçalho e o painel de detalhe não as rotulam incorretamente como se todas fossem UTC. O classificador calcula comparações por instante absoluto, e o PHP F4-27 devolve seu próprio formato normalizado; este protótipo não é integração ativa com o backend.

Os testes `OfflineFollowUpDetailContractTest` e o smoke real Chromium desktop/320 px verificam abertura, foco, Escape, fechamento, ausência de detalhes antigos e rótulos de fuso. Essas provas de regressão **não** representam auditoria de acessibilidade completa, direito de consulta a um lead, autenticação, autorização por empresa nem conformidade LGPD. O modo offline e os bloqueios SEG/DBA/CORE permanecem.
