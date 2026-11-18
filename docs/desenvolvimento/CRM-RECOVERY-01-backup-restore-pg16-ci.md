# CRM-RECOVERY-01 — Teste de backup/restauração PostgreSQL16 no CI

Status: **DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-GRANT**.

## Escopo real da entrega

A [PR #568](https://github.com/rafhael100/imagine-crm/pull/568) já provou que o Compose privado inicia em um runner GitHub e responde HTTP sem expor as rotas de negócio. Ainda não havia prova de que um backup PostgreSQL pudesse ser **restaurado em outro banco** com relacionamentos, auditoria e isolamento mantidos.

O workflow de smoke privado agora executa `scripts/ci/rehearse-crm-private-restore.sh` **depois** do HTTP, sob os seguintes limites:

- Não permite execução fora do runner GitHub Actions do repositório Imagine CRM; exige `CRM_RECOVERY_LAB=synthetic-only`, credenciais descartáveis e configuração de redes privadas validada.
- Verifica que o banco `imagine_crm_staging` está vazio, é PostgreSQL16 e que o banco destino `imagine_crm_ci_restore` ainda não existe, antes de importar qualquer SQL de teste.
- Cria apenas seis registros **fictícios** divididos em duas empresas em `tests/Labs/recovery/private-postgres-backup-seed-ci.sql`: dois leads, duas tarefas e dois eventos de auditoria, com FK empresa+lead/tarefa e três políticas FORCE RLS.
- Executa `pg_dump --format=custom`, lê o catálogo do arquivo e executa `pg_restore --single-transaction --exit-on-error` em **outro banco de outra instância PostgreSQL16**, em segundo contêiner com rede `none`, sem portas publicadas, volumes compartilhados ou credenciais reais.
- Faz comparação exata dos seis registros do banco de origem contra o restaurado e compara o status `ENABLE+FORCE RLS` das três tabelas.
- Após a restauração, a role sintética `crm_ci_restore_reader`, sem SUPERUSER/BYPASSRLS e sem INSERT/DELETE, vê um lead/tarefa/evento apenas no tenant A ou B e **zero** sem tenant definido.
- O arquivo é eliminado do diretório temporário e os contêineres e volumes temporários são apagados ao final. Não gera artefato de backup para download nem compartilha os dados fictícios fora do runner.

## Limites e pendências

Isto **não** restaura a base real do CRM, não verifica retenção, criptografia, perda de um servidor, RPO/RTO, backups off-site, permissões de objeto em outra instância, credenciais e segredos, dados reais ou conexão à Leaseweb. O restore em outra **instância PostgreSQL16 efêmera com rede desligada** demonstra melhor separação de falhas, mas ainda é uma prova intermediária. Não testa restauração após perda de máquina, snapshots/offsite, criptografia, RPO/RTO ou recuperação de base real.

Antes de liberar o SaaS continuam necessárias as decisões de IAM/MFA, autorização segura, migração persistente não destrutiva, RLS operacional revisado, Asaas Sandbox autenticado, consentimento LGPD, fluxos reais, monitoramento, restore em instância separada e pareceres independentes [#27](https://github.com/rafhael100/imagine-crm/issues/27), [#259](https://github.com/rafhael100/imagine-crm/issues/259), [#416](https://github.com/rafhael100/imagine-crm/issues/416), [#564](https://github.com/rafhael100/imagine-crm/issues/564) e gate final [#557](https://github.com/rafhael100/imagine-crm/issues/557).

O status técnico deve ser documentado pela **HEAD exata** que tiver todos os workflows verdes. Sem merge/deploy automático.
