# CRM Imagine — F3-14-R01 — Pacote para revisão SEG/DBA independente

**Data:** 09/11/2026. **Estado:** DOCUMENTAÇÃO NÃO HOMOLOGADA, sem implantação. Acompanhar [PR #49](https://github.com/rafhael100/imagine-crm/pull/49), [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27) e [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35).

## Revisor e legitimidade do parecer

- Revisor: **NÃO DESIGNADO**.
- Especialidade, independência, conflitos de interesse e escopo aceito: **PENDENTES**.
- Parecer assinado/rastreável, SHA examinada, evidências, data, condições e riscos residuais: **PENDENTES**.
- Aceite da Direção e autorização de implementação operacional: **NÃO EMITIDOS**.

CI verde, comentários do autor e este pacote **não** constituem auditoria independente.

## Matriz de decisão P0/P1

| ID | Problema e evidência | Aceite necessário |
|---|---|---|
| P0-01 — GUC/tenant | [PR #52](https://github.com/rafhael100/imagine-crm/pull/52) demonstra que papel sem BYPASSRLS pode escolher `app.tenant_id` e enxergar outra organização no laboratório | Autenticação e membership de origem verificável; tenant resolvido no servidor, escopos, revogação; transação/pool limpos |
| P0-02 — Idempotência entre duas APIs | [PR #48](https://github.com/rafhael100/imagine-crm/pull/48) e [#51](https://github.com/rafhael100/imagine-crm/pull/51) testam índice único em *uma* tabela PostgreSQL CI, não em bancos independentes | Idempotência final `(tenant, submission)` no CRM e/ou coordenador aceito, teste de partição/replay/restore |
| P0-03 — Anti-replay e origem | [PR #50](https://github.com/rafhael100/imagine-crm/pull/50) mostra MAC íntegro repetível | Gestão de chaves/KMS, identidade do emissor, HMAC sobre corpo canônico, nonce ledger transacional, rotação e revogação |
| P0-04 — Migrações destrutivas | [Issue #27 SEGDBA-01](https://github.com/rafhael100/imagine-crm/issues/27) descreve `migrate:fresh`, `db:wipe`, `DROP` fora do guard interno | ACL/ownership, DB efêmero identificado, proibição fora de CI, ensaio backup/restore |
| P0-05 — Persistência comprovada | [PR #42](https://github.com/rafhael100/imagine-crm/pull/42), [#44](https://github.com/rafhael100/imagine-crm/pull/44) e [#49](https://github.com/rafhael100/imagine-crm/pull/49) | Recibo durável separado de comprovação final do CRM; resposta HTTP/UUID alegado insuficientes |
| P0-06 — Aprovações e fronteiras | [PR #49](https://github.com/rafhael100/imagine-crm/pull/49) propõe esquema/estados sem homologação | Parecer SEG/DBA independente, owners INT/Privacidade, decisão Direção, gates CORE/SEG e plano rollback |
| P1-01 — Pool contaminado | [PR #52](https://github.com/rafhael100/imagine-crm/pull/52) demonstra persistência indevida de `set_config(..., false)` | Contrato transacional `SET LOCAL` e limpeza incondicional da conexão |
| P1-02 — Rota de armazenamento | [PR #53](https://github.com/rafhael100/imagine-crm/pull/53) detectou `PUT storage/{path}` no scaffold com `serve=true`; hardening ainda em validação | Desabilitar até política de uploads, URLs assinadas, ACLs, TTL e segregação por tenant |
| P1-03 — Expiração x entrega tardia | IN-127/147/148/149, [PR #43](https://github.com/rafhael100/imagine-crm/pull/43) | Operação atômica/versionada, provas em ambas as rotas, retenção e LGPD/backup; não apagar estado incerto |
| P1-04 — CI push/PR | [Issue #27 SEGDBA-06](https://github.com/rafhael100/imagine-crm/issues/27) identifica inconsistência de disparadores de workflow | Resolver de forma restritiva, preservar runner GitHub-hosted e banco descartável |

A severidade acima é **prioridade sugerida**, não conclusão de revisão especializada.

## Duas alternativas de autoridade de idempotência

**A — CRM como fonte autoritativa da criação final:** duas APIs podem persistir recebimentos independentemente, e o CRM impõe uma única criação por `(tenant, submission)`. É necessário reconciliar mensagens duplicadas e perdas de ACK, sem prometer exactly-once global.

**B — Coordenador durável comum + validação final CRM:** APIs consultam a mesma fonte de reserva, com regra de idempotência redundante no CRM. Requer desenhar e testar a indisponibilidade do coordenador, lease/fencing, particionamento de rede e recuperação.

**Nenhuma alternativa está aprovada.** Exigir decisão do revisor de arquitetura/INT com tradeoffs de segurança, disponibilidade, custos e recuperação.

## Roteiro de parecer verificável

1. Identificar pessoa independente, qualificação, conflitos, escopo e commits exatos de [PR #23](https://github.com/rafhael100/imagine-crm/pull/23), [#26](https://github.com/rafhael100/imagine-crm/pull/26), [#49](https://github.com/rafhael100/imagine-crm/pull/49), [#52](https://github.com/rafhael100/imagine-crm/pull/52) e [#53](https://github.com/rafhael100/imagine-crm/pull/53).
2. Examinar permissões, RLS/tenant autenticado, papéis e owner, isolamento de CI, contratos de API e integridade do corpo, anti-replay, concorrência distribuída, retenção e restauração.
3. Para cada P0/P1: registrar evidência, ameaça/impacto, requisito/teste, responsável, prazo, recomendação e risco residual.
4. Emitir status **APROVADO / APROVADO COM CONDIÇÕES / REPROVADO** acompanhado de registros verificáveis. Só então submeter gates específicos à Direção.
5. Antes de executar migrações/ALLOW, confirmar inexistência de dados reais, plano de rollback, consentimento/finalidade LGPD, testes de falha e separação de segredos.

**Não é permitido transformar aprovação genérica de desenvolvimento em autorização de API, escrita de dados de clientes, release `main`, VPS ou produção.** Todos os 67 CORE e SEG/AUT/INT/LGPD permanecem abertos.
