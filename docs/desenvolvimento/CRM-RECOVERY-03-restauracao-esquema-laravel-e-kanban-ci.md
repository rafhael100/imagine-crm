# CRM-RECOVERY-03 — Restore real do esquema Laravel migrado e dados sintéticos do CRM

**Status DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-ACCESS / NO-CHARGE**

## O problema da recuperação anterior

As PRs [#570](https://github.com/rafhael100/imagine-crm/pull/570) e [#571](https://github.com/rafhael100/imagine-crm/pull/571) provaram `pg_dump` e `pg_restore` em PostgreSQL16 independentes, mas usaram três tabelas artificiais criadas **antes** das migrations da aplicação Laravel. A [#574](https://github.com/rafhael100/imagine-crm/pull/574) executou a migration real e verificou a role não privilegiada; a [#580](https://github.com/rafhael100/imagine-crm/pull/580) conectou o painel Laravel a tabelas reais de laboratório com RLS. Até então, **a combinação pós-migração, incluindo registros e grants do novo Kanban, não tinha sido restaurada em instância independente.**

## Entrega executável

Novo roteiro `scripts/ci/rehearse-crm-full-schema-restore.sh`, obrigatório no workflow privado *após* a migration Laravel, seed do Kanban e testes HTTP:

- Exige execução em GitHub Actions do próprio repositório, `CRM_FULL_RESTORE_LAB=synthetic-only`, PostgreSQL16 no banco fixo `imagine_crm_staging` e contagens **exatas** do ambiente fictício. Não aceita IP, nome de DB, tabela ou arquivo de backup externo como parâmetros.
- Compara **24 registros** do banco de origem: duas identidades fictícias, duas organizações, duas memberships migradas, seis registros do experimento anterior (dois leads, duas tarefas, dois eventos), sete oportunidades de exemplo, quatro tarefas de exemplo e uma linha da tabela Laravel `migrations`. Não lê, grava ou imprime PII.
- Verifica seis tabelas `ENABLE+FORCE RLS`: `crm_memberships`, `crm_ci_restore_{leads,tasks,events}`, `crm_ci_demo_{opportunities,tasks}`.
- Produz um `pg_dump -Fc` **real do banco completo** em diretório temporário exclusivo do runner, inspeciona catálogo e restaura em outro processo PostgreSQL16 com `docker run --network none`, sem portas, volumes compartilhados ou upload de artefatos.
- Reconstitui somente as roles globais necessárias como `NOLOGIN/NOSUPERUSER/NOBYPASSRLS` antes do `pg_restore --single-transaction --exit-on-error`. Nenhuma senha da instância de origem é copiada para as roles reconstruídas.
- Compara os 24 registros e propriedades FORCE RLS **antes/depois da restauração**, confirma o registro da migration Laravel e testa a leitura pelo papel de aplicativo `crm_staging_app`: empresa A = `1 membership/6 oportunidades/3 tarefas`, B = `1/1/1`, sem tenant = `0/0/0`.
- Testa ainda `crm_ci_restore_reader`: A/B = `1 lead/1 task/1 event` cada, sem tenant = `0/0/0`; confirma que `crm_staging_app` restaurada **não é proprietária, não pode efetuar INSERT/UPDATE/DELETE e não pode fazer login** na nova instância isolada.
- O script apaga o dump e destrói o contêiner restaurado com `trap` mesmo após falha; o workflow continua apagando todos os volumes efêmeros do CI.

## Limitações

É uma prova de recuperação **do esquema Laravel candidato e de dados sintéticos**, em dois contêineres no **mesmo GitHub-hosted runner**, não prova recuperação após falha de hardware em região independente, disponibilidade, PITR/WAL, RPO/RTO, criptografia, offsite, retenção, chave de app, autorização IAM real, sessão MFA, pagamento, emissão fiscal nem backup de dados reais.

O fato de as políticas RLS estarem preservadas **não autentica o usuário**; `SET LOCAL app.tenant_id` não é uma credencial. `crm_staging_app` continua sem papéis operacionais aprovados; `DenyByDefaultAuthorizer` continua bloqueando ações. Nenhum contato com VPS Leaseweb, Asaas ou clientes.

Gates ainda abertos: [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416), [#564](https://github.com/rafhael100/imagine-crm/issues/564) e [lançamento #557](https://github.com/rafhael100/imagine-crm/issues/557).
