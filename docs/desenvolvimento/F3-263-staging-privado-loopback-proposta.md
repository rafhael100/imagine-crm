# CRM F3-263 — Candidato de staging privado sem dados reais (DRAFT / NO-GO)

**Objetivo:** preparar para revisão técnica um pacote de contêineres do CRM que *poderá*, somente após gate de infraestrutura, servir a aplicação Laravel F0 em localhost para verificar o health-check. Este commit **não instala nem executa qualquer contêiner**. Nenhuma URL pública, sessão de cliente, CNPJ, tenant, credencial, migração, API, e-mail ou WhatsApp é habilitada.

## Arquivos

- infra/staging/Dockerfile — pacote PHP 8.4 FPM com extensões SQLite e PGSQL; Composer --no-dev; fontes explicitamente selecionadas para **não copiar .env, testes, scripts ou migrations experimentais de IAM**.
- infra/staging/compose.yaml — aplicação PHP-FPM e Nginx, rede interna única, **sem bind mounts do host** ou serviço PostgreSQL; Nginx associado exclusivamente a **127.0.0.1:18080** no host, sem porta pública; auth experimental false, SQLite :memory:, cache, fila, sessão e mailer locais.
- infra/staging/Dockerfile.web — artefato Nginx candidato: copia somente nginx.conf e public/ do mesmo checkout em vez de consumir diretórios montados do host.
- infra/staging/nginx.conf — único entrypoint FastCGI index.php, proibição de outros PHP e dotfiles, headers de segurança e limite de request curto.
- tests/Unit/StagingSmokeBlueprintContractTest.php — tripwires de segurança de fonte: portas, containers, conexões, serviços e migrações.
- scripts/ci/verify-staging-rendered-compose.php — política fail-closed que recebe o JSON normalizado do Compose e recusa rede não interna, serviços extras, publicação em 0.0.0.0, mounts do host, configuração IAM/DB/SMTP não fictícia, usuários privilegiados, adições de capabilities, políticas seccomp enfraquecidas, comandos/entrypoints substituídos, imagens não declaradas e contextos de build fora do repositório.
- tests/Unit/StagingRenderedComposePolicyTest.php — cenários negativos para alterações adversariais de portas, volumes, segredos, banco, flag IAM e permissões.
- .github/workflows/ci-crm-private-staging-blueprint.yml — **apenas** docker compose config --quiet/--format json + política PHP e leitura do GitHub; não executa compose up, build, pull, run nem deploy.

## Escopo demonstrável

CI pode provar sintaxe da configuração Compose, modelo normalizado e consistência estática do contrato. **Não** confirma disponibilidade da imagem, build PHP, boot de FPM, healthcheck via Nginx, permissões efetivas, rede de uma VPS, isolamento Docker real, TLS/Cloudflare, segurança do host ou homologação privada completa.

A conexão de banco do container está propositalmente inerte (SQLite em memória). O PostgreSQL da homologação **não está configurado**: só será desenhado após definição de papéis de runtime não superusuários, migrações, back-up/restore, segredos, RLS e aprovação especializada.

## Gates para qualquer deploy ou acessibilidade

1. Revisão independente SEG/DBA #27 de política, RLS, IAM, papel de banco, segredo e isolamento;
2. Aprovação CORE/LGPD #259 da jornada Portal → CRM, consentimento, retenção, dados e reconciliação;
3. Avaliação visual UX #416 e revisão de transparência das telas;
4. Pin de digests de imagens, prova de build, SBOM e vulnerabilidades, controle de rede e volumes, política de rede de saída, HTTPS e SSH-tunnel;
5. Rotina de segurança de deploy/rollback, restauração e disponibilidade dos demais serviços Imagine na VPS compartilhada.

O pacote deve permanecer **DRAFT/NO-MERGE, NO-DEPLOY** até estas decisões. Mesmo se a CI passar, o pacote não está pronto para executar na VPS e não é instrução operacional de ativação. Nenhuma alteração em main, VPS, domínio, custos, dados pessoais, autenticação ou produção foi autorizada por esta proposta.
