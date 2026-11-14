# CRM — leitura de leads para o painel, proposta v0.1

**14/11/2026 · DOCUMENTAL · DRAFT / NO-MERGE / NO-DEPLOY**

Autorização desta rodada: preparar e revisar o contrato documental de leitura;
não é aprovação dos parâmetros abaixo nem autorização de endpoint, ALLOW, SQL,
login, criação de usuário ou acesso a dados. Base DEV observada:
`7999b1e34a6cbd0ee1c5e2351067ad172e01bbad`.
Baseline web independente: [PR #534](https://github.com/rafhael100/imagine-crm/pull/534),
HEAD `4aaa2779679512bb63d976f51de3ce500ccefa5a`, com dez workflows success.
Esta proposta não altera aquela HEAD nem herda seu aceite técnico.

## 1. Fontes e precedência

| Fonte | O que sustenta | Limite |
|---|---|---|
| [REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), DAT-R01/02/04/05/06/09/14 | Propriedade por organização, visualização, origem, minimização, separação de exportação | Base documental; DAT-D/P ainda abertos; sem schema aprovado |
| [F0-03](../desenvolvimento/F0-03-CONTRATOS-IAM-E-MULTITENANCY.md) | Identidade, membership, política por recurso/ação/campo/escopo e RLS adicional | Não concede direitos executáveis |
| [CORE01](../planejamento/CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md) | Resolução e isolamento de organização | Contexto fornecido pelo cliente não é identidade |
| [CORE02-D05](../planejamento/CRM-CORE-P0-ETAPA2-CORE02-D05-combinacao-perfis-negacoes-delegacao-proposta-v0.1.md) | Grants e negações no mesmo escopo | Implementação física não homologada |
| [#27](https://github.com/rafhael100/imagine-crm/issues/27), [#259](https://github.com/rafhael100/imagine-crm/issues/259) | Revisão SEG/DBA/CORE/LGPD | Issues abertas consultadas; não são aceite |
| [#416](https://github.com/rafhael100/imagine-crm/issues/416) | Identidade e aceite UX por tela | Issue aberta; testes de navegador não substituem aceite |

Os limites numéricos e nomes técnicos desta proposta são **novas sugestões para
revisão**, não regras comerciais já aprovadas. A janela de 18 dias IN-127/147
não define retenção de leads confirmados no CRM.

## 2. Fronteira e inconsistência observada

Em #534, `painel-demo/dashboard.js` usa `deals` para a tela “Leads e contatos”,
com empresa, interesse, valor e etapa de oportunidade. Isso é apresentação
fictícia, não modelo de domínio aprovado. O laboratório #522 usa duas submissões
sintéticas; também não é a fonte de dados da tela.

Esta primeira proposta cobre **listar resumos de leads de uma organização**.
Contatos e oportunidades terão contratos próprios. Não cria detalhe, busca por
PII, Kanban de oportunidades, relatório, exportação, edição ou operação de suporte.
O administrador Imagine não recebe acesso transversal por selecionar esse perfil.

| Coluna atual | Destino proposto | Motivo |
|---|---|---|
| Empresa fictícia | Nome de exibição do lead | Empresa relacionada é entidade distinta; não inferir vínculo |
| Interesse | Fora desta primeira lista | Texto livre pode conter dados excessivos; depende de classificação |
| Valor ilustrativo | Remover desta lista | Valor pertence à oportunidade e requer permissão própria |
| Etapa Novo/Contato/Proposta/Concluído | Fora desta primeira lista | Não converter etapa comercial em estado de lead sem regra aprovada |

O mapa orienta futura alteração de UI; nenhum HTML/JS foi modificado nesta rodada.

## 3. Operação lógica e sequência de decisão

Nome lógico proposto: `lead.list_summary.v1`; **sem URL ou rota criada**.

1. Validar identidade humana e sessão no servidor por mecanismo aprovado.
2. Resolver organização ativa e membership legítima, inclusive revogação.
   Escolha de empresa na UI é somente uma solicitação ao servidor.
3. Avaliar recurso, ação, escopo próprio/equipe/empresa e campos. A ausência de
   decisão positiva revisada é negação. Papéis, objetos em memória ou CNPJ não
   bastam; negações aplicáveis e bloqueios mandatórios prevalecem.
4. Somente após contexto autorizado, validar filtros/cursor e consultar registros
   já limitados ao escopo. Filtrar **antes** de ordenar, paginar e contar.
5. Projetar somente campos aprovados e revalidar autoridade antes da resposta;
   revogação detectada durante a leitura descarta o resultado inteiro.
6. Emitir resposta privada, sem PII em logs, com trilha técnica minimizada.

Nenhuma etapa é implementada aqui. `DenyByDefaultAuthorizer` permanece intacto.
A futura consulta deve usar role sem SUPERUSER/BYPASSRLS, escopo transacional e
limpeza de contexto em pool; RLS não substitui as etapas anteriores. A função
PG18 criada como postgres em #522 não comprova esse caminho operacional.

## 4. Projeção mínima candidata

| Campo lógico | Tipo proposto | Exposição candidata e decisão pendente |
|---|---|---|
| `lead_ref` | Identificador opaco | Referência não sequencial; formato definitivo a revisar; não autoriza acesso |
| `display_name` | Texto simples, máximo proposto 160 caracteres | Exige classificação e direito de leitura; renderizar como texto, nunca HTML |
| `created_at` | Data/hora UTC RFC3339 | Data do registro oficial CRM, sem inventar a partir de submissão/recebimento |
| `source_label` | Rótulo de vocabulário aprovado ou null | Não expor URL, UTM ou formulário bruto; null significa indisponível, não inferido |

A lista candidata só pode operar após aprovação explícita dos campos essenciais
`lead_ref`, `display_name`, `created_at`. Falta de direito a campo essencial nega
a operação, sem tentar consultar/retornar o restante. `source_label` exige direito
próprio; quando não autorizado, é omitido, e quando autorizado mas ausente, é null.
Nenhum indicador revela que há campo oculto em registro de outra organização.

Excluídos: telefone/e-mail completos ou mascarados, CPF/CNPJ, observações, conteúdo
de formulário, consentimentos, anexos/URLs, histórico, score, responsáveis,
valores, IDs de tenant/membership, chaves e bindings técnicos. Campo personalizado
não entra por expansão automática. Mascarar PII também requer autorização.

Resposta lógica positiva candidata: `items` e `next_cursor` (token ou null),
sem `total`, total global ou agregados. Um resultado vazio autorizado contém
`items: []` e `next_cursor: null`. Um erro não contém `items`, contagens ou cursor.
Esta especificação não cria payload de produção nem promete disponibilidade.

## 5. Entrada e paginação propostas

| Parâmetro | Proposta | Recusa |
|---|---|---|
| `limit` | Inteiro, padrão 20, faixa 1..50 | Strings, booleanos, zero, negativos e excedentes |
| `cursor` | Opaco, até 2048 bytes; emitido pelo servidor | Token adulterado, expirado, de outra identidade/organização/escopo |
| `created_from`, `created_before` | UTC RFC3339; intervalo [from,before), opcional | Formato inválido, ordem invertida; intervalo máximo ainda a decidir |
| Demais parâmetros | Não aceitos nesta versão | `tenant_id`, campos livres, ordenação arbitrária, offset, busca de PII |

Ordem candidata fixa: `created_at DESC, lead_ref DESC`, com desempate estável.
Paginação keyset; o tamanho de página é limite técnico, não cota comercial.
O servidor lê no máximo limit+1 registros autorizados para determinar continuação.

Cursor deve proteger integridade e confidencialidade, não conter PII em claro e
vincular versão, identidade, organização, revisão da política/membership, escopo,
filtros normalizados, ordenação, limite, último par de ordenação e expiração.
Chave e TTL dependem de SEG; sem configuração aprovada, operação indisponível.
Não reutilizar HMAC/chaves fictícias de #522. Trocar filtro ou limite exige iniciar
nova listagem; cursor não revalida sessão, não concede permissão e não é exportação.

Sem snapshot transacional entre requisições: novas inclusões podem surgir somente
numa nova listagem; registros removidos/revogados deixam de aparecer. Não prometer
visão histórica consistente. Ordenação usa campos imutáveis; se isso não puder ser
garantido, a paginação depende de nova decisão antes de implementar.

## 6. Falhas e apresentação

| Condição | Estado lógico proposto | Interface candidata |
|---|---|---|
| Sessão ausente/expirada | `UNAUTHENTICATED` | Limpar dados locais da tela e solicitar login quando existir fluxo aprovado |
| Empresa/membership/escopo sem direito, inclusive tenant forjado | `ACCESS_DENIED` genérico | Não revelar empresa, registro ou motivo interno |
| Parâmetro/cursor inválido após autorização básica | `INVALID_REQUEST` | Orientar reiniciar a lista, sem ecoar token ou entrada |
| Política/contexto/serviço indisponível | `UNAVAILABLE` | Falhar fechado; nunca usar fixtures como se fossem dados reais |
| Listagem autorizada vazia | `OK_EMPTY` | Estado vazio separado de erro e de acesso negado |

Status HTTP, mensagens finais, TTL e política de rate limit precisam revisão
específica; não existe adaptador HTTP. Negação não se apresenta como “zero leads”.
Ao trocar organização ou perder sessão, apagar resultados/cursor visíveis antes
de nova consulta; descartar resposta atrasada cujo contexto já mudou. Cache HTTP
proposto `no-store`; sem armazenamento persistente no navegador, URL com PII ou
cache compartilhado. Limites/rate limit protegem coleta em massa sem conceder
exportação, que tem política própria.

Auditoria candidata: ID de correlação gerado no servidor, operação, referências
internas protegidas do ator/organização, decisão/revisão e instante do servidor.
Nunca registrar nomes, contato, token/cursor, payload, filtro bruto ou SQL. Acesso,
retenção e exclusão da trilha precisam decisão CORE/LGPD; falha de auditoria deve
seguir política explícita antes de liberar leitura, nunca ser ignorada por padrão.

## 7. Matriz de revisão antes de código

Os cenários estão em [CRM-LEAD-READ-V0.1-CENARIOS.md](CRM-LEAD-READ-V0.1-CENARIOS.md).
São especificações, **não testes executados**. Aceite deve indicar SHA, IDs de
cenários, evidência e responsável real por competência, sem atribuição fictícia.

| ID | Decisão a obter | Competência exigida | Situação |
|---|---|---|---|
| LR-D01 | Entidades separadas e projeção essencial/opcional | Produto + CORE + Privacidade | Proposta |
| LR-D02 | Matriz recurso/ação/campo/escopo e revogação | CORE/IAM + Segurança | Pendente; sem grant |
| LR-D03 | Role/RLS, transação e pool; isolamento multiempresa | DBA + Segurança, #27 | Pendente |
| LR-D04 | Limites, ordenação imutável, cursor/TTL e rate limit | Engenharia + Segurança | Proposta; números não aprovados |
| LR-D05 | Minimização, trilha e retenção/exclusão | CORE + Privacidade, #259 | Pendente |
| LR-D06 | Estados, mensagens e aceite por tela | Produto/UX, #416 | Proposta |

A aprovação para elaborar esta proposta não encerra LR-D01..06. A revisão interna
do autor não é parecer independente. Depois dos aceites aplicáveis, preparar tarefa
específica de implementação e sua matriz de testes; não promover laboratório a
runtime. Rollback desta entrega: fechar a PR documental e remover sua branch.
