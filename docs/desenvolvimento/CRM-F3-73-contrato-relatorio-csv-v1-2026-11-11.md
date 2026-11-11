# CRM F3-73 — Contrato de relatório CSV v1 só de metadados (sem API)

**Data:** 11/11/2026. **Escopo:** camada PHP Core sintética; sem endpoint, usuário real, upload ou persistência.

## Funcionalidade

`SyntheticCsvReviewReportV1::build(SyntheticCsvImportPreflightSummary)` transforma o resultado interno da pré-validação CSV numa estrutura estável, versionada e **somente numérica/booleana**, apta para ser consumida futuramente por uma interface revisada sem transportar valores de células.

O contrato contém `protocol='imagine.crm.synthetic_csv_review'`, `schema_version=1`, `status='REVIEW_ONLY_DENIED'`, contagens de linhas/colunas/vazios, pares de mapeamento posicional, posições de fórmulas/repetições/cabeçalhos necessários ausentes/células obrigatórias vazias, indicadores de quais análises foram realizadas e um **código limitado de negativa**. Nunca inclui nome de campo, texto de célula, hash, cliente, nome, e-mail, telefone, identificador da empresa, principal, `correlationId`, payload bruto ou exceções com conteúdo original. Os campos `allowed`, `can_import`, `can_persist_lead` e `can_enable_import_route` permanecem literalmente falsos.

A classe **revalida** os resumos antes de serializar, mesmo que tenham sido criados por outro código: tipos inteiros, listas, chaves exatas, dimensões válidas (até 50 linhas/8 colunas), posições base 1 dentro do intervalo, mapeamento um-para-um, repetições com posição primeira anterior à repetida, coerência entre resumos da mesma prévia e proibição de células obrigatórias vazias fora das colunas marcadas. Estruturas forjadas, objetos com string/payload no lugar de índices e inconsistências são rejeitados com erro fixo sem ecoar dados.

A implementação é propositalmente um **gerador de array interno**. Não abre rota nem define autenticação, autorização ou permissão comercial. Uma futura API precisará de contrato, identidade, políticas por empresa, LGPD e revisão SEG/DBA próprios.

## Testes

`SyntheticCsvReviewReportV1Test` valida saída exata com CSV `;`, fórmulas, células vazias, cabeçalho ausente, repetição e negativa; versão/ordem de chaves, ausência de identificadores e payload no JSON, comportamento opcional de campos obrigatórios, empresa divergente com `CONTRACT_INVALID`; rejeição de arrays de posições forjadas com e-mail e de contagens fora de limites. Exigir F0 Laravel, PostgreSQL/RLS, Sites/Agents verdes na SHA exata e diff/base revisados antes de merge exclusivamente DEV.

## Gates

O contrato é exclusivo de **pré-visualização sintética** e não representa liberação de importação, exportação, deduplicação, cadastro de lead, auditoria LGPD ou permissões. Nenhuma alteração de API, SQL, migrations, IAM, ALLOW, VPS, DNS, `main`, produção, segredo ou custo. CORE/SEG/DBA/LGPD continuam NO-GO.
