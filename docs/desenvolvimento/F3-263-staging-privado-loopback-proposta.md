# CRM F3-263 — Candidato de staging privado sem dados reais (DRAFT / NO-GO)

**Objetivo:** preparar para revisão técnica um pacote de contêineres do CRM que *poderá*, somente após gate de infraestrutura, servir a aplicação Laravel F0 em localhost para verificar o health-check. O pacote **não instala serviços na VPS ou em produção**. A partir de F3-267, uma CI isolada e temporária pode construir e executar contêineres somente no runner descartável do GitHub para testar `/up` e negativas de rotas; não há deploy nem serviço permanente. Nenhuma URL pública, sessão de cliente, CNPJ, tenant, credencial, migração, API, e-mail ou WhatsApp é habilitada.

## Arquivos

- infra/staging/Dockerfile — pacote PHP 8.4 FPM com extensões SQLite e PGSQL; Composer --no-dev; fontes explicitamente selecionadas para **não copiar .env, testes, scripts ou migrations experimentais de IAM**.
- infra/staging/compose.yaml — aplicação PHP-FPM e Nginx, rede interna exclusiva para PHP-FPM e segunda bridge restrita a Nginx/loopback, **sem bind mounts do host** ou serviço PostgreSQL; Nginx associado exclusivamente a **127.0.0.1:18080** no host, sem porta pública; auth experimental false, SQLite :memory:, cache, fila, sessão e mailer locais.
- infra/staging/Dockerfile.web — artefato Nginx candidato: copia somente nginx.conf e public/ do mesmo checkout em vez de consumir diretórios montados do host.
- infra/staging/nginx.conf — único entrypoint FastCGI index.php, proibição de outros PHP e dotfiles, headers de segurança e limite de request curto.
- tests/Unit/StagingSmokeBlueprintContractTest.php — tripwires de segurança de fonte: portas, containers, conexões, serviços e migrações.
- scripts/ci/verify-staging-rendered-compose.php — política fail-closed que recebe o JSON normalizado do Compose e recusa ligação do PHP à rede de entrada, NAT na bridge de loopback, redes extras ou públicas, serviços extras, publicação em 0.0.0.0, mounts do host, configuração IAM/DB/SMTP não fictícia, usuários privilegiados, adições de capabilities, políticas seccomp enfraquecidas, comandos/entrypoints substituídos, imagens não declaradas e contextos de build fora do repositório.
- tests/Unit/StagingRenderedComposePolicyTest.php — cenários negativos para alterações adversariais de portas, volumes, segredos, banco, flag IAM e permissões.
- .github/workflows/ci-crm-private-staging-blueprint.yml — **apenas** docker compose config --quiet/--format json + política PHP e leitura do GitHub; não executa compose up, build, pull, run nem deploy.

## F3-265 — fechamento adicional de build e rede, com prova CLI negativa

A política que recebe o **JSON normalizado do Docker Compose** passou a rejeitar:
- qualquer chave adicional na configuração de build (incluindo additional_contexts, target, dockerfile_inline, cache_from, pull e tags), e não apenas parâmetros args/secrets anteriores;
- configurações adicionais na rede crm_private, como attachable, external, driver_opts, driver alternativo ou nome inesperado;
- serviços malformados ou não representados por um mapa de configuração.

Os testes PHPUnit de StagingRenderedComposePolicyTest verificam alterações adversariais **com valores exclusivamente fictícios** e também executam o **validador PHP como processo CLI separado**: modelo válido retorna 0; DB_HOST extra, JSON malformado e carga acima do limite retornam erro; rejeições não imprimem o conteúdo enviado.

**Isso continua sendo uma prova estática de configuração e da CLI de CI**, sem imagem executada, sem daemon Docker, sem acesso à VPS e sem liberação operacional. O PR continua em rascunho.

## F3-266 — compatibilidade PHP e isolamento do contexto de build

**Achado bloqueante antes de um build:** o arquivo composer.lock fixa pacotes de produção (incluindo laravel/framework) que exigem ext-mbstring. O Dockerfile PHP tinha libonig-dev, mas não compilava mbstring. Foi corrigido para incluir mbstring em docker-php-ext-install. O novo teste StagingPhpRuntimePlatformContractTest compara extensões ext-* exigidas pelo lock de produção com as compiladas explicitamente e uma lista fechada de módulos esperados na imagem base. Alterações de dependências ou Dockerfile reexecutam a política. **O teste estático não substitui php -m ou composer check-platform-reqs --no-dev na imagem realmente construída**.

**Outro risco identificado:** COPY explícito evita inserir segredos na imagem, mas não impede que arquivos extras do checkout sejam enviados ao builder como contexto. Foram criados ignore-files **específicos por Dockerfile**:
- infra/staging/Dockerfile.dockerignore: somente Composer, artisan e fontes de app/config/bootstrap/routes/resources/public/migrations normais;
- infra/staging/Dockerfile.web.dockerignore: somente public/ e nginx.conf revisado.

