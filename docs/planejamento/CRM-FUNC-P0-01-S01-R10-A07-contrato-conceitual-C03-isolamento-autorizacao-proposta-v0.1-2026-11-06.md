# FUNC-P0-01/S01-R10-A07 — Contrato conceitual C03: autorização e isolamento Laravel/PostgreSQL (proposta v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo). **Classificação:** minuta técnica DOCUMENTAL — **NÃO HOMOLOGADA, SEM SQL, SEM IMPLEMENTAÇÃO, SEM TESTES E SEM ALLOW**.

**Mandato específico do usuário:** 'aprovado' em resposta à proposta de **avançar diretamente no detalhamento técnico documental de C03**, sem repetir aprovações anteriores nem liberar código. Esse aceite autoriza **preparar esta minuta**, não aprova cada escolha técnica ainda aberta.

## 1. Evidências e precedência

- [R10-A06 — bloqueios concretos e prioridade de C03](CRM-FUNC-P0-01-S01-R10-A06-sequenciamento-contratos-e-gates-proposta-v0.1-2026-11-06.md), blob `66f0e1eb6c91eab1116e57b384e71dffd3ea7087` (**sequenciamento proposto, não decisão de gate**).
- [Ficha R07-C03](CRM-FUNC-P0-01-S01-R07-FICHA-C03-evidencias-v0.1-2026-11-06.md), blob `f8405c42f8d9d0915ac842114dc04076da1fcc9b` (C03 PENDENTE).
- [CORE-P0-01](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md), blob `ec7a03e38fedc3514eaa2c91c6def80d0ac6dba1`: identidade global, um tenant lógico por organização, camada PostgreSQL RLS + Laravel; D01–D03 como base, D04–D07 abertos.
- [CORE-P0-02](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md), blob `6222b2676f5ae59282c36ee607a3f9e62dd2ead6`: direitos por principal/tenant/recurso/ação/campo/escopo; D01–D03 base, D04+ e direitos executáveis abertos.
- [CORE-P0-03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md), blob `3266eedea35f9d58f0b1fc85f4c991bc8400994a`: serviços/jobs/callbacks não herdam permanentemente direitos humanos; D01–D03 base, D04–D10 abertos.
- [R01 original de S01](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md), blob `36a353caf695aa03aea3b0d6e0e13331a1fda5b3`, e [cenários originais S01-T01..T22](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md), blob `78e1d021c0f1c5f66fc4b9f59092383a4d7b4` (nenhum teste executado nesta fase).
- [R10-A02](CRM-DEC-FUNC-P0-01-S01-R10-A02-D01-D08-alternativas-A-2026-11-06.md), [R10-A03](CRM-DEC-FUNC-P0-01-S01-R10-A03-D01-D08-alternativas-A-2026-11-06.md), [R10-A04](CRM-DEC-FUNC-P0-01-S01-R10-A04-D01-D06-alternativas-A-2026-11-06.md) e [R10-A05](CRM-DEC-FUNC-P0-01-S01-R10-A05-D01-D04-alternativas-A-2026-11-06.md): **diretrizes preliminares**, não novas permissões.

## 2. Limite funcional da S01

A única operação em estudo é **autoconsulta humana da própria organização corrente e do próprio vínculo ativo**. Apenas CAM01 (nome de exibição da organização) e CAM02 (rótulo do próprio vínculo ativo) seriam candidatos visíveis **depois de contratos/gates e autorização futuros específicos**. S01 não troca tenant, lista membros, expõe CNPJ/pagamento/plano/leads, concede benefícios, edita dados ou exporta. Direitos de portabilidade/regularização continuam em fluxos independentes.

**Invariantes para o desenho de autorização (não algoritmo executável):** identidade/sessão humana válida → tenant corrente confiável server-side → membership própria ativa/versionada → política positiva recurso+ação+campo+escopo, sujeita a veto, situação e risco → dados confinados pelo Laravel e PostgreSQL RLS → saída mínima sanitizada. Ausência de qualquer fator ou erro obrigatório = negativa específica, não autorização presumida.

