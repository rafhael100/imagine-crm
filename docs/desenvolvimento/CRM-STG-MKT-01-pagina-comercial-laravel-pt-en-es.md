# CRM-STG-MKT-01 — Página comercial Laravel PT/EN/ES (prévia privada)

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN / NO-CHECKOUT**.

## Entrega implementada

A versão integrada do Imagine CRM possui muitos protótipos comerciais offline, mas `GET /` permanecia 404 na aplicação Laravel. Esta entrega implementa **uma página Blade real na aplicação**, porém somente para homologação privada.

- `StagingMarketingPreview::presentation()` monta conteúdo localizado em **português, inglês e espanhol**, com whitelist estrita de `pt`, `en` e `es`. Parâmetros manipulados/arrays/HTML não são aceitos e caem no português.
- Quatro cards de planos obtidos do **catálogo interno** `CommercialPlanReferenceCatalog` (Básico R$69, Essencial R$89, Profissional R$149, Empresarial R$299, valores mensais **de referência**). O método não aceita alteração de preço pelo request. Todos os cards permanecem `price_homologated=false` e `can_checkout=false`.
- `resources/views/crm-staging-marketing.blade.php`: layout responsivo clean, logo textual Imagine, identidade laranja `#F9941D` e grafite, dashboard **puramente ilustrativo** sem dados pessoais, quatro benefícios planejados, FAQ implícito de planos, indicação de prévia não contratável e três seletores de idioma com bandeiras 🇧🇷 🇺🇸 🇪🇸.
- `routes/web.php` registra **somente `GET /`** quando `APP_ENV=staging` e `CRM_STAGING_MARKETING_PREVIEW=true`. Em `production/testing` (incluindo o perfil F3-261 com Composer `--no-dev`), não existe rota comercial nem acesso à homepage.
- Resposta com `no-store`, `noindex,nofollow,noarchive`, `nosniff`, `Referrer-Policy: no-referrer` e CSP restrita sem scripts, iframes ou formulários. Sem analytics/cookies de marketing, fontes externas, botões falsos de contratação ou ações de escrita.
- O Compose privado ativa a flag apenas em **Nginx ligado a `127.0.0.1:18080`**; nenhum listener público foi adicionado.
- Unit tests verificam planos, idiomas, parâmetros hostis e proibição de signup/trial/checkout. O smoke CI de HTTP verifica PT/EN/ES, preço ilustrativo, noindex/CSP, fallback e `/signup`, `/login`, `/checkout`, `/api/trial` ainda 404, além do Core, Asaas, PG16 RLS e restore.

## Implicações

Esta é **uma demonstração privada navegável da página comercial**. Não significa que os planos, recursos ou preços estejam homologados nem que o usuário possa contratar. A página não está publicada em domínio público, não usa URL real do cliente e não foi implantada na VPS Leaseweb.

Ainda faltam: revisão comercial de descrições/preços/benefícios; identidade visual final com logo oficial; acessibilidade/UX externa; onboarding com login/MFA e CNPJ/OTP; trial 14 dias com regras de LGPD; checkout autorizado, Asaas Sandbox autenticado e conciliação; backend multiempresa operacional; TLS/domínio; backups/restore da VPS; revisão independente SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259), UX [#416](https://github.com/rafhael100/imagine-crm/issues/416), concorrência [#564](https://github.com/rafhael100/imagine-crm/issues/564), lançamento [#557](https://github.com/rafhael100/imagine-crm/issues/557).

**No merge, no deploy, no access grant, no real customer or payment.**
