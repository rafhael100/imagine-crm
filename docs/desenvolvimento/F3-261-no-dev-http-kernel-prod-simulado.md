# F3-261 — Smoke HTTP real do kernel sem autoload-dev em ambiente isolado

## Objetivo

A F3-260 passou a verificar duas vezes o inventário de rotas do Laravel, inclusive com a variável APP_ENV=production. Este incremento amplia o controle: o script CLI **scripts/ci/verify-no-dev-http-kernel.php** inicializa o **kernel HTTP Laravel real** no runner do GitHub, via o pacote Composer instalado com --no-dev, e executa requisições em memória sem iniciar servidor, publicar URL, mandar e-mail ou abrir socket.

### Contrato de execução

- Exige simultaneamente PHP CLI, ausência de argumentos, APP_ENV=production (somente simulado no runner), APP_DEBUG=false, SQLite em memória, MAIL_MAILER=array e ausência de DATABASE_URL/DB_URL.
- GET e HEAD /up retornam HTTP 200.
- GET / e /signup, POST /signup, /api/trial, /api/otp/verify e rotas __ci/f3-252, f3-254, f3-258, f3-259 devem retornar HTTP 404.
- Nenhuma resposta pode incluir Set-Cookie ou Location; status inesperado causa falha sanitizada.
- 11 verificações são exigidas; não há saída de request, stack trace, OTP ou dados pessoais.
- O workflow com permissão contents:read executa php -l e o script em uma etapa exclusiva ao lado dos inventários de rota testing e production. Testes PHPUnit garantem que o script e o workflow não removem os controles.

### Limite explícito da prova

Produção é **apenas o nome de um ambiente simulado no processo CI**. Este teste **não** inicia HTTP público, não acessa a VPS, não testa Nginx/Cloudflare, TLS, firewall, DB real, roles, políticas de tenancy, SMTP/WhatsApp, disponibilidade entre processos, backup, restore ou LGPD. Qualquer claim de homologação operacional ou de segurança da VPS seria falso.

Apenas merge controlado em DEV após revisão do diff e cinco verificações CI verdes na SHA exata. Preservar main e bloquear liberação de cadastro, identidade e trial reais até SEG/DBA #27, CORE/LGPD #259 e UX #416.
