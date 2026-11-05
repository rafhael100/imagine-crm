# F1-09 — integridade e recuperação simulada do ledger

05/11/2026, [Issue #17](https://github.com/rafhael100/imagine-crm/issues/17).
Branch `codex/f1-09-async-ledger-integrity-recovery`, base DEV
`f2955fa367f3417419a87a7bc6c06c1a1466a748`, contendo F1-02..F1-08.
PR DRAFT exclusivamente para `dev/fast-track-crm-2026-11-04`.
Nenhum aceite CORE/SEG, merge, main, VPS ou produção autorizado.

## Fontes e estado conferido

AGENTS.md e STATUS-ATUAL.md atualizados, Issue #17, ADRs F1-02..F1-08 e
conciliações, CORE03/06/09 com aditamentos D01..D03, CORE01/02/04/05/07/08,
AUT-P0, INT-R01/09/10/12, CT-03/04, SEG-01/02, LGPD e IN-127 regem o recorte.
As decisões P1+P2, categorias de auditoria e autoridade A–E são preservadas;
não são grants executáveis ou autorização produtiva. IN-127 é Sites↔CRM;
432h/90 dias não são prazos do ledger.

HEAD da DEV conferida com quatro checks success antes de criar a branch:
[Laravel push 42122594881](https://github.com/rafhael100/imagine-crm/actions/runs/42122594881/job/127578514632)
e [PostgreSQL/Redis push 42122594973](https://github.com/rafhael100/imagine-crm/actions/runs/42122594973/job/127578595499).
Comparação GitHub `49bd5b66..f2955fa3`: somente AGENTS.md e STATUS-ATUAL.md
diferem na árvore. Snapshot local F1-08 reutilizado com esses dois arquivos
atualizados. Branch remota criada da DEV real; não publicar ancestralidade
do snapshot Git local. Nenhuma alteração em AGENTS/STATUS ou normas técnicas.

## ADR — somente consulta, sem recuperação operacional

Toda programação F1-09 fica em tests/Fixtures, testes e configuração de CI.
Nenhum serviço app/, binding, rota, comando, scheduler, worker, cliente,
fila, Publisher/Consumer, n8n, HTTP, Mail ou efeito de negócio foi criado.
As fixtures F1-08 e suas migrações não são alteradas. Não há schema novo.
O único autorizador runtime continua DenyByDefaultAuthorizer, byte a byte.

`LedgerIntegrityProbe` compara as quatro tabelas F1-08 contra expectativas
independentes, construídas pela fixture a partir de LedgerDescription e
recibos sintéticos conhecidos. O manifesto não é derivado da leitura que
se pretende validar. Sem manifesto confiável não há prova de completude:
um snapshot e seu digest não provam origem, autoridade ou veracidade.
O manifesto também é objeto de laboratório, não atestado criptográfico.

Verifica estrutura fechada, tenant, identidade lógica/operação/chave/revisão/
escopo, unicidade aparente, política/versão, digest, estado inerte, validade,
par inbox/outbox, FK lógica, tentativas e correlação/causalidade backend
contra os recibos conhecidos. Ausências, associações duplas, órfãos, registros
inesperados, alteração de digest/versão/tempo e referências divergentes
geram enums fechados. As constraints reais continuam impedindo parte das
inconsistências; amostras em memória permitem testar corrupção que o banco
íntegro corretamente não admite. Nunca remover constraints para simular isso
no schema de origem.

O verificador não modifica arrays nem banco, não apaga, reinsere, reenvia,
reabre tentativas ou transforma leitura em grant. Mesmo uma amostra sem
divergências resulta em **inconclusive**, **unknown/needs_owner**, deny e
ReconciliationTrace pendente. Divergências resultam reconciliation_required.
Nenhum caminho declara admitted/forwarded/officially_confirmed ou ALLOW.

`LedgerIntegrityReport` reutiliza AsyncDiagnostic/AsyncDenial/observação e
ReconciliationTrace F1-07. Signal tem somente enums, cobertura parcial,
nova correlação backend e resultado deny, sem linhas, IDs de tenant/operação/
tentativa recebidos, hashes, SQL, exceção, payload, PII ou segredo. Não há
sink persistente nem auditoria oficial nova. Limite de 200 linhas por tabela
e timeout de 500ms são contenção de fixture, não quotas/SLA produtivos.

## ADR — leitura PostgreSQL isolada e consistente

`PostgresLedgerReader` só aceita tenant C/D da fixture confiável, PostgreSQL,
profile experimental explícito, local/testing e conexão sem transação externa.
Antes da primeira consulta define **REPEATABLE READ, READ ONLY**. Confere
role sem SUPERUSER/BYPASSRLS/ownership, SELECT presente e nenhum privilégio
INSERT/UPDATE/DELETE/TRUNCATE/REFERENCES/TRIGGER nas quatro tabelas; todas
devem ter ENABLE + FORCE RLS. Schema ausente e role imprópria são recusados.

Role `crm_async_audit_ci` efêmera tem USAGE no schema/CONNECT e somente SELECT
nas quatro tabelas, sem IAM/membership/credenciais. Role escritora F1-08
permanece separada e é recusada pelo leitor por possuir INSERT. Não ampliar
grants anteriores. Nenhuma role/credencial produtiva é emitida. Senhas fixas
da CI existem apenas no PostgreSQL efêmero do runner.

GUC app.tenant_id herdada é recusada antes da leitura; não é normalizada
silenciosamente. Contexto só é atribuído transacionalmente com parâmetro
bound. RLS é defesa adicional, não autenticação: SQL arbitrário/credencial
comprometida ainda podem manipular GUC; nenhuma fixture é API pública.
Conexão suja requer infraestrutura de quarentena/reset ainda pendente.
Consultas projetam colunas fixas, tenant explícito e amostra limitada.
Snapshot único das quatro tabelas evita visão híbrida durante commit paralelo;
nova consulta vê dados commitados posteriormente. Não bloqueia escritores.

`ConsultativeLedgerInspector` reexecuta AsyncAuthorityBridge a cada inspeção.
Contrato inválido ou tenant divergente é recusado. Para A, sessão/membership
reais são relidas pela fronteira existente; para B–E continuam ausentes as
provas técnicas próprias, sem fabricar humano. Integridade e autorização
permanecem dimensões distintas: snapshot consistente não prevalece sobre
revogação, e os locks IAM terminam na inspeção negada, sem efeito posterior.
Somente ledger é read-only; a revalidação mantém o comportamento IAM/auditoria
pré-existente, sem redefinir esse subsistema. Falhas preservam negação e
diagnóstico sanitizado, sem cache de sucesso, retry ou auto-reparo.

## ADR — ordem causal sem regra global inventada

`SyntheticCausalProbe` recebe apenas amostras fictícias fechadas de IDs,
revisões 1/2, predecessor alegado e conteúdo lab.alpha/lab.beta. Distingue
predecessor ainda não observado na ordem de chegada, versão numericamente
mais antiga, nova tentativa da mesma intenção, tentativa repetida e conflito.
IDs de operação e tenant nunca são fundidos. Mesmo sequência aparentemente
ordenada permanece unknown/needs_owner: não há contrato homologado para
ordenar globalmente módulos, migrar instâncias, cancelar versão antiga ou
afirmar causalidade autenticada. Um predecessor ausente não prova perda:
pode não estar na amostra. Nenhum relógio global ou replay é implementado.

## ADR — restauração descartável e falhas

Somente o TESTE cria schema `lab_recovery_<hex>` no PostgreSQL da CI, instala
explicitamente a migração experimental async existente, copia arrays mínimos
da leitura sintética e os insere em uma transação de setup. Leitor só recebe
SELECT nessa réplica descartável. Nenhuma cópia para origem, dump SQL, arquivo
de cliente, pg_dump, host real, WAL/PITR, backup offsite, RPO/RTO ou recuperação
de desastre foi implementado/testado. Não declarar restauração de host.

Snapshot completo preserva linhas/digests/invariantes e RLS; parcial sem
outbox ou digest alterado gera lacuna sem correção. Restore conflitante é
recusado pela constraint única com rollback integral, e consulta posterior
detecta ausência. Fonte e outro tenant permanecem inalterados. Setup limpa
somente seus schemas descartáveis ao terminar, sem mecanismo de expurgo real.

Duas conexões restritas exercitam commit entre leituras, dados não commitados
e rollback. ACK perdido após commit é injetado na fixture escritora F1-08;
consulta não fabrica recibo first_seen nem cria nova tentativa. Falha DB real
é conexão recusada em 127.0.0.1:1 na CI. Testes recusam contexto residual,
transação externa, schema ausente, role privilegiada/escritora, RLS insegura,
profile desligado/staging/production e sessão/membership revogadas. Expiração,
órfãos, versões, associações e sinais adulterados também são negativos.

## Matriz de lacunas, competências e gates ABERTOS

| Observação experimental | Decisão/validação futura ausente | Owner/gate |
|---|---|---|
| Par ausente, órfão, duplicidade e restore parcial | Semântica por domínio, checkpoints e política de restauração/replay/retry/DLQ/cancelamento | CORE03-D04..D10, AUT-P04, Eventos/Filas/AUT/DBA |
| Digest, versão, estado ou identidade divergente | Evento oficial, schema físico, compatibilidade, origem verificável e confirmação do efeito | Owner do módulo, CORE03/INT-09/CT-03/04 |
| Correlação/causalidade divergente ou recibo perdido | Integridade/proveniência oficial e reconciliação por evidência competente | CORE06, INT-10/12, Auditoria Geral e owner |
| Ordem de chegada/versão antiga/predecessor ausente | Ordenação, coexistência/migração de versões e retomada contextual | CORE03/AUT-P01..14/owners, nunca regra global presumida |
| GUC/role/RLS/falha DB/revogação | Identidade técnica, delegação, pool seguro, ameaça SQL, revogação e revisão de grants | CORE01/02/04/05/07/08/09, SEG-01/02, Segurança/DBA |
| Retenção, restauração e custos | Classificação LGPD/holds/expurgo sem ressuscitar dados descartados, backup/PITR/RPO/RTO/capacidade/orçamento | CORE06/ARQ/FIN/CON/LGPD/Operações/direção |

Todos os **67 IDs CORE**, CORE01..09, CORE03/CORE09-D04..D10,
CORE02-D04..D10, AUT-P01..14, SEG-01/02, ARQ/INT/FIN/CON/LGPD e G01..G06
permanecem abertos. CORE03-D07 e AUT-P04 não são encerrados por esta prova.
Verificação sintética compara amostras; consulta consultiva aponta lacunas;
reconciliação oficial requer fontes/owners; execução exige autoridade atual
e contrato homologado. CI verde não substitui nenhum dos dois últimos.
Próximo passo: revisão humana do PR DRAFT, sem merge automático ou avanço
de etapa sem autorização própria.

## Validação

Executar suítes completas Laravel/SQLite e PostgreSQL 16/RLS/Redis no PR e
registrar SHA/URLs/pass/skip/asserts após execução. Regressões F1-02..F1-08
preservadas. PHP/Composer/Docker ausentes no terminal; não declarar execução
local dessas plataformas. SQLite prova os contratos puros; somente PostgreSQL
com role restrita prova RLS, grants, snapshot e concorrência reais. Redis
efêmero continua nas regressões IAM/antiabuso/pane; não vira fonte de ledger.
