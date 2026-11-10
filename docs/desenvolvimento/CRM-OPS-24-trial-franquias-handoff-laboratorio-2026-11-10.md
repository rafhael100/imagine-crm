# CRM Imagine — OPS-24: trial e franquias, laboratório REV-F5

**Data do levantamento:** 10/11/2026 (America/Sao_Paulo).  
**Ramo permitido:** `dev/fast-track-crm-2026-11-04`.  
**Estado:** implementação PHP e interface **somente com dados fictícios**, sem autorização para clientes.

## 1. Entregas verificáveis

| Recorte | Código e resultado | Limitação |
|---|---|---|
| [#142](https://github.com/rafhael100/imagine-crm/pull/142) · REV-F5-01 | `SyntheticTrialQuotaOverview` une os componentes pré-existentes `TrialWindowAdvisory` (14 dias) e `QuotaSnapshotReadout`. Mostra a fase do trial, segundos/dias remanescentes, cartões de franquias, contagem por banda. SHA DEV `1030b9e62237ca538762c598b8e1bafbd679fee5`. | Não consulta elegibilidade, CNPJ, plano, usuário, tenant, MFA, cobrança ou situação real de trial. `authorized=false`, `can_activate=false`, `can_charge=false`, `can_enforce=false`. |
| [#143](https://github.com/rafhael100/imagine-crm/pull/143) · UX-REV-F5-01 | Dashboard offline: três fases (antes, durante, depois) e quatro estados de franquia (disponível, no limite, excesso e capacidade zero). PHPUnit compara os exemplos com as classes PHP; Chromium comprova as interações, inclusive a 320px. SHA DEV `03279c48836c376a72bf7c2f40ebba03bbb1b2d7`. | Exibição estática, sem API, relógio real, login, alterações de plano ou faturamento. Mantém 9 telas e CSP offline. |
| [#144](https://github.com/rafhael100/imagine-crm/pull/144) · REV-F5-02 | `SyntheticQuotaImpactPreview`: cenários de uso atual + aumento hipotético, de 1 a 12 medidores distintos. Detecta `in_range`, `at_limit`, `would_exceed`, `already_over`, `zero_capacity`, preserva números e rejeita duplicatas/entradas inválidas. SHA DEV `812bedbce547c631d2afaa563e3be08326626880`. | Nenhuma reserva de franquia, escrita, bloqueio, permissão ou preço; `authorized=false`, `can_reserve=false`, `can_increase=false`, `can_charge=false`. Zero não equivale a ilimitado. |
| [#145](https://github.com/rafhael100/imagine-crm/pull/145) · UX-REV-F5-02 | Prévia offline sobre 4/20 leads fictícios, com simulação de aumentos 0, 10, 16 e 17, status e saldo calculados. Testes PHP/Chromium para 320px e limites. SHA DEV `fddcf15c52912a75afe0798d1af724fea672011b`. | Sem consulta de dados reais, criação de leads, solicitação de upgrade, pagamento ou bloqueio. |

## 2. Evidências de CI na DEV (não substituem homologação)

- **PR #142**, SHA `1030b9e6`: [Laravel/F0 #42752837537](https://github.com/rafhael100/imagine-crm/actions/runs/42752837537), [PostgreSQL/RLS #42752837544](https://github.com/rafhael100/imagine-crm/actions/runs/42752837544) — ambos `success`.
- **PR #143**, SHA `03279c48`: [Laravel/F0 #42753286028](https://github.com/rafhael100/imagine-crm/actions/runs/42753286028), [PostgreSQL/RLS #42753285986](https://github.com/rafhael100/imagine-crm/actions/runs/42753285986) — ambos `success`.
- **PR #144**, SHA `812bedbc`: [Laravel/F0 #42753689673](https://github.com/rafhael100/imagine-crm/actions/runs/42753689673), [PostgreSQL/RLS #42753689544](https://github.com/rafhael100/imagine-crm/actions/runs/42753689544) — ambos `success`.
- **PR #145**, SHA `fddcf15c`: [Laravel/F0 #42754127338](https://github.com/rafhael100/imagine-crm/actions/runs/42754127338) — `success`; [PostgreSQL/RLS #42754127309](https://github.com/rafhael100/imagine-crm/actions/runs/42754127309) — consultar conclusão antes de declarar verde.

## 3. Contratos operacionais que NÃO foram implementados

**Trial real:** elegibilidade de organização/CNPJ, deduplicação por CNPJ, verificação de e-mail e telefone/OTP, início confiável do período, política de reativação, múltiplas organizações, estados ativos/suspensos/cancelados, antifraude, conversão, retenção pós-trial, obrigações legais e efeitos em sessões. `TrialWindowAdvisory` calcula **apenas** uma cronologia aritmética sobre timestamps já fornecidos.

**Franquias reais:** medidores de origem autoritativa, limites por plano, consumo transacional concorrente e idempotente, reservas/reversões, alterações de plano, cortesias, créditos de IA, tamanho de anexos, limites de funis/automação, bloqueios e exceções. `QuotaUsageProjection` e os novos readouts **não são enforcement**.

**Cobrança real:** catálogo de planos/preços aceito por FIN/CON, contratação, emissão de cobranças, Pix/Pix Automático, descontos, inadimplência, trilha de faturamento, reembolso, segurança e consentimento. Uma banda de excesso não autoriza cobrança.

**Segurança e implantação:** políticas por ação/recurso/campo/tenant, sesssão/MFA/membership, revogação, RLS não bypass, auditoria, privacidade/LGPD, testes adversariais, implantação em homologação e aceite independente. O teste [SEC-ROUTE-01](https://github.com/rafhael100/imagine-crm/pull/138) continua impedindo publicação de qualquer rota pública além do health `GET|HEAD /up`.

## 4. Gates e encaminhamento

1. [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22): catálogo R01-D01..D05, CAM/EST e decisões de autoconsulta real **ainda não homologados**; diretrizes anteriores eram documentais e não configuram `ALLOW`.
2. [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27): revisor independente SEG/DBA **não designado**, sem parecer P0/P1 itemizado. A autoria do código e CI não equivalem a independência de revisão.
3. CORE/FIN/CON/LGPD/AUT/QA, teste real de backup/restore e ativação de infraestrutura seguem condicionados a suas competências.
4. Antes do próximo pacote operacional, elaborar contratos revisados de trial/quotas/billing e autorização separada por escopo, com testes negativos de concorrência, revogação, tenant e transparência de faturamento.

**Garantias desta rodada:** nenhuma modificação de `main`, publicação em VPS/produção, API operacional, `ALLOW`, senha/segredo real, cliente real ou contratação de serviço adicional.
