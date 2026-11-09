# CORE02-MX-03 — Redação mínima de sinais de acesso negado (DEV)

**Data:** 09/11/2026. **Natureza:** teste de regressão, não implementação de auditoria, acesso ou política. Complementa [CORE02-MX-01](CORE02-MX-01-matriz-negativa-autorizacao-2026-11-09.md), [CORE02-R02](CORE02-R02-pacote-decisao-matriz-autoridade-2026-11-09.md), CORE06 e LGPD.

## Objetivo

A avaliação negada pelo `DenyByDefaultAuthorizer` só expõe um **código de catálogo restrito** e um identificador de correlação na forma UUID válida, quando houver. Não deve refletir nome de campo, categoria de classificação, principal, organização, escopo, ação, canal, segredo ou dado de contato na resposta serializada.

O teste `SanitizedAuthorizationDenialTest` percorre **432 cenários de laboratório** (6 ações × 9 classes de campo × 4 canais × 2 tipos de escopo), cada um com `AuthorizationIntent`, `AuthorizationEnvelope` e `AccessRequest` fictícios. Confere `allowed=false` e campos mínimos tanto de `AuthorizationDecision::signal()` quanto de sua serialização em JSON.

Os testes adicionais verificam que um valor arbitrário em propriedade desconhecida do contrato resulta na mesma exceção genérica, que uma correlação com formato de e-mail é descartada e que código de razão desconhecido não ecoa seu conteúdo.

## Limites

- Os 432 resultados são apenas repetições de negação **em memória**; não correspondem a auditoria oficial de operações, masking real de APIs, logs de terceiros, DLP, LGPD homologada ou segurança de uma futura exportação.
- A presença de UUID válido ainda exige decisão futura de privacidade sobre acesso à telemetria, retenção e finalidade.
- Não são registrados dados pessoais reais; todos os valores de campo, identidades, segredos e contatos são deliberadamente fictícios.
- **Nenhuma modificação** no contrato de decisão, no autorizador em produção, nas rotas, banco, RLS, grants, filas, IAM, `main`, VPS ou credenciais. Sem `ALLOW` ou autorizações.

**Gates:** CORE02-D04..D10, CORE06, SEG-01/02, parecer independente SEG/DBA [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27), INT, LGPD e homologação futura permanecem abertos. Integrar apenas DEV com diff e dois workflows verdes na SHA exata.
