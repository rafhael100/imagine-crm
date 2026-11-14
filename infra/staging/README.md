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
- Valores para `CRM_STAGING_DB_PASSWORD`, `CRM_STAGING_APP_DB_PASSWORD` e `CRM_STAGING_APP_KEY` são **obrigatórios** e devem vir de cofre/variáveis fora do GitHub. Jamais colocar valores reais em PR, comentário, screenshot ou arquivo de configuração versionado.
- A aplicação tem `/up` e uma **página comercial read-only em `GET /` apenas quando `APP_ENV=staging` e `CRM_STAGING_MARKETING_PREVIEW=true`**, acessíveis exclusivamente em `127.0.0.1:18080` na configuração candidata. O conteúdo é apresentado em PT/EN/ES e usa preços ilustrativos do catálogo interno, **não homologados**. A rota usa `Cache-Control: no-store`, `X-Robots-Tag: noindex`, CSP sem scripts/formulários e não recebe nem armazena dados. **Não há login, trial, leads ou checkout operacional** neste recorte.
- O contêiner PostgreSQL inicializa o administrador `crm_staging` (somente bootstrap/migração) e um usuário de aplicação separado `crm_staging_app`, **NOSUPERUSER/NOBYPASSRLS/NOCREATEDB/NOCREATEROLE**, com senha própria e sem acesso a DDL/DML de negócios por padrão. A aplicação utiliza **apenas** `crm_staging_app` na conexão padrão. A concessão `SELECT` em memberships usada no teste não é executada automaticamente na VPS: exige desenho IAM/SEG independente.
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

## Ensaio de migração e isolamento real — CRM-STG-SCHEMA-01

O workflow privado, exclusivamente no runner GitHub, gera **duas senhas independentes e descartáveis**: administrador de banco e usuário de aplicação, além da `APP_KEY`. O arquivo `infra/staging/01-crm-staging-app-role.sh` é executado pelo bootstrap do PostgreSQL apenas no primeiro início do volume e cria o papel de aplicação sem privilégios de administração. Esse papel não possui autorização de negócio e não é suficiente para login ou acesso a clientes reais.

O roteiro `scripts/ci/rehearse-crm-private-schema.sh` só roda após verificar o banco de testes e as flags de CI. Ele executa `php artisan migrate --force` sob **credencial administrativa apenas no subprocesso de migração**, nunca no PHP-FPM padrão, verifica que a migração é idempotente e que `crm_memberships` está `ENABLE+FORCE RLS`. Insere dois tenants e duas memberships *fictícios*; a grant temporária somente de leitura da tabela de memberships é criada apenas na prova CI. Por fim, `scripts/ci/check-staging-runtime-schema.php` executa o **bootstrap Laravel real** com o papel `crm_staging_app` e demonstra leitura de uma membership de cada tenant, com zero visibilidade sem tenant e sem `CREATE/INSERT/UPDATE/DELETE`.

**Limites:** a credencial de bootstrap `crm_staging` é superusuária do PostgreSQL no contêiner candidato e não pode ser usada por um processo de aplicação ou exposta via rota. O usuário `crm_staging_app` ainda não possui os grants de `SELECT FOR UPDATE` necessários para autenticação operacional, nem uma identidade de usuário autenticada e governança aprovada de roles. A migração é uma candidata experimental, **não** uma autorização de aplicação em banco da VPS. Antes de homologação real: segregação de papéis revisada por SEG/DBA, gestão externa de segredos, migrations aprovadas, IAM/MFA, consentimento LGPD e restauração de backup em infraestrutura separada.

## Prévia comercial privada CRM-STG-MKT-01

A página de apresentação do Imagine CRM está implementada em Laravel Blade (não é apenas HTML offline), com identidade visual grafite/laranja `#F9941D`, layout responsivo e troca de idioma por links locais `?lang=pt|en|es` (🇧🇷, 🇺🇸, 🇪🇸). Os planos são obtidos diretamente de `CommercialPlanReferenceCatalog`, conservando `price_homologated=false` e `can_checkout=false`. Uma mensagem visível avisa que assinatura, trial e pagamentos permanecem indisponíveis.

A condição de publicação é dupla e explícita: **`APP_ENV=staging` E `CRM_STAGING_MARKETING_PREVIEW=true`**. `production`, `testing` e demais ambientes não registram `GET /`. O health `/up` permanece. A CI sobe os contêineres descartáveis, verifica `GET /` em três idiomas, preços de referência, headers `noindex`/CSP, fallback de parâmetro inválido e `/signup`, `/login`, `/checkout`, `/api/trial` respondendo 404. Nenhuma versão foi implantada na VPS.

A página não integra Asaas, não identifica usuários, não capta leads, não possui formulários e **não deve ser divulgada como site comercial publicado** antes das aprovações UX, comercial, segurança e LGPD. A opção do Compose é somente para inspeção privada; nunca transportar a flag automaticamente para produção.

## CRM-STG-DEMO-01 — Painel Laravel navegável com dados fictícios

A homepage privada possui agora um link funcional para `GET /demo?lang=pt|en|es`. O painel usa uma nova view Blade responsiva com sidebar, âncoras navegáveis de funil e tarefas, indicadores de registros sintéticos, quatro colunas Kanban e cartões `EXEMPLO_*`/`TASK_DEMO_*` **codificados no próprio aplicativo**. A página **não consulta PostgreSQL, Asaas, usuários, contatos ou dados pessoais**, não tem formulários, scripts, comandos nem ações de escrita. Os três idiomas compartilham os mesmos registros fictícios e exibem avisos de prévia.

O endpoint é registrado **exatamente sob a mesma condição de staging** da homepage, não existe em produção e responde somente a GET; `POST /demo` deve retornar 405. O workflow privado valida links entre páginas, PT/EN/ES, dados sintéticos, ausência de formulário/script, headers de segurança e resposta negada ao POST. Nada é publicado na VPS. Esta é uma demonstração **visual**, não um dashboard integrado a dados reais ou autenticação comercial.
