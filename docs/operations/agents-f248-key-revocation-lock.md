# CRM F248 — serialização física de revogação de chave Ed25519 no PostgreSQL

**15/11/2026 — CI de banco descartável, DRAFT / NO-GO / NO-MERGE / NO-DEPLOY**

A F248 porta o padrão de bloqueio da chave provado na árvore F0 CRM #602 para a árvore **funcional privada** do CRM, com código independente e sem importar migrations ou rotas produtivas.

`AgentsPrivateKeyRevocationLockLab` exige PostgreSQL16 descartável, role `crm_aia_keylock_f248_ci` separada com `SELECT` e `UPDATE(lock_marker)` exclusivamente na tabela sintética. `FORCE ROW LEVEL SECURITY` e política `WITH CHECK(false)` impedem a alteração real de `lock_marker`, `tenant_ref`, chave pública ou revogação. A role não é superusuário, não bypassa RLS e não pode inserir ou excluir.

O código usa `SELECT ... FOR UPDATE` para manter o vínculo `key_id/tenant_ref/public_key` sob lock durante a verificação Ed25519 original Agents e o registro de nonce PostgreSQL fictício. O teste cria segunda conexão PG física, configura `lock_timeout` restrito e demonstra que uma revogação concorrente da mesma chave é bloqueada até terminar a verificação. Depois do commit, a revogação ocorre e uma nova assinatura não entra na callback nem consome um nonce novo.

A role é criada e eliminada apenas na CI (inclusive REVOKE explícito de permissões de schema/banco); o banco é descartado e nenhuma chave ou segredo operacional é utilizado. A implementação não é conectada a rotas, cliente real, contêiner de homologação, tenant IAM, serviços externos ou publicação.

**Limites:** o bloqueio só cobre o fluxo de laboratório que usa este contrato; revogações feitas por outros caminhos não são automaticamente serializadas em produção. É necessária revisão independente das políticas, transação de negócio real, proveniência de empresa e permissão por principal/recurso/campo, retenção LGPD e estratégia de rollback. **Nunca interpretar CI verde como autorização GO.** CRM #57: 14 gates, 7 decisões e 13 cenários operacionais não homologados.
