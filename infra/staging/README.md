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
- Não há `docker compose up` ou deploy na CI. A configuração será inspecionada antes de qualquer uso em servidor.

## Critérios antes de homologação em VPS

1. Revisão de redes, hardening de imagens, autenticação na frente do serviço, TLS e regras de firewall; testar acesso externo NEGADO.
2. Segredos do cofre, rotação e revogação; sem chaves Asaas reais até gate financeiro.
3. Postgres com usuário sem privilégios elevados, migrações aprovadas e não destrutivas; restaurar backup em ambiente de teste.
4. Identidade, memberships, RLS, consentimento e fluxo de cadastro com testes reais e auditoria.
5. Revisão independente SEG/DBA **#27**, CORE/LGPD **#259**, UX **#416**; condições para abrir #519 como homologação privada do site.

A CI prova **sintaxe e fronteiras estáticas**, não o funcionamento da infraestrutura, da VPS ou de um SaaS. Nenhum passo de produção é autorizado.
