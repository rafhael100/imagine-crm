# Imagine CRM — VAL-PR23-PR24 — Validação conjunta sintética em branch isolada (PR #25)

**Data:** 06/11/2026 (America/Sao_Paulo). **Estado:** COMPOSIÇÃO ESTÁTICA VERIFICADA; EXECUÇÃO DE CI **BLOQUEADA ANTES DOS STEPS** — NÃO HOMOLOGADA. **NO MERGE.**

## 1. Mandato expresso e fronteiras

A Direção respondeu **'ok vamos'** à recomendação de **preparar uma validação conjunta e exclusivamente sintética dos PRs #23 e #24, em branch isolada, para verificar compatibilidade antes de solicitar eventual integração na DEV**. O mandato não autoriza merge de nenhum PR, mudança de `main`, branch DEV original, VPS, banco persistente, código funcional, `ALLOW`/grants, produção nem fechamento de gates.

## 2. Composição exata e identificadores

- Repositório: `rafhael100/imagine-crm`.
- DEV original/base: `dev/fast-track-crm-2026-11-04` — SHA `8c9683acfd8decf98544402acda360d7e0f0a489`.
- [PR #23](https://github.com/rafhael100/imagine-crm/pull/23), migrações de laboratório, **OPEN DRAFT** HEAD `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558`; contém **7 arquivos** em comparação com DEV.
- [PR #24](https://github.com/rafhael100/imagine-crm/pull/24), testes negativos S01, **OPEN DRAFT** HEAD `072f369542d0667056627f60f1f12d07173d883b`; contém somente `tests/Feature/S01NegativeBoundaryTest.php`, blob `e773c3c9382648616dec710ed97e81a1fd67669c`.
- Branch de teste criada: **`dev/validation-pr23-pr24-synthetic-2026-11-06`**, a partir da HEAD exata do PR #23; copiado somente o conteúdo do arquivo de teste do PR #24, **sem refatoração**.
- Commit novo da branch de validação: **`3cbe52e5cbaa505d45d85c9292856f4921b56de7`**.
- [PR #25](https://github.com/rafhael100/imagine-crm/pull/25): **OPEN DRAFT, base DEV, NÃO AUTORIZADO A MERGE**. Objetivo exclusivamente acionar os workflows CI no código combinado.
- Compare de validação versus DEV: **6 commits ahead / 0 behind, 8 arquivos** exatamente (7 do #23 + 1 do #24), sem conflito de caminhos. Essa observação **não comprova ausência de incompatibilidade em runtime**.

## 3. Tentativas do CI na HEAD combinada — falha pré-execução

| Workflow | Run | Tentativas | Status reportado | Evidência concreta |
|---|---|---|---|---|
| F0 PHP/Laravel/SQLite | [42277139605](https://github.com/rafhael100/imagine-crm/actions/runs/42277139605) | **2** — original + retry | `completed / failure` | Job em tentativa 2 `128096971948` com `steps=[]`, `runner_id=0`, `runner_name=''`, terminado cerca de 3 s após início; ausência de logs recuperáveis (`BlobNotFound`) |
| PostgreSQL 16/RLS | [42277139632](https://github.com/rafhael100/imagine-crm/actions/runs/42277139632) | **2** — original + retry | `completed / failure` | Job em tentativa 2 `128096988078` com `steps=[]`, nenhum runner identificado, terminado cerca de 2 s após início; logs recuperáveis ausentes (`BlobNotFound`) |

**Leitura fundamentada:** nenhum dos workflows alcançou Checkout, instalação de dependências, migrations ou PHPUnit. Não existem resultados de testes da combinação. O padrão aponta **falha antes do runner executar steps**, mas **a causa-raiz é indeterminada** a partir dos registros disponíveis. Não atribuir a cobrança, cotas, outage, configuração, branch policy ou código sem anotação/diagnóstico verificável.

**Resultados individuais anteriores permanecem válidos somente nos SHAs originais:** PR #23 teve CI F0 e PostgreSQL verdes; PR #24 [F0 42270254064](https://github.com/rafhael100/imagine-crm/actions/runs/42270254064) e [PostgreSQL 42270254086](https://github.com/rafhael100/imagine-crm/actions/runs/42270254086) verdes. **Não extrapolar estes resultados à combinação.**

## 4. Critérios de sucesso ainda não atendidos

| ID | Evidência necessária da combinação | Situação |
|---|---|---|
| VAL-01 | Run F0 executa Checkout/dependências/migrations e suite completa no SHA combinado | **BLOQUEADO (não executou)** |
| VAL-02 | Run PostgreSQL 16 executa migração tenant explícita de laboratório e testa RLS/roles e suites F1 + S01 | **BLOQUEADO (não executou)** |
| VAL-03 | Risco de ledger/migration após #23 e comportamento de `read_self` deny-only após #24 sem regressão | **PENDENTE DE CI COMBINADO** |
| VAL-04 | Inspeção de revisão independente e riscos P0/P1 de SEG/DBA (comandos destrutivos, alvo de banco, estados, política positiva) | **PENDENTE** |
| VAL-05 | Authorization expressa e separada para cada merge na DEV e reconferência de SHAs/CI | **NÃO CONCEDIDA** |

## 5. Encaminhamento

1. Um mantenedor com acesso às verificações completas do GitHub Actions deve consultar **as anotações dos checks** de ambas as tentativas (há indicação de anotações na API de check-runs), além de política de runners, limites de uso e eventual problema administrativo; não inferir o motivo sem evidência. Os logs de steps não existem/estão indisponíveis.
2. Depois de resolver a condição de pré-execução, reexecutar CI **na mesma HEAD** `3cbe52e5cbaa505d45d85c9292856f4921b56de7` ou registrar nova HEAD com justificativa, comparando evidências da versão exata.
3. Inspecionar especialmente o workflow modificado pelo PR #23 (`Verify default migration discovery` e `Explicitly migrate isolated tenant laboratory`) e a suíte `S01NegativeBoundaryTest` do #24; nenhum teste SQL real autorizado.
4. Manter **PR #25 exclusivamente como artefato de validação, DRAFT, NO MERGE**. Qualquer integração futura deverá ocorrer, se autorizada, nos PRs originais/fluxo técnico formal, jamais por merge acidental deste veículo de CI.

## 6. Inalterados

`main`, DEV original, PRs #2/#21/#23/#24 e VPS não modificados nesta atividade; nenhum `ALLOW`, SQL, segredo, dado real, deploy ou pagamento. C01..C08, 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates permanecem **ABERTOS**. Revisão SEG/DBA independente quando exigida não foi concluída.
