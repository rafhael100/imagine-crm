# F3-29 — Contrato negativo para descrever consultas de listas de leads fictícios

**Data-base:** 09/11/2026. **Escopo:** apenas PHP puro em DEV, dados sintéticos e testes de regressão. Continuidade de [F3-26](F3-26-contrato-negativo-historico-leads-2026-11-09.md), [F3-27](F3-27-tripwire-historico-leads-2026-11-09.md), [F3-28](F3-28-continuacao-historico-sintetico-leads-2026-11-09.md), [UX-F3-28](UX-F3-28-navegacao-funil-ficha-offline-2026-11-09.md) e da matriz [CORE02](../planejamento/CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md). [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35).

## Problema e objetivo limitado

A interface offline já tem quatro linhas fictícias, filtros, ordenação e fichas individuais. Isso **não** define o direito de consultar registros no CRM nem um contrato SQL/API aprovado. Antes de qualquer consulta real, é necessário distinguir entre **descrição sintática recebida** e **autoridade de leitura comprovada**.

`SyntheticLeadListQueryInspector::inspect(expectedTenant, candidate)` aceita exclusivamente um descritor de laboratório com as chaves **exatas**, sem parâmetros adicionais:

| Chave de laboratório | Regra de diagnóstico sintático (não direito) |
|---|---|
| `schema_version` | Inteiro estrito `1` |
| `resource` | Literal `lab.lead`, **não** recurso público `lead` |
| `action` | Literal `list`, não `read/search/export/update/delete` |
| `tenant_reference` | UUID não nulo, sintaticamente válido, igual ao `expectedTenant` declarado |
| `fields` | Lista não vazia, sem repetições, de até três **rótulos de exemplo**: `lead_reference`, `stage_label`, `score_band` |
| `sort_mode` | `original`, `score_high`, `score_low`; não é expressão SQL |
| `page_size` | Inteiro estrito de 1 a 20, **somente teste de limite** |
| `cursor` | Apenas `null`; qualquer cursor fornecido é rejeitado sem interpretação |

Uma estrutura coerente produz **somente** o código `STRUCTURE_ONLY_UNVERIFIED`. Erros distintos são `CONTRACT_INVALID`, `VERSION_UNSUPPORTED`, `RESOURCE_UNSUPPORTED`, `ACTION_UNSUPPORTED`, `REFERENCE_FORMAT_INVALID`, `SCOPE_MISMATCH`, `FIELDS_UNVERIFIED`, `SORT_UNVERIFIED`, `PAGE_SIZE_INVALID` e `CURSOR_UNVERIFIED`. O `signal()` serializa exclusivamente `{code}`: **nenhum tenant, campo, cursor, objeto ou dado de lead**.

Todas as capacidades `canListLeads`, `canReadLead`, `canExportLeads` e `canRevealFields` permanecem **false** em todos os caminhos. Nem a igualdade de UUIDs nem o nome `lab.lead` significam autenticação, membership, segregação por tenant, filtro RLS, autorização de campo, entitlement, consentimento, LGPD ou prova de origem.

## Evidência de testes negativos

`SyntheticLeadListQueryInspectorTest` valida:
- descritor coerente e permutação da ordem de chaves; **30 combinações** de campos/ordens aceitas somente como forma;
- rejeição de alegações `allow`, `role`, `mfa_verified`, `field_grants`, cláusulas `where`, chaves desconhecidas, formatos de tenant inválidos e organização diferente;
- campos pessoais, sensíveis e personalizados, *wildcards*, duplicatas e listas malformadas;
- ordenação arbitrária, limites de páginas, cursors vazios ou adulterados e respostas sem PII;
- ausência de ponto de entrada para SQL, exportação, autorização e persistência nos novos tipos.

Os testes são inspecionados tanto no workflow F0 (PHPUnit com SQLite isolado) quanto no de PostgreSQL/RLS, mas **o código não consulta banco algum**.

## Trabalho para o futuro — pendente, não implementado

A implementação funcional exigirá decisão documentada de escopos, permissões `list/read/search/export` por entidade/ação/campo, identidade autenticada e tenant resolvido no servidor, política de projeção de campos, suporte/IA, propriedade/equipe, classificação LGPD, filtros obrigatórios no banco, paginação estável com cursor opaco assinado e vinculado a tenant/ator/filtro/versão, consistência em concorrência, taxas e trilha de auditoria. **Não assumir que os três campos ilustrativos constituem uma lista homologada**.

CORE02-D04..D10, CORE01, CORE03, CORE06, CORE07, CORE08, [SEG/DBA independente #27](https://github.com/rafhael100/imagine-crm/issues/27), INT/DAT, FIN/CON e LGPD permanecem abertos. Sem API/rotas, SQL, migrations, grants, `ALLOW`, dados reais, `main`, VPS, produção, fornecedores ou custos.

**Merge exclusivamente DEV** somente após diff/HEAD/base revisados e dois workflows efetivamente executados e verdes na SHA exata. Revisão humana e homologação posterior continuam obrigatórias.
