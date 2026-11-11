# F3-44 — CRM ↔ Imagine AI Agents: limite de leitura sintética OP-01

**Data:** 10/11/2026. **Âmbito:** laboratório PHP isolado na branch DEV, sem integração operacional.

## Fontes e compatibilidade

Contrato de entrada do Agents F1-12 (`contracts/crm-aia-readonly.v1.synthetic.schema.json` e `src/MockBridge/MockCrmReadRequest.php`, branch `dev/ft01-aia-core-mocks-2026-11-06`) consultado diretamente no repositório `rafhael100/imagine-ai-agents`.

Este pacote adiciona no CRM `AiaReadOnlyMockBoundary` com a mesma forma de oito propriedades e referências `SYNTHETIC_*` estritas, operação única `crm.lead.read_minimal` e campos ilustrativos `ref/stage`.

**Todos os pedidos — inclusive os bem formados — recebem a mesma resposta de negativa:**
```json
{"contract":"SYNTHETIC_CRM_AIA_PUBLIC_V1","state":"DENIED_OR_NOT_FOUND","projection":null,"real_authorization":false,"external_calls":0}
```

Nenhum valor da requisição é devolvido. Não há cadastro, controller, rota, rede, chamada ao Agents, autorização positiva, consulta a lead, token, integração de conta, memória de sessão, banco, nem transmissão de PII. Ser compatível com o formato de um protótipo **não** é ter integração funcional.

## Testes negativos

`tests/Unit/AiaReadOnlyMockBoundaryTest.php`: requisição válida continua negada; permutações e campos permitidos também; spoofing de tenant, principal, nova versão, escrita, campos sigilosos, role/is_admin, campos extras, tipos inválidos, listas não consecutivas, falta de chaves, entradas com e-mail e tentativa de vazamento da resposta. Testes devem ser efetivamente executados no GitHub CI antes de declarar PASS.

## Pendências para integração operacional futura

- SEG/IAM/CORE: identidade autenticada entre serviços, autorização granular por tenant, usuário, recurso e campo, auditoria e RLS.
- Produto/Privacidade: esquema de respostas real, minimização, base legal e consentimento/finalidade quando aplicável.
- Operação: troca de credenciais, assinatura, anti-replay distribuído, TTL, mTLS/TLS, limites, revogação e observabilidade.
- Direção: aprovar explicitamente integração funcional, responsáveis, ambiente de homologação e qualquer custo.

**NO-GO:** sem `ALLOW`, leitura real de leads, deploy, VPS, `main`, dados reais, API funcional ou fechamento de gates. Esta classe é proibida de ser ligada ao runtime; `SyntheticRuntimeIsolationTest` continua sendo o tripwire.

**Errata F3-46:** o identificador de saída é `SYNTHETIC_CRM_AIA_PUBLIC_V1`; `SYNTHETIC_CRM_AIA_READ_V1` identifica exclusivamente a requisição. Essa correção não altera as negativas nem concede acesso.
