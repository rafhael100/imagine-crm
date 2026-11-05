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

**Consultar [STATUS-ATUAL.md](STATUS-ATUAL.md), Issues, PRs e Actions atuais antes de agir.** O STATUS é um registro datado; o GitHub prevalece.

- **F1-02**: [PR #4](https://github.com/rafhael100/imagine-crm/pull/4), DEV merge `3ec4cfa0621140056eab274826a639aba125e0a6`.
- **F1-03**: [PR #6](https://github.com/rafhael100/imagine-crm/pull/6), DEV merge `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`.
- **F1-04**: [PR #8](https://github.com/rafhael100/imagine-crm/pull/8), DEV merge `407a2ead654864cd457436080999736a1186e219`.
- **F1-05**: [PR #10](https://github.com/rafhael100/imagine-crm/pull/10), DEV merge `9cc5ae4214e07903b2b69d9d40541c61f395d4bf`. Contratos de intenção e autorização de negócio somente **deny-by-default**.
- **F1-06**: [PR #12](https://github.com/rafhael100/imagine-crm/pull/12), DEV merge **`691f8c0e508fabae5d3560cca044bf280d2fcfb5`**. Contratos assíncronos por classe A–E para laboratório, sem execução de jobs/webhooks, grants, efeitos, filas ou credenciais reais.
- **`main`** não foi alterada por esses merges; [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) continua **DRAFT**, não realizar merge para `main` sem autorização específica.

**Próximo incremento de Codex:** [Issue #13 — F1-07](https://github.com/rafhael100/imagine-crm/issues/13): **observabilidade segura, classificação das falhas e reconciliação inerte da ponte assíncrona**, sempre em laboratório e deny-only.

Ao iniciar F1-07:
1. Usar a **HEAD atual** de `dev/fast-track-crm-2026-11-04` (nunca `main`); verificar checks da branch. Criar `codex/f1-07-async-observability-reconciliation` e abrir **PR DRAFT dirigido somente à DEV**.
2. Ler Issue #13, ADR F1-06, CORE03/06/09, INT-10/12, SEG-01/02, LGPD e documentação normativa. Preservar F1-02..F1-06.
3. Criar apenas contratos candidatos de telemetria/diagnóstico **sem vazamento de input, token, stack trace ou PII**, e testes sintéticos de negação, exceção técnica, rollback, reuso de pool, contextos A–E, idempotência e reconciliação. Qualquer sink permanece em **fixture de tests/**; nenhuma gravação/rota pública/worker ativo.
4. Não transformar `catch(Throwable)` em sucesso, nem inferir que sinal de auditoria significa comprovação de autoridade. Nenhum `ALLOW`, sem segundo autorizador, sem identidade técnica emitida, sem mensagem externa ou execução real.
5. Executar CI Laravel/SQLite e PostgreSQL 16/Redis no SHA final; incluir links, aprovados, skipped, asserts e riscos. Solicitar revisão humana antes de merge, **sem merge automático ou deploy na VPS**.

### Gates obrigatórios
- **67 IDs técnicos CORE**, CORE01..CORE09 (incluindo CORE03/09-D04..D10), SEG-01/02, ARQ/INT/FIN/CON/LGPD continuam abertos. CORE03/09-D01..D03 são apenas base documental.
- `DenyByDefaultAuthorizer` permanece o único autorizador runtime, sem `ALLOW` para cliente, suporte, IA, jobs, integrações, leads, exportações ou qualquer dado de negócio.
- n8n é reservado às operações internas controladas Imagine (dependente de avaliação de licenciamento); motor nativo CRM é planejamento independente, **não implementar nesta issue**.
- Não criar identidade técnica produtiva, cofre, credencial real, autenticação pública, conexão de cliente, autorização por cabeçalho/prompt, webhook, fila, outbox/inbox real, custo ou deploy.
- `app.tenant_id` (GUC de PostgreSQL) não é prova de autenticação e contexto contaminado em pool/worker deve falhar fechado.

## Provas de execução
- F1-06 head pré-merge `fd08a9212a43c01d9b8a66f1188a361e66b14774`: [Laravel/SQLite run 42070845197](https://github.com/rafhael100/imagine-crm/actions/runs/42070845197) **307 passed / 33 skipped** e [PostgreSQL/Redis run 42070845055](https://github.com/rafhael100/imagine-crm/actions/runs/42070845055) **339 passed / 1 skipped**.
- F1-06 após merge na DEV, `691f8c0e508fabae5d3560cca044bf280d2fcfb5`: [Laravel run 42073457091](https://github.com/rafhael100/imagine-crm/actions/runs/42073457091) e [PostgreSQL/Redis run 42073457093](https://github.com/rafhael100/imagine-crm/actions/runs/42073457093), ambos `success`.
- Todas as CI são sintéticas e limitadas; não significam aceites CORE/SEG ou release. Verificar novos checks após commits documentais.
