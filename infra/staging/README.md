# Imagine CRM — infraestrutura candidata de homologação privada

**Status: DRAFT / NO-MERGE / NO-DEPLOY / NO-GO de clientes.** Arquivos propostos para revisão e CI, não representam VPS configurada ou sistema operacional homologado.

## Componentes

- `infra/staging/compose.yaml`: PHP 8.4-FPM, PostgreSQL16 persistente **somente para futura homologação sintética**, Nginx que publica porta **apenas em `127.0.0.1:18080`**. Não há portas públicas do PHP-FPM ou PostgreSQL.
- `infra/staging/Dockerfile`: Laravel com dependências bloqueadas por `composer.lock`; nenhum comando de migration, seed ou ativação.
- `infra/staging/nginx.conf`: roteamento só por `public/index.php`; bloqueio das demais URLs PHP e arquivos ocultos.
- `.dockerignore`: impede que o contexto de build inclua `.env`, chaves, `.git`, cache e artefatos locais.
- `scripts/ci/check-private-staging-compose.py`: lê a configuração, injeta exclusivamente valores falsos e executa **`docker compose config`**, sem iniciar contêineres ou falar com bancos. A CI associada não tem credenciais nem autorização para acessar a Leaseweb.

## Fronteiras deliberadas

- `CRM_AUTH_EXPERIMENTAL=false` e todas as opções Asaas desativadas.
- Rede do banco e do PHP isolada; só o Nginx dispõe de conexão para o ingresso local.
- Valores para `CRM_STAGING_DB_PASSWORD` e `CRM_STAGING_APP_KEY` são **obrigatórios** e devem vir de cofre/variáveis fora do GitHub. Jamais colocar valores reais em PR, comentário, screenshot ou arquivo de configuração versionado.
- A aplicação tem apenas o healthcheck /up e os componentes permitidos pela branch. **Não há login, trial, leads ou checkout operacional** neste recorte.
- Postgres tem volume persistente e **não pode** ser confundido com banco descartável de GitHub Actions. Nenhum teste de migração de CI deve ser apontado para ele.
- Sem TLS ou domínio público neste recorte. Para inspeção posterior, a porta localhost pode ser visualizada via túnel SSH autenticado, quando SSH do operador e gate de infraestrutura forem aprovados.
- Há um **smoke test Docker Compose estritamente efêmero** dentro do runner do GitHub Actions: ele cria contêineres com credenciais fictícias, valida `/up` e rotas de negócio 404 e destrói os volumes. Isso **não é deploy** nem acesso à VPS. O runner também executa uma recuperação `pg_dump`/`pg_restore` em banco de destino diferente, somente com dados fictícios, e remove o arquivo local ao término.

## Critérios antes de homologação em VPS

1. Revisão de redes, hardening de imagens, autenticação na frente do serviço, TLS e regras de firewall; testar acesso externo NEGADO.
2. Segredos do cofre, rotação e revogação; sem chaves Asaas reais até gate financeiro.
3. Postgres com usuário sem privilégios elevados, migrações aprovadas e não destrutivas; restaurar backup em ambiente de teste.
4. Identidade, memberships, RLS, consentimento e fluxo de cadastro com testes reais e auditoria.
5. Revisão independente SEG/DBA **#27**, CORE/LGPD **#259**, UX **#416**; condições para abrir #519 como homologação privada do site.

A CI prova **sintaxe e fronteiras estáticas**, não o funcionamento da infraestrutura, da VPS ou de um SaaS. Nenhum passo de produção é autorizado.

## Recuperação verificada em CI — CRM-RECOVERY-01

A rotina `scripts/ci/rehearse-crm-private-restore.sh` exige `GITHUB_ACTIONS=true`, repositório esperado, marca `CRM_RECOVERY_LAB=synthetic-only` e banco `imagine_crm_staging` **vazio** no contêiner temporário PostgreSQL16. Ela executa o arquivo fictício `tests/Labs/recovery/private-postgres-backup-seed-ci.sql`, cria um arquivo custom-format real com `pg_dump` e o restaura no banco `imagine_crm_ci_restore` de **outra instância PostgreSQL16**, executada em um segundo contêiner com `--network none`, sem portas ou volumes compartilhados, sem sobrescrever o banco original.

O teste compara seis registros de duas empresas (leads, tarefas e histórico), confirma três tabelas `FORCE RLS`, políticas preservadas, grants restritos e leituras limitadas por empresa; sem tenant definido, o papel restrito deve ver zero registros. O arquivo local é apagado e os contêineres/volumes são destruídos no runner. **Não é backup permanente, replicação, disaster recovery real nem recuperação da base operacional do CRM.** A instância de restauração é completamente descartável e destruída ao final da CI. Essas atividades continuam exigindo plano aprovado, criptografia, guarda de cópias, teste de restore separado, retenção e aprovação SEG/DBA antes da VPS.
