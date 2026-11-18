# CRM-STG-DATA-01 — Kanban/Tarefas reais em PostgreSQL, exclusivamente fictícios

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN / NO-GRANT / NO-CHARGE**

## Evolução após o painel visual

A [PR #577](https://github.com/rafhael100/imagine-crm/pull/577) entregou o painel Laravel `GET /demo` com dados escritos no próprio código. Este recorte implementa uma **segunda visualização, `GET /demo/live`, cujos cartões e tarefas são lidos efetivamente do PostgreSQL16**, porém mantendo a forte distinção entre um laboratório e o CRM operacional.

### Persistência e isolamento

- `tests/Labs/demo/ci-persisted-kanban-fixture.sql` cria **duas tabelas exclusivas de teste**: `public.crm_ci_demo_opportunities` (lead, etapa, referência, rótulo) e `public.crm_ci_demo_tasks` (tarefa vinculada com FK composta empresa+lead), com títulos limitados a rótulos artificiais `EXEMPLO_*`/`FOLLOW_UP_DEMO_*`, sem e-mail, CNPJ, telefone, mensagem, endereço ou identificador de cliente.
- SQL cria 6 oportunidades + 3 tarefas para o tenant fictício A e 1+1 para o tenant B, `FORCE ROW LEVEL SECURITY` com `app.tenant_id` local à transação e `GRANT SELECT` apenas ao papel de aplicação já segregado `crm_staging_app`, sem INSERT/UPDATE/DELETE.
- `StagingPersistedDemoPreview::presentation` exige exclusivamente **staging**, flag explícita, nome do banco descartável, conexão PostgreSQL sem transação herdada, papel exato não superusuário/owner/BYPASSRLS, duas tabelas FORCE RLS e falta de privilégios de escrita. Inicia transação **READ ONLY**, verifica ausência de escopo herdado, fixa o UUID do tenant sintético **no servidor** e lê as tabelas por colunas restritas. Qualquer falha de DB/fixture é tratada como 404 sem fallback para dados operacionais. O navegador **não escolhe** empresa, mesmo enviando `?tenant=...`.
- O modelo conserva rótulos PT/EN/ES e campos `can_edit=false`, `can_contact=false`, `can_notify=false`, `can_signup=false`, `can_checkout=false` da demo original. O Blade compartilhado identifica claramente a fonte fictícia `PostgreSQL16 de teste` e mostra contagens reais por etapa.
- `GET /demo/live` só é registrado sob o gate duplo de `GET /`/`GET /demo` **mais** `CRM_STAGING_PERSISTED_DEMO=true`. Não há POST/PUT/DELETE, formulários, JS nem chamadas Asaas. O Compose fica em `127.0.0.1:18080` e não é um deploy na VPS.

### Evidências solicitadas da CI

A nova etapa `rehearse-crm-persisted-demo.sh` roda **depois** do teste de migração Laravel e dos testes de recuperação com instância PostgreSQL independente. Ela exige banco fictício reconhecido e role restrita, recusa tabelas já existentes, implanta apenas a fixture e comprova por PostgreSQL verdadeiro: **tenant A 6/3, tenant B 1/1 e escopo ausente 0/0**. Verifica também que a role de runtime não consegue fazer DML.

O smoke HTTP exige renderização real de PT/EN/ES, proteção de idioma/tenant adulterados, presença de registros A e ausência de B, `POST /demo/live=405`, headers `noindex`/CSP. Para demonstrar que a tela **consulta o banco**, uma única ação administrativa efetuada no runner temporário muda a etapa do cartão `DEMO_002`; o novo HTML deve exibir `new=1, qualified=2` (antes `new=2, qualified=1`), sem mudar a demo estática. O volume inteiro é destruído ao final do workflow.

### Limites

**Não é login nem API do CRM**. O tenant A é fixo e fictício; o SQL fica em tabelas laboratoriais separadas, sem relação com tabelas/IDs de clientes. A chave `app.tenant_id` é uma marca de escopo técnica, não prova identidade. A prova não concede `ALLOW` no `DenyByDefaultAuthorizer` nem publica `crm_ci_demo_*` em infraestrutura real. Sem revisão de papéis e IAM, dados de clientes continuam proibidos.

Critérios ainda P0: login/MFA, autorização por ação e tenant com vínculo revalidado, auditoria operacional, migrações produtivas aprovadas, backup real, Asaas Sandbox autenticado, contrato/consentimento, entrega de e-mail/OTP, acessibilidade e QA UX, TLS/domínio e homologação Leaseweb. Gates [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416), [concorrência #564](https://github.com/rafhael100/imagine-crm/issues/564) e [lançamento #557](https://github.com/rafhael100/imagine-crm/issues/557) seguem abertos.
