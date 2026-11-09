# UX-F3-16 — Painel demonstrativo de franquias (offline)

**Data:** 09/11/2026. **Base DEV:** UX-F3-15 no PR #70. Recorte seguro e apenas ilustrativo.

## O que foi acrescentado

A tela já existente **Visão geral** recebeu um bloco estático com três `<meter>` HTML acessíveis: leads inventados **4/20**, usuários ilustrativos **1/2** e automações ilustrativas **0/2**. Não foram adicionadas novas telas ou campos de dados e a navegação continua em nove telas.

A apresentação tem título próprio por `aria-labelledby`, rótulos explícitos para leitores de tela, valores textuais, grade responsiva e avisos de **dados fictícios** e ausência de qualquer autorização.

## Limites de escopo

Os valores são strings estáticas no HTML. **Não há ligação com ENT-01/02/03/04 PHP**, autenticação, banco, medição real, plano, trial, faturamento, uploads ou event sourcing. O público não pode editar, inserir, enviar ou gravar dados; nenhuma API é chamada.

Os limites ilustrativos, mesmo semelhantes ao trial em planejamento, **não afirmam uma quota real contratada nem um direito comercial**. Futuro dashboard produtivo exigirá fonte auditável, tenant/membership verificados, políticas de campos, entitlements, estados de cobrança, privacidade, isolamento RLS e revisão de UX/WCAG.

## Evidências exigidas

- `OfflinePrototypeQuotaDashboardTest`: estrutura acessível, número e rótulos dos três medidores, dados exclusivamente fictícios e CSP offline.
- `check-offline-prototype-browser.php`: Chromium real em 520 px, navegação até dashboard e três medidores previstos.
- `check-offline-prototype-320.php`: Chromium com viewport real de 320 px, ausência de overflow e valores fixos corretos.
- Regressões dos filtros, navegação e ordenação de leads fictícios permanecem obrigatórias.

Merge apenas na branch `dev/fast-track-crm-2026-11-04` após revisão do diff e F0/PostgreSQL CI verde na SHA exata. **Sem `main`, deploy, VPS, rotas, grants, migrations, rede, informações de clientes ou custos.**
