# CRM F3-116 — Códigos ilustrativos para oportunidade ganha/perdida

**Data:** 11/11/2026. **Ambiente:** DEV, somente PHP puro, sem API ou registros de oportunidades reais. **Referência:** CRM-REV-06 VEN-R04, VEN-R05, VEN-R07 e VEN-P03.

## Implementação

`SyntheticOpportunityOutcomeInspector` inspeciona até 20 registros **inteiramente inventados**, cada um com somente `outcome` (`open`, `won`, `lost`) e `loss_reason_code`. A prévia exige `loss_reason_code` de uma lista fechada para qualquer situação `lost`; `open` ou `won` obrigatoriamente usam `null`. Metadados extras, PII, motivo em texto livre, formatos errados e estados incompatíveis são recusados de forma genérica.

A lista de **exemplos técnicos** de motivo é `price`, `timing`, `competition`, `no_response`, `other`. Ela **não substitui** a configuração de motivos por empresa, nem autoriza aprovar o dicionário final do produto. O motivo `other` não aceita texto adicional neste laboratório.

Retorna somente contagens de alegações de `open/won/lost` e de códigos de perda. Não retorna identificadores, valores monetários, motivos textuais, nomes ou e-mails. A soma dos códigos de motivo é igual ao número de perdas alegadas.

## Importante: alegação não é venda confirmada

Uma linha rotulada `won` no laboratório **não confirma uma venda**, não gera evento auditável, meta, comissão ou receita. `outcomes_verified=false`, `loss_reason_policy_verified=false` e `recognized_wins=0` em todos os resultados. Todas as operações de marcar ganha/perdida, reabrir, contabilizar receita, notificar ou persistir são negadas, mesmo quando há alegações de ganho. **Não há deduplicação por ID porque nenhum ID é aceito:** os totais não devem ser tratados como histórico comercial real.

## Testes e gates

`SyntheticOpportunityOutcomeInspectorTest` cobre estados válidos, perda com motivo, múltiplos motivos, entrada vazia, limite de 20, ausência de motivo, motivo conflitante com vitória, campos pessoais e vocabulário restrito. Exige mensagens de erro genéricas sem revelar conteúdo.

A implementação de VEN-R04/R05 em produção depende de tenant, campos obrigatórios, permissões, versão/concorrência, auditoria, motivos por empresa, metas e revisão independente VEN-P03, CORE/SEG/DBA/LGPD. Nenhum SQL, endpoint, cobrança, envio, aprovação, n8n, custo, VPS, `main` ou produção.
