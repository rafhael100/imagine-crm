# CRM F3-198 — pré-análise offline de operações de portabilidade (IN-118)

**Data:** 12/11/2026. **Origem:** [issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [IN-118](../planejamento/IN-118-central-backup-portabilidade-dados.md), CRM-REV-03 (DAT-R13/R14/R15/R22). **Natureza:** laboratório PHP inerte; não é implementação operacional da Central de Backup.

## Implementado
`SyntheticPortabilityOperationPreflight::inspect` recebe somente descritores fictícios, fechados e sem PII, para sete tipos de intenção: `export_csv`, `export_xlsx`, `import_csv`, `import_xlsx`, `backup_complete`, `restore_complete`, `migrate_account`. Reconhece quatro planos documentados (`basic`, `essential`, `professional`, `enterprise`); **não infere regras de trial**. Todos os planos têm portabilidade essencial prevista no documento IN-118, porém nenhum direito/assinatura/tenant é verificado neste componente.

A análise diferencia **planilha simples** de **pacote de backup completo**, com aviso específico para escopo incompatível e manifesto/integridade não verificados. Consegue destacar dados fictícios com potenciais duplicidades, fórmulas perigosas, segredos a excluir, ausência de declaração de confirmação, destino com registros ou estado de destino desconhecido. Não inspeciona, corrige, importa, converte ou entrega bytes.

**Franquia de anexos:** mesmo com utilização **declarada** de 100%, `portability_blocked_by_attachment_quota=false`. O documento IN-118 não autoriza bloquear exportação/download dos dados preexistentes por falta de espaço para **novos anexos**. A saída não concede acesso nem atesta o consumo real.

## Provas automatizadas
- 4 planos × 7 operações = 28 combinações inertes testadas.
- Origem, destino, formato e confirmação continuam alegações e todas as flags `*_verified`, `authorized`, `can_* = false`.
- Rejeição de chaves extras (e-mail, tokens, paths), tipos ambíguos, nomes de operação não aprovados, listas não contíguas e duplicidade de entidades.
- Casos de colisão, backup não equivalente a CSV, retenção de direitos a 100% e ausência de segredos no resultado.
- Matriz transversal `SyntheticCommercialCapabilityDenyMatrixTest`: 41 → **42** recortes de negócio que não podem habilitar capacidades.
- Devem passar F0/Laravel, PostgreSQL/RLS, Sites e AIA na HEAD exata antes de qualquer merge DEV.

## Proibido / ainda pendente
Documento IN-118 aprova **conceitos funcionais**, mas especificação técnica, contas elegíveis, schema de manifesto, versões de backup, criptografia, retenção, autorizações, controle de taxa, recuperação, compatibilidade, direitos LGPD e revisão de terceiros estão pendentes. **Nenhum endpoint, download, API, arquivo, queue, banco, migration, SSO, IAM ALLOW, credencial, armazenamento real, custo ou deploy foi criado.** Escala operacional segue NO-GO por [#27](https://github.com/rafhael100/imagine-crm/issues/27) e [#259](https://github.com/rafhael100/imagine-crm/issues/259).
