# CRM Imagine — F3-15 — Integridade HMAC de metadados de recibos (laboratório)

**Data:** 08/11/2026. **Status:** implementação sintética PHP pura em branch DEV, pendente da CI e revisão; não constitui homologação de protocolo ou segredo de produção. Relacionamentos: [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27), [PR #49 F3-14 — ledger proposto](https://github.com/rafhael100/imagine-crm/pull/49), IN-124, IN-148/149.

## Objetivo

Exercitar uma verificação criptográfica **HMAC-SHA256 com comparação constant-time via `hash_equals`** sobre um conjunto de metadados sintéticos: organização, vínculo, submissão, site, formulário, destino CRM, timestamps e nonce. O componente não transporta nem autentica **conteúdo do lead**, dados pessoais, payload, URL, cabeçalhos HTTP nem recibo de armazenamento.

- O domínio de laboratório `imagine.lead-intake.synthetic-receipt.v1` é prefixado, com UUIDs já validados por `LeadSubmissionIdentity` e `SyntheticIdempotencyClaim`; campos canônicos separados por `LF`. Entradas inteiras para timestamps e `nonce` UUID em minúsculas.
- A janela **provisória só do laboratório** é TTL até 300 segundos e tolerância de relógio futuro até 30 segundos. Não é política de assinatura final aprovada para as APIs.
- `SyntheticLeadReceiptMacInspector` usa chave sintética passada explicitamente e exige comprimento de ao menos 32 bytes; o teste usa *somente* string claramente marcada como fixture de laboratório, nunca segredo operacional.
- Código `LAB_MAC_MATCH_UNTRUSTED` significa unicamente que **os bytes de metadados coincidiram com o HMAC fornecido usando a chave fornecida**, não que esta chave foi obtida de uma fonte confiável, não que a API persistiu dados, nem que o tenant foi autenticado.
- Todas as flags `canAcceptLead()`, `canAcknowledgeDurableReceipt()`, `canDeliverToCrm()` e `canTreatAsReplaySafe()` são **invariavelmente falsas**.

## Cobertura e limitações

Testes: metadados íntegros, chave errada, nonce adulterado, todas as referências de origem/destino pinadas, validade temporal, assinatura malformada, segredo curto e ausência de IDs/chave no diagnóstico. **O teste de repetição demonstra a incapacidade de evitar replay por verificação HMAC isolada**: dois pedidos idênticos têm HMAC válido; somente nonce ledger persistente e transacional, junto da chave de idempotência, poderá impedir dupla aceitação.

Antes de usar assinaturas em produção, ainda serão necessários: KMS/vault, distribuição e rotação de chaves, autenticação mútua ou outro vínculo de serviço aprovado, segurança contra request smuggling, HMAC/assinatura sobre corpo canônico com proteção de payload e versões, observabilidade segura, validação de origem independente, antiabuso/rate-limit, relógios confiáveis, ledger de nonces durável, rejeição/reconciliação após timeout e integração com IAM/RLS.

**Sem endpoint, HTTP, rede, armazenagem, fila, SQL/migration, cliente, `ALLOW`, deploy, main, VPS, produção ou custos.** A CI apenas prova esse contrato de laboratório; 67 CORE, SEG/DBA Issue #27 e LGPD/INT/AUT permanecem abertos. O documento F3-14 no PR #49 permanece DRAFT, sem merge.
