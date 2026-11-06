# CRM Imagine — REV-S01-NEG-01 — Revisão técnica estática do PR #24 e compatibilidade com PR #23

**Data:** 06/11/2026 (America/Sao_Paulo). **Escopo:** revisão técnica do PR #24 solicitada com confirmação “está ok” após sua recomendação; **não há autorização de correção de código, merge, banco persistente, execução de SQL, VPS, `ALLOW` ou deploy**.

## 1. Objetos revisados e trilha de evidência

- [PR #24](https://github.com/rafhael100/imagine-crm/pull/24) `OPEN / DRAFT / merged=false / mergeable=true`, base DEV `dev/fast-track-crm-2026-11-04` SHA `8c9683acfd8decf98544402acda360d7e0f0a489`, HEAD `072f369542d0667056627f60f1f12d07173d883b`.
- Patch completo do PR #24 conferido: **um único arquivo adicionado**, `tests/Feature/S01NegativeBoundaryTest.php`, **238 linhas de PHP apenas de testes**; nenhuma modificação de `app/`, `routes/`, `database/`, workflows, permissões ou dependências. Dois commits à frente e zero atrás da DEV verificada.
- Código consultado: `app/Core/Tenancy/TenantAccessBoundary.php`, `app/Core/Authorization/ExperimentalResourceCatalog.php`, `DenyByDefaultAuthorizer.php` (revisado na execução precedente) e o arquivo de teste; nenhum código de produção foi alterado.
- [PR #23](https://github.com/rafhael100/imagine-crm/pull/23): `OPEN / DRAFT / merged=false`; base mesma DEV SHA `8c9683acfd8decf98544402acda360d7e0f0a489`, HEAD `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558`, 7 arquivos alterados, incluindo localização e guarda da migration tenant e workflow de PostgreSQL. **As branches são paralelas**, não empilhadas.

## 2. Testes efetivamente executados

| Suite | Run/ambiente | Resultado verificado na HEAD do PR #24 | Observação |
|---|---|---|---|
| F0 Laravel / SQLite | [42270254064](https://github.com/rafhael100/imagine-crm/actions/runs/42270254064) | `completed/success` — **391 passados, 66 ignorados** | Os dois testes PostgreSQL-only de S01 foram corretamente ignorados no F0 |
| PostgreSQL16 / RLS / PHP8.4 | [42270254086](https://github.com/rafhael100/imagine-crm/actions/runs/42270254086) | `completed/success` — **456 passados, 1 ignorado** | `S01NegativeBoundaryTest` explicitamente PASS, inclusive consulta negada após MFA/membership ativos, sessão revogada e vínculo de outro principal |

**Ressalva:** resultado independente dos PRs, não testes de combinação, staging, banco real, autenticação pública ou produto. O `COMMENTED` anterior no PR #24 contém evidências de execução; não é aprovação independente.

## 3. Resultados de revisão estática

| ID | Constatação | Nível / implicação | Decisão / encaminhamento |
|---|---|---|---|
| **REV24-01** | O PR #24 só adiciona testes negativos. Nenhuma nova rota, `ALLOW`, regra real ou migração; a cadeia deny-only foi mantida. | **Conforme no recorte autorizado** | Aceitar como prova de regressão inerte, não homologação de leitura |
| **REV24-02** | O teste de `organization.self/read_self` passa por `AuthorizationIntent::fromArray`, mas o `ExperimentalResourceCatalog` aceita somente `lab.record` e ações `read/search/update/export/report/execute`. Assim, mesmo com membership ativa, a negação decorre do **contrato não descrito** (`CONTRACT_INVALID`), não de avaliação de uma futura política real `read_self`. | **Limitação P1 de representatividade**, sem violação do requisito deny-only | Não alegar que permissões positivas `read_self` foram validadas. Para estágio posterior, exigir contrato homologado e provas de grant/deny sob alçada própria. **Não** implementar `ALLOW` neste PR |
| **REV24-03** | O caso de cross-tenant com principal A no tenant C e membership B no tenant D valida negação de acesso ao vínculo de outro principal. Não testa o cenário distinto de **uma mesma identidade com duas memberships ativas** e a troca não autorizada de contexto. | **Lacuna P1 de cobertura futura** | Adicionar, se autorizado em incremento próprio, um teste negativo sintético da identidade A vinculada a C e D, impedindo seleção por header/query e revalidando o tenant server-side. Sem seletor/roteamento real hoje |
| **REV24-04** | Os testes sintéticos consultam `crm_memberships` e a role de aplicação no PostgreSQL. O PR #23 move a migration tenant para caminho experimental e troca a etapa CI de descoberta padrão por execução explícita com flag e banco de laboratório. Os arquivos alterados pelos PRs **não conflitam textualmente**, mas o ambiente de teste combinado não foi executado. | **Risco de integração P1, não defeito provado** | Antes de merge DEV em qualquer ordem, revalidar HEAD e CI na combinação efetivamente resultante ou em branch efêmera de teste autorizado, sem merge na DEV apenas para 'experimentar'. Se #23 entrar primeiro, rebase/atualização do #24 e novo CI |
| **REV24-05** | A revisão estática foi realizada pelo mesmo assistente que criou os testes, usando a conta GitHub conectada; não há parecer de revisor independente competente atestado no PR. | **Pendente de governança/independência** | Revisor SEG/QA/DBA competente quando exigido, com escopo e riscos residuais; este relatório não substitui assinatura/parecer |

## 4. Rastreabilidade por recorte

- Sessão ausente/payload/header e sessão de MFA pendente: tentativa não alcança membership nem callback.
- Sessão revogada: registro invalida a cadeia antes de consulta.
- Membership própria e MFA concluído em PostgreSQL: callback não executa para `read_self` sem contrato/policy.
- Tentativa de vínculo B/D sob sessão A/C: nega sem executar callback e com contexto transacional limpo.
- Campos declarados não homologados ou classe inválida: parsing rejeita antes de consultar identidade/membership; **isso não é homologação de CAM-01/CAM-02 no runtime**.
- Envelope sintético construído manualmente: permanece negado; ausência de factory `allow` no contrato.
- Rotas candidatas de login e leitura S01 seguem inexistentes.

## 5. Parecer e condições posteriores

**PARECER TÉCNICO LIMITADO:** a implementação do S01-NEG-01 cumpre o recorte de testes negativos autorizado, e CI separado do PR #24 está verde. Não foi encontrado desvio de escopo ou inclusão de privilégio no patch estático. **NÃO É GO DE MERGE**, nem validação de funcionalidade, nem parecer independente. O PR #24 deve permanecer **DRAFT** para avaliação.

Para qualquer autorização posterior de merge exclusivamente na DEV: (1) obter revisão competente/independente quando requerida, (2) tratar as ressalvas REV24-02/03 como limitações documentadas e não usar estes testes como alegação de autorização positiva, (3) reconfirmar SHA/CI, (4) verificar cenário combinado com PR #23 conforme sua ordem, e (5) obter ato expresso e específico da Direção. Regras de produção e gates não mudam por merge DEV.

## 6. Preservações

`main` SHA `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`, DEV original `8c9683acfd8decf98544402acda360d7e0f0a489`, PRs #2/#21 e VPS **não alterados por esta revisão**. PRs #23/#24 permanecem abertos e DRAFT. C01..C08, 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates continuam ABERTOS.
