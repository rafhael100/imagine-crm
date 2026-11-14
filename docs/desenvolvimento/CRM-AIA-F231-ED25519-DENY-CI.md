# CRM↔Agents F231 — prova bilateral Ed25519 com negação obrigatória

**14/11/2026 · CI efêmera, sem rede para clientes, sem IAM real, NO-GO.**

## Origem e alcance

O Imagine AI Agents [PR #320](https://github.com/rafhael100/imagine-ai-agents/pull/320)
implementa um verificador Ed25519 **só para fixtures sintéticas**. O
candidato CRM aqui executa o PHP **original do Agents na SHA fixa
`fe79e7e9a55e4d6728ab42c2ef578e40d9bd68ba`**, sem copiar ou
substituir os validadores por implementações reescritas.

A prova usa `scripts/ci/check-aia-f231-ed25519-deny.php` e a classe
original do CRM `AiaReadOnlyMockBoundary::inspectCanonicalJson`:
uma assinatura válida de serviço fictício **não altera a negativa
uniforme** `DENIED_OR_NOT_FOUND`, não oferece projeção e mantém
`real_authorization=false` / `external_calls=0`.

Exercita 3 tenants fictícios, 12 negativos independentes, replay por
nonce, falsificação de tenant inclusive re-assinada, keyring desabilitado,
ausência de chaves de confiança e prazo vencido. A matriz PHP 8.3/8.4
usa Actions com dois checkouts SHA-fixados (Agents e action checkout),
permissão `contents: read` e sem persistência de credenciais.

## Relação com o IAM humano do CRM

As PRs CRM [#597](https://github.com/rafhael100/imagine-crm/pull/597),
[#598](https://github.com/rafhael100/imagine-crm/pull/598) e
[#599](https://github.com/rafhael100/imagine-crm/pull/599) lidam com
**identidades de pessoas** e sessões de teste senha+TOTP+PostgreSQL+Redis.
Este contrato F231 **não** autentica usuários nem se integra às sessões
MFA; sua finalidade é preparar uma identidade técnica independente
para o serviço Agents sem aceitar headers fornecidos pelo cliente.

## Próximos bloqueios reais

A implementação operacional precisa de revisões CRM SEG/AUT/CORE,
gestão de chave privada em segredo externo, validação issuer/audience,
identidade delegada e tenant confiável, nonce compartilhado atômico,
ACL/recurso/campo no CRM, política de finalidade, TLS/mTLS, privacidade
e homologação completa.

**CRM #57 permanece DRAFT/OPEN; 14 gates e 13 cenários reais
NAO_EXECUTADO; NO-GO.** Esta PR é DRAFT/NO-MERGE/NO-DEPLOY,
não mexe na `main`, VPS, usuários, clientes, dados reais ou pagamentos.
Nenhum custo adicional autorizado.
