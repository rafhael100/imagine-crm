# F3-TRIAL-HTTP-PG01 — Backend HTTP fictício e unicidade global PostgreSQL

**Escopo:** teste isolado do kernel Laravel, com rota criada **somente em PHPUnit** e banco PostgreSQL descartável no GitHub. Não é endpoint operacional de cadastro/trial.

## O que agora se comprova

`tests/Feature/PostgresLabTrialHttpHandoffTest.php` combina o código real do `SignupTrialSequencedHttpLab`, do `LaboratoryHistoricalTrialAliases` e das restrições físicas PostgreSQL. O percurso no teste é:

1. `POST /__ci/f3-trial-pg/issue` — emissão interna de código fictício, que jamais sai da fixture.
2. `POST .../verify` — comparação local, sem comprovar posse de contato real.
3. `POST .../trial` — depois da sequência sintética de OTP, tenta gravar um **marcador fictício** para o CNPJ constante `12.ABC.345/01DE-35`, pelo papel INSERT-only e por HMAC.
4. Reinicia o laboratório HTTP, como se outra sessão ou processo tentasse novamente. O novo estado em memória aceita sua própria primeira marca, mas **o PostgreSQL recusa uma segunda marca para o mesmo documento**.
5. Em todos os cenários, a resposta HTTP continua idêntica e neutra: não revela HMAC, código OTP, existência de cadastro, empresa, identidade, tenant nem direitos. Não cria conta/trial/assinatura; nunca altera routes/web.php.

Há testes negativos para OTP ausente/cancelado, payload com campo `cnpj`/contato/`role` enviado pelo navegador e endpoints públicos indisponíveis. Os testes de concorrência e rotação v1/v2 das etapas PG01/PG02 permanecem separados.

## Limites antes de homologar cadastro real

O código HTTP acima **não** implementa verificação de posse de e-mail/telefone, entrega via serviço autorizado, MFA, armazenamento durável de desafios e orçamento de tentativas, vínculo fiscal e titularidade, consentimento, aceites de contratos, organização/usuário real nem idempotência de toda a transação de ativação. Os dados de CNPJ e contato estão fixados na fixture e nunca vêm da requisição.

A migração continua experimental no banco de laboratório. Não há publicação na VPS Leaseweb. Para login, gestão de empresas e cobrança reais, ainda são necessárias as decisões operacionais de SEG/DBA #27, CORE/LGPD #259, UX #416, infraestrutura e fornecedores de pagamento.

**NO-GO:** não converter este teste em rota ou service provider; ele valida somente uma possível ordem técnica de operações e o bloqueio de duplicidade física. Nenhuma alteração em `main` nem credenciais de cliente.
