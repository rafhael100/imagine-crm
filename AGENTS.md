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

**Conferir sempre [STATUS-ATUAL.md](STATUS-ATUAL.md), Issues, PRs e checks do GitHub antes de iniciar atividades.** Esse status é uma fotografia, não aprovação dos gates nem autorização para implantar.

- **F1-02**: [PR #4](https://github.com/rafhael100/imagine-crm/pull/4), merge somente DEV `3ec4cfa0621140056eab274826a639aba125e0a6`.
- **F1-03**: [PR #6](https://github.com/rafhael100/imagine-crm/pull/6), merge somente DEV `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`.
- **F1-04**: [PR #8](https://github.com/rafhael100/imagine-crm/pull/8), merge somente DEV `407a2ead654864cd457436080999736a1186e219`.
- **F1-05**: [PR #10](https://github.com/rafhael100/imagine-crm/pull/10), merge somente DEV `9cc5ae4214e07903b2b69d9d40541c61f395d4bf`; contratos de autorização, deny-only.
- **F1-06**: [PR #12](https://github.com/rafhael100/imagine-crm/pull/12), merge somente DEV `691f8c0e508fabae5d3560cca044bf280d2fcfb5`; classes A–E, nenhuma execução assíncrona real.
- **F1-07**: [PR #14](https://github.com/rafhael100/imagine-crm/pull/14), merge somente DEV `71cecea7d53fd5f2608d4b0b96d685c696a144ed`; diagnósticos sanitizados e reconciliação inerte.
- **F1-08**: [PR #16](https://github.com/rafhael100/imagine-crm/pull/16), merge **exclusivamente na DEV** `2923cc57afe240f8aa6024d7028a75bb8f8b54ef`; ledger PostgreSQL e inbox/outbox **somente laboratório**, em migração experimental opt-in e fixtures `tests/`. Não equivale a fila, mensageria, privilégio, autorização, persistência produtiva ou efeito de negócio.
- **`main`** continua isolada e inalterada: [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) permanece DRAFT, não integrar sem autorização própria. Sem deploy na VPS/produção.

**Próximo incremento de Codex: [Issue #17 — F1-09](https://github.com/rafhael100/imagine-crm/issues/17), integridade, recuperação e reconciliação apenas consultiva do ledger experimental.** Seu alvo é demonstrar detecção segura de lacunas e divergências após falhas/snapshots sintéticos, não restaurar ou operar clientes.

### Execução permitida para F1-09
1. Partir **somente da HEAD atual de `dev/fast-track-crm-2026-11-04`**; validar checks; criar `codex/f1-09-async-ledger-integrity-recovery`, abrir **PR DRAFT apontando para DEV**. Não usar `main`, não fazer merge ou deploy automático.
2. Ler Issue #17, ADRs F1-05..F1-08, CORE03/06/09, AUT-P0, INT-R01/09/10/12, CT-03/04, SEG-01/02, LGPD e decisões adicionais. CORE03-D07 e AUT-P04 são **gates abertos**: não inferir schema/filas/retentativas, owner, prazos ou confirmação oficial.
3. Criar **somente em `tests/Fixtures` e testes/ADR** verificador consultivo read-only de invariantes do ledger F1-08, tentativas e inbox/outbox. Detectar pares ausentes, registros inconsistentes, falha/rollback, versões/ordem causal conflitantes, cross-tenant e casos que exigem validação do módulo owner. Não alterar automaticamente o estado ou produzir autorização por ter lido o ledger.
4. Ensaiar restauração/comparação apenas em **schema descartável de CI** ou snapshot fictício seguro, sem `pg_dump`/PITR de servidor real e sem afirmar RPO/RTO. Migrações, se necessárias, ficam exclusivamente em `database/experimental-migrations/async`, opt-in por ambiente local/testing + `identity.experimental=true`, nunca no `migrate` padrão.
5. Preservar PostgreSQL 16 com FORCE RLS, conexão de privilégio mínimo, isolamento por tenant e GUC `app.tenant_id` transacional **não autenticadora**; recusar pool contaminado. Sem efeitos mesmo se auditoria/snapshot apontar estado aparentemente concluído.
6. Cobrir testes negativos: lacunas/inconsistências, tentativas concorrentes, rollback, host/reconexão simulados, acesso de tenant cruzado, papel privilegiado negado, expiração, credencial/membership revogados, falha de DB, sinal sanitizado e **nenhum** Bus/Queue/Event/Mail/HTTP/n8n/webhook/worker disparado.
7. Executar as suítes completas Laravel/SQLite e PostgreSQL 16/RLS/Redis na HEAD FINAL do PR, registrar links, pass/skip/asserts e justificar a plataforma dos skips. Sem acesso a VPS ou contas reais.

### Bloqueios obrigatoriamente abertos
- Todos os **67 IDs técnicos CORE**, CORE01..09 (CORE03-D04..D10, CORE09-D04..D10, CORE02-D04..D10), AUT-P01..14, SEG-01/02, ARQ/INT/FIN/CON/LGPD, G01..G06. As bases CORE03/09-D01..D03 aceitas são documentais, **não autorizam trabalho produtivo**.
- O único autorizador runtime continua `DenyByDefaultAuthorizer`; jamais implementar `ALLOW`, credencial de serviço produtiva, entrega real, customer CRUD, acesso de cliente, planos, faturamento, suporte, novos custos, processamento real, dados pessoais, fila/worker, cache de permissão ou n8n.
- Outbox/inbox do laboratório não constituem Central de Eventos, motor nativo de automações, backup/restore produtivo, DLQ ou reconciliação oficial. **Não transplantar os prazos IN-127 Sites↔CRM para o CRM**.
- CI verde não encerra gates. Nunca alterar `main`, integrar PR #2 ou implantar na VPS sem autorização distinta e homologações necessárias.

## Provas de execução
- F1-08 HEAD pré-merge `49bd5b661ddee3fec434cd550530c07c1430e878`: [Laravel/SQLite 42119017856](https://github.com/rafhael100/imagine-crm/actions/runs/42119017856) **340 passed / 49 skipped, 1366 asserts**; [PostgreSQL/RLS/Redis 42119018061](https://github.com/rafhael100/imagine-crm/actions/runs/42119018061) **388 passed / 1 skipped, 1827 asserts**.
- Merge F1-08 somente na DEV `2923cc57afe240f8aa6024d7028a75bb8f8b54ef`: [Laravel 42122175413](https://github.com/rafhael100/imagine-crm/actions/runs/42122175413) e [PostgreSQL/RLS/Redis 42122175636](https://github.com/rafhael100/imagine-crm/actions/runs/42122175636) **success**.
- Novos commits documentais podem acionar novos workflows. Confira sempre checks do SHA atual. Nenhuma dessas provas constitui aceite de CORE/SEG ou release.