## 3. Matriz de objetos e classes — candidatos, não tabelas físicas

| Objeto/família | Fronteira candidata | Autoridade a validar | Implicação para C03 |
|---|---|---|---|
| Identidade humana e sessão | Global, não tenant-bound | IAM/CORE04 | Comprova principal; não prova acesso a dados de negócio |
| Membership/atribuição de papéis | Referenciada à identidade + tenant | CORE01/CORE02/CORE07 | Estado/versionamento e escopos próprios; vínculo inativo nega S01 |
| Organização CRM | Tenant lógico isolado | CORE01 | CAM01 pode vir do cadastro próprio apenas quando autorização futura permitir |
| Tabelas de negócios tenant-bound | Isolamento obrigatório no DB | Cada dono de módulo + CORE01/SEG | Listagem completa por tabela/índice/FK **a fazer** antes de SQL |
| Objetos globais (identidade, catálogo, configurações) | Exceção restrita, não grant geral | CORE01/CORE02/SEG | Classificar exceção e acesso por ação; nunca leitura universal |
| Suporte/administrador Imagine | Plano de controle distinto | CORE05/SEG | Mandato excepcional quando acessar dados de cliente; sem role bypass genérica |
| Service principals, jobs e webhooks | Escopo operacional específico | CORE03/INT/CORE09 | Revalidar na execução, sem personificar humano |
| Dados de exibição CAM01/CAM02 | Projeção mínima autorizável apenas futuramente | CORE01/07 + CORE02/LGPD | CAM03..CAM10 fora da V0; sem retorno comercial |

**Não** definir nomes de tabelas, índices, roles SQL, comandos SET, claims de JWT ou estratégia física de particionamento neste arquivo. Nomes lógicos não são schema homologado.

## 4. Camadas de decisão e isolamento (modelo de revisão)

1. **Entrada humana:** autenticação/segurança resolve pessoa, sessão, MFA aplicável, e contexto de organização exclusivamente por fonte backend. Identificador do navegador pode expressar intenção de troca em fluxo separado, mas jamais prova permissão. CORE04/CORE01 permanecem owners da validação.
2. **Autorização de aplicação:** CORE02 e dono do recurso avaliam ação específica, campos mínimos, escopo tenant/próprio e bloqueios pertinentes. Estado pago, CNPJ, papel nominal ou membership isolada não são grants. Ausência de grant leva a deny-by-default; precedência final de múltiplos perfis e vetos permanece CORE02-D05.
3. **Isolamento do banco:** para tabelas de negócio tenant-bound, RLS PostgreSQL deve complementar, não substituir, a autorização Laravel. **Role da aplicação com privilégios de owner/BYPASSRLS é risco P0**. Catalogar objetos globais e exceções antes da política física.
4. **Fronteira da conexão:** toda operação que depender de contexto tenant no PostgreSQL precisa de escopo de transação/conexão, propagação e descarte bem definidos para pools, jobs, retries, falhas e rollback. `app.tenant_id`/GUC é contexto técnico e **não** autenticação do usuário. Solução como SET LOCAL é candidata de discussão, não comando aprovado.
5. **Objetos e serviços indiretos:** FKs, views, funções, buscas, anexos, logs, filas e exportações devem preservar autorização e tenant. Mandato de suporte, identidade de serviço e evento de webhook seguem contratos próprios CORE03/05/09/INT, sem personificar S01 humana.
6. **Revogação/cache/concorrência:** revalidar direito antes de nova leitura ou novo efeito conforme classe de operação; tratar janelas concorrentes e desatualização de cache, sem afirmar revogação instantânea global ainda não comprovada. Falha obrigatória conduz a negativa do recorte S01.

## 5. Registro priorizado de ameaças e vínculos com testes FUTUROS

