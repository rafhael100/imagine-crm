# UX F3-54 — PR focalizada no estado atual da DEV (painel de conferência offline)

**Rebase de escopo, sem transplantar a pilha inteira:** a DEV `dev/fast-track-crm-2026-11-04` foi conferida em SHA `9e35d2224904ea7865cec054b01a7058e37dc86d` antes de criar esta branch. A tentativa de consolidar a pilha de PRs #220→#245 de uma só vez resultaria em 81 commits à frente, 21 commits atrás, e 61 arquivos de mudanças ao comparar contra a DEV naquela consulta. Como esse conjunto abrange testes de identidade/RLS e contratos críticos ainda condicionados à revisão SEG/DBA/LGPD, **não é seguro misturá-los com a entrega visual para DEV**.

Este recorte aplica **apenas a funcionalidade de UX offline F3-52/F3-53** diretamente sobre a DEV vigente: painel standalone com seis casos sintéticos, filtro e ordenação, fichas de histórico ilustrativo, regras de não confirmar/retransmitir automaticamente, link na tela de Leads fictícios e retorno ao protótipo principal. Seus testes de segurança e browser foram portados integralmente; não traz os commits experimentais anteriores F3-40 a F3-51 nem altera `app/Core`, SQL, `composer.lock`, IAM, CI de banco ou rotas.

## Arquivos
- `prototypes/f3-52-delivery-reconciliation/index.html`: interface estática e CSP bloqueando rede, sem coleta e sem persistência
- `prototypes/s01-prot-01/index.html`: link local na tela de leads, sem alterar as nove telas preexistentes
- `tests/Unit/OfflineDeliveryReconciliationDashboardTest.php` e `tests/Unit/OfflineDeliveryReconciliationNavigationTest.php`
- `tests/Unit/OfflinePrototypeResourceBoundaryTest.php` e `tests/Unit/OfflineFollowUpNavigationContractTest.php`: apenas destino offline estritamente autorizado e contagem correspondente de links
- `scripts/ci/check-offline-delivery-reconciliation-browser.php`: cliques e verificações em Chromium desktop e **320 CSS px reais**
- `.github/workflows/ci-f3-54-reconciliation-on-dev.yml`: regressões específicas além dos workflows F0 e PostgreSQL/RLS já existentes na branch de desenvolvimento

## Gates
PR draft diretamente contra DEV para revisão independente do UX, **sem merge automático**, sem alterar `main`, VPS, produção ou dados reais. Os PRs originais #243 e #245 permanecem como histórico da implementação; este PR substitui **apenas o trajeto de integração visual** no planejamento de revisão, sem afirmar que os PRs de laboratório foram absorvidos. `F70` Comercial, CORE/IAM, revisão SEG/DBA/LGPD, identidade de serviço, consentimento, recibo e armazenamento duráveis seguem bloqueados. Não interpretar teste em PostgreSQL efêmero como operação homologada.

## Ampliação controlada da mesma PR — prévia de riscos CSV já existente

A aba **Prévia de importação**, que permanece sem upload nem autorização de importação, agora abre por um hyperlink `file://` exclusivamente local o protótipo preexistente `prototypes/f3-22-csv-risk-preview/index.html`; o protótipo CSV já contém retorno ao CRM principal. Os testes `OfflineCsvRiskNavigationTest`, `OfflineCsvRiskPreviewParityTest` e `check-offline-csv-risk-preview-browser.php` foram incluídos no workflow dedicado. A whitelist local de destinos passa a incluir só este caminho aprovado, e o contrato de cardinalidade da página principal muda de três para quatro links (pular para conteúdo, acompanhamentos, conferência de entregas e prévia CSV). **Não** há acesso a arquivos reais nem aprovação de importação.

Assim a PR focalizada reúne duas jornadas de revisão UX disponíveis no código existente, preservando a base DEV e evitando novas PRs empilhadas apenas para acrescentar navegação.

## Simulador de funil offline — UX F3-55 (mesma PR, sem novo encadeamento)

A tela **Funil ilustrativo** do protótipo principal também aponta para `prototypes/f3-55-sales-pipeline-lab/index.html`, uma prévia **interativa porém exclusivamente em memória**. Quatro exemplos fictícios (scores 30, 55, 85, 70) podem ser movidos entre três etapas, desfeitos e restaurados; a interface oferece feedback, contadores de movimentos e histórico apenas local. Uma cópia de `LeadScoringBands` **não** foi criada: o teste PHP valida a paridade das fixtures com `SyntheticPipelineSummary` já implementado. Testes reais de Chromium desktop e 320px cobrem foco ao selecionar cards, avanço/retorno, undo/reset, limite de etapa, ausência de overflow e CSP sem rede.

A PR agora concentra **três jornadas de experiência offline** revisáveis diretamente contra DEV: conferência/reconciliação de entrega, prévia de riscos CSV já desenvolvida e simulador reversível de funil. Todas as integrações são links `file://` com whitelist exata, **sem** backend, importação, permissões, dados pessoais, dependências novas, mudança de esquema ou implantação.
