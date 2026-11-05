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

**Ler [STATUS-ATUAL.md](STATUS-ATUAL.md), Issues, PRs e GitHub Actions reais antes de executar.** O histórico é documental, não substitui a autorização expressa para merge, `main` ou VPS.

- **F1-02** [PR #4](https://github.com/rafhael100/imagine-crm/pull/4) — merge apenas na DEV `3ec4cfa0621140056eab274826a639aba125e0a6`.
- **F1-03** [PR #6](https://github.com/rafhael100/imagine-crm/pull/6) — merge apenas na DEV `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`.
- **F1-04** [PR #8](https://github.com/rafhael100/imagine-crm/pull/8) — merge apenas na DEV `407a2ead654864cd457436080999736a1186e219`.
- **F1-05** [PR #10](https://github.com/rafhael100/imagine-crm/pull/10) — merge apenas na DEV `9cc5ae4214e07903b2b69d9d40541c61f395d4bf`; autorização recurso/ação/campo/escopo, deny-only.
- **F1-06** [PR #12](https://github.com/rafhael100/imagine-crm/pull/12) — merge apenas na DEV `691f8c0e508fabae5d3560cca044bf280d2fcfb5`; contratos assíncronos A–E, sem jobs reais.
- **F1-07** [PR #14](https://github.com/rafhael100/imagine-crm/pull/14) — merge apenas na DEV `71cecea7d53fd5f2608d4b0b96d685c696a144ed`; observabilidade e diagnósticos internos sintéticos.
- **F1-08** [PR #16](https://github.com/rafhael100/imagine-crm/pull/16) — merge apenas na DEV `2923cc57afe240f8aa6024d7028a75bb8f8b54ef`; ledger/idempotência e inbox/outbox inertes com migração opt-in PostgreSQL/RLS em laboratório.
- **F1-09** [PR #18](https://github.com/rafhael100/imagine-crm/pull/18) — merge **somente na DEV** `af4cd127655572f9ce7327b4f52dca36a5e0a864`; integridade, restauração sintética e reconciliação **read-only/consultiva**, sem privilégio ou efeito.
- **`main` continua intocada por estes merges**; [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) segue **DRAFT** para `main`; VPS/produção sem deploy.

**Próximo incremento de Codex: [Issue #19 — F1-10](https://github.com/rafhael100/imagine-crm/issues/19), consolidação da baseline de segurança e matriz de gates para planejar implementação funcional.** Objetivo: tornar verificáveis as invariantes e as pendências F1-02..F1-09 antes de decidir uma próxima fase; **não implementar `ALLOW`, autenticação pública ou módulos comerciais.**

### Execução permitida para F1-10
1. Criar `codex/f1-10-fast-track-security-baseline` da **HEAD atual de `dev/fast-track-crm-2026-11-04`** depois de conferir os checks; **nunca partir de `main`**. Abrir PR **DRAFT somente para DEV**, sem merge automático.
2. Ler Issue #19 e ADRs F1-01..F1-09, `STATUS-ATUAL.md`, CORE01..09, SEG-01/02, AUT-P01..14, INT-R01/09/10/12, CT-03/04, ARQ/FIN/CON/LGPD. Registrar matriz rastreável: evidência de laboratório ≠ aprovação documental ≠ homologação física ≠ recurso comercial pronto.
3. Implementar somente verificadores automatizados de **não regressão** em `tests/`, `scripts/ci/` ou CI que demonstrem: `DenyByDefaultAuthorizer` exclusivo; nenhum `ALLOW` ativo; `identity.experimental=false` padrão; migrações auth/async experimentais opt-in fora de `migrate` padrão; sem binding das fixtures sintéticas a routes/providers/jobs/webhooks; RLS/roles mínimos, grants controlados e tenant isolado; nenhum efeito de Bus/Queue/Mail/HTTP em F1-06..F1-09.
4. Preferir asserções semânticas runtime/migrate/ACL e testes negativos aos padrões de texto frágeis. **Não** alterar comportamento produtivo ou normalizar concessões fictícias. Verificadores só exigem ambientes de CI local/testing, PostgreSQL 16 e Redis efêmeros.
5. Documentar `docs/desenvolvimento/F1-10-consolidacao-gates-seguranca.md` com inventário, mapa de dependências e ordem proposta para a próxima implementação **submetida à aprovação humana**, incluindo 67 IDs CORE, CORE03/09-D04..D10, CORE02-D04..D10, SEG, AUT-P04 e owner/gates. **Não declarar gates encerrados.**
6. Executar CI Laravel/SQLite e PostgreSQL/RLS/Redis completos no SHA FINAL do PR; anexar links, contagens de passed/skipped/assertions, riscos e contraprovas de falha fechada. Todas as regressões F1-02..F1-09 preservadas.
7. Não realizar merge, ativar produção, usar dados reais, acessar VPS, criar endpoints, webhook, motor de cliente ou despesas; não editar decisões normativas em `docs/planejamento` sem autorização expressa.

### Bloqueios abertos e precedência
- **67 IDs técnicos CORE**, CORE01..09 (incluindo CORE02-D04..D10, CORE03/CORE09-D04..D10), AUT-P01..14, SEG-01/02, ARQ/INT/FIN/CON/LGPD e G01..G06 permanecem **abertos**. CORE03/09-D01..D03 são bases documentais; não representam implementação/homologação.
- `DenyByDefaultAuthorizer` permanece o **único autorizador de runtime**. `app.tenant_id`/GUC não autentica; ledger/integridade/restore e observações de laboratório não provam autoridade, não substituem owner nem garantem efeitos idempotentes de negócio.
- Motor nativo de automações e n8n interno Imagine são decisões arquiteturais distintas, sujeitos a seus gates. Não transmitir efeitos, publicar inbox/outbox, ativar jobs, criar grants, importar/exportar dados reais, gerir cobrança, liberar cliente, fazer backup produtivo ou generalizar os prazos IN-127 de Sites↔CRM.
- CI sintética não demonstra readiness de produção; aprovações de PR devem se limitar à DEV. `main`/PR #2/VPS dependem de autorização explícita separada e homologação.

## Provas de execução
- F1-09 HEAD pré-merge `6c8dd1dbe9b0c5ff78402ff7d186cae571825536`: [Laravel/SQLite 42126536166](https://github.com/rafhael100/imagine-crm/actions/runs/42126536166), **366 passed / 63 skipped / 1.574 assertions**; [PostgreSQL/RLS/Redis 42126536165](https://github.com/rafhael100/imagine-crm/actions/runs/42126536165), **428 passed / 1 skipped / 2.250 assertions**.
- F1-09 merge exclusivamente DEV `af4cd127655572f9ce7327b4f52dca36a5e0a864`: [Laravel 42130265061](https://github.com/rafhael100/imagine-crm/actions/runs/42130265061) e [PostgreSQL/RLS/Redis 42130265271](https://github.com/rafhael100/imagine-crm/actions/runs/42130265271), ambos **success**.
- Novos commits documentais iniciam novos checks. Confirmar SHA e execução antes de trabalhar; nenhum resultado acima autoriza `main` ou VPS.
