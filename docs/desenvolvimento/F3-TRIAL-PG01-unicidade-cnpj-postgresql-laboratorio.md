# F3-TRIAL-PG01 — Prova física de unicidade de trial por CNPJ com PostgreSQL

**Estado: implementação isolada em CI, SEM liberação comercial/cliente.**

## Problema verificado

Os protótipos atuais de signup, OTP e trial usam `TrialEnrollmentLifecycleSandbox`: um `array` no processo PHP, incapaz de impedir que duas requisições em processos/conexões diferentes registrem o mesmo CNPJ. A regra aprovada de **um trial por CNPJ**, inclusive após os 14 dias, requer um invariante físico no banco, além de identidade e titularidade verificadas.

## O que foi implementado e pode ser testado agora

- Migração **fora de `database/migrations/`**, em `database/experimental-migrations/trial`, com guard obrigatório: `APP_ENV=testing`, GitHub Actions, PostgreSQL descartável no `127.0.0.1` e `CRM_TRIAL_LEDGER_LAB=synthetic-only`.
- Tabela `crm_lab_trial_claims` sem CNPJ bruto, e-mail, contato, IP, tenant ou pessoa. Chave única global: HMAC-SHA256 da forma canônica do documento, usando `OfflineCnpjTrialHmacBinding`. Restrição de estado imutável `synthetic_observation_only`, janela de 14 dias e flags de direitos permanentemente falsas no registro.
- Papel dedicado `crm_trial_claim_ci` apenas com permissão `INSERT` nas colunas necessárias, **sem SELECT, UPDATE, DELETE, TRUNCATE**, `BYPASSRLS`, grants públicos ou privilégio de DDL. Não há rota de API, método de login ou contratação.
- `LaboratoryDurableTrialClaim::observe()` usa SQL atômico `INSERT ... ON CONFLICT DO NOTHING` e número de linhas afetadas: primeiro evento 1, duplicidade 0; **não** executa `SELECT` prévio, ativação, notificação, cadastro ou cobrança.
- Testes `PostgresLabTrialClaimUniqueTest`: duas conexões reais, conflito enquanto a primeira transação não encerra, resultado após commit e rollback, sem reuso da chave após expiração, permissões do papel, entradas inválidas, ausência de HMAC no retorno e rejeição de conexões administrativas.
- A exceção de erro oculta SQL e parâmetros potencialmente pseudônimos; não há logging de CNPJ ou impressão de HMAC.

## Limites de segurança que continuam abertos

Esta prova vale **apenas para uma chave de HMAC de versão 1 dentro do laboratório**. Uma troca de segredo poderia produzir HMAC distinto para o mesmo CNPJ e contornar a restrição física; há teste que demonstra explicitamente essa limitação. Antes de publicar, implementar consulta protegida e atômica de todos os candidatos históricos, mecanismo de migração/alias compatível com rotação, controle de versões, posse de chaves e reconciliação. Também faltam:

- Verificação real do CNPJ e titularidade/representação, contato e OTP com consumo durável e limitação de abuso.
- Consentimento e base legal, política de retenção e exclusão, armazenamento seguro e minimização auditáveis pela LGPD.
- Organização e identidade reais, RLS com função DB aprovada, criação atômica de trial/usuário/membership, convite e quota.
- Máquina de estados de trial, pausa/cancelamento, upgrade, ativação e pagamentos idempotentes.
- Revisões de segurança SEG/DBA #27, CORE/LGPD #259, UX #416 e homologação em VPS Leaseweb.

**Não usar essa tabela nem o papel CI em ambientes compartilhados, homologação real ou produção.** Não há mudança de direito/conta, custo, DNS, VPS ou `main`; esta é uma prova de persistência e concorrência para construir o serviço operacional posteriormente.

## Execução de testes

Na CI **separada** `.github/workflows/ci-crm-f3-trial-postgres.yml`, com banco PostgreSQL efêmero próprio e preflight `SEC-CI-02` antes da migração:

```bash
php artisan migrate --path=database/experimental-migrations/trial --force --no-interaction
php artisan test --filter=PostgresLabTrialClaimUniqueTest --no-ansi
```

O workflow cria `crm_trial_claim_ci` e limita os grants antes de executar os testes. A suíte PostgreSQL/RLS existente permanece com seu inventário fechado original de migrações e papéis; a nova tabela e o novo papel NÃO entram naquele banco de teste. O método não deve ser chamado em rotas da aplicação. No ambiente F0 sem PostgreSQL isolado, esses testes são ignorados propositalmente; isso **não** conta como validação de unicidade.
