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
- **F1-02**: [PR #4](https://github.com/rafhael100/imagine-crm/pull/4), commit `3ec4cfa0621140056eab274826a639aba125e0a6`, integrado somente em `dev/fast-track-crm-2026-11-04`.
- **F1-03**: [PR #6](https://github.com/rafhael100/imagine-crm/pull/6), commit `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`, integrado somente na mesma branch. Núcleo de credenciais e sessões experimentais, MFA TOTP/recovery, controle de revogação concorrente e migrations de autenticação apenas em `database/experimental-migrations/auth`; **nenhuma autenticação pública ou produção habilitada**.
- `main` permanece independente; [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) continua **DRAFT**. Não efetuar merge em `main`, deploy nem abrir contas reais.

**F1-04 — hardening IAM para laboratório:** a [Issue #7](https://github.com/rafhael100/imagine-crm/issues/7) já possui a [PR #8](https://github.com/rafhael100/imagine-crm/pull/8) **DRAFT** na branch `codex/f1-04-iam-hardening-audit`, dirigida a `dev/fast-track-crm-2026-11-04`. O escopo abrange provisionamento interno sintético e prova de autoridade, auditoria sem segredos, antiabuso/rate limiting, compensação entre SQL/sessão/auditoria e ADR de MFA resistente a phishing. **Próximo passo é revisar a PR #8 e seus checks, não abrir outra PR ou fazer merge automático**. Os dois checks Laravel/SQLite e PostgreSQL/RLS foram concluídos com sucesso na HEAD observada `63b007f0048c09d3d794077b6ca1ae437dd89dcd`; revisão humana e autorização de merge continuam pendentes. Se o SHA mudar, revalidar checks.

**Gates abertos:** CORE04-D04..D10, CORE01/02, SEG-01/02 e demais CORE/ARQ/INT/FIN/CON/LGPD. IAM real, verificação de titularidade, recovery produtivo, autorização comercial, identidade técnica, auditoria/retencão/antifraude e sessões distribuídas necessitam revisão/aceite específico. `DenyByDefaultAuthorizer` deve continuar negando todas as ações de negócio; não confiar na custom GUC `app.tenant_id` como prova de autenticação.

## Provas de execução
- F1-03, head `00c965df`: [SQLite/Laravel](https://github.com/rafhael100/imagine-crm/actions/runs/41945564965) **67 testes aprovados, 24 skips**, e [PostgreSQL/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/41945564986) **90 testes aprovados, 1 skip**.
- Pós-merge de F1-03, commit `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`: [CI SQLite](https://github.com/rafhael100/imagine-crm/actions/runs/41946853493) e [CI PostgreSQL](https://github.com/rafhael100/imagine-crm/actions/runs/41946853510) concluídas com sucesso.
- Estas evidências cobrem **apenas o cenário sintético experimental**, e não validam produção nem encerram pendências. Após alterações em `AGENTS.md`, confirmar novamente os checks da HEAD.