| Risco | Classe | Exemplo adverso | Controle / resultado esperado (sujeito à homologação) | Cenários originais (NÃO executados) |
|---|---|---|---|---|
| C03-R01 | Escopo A→B por IDOR | Identidade humana em tenant A conhece identificador de recurso B | Backend não considera ID como prova de posse; autorização efetiva por tenant/objeto; DB deve impedir retorno cruzado | S01-T04/T05; CORE01-T01/T02; CORE02-T02 |
| C03-R02 | Role privilegiada contorna RLS | Aplicação usa owner, superuser ou role com BYPASSRLS; política ausente em tabela | Inventário de papéis, privilégios e tabelas; papel runtime tenant-bound não contorna RLS; exceções globais explicitadas | S01-T16; CORE01-T07 |
| C03-R03 | Pool ou conexão contaminada | Consulta B reutiliza contexto de A em transação/pool/worker | Contexto definido/validado com fronteira transacional e descarte garantido; fail-closed se incompleto; nenhuma GUC aceita como autenticação | S01-T16/T20; CORE01-T03; CORE03-T02 |
| C03-R04 | FK cross-tenant e objetos indiretos | Registro de A referencia anexo/dado de B ou objeto global sem política | FKs/relações tenant-bound e consultas indiretas sob mesma fronteira; catálogo de exceções globais | CORE01-T09; CORE02-T02 |
| C03-R05 | Revogação concorrente | Membership ou permissão revogada enquanto sessão/tela/job continua | Revalidar em nova operação e antes do efeito assíncrono segundo contrato; cache antigo não libera | S01-T06/T07/T20; CORE01-T04; CORE03-T01 |
| C03-R06 | Permissão inferida de papel/plano | Título administrativo, assinatura paga ou vínculo ativo resulta em grant implícito | Matriz recurso×ação×campo×escopo, política cumulativa por principal e entitlement, deny-by-default e negações escopadas | S01-T09/T10/T11/T15; CORE02-T04/T11 |
| C03-R07 | Canal alternativo ou exportação | Endpoint/API/report/CSV/job oferece campo fora de CAM01/02 sem checar direito | Autorização server-side por canal/ação e projeção mínima; exportação/portabilidade por contrato próprio, nunca por S01 | S01-T12/T13/T18/T21; CORE02-T03/T10 |
| C03-R08 | Admin Imagine e suporte excepcional | Conta central, suporte ou agente técnico lê cliente sem mandato | Controle separado com caso, escopo, finalidade, prazo e auditoria; nenhum bypass universal | S01-T14; CORE01-T07/T08; CORE02-T01/T12 |
| C03-R09 | Falha de infraestrutura/caching | DB/Redis/autorizador indisponível; cache de tenant anterior ou resposta em erro | Falha segura específica da operação; sem retorno de dado protegido nem vazamento em log/stack | S01-T17/T19; CORE03 |
| C03-R10 | Eventos e automações substituem identidade | Webhook/n8n/IA define tenant ou principal por payload/prompt/ACK | Identidade de serviço distinta, contrato oficial tipado, revalidação e escopo no destino; não simular read_self humano | S01-T14; CORE01-T06/T08; CORE03-T07/T09 |

Esses riscos **não** são novos testes, nem evidência de que o sistema já os bloqueia. Os cenários originais devem ser executados em ambiente explicitamente autorizado somente após desenho físico e GO técnico específico.

## 6. Bloqueadores para contrato técnico C03 — status real

