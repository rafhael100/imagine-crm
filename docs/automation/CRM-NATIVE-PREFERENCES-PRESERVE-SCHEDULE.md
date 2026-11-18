# Preferências preservam os acompanhamentos quando a automação não muda

Base funcional: PR #640, `0bd2742eca1e0bc042875a6eae629d161a1fce81`.
Base de integração: PR #642, `6376fc23497ca1e4df81e43b8e4e487659d03873`.
Estado: candidato de desenvolvimento privado; os testes HTTP/PostgreSQL devem
ser confirmados na CI da SHA publicada. Não representa homologação operacional.

## Defeito observado

O formulário de preferências envia idioma, regras e atraso em uma única request.
O servidor criava uma revisão de automação e cancelava todas as execuções
pendentes em qualquer salvamento. Mudar apenas o idioma ou reenviar os valores
atuais descartava acompanhamentos válidos e consumia uma das 20 revisões do
laboratório.

## Comportamento corrigido

O servidor mantém a autorização do proprietário, o vínculo com a empresa, o
trial ativo, o lock da preferência e a comparação com a versão enviada. Depois
disso, compara as regras normalizadas e o atraso com a configuração persistida.
A ordem dos dois modelos permitidos não representa uma mudança de configuração.

| Request | Revisão e execuções | Auditoria |
| --- | --- | --- |
| Mesmos valores | Permanecem iguais | Nenhum evento novo |
| Só idioma diferente | Permanecem iguais | Um `preferences.language_changed`, atribuído ao ator atual e à empresa |
| Regra ou atraso diferente, versão menor que 20 | Próxima revisão e cancelamento dos comandos pendentes, como antes | Eventos de revisão, cancelamento e preferências existentes |
| Versão antiga, inclusive em salvamento sem mudança | HTTP 409, sem alteração | Nenhum evento novo |
| Versão 20 com os mesmos valores ou só idioma | Revisão 20 e comandos preservados | Apenas o evento de idioma, quando ele muda |
| Regra ou atraso diferente na versão 20 | HTTP 409, sem alteração parcial do idioma | Nenhum evento novo |

O controller aceita `automation_version=20` como token de comparação. O limite
continua sendo 20 revisões; não existe autorização para criar uma revisão 21.
Regras duplicadas continuam inválidas. O atraso permanece limitado a 0–86400
segundos. As respostas de validação dos campos HTTP mantêm o comportamento do
formulário privado existente.

Na versão 20, o formulário mantém idioma e botão de envio disponíveis. Somente
os controles de regras e atraso ficam desabilitados, com uma explicação do
limite. Campos hidden preservam os valores exatos desses controles no envio
normal do navegador. Qualquer atraso válido já salvo também tem uma opção
selecionada no formulário, mesmo quando não pertence aos atalhos de prazo.

Não se regravam o JSON da automação, seu histórico, os comandos, as sessões
causais ou as chaves de execução quando só o idioma muda. Uma falha no limite
de revisões também não aplica parcialmente o idioma da mesma request.

## Prova no fluxo HTTP existente

`assert_preference_preservation` em `scripts/ops/test-private-crm-http.py` usa o
cliente B já autenticado pelo ensaio anterior, com MFA, CSRF e PostgreSQL reais:

1. Reenvia regras em ordem diferente e comprova igualdade completa da preferência,
   dos eventos, dos leads, das execuções e das tarefas.
2. Cria um acompanhamento futuro e muda o idioma para inglês. Só a coluna de
   idioma e um evento de auditoria podem mudar; a página passa a exibir inglês.
3. Rejeita versão antiga e regras duplicadas sem alterar o snapshot.
4. Altera efetivamente o atraso e comprova uma revisão nova, o cancelamento do
   comando anterior e os eventos correspondentes.
5. Atinge a revisão 20 por alterações HTTP reais, sem reescrever fixtures com
   privilégio de owner, e agenda um novo acompanhamento da proposta.
6. Na revisão 20, salva sem mudanças e muda para espanhol. A execução permanece
   intacta. CAS 19, alteração de regras/atraso e campos inválidos são recusados
   sem efeitos parciais.
7. Processa o comando preservado quando vence e exige exatamente a tarefa com o
   ID da execução. Uma segunda tentativa não cria tarefa nem auditoria duplicada.
8. Compara o snapshot da outra empresa, incluindo preferências, para comprovar
   isolamento.

O cenário usa um lead adicional do cliente B e fica depois das verificações que
exigem esse cliente vazio. Os fluxos preexistentes de browser/resiliência usam
o cliente A, e o ensaio de restart compara o ledger existente.

O browser acrescenta um contexto separado do cliente B, já na revisão 20.
`scripts/ops/test-private-crm-browser.cjs` confere os valores que `FormData`
realmente envia, o prazo selecionado, controles de automação bloqueados e botão
de idioma habilitado. Em seguida, clica no botão sem mudanças e novamente depois
de mudar o idioma, exigindo sucesso, idioma aplicado e revisão/regras/atraso
preservados. Usa o código de recuperação 1 do cliente B: o código 0 já foi
consumido pelo ensaio HTTP anterior. A captura `crm-preferences-revision-20.png`
mostra a seção após o login e o salvamento, sem códigos ou credenciais.

## Escopo

Sem alterações de schema, quotas, preços, papéis, permissões comerciais ou
integrações. Permanecem os dados sintéticos, os gates humanos existentes e a
proibição de merge/deploy/produção neste pacote de desenvolvimento.
