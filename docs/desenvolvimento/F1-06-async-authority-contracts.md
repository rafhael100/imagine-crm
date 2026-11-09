# F1-06 — contratos experimentais de autoridade assíncrona (deny-only)

05/11/2026, [Issue #11](https://github.com/rafhael100/imagine-crm/issues/11).
Base `dev/fast-track-crm-2026-11-04` em
`0e93a6919ea611a02bce487e7975e1abb21b2ee9`, incluindo F1-02..F1-05.
Branch `codex/f1-06-async-authority-contracts`, PR DRAFT somente para DEV.

## Fontes e conciliação

Lidos AGENTS.md e STATUS-ATUAL.md atuais, Issue #11, ADRs F1-02/03/04/05,
CORE03, CORE09, CORE01/02/04/05/06/07/08, SEG-01/02, CRM-AUT-P0,
INT-R01/INT-09, CT03/CT04 e IN-127-R01..R06. Main conferida somente em
leitura em `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`; as decisões
posteriores continuam as conciliadas em F1-02/F1-05: P1+P2 escopada é base
documental, não grants ou política executável. CORE03/09-D01..D03 foram
aceitas como base documental, superando os trechos históricos que aguardavam
aprovação. D04..D10 e todos os gates permanecem abertos.

O comparativo GitHub `312f2933..0e93a691` tem somente AGENTS.md e
STATUS-ATUAL.md alterados. Reutilizado o snapshot local F1-05 publicado,
com esses documentos atualizados; a branch remota foi criada da DEV real.
Git local serve apenas a edição/diff, nunca ao envio de ancestralidade falsa.
AGENTS.md/STATUS-ATUAL.md, workflows, composer.json/lock e decisões normativas
não são alterados por esta etapa. Sem migrations/outbox/inbox runtime novos.

## Testes confirmados antes da implementação

HEAD atual da DEV `0e93a6919ea611a02bce487e7975e1abb21b2ee9`, ambos os
workflows de push com success e resumos dos logs conferidos:

| Ambiente | Resultado | Log da base atual |
|---|---|---|
| Laravel 13 / PHP 8.4 / SQLite | 229 passed, 32 skipped, 753 assertions | [42053187797 / 127334443459](https://github.com/rafhael100/imagine-crm/actions/runs/42053187797/job/127334443459) |
| PostgreSQL 16 / Redis efêmeros | 260 passed, 1 skipped exclusivo SQLite, 917 assertions | [42053187506 / 127334725130](https://github.com/rafhael100/imagine-crm/actions/runs/42053187506/job/127334725130) |

## ADR — intenção tipada, sem autoridade transportável

`AsyncAuthorityRequest` é um DTO imutável de alegações não confiáveis.
Schema inteiro 1 e policy `lab-async-deny-v1` fechados; ambiente exclusivamente
`laboratory`. Inclui operação/tenant, origem e referência de prova, classe
A–E, ator causal opcional conforme classe, executor técnico separado,
intenção F1-05 (recurso/ação/campos/escopo/canal/versão), finalidade sintética,
referências de autorização/mandato/entitlement, correlação/causalidade,
issued_at/expires_at, chave de idempotência e owner da confirmação.

Somente UUIDs/referências e vocabulário sintético fechado, sem conteúdo de
negócio. Campos extras, coerção de tipos, esquema/versão desconhecidos,
URL/SQL/script/payload/token, flag de assinatura verificada, suposto resultado
oficial e origem incompatível com a classe são rejeitados. Não aceitar
`interactive` para disfarçar uma alegação assíncrona. Humano causal não pode
ser representado pelo mesmo ID como executor de serviço (inclusive UUID em
caixa diferente). Referências nulas modelam evidências ainda ausentes;
referência preenchida também **não comprova autenticação ou autorização**.

O prazo fornecido não é autenticado e não fixa TTL global: o inspector rejeita
issued_at futuro e expires_at vencido pelo relógio backend; um prazo futuro
não permite executar. Os 60 segundos das fixtures não são política aprovada.
Não copiar os 432h/90 dias de Sites↔CRM para jobs. `received` em memória não
é persistência durável/T0, captação nem confirmação de entrega.

## ADR — ponte de inspeção e única autorização F1-05

`AsyncAuthorityBridge::inspect` não recebe callback de efeito e retorna apenas
`AsyncDenial`. Limitada a local/testing e profile IAM experimental explícito;
profile desligado/production nega antes de consumir identidade. Sem binding
permissivo, guard de serviço fictício, tokens emitidos, rotas ou handlers.

O inspector valida formato/contexto, lista dependências de vocabulário fixo
e nega. Reutiliza AuthorizationIntent, catálogo sintético e resultado fechado
AuthorizationDecision da F1-05. `DenyByDefaultAuthorizer` permanece o único
binding de Authorizer; não foi substituído nem ampliado. A ponte não combina
papéis/grants, não avalia entitlements e **não é segundo motor de permissão**.

Para A, somente um ensaio com o humano **atualmente** autenticado pode chegar
à fronteira F1-05. Ela mantém sessão/credencial/MFA sob locks, membership
reconsultada/RLS e decisão deny-only. `expectedPrincipalId` opcional na API
existente é uma restrição adicional aplicada **dentro do lock IAM**, antes
de membership; não seleciona nem autentica usuário. Chamadas anteriores sem
o parâmetro continuam iguais. O ID causal do payload não substitui o guard.
O único callback interno é uma sentinela que lança exceção, sem efeito.
Qualquer erro nega, sem expor exception text; não há retry automático.

Isso não é execução de job A por sessão serializada. Credencial/delegação
de executor técnico e revalidação humana independente de browser continuam
CORE03/09. Para B/C/D/E não há prova própria implementada: a ponte **não**
fabrica TenantContext humano, não consome GuardPrincipalSource e não invoca
autorização humana em nome de serviço/visitante. Retorna rejeição e contratos
pendentes. Ausência de funcionário não dá privilégio e também não define
que uma rotina obrigatória futura precise da sessão desse funcionário.

## Matriz de responsabilidades por classe

| Classe | Quem deverá autenticar/autorizar, com qual prova atual | Retorno oficial e pendências |
|---|---|---|
| A — humano diferido | IAM: humano global/membership/MFA atuais; executor técnico próprio, delegação por objeto/ação/campo/escopo, entitlement e regra do owner | Owner confirma ato. Sessão/membership revogada impede nova unidade; envio/receipt passado não confere direito futuro. CORE03-D05, CORE04/07/09 |
| B — processo oficial obrigatório | IAM/CORE09: serviço autônomo limitado, evento oficial do módulo e tenant/estado atuais; não login do ator causal antigo | Owner da rotina/fato. Não usar suspensão de slot comercial para cancelar obrigação legítima por analogia. CORE03/09, owner |
| C — automação nativa do cliente | Motor NATIVO/owner/IAM: definição publicada/versionada, instância elegível, executor, consentimento/canal, plano/quotas/direitos atuais | Owner do efeito. n8n não executa automações do cliente. AUT-P01..14, CORE02/08/09, FIN/CON |
| D — ingresso externo | Serviço oficial/INT: origem/assinatura quando aplicável, conexão/contrato/site/formulário/destino CRM autorizado; visitante não vira membership | Backend/owner confirma recepção durável e fato separadamente. IN-127 específico; ID/proof_reference não verifica assinatura. CORE03/09/INT/Sites |
| E — operação técnica Imagine | Serviço/Segurança: principal técnico, ambiente/playbook/versão/risco/alçada; suporte requer mandato próprio por caso/tenant/prazo | Owner confirma negócio. Agente/IA/prompt/admin não dão grant; n8n apenas interno sob licença adequada. CORE05/09, SEG-01/02, AGT/INT |

## ADR — ciclo e resultado exclusivamente inertes

`AsyncLifecycle` é contrato de traço sintético, não máquina global de negócio,
coordenador INT ou motor AUT. Transições explícitas: received →
format_validated → denied; recebido também pode negar diretamente. Um relato
de transporte sobre traço validado só produz inconclusive, seguido de
reconciliation_required. Admitted/forwarded/officially_confirmed são conceitos
reservados **inalcançáveis**; sem fábrica/transition para admissão, dispatch,
confirmação ou retry. HTTP 2xx, falha e timeout não provam negócio. Estado
negado ou reconciliação não reabre envio; nenhuma chamada externa ocorre.

`SyntheticAsyncInbox` existe **somente em tests/Fixtures**: compara
tenant+idempotency_key e fingerprint do DTO para distinguir first_seen,
duplicate e conflict. Nenhum desses resultados é admissão, aceite durável,
garantia exactly-once ou prova de autoridade. Não é persistência, outbox,
cache de autorização ou dedupe produtivo. Mesmo replay idêntico continua
negado pela ponte. Identidade/tentativas/versões reais e tratamento de crash
após efeito exigem owner, outbox/inbox e reconciliação homologados.

## ADR — contexto, falhas e evidência mínima

Ponte readonly/sem estado por tenant, sem cache de sessão/prova/decisão e sem
GUC própria. A revalidação humana usa a ordem existente: credencial → sessão
→ membership → autorizador, sem transação aninhada ou replay. Em PostgreSQL,
teste reutiliza a mesma ponte/conexão entre tenants e classes A–E, confirma
transações encerradas e GUC vazia após negação. Conexão com GUC previamente
manipulada é recusada pelo store; teste a limpa explicitamente em finally.
Não alegar que uma conexão contaminada está recuperada: quarentena/reset
seguro do pool/worker e atestado confiável continuam CORE03-D06/CORE01/SEG.

Signal reutilizado da F1-05 contém apenas código neutro e correlação NOVA
gerada pelo backend, não a correlação arbitrária recebida. Dependências são
códigos internos fixos e não são resposta pública de política. Sem logs de
payload/headers, exception text, identidades, tenant, UUID de segredo, PII,
prompt ou tokens. Auditoria IAM existente não é duplicada; integração à
Auditoria Geral, proveniência e retenção por categoria dependem CORE06/INT-12.
Nenhum novo prazo de retenção ou sink persistente é inventado.

## Gates para qualquer implantação futura em workers Laravel

| Gate aberto | Aceite/evidência ainda necessário | Responsáveis |
|---|---|---|
| CORE03-D04 / CORE09-D05 | Schema físico/transporte, autenticação de origem/executor e cadeia de confiança, assinatura/credenciais reais e proteção de referências | IAM/Segurança/CORE09/INT |
| CORE03-D05 / CORE02/04/05/07/08 | Delegação e revalidação específica A–E, MFA/alçadas, mandato, entitlement/consentimento/versões e revogação antes do efeito | Owners, IAM/FIN/CON/Segurança |
| CORE03-D06 / CORE01 / SEG-01/02 | Unidade transacional, roles sem bypass/ownership, pools/jobs sem contexto residual, limites/locks/SQL injection/GUC não autenticadora | DBA/Arquitetura/Segurança |
| CORE03-D07 / AUT-P01..14 | Filas duráveis, outbox/inbox, idempotência no owner, concorrência, crash/replay/retry/DLQ e reconciliação por domínio | Eventos/Filas/AUT/INT/owners |
| CORE03-D08 / INT | Contratos específicos Sites, WhatsApp, Agenda, FIN/CON, IA; confirmação pelo owner e IN-127 sem redirecionamento | Respectivos owners/INT |
| CORE03-D09 / CORE06 / CORE09-D09 | Minimização/retencão, auditoria oficial, quotas, limites, alertas/monitoração independente, contingência | Segurança/Operações/Auditoria/LGPD |
| CORE09-D04..D10 / SEG-01/02 | Cofre/provedor/identidade/ferramenta/risco, rotação/revogação, backup/restore, autoridade e licença n8n/custos | Segurança/Operações/FIN/INT |
| CORE03-D10 / CORE09-D10 | Matriz real por operação/tenant/ator/campo/escopo, testes positivos/negativos, aceite de especialistas e autorização própria de ativação | Owners/Segurança/QA/direção |

Todos os **67 IDs CORE**, ARQ/INT/G01..G06/FIN/CON/LGPD e demais pendências
seguem abertos. Modelo candidato, prova sintética, revisão/aceite e release
são marcos diferentes. Nenhum CI verde fecha gates. Próximo passo: revisão
humana desta PR DRAFT, sem merge automático ou avanço de etapa não solicitado.

Sem jobs/enqueue reais, eventos/webhooks públicos, autenticação técnica,
grants, tokens/segredos operacionais, comunicação externa, dados reais,
clientes, plano/cobrança, novo custo, main, n8n, VPS ou implantação.

## Validação da implementação

Executar e registrar as CI Laravel/SQLite e PostgreSQL 16/Redis da PR antes
da entrega; os resultados da base acima não validam o incremento novo.
PHP/Composer/Docker não disponíveis no terminal local. Composer lock intacto:
`08C77FE9D787BE841633DEA38461BBBDC4C3EDFA419FF676D8FADF7581636F5C`.
Skips SQLite que exigem PostgreSQL/Redis devem ser executados nessa plataforma.

## Evidências executadas em 05/11/2026

[PR DRAFT #12](https://github.com/rafhael100/imagine-crm/pull/12), revisão
`95facbc14432434c08bc64c0f94c44d3e64b5e40`. Ambos os workflows success;
resumos e casos relevantes conferidos nos logs GitHub Actions:

| Ambiente/comando | Resultado observado | Log |
|---|---|---|
| PHP 8.4 / Laravel 13 / SQLite — `php artisan test --no-ansi` | **307 passed, 33 skipped, 1014 assertions; 0 falhas** | [42070162389 / 127395597651](https://github.com/rafhael100/imagine-crm/actions/runs/42070162389/job/127395597651) |
| PostgreSQL 16 / Redis efêmeros — suíte completa | **339 passed, 1 skipped exclusivo SQLite, 1233 assertions; 0 falhas** | [42070162392 / 127395597743](https://github.com/rafhael100/imagine-crm/actions/runs/42070162392/job/127395597743) |
| Mesmo job PostgreSQL — filtro `PostgresMembershipRlsTest` | **18 passed, 49 assertions** | Mesmo log PostgreSQL |
| Local — `git diff --cached --check` nos arquivos F1-06 | **Sem erros de whitespace** | Snapshot de edição, não clone autenticado |

79 casos novos, além das regressões F1-02..F1-05. No job PostgreSQL passaram
`postgres reused inspector never keeps tenant context for any class`, a
regressão `postgres shared redis budgets and contended lock fail closed` e
os casos de negação sem jobs/eventos/mail/HTTP/exports por classe A–E.
Os 33 skips SQLite exigem PostgreSQL/Redis, incluindo o novo caso de conexão
reutilizada; foram executados no ambiente adequado, não validados por skip.
O único skip PostgreSQL é exclusivo de SQLite. Erros SQL/RLS/55P03 dos probes
negativos anteriores são esperados, não falhas finais. Não houve falha de
suíte ou correção posterior à primeira execução deste incremento.

Não executados localmente: Laravel/PostgreSQL/Redis, por ausência das
ferramentas PHP/Composer/Docker. Resultados reais acima são da CI sintética,
sem dados de clientes ou contato com VPS. A inclusão deste registro altera
somente documentação; verificar novamente os checks do SHA final na PR após
esse commit. Não é declaração de revisão humana, aceite CORE/SEG ou release.
