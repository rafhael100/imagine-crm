# Imagine CRM — OPS-26 | Evidências de SEG/DBA que NÃO equivalem a homologação

**Data do registro:** 10/11/2026 · **Ambiente:** branch de desenvolvimento e CI PostgreSQL 16 temporário do GitHub · **Alcance:** testes técnicos de laboratório, sem clientes.

## Resumo de entregas nesta rodada

| Referência | Evidência produzida | O que não foi provado |
|---|---|---|
| [PR #151 — SEGDBA-05](https://github.com/rafhael100/imagine-crm/pull/151) | Acrescenta três cenários PostgreSQL: a **mesma identidade fictícia** tem vínculos simultaneamente ativos nas empresas A e B; cada nova operação revalida o vínculo e o isolamento RLS; duas conexões mantêm contextos locais separados; revogar B não revoga A; a autorização de negócio permanece deny-only. DEV SHA `8964cf88a38465b7a87a5052c448f9fdbb4bb837`. | Não implementa primeira leitura autorizada, não prova sessão/MFA real, identidade confiável, isolamento em rede/servidor operacional ou direito de ver dados pessoais. |
| [PR #152 — SEC-CI-SRC-01](https://github.com/rafhael100/imagine-crm/pull/152) | Suíte de regressões de **código-fonte** sobre os dois workflows existentes: gatilhos GitHub fechados, token de leitura, runner hospedado, F0 em SQLite memória, PostgreSQL efêmero, roles `NOBYPASSRLS`, três migrations explicitamente precedidas de preflight e casos de adulteração rejeitados. DEV SHA `ec30f314fb1afb1b626b7854d1a4db173c83a63e`. | O teste não verifica autenticação de autores/commits, runner comprometido, rede persistente, Docker externo, segredos, banco operacional nem comandos escondidos fora do inventário. É tripwire de revisão, não barreira antifraude. |
| [PR #153 — SEGDBA-03](https://github.com/rafhael100/imagine-crm/pull/153) | Consultas **somente leitura** nas cinco roles efêmeras de CI (RLS/IAM/provisionamento/async/auditoria): proibição de `CREATE` no banco/schema, `SUPERUSER`, `CREATEDB`, `CREATEROLE`, `BYPASSRLS` e de herança de roles administrativas. DEV SHA `c8173047c7dbe0120591605f443611a39db45ebc`. | Não garante credenciais seguras na VPS, rotação, ownership e ACL de instância real, restauração, provisioning no ambiente de clientes ou revisão de pessoas independentes. |

## Links para as CIs executadas

- #151 pré-merge: [F0 #42758973104](https://github.com/rafhael100/imagine-crm/actions/runs/42758973104) e [PG/RLS #42758972861](https://github.com/rafhael100/imagine-crm/actions/runs/42758972861), ambos `success`; pós-merge: [F0 #42759135036](https://github.com/rafhael100/imagine-crm/actions/runs/42759135036) e [PG/RLS #42759135046](https://github.com/rafhael100/imagine-crm/actions/runs/42759135046), ambos `success`.
- #152 na HEAD inicial `4cef8a9b`: [F0 #42759369733](https://github.com/rafhael100/imagine-crm/actions/runs/42759369733) e [PG/RLS #42759369566](https://github.com/rafhael100/imagine-crm/actions/runs/42759369566) **falharam** pelo *falso positivo na expressão do teste do runner*, sem evidência de violação do runner. Corrigido pela HEAD `1b361c58`; [F0 #42770244682](https://github.com/rafhael100/imagine-crm/actions/runs/42770244682) e [PG/RLS #42770244383](https://github.com/rafhael100/imagine-crm/actions/runs/42770244383) `success`; pós-merge [F0 #42770390378](https://github.com/rafhael100/imagine-crm/actions/runs/42770390378) e [PG/RLS #42770390495](https://github.com/rafhael100/imagine-crm/actions/runs/42770390495) `success`.
- #153 pré-merge: [F0 #42770597562](https://github.com/rafhael100/imagine-crm/actions/runs/42770597562) e [PG/RLS #42770597249](https://github.com/rafhael100/imagine-crm/actions/runs/42770597249) `success`; pós-merge [F0 #42770764173](https://github.com/rafhael100/imagine-crm/actions/runs/42770764173) `success`; [PG/RLS #42770764290](https://github.com/rafhael100/imagine-crm/actions/runs/42770764290) **verificar o resultado final antes de afirmar aprovação**.

## Matriz de aceite pendente — encaminhamento à pessoa independente

A [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27) **continua aberta**, sem nomeação de revisor SEG/DBA independente e sem decisão final P0/P1. Os testes novos são evidências de construção e regressão **pelo próprio desenvolvimento**, não evidências de independência.

| Pauta | Situação após os três PRs | Evidência/decisão ainda indispensável |
|---|---|---|
| SEGDBA-01 — destruição antes de guard de migration | **NO-GO**: travas de workflow e migração não impedem um SQL administrativo arbitrário ou `DROP` fora do CI | Revisão de acesso/ownership/roles, barreira externa à migration, política de mudança/backup e exercício de recuperação descartável autorizado |
| SEGDBA-02 — banco realmente descartável e isolado | Mitigação de CI por preflight `SEC-CI-02`; **não** atesta VPS | Prova de ciclo de vida, volumes, firewall/rede, credenciais, origem real e inexistência de conexão a dados operacionais |
| SEGDBA-03 — papéis e privilégios | **Evidências adicionais** com #153 para cinco roles efêmeras sem criar schema nem herdar roles privilegiadas | DBA validar matriz de identidade, GRANT/DEFAULT PRIVILEGES/ownership, conexões reais e rotação segura |
| SEGDBA-04 — migrações legadas e restore | **NO-GO**, nenhuma nova restauração executada | Compatibilidade de ledger, instalação limpa versus legado, drift/rollback, recuperação e RPO/RTO com evidências autorizadas |
| SEGDBA-05 — tenant/RLS, revogação e multi-membership | **Evidências adicionais** com #151, inclusive identidades com dois vínculos ativos e sessões de BD distintas | Testes reais sob identidades/sessões confiáveis, revogação antes de leituras comerciais, autorização por ação/campo/escopo e conexão/pooling |
| SEGDBA-06 — eventos push versus PR | Preflight `SEC-CI-02` anteriormente corrigido; #152 detecta desvios textuais | Revisão do workflow e sua governança de edição/branches; diferenciar CI de release seguro |
| SEGDBA-07 — revisão independente | **PENDENTE / NÃO DESIGNADO** | Revisor nomeado com alçada e independência comprováveis, parecer itemizado P0/P1, riscos residuais, decisão e assinatura apropriadas |

## Precondições que não podem ser inferidas por CI

- [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22): R01-D01..D05, CAM/EST, fontes de organização/membership e owners competentes ainda pendentes; recorte A aceito **somente para documentação**, não para criar endpoint, `ALLOW`, billing ou regra produtiva.
- `DenyByDefaultAuthorizer` permanece deny-only, e o inventário HTTP só admite `GET|HEAD /up` nesta fase; não conceder direito positivo por confiança em objeto PHP, nome de medidor, `app.tenant_id` ou resultados da suíte.
- O sistema não está operacional para clientes; não houve deploy, uso da VPS, migrações em banco real, alteração de `main`, acesso a dados pessoais ou autorização para cobrança.

**Critério de continuidade segura:** manter implementações inertes e provas negativas na DEV com revisão de diffs e CI, enquanto o responsável competente resolve os contratos e o revisor independente emite parecer real. Nenhum teste descrito aqui marca um gate CORE/SEG como concluído.
