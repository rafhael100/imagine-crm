# CRM Imagine — F3-13 — Prova física PostgreSQL de chave idempotente (CI exclusivamente)

**Data:** 08/11/2026. **Estado:** teste de integração com dados fictícios no contêiner efêmero GitHub Actions PostgreSQL 16; ligado à [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35) e à [Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27). Referências: F3-12, IN-124, IN-148 e IN-149.

## Propósito e escopo real

A F3-12 implementou apenas o modelo PHP de pré-condições CAS e reconheceu que duas leituras simultâneas podem produzir a mesma proposta. **F3-13 testa comportamento real do PostgreSQL**, mas **reutiliza exclusivamente a tabela laboratorial existente** `crm_lab_async_ledger`, criada pelo workflow atual `.github/workflows/ci-postgresql-rls.yml` na base efêmera do job.

O teste `PostgresLeadIdempotencyBoundaryTest` acrescenta **nenhuma migration, SQL schema novo, coluna, policy, grant, role, worker ou API**. Sua execução exige simultaneamente driver PostgreSQL, GitHub Actions, `DB_HOST=127.0.0.1`, `DB_DATABASE=imagine_crm_ci` e a role laboratorial `crm_async_ci`. Na suíte SQLite, os cenários são **SKIPPED**, pois SQLite não valida esse comportamento físico.

Usa duas conexões independentes, ambas com a role `NOSUPERUSER NOBYPASSRLS` existente, e verifica que `FORCE RLS` está habilitado e a role não dispõe de `UPDATE/DELETE/TRUNCATE`. O escopo se restringe a dois UUIDs fictícios de organização, com limpeza por fixture admin **somente no job PostgreSQL descartável**, em `tearDown`, após rollback de quaisquer transações de teste.

## Testes de integração e limites

- **Concorrência física:** transação da API primária mantém inserção não confirmada; segunda conexão, com `lock_timeout=250ms`, tenta a mesma chave e deve receber SQLSTATE `55P03`. Depois do `COMMIT`, o replay `ON CONFLICT DO NOTHING` não cria segunda linha.
- **Rollback:** quando a primeira transação aborta, a segunda conexão pode registrar exatamente uma ocorrência numa nova tentativa explícita; não há retentativa automática.
- **Separação de tenants:** a mesma chave de envio em tenants diferentes não conflita com índice único cujo prefixo é `tenant_id`. `RLS` com contexto transacional restringe SELECT ao tenant indicado e rejeita INSERT cross-tenant com SQLSTATE `42501`.
- **Envios distintos:** IDs de submissão diferentes não são deduplicados por pessoa ou conteúdo, que nem sequer estão presentes na tabela experimental.
- **Sem falsa homologação:** a tabela de ledger existente é **assíncrona laboratorial deny-only**; não possui site, formulário, destino CRM, snapshot de vínculo, prova de confirmação nem política de retenção específica para leads. Os testes verificam **invariantes físicos reaproveitáveis**, não a implementação do ledger de leads.

## Riscos/gates que NÃO são resolvidos por este PR

1. A `app.tenant_id` é configurável por conexão. A RLS aqui é somente isolamento condicionado a contexto previamente confiável; **não é autenticação de tenant nem membership**. A origem/verificação de contexto deve ser homologada antes de runtime.
2. A role admin `postgres` usada no serviço efêmero para migrations e limpeza de fixture possui poderes elevados **apenas no contêiner descartável CI**. Não copiar credenciais ou grants para VPS ou produção; revisar em SEGDBA-01..06.
3. Não existe transação distribuída entre APIs independentes, fencing, lease, outbox de leads, confirmação CRM, replay seguro de timeout, comparação de digest/contexto fixado, revogação de vínculo ou decisão de eliminação no 18º dia.
4. A contenção `55P03` é propositalmente **inconclusiva**, não sinal para confirmar sucesso, reenvio cego, descartar registro ou executar `ALLOW`.
5. A garantia de chave única foi exercitada na tabela existente; não há DDL/migration operacional, nem teste de recuperação após crash entre os dois bancos independentes.

**Governança:** mantém todos os 67 CORE, SEG/DBA Issue #27, LGPD, AUT/INT e DEV-GATE abertos. Integração, quando aprovada, somente na branch DEV. `main`, VPS, produção, clientes e serviços pagos intocados. Não declarar que existe um ledger funcional de leads.
