# Preferências com lista de regras nula

Data: 18/11/2026. Candidata de desenvolvimento sintético,
**DRAFT / NO-MERGE / NO-DEPLOY**.

## Problema observado nas fontes

O Controller declara `rules` como `nullable|array|max:2`, mas encaminhava
`$r->input('rules', [])` a `Workspace::preferences`, cujo segundo parâmetro
exige `array`. O valor padrão de `input` cobre a ausência da chave; uma chave
presente com valor `null` continua nula.

Isso ocorre com JSON `"rules": null` e com o campo de formulário `rules=`.
A entrada privada passa por `public/index.php` e `bootstrap/app.php`, sem
substituição da pilha global padrão. Nessa pilha, o Laravel transforma a
string vazia em `null` antes da validação. A regra `nullable` aceita esse
valor, mas a assinatura do Workspace não o aceita.

As fontes da dependência estão fixadas no `composer.lock`, Laravel
`v13.40.0`, commit `1449bf241017087fbd38c118ecaabef3f6797fdc`:

- [ConvertEmptyStringsToNull](https://github.com/laravel/framework/blob/1449bf241017087fbd38c118ecaabef3f6797fdc/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php) transforma a string vazia em nulo.
- [Request::input](https://github.com/laravel/framework/blob/1449bf241017087fbd38c118ecaabef3f6797fdc/src/Illuminate/Http/Concerns/InteractsWithInput.php) usa `data_get` sobre os dados da solicitação.
- [data_get](https://github.com/laravel/framework/blob/1449bf241017087fbd38c118ecaabef3f6797fdc/src/Illuminate/Collections/helpers.php) preserva o valor de uma chave presente, inclusive nulo.
- [Validator](https://github.com/laravel/framework/blob/1449bf241017087fbd38c118ecaabef3f6797fdc/src/Illuminate/Validation/Validator.php) trata a marcação `nullable` antes das regras não implícitas.

Pelo caminho de código anterior, a passagem de nulo ao parâmetro tipado
provocaria um `TypeError`, tratado pelo handler privado como 503 antes da
transação do Workspace. Esse resultado é uma inferência das fontes; esta
candidata não declara ter executado uma solicitação HTTP histórica.

## Correção

O Controller passa a encaminhar `$r->input('rules', []) ?? []`. O nulo
assume o mesmo significado de lista vazia já usado quando o campo está
ausente. A validação anterior à chamada permanece: valores de outros tipos,
quantidade de regras e nomes permitidos continuam sujeitos às mesmas regras.

O Workspace mantém sua assinatura, autorização, lock e comparação da versão.
Se regras, atraso e idioma já são iguais aos valores atuais, o salvamento
permanece uma operação sem alteração de dados ou auditoria. A mudança não
acrescenta uma revisão de automação nem uma regra nova de agendamento.

## Prova pela rota real

O harness usa a pausa nominal já existente no roteiro, quando as regras
persistidas estão vazias. Obtém a empresa e a versão pela rota autenticada
de estado; lê idioma, atraso e configuração persistida pelo snapshot; obtém
o token CSRF do formulário real de configurações.

Executa dois POSTs autenticados para `/preferencias`, um com `rules=` em
formulário e outro com `"rules": null` em JSON. Ambos devem retornar 303
com o destino de sucesso da própria origem privada: `/painel?section=settings`.
O destino faz parte da asserção, porque uma falha de validação também pode
retornar 303, mas para `/painel`.

Depois de cada operação, as linhas completas de leads, tasks, events,
preferences, orders e automation_runs das duas empresas devem continuar
idênticas ao snapshot anterior. Versão, idioma e atraso usados na solicitação
vêm do estado observado, sem assumir valores padrão. Nenhuma fixture,
pausa, reativação ou execução de automação é acrescentada ao roteiro.

O ensaio observa os dois salvamentos atuais em uma configuração já pausada.
O fluxo nominal anterior de ausência de `rules` e a reativação posterior
permanecem. Não se atribui a esses dois POSTs uma prova nova de cancelamento
de regras ativas; os regressivos existentes conservam esse escopo.

## Linhagem e validação

Pai único: [#666](https://github.com/rafhael100/imagine-crm/pull/666),
commit `2a6d2950c54873ebbb233b64ef7985e0750c0576`, árvore
`31eea97408f0fd05752ee4fb643b50965309a2f8`.

Branch: `fix/crm-nullable-automation-preferences-2026-11-18`.
Base DEV: `dev/fast-track-crm-2026-11-04`, commit
`d7c7aacc766c7ade4c65dcd211ed4569442144a9`.

O guard owner acrescenta a oitava tupla exata de branch e base, exigindo
nove ancestrais, incluindo a #666. Os sete contextos anteriores permanecem.
A condição é de ancestralidade; não fixa o pai imediato dos futuros commits.

Vinte e um testes locais de admissão passaram: oito tuplas válidas, 24
combinações inválidas recusadas antes de Git e recusa da ausência de qualquer
um dos nove ancestrais. Os seis testes da fronteira de sessão permanecem.

A revisão local verifica o delta do Controller, o posicionamento do ensaio,
AST Python, guard, preimages, modos e reconstrução exata do patch. A CI da
HEAD publicada deve confirmar PHP, HTTP, PostgreSQL, navegador e os demais
regressivos. Resultados e contagens terminais serão registrados na PR.

A #666 já passou em 25 workflows e preservou os dois gates de escopo
bloqueados. Esses resultados pertencem à HEAD da #666; não aprovam esta
candidata por herança. F251/F252, schema, rotas, fixtures e critérios
operacionais permanecem preservados.
