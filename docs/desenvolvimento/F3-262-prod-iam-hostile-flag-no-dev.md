# F3-262 — Perfil production resiste à variável IAM experimental habilitada por engano

## Lacuna fechada nesta entrega

O job CI Composer --no-dev já provava a ausência de rotas publicadas e de carregamento das classes de testes. A prova não exercitava um cenário de **configuração hostil**: variável CRM_AUTH_EXPERIMENTAL=true com APP_ENV=production.

Novo script CLI (scripts/ci/verify-production-auth-misconfig-deny.php), testado no **mesmo runner efêmero do GitHub**, inicializa o aplicativo Laravel com a configuração deliberadamente hostil e exige todas as condições:

1. A variável realmente foi carregada como identidade experimental verdadeira; APP_ENV continua production — nenhuma prova é aceita se o cenário adversarial não acontecer.
2. Nenhum guard ou provider de identidade CRM experimental aparece na configuração.
3. Authorizer continua a classe DenyByDefaultAuthorizer e uma requisição de leitura sintética não recebe ALLOW.
4. GuardPrincipalSource sem sessão autorizada continua a rejeitar principal.
5. As instâncias ExperimentalAuthenticator e LaboratoryProvisioner recusam resolução através dos próprios bloqueios explícitos de ambiente, sem acessar banco de dados nem criar identidade.

A execução obriga PHP CLI sem argumentos, APP_DEBUG=false, DB SQLite em memória, MAIL_MAILER=array, ausência de DATABASE_URL/DB_URL e logs sanitizados. Existe teste PHPUnit de contrato do script e do workflow no-dev.

## Limites

APP_ENV=production e CRM_AUTH_EXPERIMENTAL=true são **variáveis de teste no runner GitHub-hosted**, não acesso à produção. Não exercita VPS, registro realmente persistente, sessão HTTP de cliente, grant PostgreSQL, secret manager, proteção perimetral, backup, concorrência ou LGPD.

A prova de misconfiguração **não habilita autenticação nem cadastro**, e não significa homologação funcional. PR isolada em branch filha de DEV; só merge controlado após 5/5 CI verdes, diff revisado e HEAD atual. Main, deploy, clientes, fornecedores e despesas inalterados.

SEG/DBA #27, CORE/LGPD #259 e UX #416 permanecem NO-GO.
