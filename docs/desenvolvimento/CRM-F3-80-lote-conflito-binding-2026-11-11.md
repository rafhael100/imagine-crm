# CRM F3-80 — Inspeção em lote de conflitos de vínculo e submissão

**Data:** 11/11/2026. **Escopo:** Core PHP puro, reivindicações técnicas inventadas, sem operação.

## Avanço funcional

A F3-77 distinguia submissões lógicas por organização declarada; a F3-78 examinava lotes de até 50 pares técnicos, sinalizando repetições e conflitos de organização. A F3-79 passou a detectar **mudança do vínculo textual declarado** para a mesma submissão. A F3-80 integra esses diagnósticos **em um único lote limitado**, com tratamento estrito de entradas malformadas.

`SyntheticSubmissionBindingBatchInspector::inspect($claims)` aceita **1 a 50 trios técnicos** `organization_reference`, `submission_reference`, `binding_reference`, de UUIDs estritos sem campos extras. Qualquer entrada inválida rejeita **o lote inteiro** e retorna apenas uma mensagem genérica, sem eco de dados nem mapa parcial. Os resultados em `SyntheticSubmissionBindingBatchSummary` contêm somente `claimsInspected` e índices de pares, começando em 1:

1. `sameClaimPositions`: mesmas três referências textuais declaradas;
2. `bindingConflictPositions`: mesma organização e submissão declaradas, mas vínculos declarados divergentes;
3. `organizationConflictPositions`: submissão declarada igual, organizações textuais diferentes.

A inspeção é feita com `SyntheticSubmissionBindingClaimInspector` F3-79, no máximo **1.225 comparações** em memória, sem hash, I/O, persistência, contatos, e-mails, nomes ou telefone. Cada resultado conserva todas as capacidades negativas: **sem retry autorizado, mesclagem, gravação de lead ou ACK**.

## Testes e paridade

`SyntheticSubmissionBindingBatchInspectorTest` testa a classificação dos três tipos de colisão, ordem determinística de pares, submissões únicas, somente uma tentativa, 50 trios iguais/1.225 pares, conflitos interempresa e rejeição integral de campos extras, dados pessoais e UUIDs inválidos. Prova também que a projeção dos pares de vínculo **coincide com a classificação F3-78 de dois campos**: a união de repetições de três campos e divergências de vínculo corresponde às repetições de organização+submissão, enquanto conflitos de organização coincidem exatamente. Isso evita duplicação de regras contraditórias entre componentes.

## Gates

Não é identidade autenticada, binding servidor–servidor, ledger idempotente, validação da propriedade da empresa, antirreplay, deduplicação de pessoa, consentimento ou persistência. [Issue #259](https://github.com/rafhael100/imagine-crm/issues/259) CORE/SEG/DBA/LGPD segue NO-GO para Portal→CRM real. Não há API, rota, SQL, migration, conta, credencial, PII, IAM/ALLOW, rede, custo, VPS, `main` ou produção. CI F0, PG/RLS, Sites e Agents na HEAD exata, revisão de diff e `behind_by=0` antes de merge somente DEV.
