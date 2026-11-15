# CRM-IAM-RECOVERY-HTTP-01 — Código de recuperação MFA de uso único por HTTP

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN / NO-ALLOW / NO-CHARGE**, 14/11/2026.

## Objetivo

Cobrir uma jornada concreta do usuário que perdeu o aplicativo TOTP,
mantendo a autenticação apenas dentro dos testes PHPUnit e usando usuários
fictícios. Os serviços de autenticação já ofereciam código de recuperação
hasheado; esta PR traz a regressão pela pilha HTTP real Laravel, CSRF,
cookie de sessão, registro IAM PostgreSQL16 e Redis 7.

## Cenários

- Antes da senha e depois da senha, mas antes do MFA, a identidade continua 401.
- POST senha e POST código de recuperação com CSRF válido retornam, respectivamente,
  202 mfa_pending e 200 authenticated.
- O principal da sessão validada é A, e todas as capacidades de negócio
  continuam false; nunca retorna contatos, clientes ou cobranças.
- O código de recuperação é consumido uma única vez na tabela de hash,
  após a operação; logout revoga o registro IAM e volta a 401.
- Uma nova tentativa de autenticação com o **mesmo código consumido** retorna
  401, sem sessão ativa e sem restaurar o código.
- CSRF ausente retorna 419. Nenhuma rota pública de login, recuperação
  ou API é registrada no aplicativo: rotas `/_ci/*` existem somente no PHPUnit.

## Infra CI

O script já protegido `scripts/ci/rehearse-crm-iam-redis-session.sh`
adiciona `CrmIamRecoveryHttpBoundaryTest`, com sessões Redis criptografadas
reais e papéis IAM PostgreSQL16 sem `BYPASSRLS`. Na suíte geral F0,
o teste roda sob sessões de array, cobrindo a mesma semântica HTTP. Exige
CI GitHub, banco efêmero, gate `CRM_IAM_REDIS_LAB=synthetic-only`
para o ensaio Redis.

Não há APIs operacionais, rotas de registro, tela pública de recuperação,
contatos reais, e-mail/SMS, alteração de ambiente ou VPS, cobrança
Asaas/C6 ou autorização de recursos empresariais.

## Bloqueios de GO

Ainda faltam provisionamento verificado de códigos de recuperação e OTP,
entrega segura ao titular, fluxo de troca de dispositivo, controles antiabuso,
retenção/auditoria, conta real, UX e acessibilidade, infraestrutura autenticada
e revisão independente #27 SEG/DBA, #259 CORE/LGPD, #416 UX e #557
homologação completa. Não é pronto para clientes.