| ID | Bloqueio | Entregável faltante | Competência requerida | Estado |
|---|---|---|---|---|
| C03-B01 | Papéis DB e fronteira runtime | Role(s) da aplicação sem bypass, ownership, FORCE RLS quando tecnicamente cabível, manutenção, migrações e operação privilegiada | CORE01/SEG/DBA | Nenhum catálogo físico/política SQL homologado |
| C03-B02 | Cobertura por tabela, view, função, FK e objetos globais | Inventário de objetos, fronteira de tenant, exceções, cascatas e caminhos indiretos | CORE01/02/03, donos módulos | Inventário físico e provas ausentes |
| C03-B03 | Contexto transacional e pool de conexões | Origem confiável e duração do contexto, reuso worker/pool, rollback, sessão contaminada, fail-closed | CORE01/03/SEG | Mecanismo SET LOCAL/GUC ou equivalente não escolhido nem testado |
| C03-B04 | Política efetiva Laravel por operação | Recurso/ação/campo/escopo, principal, estado, grants/denies, interseções e revogação | CORE02-D04..D10, CORE07/08, SEG | Nenhum ALLOW/GRANT executável |
| C03-B05 | Segurança entre canais e principal técnico | API interna, workers, exportações, relatórios, integração e suporte separados da S01 | CORE03/05/09, INT, SEG | Manifests e contratos tipados executáveis ausentes |
| C03-B06 | Teste e avaliação de riscos | S01-T04/T05/T09–T11/T14/T16/T17/T20/T21 e CORE01-T01/T03/T07/T09, sem redundar suítes | CORE10/SEG, DBA/QA | Nenhum teste funcional autorizado/executado |
| C03-B07 | Parecer competente e independência quando necessária | Revisão por competência SEG/DBA e decisão documentada de risco, distinguindo autoria de independência | G04 interno e revisão especializada externa quando exigida | G01–G06 com mesmo titular não satisfazem independência |

**Conflito de funções:** G01–G06 estão documentalmente assumidas pela mesma pessoa; revisões de G02/G04 ou G05/G06 não são independentes entre si. Se o critério exige revisão independente, o parecer especializado permanece pendente.

## 7. Critérios de prontidão por estágio — não confundir com gates

| Estágio | Condição mínima | Situação hoje |
|---|---|---|
| Preparação documental C03 | Fontes, invariantes, riscos, objetos lógicos e bloqueios identificados | **Este arquivo ajuda a organizar; ainda há decisões físicas pendentes** |
| Pronto para decisão de implementação | Política de ação/escopo, inventário físico, roles, contexto de conexão, requisitos de SEG e dependências IAM coerentes; riscos documentados | **NÃO** |
| Pronto para testes reais | Código/SQL e ambiente de testes autorizados separadamente, dados fictícios, casos S01/CORE e pareceres exigíveis | **NÃO** |
| Homologação de C03 | Evidência íntegra de testes de isolamento/cross-tenant, revisão competente, risco residual aceito por alçada e gate formalmente encerrado | **NÃO** |

## 8. Decisões concretas ainda abertas e encaminhamento recomendado

- **C03-P01 — Inventário físico:** definir, no futuro, a lista de tabelas/visões/funções tenant-bound e globais, com exceções documentadas, sem permitir owner/BYPASSRLS no runtime comum.
- **C03-P02 — Contexto do PostgreSQL:** escolher e revisar modelo de transação/conexão, pool/reuso, limpeza, rollback e workers antes de SQL.
- **C03-P03 — Política de autorização:** reconciliar CORE02-D04..D10, recurso/ação/campos/escopo e casos de grant/deny, sem atribuir `read_self` implicitamente.
- **C03-P04 — Comprovação futura:** escolher abordagem de teste e revisão independente ou competente quando exigida para RLS, IDOR, revogação, jobs e comportamento com falha; executar **apenas após** autorização apropriada.

**Próximo recorte útil:** completar documentalmente o **inventário lógico para C03-P01/P02**, com classes de roles, pontos de persistência de tenant, lista de superfícies a analisar e decisão de risco; não abrir uma nova série de aprovações genéricas das mesmas diretrizes.

## 9. Preservações explícitas

**R02-C01..C08 = PENDENTES; 67 IDs CORE, SEG-01/02, AUT, ARQ/AGT, FIN/CON, INT P0/G01..G06, LGPD, CORE10/DEV-GATE e todos os demais gates permanecem ABERTOS.** R08-Gxx são funções internas, não gates INT-Gxx.

**Proibido por este documento:** alterar `main`, PRs #2/#21, branch `dev/fast-track-crm-2026-11-04`, VPS, produção, código, SQL, migrations, roles e grants físicos, `ALLOW`/`GRANT`, CI, testes funcionais, dados reais ou cobrança. Modificação restrita a Markdown em branch documental.