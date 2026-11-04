# CRM Imagine — F0-02: base Laravel 13 e configuracao inicial

**Data:** 04/11/2026.
**Branch:** `dev/fast-track-crm-2026-11-04`; [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) continua em rascunho.
**Autorizacao:** [DEC-FASTTRACK-01](../planejamento/CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md).
**Fonte estrutural:** `laravel/laravel` branch `13.x` no commit `def7ce14811161f3c689a71ca8abc1369da44bd6` (https://github.com/laravel/laravel/tree/def7ce14811161f3c689a71ca8abc1369da44bd6), esqueleto upstream MIT. Arquivos de terceiro foram adaptados quando necessario, sem copiar views grandes, templates nao utilizados ou migracoes de usuarios inadequadas ao CRM.

## Entrega realizada

- Estrutura `artisan`, `bootstrap/app.php`, `bootstrap/providers.php`, configuracoes oficiais `config/`, entrada HTTP `public/index.php`, `phpunit.xml`, pastas de runtime protegidas.
- `composer.json` de Laravel 13.x, PHP `^8.3`, com script de preparacao **sem migracoes automaticas**. Nenhum `composer.lock` gerado porque nao foi possivel instalar pacotes.
- Banco PostgreSQL e servicos locais configurados no `.env.example`, sem usuario, senha, chave ou acesso real.
- Nenhuma rota de negocio publicada em `routes/web.php`; `/up` e o endpoint de saude do Laravel, disponivel **somente quando a aplicacao estiver instalada e inicializada**.
- Injeção da interface `App\Core\Authorization\Authorizer` para `DenyByDefaultAuthorizer`: todas as decisoes retornam `deny`. `TenantContext` e apenas estrutura de dados; **nao autentica nem comprova tenant**.
- Exemplos de teste adicionados como arquivos de fonte; **nao executados**.

## Pre-requisitos e passo a passo futuro (apenas ambiente local autorizado)

1. PHP >= 8.3, Composer 2, extensoes PHP exigidas pelo Laravel, servidor PostgreSQL de desenvolvimento e acesso ao repositório.
2. Fazer checkout da branch F0 e executar `composer install` com rede disponivel, evitando credenciais de producao.
3. Criar `.env` local a partir de `.env.example`, preencher exclusivamente configuracoes de desenvolvimento, gerar `APP_KEY` localmente com `php artisan key:generate`.
4. Conferir `php artisan --version`, `php artisan route:list` e `php artisan test` com dados sintéticos **somente após instalar dependencias**. Documentar ambiente/resultado; testes escritos não significam testes aprovados.
5. Gerar e versionar `composer.lock` de instalacao reconciliada e segura. O esqueleto **ainda nao e instalacao ou aplicativo pronto para uso**.
6. **Nao executar** `php artisan migrate` antes de concluir modelo de identidade global, membership por tenant, roles, constraints e RLS no pacote de seguranca. Nao habilitar cadastro/login provisório sem o desenho IAM final.
7. Abrir revisao de seguranca e contratos CORE/SEG antes de adicionar endpoints de leads/contatos/arquivos, integração Portal/Sites, WhatsApp, IA, n8n ou pagamentos.

## Restricoes expressas

**Nao feito:** instalacao de Composer ou dependencias, `composer.lock`, execucao do Laravel, testes automatizados, migrations, banco de dados, deploy, login, APIs tenant-bound ou verificacao RLS. O ambiente de autoria tinha PHP 8.4 mas nao disponibilizava Composer/rede para instalar as dependencias.

**Nao autorizado nesta fase:** acesso a dados reais, pagamentos, envios externos, cofre de producao, contratos de servicos ou merge/release. Os **67 identificadores CORE** e gates SEG/INT/ARQ seguem abertos quanto à sua implementacao e homologacao.
