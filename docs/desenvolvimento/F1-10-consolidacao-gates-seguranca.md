# F1-10 — consolidação, gates de segurança e proposta de transição

05/11/2026. [Issue #19](https://github.com/rafhael100/imagine-crm/issues/19).
Base exclusiva DEV `788f7e792793718ff5f96252f11216c2cd291d05`.
Branch `codex/f1-10-fast-track-security-baseline`, PR DRAFT somente para
`dev/fast-track-crm-2026-11-04`. **Nenhum gate é fechado neste documento.**
Não é homologação, aceite de risco, GO funcional ou autorização de produção.

## 1. Proveniência e precedência

Lidos AGENTS.md/STATUS-ATUAL.md atuais, Issue #19, ADRs F1-01..F1-09 e suas
conciliações, CORE01..10, matriz Etapa 2/R01, AUT-P0, IN-168, INT-R01/09/10/12,
CT-03/04, G01..G06 e dependências FIN/CON/LGPD. As fontes são vinculadas abaixo.
ADRs descrevem provas históricas; não substituir normas por comportamento de
fixture. As restrições documentais históricas de programação são conciliadas
com DEC-FASTTRACK e esta autorização específica para testes de contenção.
Isso não autoriza funcionalidade comercial, merge, main ou VPS.

HEAD inicial conferida com quatro checks success. Push:
[Laravel 42130690343](https://github.com/rafhael100/imagine-crm/actions/runs/42130690343/job/127606223020)
e [PostgreSQL/Redis 42130690359](https://github.com/rafhael100/imagine-crm/actions/runs/42130690359/job/127606344376).
Comparação GitHub `6c8dd1db..788f7e79`: somente AGENTS.md e STATUS-ATUAL.md
diferem da F1-09 na árvore. Snapshot local reutilizado com esses documentos
atualizados; branch remota criada da DEV real, sem push do histórico local.
Esses checks da base não validam as alterações novas.

Referências canônicas de estados/IDs:

- [Inventário Etapa 2 — contagem 67 e aditamentos][E2]; [R01 — contratos compartilhados][R01].
- [Painel Etapa 1 — D01..D03 dos dez blocos][E1]; cada CORE abaixo remete à sua fonte exata.
- [Conciliação F1-02](F1-02-revisao-tecnica-e-conciliacao.md), incluindo DEC-FASTTRACK, pacote F1-P0, CT-F1-01 candidatos e [decisão posterior P1+P2 escopada][P12]. Esta última supera a ausência de escolha em trechos antigos, **não** cria catálogo executável nem fecha CORE02-D05.
- [AUT-P0][AUT], [aceite limitado AUT-P0][AUTDEC], [IN-168][IN168], [INT GATE][INTG], [CT-03/04][CT], [DEV-GATE-00][DEVG].

## 2. Como interpretar os estados

| Dimensão | Significado neste registro | O que não significa |
|---|---|---|
| Implementado em laboratório (L) | Código executado com dados fictícios em teste/CI, com limites indicados | Recurso de cliente, identidade real, proteção produtiva homologada |
| Aceito documentalmente (D) | Decisão expressa de princípio/base; preliminares são identificadas como tais | Schema físico, permissão concedida, pacote instalado ou GO |
| Pendente de homologação (H) | Contrato, implementação, revisão de especialista e evidências ainda necessários | Fechamento por CI verde ou merge em DEV |
| Não implementado (N) | Não existe capacidade operacional no recorte | Ausência de decisão documental ou permissão para improvisar |

L e D podem coexistir com H/N. **Todos os 67 IDs continuam ABERTOS.**
As competências abaixo são owners funcionais propostos; nenhuma pessoa foi
designada por automação. CORE são bloqueios do núcleo P0. Prioridades dos
demais IDs preservam a fonte; prioridades de sequência não são reclassificação
normativa. P1/P2 que afetem a segurança do recorte precisam decisão própria.

## 3. Inventário técnico das camadas F1-02..F1-09

| Camada / incremento | PR e merge DEV | Arquivos críticos e responsabilidade | Evidência L e ambiente | D / H / N |
|---|---|---|---|---|
| Identidade global e membership — F1-02 | [#4](https://github.com/rafhael100/imagine-crm/pull/4), `3ec4cfa0621140056eab274826a639aba125e0a6` | GuardPrincipalSource, PostgresActiveMembershipStore, TenantAccessBoundary em app/Core; separa sujeito, vínculo, tenant e decisão | Guard dedicado, reconsulta/locks, recusa de vínculo/contexto falso; Laravel e PG | CORE01 D01..03 D; lifecycle completo, titularidade, schema geral H/N |
| MFA e sessões — F1-03 | [#6](https://github.com/rafhael100/imagine-crm/pull/6), `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10` | ExperimentalAuthenticator, DatabaseSessionAuthority, Totp; registry server-side/epoch/binding; auth migration opt-in | TOTP/recovery/rotação/revogação e locks; SQLite + PG | Núcleo interno L; enrollment/login público, prova real de titularidade e política por papel H/N |
| Provisionamento/auditoria/abuso — F1-04 | [#8](https://github.com/rafhael100/imagine-crm/pull/8), `407a2ead654864cd457436080999736a1186e219` | LaboratoryProvisioner, IamOperationRunner, IamAudit, IamAbuseGuard; mandato sintético e compensação restrita | Redis real efêmero, falha fechada/contenção; IAM SQL separado | Mandato de laboratório não é suporte comercial; Auditoria Geral/cofre/DoS distribuído H/N |
| RLS — F1-01/02 e regressões | [ADR F1-01](F1-01-modelo-IAM-RLS-evidencias-CI-2026-11-04.md) | migrations oficiais IAM/organization/membership; adaptador PG de contexto | FORCE RLS membership, GUC local limpa, role não owner/bypass; somente PG prova RLS | GUC não autentica; SQL injection/atestado/pool/roles por recurso H |
| Recurso/ação/campo/escopo — F1-05 | [#10](https://github.com/rafhael100/imagine-crm/pull/10), `9cc5ae4214e07903b2b69d9d40541c61f395d4bf` | AuthorizationIntent/Envelope, ExperimentalResourceCatalog, DenyByDefaultAuthorizer | lab.record, classes fechadas, todos canais/ações negados | Vocabulário D/L; matriz real/grants/mascaramento/entitlement N/H; **único binding deny** |
| Autoridade assíncrona A–E — F1-06 | [#12](https://github.com/rafhael100/imagine-crm/pull/12), `691f8c0e508fabae5d3560cca044bf280d2fcfb5` | AsyncAuthorityRequest/Bridge/Lifecycle; inspeção sem callback de efeito | A revalida humano; B–E não fabricam humano/autoridade técnica; Laravel/PG | Classes D; credencial de worker, delegação, evento oficial e execução N/H |
| Observabilidade — F1-07 | [#14](https://github.com/rafhael100/imagine-crm/pull/14), `71cecea7d53fd5f2608d4b0b96d685c696a144ed` | AsyncDiagnostic/Observation, ReconciliationTrace; observer só em tests | Sinal sanitizado, diagnóstico tipado e pane DB/Redis; sem sink | Categoria/trace L, Auditoria Geral, retenção, alertas e cobertura oficial N/H |
| Ledger/idempotência — F1-08 | [#16](https://github.com/rafhael100/imagine-crm/pull/16), `2923cc57afe240f8aa6024d7028a75bb8f8b54ef` | tests/Fixtures/PostgresSyntheticLedger e LedgerDescription; migração async opt-in | Quatro tabelas inertes, constraints/locks/rollback e ACK perdido; PG | Persistência local L; fila/outbox publicável, efeitos idempotentes no owner e exactly-once distribuído N |
| Integridade/reconciliação/recuperação — F1-09 | [#18](https://github.com/rafhael100/imagine-crm/pull/18), `af4cd127655572f9ce7327b4f52dca36a5e0a864` | tests/Fixtures/LedgerIntegrityProbe, PostgresLedgerReader, ConsultativeLedgerInspector, SyntheticCausalProbe | SELECT-only, REPEATABLE READ/READ ONLY, restore de arrays em schema CI, consulta pending; PG e contratos SQLite | Manifesto não é atestado; integridade não autoriza; ordem global/restore real/WAL/PITR/RPO/RTO/reconciliação oficial N/H |

Os ADRs [F1-02](F1-02-fronteira-autenticacao-membership-tenant.md),
[F1-03](F1-03-auth-mfa-sessions.md), [F1-04](F1-04-iam-hardening-audit.md),
[F1-05](F1-05-authorization-contracts.md), [F1-06](F1-06-async-authority-contracts.md),
[F1-07](F1-07-async-observability-reconciliation.md),
[F1-08](F1-08-async-idempotency-ledger.md) e
[F1-09](F1-09-async-ledger-integrity-recovery.md) detalham limites e logs históricos.
Números de TTL/TOTP/lease/limites dos protótipos não são parâmetros homologados.

## 4. Baseline executável F1-10 e limites do verificador

Somente três arquivos novos de código em tests/: SecurityBaselineChecks,
FastTrackSecurityBaselineTest e PostgresSecurityBaselineTest. Sem novo
subsistema, autorizador, fixture promovida, schema, dependência ou workflow.

| Invariante | Verificação efetiva | Contraprova / limite |
|---|---|---|
| Profile fechado e único autorizador | Config real false padrão; container resolvido em local/testing/staging/production, profile false/true; decisão falsa para 6 ações × 4 canais e legado | Binding alternativo que retorna deny também é recusado; descritores não são identidade |
| Experimentos opt-in | Migrator real: diretório normal + paths registrados excluem as três migrações auth/async; UP/DOWN recusam staging/production/profile false | Testes anteriores executam migrate padrão em schema CI e rollback; SQLite não prova PG |
| Fixtures não ligadas a runtime | Inspeção de bindings/providers/ações reais de rotas e comandos por reflexão; allowlist da superfície existente; tokenização complementar de PHP runtime | Rota/binding sintéticos e imports/literais de classe são detectados; comentários ignorados. Não é análise completa de fluxo dinâmico/strings construídas; revisão humana ainda necessária |
| Sem efeitos | Ponte A–E, falha fechada do ledger com conexão inadequada e consulta pendente; Bus/Queue/Event/Mail/HTTP fakes exigem ausência | Ensaios não demonstram provedor real; suites F1-08/09 mantêm testes PG de escrita/consulta sem dispatch |
| Roles/ACL e RLS | Catálogo PG real de 13 tabelas, cinco roles sem super/bypass/create/owner, privilégios efetivos por tabela E coluna exatamente na matriz existente; FORCE RLS em membership e quatro tabelas lab | Checker rejeita grant de negócio, UPDATE de coluna, TRIGGER e perda de contrato; regressões anteriores mantêm probes C/D, revogação, GUC e concorrência |
| PR Codex só DEV | JSON real GITHUB_EVENT_PATH em evento pull_request, head codex/, base/dev e repositórios exatos; ambos workflows já executam PHPUnit | Contraprovas main/fork/metadata inválida; sem PAT/API nova. Eventos push/local não têm esse contexto e fazem skip específico; PR #2 DEV→main não é alterado |
| Inventário sem fechamento implícito | Documento contém exatamente os 67 IDs canônicos em ordem e cada linha permanece ABERTO | Teste de rastreabilidade documental não valida conteúdo normativo, owners ou aceite |

A regra de alvo verifica a metadata quando a CI roda, não altera proteção de
branch nem impede mudanças maliciosas do próprio workflow/verificador. Não é
credencial, garantia universal de detecção ou aprovação de merge. Apenas
testes observados de contenção; main/VPS não são acessadas pelo verificador.

## 5. Matriz individual dos 67 IDs CORE — todos P0/ABERTOS

Fonte de contagem: [E2, §1][E2]. CORE01 tem somente D04..D07; CORE02..10
têm D04..D10: **4 + 9×7 = 67**. Não inventar CORE01-D08..D10. As evidências
L são parciais; “nenhuma” significa ausência de implementação da capacidade,
não de documentação. Evidência H requerida inclui contrato aprovado,
implementação específica, testes positivos/negativos e aceite do owner.

### CORE01 — fonte [C01, §6][C01] e [especificação D04..D07][C01X]

D01..D03 aceitas como base; D04 ABC e D05/D06/D07 ABCD aceitas somente
preliminarmente conforme aditamentos [E2, §8..13][E2]. IDs integrais abertos.

| ID | Laboratório | Documental | Falta / evidência para homologação | Owner proposto / dependências | Estado |
|---|---|---|---|---|---|
| CORE01-D04 | F1-02 vínculo reconsultado | Preliminar ABC | Lifecycle/convite/perfil/assento versionado e concorrência, sem concessão pelo convite | IAM; CORE02/04/07/08 | ABERTO |
| CORE01-D05 | F1-02 GUC/lock; F1-03 sessão | Preliminar ABCD | Autenticação do contexto no banco, threat model SQL, pools/workers e revalidação por fronteira | DBA/Segurança; SEG/CORE03/09 | ABERTO |
| CORE01-D06 | Nenhum ciclo comercial | Preliminar ABCD | Fonte oficial e prova Site↔tenant/assinatura, estados, titularidade e idempotência sem duplicação | FIN/CON/Portal/CRM; CORE08/INT | ABERTO |
| CORE01-D07 | Schema IAM mínimo + RLS | Preliminar ABCD | Schema geral, constraints/FKs tenant-bound, índices e políticas por recurso/role, testes de migração/restore | DBA/IAM; CORE02/06/10/SEG | ABERTO |

### CORE02 — fonte [C02, §10/aditamentos][C02]

D01..D03 base aceita; D04 ABCD preliminar. D05 preserva [P1+P2 escopada][P12]
posterior à minuta; isso não homologa todos os contratos D05. Sem grant real.

| ID | Laboratório | Documental | Falta / evidência para homologação | Owner proposto / dependências | Estado |
|---|---|---|---|---|---|
| CORE02-D04 | F1-05 nove classes fictícias | Diretrizes preliminares | Inventário real por módulo/campo, mascaramento, busca/IA/export/derivados e customizados | Owners DAT/VEN/ATD/AGD + LGPD | ABERTO |
| CORE02-D05 | F1-05 deny-only | P1+P2 documental; integral pendente | Traduzir precedência escopada, herança/delegação/negações e atribuição em contratos testáveis sem autoelevação | IAM/Segurança; CORE07/SEG | ABERTO |
| CORE02-D06 | Catálogo lab.record | Vocabulário base; catálogo real pendente | Ação/recurso/estado/owner reais, direitos distintos e testes por operação | Cada owner de módulo; FIN/CON/INT | ABERTO |
| CORE02-D07 | Step-up interno F1-03/04 | Alçadas específicas pendentes | Ações sensíveis, janela, risco e aprovação contextual; sem segundo aprovador universal | Segurança/IAM; CORE04/05/08/CT02 | ABERTO |
| CORE02-D08 | Fronteira deny + RLS parcial | Físico pendente | Enforcement real por canal/tabela, versão/cache, revogação e projeções/contagens/arquivos sem leak | Backend/DBA; CORE01/03/07/09 | ABERTO |
| CORE02-D09 | Nenhum entitlement real | Revisão comercial pendente | Catálogo de direitos e quotas por plano/ação/canal, estados e fonte oficial | Produto/FIN/CON; CORE08/AUT | ABERTO |
| CORE02-D10 | Matriz negativa sintética | Homologação pendente | Casos canônicos por módulo/ator/campo/escopo, auditoria/retencão e aceite de especialistas | QA/Segurança/owners; CORE06/10 | ABERTO |

### CORE03 — fonte [C03, §9][C03]; D01..D03 base aceita pelo aditamento

| ID | Laboratório | Documental | Falta / evidência para homologação | Owner proposto / dependências | Estado |
|---|---|---|---|---|---|
| CORE03-D04 | F1-06 DTO estrito | Envelope lógico aceito; físico pendente | Schema/versionamento e origem/autenticação verificável por operação | IAM/INT/Segurança; CORE09/CT03 | ABERTO |
| CORE03-D05 | A revalida humano; B–E negam | Classes base aceitas | Delegação atual por classe, consentimento/mandato/plano e revogação antes do efeito | Owners/IAM; CORE02/04/07/08 | ABERTO |
| CORE03-D06 | GUC/lock/snapshot em CI | Contexto físico pendente | Unit of work, roles/pool/worker/quarentena e ameaça SQL aceitos | DBA/Arquitetura/SEG; CORE01/09 | ABERTO |
| CORE03-D07 | F1-08 ledger e F1-09 consulta | Filas/contratos executáveis pendentes | Fila/outbox/inbox reais, idempotência no owner, retry/DLQ/replay/cancelamento por domínio | Eventos/Filas/AUT/INT; AUT-P04/ARQ02 | ABERTO |
| CORE03-D08 | Nenhum conector real | Contratos específicos pendentes | Interfaces Sites/WhatsApp/FIN/Agenda/IA com confirmação competente; IN-127 só Sites | Respectivos owners/INT | ABERTO |
| CORE03-D09 | Sinais sanitizados F1-07 | Quotas/retencão/alertas pendentes | Limites por tenant, observabilidade independente, cofre e auditoria sem PII indevida | Operações/CORE06/09/SEG/LGPD | ABERTO |
| CORE03-D10 | Probes sintéticos A–E | Aceite final pendente | Matriz real de fronteiras, efeitos/restore/revogação com owners e homologação | QA/Segurança/CORE10/INT | ABERTO |

### CORE04 — fonte [C04, §3][C04]; D01..D03 base aceita

| ID | Laboratório | Documental | Falta / evidência para homologação | Owner proposto / dependências | Estado |
|---|---|---|---|---|---|
| CORE04-D04 | Senha/TOTP internos | Métodos finais pendentes | Titularidade/verificação inicial, enrollment, escolha de fatores/passkeys, acessibilidade/antifraude | IAM/UX/Segurança; SEG/CORE01 | ABERTO |
| CORE04-D05 | Registry/rotação/locks | Sessões por papel pendentes | Política web/API/dispositivo, durações e cookies/tokens, consistência distribuída e concorrência | IAM/Segurança; CORE01/02/09 | ABERTO |
| CORE04-D06 | Budgets Redis e pane | Números candidatos | Stuffing/spray/DoS, desbloqueio/contestação e alertas; lease/evicção/fencing avaliados | Segurança/Operações/Observabilidade | ABERTO |
| CORE04-D07 | Recovery sintético limitado | Prova real pendente | Recuperação por papel/último admin, autoridade/titularidade, notificações e contestação | IAM/Privacidade/Suporte; CORE05/07/08 | ABERTO |
| CORE04-D08 | Step-up interno | Matriz sensível pendente | Política por ação/risco e aprovações próprias, sem janela universal presumida | Segurança/owner; CORE02-D07/CT02 | ABERTO |
| CORE04-D09 | IamAudit mínimo | Auditoria oficial pendente | Categorias, acesso/mascara/exportação, integridade e retenção homologadas | Auditoria Geral/CORE06/LGPD | ABERTO |
| CORE04-D10 | Revogação SQL e replay negado | Contrato integrado pendente | Propagação/revalidação em API/serviço/job/cache/anexo, testes multissistema | IAM/CORE03/09/10/SEG | ABERTO |

### CORE05 — fonte [C05, §9][C05]; D01..D03 base aceita

| ID | Laboratório | Documental | Falta / evidência para homologação | Owner proposto / dependências | Estado |
|---|---|---|---|---|---|
| CORE05-D04 | Nenhum suporte comercial | Catálogo pendente | Separar telemetria e acesso a objeto/campo sensível, operações permitidas e minimização | Suporte/Segurança; CORE02 | ABERTO |
| CORE05-D05 | Nenhuma titularidade real | Legitimidade pendente | Representante elegível, autorização cliente/legal/contratual e alçadas verificáveis | Jurídico/LGPD/CON; CORE04/INT OWN | ABERTO |
| CORE05-D06 | Mandato F1-04 só provisão lab | Mandato comercial pendente | Operador, tenant/recurso/ação/prazo, revogação e RLS sem bypass | IAM/Segurança/DBA; CORE01/03/04/SEG | ABERTO |
| CORE05-D07 | Nenhum break-glass | Procedimento pendente | Playbook, autoridade, duração, verificação, comunicação e revisão sem superpoder improvisado | Incidentes/Segurança/AGT/SEG | ABERTO |
| CORE05-D08 | Nenhum painel de suporte | Transparência pendente | Consulta/revogação/contestação pelo cliente com isolamento e comunicação | Suporte/UX/Privacidade/CON | ABERTO |
| CORE05-D09 | IamAudit não substitui trilha | Auditoria de suporte pendente | Eventos/campos/consulta/export/retencão e auditoria da investigação | CORE06/Auditoria/INT12/LGPD | ABERTO |
| CORE05-D10 | Nenhuma homologação real | Contratos/aceite pendentes | Matriz negativa por mandato/recurso/ator, responsabilidade e gates cruzados | QA/Segurança/CORE10/INT | ABERTO |

### CORE06 — fonte [C06, §9][C06]; D01..D03 base aceita

| ID | Laboratório | Documental | Falta / evidência para homologação | Owner proposto / dependências | Estado |
|---|---|---|---|---|---|
| CORE06-D04 | Vocabulário IAM/async mínimo | Taxonomia base aceita | Catálogo de eventos/PII/finalidades por domínio e origem oficial | Owners/Auditoria/Privacidade | ABERTO |
| CORE06-D05 | Insert-only IAM, samples lab | Físico/proveniência pendentes | Armazenamento/integridade/criptografia/fail-safe, resistência a adulteração e custo | Arquitetura/Segurança/Auditoria | ABERTO |
| CORE06-D06 | Nenhuma consulta oficial | Acesso final pendente | Direito separado de consulta/exportação, justificativa e auditoria da auditoria | IAM/CORE02/05/INT12 | ABERTO |
| CORE06-D07 | Nenhuma retenção nova | Prazos existentes preservados | Política por registro oficial, legal hold, cancelamento, fiscal, backups/anexos e LGPD | Jurídico/Privacidade/FIN/CON | ABERTO |
| CORE06-D08 | Restore descartável F1-09 | Expurgo/restore real pendentes | Eliminação comprovável por categoria e recuperação sem ressuscitar dados descartados | Operações/DBA/LGPD/ARQ | ABERTO |
| CORE06-D09 | Sem sink/monitor real novo | Volume/custos pendentes | Índices/capacidade/cotas/alertas/export e observabilidade independente | Operações/FIN/OBS | ABERTO |
| CORE06-D10 | Provas laboratoriais | Homologação oficial pendente | Matriz de trilhas/cobertura/proveniência, revisão LGPD/Segurança e aceite | Auditoria/QA/CORE10/INT | ABERTO |

### CORE07 — fonte [C07, §9][C07]; D01..D03 base aceita

| ID | Laboratório | Documental | Falta / evidência para homologação | Owner proposto / dependências | Estado |
|---|---|---|---|---|---|
| CORE07-D04 | Vínculo distinto do convite | Princípio aceito | Token/expiração/reenvio/cancelamento/aceite e corrida/idempotência de convites | IAM/CORE01/04 | ABERTO |
| CORE07-D05 | Nenhum assento real | Contagem/reserva pendentes | Quota por estado, último assento simultâneo, extras/trial/benefícios | Produto/FIN/CON/CORE08 | ABERTO |
| CORE07-D06 | Nenhuma troca de admin real | Gestão/titularidade pendentes | Transferência/recuperação do último admin sem bypass MFA/assento | IAM/Segurança/CORE04/08 | ABERTO |
| CORE07-D07 | Nenhuma equipe/posse real | Regras por módulo pendentes | Equipe/unidade/caixa/redistribuição e visibilidade, separadas de membership | VEN/ATD/AGD/CORE02 | ABERTO |
| CORE07-D08 | Revogação sessão/vínculo local | Integração pendente | Cache/job/anexo/export/automação/conexão e downgrade sem direito residual | CORE01/02/03/04/SEG | ABERTO |
| CORE07-D09 | Auditoria IAM parcial | Trilha por vínculo pendente | Convite/papel/equipe/transferência/desligamento e mascaramento/retencão | Auditoria/CORE06/LGPD | ABERTO |
| CORE07-D10 | Testes parciais F1-02..09 | Estados/aceite pendentes | Matriz simultânea/multiempresa/quotas/escalada e contratos reconciliados | QA/CORE10/INT/SEG | ABERTO |

### CORE08 — fonte [C08, §9][C08]; D01..D03 base aceita

| ID | Laboratório | Documental | Falta / evidência para homologação | Owner proposto / dependências | Estado |
|---|---|---|---|---|---|
| CORE08-D04 | Nenhum benefício comercial | Distinções lógicas aceitas | Contratos/versionamento Site/CNPJ/tenant/assinatura/trial/cortesia e idempotência | FIN/CON/Portal/CORE01 | ABERTO |
| CORE08-D05 | Nenhuma combinação real | Precedência pendente | Concessões simultâneas/prazos/estado e fonte financeira, sem benefícios implícitos | FIN/CON/GRAT/REV02 | ABERTO |
| CORE08-D06 | Nenhuma ativação real | Prova/destino pendentes | Titularidade, vínculo/campanha, antifraude, concorrência e prevenção de tenant fantasma | Segurança/Portal/INT/CORE03 | ABERTO |
| CORE08-D07 | Nenhum ciclo financeiro | Estados oficiais pendentes | Ordenação/timestamps/fuso, confirmação financeira, reativação e reconciliação | FIN/CON/INT CT03/04 | ABERTO |
| CORE08-D08 | Nenhuma conta cliente real | Direitos restritos pendentes | Portabilidade/MFA/suporte/cobrança e comunicação/avisos por estado, LGPD | CORE04/05/06/UX/Privacidade | ABERTO |
| CORE08-D09 | Nenhuma quota/plano real | Valores finais pendentes | Direitos/assentos/extras/IA/anexos, preço/trial/downgrade e revisão comercial | Produto/FIN/CON/CORE07 | ABERTO |
| CORE08-D10 | Nenhuma homologação comercial | Casos/aceite pendentes | Matriz owners/Portal/INT/FIN, prova de titularidade e efeitos oficiais | QA/CORE10/FIN/CON/INT | ABERTO |

### CORE09 — fonte [C09, §9][C09]; D01..D03 base aceita

| ID | Laboratório | Documental | Falta / evidência para homologação | Owner proposto / dependências | Estado |
|---|---|---|---|---|---|
| CORE09-D04 | APP_KEY/senhas CI efêmeras | Opções de cofre não escolhidas | Provedor/topologia/região/HA/criptografia/custo/capacidade e suporte aprovados | SEG02/Arquitetura/Operações/FIN | ABERTO |
| CORE09-D05 | B–E sem credencial técnica | Cadeia lógica aceita | Bootstrap/autenticação/renovação de serviço por ambiente/tenant e origem verificável | IAM/SEG02/CORE01/04/INT | ABERTO |
| CORE09-D06 | Deny-only sem ferramentas | Matriz executável pendente | Agente/serviço×ação×ferramenta×alvo×tenant×risco, autorizador independente | SEG01/AGT01/CORE02/INT SEC | ABERTO |
| CORE09-D07 | Epoch de sessão humana parcial | TTL técnico pendente | Rotação/revogação local/remota, versões e propagação/reconciliação em workers | SEG02/INT02/CORE03/04 | ABERTO |
| CORE09-D08 | Restore de arrays, não cofre | DR real pendente | Backup/chaves/failover/falta de cofre/contingência e vigilância independente | ARQ/Operações/DBA/CON01 | ABERTO |
| CORE09-D09 | Sinal sanitizado lab | Governança final pendente | Aprovações, auditoria/retencão/incidentes/LGPD e custo/licença | CORE05/06/INT12/FIN/Jurídico | ABERTO |
| CORE09-D10 | Testes negativos parciais | Contratos/aceite pendentes | Homologação multissistema de identidade/cofre/ferramentas e evidências por owner | Segurança/QA/CORE10/INT/SEG | ABERTO |

### CORE10 — fonte [C10, §9][C10]; D01..D03 aceitam método, não fechamento

| ID | Laboratório | Documental | Falta / evidência para homologação | Owner proposto / dependências | Estado |
|---|---|---|---|---|---|
| CORE10-D04 | ADRs/links históricos | Método de rastreio aceito | Cotejo literal de fontes/versões/aceites/exceções e divergências resolvidas | Arquitetura documental/INT DOC/TR | ABERTO |
| CORE10-D05 | Checklist de contenção F1-10 | Critérios físicos pendentes | Matriz domínio/tenant/papel/recurso/campo com owners reais e contratos executáveis | IAM/Segurança/owners/INT OWN | ABERTO |
| CORE10-D06 | CI sintética por SHA | Roteiros finais pendentes | Catálogo canônico de riscos/casos/ambientes/cobertura sem duplicar HT/AUT/CORE | QA/INT ACC/AGT | ABERTO |
| CORE10-D07 | Falha fechada observada | NO-GO/risco pendentes | Severidade, limiares/defeitos/exceções e aceite explícito de risco por release | Segurança/Arquitetura/owners | ABERTO |
| CORE10-D08 | CI sem custos externos | Custos/continuidade pendentes | Licenças/fornecedores/LGPD/contratos/topologia/backup/RPO/RTO e orçamento | FIN/CON/Jurídico/Operações | ABERTO |
| CORE10-D09 | Matriz transversal proposta | Conciliação global pendente | CORE/REV/INT/IN-168/G01..06 e P1/P2 impeditivos reconciliados com responsáveis | Arquitetura/Segurança/Auditoria | ABERTO |
| CORE10-D10 | Nenhum GO de produção | Aceite final pendente | SHA/tag/escopo/checklist revisados e assinatura/autorização expressa competente | Direção/Governança/owners | ABERTO |

## 6. SEG, AUT e demais dependências transversais

### SEG/infra/dados — prioridades originais de [IN-168][IN168]

| ID / prioridade | D e L existentes | H/N e evidência requerida | Owner proposto / dependências | Estado |
|---|---|---|---|---|
| SEG-01 / P0 | Diretriz de autorização independente; deny L | Matriz ferramenta/ator/ação/serviço/tenant/ambiente/risco e revogação auditável | Segurança/IAM/AGT/CORE02/09 | ABERTO |
| SEG-02 / P0 | Isolamento lógico D; secrets CI não são cofre | Escolha/projeto de cofre/identidade/rotação/contingência/custo e homologação | Segurança/Operações/FIN/CORE09 | ABERTO |
| ARQ-01 / P0 | Separação de aplicações/agentes/n8n D | Inventário/topologia/capacidade/região/custo/contingência reais | Arquitetura/Operações/FIN | ABERTO |
| ARQ-02 / P0 | Contratos lógicos e ledger L | Fronteiras API/filas/CRM com identidade/estado/recepção e confirmação; IN-127 só Sites | INT/Filas/CORE03/Portal | ABERTO |
| ARQ-03 / P0 | Diretriz de monitor independente D | Componentes/alertas fora da IA/supervisor e falhas comuns homologados | Operações/Segurança/OBS01 | ABERTO |
| AGT-01 / P0 | Níveis/playbooks propostos | Catálogo homologável, alçadas/risco/suspensão/reversão e licença n8n interno | Segurança/Operações/SEG01 | ABERTO |
| CON-01 / P1 | Metas de continuidade conceituais | RPO/RTO numéricos por serviço, restauração real autorizada e orçamento | Operações/CON/FIN/ARQ | ABERTO |
| OPS-01 / P1 | Diretrizes de assunção D | Cobertura humana/substitutos/canais/contingência e custos | Operações/direção | ABERTO |
| FIN-01 / P1 | Sem despesa nova | Orçamento IA interno/cotas/modelos/alertas, separado de créditos de clientes | FIN/direção/IA01 | ABERTO |
| IA-01 / P1 | Metodologia G1–G8/critérios D | Modelo/versão/tarefa/provedor/fallback/preço e avaliação autorizada; nenhum modelo homologado aqui | IA/Segurança/FIN | ABERTO |
| OBS-01 / P1 | Diagnósticos lab não são sink | Contratos/logs/métricas/traces/alertas/tecnologia/custo sem central paralela | Observabilidade/CORE06/ARQ03 | ABERTO |
| DAD-01 / P1 | Prazos por categoria preservados | Expurgo/backup/exceção legal e prova de destruição/restore segregado | LGPD/DBA/CORE06/ARQ02 | ABERTO |
| AGT-02 / P1 | Sem publicação operacional | Manifesto/versionamento/rollback e prazo próprio de prompts aprovado | AGT/Segurança/LGPD | ABERTO |
| DAD-02 / P2 | Revisão não implica apagar | Destino de rascunhos revisados não arquivados | Privacidade/owners | ABERTO |
| OPS-02 / P2 | Provas CI não são DR real | Indicadores/desempenho/recuperação/homologação periódica autorizada | Operações/QA/CON01/OBS01 | ABERTO |
| DOC-01 / P2 | Conciliações locais registradas | Auditoria cruzada final e precedência das decisões por produto | Governança/owners | ABERTO |
| DOC-02 / P2, impeditivo global | Pacote proposto | Baseline/escopo/custo/risco revisados e aprovação final expressa | Direção/CORE10/INT | ABERTO |

### AUT-P01..14 — fonte [AUT, §12][AUT]

AUT-P0 tem aceite de **base documental**, não parâmetros/contratos individuais.
Prioridade abaixo é **proposta para ordenar a transição**, sem mudar a fonte.
P0 = condição antes de executar a capacidade; P1 = detalhamento funcional que
também impede aquela capacidade enquanto ausente. Motor nativo do cliente e
n8n interno Imagine são distintos, sem licença operacional inferida.

| ID / prioridade proposta | D / L | H/N e evidência requerida | Owner proposto / dependências | Estado |
|---|---|---|---|---|
| AUT-P01 / P0 | Envelope lógico D; DTO lab L | Gatilho/evento oficial/owner e versão por módulo | AUT/owners/CORE03/INT | ABERTO |
| AUT-P02 / P0 | Condições tipadas D | Operador/campo/escopo, nulos/fuso e snapshot versus estado atual homologados | AUT/DAT/owners/CORE02 | ABERTO |
| AUT-P03 / P0 | Ação proprietária D; deny L | Ação real, autorização e confirmação no owner por canal/recurso | Owners/CORE02/03/INT | ABERTO |
| AUT-P04 / P0 | Ledger/consulta lab L | Outbox/inbox/filas reais, loop guard, checkpoint, efeito idempotente/replay/concorrência por domínio | Eventos/Filas/AUT/INT/DBA | ABERTO |
| AUT-P05 / P0 | Versões imutáveis D | Coexistência/migração/pausa/retomada e revalidação atuais | AUT/CORE03/owners | ABERTO |
| AUT-P06 / P1 | Biblioteca/elegibilidade D | Catálogo comercial final/modelos publicados e critérios específicos | Produto/AUT/owners | ABERTO |
| AUT-P07 / P1 | Referências históricas de slots D | Revisão comercial conjunta, sem ratificar números por este baseline | Produto/FIN/CON/CORE08 | ABERTO |
| AUT-P08 / P0 | Contadores distintos D | Fonte de entitlement/quotas/saldo/custos e prova de limites concorrentes | FIN/CON/CORE08/AUT | ABERTO |
| AUT-P09 / P1 | Import seguro D | Schema/versionamento/remapeamento/compatibilidade sem segredos | AUT/Segurança/CORE02 | ABERTO |
| AUT-P10 / P0 | Motores centrais/competências D | Contratos CORE/INT executáveis e isolamento do n8n | Arquitetura/AUT/INT/SEG | ABERTO |
| AUT-P11 / P0 | Revalidação D; deny L | Direitos reais de criar/publicar/pausar/retomar e auditoria | IAM/AUT/CORE02/04/06 | ABERTO |
| AUT-P12 / P1 | Relógios por módulo D | Prazos/estados elegíveis de ATD/AGD/VEN, sem timer universal | Respectivos owners/AUT | ABERTO |
| AUT-P13 / P0 | Canal não vem do slot D | Canal/consentimento/provedor/Meta/BSP/custo e licenciamento | ATD/COM/INT/FIN/LGPD | ABERTO |
| AUT-P14 / P1 | Regras de módulos preservadas D | Conciliação LEG/ATD/VEN/AGD/IA e inventário final de dependências | Owners/Produto/CORE10 | ABERTO |

### INT, FIN/CON e LGPD — fontes [INT GATE][INTG], [CT-03/04][CT], [IN-168][IN168]

| ID / prioridade | D / L | H/N e evidência requerida | Owner proposto | Estado |
|---|---|---|---|---|
| P0-DOC-01 / P0 | Sínteses INT existentes | Fontes originais/aceites/exceções cotejados literalmente | Governança INT/direção | ABERTO |
| P0-DOC-02 / P0 | INT-R01/R02 versionados D | Cotejo integral das normas e contratos/invariantes | Arquitetura INT | ABERTO |
| P0-TR-01 / P0 | TR/CT/HT mapeados D | Proveniência verificável e divergências resolvidas | INT/owners | ABERTO |
| P0-OWN-01 / P0 | Competências lógicas D | Owner por operação/recurso/etapa/confirmação e responsabilidade real | Owners dos módulos/INT | ABERTO |
| P0-CON-01 / P0 | CT01..11 bases documentais | Schema/versão/estado/parâmetros por operação/provedor e homologação | INT/owners | ABERTO |
| P0-SEC-01 / P0 | Autoridade separada do executor D | Contratos de permissão/alçada/delegação/origem e aceites | IAM/Segurança/SEG | ABERTO |
| P0-DEP-01 / P0 | Mapa parcial D | Dependências operacionais/contingência sem duplicar motores | Arquitetura/INT | ABERTO |
| P0-VER-01 / P0 | Versionamento conceitual D | Compatibilidade/migração de instâncias e conectores | INT/owners | ABERTO |
| P0-ACC-01 / P0 | Roteiros HT aprovados como base | Homologação integrada com dados/ambiente/owner/evidências; não converter testes F1 em HT executados | QA/INT/owners | ABERTO |
| P0-XREF-01 / P0 | Links cruzados D | Conciliação entre fontes CORE/REV/INT/Sites e precedência | Governança/owners | ABERTO |
| P0-GIT-01 / P0 | Commits de trabalho L | SHA/tag final aprovado como fechamento com escopo e fonte | Direção/Governança | ABERTO |
| G01 / gate | Fontes parciais | Completude/proveniência final de fontes conforme painel | Governança INT | ABERTO |
| G02 / gate | Documentação integral ainda parcial | Sínteses cotejadas com textos completos e contratos granulares conforme painel | INT/owners | ABERTO |
| G03 / gate | Dependências parciais | Reconciliação de fronteiras e lacunas conforme painel | Arquitetura/Segurança | ABERTO |
| G04 / gate | Owners/validação parciais | Responsabilidades/aceites específicos conforme painel | Owners/QA | ABERTO |
| G05 / gate | Sem aceite global | Aprovação final expressa e riscos tratados | Direção/owners | ABERTO |
| G06 / gate | Sem tag final homologada | Versão/SHA final aprovado, sem inferir pelo último CI | Direção/Governança | ABERTO |
| FIN/CON/REV02/GRAT-P / P0 no recorte | Regras funcionais e bases D | Titularidade, direitos/planos/quotas/estados/preços/licenças e confirmação financeira por fonte competente | FIN/CON/Produto/Portal | ABERTO |
| LGPD / transversal impeditivo | Minimização e categorias D; sinais sintéticos L | Finalidade/base legal, acesso/portabilidade/retencão/expurgo/hold/backup/suporte e análise competente por dados reais | Privacidade/Jurídico/owners/CORE06 | ABERTO |

As prioridades não dispensam o processo de aceite. Nenhum teste novo é
homologação INT HT01..18, avaliação jurídica, aprovação comercial ou prova de
produção. Não generalizar retenção/entrega de leads IN-127 para filas CRM.

## 7. Sequência técnica recomendada — proposta, não implementação

**Situação atual: NO-GO para habilitar funcionalidades reais.** O fast-track
foi autorização limitada de laboratório. O próximo bloco a submeter à direção
é um **pacote de contratos e critérios de aceite do primeiro recorte funcional**,
não outro protótipo assíncrono nem liberação genérica de ALLOW.

| Ordem proposta | Entrada necessária / trabalho a submeter | Entrega e critério técnico antes de avançar | Gates/owners e limites |
|---|---|---|---|
| 1. Delimitar recorte e responsabilidades | Revisão deste baseline, fontes/precedência e designação humana dos owners; escolher operação/módulo/dados/canais exatos | Documento versionado de atores, recurso/ações/campos/escopo, invariantes, ameaças, critérios positivos/negativos e dependências aplicáveis | CORE10/INT OWN/CORE02, direção/owners. Nada implementado por esta proposta |
| 2. Contratos executáveis IAM/tenant/autorização | CORE01-D04/D05/D07 + CORE02-D04..D08 + CORE04/06 pertinentes; SEG01/02 e FIN/CON para o direito do recorte | Resolver identidade/titularidade/membership, matriz real e precedência P1+P2, MFA/recuperação, sessão/revogação, auditoria e RLS/roles por recurso; ameaça SQL/pool revisada | IAM/DBA/Segurança/Privacidade/owner; sem inventar plano/mandato. Exigir aceites específicos e governança global |
| 3. Homologar fundação em ambiente autorizado | Contratos físicos aceitos, autorização expressa de implementação do recorte e ambiente descartável/homologação aprovado | Migrações/upgrade/rollback, constraints tenant-bound, origem de identidade, ausência de bypass, concorrência/revogação, logs minimizados, integridade/recuperação e testes positivos/negativos por canal | QA/Segurança/Operações/CORE10. Não promover fixtures automaticamente; main/VPS ainda exigem autorização distinta |
| 4. Primeira fatia funcional interna restrita | Revisão da fundação e GO humano específico para usuários internos designados | Candidato a deliberar: gestão interna de organização/membership e, depois, uma operação DAT mínima de lead/contato; schema/dados/owner/finalidade e direitos aprovados; autorização específica antes de qualquer grant real | CORE01/02/04/06/07/08, DAT/LGPD/FIN/CON; seleção do candidato não é decisão. Sem login público, import/export ou integração por inferência |
| 5. Ampliar fluxos por módulo | Matriz e aceite próprios da próxima operação, entitlement/cota/consentimento oficiais | Incrementos DAT/VEN/ATD/AGD por operação, com testes de projeção/busca/count/export/anexo quando especificamente autorizados e fonte de confirmação no módulo | Respectivos owners/CORE02/08/INT/FIN/CON; não herdar grant de outro módulo |
| 6. Assíncrono/integrações e AUT | CORE03/09, SEG/cofre/serviço, AUT-P01..14, ARQ02 e CT/owners aprovados para cada efeito | Filas/outbox/inbox e idempotência no proprietário, confirmação/reconciliação, limites/DLQ/retry/cancelamento por domínio e falhas tardias; só depois automações comerciais elegíveis | Eventos/Filas/AUT/INT/Segurança. n8n apenas Imagine interno sob licença; IN-127 somente Sites |
| 7. Abertura pública e release | Gates de segurança/dados/operação/comercial aplicáveis tratados, homologação e autorização global na forma normativa | Titularidade/onboarding/MFA/abuso/suporte, continuidade/restore/monitoramento/retencão, custos/licenças, checklist de release/SHA/tag e aprovação expressa | CORE10/G01..06/SEG/ARQ/FIN/CON/LGPD/direção. Merge/main/VPS/produção são decisões separadas |

Critérios mínimos para **solicitar** início do código funcional: owners
formalmente definidos; requisitos/fontes/aceites coerentes; contratos de
identidade, tenant, direito, recurso/ação/campo/escopo e dados completos para
o recorte; SEG e dependências impeditivas resolvidas conforme normas; threat
model e plano de testes/homologação revisados; escopo/ambiente/custos permitidos;
baseline imutável e autorização humana específica registrada. Caso a direção
queira liberação faseada, deve deliberar expressamente o escopo e as condições;
este documento não dispensa gates globais nem aceita riscos em nome dela.

O primeiro ALLOW futuro teria de ser estreito, comprovado e revisado para uma
operação real homologada, com deny nas demais; isso **não é implementado aqui**.
Não abrir autenticação pública para “testar depois”, nem usar ledger/UUID/GUC,
snapshot, job ou admin global como fonte de direito.

## 8. Validação e entrega desta etapa

Executar as duas suítes completas existentes, com fixtures fictícias e PG16/
Redis efêmeros. Registrar abaixo SHA, logs, pass/skip/asserts após execução.
PHP/Composer/Docker ausentes no terminal local; nenhum resultado local de
plataforma será declarado. Conferir SHA final do PR após commit documental.
Preservados app/, config/, routes/, bootstrap/, database/, workflows, normas,
AGENTS.md/STATUS-ATUAL.md, composer.json/lock e todas as regressões anteriores.
Próximo passo: revisão humana do DRAFT e deliberação do pacote da ordem 1–2.
Sem merge, main, VPS, produção, dados reais, serviço pago ou novo efeito.

### Evidências executadas e conferidas em 06/11/2026

[PR #20 DRAFT](https://github.com/rafhael100/imagine-crm/pull/20), revisão
`bdf7cc486dc1f2cae6cc8dc658643f9d3d7d7428`. Execuções de 05/11/2026,
resumos/probes conferidos nos logs em 06/11/2026, America/Sao_Paulo.
Ambos os checks **success**, sem falha de suíte ou correção de código após
a primeira execução deste incremento.

| Ambiente/comando | Resultado real | Log |
|---|---|---|
| PHP 8.4 / Laravel 13 / SQLite — php artisan test --no-ansi | **383 passed, 64 skipped, 2205 assertions; 0 falhas** | [42133251625 / 127614884468](https://github.com/rafhael100/imagine-crm/actions/runs/42133251625/job/127614884468) |
| PostgreSQL 16 / Redis 7 efêmeros — suíte completa | **446 passed, 1 skipped, 2992 assertions; 0 falhas** | [42133251598 / 127614883692](https://github.com/rafhael100/imagine-crm/actions/runs/42133251598/job/127614883692) |
| Mesmo job PostgreSQL — filtro PostgresMembershipRlsTest | **18 passed, 49 assertions** | Mesmo log PostgreSQL |
| Local — git diff --cached --check nos quatro arquivos F1-10 | **Sem erros de whitespace** | Snapshot de edição, não histórico remoto |

18 casos novos: 16 da baseline runtime/metadata/documento e dois da baseline
ACL (um catálogo PG real e uma contraprova pura). Os 64 skips SQLite exigem
PostgreSQL/Redis, incluindo o novo catálogo de ACL; executados na plataforma
adequada. Único skip PostgreSQL é exclusivo de SQLite. O teste de metadata
usa o evento real pull_request na CI destes logs; local/push sem esse evento
terá um skip adicional explicitamente justificado, além das contraprovas puras.

Confirmados os probes effective role and column acl matches the existing
closed laboratory matrix, acl verifier rejects added business and column
grants, e postgres shared redis budgets and contended lock fail closed.
Permanecem as regressões reais de falha Redis, RLS C/D, GUC mutável não
autenticadora, locks/revogação, isolamento de migrações, ledger inerte e
restore read-only/consultivo. Nenhum teste F1 é declarado HT de homologação.

Não executados localmente: Laravel/PostgreSQL/Redis, por ausência de
PHP/Composer/Docker no terminal. Execução efetiva somente em CI sintética.
App/config/bootstrap/routes/database/workflows comparados por hash com F1-09,
sem diferenças. Composer lock e autorizador preservados. Publicação por
GitHub autenticado, sem usar/pushar ancestralidade do snapshot Git local.
O upload do documento ficou pendente e foi substituído pelo editor GitHub;
conteúdo publicado conferido contra o arquivo local e os 67 IDs antes do PR.

Este registro altera apenas documentação. Conferir novamente SHA/checks
finais do PR após o commit; registrar os links finais na descrição do PR,
sem criar ciclo de commits de evidência. Nenhum aceite humano, GO funcional,
merge ou fechamento de CORE/SEG/AUT é declarado por estes resultados.

[E1]: ../planejamento/CRM-CORE-P0-ETAPA1-painel-fechamento.md
[E2]: ../planejamento/CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md
[R01]: ../planejamento/CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md
[C01]: ../planejamento/CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md
[C01X]: ../planejamento/CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md
[C02]: ../planejamento/CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md
[C03]: ../planejamento/CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md
[C04]: ../planejamento/CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md
[C05]: ../planejamento/CRM-CORE-P0-05-acesso-excepcional-imagine-mandatos-suporte-proposta-v0.1.md
[C06]: ../planejamento/CRM-CORE-P0-06-auditoria-categorias-retencao-acesso-proposta-v0.1.md
[C07]: ../planejamento/CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md
[C08]: ../planejamento/CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md
[C09]: ../planejamento/CRM-CORE-P0-09-cofre-segredos-identidades-servico-proposta-v0.1.md
[C10]: ../planejamento/CRM-CORE-P0-10-pacote-homologacao-documental-nucleo-proposta-v0.1.md
[P12]: https://github.com/rafhael100/imagine-crm/blob/e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012/docs/planejamento/CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md
[AUT]: ../planejamento/CRM-AUT-P0-execucao-segura-motor-proprio-automacoes-v0.1.md
[AUTDEC]: ../planejamento/CRM-DEC-aceite-base-AUT-P0-execucao-segura-2026-11-04.md
[IN168]: ../planejamento/IN-168-matriz-pendencias-fechamento-arquitetura.md
[INTG]: ../planejamento/integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md
[CT]: ../planejamento/integracoes/P0-CON-CT03-CT04-comandos-resultados-transicoes.md
[DEVG]: ../planejamento/CRM-DEV-GATE-00-checklist-unico-liberacao-desenvolvimento-v0.1.md
