# AGENTS.md — CRM Imagine (instruções para agentes de programação)

Este repositório é o **CRM Imagine**, SaaS multiempresa Laravel 13 / PostgreSQL 16. As decisões normativas estão em `docs/planejamento/`; código experimental está em `dev/fast-track-crm-2026-11-04`. Consulte o PR #2 e as fontes antes de alterar regras.

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

**F1-04 — hardening IAM para laboratório:** implementar a [Issue #7](https://github.com/rafhael100/imagine-crm/issues/7), incluindo provisionamento interno sintético e prova de autoridade, auditoria sem segredos, antifraude/rate limiting, compensação de falhas entre SQL/sessão/auditoria e ADR para MFA resistente a phishing. Criar `codex/f1-04-iam-hardening-audit` **a partir da HEAD atual de `dev/fast-track-crm-2026-11-04`**, abrir PR em rascunho contra essa branch, executar CI PostgreSQL/SQLite, **sem merge automático**.

**Gates abertos:** CORE04-D04..D10, CORE01/02, SEG-01/02 e demais CORE/ARQ/INT/FIN/CON/LGPD. IAM real, verificação de titularidade, recovery produtivo, autorização comercial, identidade técnica, auditoria/retencão/antifraude e sessões distribuídas necessitam revisão/aceite específico. `DenyByDefaultAuthorizer` deve continuar negando todas as ações de negócio; não confiar na custom GUC `app.tenant_id` como prova de autenticação.

## Provas de execução
- F1-03, head `00c965df`: [SQLite/Laravel](https://github.com/rafhael100/imagine-crm/actions/runs/41945564965) **67 testes aprovados, 24 skips**, e [PostgreSQL/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/41945564986) **90 testes aprovados, 1 skip**.
- Pós-merge de F1-03, commit `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`: [CI SQLite](https://github.com/rafhael100/imagine-crm/actions/runs/41946853493) e [CI PostgreSQL](https://github.com/rafhael100/imagine-crm/actions/runs/41946853510) concluídas com sucesso.
- Estas evidências cobrem **apenas o cenário sintético experimental**, e não validam produção nem encerram pendências. Após alterações em `AGENTS.md`, confirmar novamente os checks da HEAD.
