# CRM F3-57 — pipeline sintético completo: CSV → prévia → sugestão → negativa

**Data:** 11/11/2026. **Escopo:** PHP Core isolado, sem API nem dados de clientes.

## Implementação

O CRM já possui parser único CSV estrito (F3-55), sugestões exatas por rótulo (F3-56) e `SyntheticPortabilityDenyGate` (F3-05). O novo `SyntheticCsvImportPreflight` compõe essas peças **em uma única chamada de código**:

1. Lê string CSV fictícia limitada a 32 KiB, 8 colunas e 50 linhas; gera somente contagens e posições de risco de fórmula.
2. Sugere correspondência exata entre rótulos de origem/destino, sem expor texto original das células e cabeçalhos.
3. Verifica que cada campo técnico reivindicado consta **efetivamente** entre as colunas sugeridas. Campos fora do mapeamento, repetidos, corrompidos ou falsos forçam `CONTRACT_INVALID`.
4. Entrega o resumo à `SyntheticPortabilityDenyGate` em operação de laboratório `import` fixada do lado do servidor. Mesmo quando as referências textuais de empresa combinam e o mapeamento é completo, a decisão continua `SCOPE_NOT_VERIFIED` com `allowed=false`: isso não prova identidade ou direito.
5. Retorna `SyntheticCsvImportPreflightSummary` com `preview` agregado e decisão negativa; os métodos `canImport`, `canPersistLead` e `canEnableImportRoute` são permanentemente falsos.

## Evidências

`SyntheticCsvImportPreflightTest` valida o fluxo integrado com mapeamento completo, fórmulas perigosas, campos não sugeridos, colunas sem correspondência, empresa divergente, falsificação de contexto, duplicidade, campos malformados, correlação não UUID e CSV inválido. A resposta não deve ecoar nomes, headers, campos, dados do CSV nem empresa. Exigir workflows de Laravel, PostgreSQL/RLS, Sites ↔ CRM e Agents ↔ CRM verdes na SHA exata para merge DEV.

## NO-GO operacional

O contexto `TenantContext` continua **mera representação**, não é autenticação. A prova é de composição determinística de código com falha fechada, **não de liberação de importação**. Sem upload real, endpoint, dados pessoais, SQL persistente, parser de arquivos externos, migração, permissões, ALLOW, exportação, consentimento, acesso à VPS, deploy, custo ou alteração de `main`. Dependências CORE/IAM/SEG/DBA/LGPD e decisão competente ainda estão pendentes.
