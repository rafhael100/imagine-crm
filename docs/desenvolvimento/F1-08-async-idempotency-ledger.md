# F1-08 — ledger sintético PostgreSQL e inbox/outbox inertes

05/11/2026, [Issue #15](https://github.com/rafhael100/imagine-crm/issues/15).
Base DEV `bbb6e23e87e2f45a6285f5b6a4cfe0304ac1e5ea`, incluindo F1-02..F1-07.
Branch `codex/f1-08-async-idempotency-ledger`; PR DRAFT exclusivamente para
`dev/fast-track-crm-2026-11-04`. Sem autorização de merge ou implantação.

## Fontes e base conferida

AGENTS.md/STATUS-ATUAL.md atualizados, Issue #15, ADRs F1-02..F1-07,
CORE03 e seu aditamento D01..D03, AUT-P0, INT-R01, INT-09/10/12 e CT-03/04
regem este recorte. Preservada a conciliação F1-02: P1+P2 é diretriz
documental, não grant executável. CORE03/09-D01..D03 são bases documentais;
os demais contratos e aceites continuam abertos. IN-127 é específico de
Sites↔CRM; não copiar 432h/90 dias para este ledger.

Checks da HEAD atual da base conferidos no GitHub: quatro successful.
[Laravel push 42092788914](https://github.com/rafhael100/imagine-crm/actions/runs/42092788914/job/127474934628)
e [PostgreSQL/RLS/Redis push 42092788802](https://github.com/rafhael100/imagine-crm/actions/runs/42092788802/job/127474973683).
Comparação `c4255fd7..bbb6e23e` contém somente AGENTS.md e STATUS-ATUAL.md.
Snapshot F1-07 reutilizado com esses arquivos atualizados. Branch remota
criada da DEV real; snapshot Git local não é ancestralidade para push.

## ADR — contenção no laboratório

**Adaptador e contratos novos ficam exclusivamente em tests/Fixtures.**
Não há binding/provider, rota, comando artisan, worker, listener de negócio,
sink de observabilidade ou API no app. Isso permite provar SQL real sem
inventar um resolvedor de autoridade de serviço ainda não homologado.
O tenant vem da fixture confiável do construtor e só admite os dois UUIDs
fictícios C/D; input divergente é recusado. Essa configuração não autentica
usuário/tenant, e o adaptador não pode ser promovido ao runtime por analogia.

`LedgerDescription` reutiliza AsyncAuthorityRequest e o catálogo F1-05.
Conteúdo é somente enum de laboratório lab.alpha/lab.beta, revisões 1/2;
nunca payload livre, PII, token, URL, script, mensagem de erro ou SQL.
Digest SHA-256 de JSON canônico inclui alegações semânticas e validade,
normaliza UUIDs/ordem de campos e exclui correlação/causalidade de transporte.
Alterar apenas a correlação não cria nova intenção; mudar validade/conteúdo
na mesma identidade gera conflito. Versões 1/2 não são política produtiva.

Cada chamada, inclusive duplicata, reinspeciona pela AsyncAuthorityBridge.
A usa registry/membership reais e Authorizer existente; B–E continuam sem
autoridade técnica comprovada. O registro do ensaio negado pode persistir,
mas **first_seen/duplicate/conflict não são autorização, admissão ou efeito**.
O resultado contém a negação atual independente da existência da linha.
Os locks IAM/membership terminam com a inspeção; não se estendem à escrita
do ensaio negado. Esse registro histórico inerte não é autorização vigente
nem cria uma janela de execução após revogação.
Nenhum resultado do ledger vira grant, cache de autorização ou segundo motor.
DenyByDefaultAuthorizer e todos os arquivos app/ permanecem inalterados.

## ADR — transação local e identidade

Uma conexão dedicada PostgreSQL, sem transação externa, inicia transação
com uma tentativa apenas. Recusa GUC herdada, SUPERUSER/BYPASSRLS, owner ou
membership da role dona, row_security off, RLS/ FORCE RLS desligadas.
Só então atribui app.tenant_id transacional, por query parametrizada.
GUC é contexto configurável, nunca credencial. SQL arbitrário/credencial
comprometida não são resolvidos por RLS; a fixture não expõe SQL livre.
Conexão contaminada é recusada, sem limpeza silenciosa.

Quatro tabelas: ledger imutável, tentativas, inbox e outbox inertes.
Ledger contém UUIDs sintéticos, versão, escopo, digest, instante/validade e
estado fechado registered_for_test. Constraints únicas independentes por
tenant + operação lógica OU chave de idempotência + revisão + escopo.
Aliases divergentes conflitam; não trocam silenciosamente a identidade.
INSERT ON CONFLICT DO NOTHING usa a arbitragem/locks dos índices únicos;
SELECT subsequente identifica o registro imutável. A role não pode atualizar
ou apagar linhas, portanto não exige SELECT FOR UPDATE/grant de mutação.
Não há auto-replay em deadlock/timeout/erro, nem exactly-once distribuído.

Tentativa tem novo UUID backend e resultado fechado; referência composta
tenant+ledger impede FK cruzada. Correlação/causa vêm da observação backend
F1-07, não dos UUIDs recebidos como se fossem prova. Nenhum payload ou segredo
é persistido. Na primeira intenção, as quatro escritas commitam juntas.
Duplicata/conflito só acrescentam tentativa; inbox/outbox não se multiplicam.
Constraints proíbem admitted/forwarded/officially_confirmed; tentativas
permanecem reconciliation_required e ReconciliationTrace reutiliza F1-07.

Inbox/outbox não possuem canal/destinatário/credencial, consumidor/publicador,
fila, Job/Bus/Event/Mail/HTTP, webhook, n8n, cron ou scheduler. São provas de
atomicidade local, não implementações da Central de Eventos/Filas ou AUT.
Não existe efeito de negócio acoplado à transação.

Expiração/futuro/contrato inválido não escrevem. Falha de persistência resulta
inconclusive + diagnóstico sanitizado F1-07 + negação/reconciliação, mesmo
se o commit pode ter ocorrido. Simulação de ACK perdido após commit exige
reconexão/consulta do registro; nunca autoriza reenviar efeito. Novo processo
é aproximado por descarte completo da conexão/adaptador, sem cache em memória;
não é prova de reinício de host, WAL/PITR ou recuperação de desastre.
HTTP 2xx continua sem competência de confirmação do owner.

## Migração e CI

Única migração nova: database/experimental-migrations/async/000004 (nome
completo no diretório). Não é descoberta por migrate padrão, nem pelo path
auth. UP/DOWN exigem identity.experimental=true, ambiente local/testing e
PostgreSQL. Recusa explícita de staging/production/SQLite; SQLite verifica
contrato/guard, não persistência ou RLS. Teste usa schema descartável de CI
para provar ausência no migrate padrão e rollback só do schema assíncrono.

Workflow PostgreSQL existente ganha passo opt-in separado e role crm_async_ci
efêmera com SELECT/INSERT somente nas quatro tabelas. Sem owner/bypass,
UPDATE/DELETE/TRUNCATE/TRIGGER ou leitura IAM/membership. As roles anteriores
não recebem grants novos. PostgreSQL 16 + Redis 7 efêmeros, dados fictícios.
As senhas fixas da CI não são credenciais reais e só existem no runner.
Default config, migrations oficiais, auth migrations e composer.lock intactos.

## Matriz de prova e limites

Testes novos: parser fechado/digest canônico, guards opt-in, first_seen e
duplicata após reconexão, conflito/aliases, tenant/versão/escopo independentes,
duas conexões competindo por INSERT único com commit/rollback, outro tenant
progredindo, exceção após escrita/pre-commit (rollback das quatro tabelas),
ACK perdido depois do commit, validade, GUC contaminada/roles, conexão real
indisponível, replay com sessão e membership revogadas, RLS em todas tabelas,
grants mínimos, constraints de estados/FK, migração opt-in/rollback.
Regressões completas F1-02..F1-07 incluem Redis real, budgets/contenção/pane;
ledger não usa Redis como fonte durável nem cacheia decisões.

Concorrência usa conexões realmente distintas com a mesma role mínima:
uma mantém INSERT sem commit,
outra tenta registrar via adaptador e recebe lock_timeout (55P03); não é
prova de carga distribuída, fairness, workers ou relógios independentes.
Falhas antes/depois do commit são fault injection sintética de exceções;
falha de conexão é PDO real em 127.0.0.1:1 da CI. Sem efeito externo simulado
como sucesso e sem compensação universal.

## Dependências obrigatoriamente abertas

| Gate | Dependência não homologada | Competência |
|---|---|---|
| CORE03-D04..D10, AUT-P01..14 (especialmente P04) | IDs/schema físico, eventos oficiais, transporte/filas/loop guard, prioridades, quotas, retry/backoff/DLQ/cancelamento e idempotência no owner | Eventos/Filas/AUT/owners/Arquitetura |
| CORE09-D04..D10, SEG-01/02 | Identidade e cofre técnicos, autoridade por operação/ferramenta/ambiente/tenant, rotação/revogação, ameaça SQL/GUC e revisão de roles | IAM/DBA/Segurança/Operações |
| CORE01/02/04/05/07/08 | Contexto confiável, grants por recurso/ação/campo/escopo, MFA/titularidade, mandato, posse e entitlement | Respectivos owners/IAM/FIN/CON |
| CORE06/INT-12/LGPD | Auditoria oficial, integridade/proveniência, acesso, retenção/expurgo por categoria e cobertura de monitoramento | Auditoria Geral/Segurança/LGPD |
| INT-R01, INT-09/10, CT-03/04, G01..G06 | Reconciliação oficial e confirmação por domínio, eventos tardios/reordenados, efeito externo incerto e contratos específicos | INT e módulo proprietário |
| ARQ/FIN/CON | Backup/restore/PITR, RPO/RTO, isolamento de infraestrutura, dimensionamento/limites/custos e contratos | Arquitetura/Operações/direção |

Todos os **67 IDs CORE**, CORE01..CORE09, SEG, ARQ, INT, FIN, CON, LGPD e
demais pendências continuam abertos. Nada fecha CORE03-D07 ou AUT-P04.
Não instituir retenção/retry numéricos; n8n continua apenas Imagine interno
sob licença pertinente. Sem serviços/custos, dados reais, merge/main/VPS.
Próximo passo: revisão técnica humana do PR DRAFT e aceites específicos.

## Execução e evidências

PHP/Composer/Docker ausentes do terminal local; testes de plataforma serão
executados na CI e registrados por SHA/URL, com aprovados/skips/asserções.
Não declarar sucesso antes dos logs. SQLite não valida RLS nem Redis.

### Evidências executadas — 05/11/2026

[PR #16 DRAFT](https://github.com/rafhael100/imagine-crm/pull/16), revisão
`0228b7d7f5bd765a4480b1a71cd958fa6c85cad8`. Dois checks success; resumos
e probes conferidos diretamente nos logs Actions:

| Plataforma/comando | Resultado real | Evidência |
|---|---|---|
| Laravel 13 / PHP 8.4 / SQLite — php artisan test --no-ansi | **340 passed, 49 skipped, 1366 assertions; 0 falhas** | [42118579172 / 127564629930](https://github.com/rafhael100/imagine-crm/actions/runs/42118579172/job/127564629930) |
| PostgreSQL 16 / Redis 7 efêmeros — suíte completa | **388 passed, 1 skipped, 1827 assertions; 0 falhas** | [42118578763 / 127564627427](https://github.com/rafhael100/imagine-crm/actions/runs/42118578763/job/127564627427) |
| Mesmo job PostgreSQL — filtro PostgresMembershipRlsTest | **18 passed, 49 assertions** | Mesmo log PostgreSQL |
| Local — git diff --cached --check, oito arquivos F1-08 | **Sem erros de whitespace** | Snapshot de edição, sem push do histórico local |

16 casos novos além das regressões F1-02..F1-07. Os 49 skips SQLite são
dependências PostgreSQL/Redis (13 novos casos persistentes incluídos); foram
executados no job apropriado. Único skip PostgreSQL é exclusivo de SQLite.
RLS, role restrita, concorrência, rollback, ACK perdido, revalidação e
migração opt-in passaram. Redis real permanece coberto pela suíte de
regressão IAM/antiabuso/pane, sem transformá-lo em fonte durável do ledger.

Primeira execução em `de5d60dc0fe8d8601253122acae9ccba4a5180af`:
[Laravel](https://github.com/rafhael100/imagine-crm/actions/runs/42118236605/job/127563469879)
passou (340/49/1366), mas
[PostgreSQL](https://github.com/rafhael100/imagine-crm/actions/runs/42118236573/job/127563470383)
teve **1 failed, 1 skipped, 387 passed, 1797 assertions**. A única falha
foi a asserção Event::assertNothingDispatched após recriar a conexão: Laravel
emitira 20 eventos internos legítimos de conexão/query/transação. Não eram
eventos de negócio, enqueue ou transmissão. Corrigido somente o teste com
fakeExcept de sete tipos EXPLÍCITOS Illuminate Database (connection,
statement/query e lifecycle da transação); qualquer outro evento continua
capturado, e assertNothingDispatched continua exigido. Nenhuma captura
genérica foi relaxada no app. Na mesma revisão, os probes de concorrência
passaram a usar duas conexões com role mínima (sem setup privilegiado no
INSERT concorrente) e foram adicionadas asserções de correlação backend.

Após a correção, ambas as suítes completas passaram no SHA acima. Não houve
execução local Laravel/PostgreSQL/Redis por ausência das ferramentas; CI é
a evidência efetivamente executada, sempre sintética. Composer lock intacto,
SHA256 `08C77FE9D787BE841633DEA38461BBBDC4C3EDFA419FF676D8FADF7581636F5C`;
autorizador intacto, SHA256
`800F5F38E0A60269CCD4A3C5E8B06E66DE5DBC72CD312F87DFE9BCCAC016E0CF`.
Este registro altera somente documentação: conferir novamente o SHA final
e seus checks no PR, sem confundir evidência experimental com aceite humano,
fechamento de CORE/SEG, merge, autorização de negócio ou produção.
