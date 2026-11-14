# CRM-STG-THREAT-01 — Ataques repetidos ao Kanban SQL privado (24 requisições)

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN / NO-ALLOW**

## Risco monitorado

Na prévia PostgreSQL do CRM o papel de aplicação não pode receber controle de tenant, principal ou permissões a partir de cabeçalhos, cookies e parâmetros de URL. A rota `/demo/live` usa UUID fictício A **fixo no servidor** e transação de leitura. É necessário assegurar que pedidos repetidos não façam a página devolver registros fictícios de B, refletir claims forjados ou escrever no banco.

## Prova integrada em CI

O script `scripts/ci/rehearse-crm-private-http-threats.sh` executa no **runner GitHub Actions** somente após migração Laravel, fixture do Kanban de duas empresas, atualização de etapa e recuperação integral no PostgreSQL16 descartável. Exige repositório, flags de CI, base `imagine_crm_staging` e estado sintético exato `7 oportunidades/4 tarefas/2 memberships`, com `DEMO_002` qualificada após a prova anterior.

- Em **24 requisições GET consecutivas**, alterna PT/EN/ES/locale malicioso, `?tenant=B`, `?organization_id=B`, `?is_admin=true`, `?can_checkout=true`, cookies de tenant/administrador e headers falsificados `Authorization`, `X-Service-Identity`, `X-Tenant-Ref`, `X-Organization`, `X-Principal-Ref`, `X-Forwarded-User` e `X-Forwarded-Host`.
- Para cada resposta real Nginx/PHP/PostgreSQL, exige idioma esperado, conteúdo fictício A, **nenhum cartão B**, ausência de reflection de claims e **contagens persistidas corretas** `new=1/qualified=2`, demonstrando que o pedido malicioso não muda o escopo.
- Tenta `POST`, `PUT`, `PATCH` e `DELETE` com payload de atualização forjada. Cada uma deve receber `404/405/419`, **nunca sucesso**, e o estado final do PostgreSQL deve permanecer igual ao inicial.
- O script emite somente resultado sanitizado; não há chamadas externas, PII, segredos reais, acesso VPS ou alterações persistentes.

## Limites e testes adicionais necessários

As 24 requisições são uma regressão de **demo sintética em runner**. Isso não comprova IAM autenticado, roteamento/ACL de negócio, sessão real, revogação sob concorrência, resistência contra múltiplos workers/pools operacionais ou proteção de uma implantação em domínio externo. Não conceder `ALLOW`, não ativar endpoints produtivos e não considerar concluídos os gates SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259), UX [#416](https://github.com/rafhael100/imagine-crm/issues/416) e SaaS [#557](https://github.com/rafhael100/imagine-crm/issues/557).
