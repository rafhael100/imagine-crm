# F3-52 — matriz executável de quatro projetos (laboratório isolado / NO-GO)

Esta rodada **une a execução** de testes reais de código dos quatro repositórios **Imagine Comercial, Portal, Sites e CRM** no mesmo job de CI (PHP 8.3 e 8.4). Não une suas identidades, grants, bancos de produção, sessões ou ambientes.

## Três fronteiras verificadas, todas sintéticas

1. **Comercial → Portal → CRM:** o preflight JavaScript real do Comercial gera apenas oito campos técnicos fictícios; o candidato PHP Portal F119 interage com o receptor de laboratório CRM F3-41 no loopback, em PostgreSQL 18/16 descartáveis, incluindo negativos de autenticação/replay/idempotência; preserva F3-49/F3-50.
2. **Portal → Sites:** as classes PHP reais do candidato Portal F1-23 produzem contrato e assinatura testados pelo verificador Sites real, inclusive replay, expiração, tenant adulterado e estado de autoridade ausente. O teste já existente do repositório Sites é executado, **sem cópia ou reimplementação do validador**.
3. **Sites → CRM:** o rascunho PHP Sites gera somente metadados de envelope que o inspetor Core CRM interpreta como **estrutura não autenticada**. Os testes reais recusam autorização, dados pessoais e envio automático, incluindo variações de tenant/vínculo.

As provas são **três fronteiras independentes**, não uma jornada operacional única; não se afirma compra real → publicação → captura CRM. Os candidatos de Portal para F119 e F1-23 são **revisões diferentes**, fixadas por SHA, intencionalmente para isolar o acervo de interfaces sem unir branches ainda não aprovadas.

## Proveniência imutável e limites

- Portal (Comercial/CRM): `af414ed7aece433e65b4f813be12ba3546cc5c8a`.
- CRM Core candidate: `881ebbf0ddf486f8a85c4848f85719a2f4207f6c`.
- Comercial: `fe42b6c0683ff66d836dc58677f25a8fed2d793d`.
- Portal (Sites F1-23): `8d1e20bc5c39dc4106b549c5e979351b5c766615`.
- Sites (F1-23 e F3-51): `057b6f859df5050c842a86d18676e6332a79d1f9`.

As cinco verificações de `git rev-parse HEAD` impedem substituição silenciosa. Checkouts desativam persistência de credenciais; o workflow tem `contents: read`; não utiliza credenciais de clientes, API externa, GitHub Secrets, deploy, VPS, Cloudflare, domínio ou cobrança. As portas locais e usuários PostgreSQL são **somente de fixtures descartáveis da CI**. Este teste não valida segurança ou integridade de produção.

## Próximos bloqueios P0

Antes da homologação de uso real, falta uma revisão integrada das versões *compatíveis e aprovadas* dos quatro projetos (mesmos contratos no mesmo release), verificação de IAM/MFA, RLS/escopo do tenant, revogação, transporte autenticado, consentimento e durabilidade de recibo, revisão AppSec/DBA e uma jornada autorizada em staging privado com dados fictícios. Nenhuma aprovação de publicação pode ser inferida da CI.

**Estado: NO-GO.** A PR é empilhada sobre a PR #445 exclusivamente para revisão. Não realizar merge em `main` nem modificar VPS/prod sem os gates.
