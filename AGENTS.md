# AGENTS.md — CRM Imagine (instruções para agentes de programação)

Este repositório é o **CRM Imagine**, SaaS multiempresa Laravel 13 / PostgreSQL 16. As decisões normativas estão em `docs/planejamento/`; código experimental está em `dev/fast-track-crm-2026-11-04`. Consulte o PR #2 e as fontes antes de alterar regras.

## Orientação e estado corrente do projeto
- **Leia primeiro [STATUS-ATUAL.md](STATUS-ATUAL.md)** e confira, no GitHub, as Issues, Pull Requests, branches e checks citados antes de aceitar uma nova tarefa. O STATUS é um **resumo histórico datado**, sujeito a defasagem; o estado real da PR/CI prevalece.
- **Este `AGENTS.md` define normas de trabalho**, não é autorização permanente para iniciar qualquer tarefa. As ordens de implementação são as Issues e as autorizações específicas da direção.
- Na entrega, informe: issue/PR, branch base, SHA do commit, arquivos alterados, testes executados (com URL dos logs), testes não executados, riscos, gates ainda abertos e sugestão para o próximo passo. Não altere status para “aprovado/concluído” sem revisão ou aceite verificável.
- Depois de uma etapa oficialmente aceita, proponha atualização do `STATUS-ATUAL.md` por commit revisável, sem modificar documentação normativa ou avançar para a etapa seguinte sem autorização. **Nunca** fazer merge/deploy automaticamente por causa dessa atualização.

## Fluxo de trabalho
- **Branch base dos próximos incrementos:** `dev/fast-track-crm-2026-11-04`, NÃO `main`. Para tarefas de Codex, crie uma branch derivada da base e abra **PR em rascunho direcionado à branch de desenvolvimento**, com referência à issue. Não faça merge automaticamente.
- Execute apenas desenvolvimento local/CI com **dados sintéticos**. Não provisionar VPS, conectar ambientes de clientes, pagar serviços, emitir mensagens externas ou realizar implantação.
- Commits pequenos e revisáveis, com testes negativos e documentação dos riscos. Não alterar `composer.lock` sem mudança justificada de dependência.
- Utilize `php artisan test` e os workflows `.github/workflows/ci-f0.yml` e `.github/workflows/ci-postgresql-rls.yml`. Não declare testes como aprovados se não houver logs de execução.
- **Não baixar regras comerciais por suposição:** preços, planos, créditos IA, cortesia, cobrança e entitlement dependem de proprietários e especificações normativas.

## Limites de segurança
- **Identidade global é distinta de organização (tenant) e membership.** CNPJ/e-mail ou parâmetro `tenant_id` recebido do usuário não são autenticação nem autorização.
- `TenantContext`, `MembershipSnapshot` e `PrincipalReference` já existentes são **representações**, não evidências validadas. Um objeto construído em memória não pode, por si, conceder privilégio.
- `DenyByDefaultAuthorizer` deve permanecer negando todos os efeitos de negócio até a política por recurso/ação/campo e contratos CORE/SEG estarem revisados. Não substituir por `allow` genérico nem inventar política P1–P4 de grants/denies.
- O PostgreSQL RLS é **defesa adicional**. `app.tenant_id` é um parâmetro configurável, portanto **não identifica um usuário autenticado**. Resolver tenant/membership em backend confiável, usar contexto transacional, evitar injeção e garantir limpeza em pools/jobs. Sem roles `SUPERUSER`/`BYPASSRLS` na execução cotidiana.
- Não ampliar direitos entre organizações, equipes, canais, planos, agentes IA ou usuários de suporte. Nunca permitir que admin global acesse dados comerciais de tenant sem mandato.
- Não adicionar rotas públicas de autenticação, leads, exportação ou anexos sem avaliação específica e testes negativos de segurança.
- Segredos somente por mecanismos apropriados e variáveis protegidas em ambiente autorizado; **nunca** versionar segredos, dumps reais nem tokens.

## Fonte e pendências obrigatórias
- [DEC-FASTTRACK-01](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md) autoriza **desenvolvimento controlado em branch isolada**, não entrega aos clientes nem implantação.
- [F1-01](docs/desenvolvimento/F1-01-modelo-IAM-RLS-evidencias-CI-2026-11-04.md) registra a prova de conceito de identities/organizations/memberships e RLS, com limitações explícitas.
- [F0-03](docs/desenvolvimento/F0-03-CONTRATOS-IAM-E-MULTITENANCY.md), [CORE01-D04..D07](docs/planejamento/CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md), [CORE02-D05](docs/planejamento/CRM-CORE-P0-ETAPA2-CORE02-D05-combinacao-perfis-negacoes-delegacao-proposta-v0.1.md), [DEV-GATE-00](docs/planejamento/CRM-DEV-GATE-00-checklist-unico-liberacao-desenvolvimento-v0.1.md) e SEG-01/02 regem as dependências.
- **67 pendências técnicas CORE** e gates SEG/ARQ/INT/FIN/CON permanecem em aberto quanto a implementação, revisão e aceite. Não marcar como fechados por CI verde em prova parcial.

## Incrementos integrados e próxima etapa prioritária

**Consultar [STATUS-ATUAL.md](STATUS-ATUAL.md), Issues, PRs e GitHub Actions atuais antes de agir.** STATUS é registro datado; só as autorizações expressas da direção liberam operações específicas.

