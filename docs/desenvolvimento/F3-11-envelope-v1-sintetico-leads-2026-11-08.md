# CRM Imagine — F3-11 — Proposta de envelope técnico v1 para leads (laboratório)

**Data:** 08/11/2026. **Escopo:** código PHP puro e testes sintéticos apenas na DEV, ligado à [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [IN-124](../planejamento/IN-124-integracao-inteligente-formularios-leads.md) e [IN-149](../planejamento/IN-149-protocolo-entrega-confirmacao-leads.md). Não é aprovação da especificação final de API.

## Campos da proposta provisória

O contrato local `SyntheticLeadEnvelopeV1Inspector` exige oito chaves técnicas, sem conteúdo de lead:

| Chave | Proposta |
|---|---|
| `protocol` | literal `imagine.lead-intake` |
| `schema_version` | inteiro `1`, estrito |
| `organization_reference` | UUID alegado |
| `binding_reference` | UUID alegado de vínculo site/formulário/CRM |
| `submission_reference` | UUID estável por envio lógico |
| `site_reference` | UUID alegado de site |
| `form_reference` | UUID alegado de formulário |
| `destination_reference` | UUID alegado de destino CRM |

O resultado contém só um código diagnóstico: `STRUCTURE_ONLY_UNVERIFIED`, `ENVELOPE_STRUCTURE_INVALID`, `PROTOCOL_UNSUPPORTED`, `VERSION_UNSUPPORTED` ou `REFERENCE_FORMAT_INVALID`. Sem conteúdo bruto ou identificadores no retorno.

**Muito importante:** esta estrutura não transporta nome, contato, consentimento, formulário, payload, carimbo de tempo da primeira persistência, assinatura ou autenticação. Receber alegações sobre IDs, mesmo bem formatadas, não autentica qualquer vínculo. Os três métodos `canAcceptSubmission`, `canAcknowledgeDurableReceipt` e `canDeliverToCrm` retornam **sempre falso**.

## Para a versão real

A versão real ainda depende de autenticação e autorização da origem, snapshot do vínculo validado no backend, assinatura, transporte, replay/TTL, limites, esquema versionado dos dados, base legal/privacy notice, IP e antispam, idempotência durável concorrente entre duas APIs independentes, auditoria, resposta com semântica de persistência comprovável e revisão SEG/DBA. A rejeição de chaves extras nesta proposta evita tratar timestamps ou consentimentos declarados pelo cliente como prova.

**Sem API, endpoint, dados de cliente, SQL, migrations, filas, HTTP, banco, `ALLOW`, alteração da `main`, VPS, produção ou custos.** Os 67 gates CORE e a [Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27) permanecem abertos.
