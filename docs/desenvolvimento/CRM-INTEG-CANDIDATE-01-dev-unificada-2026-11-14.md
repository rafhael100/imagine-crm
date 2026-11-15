# CRM-INTEG-CANDIDATE-01 — Consolidação unificada na HEAD atual da DEV

**DRAFT / NO-MERGE / NO-DEPLOY / NO-GRANT / NO-PII / NO-CHARGE / NO-GO.**

## Por que esta entrega é diferente

A [issue #557](https://github.com/rafhael100/imagine-crm/issues/557) aponta como prioridade #1 **consolidar o candidato**, para não confundir dezenas de PRs empilhadas com um SaaS pronto. Os recortes de migração, Billing Asaas, leads/tarefas, homepage comercial, dashboard, recuperação e UX existiam em uma cadeia de branches que divergia da DEV, deixando difícil avaliar a integridade da solução combinada.

Este candidato **não faz merge** nem promove para main. Sua base é a DEV real `2c97f1dd82c59d42d33e902dade180c6f92431be` de 14/11/2026; o outro lado é a HEAD `c7a9960c1889817f84094ba4c37996d1ee249777` da PR #587. O `merge-base` observado era `7999b1e34a6cbd0ee1c5e2351067ad172e01bbad`. Comparações completas das árvores Git não truncadas revelaram **100 arquivos alterados no candidato** e **18 arquivos modificados na DEV desde a base comum**, com **zero sobreposição conflitante**.

Foi criado um commit **novo e revisável** que aplica somente as 100 alterações do candidato sobre a árvore da DEV atual, **preservando todos os 18 arquivos da DEV**, inclusive avanços de AIA/Agents. Não há force push, rewrite de branches existentes, merge ou deploy.

## Capacidades presentes no mesmo código — NÃO operacionais

- **Core:** laboratório multiempresa de leads/Kanban/tarefas com auditoria append-only, integridade/idempotência do Sites e provas PostgreSQL/RLS com dados fictícios e `DenyByDefaultAuthorizer` inalterado.
- **Billing:** clientes Pix, rascunhos de assinatura, consulta e reconciliação Asaas *somente via mocks/laboratório*, além de comparador de taxas fictícias Asaas × C6 Bank que não cria cobranças nem troca gateway já vinculado.
- **Frontend Laravel:** página comercial PT/EN/ES e painel Kanban navegável com dados fictícios fixos e uma variante consultando PostgreSQL de laboratório com `SELECT-only`/RLS, sob flags de staging; nenhuma área de cliente, login, trial ou checkout ativo.
- **Staging/recuperação:** Compose Nginx+PHP+PostgreSQL16 em `127.0.0.1:18080`, app role separada sem BYPASSRLS, migrations Laravel testadas, restauração real em outra instância PostgreSQL descartável, Nginx host guard 421 e `noindex`.
- **UX:** tokens locais da identidade original Imagine Node, navegação via teclado e alvos móveis de 44px; logo PNG canônico e auditoria UX externa ainda pendentes.
- **AIA:** permanecem os trabalhos mais novos de Agents incluídos nos 18 arquivos da DEV, sem remoção ou substituição pelo candidato.

## Evidências mínimas antes de qualquer integração posterior

1. **10/10 GitHub Actions `completed/success` na SHA exata deste commit integrado**, especialmente F0, PostgreSQL/RLS, Asaas, Leads PG16, Sites, Agents, F3 production-deny e Compose real privado com restore.
2. Verificar a base DEV novamente antes de qualquer decisão; se tiver avançado, registrar divergência e revalidar. CI verde em SHA anterior não serve.
3. Revisar integralmente o diff desta PR de 100+ arquivos (fonte e licença, permissões DB, rotas, CSP, dados de CI, `composer.lock`, .env, e compatibilidade AIA). O teste não substitui revisão.
4. Manter `main`, VPS Leaseweb, segredos, clientes reais e Asaas Sandbox/Produção **intocados**; não habilitar flags financeiras ou `ALLOW`.

## Bloqueadores de fechamento do CRM SaaS

Ainda não existe IAM/MFA real, principal validado, ACL operacional por tenant, cadastro e trial reais, CRM CRUD de clientes em staging, Asaas Sandbox com credenciais autorizadas e reconciliação real, NFS-e, integração operacional Sites/Portal, segredos administrados, TLS/DNS/monitoramento/backup offsite na VPS, UX aceita, contratos/privacidade e parecer independente SEG/DBA. Não tratar o candidato integrado como produto concluído. Gates: [#27](https://github.com/rafhael100/imagine-crm/issues/27), [#259](https://github.com/rafhael100/imagine-crm/issues/259), [#416](https://github.com/rafhael100/imagine-crm/issues/416), [#564](https://github.com/rafhael100/imagine-crm/issues/564), [#557](https://github.com/rafhael100/imagine-crm/issues/557).

Se e quando os responsáveis decidirem dar sequência à homologação real, o roteiro deve utilizar uma NOVA branch/release com segredos autorizados e validação independente; jamais usar os UUIDs e grants de laboratório como identidades de clientes.
