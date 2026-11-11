# Imagine CRM — como abrir a demonstração offline

**Versão para revisão UX (não é um sistema de produção).** Esta coleção está na PR [#246](https://github.com/rafhael100/imagine-crm/pull/246), branch `dev/f3-54-reconciliation-ui-focused-dev-2026-11-11`, sem merge nem deploy.

## Executar sem instalar nada

1. No GitHub, abra a branch acima e use **Code → Download ZIP** para obter uma cópia local do código da branch. Descompacte no computador.
2. Abra `prototypes/index.html` no Chrome/Edge/Firefox. O índice oferece **seis caminhos** já existentes dentro do próprio ZIP: CRM principal, simulador de funil, conferência de entregas, painel de acompanhamentos, lista de acompanhamentos e prévia de riscos CSV.
3. Clique nos links para revisar filtros, seleção com teclado, detalhes, estados, movimentação fictícia de leads e desfazer/restaurar. Use o botão Voltar do navegador para regressar ao índice.
4. As informações são **100% fictícias**. Você não precisa de PHP, Composer, PostgreSQL, Node.js, serviços externos nem credenciais. Não existem rotas de negócio, API, autenticação, armazenamento de dados, envio de contato, importação de arquivos, notas ou outros atos operacionais.

## O que foi testado

- PHPUnit `OfflineDemoHubContractTest`: exatamente seis links `file://` relativos, arquivos reais no repositório, CSP offline e ausência de scripts/formulários.
- Chromium real via `scripts/ci/check-offline-demo-hub-browser.php`: apresentação no desktop e em **320 CSS pixels**, sem overflow, seleção por foco e abertura do painel de instruções HTML nativo.
- Chromium `check-offline-sales-pipeline-browser.php`: quatro leads inventados, transições entre etapas, undo, reset, limites de etapa, histórico e teclado, em desktop/mobile.
- Chromium `check-offline-delivery-reconciliation-browser.php`: seis casos fictícios de transporte, filtros, prioridades e negação de reenvios sem prova.
- Scripts legados existentes testam CRM principal, painel e lista de acompanhamentos e revisão fictícia CSV.

**Os testes são evidência de funcionamento offline e não de homologação do CRM real.** A captação pública continua bloqueada (Imagine Comercial F70); CORE/IAM/SEG/DBA/LGPD e revisão independente seguem necessários para implantar qualquer backend, permitir leads reais, pagamentos ou confirmar entrega.

## Onde ficam os arquivos

| Caminho no ZIP | Área da demonstração |
|---|---|
| `prototypes/index.html` | Tela inicial única |
| `prototypes/s01-prot-01/index.html` | Nove telas do CRM fictício |
| `prototypes/f3-55-sales-pipeline-lab/index.html` | Funil reversível sem persistência |
| `prototypes/f3-52-delivery-reconciliation/index.html` | Conferência técnica sem confirmação real |
| `prototypes/f4-15-followup-dashboard/index.html` | Indicadores de acompanhamento |
| `prototypes/f4-28-followup-worklist/index.html` | Lista de acompanhamentos fictícios |
| `prototypes/f3-22-csv-risk-preview/index.html` | Sinalizações de risco CSV em exemplos fixos |

Nenhum acesso à VPS, alteração de domínio/DNS, mudança na `main`, serviço pago ou disponibilização pública de dados de clientes foi feito para produzir esta coleção.
