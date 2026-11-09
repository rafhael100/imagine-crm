# AGENTS.md — CRM Imagine (instruções para agentes de programação)

Este repositório é o **CRM Imagine**, SaaS multiempresa Laravel 13 / PostgreSQL 16. As decisões normativas estão em `docs/planejamento/`; código experimental está em `dev/fast-track-crm-2026-11-04`. Consulte o PR #2 e as fontes antes de alterar regras.

## Orientação e estado corrente do projeto

**Atualização operacional em 09/11/2026 (não altera contratos normativos):** a Direção reiterou a autorização para concluir incrementos técnicos **inertes, documentais ou exclusivamente CI** com dados sintéticos, em branches filhas da DEV, sem solicitar aprovações rotineiras. Esses lotes podem ter **merge controlado exclusivamente na DEV**, desde que a HEAD e a base sejam rechecadas, o diff seja integralmente revisado, ambos os workflows estejam verdes na SHA exata e o risco não envolva migrations operacionais, grants, dados reais, APIs públicas, custos, produção ou `main`. **Não executar merges automáticos por simples conclusão de Codex/CI.**

A Direção assumiu a **responsabilidade decisória** pela pauta SEG/DBA ([Issue #27](https://github.com/rafhael100/imagine-crm/issues/27)), mas isso **não equivale a parecer técnico independente ou aceite P0/P1 itemizado**. O [PR #49](https://github.com/rafhael100/imagine-crm/pull/49) continua **DRAFT/documental, sem merge**, aguardando revisão competente. Consultar [STATUS-ATUAL.md](STATUS-ATUAL.md) e o GitHub para as HEADs reais; não tomar SHAs históricos como autoridade.

**CI de PostgreSQL:** [SEC-CI-02 / PR #55](https://github.com/rafhael100/imagine-crm/pull/55) integrado somente DEV, preflight read-only de serviço PostgreSQL efêmero antes das migrations preexistentes. **Push pós-merge** [#42564240499](https://github.com/rafhael100/imagine-crm/actions/runs/42564240499) e [F0 #42564240500](https://github.com/rafhael100/imagine-crm/actions/runs/42564240500) verdes. Isso mitiga o achado específico de trigger **SEGDBA-06**, mas não resolve controles destrutivos, identidade/autorização ou parecer independente.

- **Leia primeiro [STATUS-ATUAL.md](STATUS-ATUAL.md)** e confira, no GitHub, as Issues, Pull Requests, branches e checks citados antes de aceitar uma nova tarefa. O STATUS é um **resumo histórico datado**, sujeito a defasagem; o estado real da PR/CI prevalece.
- **Este `AGENTS.md` define normas de trabalho**, não é autorização permanente para iniciar qualquer tarefa. As ordens de implementação são as Issues e as autorizações específicas da direção.
- Na entrega, informe: issue/PR, branch base, SHA do commit, arquivos alterados, testes executados (com URL dos logs), testes não executados, riscos, gates ainda abertos e sugestão para o próximo passo. Não altere status para “aprovado/concluído” sem revisão ou aceite verificável.
- Depois de uma etapa tecnicamente validada, proponha atualização do `STATUS-ATUAL.md` por commit revisável. A autorização geral vigente permite **prosseguir diretamente para o próximo recorte inerte DEV** sem nova pergunta, mas não ultrapassar gates críticos. **Nunca** fazer merge/deploy automaticamente por causa dessa atualização.

## Fluxo de trabalho
- **Branch base dos próximos incrementos:** `dev/fast-track-crm-2026-11-04`, NÃO `main`. Para tarefas de Codex, crie uma branch derivada da base e abra **PR em rascunho direcionado à branch de desenvolvimento**, com referência à issue. **Merge só controlado em DEV** para pacotes inertes, após revisão completa de diff/SHAs/CI sob autorização vigente; **nunca** disparado automaticamente pela execução de Codex. PRs críticos, documentações condicionadas a parecer e branches para `main` não são incluídos nessa autonomia.
- Execute apenas desenvolvimento local/CI com **dados sintéticos**. Não provisionar VPS, conectar ambientes de clientes, pagar serviços, emitir mensagens externas ou realizar implantação.
- Commits pequenos e revisáveis, com testes negativos e documentação dos riscos. Não alterar `composer.lock` sem mudança justificada de dependência.
- Utilize `php artisan test` e os workflows `.github/workflows/ci-f0.yml` e `.github/workflows/ci-postgresql-rls.yml`. Não declare testes como aprovados se não houver logs de execução.
- **Não baixar regras comerciais por suposição:** preços, planos, créditos IA, cortesia, cobrança e entitlement dependem de proprietários e especificações normativas.

## Limites de segurança
- **Identidade global é distinta de organização (tenant) e membership.** CNPJ/e-mail ou parâmetro `tenant_id` recebido do usuário não são autenticação nem autorização.
- `TenantContext`, `MembershipSnapshot` e `PrincipalReference` já existentes são **representações**, não evidências validadas. Um objeto construído em memória não pode, por si, conceder privilégio.
- `DenyByDefaultAuthorizer` deve permanecer negando todos os efeitos de negócio até a política por recurso/ação/campo e contratos CORE/SEG estarem revisados. Não substituir por `ALLOW` genérico: CORE02-D05 alternativa B (P1+P2 escopada) foi ratificada **documentalmente** em PR #21 ainda DRAFT, mas a política física, grants/denies e alçadas continuam **não homologados**.
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

**Antes de qualquer atividade, ler [STATUS-ATUAL.md](STATUS-ATUAL.md), os PRs/Issues e conferir checks da HEAD do GitHub.** Nenhum documento, CI verde ou merge DEV autoriza produção, `main`, dados reais ou permissões comerciais.

### Etapas já integradas SOMENTE na branch de desenvolvimento
- F1-02 [PR #4](https://github.com/rafhael100/imagine-crm/pull/4): `3ec4cfa0621140056eab274826a639aba125e0a6`.
- F1-03 [PR #6](https://github.com/rafhael100/imagine-crm/pull/6): `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10`.
- F1-04 [PR #8](https://github.com/rafhael100/imagine-crm/pull/8): `407a2ead654864cd457436080999736a1186e219`.
- F1-05 [PR #10](https://github.com/rafhael100/imagine-crm/pull/10): `9cc5ae4214e07903b2b69d9d40541c61f395d4bf`.
- F1-06 [PR #12](https://github.com/rafhael100/imagine-crm/pull/12): `691f8c0e508fabae5d3560cca044bf280d2fcfb5`.
- F1-07 [PR #14](https://github.com/rafhael100/imagine-crm/pull/14): `71cecea7d53fd5f2608d4b0b96d685c696a144ed`.
- F1-08 [PR #16](https://github.com/rafhael100/imagine-crm/pull/16): `2923cc57afe240f8aa6024d7028a75bb8f8b54ef`.
- F1-09 [PR #18](https://github.com/rafhael100/imagine-crm/pull/18): `af4cd127655572f9ce7327b4f52dca36a5e0a864`.
- **F1-10 [PR #20](https://github.com/rafhael100/imagine-crm/pull/20): `6e3d02f432e0e6e6e1c9322c2d286b035661c5c2`**, baseline de contenção e matriz dos 67 IDs CORE em [documento](docs/desenvolvimento/F1-10-consolidacao-gates-seguranca.md), ainda apenas experimental.

### Próxima etapa: DECISÃO HUMANA, não comando de Codex

[Issue #22 — FUNC-P0-01](https://github.com/rafhael100/imagine-crm/issues/22) registra **RECORTE A APROVADO EXPRESSAMENTE SOMENTE PARA DETALHAMENTO DOCUMENTAL**, autoconsulta read-only da própria organização e membership do próprio humano legítimo. As [diretrizes S01-D01/D02/D03 e propósitos D04..D08](docs/desenvolvimento/FUNC-P0-01-S01-DEC-ratificacao-diretrizes-preliminares-D01-D08-2026-11-06.md) foram **APROVADOS EXPRESSAMENTE apenas como documentação preliminar**. A [S01-R01 — campos, estados e segurança](docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) é **PROPOSTA NÃO APROVADA**: CAM-01..10, EST-01..14 e R01-D01..D05 aguardam avaliação. S01 original propôs seis contratos e 22 casos de homologação **não executados**. **Não criar código, login/API, ALLOW, grants ou testes de homologação em razão dessas diretrizes.** Alternativas B (organizações/convites) e C (leads/contatos) não foram escolhidas nesta etapa.

Antes de programar funcionalidade:
1. **Recorte A e diretrizes S01-D01/D02/D03 + propósitos gerais D04..D08 já aprovados só documentalmente**. Solicitar à direção **avaliação separada de R01-D01..D05** antes de qualquer outra especificação; owners funcionais/Segurança/DBA/Privacidade/Produto/FIN/CON ainda não designados, campos/estados/risco não homologados.
2. Contratos por ação/objeto/campo/escopo, identidade, sessão/MFA, tenant, membership, revogação, RLS, auditoria e risco de SQL/pool são especificados e **submetidos a aceites expressos** por competência.
3. Regra de permissões **CORE02-D05 alternativa B** e governança de delegação **GOV01 Modelo M2** foram ratificadas em 06/11 no [PR documental #21](https://github.com/rafhael100/imagine-crm/pull/21), mas **não são mecanismos runtime aprovados**: união de grants positivos válidos no mesmo tenant/escopo (P1) + negação explícita válida sobre a interseção exata (P2), com bloqueios mandatórios superiores; ausência de grant é deny-by-default. GOV01/M2 exige competência específica para delegar sem autoelevação e preserva apenas um administrador humano com MFA no Básico; **GOV02-A/B/C/D segue proposta NÃO aprovada**. D06..D10 A–D também ratificadas apenas como diretrizes preliminares. **PR #21 permanece DRAFT para `main` e não foi integrado**; o documento de planejamento físico ainda requer reconciliação/autorização própria.
4. Classificação de campos CORE02-D04, catálogo D06, MFA/alçadas D07, enforcement/revogação D08, entitlement/estados D09, rastreabilidade/homologação D10, SEG-01/02, CORE01/04/06/07/08/09/10, LGPD e G01..G06 exigem tratamento aplicável ao recorte. **Todos os 67 IDs CORE, AUT-P01..14 e demais gates permanecem ABERTOS**.
5. Somente após aprovação do pacote pode ser proposta uma **nova tarefa, autorização de código e avaliação de `ALLOW` específica**, nunca por analogia. Até lá `DenyByDefaultAuthorizer` é o único autorizador de runtime, `identity.experimental=false` por padrão e nenhuma fixture migra para produção.

### Proibições contínuas e preservação de branches

- **Não iniciar a implementação funcional agora.** Não criar rotas públicas, API autenticada real, grant, new role SQL, novas migrations produtivas, login aberto, usuário real, suporte com acesso a clientes, leads, automações, workers, e-mails, n8n, pagamento, import/export ou coletar dados pessoais.
- **Não modificar `main`, [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) nem [PR #21](https://github.com/rafhael100/imagine-crm/pull/21)**. Ambos PRs continuam DRAFT para `main`, fluxo documental independente do fast-track.
- **Não acessar/implantar na VPS/produção** nem contratar serviços/gerar custos. Os testes usam somente dados fictícios e infraestrutura CI descartável.
- `app.tenant_id` é contexto, **não** autenticação, ACL, mandado ou direito de negócio. Não juntar escopos de diferentes tenants e não transformar deny explícito em veto global.
- A IN-127 Sites↔CRM não estabelece retenção/timeout para filas gerais do CRM. Sucessos HTTP e snapshots não confirmam fatos de negócio.

## Testes e evidências
- SHA F1-10 pré-merge `b6a5d4e3afd2d34a00846783afb89aff60fbab9a`: [Laravel/SQLite run 42154310465](https://github.com/rafhael100/imagine-crm/actions/runs/42154310465) **383 passed / 64 skipped / 2.205 assertions**; [PostgreSQL 16/RLS/Redis run 42154310247](https://github.com/rafhael100/imagine-crm/actions/runs/42154310247) **446 passed / 1 skipped / 2.992 assertions**, ambos success.
- Merge F1-10 SOMENTE DEV `6e3d02f432e0e6e6e1c9322c2d286b035661c5c2`: [Laravel run 42155367413](https://github.com/rafhael100/imagine-crm/actions/runs/42155367413) e [PostgreSQL/RLS/Redis run 42155367330](https://github.com/rafhael100/imagine-crm/actions/runs/42155367330), ambos success.
- Commits **documentais** geram novas execuções; conferir checks do SHA atual antes de qualquer atividade. CI laboratório não homologa CORE/SEG/AUT, nem GO funcional.

**Status final:** F1-10 integrada somente na DEV; **FUNC-P0-01 recorte A aprovado exclusivamente para detalhamento documental**, [diretrizes S01 preliminares aceitas](docs/desenvolvimento/FUNC-P0-01-S01-DEC-ratificacao-diretrizes-preliminares-D01-D08-2026-11-06.md), [R01 ainda submetida à avaliação](docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md); PRs #2/#21 DRAFT; `main`/VPS preservados; 67 CORE, SEG, AUT e demais gates abertos. **Nenhuma nova atividade Codex autorizada.**
