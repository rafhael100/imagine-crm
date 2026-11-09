# F3-24 — Contrato negativo de escopo para a prévia CSV mapeada

**Data:** 09/11/2026. **Mandato:** [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35) e sequência F3-20..F3-23. Natureza: laboratório **exclusivamente negativo**, sem serviço de autorização.

## Objetivo

O `SyntheticMappedImportDenyGate::evaluate()` recebe metadados sintéticos de mapeamento CSV, um `TenantContext` **não confiável por construção**, um tenant pretendido, uma operação declarada e posições de campos alvo solicitadas (índices numéricos, não nomes de clientes).

A classe separa duas respostas, ambas **sempre com `allowed=false`**:

- `CONTRACT_INVALID`: tenant/principal ausente ou não UUID, comparação de referência inconsistente, operação diferente de `import`, conjunto de posições incompleto/duplicado/fora do intervalo, relatório sintético forjado/inconsistente, linhas zero, avisos de revisão ou mapeamento parcial.
- `SCOPE_NOT_VERIFIED`: forma sintática coerente, resumo sintético limpo e todos os alvos explicitamente mapeados. **Isso não significa autorização**, pois UUID, `TenantContext`, posições e conteúdo dos resumos não demonstram identity/membership, fonte de dados, direitos por campo, contrato vigente ou consentimento.

A checagem do relatório reaproveita `SyntheticImportReviewInspector`, sem reproduzir parser, ler valores ou confiar em contadores forjáveis. As mensagens de `AuthorizationDecision::signal()` contêm apenas código fixo e UUID de correlação válido, quando presente.

## Limitações deliberadas

- **Nunca** há `ALLOW`, gravação, importação, exportação, credenciais, upload, logs de dados, APIs, migrações, grants, banco ou consulta de membership.
- Comparar UUIDs **não valida o tenant**, e todas as posições de campo são números fictícios. Ausência de avisos ou correspondência exata **não comprova** direitos.
- O requisito de mapa completo serve somente ao **laboratório negativo**; não restringe o futuro produto a importações necessariamente completas nem cria limite comercial de oito campos.
- O gate verdadeiro por recurso/ação/campo deverá ser especificado separadamente com IAM confiável, `DenyByDefaultAuthorizer`, proprietário do módulo, auditoria, origem verificada do escopo, LGPD e parecer independente SEG/DBA. Não reutilizar este laboratório como autorizador.

## Testes

`SyntheticMappedImportDenyGateTest` cobre correspondência completa e ordem inversa, UUIDs coerentes porém forjados, troca de organização, principal inválido, correlação saneada, CSV vazio/com fórmula, mapa parcial, resumo forjado, operações `export/merge/ALLOW`, arrays malformados, campos extras/duplicados, valores booleanos, índices fora de faixa, ausência de métodos de mutação e inexistência de fábrica `allow()`.

**Escopo permitido:** merge exclusivamente DEV após diff e duas CIs verdes na mesma SHA. Sem `main`, VPS, produção, custo ou dados reais. Gates CORE/SEG/DBA/AUT/INT/LGPD, campos aprovados e homologação continuam **abertos**.
