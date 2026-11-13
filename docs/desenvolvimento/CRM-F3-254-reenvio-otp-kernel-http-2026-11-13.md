# F3-254 — Reenvio e rotação de OTP pelo kernel HTTP de testes

A F3-253 acrescenta e-mail e telefone fictícios separados no kernel Laravel de teste. Esta etapa exercita **reenvio e rotação pelo mesmo fluxo de requisições HTTP reais da suite Feature**. O método `advanceClockForTestOnly` pertence exclusivamente a `Tests\\Support\\SignupTrialHttpFixture` e avança até 900 segundos no relógio fictício interno. Não existe rota nem parâmetro HTTP que permita alterá-lo em um servidor publicado.

O teste verifica, em uma sequência de requisições Laravel, que reenvio 1 segundo após a primeira emissão sofre cooldown, e após o instante exato de 60 segundos cria desafio novo. Se os dois códigos numéricos forem diferentes (o padrão mais provável), o antigo não coincide com o novo; se forem iguais por colisão aleatória, o teste **não faz afirmação impossível de rejeição de dígitos coincidentes**. Um código novo aceito só consome a marcação em memória; replay, canais paralelos e tentativa de trial não geram autenticação nem autorizam empresa, acesso ou emissão real.

Teste adicional exercita os dois canais, expõe somente diagnóstico interno para o PHPUnit, recusa avanços de relógio fora da faixa, valida os cabeçalhos `no-store` e ausência de sessão/cookie e comprova que a rota temporária continua ausente de `routes/web.php` e `bootstrap/app.php`.

Sem banco, Redis, identidade, CPF/CNPJ real, SMTP, WhatsApp, provedor aprovado, persistência ou endpoints de produção. As flags `can_send`, `can_register`, `can_authenticate` e `can_reserve_trial` permanecem falsas; teste de kernel real não constitui homologação operacional. Gates SEG/DBA #27, CORE/LGPD #259 e UX #416 NO-GO. Merge somente DEV após 4/4 workflows verdes, diff isolado e revisão.

## Critérios de aceitação automática — revisão 2

A CI dedicada `CRM Imagine - F3-253 Laravel HTTP Fixture Route Seal` deve executar, além da F3-252/253, o arquivo `SignupTrialHttpFixtureResendTest.php`. O mesmo workflow confirma que somente `/up` aparece na lista de rotas publicadas e que nenhum marcador `__ci/f3-252` ou `__ci/f3-254` está presente em `routes/`, `app/` e `bootstrap/`. O caso positivo de reenvio testa a fronteira exata de 60 segundos; o negativo testa avanço de relógio inválido, acesso por GET e operação desconhecida.

**Aceite técnico estrito:** cinco verificações na mesma SHA — F0, PostgreSQL/RLS, Sites, Agentes e o route seal HTTP — todas `completed/success`. O resultado verde não substitui segurança de rede, antiabuso persistente, prova de telefone ou revisão formal de credenciais.

## Rebase de integração

A branch foi sincronizada com a DEV após atualizações documentais concorrentes, mantendo o diff da F3-254 limitado ao laboratório PHPunit, workflow dedicado, contrato e esta documentação. Antes de qualquer merge, a CI deverá ser reexecutada na **HEAD exata da branch sincronizada**, inclusive a inspeção que mantém apenas /up como rota publicada. A prova de 900 segundos e cooldown de 60 segundos não autoriza transportes reais.