- **F1-02**: [PR #4](https://github.com/rafhael100/imagine-crm/pull/4), DEV merge `3ec4cfa0621140056eab274826a639aba125e0a6`.
- **F1-03**: [PR #6](https://github.com/rafhael100/imagine-crm/pull/6), DEV merge `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`.
- **F1-04**: [PR #8](https://github.com/rafhael100/imagine-crm/pull/8), DEV merge `407a2ead654864cd457436080999736a1186e219`.
- **F1-05**: [PR #10](https://github.com/rafhael100/imagine-crm/pull/10), DEV merge `9cc5ae4214e07903b2b69d9d40541c61f395d4bf`. Contratos de intenção e autorização **deny-only**.
- **F1-06**: [PR #12](https://github.com/rafhael100/imagine-crm/pull/12), DEV merge `691f8c0e508fabae5d3560cca044bf280d2fcfb5`. Classes A–E assíncronas sem execução real.
- **F1-07**: [PR #14](https://github.com/rafhael100/imagine-crm/pull/14), DEV merge **`71cecea7d53fd5f2608d4b0b96d685c696a144ed`**. Diagnósticos e observações sanitizados, reconciliação sintética sem efeito, `DenyByDefaultAuthorizer` preservado.
- **`main` não foi alterada por esses merges**; [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) permanece **DRAFT**, sem merge, e a VPS não recebeu deploy.

**Próximo incremento do Codex:** [Issue #15 — F1-08](https://github.com/rafhael100/imagine-crm/issues/15), **ledger experimental de idempotência e inbox/outbox inertes**. Objetivo: provar persistência local, locks/constraints e reconciliação sem enfileirar, transmitir, autenticar serviços ou autorizar negócios.

### Execução permitida para F1-08
1. Partir da **HEAD atual da `dev/fast-track-crm-2026-11-04`**, nunca de `main`. Conferir CI e ler Issue #15, ADRs F1-05/F1-06/F1-07, CORE03, AUT-P0 e INT CT-03/04. Criar `codex/f1-08-async-idempotency-ledger` e abrir **PR DRAFT exclusivamente para DEV**, sem merge automático.
2. Se houver schema SQL, implementar **somente migrações experimentais opt-in** fora do diretório de migrações de produção (`database/experimental-migrations/async`), protegidas por `identity.experimental=true` e ambiente local/testing; PostgreSQL/RLS testado com roles mínimos, conexões sem `app.tenant_id` herdado e isolamento por tenant. Se a segurança não for demonstrável, limitar o recorte a fixtures `tests/`.
3. Dados sintéticos e mínimos: UUIDs de laboratório, versões, digests e identificadores de tentativa, sem payload comercial, CPF, telefone, e-mail, token, segredo ou PII. Evidência de `first_seen/duplicate/conflict/reconciliation_required` **não é admissão, entrega, efeito ou confirmação oficial**.
4. Negar efeitos mesmo com ledger persistido: sem `ALLOW`, grant de negócio, Publisher/Consumer, Queue, Job, webhook, HTTP, Mail, n8n, credencial, integração, sincronização de cliente, REST pública, cofre ou gastos. `DenyByDefaultAuthorizer` permanece o único autorizador real.
5. Testar PostgreSQL 16/RLS e SQLite onde adequado, incluindo idempotência e versões por tenant, locks/concorrência em conexões distintas, rollback e recuperação, replay, falha de conexão, rollback de migração e guard de produção; registrar links, aprovados/skips/asserts e riscos no PR. Preservar regressões F1-02..F1-07, sem tocar `main` ou VPS.
6. Não duplicar os contratos do motor nativo do CRM/AUT, da Central de Eventos/Filas, INT-R01/INT-09/10/12 nem os prazos da IN-127 para Sites↔CRM. Documentar dono do efeito e autorizações ainda ausentes.

### Gates obrigatoriamente abertos
- **67 IDs técnicos CORE** e CORE01..CORE09, especialmente CORE03-D04..D10, CORE09-D04..D10 e CORE02-D04..D10; SEG-01/02, ARQ/INT/FIN/CON/LGPD e G01..G06 continuam **abertos**. CORE03/09-D01..D03 são somente base documental aceita.
- `app.tenant_id` é marcador de contexto, não credencial/autorização. A cadeia de identidade humana e técnico/serviço, membership, RLS, mandatos, consentimentos, owner e entitlements deve ser homologada antes de permitir efeitos.
- n8n continua restrito aos serviços internos Imagine conforme avaliação de licença; motor nativo para automações do cliente é trabalho futuro separado. Nenhum worker real, fila/roteamento, ingestão real de leads, endpoint público, sessão de cliente, acesso a VPS ou dados reais.
- Qualquer próximo merge exige nova autorização expressa; CI verde em laboratório não habilita `main`, produção ou fechamento de CORE/SEG.

## Provas de execução
- F1-07 HEAD pré-merge `c4255fd728ff29f68a8ca3668eef8861e633134d`: [Laravel/SQLite run 42089372399](https://github.com/rafhael100/imagine-crm/actions/runs/42089372399) **337 passed / 36 skipped**, [PostgreSQL 16/Redis run 42089372691](https://github.com/rafhael100/imagine-crm/actions/runs/42089372691) **372 passed / 1 skipped**.
- F1-07 merge DEV `71cecea7d53fd5f2608d4b0b96d685c696a144ed`: [Laravel run 42092229368](https://github.com/rafhael100/imagine-crm/actions/runs/42092229368) e [PostgreSQL/RLS/Redis run 42092229274](https://github.com/rafhael100/imagine-crm/actions/runs/42092229274) **success**.
- Documentação pode criar novos commits/checks; conferir resultados da HEAD antes de programar e em cada revisão. Nenhuma prova acima homologa política produtiva.
