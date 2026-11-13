# CRM F3-253 — Prova de vedação de rotas de laboratório no kernel HTTP

A F3-252 criou uma prova funcional com requisições reais `postJson()` no kernel Laravel 13 e rotas registradas **apenas** pelo PHPUnit. Este workflow separado garante que tais endpoints **não apareçam no inventário da aplicação** quando o Laravel sobe normalmente.

O job PHP 8.4 executa testes da jornada OTP/trial por HTTP, o `PublicRouteSurfaceSealTest` original e um novo teste de contrato da própria CI. Depois roda o comando real `php artisan route:list --json` e exige por `jq` exatamente **uma rota**, `/up` GET/HEAD. Verifica que nenhum arquivo em `routes/`, `app/` ou `bootstrap/` contenha a rota fictícia `/__ci/f3-252` ou importação de `SignupTrialHttpFixture`. Se qualquer regra falhar, o workflow não permite resultado verde.

Não instala banco persistente, não usa secrets, não publica endpoint, não aciona SMTP, WhatsApp, VPN, VPS ou deploy. O resultado resumido em GitHub Actions não contém código OTP, CNPJ, telefone, e-mail ou valores de sessão, nem é um certificado de autorização. A execução continua dependente de ambiente PHPUnit com `autoload-dev`.

**Gates:** a rota real de cadastro permanece NO-GO por SEG/DBA #27, CORE/LGPD #259 e UX #416. Toda API futura exigirá revisões independentes de RLS/tenancy, consentimento, mecanismo de envio, orçamento distribuído, consumo atômico e auditoria. Merge exclusivamente DEV após **5/5 workflows success** na HEAD revisada (4 CI existentes mais F3-253), preservando `main`, VPS e produção.
