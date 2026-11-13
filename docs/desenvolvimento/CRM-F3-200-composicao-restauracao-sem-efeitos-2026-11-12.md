# CRM F3-200 — composição de revisão de restauração, sem efeitos

**Data:** 12/11/2026. **Origem:** IN-118, issue #35. **Pré-requisitos já integrados:** F3-198 (intenções de portabilidade) e F3-199 (manifesto versionado, apenas sintético).

## Entrega
`SyntheticRestoreReadinessInspector::inspect($operationRequest, $backupManifest)` valida **ambos os contratos completos antes de produzir resposta**. Para este recorte, aceita apenas intenções fictícias `restore_complete` e `migrate_account`; exportação/importação tabular e mera geração de backup não podem ser promovidas a restauração por engano.

O resultado combina alertas dos dois revisores com códigos adicionais:
- `RESTORE_REQUIRES_FULL_PACKAGE`: um manifesto de tabela não satisfaz pedido de restauração completa.
- `PACKAGE_CLAIMS_DISAGREE`: divergência entre o escopo declarado no pedido e no manifesto.
- `EXISTING_TARGET_REQUIRES_CONFLICT_DECISIONS`: conta destino declarada como já preenchida exige análise de colisões antes de qualquer mesclagem.
- `UNKNOWN_TARGET_REQUIRES_INDEPENDENT_CHECK`: estado desconhecido do destino nunca vira vazio automaticamente.
- `DUPLICATE_REVIEW_CANNOT_BE_AUTOMATED`: contagem de duplicidades declarada não autoriza escolha ou exclusão de contatos.

A lista `unfulfilled_gate_codes` apresenta requisitos **não cumpridos**, e não etapas já realizadas: comprovar identidade/permissões, integridade/autoria do arquivo, exclusão de segredos/criptografia, mapeamento/duplicidades/consentimentos, reversão/auditoria e confirmação forte com autorização real.

## Limitações de autoridade
Toda evidência é declarativa e fictícia. Nenhum ZIP, CSV, arquivo, caminho, checksum real, chave, token, pessoa, tenant, consentimento, permissão, snapshot ou migração SQL é lido. Toda flag `*_verified=false`, `authorized=false`, `can_restore=false`, `can_import=false`, `can_migrate=false`, `can_merge=false`, `can_erase=false`, `can_write_file=false`, `can_persist=false`. Não implementa endpoint, worker, trigger, RLS ou download.

## Regressões
PHPUnit exercita alegação de confirmação sem autorização, destino preenchido, estado desconhecido, diferenças do escopo do arquivo, versão de leitor inferior, PII extra em qualquer contrato, operações não elegíveis e não divulgação de conteúdos. A matriz transversal `SyntheticCommercialCapabilityDenyMatrixTest` passa de **43 a 44** prévias de efeito negado e veta novas rotas. Exigir 4 checks obrigatórios na SHA exata da PR antes de integração DEV.

A **Central de Backup/Portabilidade real continua não implementada**, assim como a homologação de papéis, salvaguardas de restauração, armazenamento, autenticação, criptografia real e LGPD. Gates #27 e #259 mantêm NO-GO. Nada nessa prévia equivale a aceite de operação ou de segurança.