Os dois começam negando todos os arquivos, reabilitam entradas estritamente necessárias e no final voltam a excluir .env, chaves, certificados, bancos locais, logs, dependências e storage; o primeiro mantém database/experimental-migrations fora do contexto. A precedência de ignore-file por Dockerfile é [documentada pelo Docker](https://docs.docker.com/build/concepts/context/#filename-and-location). O novo StagingBuildContextIsolationTest verifica as listas exatas e alterações inseguras simuladas.

**Limite F3-266 (histórico):** a etapa F3-266 validou somente arquivos. A autorização explícita posterior da Direção permite apenas a prova F3-267 em runner temporário de CI, sem nenhum deploy ou acesso à VPS. O PR permanece DRAFT/NO-MERGE/NO-DEPLOY.

## F3-267 — construção/execução de fumaça descartável, sem infraestrutura externa

A Direção autorizou continuar a prova de execução. A CI `.github/workflows/ci-crm-private-staging-runtime-smoke.yml` foi criada exclusivamente para o **PR #517 em rascunho, no mesmo repositório**, sem credenciais persistentes, com token GitHub limitado a leitura, tempo máximo e cancelamento de execuções substituídas. Ela é distinta da CI **puramente estática** F3-263, que permanece sem construir imagens.

Na máquina efêmera GitHub-hosted a sequência é: validação de Compose JSON fail-closed; construção local das duas imagens revisadas; execução de PHP-FPM somente na rede interna e Nginx em duas redes (backend interno e bridge de entrada com NAT desligado), com a única porta no loopback do runner; verificação de extensões PHP e dependências Composer de produção, sintaxe Nginx/FPM, GET/HEAD /up, respostas 404/405 para rotas comerciais não habilitadas, tentativa de acesso a arquivos internos e cabeçalhos HTTP; exclusão de contêineres, volumes e imagens ao final, mesmo em falha. Os checks executados e o resultado devem ser vinculados à SHA exata.

**Evidência de falha da primeira construção:** [Actions 43540911931](https://github.com/rafhael100/imagine-crm/actions/runs/43540911931) identificou ausência de libsqlite3-dev, corrigida no Dockerfile. [Actions 43541127467](https://github.com/rafhael100/imagine-crm/actions/runs/43541127467) e [43541566114](https://github.com/rafhael100/imagine-crm/actions/runs/43541566114) provaram que imagens, Composer e Nginx/FPM carregam, mas o acesso do host a /up retornou HTTP 000. [Actions 43541947109](https://github.com/rafhael100/imagine-crm/actions/runs/43541947109) demonstrou /up respondendo com sucesso **dentro do próprio Nginx**, isolando o defeito na conectividade host para contêiner da rede internal única.

**Correção estrutural sob teste:** duas redes próprias e não compartilhadas, crm_private (internal: true) contendo app+web e crm_loopback (driver: bridge) contendo somente web, com mascaramento de saída desativado e host binding forçado para 127.0.0.1. A política normalizada proíbe ligar app à ingress, habilitar NAT, rede externa, porta pública ou operações de banco. **Mesmo com NAT desativado, o isolamento de egress/firewall da bridge de ingresso não está comprovado** e deve ser revisto antes de VPS. Fontes: [Docker networking](https://docs.docker.com/compose/how-tos/networking/) e [Port publishing](https://docs.docker.com/engine/network/port-publishing/).

**Não se trata de homologação real:** nenhum contato, cadastro, login ou dado de cliente é criado; não existe serviço público, banco PostgreSQL da VPS, migração operacional, Cloudflare/TLS, credencial ou implantação. Build no runner prova somente o processo descartável da CI e **não** autoriza merge do candidato na DEV, execução na VPS, alteração de `main` ou liberação de funcionalidades de negócio. Mantêm-se os gates SEG/DBA #27, CORE/LGPD #259, UX #416, digest fixo/SBOM, backup/rollback e isolamento do host antes de qualquer ativação externa.

## Escopo demonstrável

A CI F3-263 cobre sintaxe e política estática. **A CI F3-267**, se verde, pode comprovar build e fumaça HTTP real **somente no runner descartável**. Nenhuma das duas comprova rede de uma VPS, segurança do host definitivo, TLS/Cloudflare, back-up, resiliência do PostgreSQL operacional, homologação privada da infraestrutura Imagine ou autorização de negócios.

A conexão de banco do container está propositalmente inerte (SQLite em memória). O PostgreSQL da homologação **não está configurado**: só será desenhado após definição de papéis de runtime não superusuários, migrações, back-up/restore, segredos, RLS e aprovação especializada.

## Gates para qualquer deploy ou acessibilidade

1. Revisão independente SEG/DBA #27 de política, RLS, IAM, papel de banco, segredo e isolamento;
2. Aprovação CORE/LGPD #259 da jornada Portal → CRM, consentimento, retenção, dados e reconciliação;
3. Avaliação visual UX #416 e revisão de transparência das telas;
4. Pin de digests de imagens, prova de build, SBOM e vulnerabilidades, controle de rede e volumes, política de rede de saída, HTTPS e SSH-tunnel;
5. Rotina de segurança de deploy/rollback, restauração e disponibilidade dos demais serviços Imagine na VPS compartilhada.

O pacote deve permanecer **DRAFT/NO-MERGE, NO-DEPLOY** até estas decisões. Mesmo se a CI passar, o pacote não está pronto para executar na VPS e não é instrução operacional de ativação. Nenhuma alteração em main, VPS, domínio, custos, dados pessoais, autenticação ou produção foi autorizada por esta proposta.
