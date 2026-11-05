# F1-05 — contratos de autorização, sem concessões

Issue [#9](https://github.com/rafhael100/imagine-crm/issues/9), 05/11/2026.
Base `dev/fast-track-crm-2026-11-04`, HEAD consultada
`66bed605cc376394386d3cfab6d47301a99ba4fa`. Branch
`codex/f1-05-authorization-contracts`; PR em rascunho para DEV.

## Fontes e precedência

Lidos AGENTS.md e STATUS-ATUAL.md atualizados, Issue #9,
[F0-03](F0-03-CONTRATOS-IAM-E-MULTITENANCY.md),
[conciliação F1-02](F1-02-revisao-tecnica-e-conciliacao.md),
[F1-04](F1-04-iam-hardening-audit.md), CORE02-D01..D06,
CORE01/03/04/05/06/07/08/09 e SEG-01/02.
Main conferida somente em leitura em `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`.
A [decisão posterior P1+P2 escopada](https://github.com/rafhael100/imagine-crm/blob/e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012/docs/planejamento/CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md)
supera somente a ausência histórica de escolha documental. Não é catálogo
executável nem fechamento de CORE02-D05. D04/D05 têm aceite documental
preliminar; D06 não tem homologação integral. Não aplicar P1–P4 em runtime.
Issue #9 e solicitação do responsável autorizam apenas este recorte isolado.

F1-02/F1-03/F1-04 preservadas. O comparativo GitHub entre `228d63a2` e a base
atual foi consultado: somente AGENTS.md e STATUS-ATUAL.md diferem na árvore.
Snapshot local da F1-04 reutilizado com esses documentos atualizados e a
evidência de sincronização. A branch remota nasceu da DEV real; a cópia local
é somente instrumento de edição/diff, não ancestralidade a publicar.

## ADR — intenção estrita e envelope inerte

`AuthorizationIntent::fromArray` aceita somente schema_version inteiro 1,
policy_version, resource, resource_id UUID, action, campos classificados,
scope(kind/id UUID) e channel. Sem coerção de tipos, campos extras, valores
livres, curingas, nomes arbitrários ou schema parcial. Principal, tenant,
membership, MFA, entitlement, roles, grants, mandato, serviço e correlação
não podem ser injetados no payload. Descrição sintaticamente válida ainda
precisa corresponder ao catálogo exato/versionado; desconhecido nega.

`AuthorizationEnvelope` vincula contexto/correlação gerados pelo backend,
referência do sujeito obtida do registry CRM, snapshot de membership ativa
com versão e intenção. `SUBJECT_EVIDENCE=crm_session_registry` identifica o
tipo de verificação exigida, não contém token, assinatura ou certificado.
**Construir, copiar, guardar ou serializar o envelope não comprova nada.**
Somente a execução na fronteira mantém as verificações e locks vigentes.
O autor não aceita um envelope como credencial ou capacidade portátil.

`ExperimentalResourceCatalog` contém exclusivamente `lab.record`, seis ações
de teste (`read/search/update/export/report/execute`) e nove campos fictícios
F-ID/F-BIZ/F-PII/F-RES/F-CONS/F-FIN/F-SEC/F-EVID/F-ART, versão `lab-deny-v1`.
Correspondência de descrição não é permissão. Reclassificar `lab_secret` para
F-BIZ não o torna legível. Não é catálogo de DAT/VEN/ATD/AGD/FIN ou planos.
Campos/versões/ações reais dependem de owners e CORE02; não há metadados
mutáveis por tenant, carga de catálogo externo ou persistência nova.

## ADR — único caminho de efeito e negação permanente

`TenantAccessBoundary::executeContract` usa a mesma implementação interna
de `execute`, preservado para compatibilidade. O caminho mantém
GuardPrincipalSource → SessionAuthority/locks IAM → membership PostgreSQL
reconsultada/lock/RLS → contexto → Authorizer → callback. Intenção malformada
é rejeitada antes desse caminho, sem efeito. Nenhuma rota expõe o contrato.
`DenyByDefaultAuthorizer` continua sendo o único binding em todos os ambientes.
Mesmo um envelope sintético coerente termina em POLICY_NOT_CONFIGURED:
nenhuma política/entitlement/regra proprietária foi homologada.
`AuthorizationDecision` continua sem fábrica ALLOW.

Escopo tenant não implica equipe, unidade ou posse de registro. Equipe,
unidade e record owner sem prova negam. Job, IA e suporte sem autoridade
própria negam; não converter sessão humana em principal técnico, nem afirmar
que rotinas internas obrigatórias dependem sempre da sessão do ator causal.
Mandatos F1-04 de provisionamento não autorizam dados comerciais ou suporte.
Callback não executado significa nenhuma query de negócio, contagem/índice,
escrita, arquivo, mensagem ou enqueue simulado. Não há adaptadores reais,
worker, grants de teste, segundo autorizador, avaliador permissivo ou cache
de decisão/prova. Toda chamada relê a sessão e membership atuais.

## ADR — campo, mascaramento e sinal mínimo

Leitura, busca, atualização, exportação e relatório continuam distintos.
Na ausência de política, não retornar sequer uma projeção parcialmente
mascarada: a operação inteira nega antes de consultar ou materializar dados.
Os testes verificam ausência de callback/valores/artefatos em todos os canais,
inclusive F-PII/F-RES/F-SEC; não alegam um algoritmo produtivo de masking.
Mascaramento seletivo, filtros/counts, índices, arquivos e consentimento
permanecem dependências dos módulos e Segurança/LGPD.

`AuthorizationDecision::signal` oferece somente código de vocabulário fechado
e UUID de correlação (nenhum input, ID de identidade/tenant, campo, segredo,
payload, motivo livre ou PII). Código/correlação inválidos são neutralizados.
O cliente recebe apenas `Protected operation denied.` no caminho de contrato;
não recebe o diagnóstico da política. O sinal é contrato interno para futura
integração CORE06, **não uma nova trilha persistida nem Auditoria Geral**.
Auditoria IAM F1-04 não foi duplicada ou ampliada para dados de negócio.
O adaptador futuro de auditoria deve definir retenção, origem, falhas e
revisão de dados antes de qualquer ALLOW; esta etapa não homologa isso.

## Laboratório, limites e testes

Fixtures somente fictícias. Testes de unidade exercitam contrato fechado,
versões, contexto inconsistente, classes/canais/escopos e negação mesmo com
objetos construídos manualmente. Testes de adapters em tests/ usam registry
real e uma seam explícita de membership para rodar também em SQLite.
Separadamente, PostgreSQL usa o store real com role restrita e RLS,
membership suspensa/desativada, troca A/B e limpeza de contexto.
Probe de concorrência em conexão independente exige SQLSTATE 55P03 para
revogação de sessão e membership enquanto o novo caminho retém locks;
depois do término, revogação commita e a próxima unidade nega antes de ACL.
Não é teste de efeito comercial autorizado ou de transação distribuída.

Regressões F1-02/F1-03/F1-04 preservam RLS, epochs/recovery, sessão expirada,
cache/Redis real, orçamento compartilhado, pane/contenção, compensação e
isolamento de migrations experimentais. CI PostgreSQL 16/Redis efêmeros e
Laravel/SQLite serão conferidas por SHA e registradas abaixo. Skips SQLite
não validam PostgreSQL/Redis. PHP/Composer/Docker ausentes no terminal local;
nenhuma execução local dessas plataformas é declarada. composer.lock intacto.

## Matriz de dependências e aceite antes de ALLOW

| Gate aberto | Evidência/aceite ainda exigido | Competência |
|---|---|---|
| CORE02-D04 | Inventário físico por módulo, classe/finalidade/consentimento, mascaramento e derivados | Owners DAT/VEN/ATD/AGD/AUT/IA/FIN/CON/INT, LGPD e Segurança |
| CORE02-D05 | P1+P2 documental traduzida em contratos revisados, proveniência, delegação, conflito/interseção e revogação | IAM/Segurança, owners e CORE07 |
| CORE02-D06 | Catálogo definitivo por recurso/ação/campo, estados e owner; nenhuma analogia entre módulos | Cada owner de módulo |
| CORE02-D07 | MFA/step-up/alçadas e confirmações específicas; sem segunda aprovação universal | CORE04/05, Segurança e owner |
| CORE02-D08 | Enforcement real por canal, versão/cache/revogação, jobs, RLS por recurso e concorrência | Backend/IAM/DBA/CORE01/03/07/09 |
| CORE02-D09 | Entitlements e quotas vigentes; não inferir plano/preço/capacidade | CORE08, Produto/FIN/CON |
| CORE02-D10 | Matriz de ameaças, critérios negativos, auditoria/retencão e aceite por módulo | Segurança/QA/CORE06, owners e direção |
| CORE01/SEG-01/02 | Contexto confiável, SQL injection/GUC não autenticadora, pools/workers, isolamento, revisão de roles | Segurança/DBA/Arquitetura |
| CORE03/05/09 | Identidade causal/técnica, delegação vigente, serviço/cofre, mandato de suporte por caso | IAM/Segurança/Operação/INT |
| CORE04/06/07/08 | Identidade/MFA/recovery reais, Auditoria Geral, equipe/posse e direitos de plano | Respectivos owners |
| ARQ/INT/G01..G06/FIN/CON/LGPD | Infraestrutura, integrações, contratos, dados e release sob aceite próprio | Respectivos responsáveis/direção |

Decisão normativa documental, modelo candidato, testes de laboratório,
aceite de segurança/módulo e release são marcos distintos. Os **67 IDs CORE**
e demais gates continuam abertos; CI verde não fecha nenhum deles.
Antes de implementar ALLOW: contratos e owners homologados, autoridade de
cada evidência verificável, ameaças/revogação/concorrência e auditoria aceitas,
matriz negativa por canal e autorização específica de implementação.
PR deve permanecer DRAFT para revisão humana. Sem merge, alteração de main,
autenticação pública, cliente real, novo serviço/custo, VPS ou implantação.

## Evidências executadas em 05/11/2026

[PR DRAFT #10](https://github.com/rafhael100/imagine-crm/pull/10), revisão
`cacca5a5bc33ff709f3959b43139052b920391db`; ambos os checks success.
Logs de testes conferidos no GitHub Actions (incluindo resumo e probes):

| Plataforma/comando | Resultado observado | Log |
|---|---|---|
| PHP 8.4 / Laravel 13 / SQLite — `php artisan test --no-ansi` | **229 passed, 32 skipped, 753 assertions** | [Run 42041909555 / job 127294598771](https://github.com/rafhael100/imagine-crm/actions/runs/42041909555/job/127294598771) |
| PostgreSQL 16 / Redis efêmeros — suíte completa | **260 passed, 1 skipped exclusivo SQLite, 917 assertions** | [Run 42041909537 / job 127294598174](https://github.com/rafhael100/imagine-crm/actions/runs/42041909537/job/127294598174) |
| Mesmo job PostgreSQL — `--filter=PostgresMembershipRlsTest` | **18 passed, 49 assertions** | Mesmo log PostgreSQL |
| Local — `git diff --cached --check` restrito aos arquivos F1-05 | **Sem erros de whitespace** | Snapshot local de edição; não é clone autenticado |

154 casos novos somados às regressões anteriores. Os 32 skips SQLite são
dependências reais PostgreSQL/Redis, incluindo os dois probes novos; não
são validação dessas plataformas. O teste `postgres shared redis budgets
and contended lock fail closed` efetivamente passou no job PostgreSQL.
Também passaram os novos casos de revalidação de tenancy/membership/limpeza
RLS e retenção dos locks de sessão e membership contra revogação concorrente.
Não houve falhas de suíte nesta revisão. SQLSTATE 55P03/42501/RLS nos probes
negativos são recusas esperadas, não liberação de privilégio.

Não executados localmente: Laravel/PostgreSQL/Redis, pois PHP/Composer/Docker
não estão disponíveis no terminal. Execução real acima foi exclusivamente
na CI sintética. Os workflows e composer.json/lock não foram modificados;
SHA256 do lock: `08C77FE9D787BE841633DEA38461BBBDC4C3EDFA419FF676D8FADF7581636F5C`.
AGENTS.md e STATUS-ATUAL.md permanecem idênticos à base. Este registro altera
somente documentação; conferir novamente checks e SHA final na PR depois
do commit de evidências. Nenhum aceite humano, fechamento CORE/SEG ou GO
de merge/release é declarado.
