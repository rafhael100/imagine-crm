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

**Leia [STATUS-ATUAL.md](STATUS-ATUAL.md)** e confirme no GitHub seu estado antes de iniciar trabalhos: o resumo pode defasar.

- **F1-02** — [PR #4](https://github.com/rafhael100/imagine-crm/pull/4), merge apenas em DEV, SHA `3ec4cfa0621140056eab274826a639aba125e0a6`.
- **F1-03** — [PR #6](https://github.com/rafhael100/imagine-crm/pull/6), merge apenas em DEV, SHA `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`.
- **F1-04** — [PR #8](https://github.com/rafhael100/imagine-crm/pull/8), merge **somente em DEV**, SHA `407a2ead654864cd457436080999736a1186e219`. Controles experimentais de IAM, provisionamento sintético, auditoria minimizada, antiabuso Redis e compensação. Ainda sem login público, mandatos reais, autorização de dados comerciais, VPS ou produção.
- `main` ainda é independente; [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) continua **DRAFT** e não deve ser integrado sem autorização exclusiva.

**Próxima tarefa de Codex: F1-05**, conforme [Issue #9](https://github.com/rafhael100/imagine-crm/issues/9) — contratos de autorização por recurso/ação/campo/escopo e verificações negativas de efeitos protegidos. Partir da **HEAD atual de `dev/fast-track-crm-2026-11-04`**, criar branch `codex/f1-05-authorization-contracts`, abrir **PR DRAFT direcionado à DEV**, testar, não fazer merge automaticamente.

### Restrições reforçadas na F1-05
- As diretrizes CORE02-D04/D05 têm aceite documental preliminar, porém as pendências seguem abertas. CORE02-D06 permanece proposta não totalmente aprovada; conferir todas as versões e decisões mais recentes, inclusive de `main`.
- A conciliação F1-02 menciona **P1+P2 como referência documental escopada**, não como `ALLOW`, grant, implementação final de precedência, entitlement ou direito a dados.
- **Nunca trocar `DenyByDefaultAuthorizer` por política que permita operação real**. Não liberar rotas públicas, execução de jobs reais, exportação de clientes, lead/contact CRUD, IA, n8n ou dados pessoais sem decisões e gates próprios.
- Nenhum campo, papel, recurso, escopo, módulo, plano ou canal criado em teste autoriza capacidades comerciais reais; os exemplos da Issue #9 são sintéticos e fechados.
- Preservar `identity.experimental=false` por padrão, papel IAM sem privilégios de negócio, PostgreSQL RLS e provas de membership/identidade/sessão. Todos os CORE/SEG/ARQ/INT/FIN/CON/LGPD permanecem abertos.

## Provas de execução

- F1-04 antes do merge: [Laravel/SQLite run 42027807893](https://github.com/rafhael100/imagine-crm/actions/runs/42027807893) — **77 passed / 30 skipped**; [PostgreSQL/Redis run 42027808128](https://github.com/rafhael100/imagine-crm/actions/runs/42027808128) — **106 passed / 1 skipped**.
- Após merge F1-04 em DEV, SHA `407a2ead654864cd457436080999736a1186e219`: [Laravel run 42036309101](https://github.com/rafhael100/imagine-crm/actions/runs/42036309101) e [PostgreSQL/Redis run 42036309165](https://github.com/rafhael100/imagine-crm/actions/runs/42036309165) `success`.
- Estas provas verificam **somente ambientes sintéticos**. Não encerram 67 IDs CORE nem liberação de produção. Reexecutar CI e conferir o SHA exato após novos commits.
