# CRM F244 — gate integrado da prova de compatibilidade Agents no CRM privado

**15/11/2026 — CANDIDATO DE REVISÃO / NO-GO / NO-MERGE / NO-DEPLOY**

## Fontes e entregas consolidadas

- **Agents F238–F240 / [PR #332](https://github.com/rafhael100/imagine-ai-agents/pull/332)**: original SHA `fbf50543e7c52e6c59c6ffec58b7f6a6d30564b5`. R01 integral [#43792258477](https://github.com/rafhael100/imagine-ai-agents/actions/runs/43792258477) e comparação F247/F248 [#43792258542](https://github.com/rafhael100/imagine-ai-agents/actions/runs/43792258542) foram aprovadas. 879 testes PHP oficiais, lint de 117 arquivos, contratos Python/DOM e ensaios adicionais offline.
- **CRM F241 / [PR #618](https://github.com/rafhael100/imagine-crm/pull/618)**: requisição sintética Agents dentro do Laravel funcional, adaptador HTTP F248, resposta negativa incondicional; CI [#43792579938](https://github.com/rafhael100/imagine-crm/actions/runs/43792579938) aprovada.
- **CRM F242 / [PR #619](https://github.com/rafhael100/imagine-crm/pull/619)**: prova de Ed25519 original Agents + nonce atômico no PostgreSQL 16 existente da árvore funcional, 2 conexões físicas e falhas seguras; CI [#43792918465](https://github.com/rafhael100/imagine-crm/actions/runs/43792918465) aprovada.
- **CRM F243 / [PR #620](https://github.com/rafhael100/imagine-crm/pull/620)**: cadeia completa em memória HTTP F248 → escopo sintético + Ed25519 original Agents → PG16 descartável. A resposta permanece 404 sem projeção; CI [#43793451929](https://github.com/rafhael100/imagine-crm/actions/runs/43793451929) aprovada.

## F244 — por que o gate único é necessário

As PRs acima tiveram CIs específicas. O workflow `ci-crm-aia-f244-unified.yml` agora valida **a mesma HEAD e o mesmo PostgreSQL descartável** para F241, F242, F243, o contrato original CRM F248 e o teste PG já existente, sem suprimir testes históricos. Dois SHAs do Agents são fixados: a versão recente F240 e o verificador original exigido pela suíte F248 (SHA `fe79e7e9a55e4d6728ab42c2ef578e40d9bd68ba`). São copiados somente fixtures locais de código no runner, sem substituir o código da aplicação.

Gate de rotas: inexiste `routes/api.php`; o código Agents não pode aparecer em `routes` ou `bootstrap`. Nenhum controller, middleware operacional, serviço real, migration comercial, segredo de cliente, pagamento ou deploy é criado.

## Bloqueios objetivos de produção

A suíte verde comprova **apenas interoperabilidade de protocolo criptográfico e banco descartável**. Não comprova chave privada real provisionada, identidade confiável de empresa e humano, ACL/RLS real de leads, titularidade/consentimento LGPD, revogação de serviço e rotação em produção, mTLS, nonce persistido no ambiente de homologação, rollback, segurança independente ou contrato real aprovado. As árvores CRM #602 (F0 laboratório) e #614 (privado funcional) continuam separadas, sem merge cego.

A [CRM PR #57](https://github.com/rafhael100/imagine-crm/pull/57) exige 14 gates, 7 decisões e 13 cenários operacionais independentes. **NO-GO** para qualquer publicação de integração Agents. Nem a CI nem este documento autorizam alteração da VPS ou `main`.
