# CRM-LEADS-PG07 — Serialização da revogação de vínculos no laboratório

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-GRANT**

## Problema observado no PG06

A conta restrita `crm_app_ci` não podia usar `FOR SHARE` sem obter privilégios adicionais de UPDATE. Usar SELECT simples não serializava revogação simultânea à captura, registrada como bloqueio [#564](https://github.com/rafhael100/imagine-crm/issues/564). Este recorte propõe e testa um mecanismo transacional **apenas no PostgreSQL16 descartável do GitHub CI**, preservando os grants SELECT-only da tabela de vínculos.

## Mecanismo implementado

- Toda captura assinada, após validar a associação à organização, obtém `pg_advisory_xact_lock(hashtextextended(...))` no par **empresa + ID do vínculo**; mantém a trava até o commit ou rollback da gravação de lead, claim/nonce e auditoria.
- A tabela de vínculos recebe trigger `BEFORE INSERT OR UPDATE OR DELETE`, sob a conta proprietária do fixture, que obtém **a mesma trava de transação** antes de permitir a mutação. `BEFORE TRUNCATE` é explicitamente bloqueado.
- `crm_app_ci` permanece sem UPDATE/DELETE/INSERT dos vínculos e sem SUPERUSER/BYPASSRLS/ownership. Não foi introduzido `SECURITY DEFINER`.
- O trigger proíbe mudança de empresa, binding, site, formulário, destino ou canal; a versão cresce exatamente 1 e a revogação é terminal (não se reativa o mesmo vínculo).
- A ordem de linearização proposta é a posse da trava do vínculo. Se captura já possui a trava, a revogação espera até o commit da captura. Se o revogador conclui primeiro, a captura posterior consulta o status revogado e nega. O dado ainda é fictício.
- Hashes de chaves advisory são específicos ao PostgreSQL16 em execução; colisões podem causar espera adicional, mas não bypass de RLS. Dependência de uso da **mesma função de chave por todas as rotas de mutação** precisa de revisão SEG/DBA antes de portar para staging.

## Evidências e limitações

- PHPUnit usa **duas conexões PostgreSQL físicas**: a conexão restrita mantém a trava transacional e vê a associação ativa; a conexão proprietária envia um UPDATE assíncrono de revogação, comprovando que ele fica aguardando. Após o commit da primeira, a revogação conclui e uma nova mensagem sintética assinada é recusada sem gravar lead.
- Também verifica canal incompatível, identidade do vínculo imutável, impossibilidade de reativação após revogação, membership/RLS, replay, nonce e auditoria de PG01..PG06.
- O experimento testa uma ordenação de concorrência e o comportamento SQL no CI, **não uma prova formal de ausência de deadlocks, starvation, timeout, vazamentos de advisory locks ou todos os possíveis interleavings**. Testes complementares e observabilidade operacional continuam necessários.
- Mesmo após os testes verdes, o bloqueio #564 permanece pendente de **revisão independente**, protocolo definitivo de revogação e identidade de emissor Sites autenticada; não deve ser fechado por implementação própria.

Não há migração operacional, rota pública, credenciais reais, deploy, VPS, chamadas ao Asaas nem aceitação de dados pessoais. SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259), UX [#416](https://github.com/rafhael100/imagine-crm/issues/416) e o lançamento [#557](https://github.com/rafhael100/imagine-crm/issues/557) continuam bloqueados.
