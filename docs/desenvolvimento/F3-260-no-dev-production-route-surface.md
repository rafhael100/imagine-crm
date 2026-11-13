# F3-260 — Inventário de rotas com ambiente de produção simulado (CI-only)

O workflow **CRM Imagine - F3-255 No-Dev HTTP Sandbox Isolation** já executava um `composer install --no-dev --classmap-authoritative` com `APP_ENV=testing` e exigia que o Laravel publicasse somente GET/HEAD `/up`. F3-260 adiciona uma segunda execução do inventário no **mesmo runner efêmero e sem rede de cliente**, mas com `APP_ENV=production` e `APP_DEBUG=false`.

A segunda execução verifica novamente o namespace `Tests\\` ausente no autoload Composer e exige a mesma única rota de saúde. Resultado: o ambiente de execução escolhido não pode alterar a superfície pública sem que a CI falhe. Existe também um teste PHPUnit de contrato do workflow que exige os dois inventários e recusa instruções de deploy/migrate.

## Limitações e contenção

- `APP_ENV=production` é **somente a string de configuração do processo no runner GitHub-hosted**, NÃO uma conexão ou alteração em produção.
- Banco SQLite em memória, mailer array, sessão e cache locais; sem VPS, SSH, segredos, clientes, migration, API, cadastros ou envio de mensagem.
- A checagem detecta rotas **registradas no processo Laravel desse runner**; não é validação de configuração do servidor web, firewall, tráfego, identidade, RLS ou banco real. Nunca chamar esse teste de homologação da VPS.
- Não modifica `main`. Merge autorizado apenas em `dev/fast-track-crm-2026-11-04` após cinco checks relevantes na SHA da PR, revisão do diff e confirmação de branch sincronizada.
- SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259) e revisão UX [#416](https://github.com/rafhael100/imagine-crm/issues/416) continuam **NO-GO** para onboarding operacional.

**Próximo avanço seguro:** gerar matriz de release/staging somente leitura que distinga CI, staging autorizado e produção e impeça claims de homologação indevidos sem evidência de ambiente.
