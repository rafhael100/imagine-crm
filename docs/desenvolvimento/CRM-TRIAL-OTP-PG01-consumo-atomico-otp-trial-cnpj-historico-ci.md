# CRM-TRIAL-OTP-PG01 — Primeiro onboarding persistente OTP + trial no PostgreSQL16

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-PUBLIC-ENDPOINTS / NO-LOGIN / NO-ALLOW / NO-CHARGE.**

## Lacuna que esta entrega fecha

F3-258/F3-259 já demonstravam HTTP OTP → trial de **14 dias** e
franquias fictícias, mas apenas em memória. `LaboratoryHistoricalTrialAliases`
provava unicidade PostgreSQL com múltiplas chaves HMAC, mas **sem**
consumo durável de um OTP na mesma transação.

Esta PR acrescenta `LaboratoryAtomicOtpTrialEnrollment`, que exige
PostgreSQL16 em CI GitHub efêmera, role exclusiva
`crm_trial_otp_ci`, ambiente `testing` e
`CRM_TRIAL_OTP_PG_LAB=synthetic-only`. Não existe rota ou worker
operacional que possa chamá-la.

## Comportamento implementado e demonstrado

- O desafio OTP é emitido pelo componente Core existente
  `OfflineSignupOtpChallenge` (6 números, prazo 900 segundos,
  no máximo cinco erros) e apenas o **HMAC** da resposta,
  metadados e fingerprint HMAC do contato são persistidos;
  o código fica somente na memória do teste PHPUnit.
- A leitura do OTP usa `SELECT ... FOR UPDATE` dentro de uma
  transação PostgreSQL, serializando duas tentativas com o
  mesmo nonce. Código incorreto consome uma tentativa durável.
  Expiração e repetição são negadas.
- Após correspondência criptográfica, verifica-se que a lista
  de todas as chaves HMAC históricas coincide exatamente com
  a política imutável registrada no banco de laboratório.
- O OTP é consumido e todos os aliases históricos de CNPJ são
  inseridos na **mesma transação externa**. Em colisão de um
  alias histórico, uma transação aninhada/salvamento
  (`SAVEPOINT`) desfaz as inserções parciais, enquanto o OTP
  continua consumido para impedir seu reaproveitamento.
- Há testes com dois canais fictícios, segundo OTP e dia 15,
  chave histórica v2 já ocupada (verifica rollback de v1),
  cinco tentativas inválidas, expiração exata e configuração
  incompleta de chaves (sem consumir código ou persistir alias).
- O PostgreSQL de teste usa tabelas `crm_ci_trial_*` criadas e
  destruídas pelo PHPUnit, **não migrations de produção**.
  A role de experimento é `NOSUPERUSER/NOBYPASSRLS`,
  possui INSERT de alias sem SELECT/UPDATE/DELETE desses registros,
  e `UPDATE` somente de campos de tentativas/consumo OTP.
- O job `ci-postgresql-rls.yml` faz a prova em PostgreSQL16,
  com `--fail-on-skipped`. A suíte principal, sem a flag
  explícita, pula o laboratório em vez de operar em outro banco.

## O que isto NÃO comprova

O dado HMAC representa CNPJ fictício, não titularidade fiscal:
não há consulta cadastral, e-mail/telefone entregue, OTP de
identidade de pessoa, validação de consentimento ou LGPD,
saneamento de dados operacionais, associação a tenant, prova
de retenção, login, liberação de franquias, assinatura,
cobrança, emissão fiscal ou implantação na Leaseweb.
O estado se limita à CI descartável; não é trial ativado.

**Revisões pendentes:** SEG/DBA #27, CORE/LGPD #259,
UX #416, homologação operacional #557. A transação é uma
prova de modelo; para avançar ao produto real, exigem-se
gestão segura de chaves e rotação com esquema definitivo,
identidade humana e da empresa verificadas, entrega
aprovada de OTP, rate limiting distribuído, concorrência
em dois processos, auditoria imutável, quotas e
tráfego HTTPS privado em staging.

Nenhum merge automático, novo serviço, despesa, segredo
real, endpoint público ou alteração na VPS está autorizado
por esta PR.
