# OP-01 — resumo mínimo navegável no CRM privado

## Continuidade autorizada

Esta entrega conclui o acesso visual à consulta de um lead fictício já aprovada pela Direção. Parte da [PR CRM #649](https://github.com/rafhael100/imagine-crm/pull/649), HEAD `810f6815f4d8dc35ba46dbe7accf78c6b63b7dc1`, cuja [CI OP-01 44215580347](https://github.com/rafhael100/imagine-crm/actions/runs/44215580347) passou: 43 leituras HTTP, 12 admitidas e 31 recusadas, além dos testes de admissão e preservação das fronteiras anteriores.

O recorte continua sendo usuário humano autenticado no CRM, sessão/MFA e participação ativa verificadas pelo CRM, dados fictícios e opt-in exclusivo de homologação. A [autoridade e o contrato de leitura](CRM-OP01-PRIVATE-MINIMAL-READ.md) continuam válidos. Não se introduz delegação a serviço remoto, execução Agents ou alteração de política produtiva.

## Navegação e dados

Com `CRM_PRIVATE_OP01=minimal-lead-v1` e o perfil privado admitido, cada linha da lista de leads apresenta **Ver resumo mínimo**. O link abre:

```text
GET /agentes/leads/{id}/resumo
```

A página apresenta referência e etapa como únicos dados de negócio. Usa o logo e os componentes existentes, cabeçalho de idioma, logout e retorno à seção de leads. O HTML inclui o token CSRF necessário ao logout; não é um envelope de resposta do Agents. Não recebe título, valor, funil, empresa, identidade ou mensagens flash de outros fluxos.

Cada abertura ou mudança de idioma chama `Workspace::minimalLead()` uma vez. Não carrega o painel completo, não consulta modelo integral e não dispara uma segunda consulta à API. Assim, a leitura aceita produz um único evento `lead.minimal_read`, e a falha da auditoria impede renderizar a projeção.

O UUID quebra em telas estreitas. Referência e etapa ficam em duas colunas no desktop e em uma coluna no celular. Os textos novos e as etapas usam português, inglês e espanhol; o retorno preserva seção e idioma.

## Entrada fechada e separação das respostas

| Rota | Entrada na query | Resposta de sucesso |
|---|---|---|
| `/agentes/leads/{id}/minimo` | Vazia | JSON canônico original |
| `/agentes/leads/{id}/resumo` | Vazia ou literalmente `lang=pt`, `lang=en`, `lang=es` | HTML do resumo |

`Accept: text/html` não transforma a API em página. Parâmetros duplicados, codificados, arrays, campos extras e idioma inválido são recusados no resumo. Corpo GET, HEAD, alteração de método e cabeçalhos de identidade/empresa continuam recusados. Ambas as rotas são ausentes por padrão e desaparecem quando o opt-in é removido.

## Evidência exigida na candidata

O ensaio HTTP acrescenta o caminho HTML à matriz existente: acesso owner e member, três idiomas e persistência de idioma, ausência/recurso estrangeiro indistinguíveis, sessão incompleta/encerrada, administrador sem autoridade, membership revogado, conta suspensa, entradas indevidas e falha de auditoria. Para cada leitura, compara o estado dos nove conjuntos de negócio e a auditoria. O consumidor original Agents, fixado na SHA `740abafc813dbde70f5a2ad6e138402ed7945276`, continua recebendo o JSON real; recusa também os sucessos HTML, que não são seu contrato.

O Chromium entra com senha e um código de recuperação descartável do fixture, seleciona a linha pelo título fictício e lê seu UUID antes de clicar no link. Verifica que cada navegação faz uma única requisição de resumo e nenhuma chamada `/minimo`, confere o conteúdo mínimo, os idiomas, o retorno, recurso estrangeiro e sessão encerrada. A geometria é conferida em 320, 390 e 1440 pixels; apenas capturas do resumo sem credenciais são preservadas.

O workflow conserva a comprovação da API e executa a jornada privada histórica com o opt-in desligado, na mesma SHA candidata. Pré-condições continuam exigindo runner GitHub hospedado, evento pull request do próprio repositório, revisão da candidata, projeto Docker exato e destino loopback. Os resultados de execução e as capturas ficam associados à PR e à revisão efetivamente executadas; testes escritos e revisão automatizada não equivalem a execução bem-sucedida ou aceite humano.

## Limites

As consultas não mudam dados de negócio, mas gravam auditoria e podem atualizar a atividade IAM da sessão. Os gates acumulados F251/F252, a proposta documental CRM #57 e a política global `DenyByDefaultAuthorizer` não são alterados. Esta candidata não publica nem implanta o projeto e não torna a integração S2S operacional.
