# CRM OPS-64 — Kernel HTTP de testes, canais fictícios e candidata navegável (13/11/2026)

**Snapshot:** DEV `a15023af2d3dbd872a5bd3be1e303e3b8d388c1e` (após PR #502) e `main` `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012` (preservada). O que segue foi confirmado diretamente nas PRs e no GitHub Actions, sem pressupor liberação de produção.

## Entregas realmente integradas à DEV

| PR | Commit de merge | Prova técnica | CI na HEAD testada |
|---|---|---|---|
| [#500](https://github.com/rafhael100/imagine-crm/pull/500) | `9210b71680dd` | Requisições HTTP reais do **kernel Laravel 13**, montadas **somente dentro do PHPUnit**; OTP correto/errado/replay e duplicidade CNPJ recebem resposta pública genérica e `can_register=false` | [F0](https://github.com/rafhael100/imagine-crm/actions/runs/43475285505), [PG16/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/43475285519), [Sites](https://github.com/rafhael100/imagine-crm/actions/runs/43475285907), [AIA](https://github.com/rafhael100/imagine-crm/actions/runs/43475285566) — 4/4 success |
| [#501](https://github.com/rafhael100/imagine-crm/pull/501) | `1f14448a55bb` | Workflow dedicado exige `php artisan route:list --json` com exatamente **/up GET/HEAD** e rejeita fixture de HTTP importada em `app/routes/bootstrap` | [F0](https://github.com/rafhael100/imagine-crm/actions/runs/43476337972), [PG16/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/43476338107), [Sites](https://github.com/rafhael100/imagine-crm/actions/runs/43476337955), [AIA](https://github.com/rafhael100/imagine-crm/actions/runs/43476338201), [selo HTTP](https://github.com/rafhael100/imagine-crm/actions/runs/43476338341) — 5/5 success |
| [#502](https://github.com/rafhael100/imagine-crm/pull/502) | `a15023af2d3d` | Implementação **paralela** que adicionou seleção de e-mail/telefone fictícios, binding HMAC por canal e modelo WhatsApp/e-mail sem entrega real à mesma fixture do kernel HTTP | Confirmação do merge e CI verde na DEV; conferir checks da PR antes de eventual mudança operacional |

**Nota de rastreabilidade:** as PRs **#501 e #502** reutilizaram a sigla interna **F3-253** em frentes paralelas. Identifique o trabalho pelo **número e título da PR**, e reconcilie os IDs documentais antes de fechamento de milestones; não fundir os dois escopos.

Após #501, os quatro workflows pós-merge também concluíram success na SHA `1f14448a55bb23a6a81ef386696ba5a237cb485b`. A SHA mais recente depois da PR paralela #502 é a do snapshot acima.

## Pacote navegável para avaliação de UX — ainda DRAFT

[PR visual #485](https://github.com/rafhael100/imagine-crm/pull/485), HEAD `92257faba8b17bd7795cac35c2ebb1b8412cf628`: **5/5 workflows success** ([F0](https://github.com/rafhael100/imagine-crm/actions/runs/43477714345), [PG16/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/43477714217), [Sites](https://github.com/rafhael100/imagine-crm/actions/runs/43477713947), [AIA](https://github.com/rafhael100/imagine-crm/actions/runs/43477714315), [Chromium](https://github.com/rafhael100/imagine-crm/actions/runs/43477714199)). Inclui a logomarca **original** do Imagine Portal Node, sem alteração binária, paleta aprovada, navegação por quatro etapas (empresa, código, revisão e trial), 14 dias, sete franquias comprovadas contra o Core, e simulações de duplicidade e expiração.

Para experimentar: em [Actions #43477714199](https://github.com/rafhael100/imagine-crm/actions/runs/43477714199), seção **Artifacts**, baixe `f3-240-imagine-cadastro-trial-navegavel` (**ID 14062479529**), extraia e abra `index.html` no navegador. A pasta `assets` deve permanecer junto do HTML. A demonstração usa somente o código fictício `012345`; nenhum formulário, HTTP, SQL ou mensagem real. Há cinco screenshots no artifact `f3-240-imagine-otp-offline-desktop-mobile` (**ID 14063373304**). Ambos expiram em **20/11/2026**.

A [issue de aceite UX #416](https://github.com/rafhael100/imagine-crm/issues/416) recebeu o pacote e evidências, mas **a revisão humana ainda não foi concedida**. Verificar contraste, teclado, toque e zoom efetivo 200%; o viewport 550 px simula largura menor, não prova zoom real.

## Ordem de liberação pendente

- **SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259)**: dependências de autenticação/tenancy, contador OTP atômico, persistência e unicidade trial/CNPJ em transação, prova de contato, custódia de chaves, consentimento/LGPD, auditoria, SMS/e-mail/WhatsApp aprovados. Provas descartáveis PostgreSQL [#468](https://github.com/rafhael100/imagine-crm/pull/468), [#470](https://github.com/rafhael100/imagine-crm/pull/470), [#477](https://github.com/rafhael100/imagine-crm/pull/477) permanecem **DRAFT/NO-MERGE**.
- **UX [#416](https://github.com/rafhael100/imagine-crm/issues/416)**: avaliar e aprovar visual das telas antes de qualquer merge UI, publicação ou staging online.
- Só depois abrir ambiente de homologação web isolado com dados inventados, autorização real e transportes aprovados. Até lá, o kernel é exercitado apenas pelo **PHPUnit**, não por um servidor/URL pública.

**NO-GO operacional:** nenhuma rota real de cadastro foi publicada, nenhum OTP enviado, nenhuma conta/trial criado e nenhuma alteração em `main`, VPS ou produção ocorreu nesta sequência.
