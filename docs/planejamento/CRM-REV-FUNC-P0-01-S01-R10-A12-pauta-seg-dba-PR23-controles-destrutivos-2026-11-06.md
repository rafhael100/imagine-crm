# CRM Imagine — R10-A12: pauta SEG/DBA para PR #23, execução de migrations e risco de comandos destrutivos

**Data:** 06/11/2026 (America/Sao_Paulo). **Status:** PARECER INTERNO DOCUMENTAL E SOLICITAÇÃO DE AVALIAÇÃO COMPETENTE — **NÃO É PARECER INDEPENDENTE, HOMOLOGAÇÃO NEM GO DE MERGE**.

## 1. Mandato e fatos fixos

A direção respondeu **'ok aprovado'** à proposta de **submeter as pendências técnicas à avaliação competente e, apenas posteriormente, decidir sobre autorização de merge do PR #23 exclusivamente na DEV**. Esse aceite **não autoriza merge, novas alterações de código, consultas à VPS, comandos destrutivos, SQL, nem fechamento de gates**.

- [PR #23](https://github.com/rafhael100/imagine-crm/pull/23): `open`, `draft=true`, `merged=false`; HEAD `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558`, base `dev/fast-track-crm-2026-11-04` SHA `8c9683acfd8decf98544402acda360d7e0f0a489`.
- [CI F0](https://github.com/rafhael100/imagine-crm/actions/runs/42252193082): `completed/success`, **384 passed/66 skipped** na HEAD.
- [CI PostgreSQL/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/42252193108): `completed/success`, **449 passed/1 skipped**, incluindo cenários negativos de banco/host errado e compatibilidade de migration no schema descartável.
- [Revisão anterior R10-A11](CRM-REV-FUNC-P0-01-S01-R10-A11-PR23-riscos-P0-migrations-revalidacao-2026-11-06.md), commit documental `5134bb783b8e912d0622e5f1cf5dbe982902757b`.
- `R08-G01..G06` são funções de governança acumuladas pelo responsável interno. **Nenhum profissional de segurança/DBA independente foi identificado ou emitiu parecer nesta etapa.** Não atribuir competência/profissional presumido nem apresentar este texto como segunda opinião.

## 2. Diagnóstico da revisão estática

**CONTROLE C1 — isolamento de descoberta (COMPROVADO EM LABORATÓRIO):** a migration de tenant saiu de `database/migrations/` e passou para `database/experimental-migrations/tenant/`, enquanto a execução padrão não a descobre. `up/down` têm `guard` com opt-in, `local/testing`, PostgreSQL, hostname loopback, nome de DB esperado e confirmação via `current_database()`. O CI só instala schema candidato ao executar explicitamente a migration.

**LIMITE L1 — identidade do banco (RISCO RESIDUAL):** `localhost`/127.0.0.1 podem ser uma instância durável local ou túnel para um banco remoto; nomes `imagine_crm_ci`, `imagine_crm_f0` ou `imagine_crm_lab_*` são convenções e não demonstram descartabilidade. As verificações não atestam isolamento de credenciais, escopo de rede, privilégio DDL, ambiente ou esquema real. **Não classificar R-01 como eliminado**.

**LIMITE L2 — comandos destrutivos (RISCO ABERTO):** `migrate:fresh`, `db:wipe` e equivalentes podem apagar tabelas/objeto antes de a migration candidata ser avaliada. Nenhuma guarda em `up/down` protege genericamente o processo. Avaliar também `migrate:refresh`, `rollback`, comandos shell/SQL administrativos, branch de deploy e acesso ao perfil de CI. **Não executar contra sistemas reais**.

**LIMITE L3 — migrations anteriores (PARCIAL):** teste em schema aleatório descartável verifica basename já registrado, descoberta padrão, opt-in repetido e rollback explícito. Não avalia todos os estados de drift, schema parcialmente criado, migration histórica com alterações ou banco persistente; sem evidência de banco existente examinado. 

**LIMITE L4 — revisão segregada:** revisão é do mesmo assistente que produziu código e da conta GitHub vinculada. Não equivale a avaliação SEG/DBA independente se o gate exigir independência.

## 3. Perguntas para profissional tecnicamente competente (parecer pendente)

| ID | Questão a decidir | Evidência objetiva solicitada | Critério antes do merge DEV | Estado |
|---|---|---|---|---|
| SEGDBA-01 | Controle de credenciais e banco alvo | Diagrama lab vs ambiente persistente; role e privilegios DDL limitados; explicação dos túneis e mapeamento host/database/schema | Mitigação do risco de DDL em banco errado proporcional ao DEV | **PENDENTE** |
| SEGDBA-02 | Política de operações destrutivas | Lista explícita de comandos permitidos/proibidos em CI/DEV/prod; barreiras de permissão, segregação e restauração | Não haver confiança na guarda da migration como proteção global | **PENDENTE** |
| SEGDBA-03 | Compatibilidade do migration ledger | Política de `migrate`/`migrate:status`/`rollback`/`refresh`, schemas antigos, nomes e drift; testes apenas sintéticos | Plano documentado para bancos persistentes sem presumir execução ou perda de tabelas | **PENDENTE** |
| SEGDBA-04 | Caminho de implantação e rollback | Quem pode executar SQL/DDL, forma de aprovação, prova de ambiente, backup/recovery e janelas autorizadas | Nenhuma permissão de produção por merge DEV, sem DDL com credencial de runtime | **PENDENTE** |
| SEGDBA-05 | RLS preservado e sem grants indevidos | Revisão do diff da migration, `FORCE RLS`, roles, GUC mutável e provas dos testes negativos | Defesa em profundidade preservada; nada de `ALLOW` de negócio | **PENDENTE** |
| SEGDBA-06 | Independência, responsável e risco residual | Nome e competência do revisor real (não inferidos), escopo, conflito de interesse, recomendação, limites e evidência/assinatura quando requerida | Parecer autêntico e decisão da Direção separada do parecer; nenhuma homologação automática | **PENDENTE** |

## 4. Opções para a próxima decisão, sem deliberação automática

**Opção A — recomendada por segurança:** manter PR #23 em **DRAFT** e obter o parecer SEG/DBA competente sobre SEGDBA-01..06, com independência quando exigida, antes de uma aprovação específica de merge na DEV. Não é necessária nova rodada de ratificações genéricas: somente evidências e decisões residuais.

**Opção B — apenas se devidamente justificada por competência e gates:** documentar um **merge experimental somente na DEV** apesar de risco residual de processo, com isolamento operacional estrito e sem liberar VPS/main/produção, mediante autorização explícita posterior. **Não** tratá-la como alternativa já aceita nem como dispensa de revisão independente obrigatória; verificar requisitos aplicáveis antes de contemplá-la.

**Opção C — se SEGDBA-01 ou 02 demonstrar risco inaceitável mesmo no lab:** autorizar futuramente, por ato específico, um incremento mínimo adicional em branch filha para reforçar controles de CI/deploy ou testar cenários de comando destrutivo **exclusivamente em banco descartável**; não editar nesta etapa.

## 5. Condições de governança e passagem

- G01 pode organizar esta pauta e documentar uma decisão empresarial. G04/G06 acumulados não criam parecer de revisor externo; se independência for requisito, manter **REVISÃO INDEPENDENTE PENDENTE**.
- Não realizar testes destrutivos em banco de produção/VPS, não alterar políticas de DB, grants, repositórios `main`/DEV ou PRs #2/#21. Eventuais testes futuros dependem de GO específico.
- Não declarar PR #23 'APPROVED', retirar o estado DRAFT ou efetuar merge por ter passado no CI.
- **C01–C08 PENDENTES; 67 CORE, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS.**

**Resultado deste registro:** encaminhamento de avaliação SEG/DBA estruturado documentalmente e comunicável pelo PR, **não** parecer emitido por terceiro ou prova de mitigação completa.