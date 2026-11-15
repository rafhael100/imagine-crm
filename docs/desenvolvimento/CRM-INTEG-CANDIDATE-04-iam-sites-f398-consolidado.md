# CRM-INTEG-CANDIDATE-04 — IAM HTTP + Sites F398 na mesma HEAD

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-PUBLIC-ROUTES / NO-ALLOW / NO-CHARGE / NO-GO.**

## Origem e integridade dos dois ramos

- Base verificada **#606**:
  `2a86cf4dec2b220b6dfb5c8a9f6ac6676802179c`,
  descendente do candidato integrado #604
  (`31b3221d69675d6ad1ae2cf92a485dc5710fb067`).
  Inclui IAM HTTP, MFA, Redis, recuperação e negação de negócio
  com membership PG16, além de Core, UX, Asaas mock, Sites sintético,
  migrations e restore descartável.
- Fonte original **#605**:
  `280bbd3cbf6b9ee61defb508553281e8a48b22dc`,
  descendente da PR #596
  (`987e0588198350d9163b146e437293475fb85484`).
  A comparação #596 → #605 identificou **8 commits e apenas 6 arquivos**
  próprios. A comparação #605 → #606 confirmou que esses seis arquivos
  ainda não haviam sido alterados pela linha IAM.
- Os seis arquivos da PR #605 foram preservados **byte a byte**
  no candidato atual, sem refatoração, cherry-pick automático,
  alteração da DEV ou resolução artificial de conflito:
  1. `.github/workflows/ci-crm-sites-f398-original-envelope.yml`
  2. `.github/workflows/ci-crm-sites-f398-pg16.yml`
  3. `docs/desenvolvimento/CRM-SITES-F398-interop-envelope-original.md`
  4. `scripts/ci/rehearse-crm-private-restore.sh`
  5. `scripts/ci/verify-original-sites-f398-envelope.php`
  6. `tests/Feature/PostgresLabLeadsRlsTest.php`

Os dois fluxos de restore CI receberam correção conservadora independente
para não confundir o postmaster temporário do `initdb` com a instância
PostgreSQL definitiva. O primeiro pertence à linha IAM/candidato #604,
o segundo à linha Sites #605. Nenhum script toca VPS ou base de clientes.

## Provas reunidas (continuam separadas dentro da mesma HEAD)

**Identidade humana e autorização:** login com senha/TOTP em rotas
exclusivas PHPUnit; sessão Redis 7 criptografada, vínculo PostgreSQL16
FORCE RLS para empresa, negação de leitura/alteração/exportação
pelo `DenyByDefaultAuthorizer`, revogação e recuperação MFA.

**Origem Sites e captação de laboratório:** emissor original do
`rafhael100/imagine-sites` na SHA fixa
`1e24f47f7dd96c0e5d4b68f5a71aed7c861179f4`, contrato HMAC
canônico comparado diretamente com o receptor original do CRM,
vínculo site/formulário/destino guardado em fixture pelo proprietário
PostgreSQL; primeiro evento permite registro FICTÍCIO,
repetição idêntica deduplicada, adulteração/empresa diferente e
vínculo revogado negados sob RLS. Não é identidade S2S operacional.

**Importante:** ainda NÃO há prova ponta a ponta de que a sessão de um
usuário com MFA autorize o provisionamento real de um site, nem de que
o emissor tenha mTLS, vault, consentimento ou integração HTTPS real.
Esses fluxos são distintos e ambos continuam NO-GO.

## Critérios para aprovação técnica do candidato

1. Comparação com #606: somente os seis arquivos de #605 mais este
   dossiê. Não reduzir o baseline de IAM nem as verificações F398.
2. Executar todos os workflows na SHA exata do candidato, incluindo os
   dois novos workflows cross-repo e PG16 F398, com `--fail-on-skipped`;
   a evidência anterior 12/12 de #605 ou 10/10 de #606 não basta.
3. Revisar independentemente #27 SEG/DBA, #259 CORE/LGPD,
   #416 UX, #564 revogação concorrente e #557 GO.
4. Não adicionar credenciais, PII, endpoints de produção, ALLOW,
   merge na DEV/main, deploy Leaseweb nem cobrança de cliente.
