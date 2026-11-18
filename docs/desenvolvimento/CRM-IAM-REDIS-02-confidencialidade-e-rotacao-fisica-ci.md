# CRM-IAM-REDIS-02 — Confidencialidade física de sessões Redis (CI)

**Estado: DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN / NO-ALLOW / NO-CHARGE.** 14/11/2026.

## Contexto
A PR #599 já exercita senha, MFA, HTTP e membership PostgreSQL16 com
Redis 7 no GitHub Actions, sob o gate \`CRM_IAM_REDIS_LAB=synthetic-only\`.
Esta etapa verifica diretamente uma propriedade diferente: o conteúdo que
o handler grava no Redis precisa estar criptografado e as chaves antigas
da sessão não podem sobreviver à autenticação MFA ou ao logout.

## Prova acrescentada
\`tests/Feature/CrmIamRedisConfidentialityTest.php\`, executado em processo
isolado pelo \`scripts/ci/rehearse-crm-iam-redis-session.sh\`:
1. Recusa o teste em execução comum, exigindo gate sintético explícito.
2. Confere driver e store Redis, criptografia ativa e cookies seguros,
   HttpOnly e SameSite Strict.
3. Cria uma sessão de senha pendente, salva no handler real e lê os bytes
   brutos (sem a decriptação feita pelo Session Store).
4. Exige envelope autenticado do Laravel (\`iv\`, \`value\`, \`mac\`) e
   ausência de token, campos de autenticação e identidade fictícia em claro.
5. Completa TOTP, exige mudança do ID da sessão e eliminação da chave
   anterior no Redis; nova sessão protegida é gravada e verificada.
6. Após logout, verifica destruição da sessão ativa no Redis e ausência
   de sessão IAM ativa no registro PostgreSQL.

## Limites
São exclusivamente identidades artificiais e servidor Redis/PG16 descartáveis
de CI. Nenhuma rota de aplicativo, login público, dado real, secret,
migração produtiva, autorização \`ALLOW\`, gateway de pagamento, VPS ou
ambiente de produção foi alterado. Esta prova não equivale a teste de
disponibilidade, persistência, restore, TLS/HA, retenção, autorização
operacional nem review independente.

## Gates para avançar
- CI de todos os workflows na SHA exata deste ramo; verificar falhas e corrigir.
- Revisão independente SEG/DBA (#27), CORE/LGPD (#259), UX (#416) e GO (#557).
- Manter o candidato único #596 e as PRs empilhadas #597, #598 e #599 em
  DRAFT/NO-MERGE/NO-DEPLOY.
