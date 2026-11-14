# CRM-STG-DEMO-01 — Dashboard Kanban e tarefas em prévia privada real

**DRAFT / NO-MERGE / NO-DEPLOY / NO-CUSTOMER-DATA / NO-LOGIN / NO-CHECKOUT**

## Entrega

A [PR #575](https://github.com/rafhael100/imagine-crm/pull/575) adicionou uma homepage comercial Laravel de verdade (PT/EN/ES), porém não havia painel Laravel conectado a essa navegação. Neste recorte, o visitante do **Nginx privado em `127.0.0.1:18080`** pode abrir `GET /demo` e retornar à homepage, enquanto todo acesso empresarial e financeiro permanece bloqueado.

- `StagingDemoPreview::presentation()`: gera de forma **100% determinística** um funil de seis oportunidades fictícias, quatro etapas, três tarefas fictícias e indicadores derivados dos mesmos objetos, em português, inglês e espanhol. Não acessa DB, rede, sessão, principal, webhook, filas, Asaas ou APIs.
- `crm-staging-demo.blade.php`: sidebar responsiva com links de âncora para funil e tarefas, dashboard com cards, quatro colunas Kanban e lista de atividades. Identidade `#F9941D`, grafite, layout elegante, legível no celular. Os elementos são estáticos; não existem botões sem função, `form`, `script` ou eventos de mutação.
- Homepage comercial passa a incluir link **real** para `/demo?lang=..` em todos os idiomas. O locale é validado por whitelist; valores maliciosos/arrays são descartados para PT.
- `routes/web.php` registra **somente `GET /demo`** na mesma condição dupla de `GET /`: `APP_ENV=staging` + `CRM_STAGING_MARKETING_PREVIEW=true`. No perfil production/testing continua indisponível.
- Headers no-store, noindex/nofollow e CSP proibindo formulários, scripts e frames.
- Unidade de teste exige seis registros/funil estático, três tarefas, nenhum endereço, nome ou dado real, conteúdo idêntico entre idiomas, e todos os flags de ações em `false`.
- O GitHub Actions sobe contêineres reais descartáveis e faz smoke por HTTP: homepage → link de demo, PT/EN/ES, fallback hostil, cartões fictícios, ausência de formulários, `POST /demo=405` e noindex/CSP, antes de executar testes de backup/restore e migração/role RLS já aprovados na PR #574.

## O que o dashboard NÃO faz

Não é uma sessão autenticada; não permite acessar os leads/tarefas PostgreSQL sintéticos de laboratório; não conecta a dados reais, não muda o Kanban, não envia lembretes ou mensagens, não oferece trial, não cria conta/empresa, não cobra, não consulta Asaas e não pode visualizar clientes. O visual é uma **prévia funcional de navegação**, não uma homologação de serviços produtivos.

Próximos critérios de homologação: login/MFA independente, papéis e autorização revisados, vínculo real ao tenant, migrações aprovadas, política de expiração/consentimento LGPD, catálogo e preço homologados, Asaas Sandbox autenticado e webhook, backups offsite, TLS/domínio e demonstração em VPS controlada. Gates abertos: [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416), [concorrência #564](https://github.com/rafhael100/imagine-crm/issues/564), [lançamento #557](https://github.com/rafhael100/imagine-crm/issues/557).
