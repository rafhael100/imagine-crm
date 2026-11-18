# CRM-INTEG-CANDIDATE-02 — Candidato único após UI real, segurança e revisão visual

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN / NO-ALLOW / NO-CHARGE / NO-GO**

## Consolidação incremental auditável

O usuário exige continuidade para finalizar o Imagine CRM sem criar novas pausas de rotina. A [issue #557](https://github.com/rafhael100/imagine-crm/issues/557) exige consolidar o código antes de propor uma homologação operacional, evitando tomar uma cadeia de PRs laboratoriais como uma versão única funcional.

A [PR #588](https://github.com/rafhael100/imagine-crm/pull/588) consolidou a primeira linha. Desde então foram acrescentados controles de teste adversarial, **logomarca original**, Chrome real em 360/768/1440 e uma galeria offline revisável na [PR #595](https://github.com/rafhael100/imagine-crm/pull/595), enquanto a DEV avançou em Agents.

A HEAD consolidada desta branch é **filha direta da DEV verificada `d7c7aacc766c7ade4c65dcd211ed4569442144a9`**, e aplica 106 alterações próprias do candidato sobre essa árvore. O último `merge-base` foi `2c97f1dd82c59d42d33e902dade180c6f92431be`. A comparação de três árvores Git completas (não truncadas) encontrou **seis arquivos novos na DEV** de AIA/PostgreSQL/HMAC e **nenhum conflito** com os 106 arquivos do candidato. Os arquivos AIA da DEV foram preservados integralmente, sem sobrescrita e sem reescrever branches existentes.

## Escopo presente na mesma HEAD

- Laravel e PHP com portal comercial PT/EN/ES, identidade original Imagine Node (#F9941D/#171B19), arquivo PNG original byte a byte, Kanban e tarefas fictícios, persistência PostgreSQL16 restrita em staging CI e links realmente navegáveis.
- Leads/Tarefas, eventos append-only, integração Sites HMAC e checagens de revogação com dados fictícios, RLS entre tenants e sessões PG separadas, sem direitos de negócio liberados.
- Billing Asaas de laboratório com gateway Pix/assinatura e consulta fake; comparador Asaas/C6 Bank puramente simulado, sem segredos, token, contrato ativo nem chamadas de pagamento.
- Nginx privado loopback, rejeição Host externo 421, CSP/noindex, produção simulada sem endpoints, restauração da base Laravel migrada em segundo PostgreSQL16 sem rede.
- Ataques HTTP adversariais entre requisições/tenants, 9 screenshots reais Chromium (páginas comercial/Kanban/Kanban PostgreSQL, 360/768/1440), **galeria offline** e manifest de hashes, artifact temporário em GitHub Actions.
- Avanços atuais da DEV em Agents/HMAC/nonce preservados sem edição, conforme comparação das árvores antes do commit.

## O que os testes ainda NÃO autorizam

Não existe login real com MFA, sessão vinculada a credencial verificada, autorização por papel/recurso/empresa com revisão independente, trial real com OTP/CNPJ, CRUD de contatos operacionais, cobrança Asaas/C6 autenticada, NFS-e, integrações reais Portal/Sites, gestão de segredos produtiva, implantação TLS/DNS na VPS Leaseweb, teste offsite/PITR ou aceite independente. As tabelas de demonstração continuam contendo apenas exemplos artificiais; autorização `DenyByDefaultAuthorizer` não deve ser alterada para ALLOW.

Este candidato é **um agrupamento para revisão**, não lançamento nem confirmação de status das 67 pendências CORE. Qualquer novo avanço na DEV após o SHA acima exige nova reconciliação das três árvores e CI. Devem passar todos os workflows da HEAD exata **desta PR**, não apenas das PRs ancestrais, antes de declarar o recorte técnico validado.

Gates ativos: [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416), [revogação #564](https://github.com/rafhael100/imagine-crm/issues/564) e [lançamento #557](https://github.com/rafhael100/imagine-crm/issues/557). É proibido afirmar conclusão do SaaS sem homologação real, evidências negativas e aceites.
