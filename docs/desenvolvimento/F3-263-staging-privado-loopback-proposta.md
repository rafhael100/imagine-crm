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

**Correção estrutural validada no runner GitHub efêmero (não na VPS):** duas redes próprias e não compartilhadas, crm_private (internal: true) contendo app+web e crm_loopback (driver: bridge) contendo somente web, com mascaramento de saída desativado e host binding forçado para 127.0.0.1. A política normalizada proíbe ligar app à ingress, habilitar NAT, rede externa, porta pública ou operações de banco. **Mesmo com NAT desativado, o isolamento de egress/firewall da bridge de ingresso não está comprovado** e deve ser revisto antes de VPS. Fontes: [Docker networking](https://docs.docker.com/compose/how-tos/networking/) e [Port publishing](https://docs.docker.com/engine/network/port-publishing/).

**Não se trata de homologação real:** nenhum contato, cadastro, login ou dado de cliente é criado; não existe serviço público, banco PostgreSQL da VPS, migração operacional, Cloudflare/TLS, credencial ou implantação. Build no runner prova somente o processo descartável da CI e **não** autoriza merge do candidato na DEV, execução na VPS, alteração de `main` ou liberação de funcionalidades de negócio. Mantêm-se os gates SEG/DBA #27, CORE/LGPD #259, UX #416, digest fixo/SBOM, backup/rollback e isolamento do host antes de qualquer ativação externa.

## F3-267 — resultado runtime e nova F3-268 de isolamento

A revisão F3-267 do commit 8fb304949f1826b9e76ff7d8d3bda857ecdc4574 passou **6/6 checks de CI**. O [smoke real #43542647814](https://github.com/rafhael100/imagine-crm/actions/runs/43542647814) construiu ambas as imagens, iniciou PHP-FPM e Nginx em redes separadas, confirmou GET /up HTTP 200 em 127.0.0.1:18080, negou rotas de negócio via GET e POST, negou acesso a arquivos internos e validou Composer, Nginx e FPM. Após os testes, contêineres, redes e imagens foram removidos. Isso resolve a falha **no runner descartável**, sem liberar VPS, produção, login, banco persistente ou qualquer rollout.

O recorte adicional F3-268 introduz um script de prova **dentro desse mesmo runner** para afirmar, por docker inspect e comandos somente de leitura:
- raízes de imagem read-only, ALL capabilities descartadas e no-new-privileges efetivo;
- UID não privilegiado dos dois processos e grupos de redes exatos, sem PHP-FPM ligado à rede de entrada;
- ausência, em cada imagem, de .env, código de teste, migrations experimentais e arquivos desnecessários;
- ausência de rota padrão de saída do PHP conforme /proc/net/route, verificação de porta publicada apenas por Nginx no loopback e NAT da bridge desabilitado.

A F3-268 **passou com 6/6 workflows na SHA 5c4279a20208ae13b2d83d23594cef55e6ee6ff9**, incluindo o [runtime e inspeção Docker #43543520064](https://github.com/rafhael100/imagine-crm/actions/runs/43543520064). Foi substituída uma checagem ambígua de docker compose port por inspeção fail-closed de HostConfig.PortBindings, pois EXPOSE de uma imagem não significa porta vinculada ao host. **Mesmo uma CI verde não é demonstração de ausência de egress do Nginx**, certificação de firewall/host, revisão de supply chain, segurança de VPS ou homologação operacional. Os gates SEG/DBA #27, CORE/LGPD #259, UX #416, digests/SBOM, TLS, backup/rollback e auditoria permanecem NO-GO para deploy, main e dados reais.

## F3-269 — falha controlada e recuperação somente no runner

A primeira tentativa de F3-269 com parada do serviço via Compose ([CI #43543965297](https://github.com/rafhael100/imagine-crm/actions/runs/43543965297)) não obteve código HTTP do Nginx após a saída da aplicação. A repetição com diagnósticos ([CI #43544174266](https://github.com/rafhael100/imagine-crm/actions/runs/43544174266)) encontrou HTTP 000 no runner e impossibilidade de executar nginx -t durante a interrupção, que pode decorrer da resolução do hostname do upstream, não constituindo prova de indisponibilidade do Nginx. A nova abordagem mantém o contêiner presente na rede e simula um backend não responsivo, com timeout de FastCGI explícito.

O script scripts/ci/verify-private-staging-fail-recover.sh foi preparado para executar **apenas após os smoke e testes de isolamento**, no runner GitHub-hosted e exclusivamente no projeto Compose de laboratório. Ele verifica /up saudável, **suspende temporariamente o processo do contêiner PHP-FPM efêmero sem remover seu IP/DNS da rede**, exige HTTP 502/503/504 do Nginx com cabeçalhos de segurança e sem Set-Cookie e, após o timeout FastCGI de 3 segundos, retoma o processo e exige retorno a HTTP 200 em loopback. Um trap garante a retomada mesmo se o teste falhar. A limpeza final dos contêineres é incondicional no workflow.

Esse teste **não** prova alta disponibilidade, failover, persistência, resiliência de banco, TLS, egress/firewall na VPS, rollback, nem segurança operacional. A validação precisa estar verde na SHA exata; não alterar estado de main/VPS, não autenticar usuários, não conceder ALLOW e manter o PR DRAFT/NO-DEPLOY.

## F3-269 / F3-270 — recuperação validada e healthchecks do Compose

A falha e recuperação F3-269 **passou em 6/6 workflows** na HEAD `b48596156ad1bcddcfbd48d820a6a44a129eb368`. [CI de runtime #43544656952](https://github.com/rafhael100/imagine-crm/actions/runs/43544656952) suspendeu apenas o PHP-FPM temporário preservando seu endereço na rede, observou erro HTTP 502/503/504 com cabeçalhos e sem cookies e, após retomar o backend, confirmou /up 200. Nenhum dado real foi utilizado e todos os contêineres foram eliminados.

A F3-270 adiciona **healthchecks nativos no Compose**: PHP-FPM inspeciona exclusivamente seu socket local, Nginx testa somente seu próprio GET /up em 127.0.0.1:8080, ambos com timeout e tentativas limitados, e a inicialização de Nginx depende de PHP com `service_healthy`. A CI aguarda `docker compose up --wait` e o teste F3-269 foi estendido para exigir transições Docker `healthy → unhealthy → healthy` ao simular perda e recuperação do backend. A política fail-closed do Compose verifica o comando exato e rejeita alterações em parâmetros, endpoints externos ou verificações desativadas.

**Isso verifica somente o runner efêmero se a CI ficar verde.** Healthchecks não realizam restart automático, não são SLA, não substituem monitoramento, certificado TLS, integração Portal/CRM, banco persistente, backup/restauração ou segurança da VPS. Regras DRAFT/NO-MERGE/NO-DEPLOY, SEG/DBA #27, CORE/LGPD #259 e UX #416 permanecem.

## F3-271 — inventário de revisão do pacote efêmero

Depois de passar pelos testes de runtime, a CI agora produz um inventário limitado a: commit SHA40 do checkout, hashes SHA256 do Composer lock, Dockerfiles e Compose, IDs SHA256 das **imagens locais temporárias** (não são digests assinados/publicados em registry), módulos PHP carregados e nomes/versões das dependências de produção. O gerador remove da saída descrições, URLs, tokens, variáveis de ambiente, arquivos de clientes e caminhos internos, e verifica um manifesto de checksums SHA256.

Se a etapa concluir, o workflow GitHub Actions anexará o artifact `crm-f3-271-review-inventory` por **7 dias**. O script exige runner GitHub-hosted, evento pull_request, projeto Compose restrito e diretório temporário novo, e o upload ocorre **após** as provas de HTTP, isolamento e recuperação. A CI remove contêineres, redes e imagens mesmo que o upload falhe. **O inventário não é SBOM completo, pin de digest de registry, assinatura de proveniência, aprovação de segurança ou deploy.**

## F3-272 — imagens-base fixadas aos digests observados na CI

O [build descartável #43547265544](https://github.com/rafhael100/imagine-crm/actions/runs/43547265544) registrou os digests dos três artefatos oficiais usados na construção:

- PHP 8.4 FPM Bookworm: `sha256:b75642e8c89c321039f351466134df951576fb0f7017117b3931e9a19b908d6c`
- Nginx 1.29 Alpine: `sha256:5616878291a2eed594aee8db4dade5878cf7edcb475e59193904b198d9b830de`
- Composer 2: `sha256:ca11f643d7791ebdd90a34fc20caf11380d434007ae539a9ea75cd9212e13dca`

Os Dockerfiles candidatos passaram a usar as referências `nome:tag@sha256:digest` exatas, e um teste de fonte exige os três valores para impedir deriva silenciosa de tags. **A nova CI de build precisa passar na HEAD revisada para comprovar que esses digests ainda podem ser utilizados.**

A fixação de digest garante referência ao conteúdo identificado, **não** que a imagem seja livre de vulnerabilidades ou assinada por uma origem confiável. Pendente: assinatura/proveniência verificada, análise de CVEs, SBOM de sistema operacional e política de atualizações. Nenhuma imagem foi enviada a registry privado, nenhuma VPS foi alterada e o PR permanece DRAFT/NO-MERGE/NO-DEPLOY.

## F3-273 — auditoria pública das dependências Composer de produção

A CI isolada `.github/workflows/ci-crm-staging-composer-advisory.yml` consulta exclusivamente o índice público de avisos de segurança dos pacotes fixados em `composer.lock` com `composer audit --locked --no-dev --format=json`. Não instala pacotes, não aplica migrations, não acessa cliente, não roda Docker e só executa no PR #517 em rascunho dentro do próprio repositório. A política é conservadora: aviso reportado, pacote abandonado ou erro de consulta produz **NO-GO** e o relatório JSON não é anexado à conversa nem a um artifact.

**A CI precisa concluir com sucesso na HEAD exata para registrar o resultado.** Não é verificação de pacotes Debian/Alpine, varredura de imagens, assinatura de origem, análise de zero-day ou aprovação de segurança do CRM. Continua obrigatório o gate SEG/DBA #27 e revisão completa de supply chain/host antes de qualquer acesso na VPS.

## F3-274 — inventário de pacotes Debian/Alpine das imagens locais (em validação)

O fluxo de prova do PR #517 continua limitado ao runner descartável do GitHub. Além do inventário PHP da F3-271, será gerada uma lista **normalizada e determinística** dos pacotes instalados no sistema operacional da imagem PHP Debian (`dpkg-query -W`) e da imagem Nginx Alpine (`apk info -v`), usando somente nomes/versões públicos e sem consulta a dados da aplicação. O script `emit-private-staging-os-inventory.sh` nega ambientes fora de `pull_request` GitHub-hosted, rejeita registros com campos inesperados, e acrescenta checksums SHA256 aos arquivos `debian-system-packages.tsv` e `alpine-system-packages.txt` do inventário de sete dias. A coleta ocorre **após** smoke, isolamento, recuperação, healthchecks e manifesto F3-271 e **antes** do upload do artifact.

**Limites:** isso não executa escaneamento de vulnerabilidades no Debian ou Alpine, não fornece SBOM assinado nem lista todos os arquivos da imagem. A F3-274 deve passar 7/7 checks na HEAD exata antes de ser considerada entregue. Nenhuma imagem é publicada ou implantada na VPS.

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
