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
