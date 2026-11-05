# F1-07 — diagnóstico e reconciliação assíncrona inertes

05/11/2026, [Issue #13](https://github.com/rafhael100/imagine-crm/issues/13).
Branch `codex/f1-07-async-observability-reconciliation`, derivada da DEV
`33291c12da4371557c7df5562e67ce5804c615bd`, contendo F1-02..F1-06.
Entrega experimental em PR DRAFT somente para `dev/fast-track-crm-2026-11-04`.

## Fontes e precedência

Lidos AGENTS.md/STATUS-ATUAL.md atualizados, Issue #13, ADRs F1-02..F1-06,
Issue #11, [revisão F1-06](https://github.com/rafhael100/imagine-crm/pull/12#pullrequestreview-5788951377),
CORE03/06/09, CORE01/02/04/05/07/08, SEG-01/02, CRM-AUT-P0, INT-R01,
INT-09/10/12, IN-159/160/162 e IN-127. Auditoria Geral mantém fonte oficial;
INT-10 investiga/coordena recuperação técnica e INT-12 correlaciona evidência,
sem confirmar negócio ou duplicar trilha. Bases CORE03/06/09-D01..D03 são
documentais; demais contratos/aceites continuam abertos. P1+P2 escopada não
é motor executável. Main conferida em leitura em
`e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`, sem alterações.

Comparativo GitHub `fd08a921..33291c12`: somente AGENTS.md e STATUS-ATUAL.md
diferem na árvore. Snapshot local da F1-06 publicada reutilizado com esses
documentos atualizados. Branch remota criada da DEV real; Git local só para
edição/diff, não publica ancestralidade. AGENTS/STATUS, decisões normativas,
workflows, composer.json/lock e DenyByDefaultAuthorizer preservados.

Checks da base atual conferidos: [Laravel push 42073888518](https://github.com/rafhael100/imagine-crm/actions/runs/42073888518/job/127408796933)
e [PostgreSQL/Redis push 42073888799](https://github.com/rafhael100/imagine-crm/actions/runs/42073888799/job/127409051929)
com success no SHA acima. Esses checks da base não validam o incremento novo.

## ADR — classificação de proveniência limitada

AsyncDiagnostic é imutável, sem mensagem/stack/Throwable/payload/modelo/IDs.
Não possui parser de códigos enviados pelo cliente. Taxonomia interna fechada:

| Categoria | Origem observável usada | O que NÃO se conclui |
|---|---|---|
| contract_invalid | Parser ou contexto sintético recusado na ponte | Não é incidente comprovado nem valor alegado pelo input |
| authority_unproven | Authorizer/fronteira negou; classes B–E sem prova própria | Não há grant, nem inferência de credencial técnica |
| session_revoked | Exceção tipada após row de registry sob lock corresponder a sujeito e binding e estar explicitamente revoked | ID/token recebido, outra sessão ou erro genérico não comprova revogação |
| session_unproven | AuthenticationException conhecida da fronteira humana | Não distinguir por texto expiração/MFA/ausência de sessão; não afirmar revogação |
| membership_denied | Exceção tipada do store/selector de membership | Sem expor existência/estado/tenant/identidade ao cliente |
| context_contamination | Store detecta GUC não vazia preexistente e lança exceção tipada | Não afirmar recuperação/limpeza da conexão contaminada |
| infrastructure_unavailable | PDO/QueryException com SQLSTATE de conexão indisponível em lista fechada; ou RedisException | Não inferir indisponibilidade por qualquer SQL inválido ou mensagem de erro |
| concurrency_blocked | Driver SQLSTATE 55P03/40P01/40001 | Não repetir automaticamente ou autorizar após timeout |
| unexpected_failure | Throwable não reconhecido; erro SQL sem código conhecido | Não adivinhar causa/saúde pelo texto/stack/previous exception |
| reconciliation_needed | Traço sintético pendente por evidência incompleta/duplicidade/conflito/replay | Não comprova fato do owner ou permissão de retry |
| observer_unavailable | Falha de record no observador; sinal secundário separado | Não apaga diagnóstico primário nem transforma negação em sucesso |
| laboratory_disabled | Profile desligado/ambiente fora de local/testing | Nenhum sink é chamado |

Exceções SessionRevoked, MembershipDenied e ContaminatedTenantConnection
mantêm as superclasses e mensagens genéricas anteriores. Demais verificações,
locks, ordem credencial→sessão→membership, revalidação e negação permanecem.
A nova distinção não relaxa nenhuma pré-condição. SQLSTATE é apenas comparado
com constantes; não é copiado para o sinal. 42501/23505 sem tipo contextual
próprio permanecem unexpected_failure, sem atribuir uma causa por heurística.

AsyncAuthorityBridge ainda captura Throwable para falhar fechado, mas agora
classifica tipos conhecidos e registra somente diagnóstico sanitizado no
resultado. Nenhuma exceção ou dado do input é retido nesse diagnóstico.
AuthorizationDecision externa permanece false/CHANNEL_AUTHORITY_MISSING,
independentemente da categoria interna. Não expor diagnósticos em API futura.

## ADR — observação não é auditoria oficial ou prova de identidade

AsyncObservation contém vocabulário fechado: tipo/estágio/classificação,
resultado deny, fonte/cobertura parcial, instante backend, novo ID e nova
correlação backend. Causalidade local aponta para essa inspeção backend,
**nunca para correlation_id/causation_id recebidos como se fossem comprovados**.
execution_class_claim A–E é marcada unverified_claim, não classe autenticada.
Input inválido não fornece sequer essa classe ao sinal.

Sem tenant/user/operation/mandate/secret UUID do input, token, PII, prompt,
SQL, cabeçalhos, mensagem de exceção ou stack. Construir o DTO manualmente
não é atestado criptográfico. A cobertura é laboratory_partial, não saúde
do serviço, prova de entrega, armazenamento durável ou garantia de auditoria.

AsyncObserver é apenas interface, sem binding/default sink em runtime.
Única implementação desta etapa: SyntheticAsyncObserver em tests/Fixtures,
somente arrays em memória com dados sintéticos. Sem log global, Event::dispatch,
HTTP, gravação, métrica de clientes ou retenção. A ponte observa depois de
terminar/rollback da fronteira; falha do observador é capturada uma vez,
preserva o objeto/sinal primário e marca observer_unavailable secundário.
Não faz retry, não autoriza e não reentra em transação. Profile false e
production nem invocam o observador. Nenhuma persistência CORE06 foi criada.

## ADR — reconciliação sintética e contexto

ReconciliationTrace cria um traço separado com novo ID backend e causa no ID
backend da observação. Inconclusive → reconciliation_required é terminal.
Não reabre o resultado denied original, não envia comando e não confirma
negócio. AsyncLifecycle só ganhou helper para a transição já existente;
admitted/forwarded/officially_confirmed/retry continuam inalcançáveis.

SyntheticAsyncInbox em tests/ deriva incomplete/duplicate/conflict do próprio
comparativo tenant+key/fingerprint, não de um código fornecido no payload.
ReplayObserved é um rótulo de cenário sintético, não replay autorizado. Não
há inbox/outbox real, dedupe durável, exactly-once ou política de compensação.
HTTP 2xx continua insuficiente, e owner futuro mantém confirmação oficial.

Ponte não guarda contexto/cache entre inspeções. Teste PostgreSQL real
verifica membership suspensa/revogada, GUC contaminada, lock_timeout concorrente,
rollback IAM/negócio e GUC transacional vazia, seguido de reuso A–E. GUC herdada
é recusada e permanece contaminada até a fixture que a controla limpá-la;
quarentena/reset produtivo do pool continua pendente. Pane DB/Redis controlada
em 127.0.0.1 da CI nunca consulta ou envia observações a serviço de cliente.
Nenhuma fila ou operação de negócio é executada nos probes.

## Matriz de responsabilidades e gates abertos

| Dependência | Contrato/aceite que esta etapa NÃO fecha | Competência |
|---|---|---|
| CORE03-D04..D10 | Origem/formatos físicos, delegação A–E, worker/pool, filas duráveis, idempotência/concorrência, recuperação por domínio, limites e homologação | Backend/Filas/Arquitetura/Segurança/owners |
| CORE06-D04..D10 | Categoria final, integridade/proveniência, sink oficial, direitos de consulta/exportação, retenção/expurgo, falha da auditoria mandatória e evidência | Auditoria Geral/Segurança/Operações/LGPD |
| CORE09-D04..D10 / SEG-01/02 | Identidade/cofre de serviço, autoridade por ferramenta/ambiente/tenant/risco, rotação/revogação e contingência | IAM/Segurança/DBA/Operações |
| CORE01/02/04/05/07/08 | Contexto confiável/RLS, política real, MFA/titularidade, suporte/mandato, equipes/entitlements e revogação | Respectivos owners/Segurança |
| INT-09/10/12 / G01..G06 | Coordenação, investigação/reconciliação técnica, evidências da fonte oficial; nenhum INT confirma fato comercial por transporte | INT e owner de cada negócio |
| ARQ/FIN/CON/LGPD | Infraestrutura/continuidade, custos/contratos, finalidade/base legal/retencão e direitos próprios | Respectivos responsáveis/direção |

Os 67 IDs CORE e todos os gates permanecem abertos. Prazos existentes de
IN-162/CORE06 e IN-127 são preservados por categoria, sem instituir prazo
novo ou copiar os 432h/90 dias de Sites↔CRM para qualquer observação/job.
Sem vendor, serviço, parecer jurídico, grant, segundo autorizador, identidade
técnica emitida, dados reais, cobrança, main, n8n, VPS ou implantação.
Próximo passo: revisão humana do PR DRAFT e aceites próprios antes de merge
ou implementação de sink/worker reais. CI sintética não é homologação.

## Validação

Executar as suítes completas Laravel/SQLite e PostgreSQL 16/Redis da PR,
incluindo regressões F1-02..F1-06. Registrar SHA/links/resultados após execução.
PHP/Composer/Docker ausentes localmente; não declarar execução local dessas
plataformas. composer.lock sem alteração: SHA256
`08C77FE9D787BE841633DEA38461BBBDC4C3EDFA419FF676D8FADF7581636F5C`.
