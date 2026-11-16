# Imagine CRM — WEB-P3: teste de Nginx autenticado (somente CI)

**Estado:** candidata de revisão, não implantada. A página e o cadastro fictício funcionam em Nginx temporário no GitHub runner; não há domínio, cliente, banco, compra, login, OTP real ou trial ativo.

## O que foi exercitado de verdade

- Construção de imagem Nginx a partir de `.dockerignore` de lista positiva (apenas página, logo, CSS, JS e a tela sintética de trial).
- Runtime com UID 101, `--read-only`, `--cap-drop ALL`, `no-new-privileges` e uma porta de entrada vinculada somente ao `127.0.0.1` do runner.
- Credenciais Basic Auth **fictícias e descartáveis**, montadas em arquivo somente leitura do runner, nunca versionadas.
- HTTP 401 anônimo e com senha errada; 200 com senha de laboratório; GET / HEAD autorizados; POST negado; 404 em caminhos internos e APIs não publicadas.
- Cinco arquivos entregues por HTTP comparados byte a byte à origem versionada; MIME, ausência de cookies, `no-store`, `noindex`, `nosniff` e CSP.
- Página principal CSP com CSS/JS externos; exceção `unsafe-inline` somente na tela F3-240 fictícia, que ainda depende de CSS/JS inline. Nenhuma delas pode conectar a serviços (`connect-src 'none'`) nem enviar formulários.

## O que esta prova NÃO demonstra

**Não existe staging da VPS liberado.** HTTP sem TLS no runner é uma prova técnica local de autenticação; não valida HTTPS real, certificados, WAF, Cloudflare, egress da VPS, SSH, usuários administrativos, DNS, firewalls, backups, monitoração, performance, RLS, MFA, gateway ou incidentes.

**Não instalar o Dockerfile nem o Nginx de CI na VPS como se fosse configuração pronta**. O arquivo de staging `nginx-crm-preview.conf.example` é apenas referência para revisão operacional e exige um frontend HTTPS com autenticação e controle de rede próprios, além dos testes reais de 401/200.

## Evidência e próximos critérios

- [CI Nginx + segurança real #43546763773](https://github.com/rafhael100/imagine-crm/actions/runs/43546763773) — aprovado na versão inicial.
- [CI endurecida #43546962808](https://github.com/rafhael100/imagine-crm/actions/runs/43546962808) — aprovou HEAD com exceção CSP restrita, MIME, HEAD, diretórios e ausência de cookies.
- Exigir **CI aprovada na HEAD exata** após qualquer modificação de configuração.
- Antes de ativar no Leaseweb, revisar controle de host e firewall, TLS válido, senha forte com proteção na borda, Auth fora do Git, acesso mínimo, cópia por hash, rollback e validação com navegador real.
- Manter **DRAFT / NO-MERGE / NO-DEPLOY** até gates SEG/DBA #27, CORE/LGPD #259 e UX #416, e aprovação de infraestrutura.

O laboratório não concede direitos a usuários nem modifica `main`, domínios, serviços já ativos ou custos de fornecedores.
