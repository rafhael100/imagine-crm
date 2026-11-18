# Central de Automações em português, inglês e espanhol

Desenvolvimento privado e sintético. A revisão visual do artifact da
[PR #643](https://github.com/rafhael100/imagine-crm/pull/643) mostrou o menu em
inglês e quase todo o conteúdo de Automações/Preferências ainda em português.
O seletor de idioma do cabeçalho também removia `section` da URL e levava o
usuário de volta à visão geral.

## Comportamento

A seção compartilhada de Automações e Preferências usa o idioma ativo para
títulos, explicações, regras, prazos, versões, histórico, estados das execuções,
ações, limites, avisos e mensagens vazias. Prazos persistidos fora dos atalhos
também recebem o rótulo correto em cada idioma. Identificadores, valores enviados,
datas UTC, quotas e regras de negócio não mudam.

O cabeçalho conserva a seção validada quando troca o idioma dentro do workspace.
Reconstrói a query somente com `section` e `lang`, sem propagar parâmetros
arbitrários. Em telas públicas e administrativas continua enviando somente
`lang`. Um `section` em formato de array retorna à visão geral em vez de causar
erro de tipo. Textos comuns do ambiente privado, resumo do workspace, trial e rodapé
também acompanham o idioma.

Trocar a linguagem de visualização pelo cabeçalho não grava a preferência no
banco nem altera regras ou prazos. O formulário de idioma continua sendo o fluxo
explícito de persistência da PR #643, com CAS e limite de revisões preservados.

## Verificação

O harness Chromium existente navega por Automações e Preferências e clica nos
três links de idioma. Verifica a seção da URL, título, modelos, prazo de 5
segundos, estado concluído, botão habilitado e igualdade do FormData em cada
passagem. Examina largura de página em 390 e 1440 pixels e gera capturas em
espanhol, além da captura em inglês da revisão 20.
Também envia `section[]` e um parâmetro extra, exige resposta 200 na visão geral
e verifica que os links reconstruídos propagam somente a seção válida e o idioma.

A compilação Blade, as jornadas HTTP, as submissões de preferências, os fluxos
preexistentes e as capturas reais dependem da CI da SHA publicada. O resultado
será registrado na PR; parse JavaScript local não substitui execução em browser.

Este recorte localiza a Central de Automações/Preferências e o shell comum. Não
declara tradução completa das demais telas do CRM nem aceite visual humano.
Mantém DRAFT / NO-MERGE / NO-DEPLOY.
