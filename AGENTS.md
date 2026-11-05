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

**Conferir [STATUS-ATUAL.md](STATUS-ATUAL.md) e GitHub diretamente antes de iniciar tarefas**: o STATUS é histórico, não homologação nem autorização permanente.

- **F1-02**: [PR #4](https://github.com/rafhael100/imagine-crm/pull/4), merge apenas na DEV `3ec4cfa0621140056eab274826a639aba125e0a6`.
- **F1-03**: [PR #6](https://github.com/rafhael100/imagine-crm/pull/6), merge apenas na DEV `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`.
- **F1-04**: [PR #8](https://github.com/rafhael100/imagine-crm/pull/8), merge apenas na DEV `407a2ead654864cd457436080999736a1186e219`.
- **F1-05**: [PR #10](https://github.com/rafhael100/imagine-crm/pull/10), merge apenas na DEV `9cc5ae4214e07903b2b69d9d40541c61f395d4bf`. Contratos `AuthorizationIntent`/`AuthorizationEnvelope`, campos/escopos e testes negativos; **autorização de negócio segue negada por padrão**.
- **`main`**: separada, [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) ainda DRAFT, sem merge nem deploy.

**Próxima tarefa: [Issue #11 — F1-06](https://github.com/rafhael100/imagine-crm/issues/11), contratos de autoridade assíncrona, jobs, eventos e webhooks (DENY-ONLY).** Ler CORE03, CORE09, INT, `docs/desenvolvimento/F1-05-authorization-contracts.md`, F1-04 e esta issue. Criar `codex/f1-06-async-authority-contracts` a partir da **HEAD atual de `dev/fast-track-crm-2026-11-04`**, abrir **PR DRAFT contra a DEV**, executar CI, sem merge automático, sem alterar `main` e sem acessar VPS.

### Restrições de F1-06
- As cinco classes semânticas CORE03 (A humano diferido, B processo oficial CRM, C automação nativa configurável do cliente, D ingresso externo, E operação técnica Imagine) têm **autoridades diferentes**. Não tratar ausência de sessão humana como liberdade de acesso; não transformar ator causal em principal técnico.
- `DenyByDefaultAuthorizer` é a política única em runtime; **nenhum `ALLOW`, grant comercial, role produtivo, autorização de suporte, rota pública, fila real, webhook real, emissão de token ou segredo/identidade técnica operacional**.
- Motor próprio Laravel/PostgreSQL/filas para automações do CRM, quando homologado; **n8n reservado para serviços internos controlados da Imagine**, sob avaliação de licenciamento. Não conectar nenhum dos dois nesta fase.
- Tipos/versionamento, correlação, idempotência, autorização por tenant/escopo e estado do módulo são requisitos; mensagens 2xx, sucesso técnico e GUC `app.tenant_id` não comprovam negócio, login ou permissão.
- Não copiar prazos comerciais de IN-127 (Sites↔CRM) como regras globais de jobs; requisitos de recepção de leads seguem próprios e pendentes de homologação.
- Todos os 67 IDs CORE, CORE01..CORE09, SEG-01/02, ARQ/INT/FIN/CON/LGPD e os critérios de homologação de serviços/cofre/mandatos **continuam abertos**. CORE03/CORE09-D01..D03 são somente base documental; D04..D10 abertas.

## Provas de execução
- Antes de integrar F1-05, SHA `312f2933`: [Laravel/SQLite](https://github.com/rafhael100/imagine-crm/actions/runs/42042627062) — **229 passed, 32 skipped**; [PostgreSQL/Redis](https://github.com/rafhael100/imagine-crm/actions/runs/42042626980) — **260 passed, 1 skipped**.
- Após merge F1-05 na DEV, SHA `9cc5ae4214e07903b2b69d9d40541c61f395d4bf`: [Laravel](https://github.com/rafhael100/imagine-crm/actions/runs/42052640678) e [PostgreSQL/RLS/Redis](https://github.com/rafhael100/imagine-crm/actions/runs/42052641333), ambos **success**.
- CI sintética verifica somente o recorte automatizado. Novos commits documentais exigem conferir checks da HEAD de novo; não encerram CORE/SEG ou liberam produção.
