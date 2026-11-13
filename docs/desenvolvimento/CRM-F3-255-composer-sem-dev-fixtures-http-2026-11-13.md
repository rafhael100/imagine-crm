# F3-255 — Isolamento Composer sem autoload-dev no CRM Imagine

Este incremento verifica o pacote Laravel em GitHub Actions com Composer no modo --no-dev e classmap autoritativo. O script scripts/ci/verify-no-dev-http-fixtures.php confirma que o Core de OTP real pode carregar, mas nenhuma classe dos testes de cadastro (fixtures HTTP, OTP, trial ou PHPunit) pode ser autocarregada. O workflow usa PHP 8.4 em runner descartável, sem credenciais, e confirma por php artisan route:list --json que a única rota publicada é GET/HEAD /up. Também recusa importações de fixtures em app/, routes/ ou bootstrap/.

Este é um teste de isolamento do artefato de código, NÃO deploy nem prova de segurança da VPS, servidor, banco, identidade ou entrega de OTP. A instalação usa apenas dependências padrão e nenhuma sessão empresarial. Não modifica main, VPS ou produção, não envia e-mail/WhatsApp, não concede trial, não carrega dados pessoais e não cria usuário. O resultado verde não autoriza publicação da rota de teste.

F0 roda PHPUnit do contrato do workflow; workflow dedicado instala Composer --no-dev e demonstra ausência de namespace Tests na configuração normal. Merge exclusivamente DEV após cinco verificações success na HEAD exata, revisão diff e preservação dos gates SEG/DBA #27, CORE/LGPD #259 e UX #416.
