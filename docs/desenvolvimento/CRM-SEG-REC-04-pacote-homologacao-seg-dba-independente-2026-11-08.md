# CRM Imagine — SEG-REC-04 v0.1
## Pacote de homologação para revisão independente SEG/DBA e decisão da Direção

**Data:** 08/11/2026 · **Estado:** PACOTE DOCUMENTAL PREPARADO, **SEM PARECER INDEPENDENTE** · **Vinculação:** [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27).

**Autorização recebida:** organizar e documentar o processo de revisão especializada do SEG-REC-04. **Não foi contratado, designado, acionado ou representado nenhum especialista externo**; não há autorização implícita de contratação/custo, implementação, merge, configuração de VPS, conexão com banco, deploy, `ALLOW` ou fechamento de gates.

**Importante:** o assistente que participou do planejamento/código e da análise de riscos **não é revisor independente**. Este pacote é apenas a pauta que será entregue a profissionais competentes, preferencialmente com separação entre segurança de aplicação/CI e PostgreSQL/recuperação.

## 1. Linha de base congelada para a análise

| Item | Identificação | Situação na verificação |
|---|---|---|
| [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) | `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558` | DRAFT, sem merge — migrations tenant |
| [PR #24](https://github.com/rafhael100/imagine-crm/pull/24) | `072f369542d0667056627f60f1f12d07173d883b` | DRAFT, sem merge — provas S01 negativas |
| [PR #25](https://github.com/rafhael100/imagine-crm/pull/25) | `ffefef6d5de1ecc1cdf7b863bd3bb2d8ad35e7d7` | DRAFT, **veículo de CI combinada: NO MERGE** |
| [PR #26](https://github.com/rafhael100/imagine-crm/pull/26) | `86ec918036e895f99cd6d1d98ca6469021846d2c` | DRAFT, sem merge — preflight CI |
| [PR #28](https://github.com/rafhael100/imagine-crm/pull/28) | `cf3ef4a7b441ed00cf50c8f20dbbe66c21874439` | DRAFT, sem merge — allowlist evento/ref |
| [PR #29](https://github.com/rafhael100/imagine-crm/pull/29) | `1a7e87351bcde7fc9461fbba55f3c931073c8d9b` | DRAFT, sem merge — testes negativos mock |
| [PR #30](https://github.com/rafhael100/imagine-crm/pull/30) | `ffd1993f600c3b91ee87d753052d4df2026c2c9c` | DRAFT, sem merge — laboratório PITR |
| Branch DEV de destino | `dev/fast-track-crm-2026-11-04` | Não alterada por SEG-REC-01..04; verificar HEAD novamente no futuro |
| `main` / VPS | Fora do escopo | Não alteradas por este trabalho |

**Genealogia relevante:** #30 deriva de #29 → #28 → #26 → #23. O diff total da última PR inclui dependências anteriores. **Aprovação de uma PR acumulada não implica aprovação das anteriores**, nem autoriza atalhos de merge. #24/#25 possuem provas próprias e estados próprios.

### Evidências automatizadas, sem extrapolação

- [PR #30 — PITR, run 42506448267](https://github.com/rafhael100/imagine-crm/actions/runs/42506448267): **SUCCESS** na HEAD `ffd1993f...`; recuperou fisicamente, em GitHub-hosted runner descartável, dois registros sintéticos anteriores ao restore point `seg_rec_03_cut` e excluiu o registro posterior. Retorno ao estado promovido e limpeza comprovados em log.
- [PR #30 — F0, run 42506448222](https://github.com/rafhael100/imagine-crm/actions/runs/42506448222): **SUCCESS**, **438 passed / 66 skipped / 2.540 assertions**.
- [PR #30 — PostgreSQL/RLS, run 42506448265](https://github.com/rafhael100/imagine-crm/actions/runs/42506448265): **SUCCESS**, **503 passed / 1 skipped / 3.349 assertions**.
- [Primeiro ensaio PITR, run 42506336621](https://github.com/rafhael100/imagine-crm/actions/runs/42506336621): **FAIL inicial** por regra HBA de replicação ausente. Foi corrigido na branch isolada, com `host replication postgres samenet scram-sha-256` na rede interna, antes do run bem-sucedido.
- **Limite de evidência:** nenhum resultado acima comprova segurança de produção, backups externos, disponibilidade real, RPO/RTO operacional, recuperação integrada de anexos/filas ou autorização de negócio.

## 2. Especialistas e independência — campos ainda em branco

| Função | Competência a verificar | Responsável | Estado |
|---|---|---|---|
| Revisor **SEG** | AppSec (Laravel/IAM/RLS), GitHub Actions público, Docker/CI, ACL, identidade, segredos e rede | **NÃO DESIGNADO** | Pendente |
| Revisor **DBA/REC** | PostgreSQL 16, ownership/grants, migrations, WAL, PITR, backup externo e ensaios de restore | **NÃO DESIGNADO** | Pendente |
| Aprovador empresarial | Direção Imagine, aceita custos, prazos e riscos residuais explícitos | Direção, decisão posterior | Sem GO técnico |

Antes de considerar qualquer parecer como independente, registrar o nome/identificador do profissional, competência técnica, escopo, vínculo com a autoria dos PRs, conflitos de interesse, acesso a evidências, data e assinatura/identificação do comentário. **Não inventar independência**: caso a pessoa tenha participado da autoria, registrar sua revisão como *interna*, não como segunda opinião segregada.

**Acesso mínimo ao revisor:** leitura do repositório e dos logs sanitizados; não fornecer segredos de produção, banco de clientes, dados reais, token de escrita ou acesso VPS sem aprovação contratual/técnica específica. Como o repositório está público nesta fase, não adicionar credenciais ou dados pessoais ao histórico da issue/documentos.

## 3. Questões obrigatórias do parecer SEG

| ID | Pergunta e ameaça | Evidência necessária para resolução | Estado |
|---|---|---|---|
| SEG-01 / P0 | `migrate:fresh`, `db:wipe`, `migrate:refresh`, `DROP` ou SQL administrativo passam antes do `guard()` de `up/down`. Que camada independente realmente impede perda em banco durável? | política de operações por ambiente; credenciais DDL/runtime distintas; privilégios *efetivos*; desenho de rede que impede acesso do CI a DB real; testes negativos com alvo mock e, só se autorizado, ambiente descartável | **ABERTO** |
| SEG-02 / P0 | Um runner, túnel, container, volume ou `PATH` alterado poderia enganar o preflight e atingir uma instância durável? | prova de isolamento do CI e rede; ausência de secrets reais; revisão do workflow, triggers, permissões de PR, Docker/socket e origem das imagens; controle de modificação de CI | **ABERTO** |
| SEG-03 / P1 | As roles runtime/IAM/async têm somente o mínimo necessário? `FORCE RLS`, policy e GUC `app.tenant_id` não são autenticação. | matriz role×tabela×operação×campo, owners e grants herdados, `NOBYPASSRLS`, `SECURITY DEFINER`, `search_path`, limpeza do contexto transacional, testes cross-tenant | **ABERTO** |
| SEG-04 / P1 | O allowlist `push`/PR de #28 é suficiente para os eventos autorizados e nega branches/eventos indevidos? | revisão de `GITHUB_REF`, `GITHUB_BASE_REF`, `GITHUB_HEAD_REF` e proteção de branch; considerar PR de fork, atualização de workflow, permissions e runners; evidência de push DEV real apenas após GO | **CORREÇÃO EM PR; ABERTO** |
| SEG-05 / P1 | Sanitização de evidências e código público evitam divulgação de credenciais/configurações sensíveis? | análise de secret scanning/histórico, políticas de retenção de Actions, logs, rotatividade de tokens e proteção do repositório antes de eventual retorno ao modo privado | **ABERTO** |

## 4. Questões obrigatórias do parecer DBA/REC

| ID | Pergunta e ameaça | Evidência necessária para resolução | Estado |
|---|---|---|---|
| DBA-01 / P0 | Quem pode destruir, alterar ownership, roles e policies em um DB persistente? A aplicação tem owner ou SUPERUSER/BYPASSRLS? | dump sanitizado de roles/grants/ownership de *ambiente sintético autorizado*, desenho de DDL temporário vs runtime; nenhum comando destrutivo fora do laboratório | **ABERTO** |
| DBA-02 / P1 | Como migrar versões anteriores sem erro de ledger, basename duplicado, schema parcial ou drift em policies e tabelas? | matriz de cenários, plano forward-fix/rollback versionado, pré-checagens read-only, backup restaurável e gates de mudança | **ABERTO** |
| DBA-03 / P1 | A prova `pg_basebackup`+WAL local pode evoluir para backups externos, protegidos e recuperáveis? | proposta de ferramenta (pgBackRest ou equivalente, ainda não homologada), base backup+WAL, destino externo, integridade, retenção, criptografia, proteção contra exclusão, conta/roles distintas e custo | **ABERTO** |
| DBA-04 / P1 | Como restaurar banco e anexos em um ponto consistente, evitando duplicações em leads, filas e integrações? | checkpoints e manifestos correlacionáveis, reconciliação idempotente, política de reprocessamento e trilha de auditoria, simulação futura integrada autorizada | **ABERTO** |
| DBA-05 / P1 | Quais RPO/RTO serão contratados e medidos? | dimensionamento real, prova de restore fora da máquina de origem, medição sob carga adequada e aceite de perdas/tempo. **RPO 15 min e RTO 4 h continuam referências NÃO APROVADAS** | **ABERTO** |
| DBA-06 / P1 | A regra HBA `samenet` do laboratório é restrita ao seu contexto e não contaminaria instalações reais? | revisão do script #30, confirmação de redes Docker `--internal`, volumes descartáveis e ausência de portas publicadas; exigir configuração diferente e homologada na infraestrutura final | **ABERTO** |

## 5. Entregáveis exigidos do especialista

**Documento SEG/DBA-REV-v1**, separado por especialidade, contendo:

1. Identificação do profissional, qualificação, independência/conflitos e escopo.
2. SHAs e PRs **exatos** examinados, evidências e limites de acesso; indicar o que não foi visto/testado.
3. Por achado: ID, severidade, cenário reproduzível, impacto, probabilidade (se estimável), evidência, ação de mitigação, responsável, prazo e resultado de reteste.
4. Status **PASS / PASS CONDICIONAL / REPROVADO / NÃO AVALIADO** por área; `NÃO AVALIADO` jamais deve ser tratado como `PASS`.
5. Matriz de privilégios e arquitetura proposta para banco/CI reais, com decisões de segurança e recuperação.
6. Plano de backup/restore que diferencie cópias internas operacionais e [IN-118](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/planejamento/IN-118-central-backup-portabilidade-dados.md) (backup/exportação por cliente).
7. Riscos residuais que a Direção precisará aceitar **explicitamente**. Parecer técnico não substitui autorização empresarial de merge/deploy.
8. Assinatura/identificação verificável, data e link da evidência original; não há garantia de revisão externa simplesmente pela existência desta pauta.

**Critérios mínimos para reconsiderar qualquer PR para integração na DEV:** P0 resolvidos ou decisão formal de risco sustentada por evidência competente; P1 avaliados com mitigação/aceite explícito, testes na HEAD exata, plano de recuperação, revisão de conflito/dependências, e **autorização separada por PR pela Direção**. PR #25 e os PRs agregadores não são atalhos para integrar dependências ainda sem aceite. Não há qualquer autorização automática de `main` ou VPS.

## 6. Formulário operacional de decisão — PREENCHIMENTO FUTURO

| Campo | Estado no momento |
|---|---|
| Revisor SEG / data / resultado | **PENDENTE / NÃO DESIGNADO** |
| Revisor DBA / data / resultado | **PENDENTE / NÃO DESIGNADO** |
| SEGDBA-01 / proteção comandos destrutivos | **ABERTO P0** |
| SEGDBA-02 / isolamento real do alvo | **ABERTO P0** |
| Matriz roles/ACL e migrations | **NÃO HOMOLOGADA** |
| Backup externo, WAL, imutabilidade e restore isolado | **NÃO HOMOLOGADOS** |
| Ensaio PITR sintético local em CI | **SUCESSO COMPROVADO no SHA do PR #30** |
| Recuperação banco+anexos+filas | **NÃO TESTADA** |
| Métricas RPO/RTO operacionais | **NÃO APROVADAS** |
| Revisão de riscos residuais | **PENDENTE** |
| Decisão da Direção sobre PRs individuais | **NÃO CONCEDIDA** |
| Autorização VPS / produção / `ALLOW` | **NÃO CONCEDIDA** |

O resultado futuro da revisão deve ser registrado na [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27), com link ao parecer emitido pelo profissional identificado. **A issue não pode ser fechada apenas com comentários do autor/assistente ou CI verde.**

## 7. Próximas ações organizacionais sem execução técnica

1. **Identificar/selecionar** revisor(es) SEG e DBA/REC com experiência e independência adequadas; decidir se a contratação/avaliação terá custo e solicitar aprovação financeira se houver. Não atribuir sem anuência do profissional.
2. Disponibilizar somente este pacote, SEG-REC-01, PRs e resultados de CI sanitizados. Conceder acesso adicional **somente** se necessário e após aprovação.
3. Solicitar os dois pareceres, reconciliar conclusões contraditórias e vincular os resultados à Issue #27. Não transformar comentários de autoria em aprovação externa.
4. Reavaliar riscos e decisões de integração **um PR por vez** após os pareceres; manter `main` e VPS fora de alcance nesta fase.

**Status final desta versão:** SEG-REC-04 **PACOTE DOCUMENTAL PREPARADO, REVISÃO INDEPENDENTE NÃO INICIADA**. Nenhum parecer foi fabricado, nenhuma pessoa foi contatada ou atribuída. Todos os gates CORE/SEG/AUT/INT/LGPD/FIN/CON permanecem **abertos**.
