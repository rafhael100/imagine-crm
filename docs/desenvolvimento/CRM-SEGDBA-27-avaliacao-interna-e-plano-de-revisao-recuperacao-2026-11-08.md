# CRM Imagine — SEGDBA-27: avaliação interna dos controles e plano de revisão/recuperação

**Data:** 08/11/2026 (America/Sao_Paulo). **Natureza:** análise documental e estática do código existente, a pedido da Direção. **NÃO É PARECER INDEPENDENTE SEG/DBA, HOMOLOGAÇÃO OU AUTORIZAÇÃO DE MERGE.** Nenhum ambiente real foi acessado. Não foram executados `migrate:fresh`, `db:wipe`, `DROP`, restauração, SQL persistente ou testes de infraestrutura.

**Rastreamento:** [Issue #27 — revisão SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27). Evidências de PRs consultados:
- [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) — HEAD `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558` — migration tenant isolada, DRAFT.
- [PR #26](https://github.com/rafhael100/imagine-crm/pull/26) — HEAD `86ec918036e895f99cd6d1d98ca6469021846d2c` — verificador de CI, DRAFT.
- [PR #28](https://github.com/rafhael100/imagine-crm/pull/28) — HEAD `cf3ef4a7b441ed00cf50c8f20dbbe66c21874439` — allowlist de eventos push/PR, DRAFT.
- PRs #24 e #25 são testes/prova de CI inertes; não substituir decisão de integração dos PRs individuais por um merge do PR composto.
- [F0, PR #28](https://github.com/rafhael100/imagine-crm/actions/runs/42500888455) SUCCESS, **420 passed / 66 skipped / 2.308 assertions**; [PostgreSQL, PR #28](https://github.com/rafhael100/imagine-crm/actions/runs/42500888555) SUCCESS, **485 passed / 1 skipped / 3.117 assertions**. São evidências da HEAD do PR e evento `pull_request`; **não prova push real em DEV nem segurança de produção**.

## 1. Verificações estáticas efetivamente realizadas

| Objeto | Observado no GitHub | Limite da observação |
|---|---|---|
| Migration `database/experimental-migrations/tenant/2026_11_04_000001_create_crm_identity_organizations_memberships.php` | `up()`/`down()` chamam `guard()`; exige flag, ambiente `local/testing`, driver `pgsql`, host loopback, `DB_DATABASE` da allowlist, `current_database()`; tabelas IAM/tenant criadas e `crm_memberships` com `ENABLE/FORCE RLS`. | `guard()` não intercepta `migrate:fresh`, `db:wipe`, `DROP`, acesso superuser, nem prova que um banco de nome `imagine_crm_ci` é efêmero. |
| Workflow `.github/workflows/ci-postgresql-rls.yml` na HEAD do PR #28 | Serviço `postgres:16` em GitHub-hosted runner; `DB_USERNAME=postgres` durante DDL de CI; roles de aplicação/IAM/async criadas depois como `NOSUPERUSER`/`NOBYPASSRLS`; verificações antes das quatro migrações. | Superuser do serviço CI é aceitável apenas no escopo descartável; não reutilizar role, senha, workflow ou credenciais no banco real. ACLs reais e RLS produtivo ainda não homologados. |
| `scripts/ci/verify-ephemeral-postgres.php` na HEAD do PR #28 | Antes do SQL exige evento/branch/contexto restritos, serviço PostgreSQL 16 identificado, sem bind mounts, e compara `system_identifier` de `pg_control_system()` dentro/fora do contêiner. | É pré-verificação de CI, não barreira universal de DDL nem prova de isolamento se workflow, Docker ou privilégios forem modificados; volume anônimo/efêmero é distinto de ausência total de mounts. |
| `tests/Feature/AuthMigrationIsolationTest.php` | Cobre descoberta padrão, guardas, host/DB errado, basename registrado e rollback em schema temporário. | Não cobre de modo completo drift persistente, falha parcial, upgrades históricos de bases reais ou ponto de restauração. |
| Planos `P0-ARQ01-F03`, `P0-ARQ01-F04` | Banco PostgreSQL separado da aplicação é direção candidata; criptografia em trânsito, backup externo, WAL/PITR, storage independente e monitoramento são propostas. | Diretrizes preliminares, sem arquitetura produtiva homologada ou evidência de restauração. RPO 15 min / RTO 4 h são referências **não aprovadas**. |
| `IN-118` | Central de backup/exportação/importação por cliente já aprovada funcionalmente; não substitui backup interno operacional. | Nenhuma implementação de backup completo/restore por cliente foi validada nesta revisão. |
| `F1-09-async-ledger-integrity-recovery.md` | Provas de integridade e recuperação **simulada** do ledger sintético/inert. | Não demonstra restauração PostgreSQL física, WAL/PITR, retorno de anexos ou disponibilidade real. |

## 2. Matriz de riscos e critérios de aceite

**P0 — SEGDBA-01: destruição acidental em banco persistente — ABERTO.**
- **Cenário:** uma pessoa, workflow ou script com credenciais DDL executa `migrate:fresh`, `db:wipe`, `migrate:refresh`, `DROP` ou equivalente antes que `Migration::guard()` seja chamado.
- **Impacto:** perda e indisponibilidade potencial de dados de múltiplas empresas. Probabilidade **não quantificada** sem inventário dos ambientes.
- **Controles mínimos a comprovar:** separação entre credenciais runtime/DDL; privilégio mínimo e não reutilização de superuser de CI; aprovações humanas, isolamento da instância de laboratório, restrição de comandos/deploy e mecanismo de reversão/backup.
- **Evidência para o revisor:** matriz explícita de operação por ambiente; desenho de roles/ownership/privileges; testes negativos de perfil *sem SQL destrutivo*; prova em ambiente descartável somente com GO novo.
- **Critério de aceite:** nenhuma rotina de aplicação/CI pública pode ter credenciais de produção e comandos de destruição em banco persistente não podem ocorrer por simples comando de desenvolvimento.

**P0 — SEGDBA-02: identificação/isolamento insuficiente do banco — ABERTO.**
- **Cenário:** túnel, proxy, banco localhost durável, volume persistente ou configuração de runner com acesso externo pode simular indicadores de laboratório.
- **Controles:** delimitar serviço Docker descartável, rede, volume e ciclo de vida; proibir env/secrets reais em runners de PR; verificação do cluster + provenance; revisar permissões de alterar workflow; implementar barreira estrutural de rede/ACL.
- **Critério:** documentação de topologia/identidade + evidência verificável de impossibilidade de alcançar a produção a partir do CI. Nome do banco/localhost e `system_identifier` isolados não bastam.

**P1 — SEGDBA-03: autorização RLS e roles — ABERTO.**
- **Cenário:** role com `BYPASSRLS` ou owner/superuser contorna RLS; GUC `app.tenant_id` mutável vira falsa identidade.
- **Controles:** autenticação/sessão/MFA/membership independentemente do tenant enviado, `FORCE RLS`, role operacional sem owner/BYPASSRLS, privilégio mínimo por tabela/ação, contexto transacional e limpeza comprovada.
- **Critério:** matriz role×recurso×privilégio aprovada por responsável real, testes negativos cross-tenant e sem política `ALLOW` não aprovada.

**P1 — SEGDBA-04: migration ledger/rollback/drift — ABERTO.**
- **Estados exigidos no plano:** (A) schema vazio; (B) baseline anterior com basename registrado; (C) migration parcialmente executada; (D) tabelas presentes sem ledger; (E) policies/RLS divergentes; (F) rollback interrompido; (G) backup de versão anterior; (H) incompatibilidade de código e schema.
- **Controles:** inventário read-only da instância alvo antes de qualquer mutação, plano de compatibilidade versionado, bloqueio de `fresh/wipe` fora de ambiente descartável, backup restaurável, reconciliação sob aprovação.
- **Critério:** procedimento para cada estado sem presumir execução automática no banco persistente; recuperação ensaiada em cópia descartável somente após GO.

**P1 — SEGDBA-05: recuperação operacional/PITR ainda não demonstrada — ABERTO.**
- **Diretriz atual, não implementação:** PostgreSQL em ambiente independente do app; backup externo criptografado, base+WAL, monitoramento e eventual PITR; anexos e seus backups separados; cópia em outro domínio de falha.
- **Pontos pendentes:** ferramenta (candidata pgBackRest), política de retenção, credenciais de backup sem poder de apagar histórico, imutabilidade/versionamento, região/LGPD, custos, prova de restore e plano de resposta.
- **Critério:** manifesto de backup, checksum, WAL íntegro, cadeia e carimbo temporal, restore para instante escolhido em ambiente isolado, verificação de dados sintéticos e auditoria do teste; métricas RPO/RTO definidas e aprovadas posteriormente, sem prometer números não medidos.

**P1 — SEGDBA-06: evento do workflow push/PR — CORREÇÃO EXPERIMENTAL; NÃO HOMOLOGADO.**
- **Evidência:** PR #28 aplica allowlist restrita de `pull_request` em `main`/DEV e `push` na DEV exata; testes F0/PostgreSQL verdes com o evento PR.
- **Pendente:** observação futura de `push` real na DEV, exclusivamente se houver merge autorizado, e avaliação SEG do fluxo; não simular tal evidência por ser apenas unitário.
- **Critério:** zero ampliação para tags/eventos arbitrários/runner self-hosted, runbook do que fazer em erro, controles de branch e secrets.

**Gate SEGDBA-07: independência, responsável e aceitação de risco — PENDENTE.**
- **Não há revisor independente identificado.** O assistente e os comentários de revisão já existentes são parte do mesmo processo de autoria; não constituem segunda opinião independente.
- **Entregável necessário:** pessoa competente, declaração de escopo e independência quando requerida, riscos aceitos/mitigados com evidência e assinatura/registro, decisão separada da Direção.

## 3. Runbook proposto, sem execução: proteção e restauração

**Etapa R0 — Inventário sem mutação.** Registrar quem administra CI/DEV/produção; host/instância real, versões, redes, porta, base/schema, database role e ownership; classificação de dados (sintéticos/reais); dependências, volumes, localização, acessos e segredos. Nenhuma senha em issue/PR.

**Etapa R1 — Barreiras de acesso.** Fixar allowlist rede e identidade da instância, credenciais separadas (admin DDL, runtime RLS, leitura/auditoria, backup/restore), MFA no provedor, autorização humana para alterações. Sem liberar credencial de produção para Actions público.

**Etapa R2 — Política de migrações.** Inventariar histórico e drift por consultas read-only; preparar plano por versão, lock/maintenance, rollback e contingência. Bloquear `migrate:fresh`/`db:wipe` e similares nos ambientes persistentes por controle operacional e mínimo privilégio, não por `guard()` somente.

**Etapa R3 — Backup externo.** Escolher destino protegido fora da VPS; criptografia e rotação de chaves; base+WAL com monitoramento de atrasos, integridade, retenção e acesso mínimo. Proteger exclusão/alteração maliciosa com conta/credencial independente. Manter backup de anexos distinto do banco e testar coerência entre ambos.

**Etapa R4 — Ensaio de recuperação, apenas após GO explícito.** Em instância **efêmera, isolada, sem dados reais**, gerar dataset sintético conhecido; produzir backup; falha simulada; restaurar até um ponto escolhido e validar constraints, roles, RLS, tabelas, registros, logs, WAL, integridade de anexos fictícios e horário de corte. Medir RPO/RTO realizados; não comparar com metas não ratificadas como se fossem SLA.

**Etapa R5 — Homologação e operação.** Aprovar matriz de risco, revisor, alarmes, on-call, janelas, competência, custos e segregação; autorizar separadamente cada migração/merge/deploy conforme gates. Repetir teste de restore regularmente após autorização operacional.

## 4. Plano mínimo sugerido para a próxima aprovação técnica

1. **SEG-REC-01 — documental/read-only:** especificar matriz de roles, inventário de instâncias, pontos de controle e política de comandos proibidos. Sem qualquer código ou SQL executado.
2. **SEG-REC-02 — só com autorização futura:** scripts de teste **não destrutivos** (mock/environment fingerprint) e CI guardrails sob branch filha isolada; sem alterar PRs #23/#26/#28 ou base DEV.
3. **SEG-REC-03 — só com autorização futura distinta:** ensaio de restore sobre DB sintético descartável totalmente isolado, inclusive verificação de segurança e estimativa RPO/RTO. **Nunca** apontar para VPS persistente nem clientes.
4. **SEG-REC-04 — humana:** parecer de profissional SEG/DBA independente, se exigido, e decisão pela Direção sobre riscos residuais e merges individuais.

## 5. Governança e limites

Esta análise interna **não encerra** a Issue #27 nem aprova revisões especializadas, implementações e custos. Não declara que os controles propostos já existem. A suíte de CI comprova apenas o que foi executado. Não autoriza merge de PRs #23/#24/#25/#26/#28, mudança da DEV original ou `main`, VPS, produção, RLS de negócio, `ALLOW`, SQL ou acesso a dados reais. Gates CORE/SEG/AUT/INT/FIN/LGPD e demais pendências permanecem ABERTOS. A Central de Backup do cliente (IN-118) não substitui os backups operacionais e sua implementação futura possui governança própria.
