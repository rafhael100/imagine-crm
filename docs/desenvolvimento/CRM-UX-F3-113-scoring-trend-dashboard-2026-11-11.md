# CRM UX F3-113 — Painel local de evolução de scoring

**Data:** 11/11/2026 · **Escopo:** protótipo HTML offline e validação real do PHP F3-112

## Interface e navegação

O laboratório recebe um **sexto cartão** em `prototypes/index.html`, apontando para `prototypes/f3-113-scoring-trend/index.html`. A nova tela alterna entre quatro histórias fictícias (crescimento, oscilação, queda e ausência de amostras), apresenta primeira/última pontuação, variação, quantidades de aumentos/quedas/estabilidades e mudanças de faixa frio/morno/quente.

O gráfico é desenhado inteiramente com barras CSS e nós de texto `textContent`, sem dependência externa, canvas remoto ou upload. Todos os dados são números sintéticos e instantes UTC fixos no HTML, sem identidade, evento real, principal, lead, empresa ou política personalizada. Os botões usam `aria-pressed` e foco visível, o status usa `aria-live`, e o layout contempla 320px. O estado vazio impede que a ausência de itens fictícios seja confundida com inexistência de leads reais.

## Provas obrigatórias

`OfflineScoringTrendParityTest` extrai as quatro sequências JSON do próprio HTML e compara os valores mostrados **com `SyntheticScoreTrendPreview` real**, incluindo faixas, delta e contagem de movimentos. Todas as flags de autenticidade, autorização, notificação, automação e persistência são comprovadamente `false`.

`check-offline-scoring-trend-browser.php` valida as quatro histórias em **Chromium real** desktop (1100px) e mobile (320px), pressiona os botões, verifica foco, exclusividade, barras em percentuais, números esperados, aviso de dados fictícios, status de não confirmação e ausência de overflow horizontal. A CI F0 exige o novo smoke. PHPUnit e Chromium do hub foram ajustados de cinco para seis cartões sem apagar as rotas anteriores.

## Limites

A amostra não é histórico verificado nem score real. O motor não define pesos ou regras aprovadas por organização. `authorized/can_rescore/can_notify/can_trigger_automation/can_persist` continuam falsos e nenhuma decisão automática é acionada. Para o módulo oficial são necessários proveniência, eventos auditáveis, direitos por tenant/atributo, catálogo de pesos e revisão de segurança.

Nenhum novo endpoint, SQL, job, credencial, custo, VPS, `main` ou produção. CORE/SEG/DBA/LGPD permanecem NO-GO. O PR UX draft #255 deve reconciliar os seis links antes de eventual aceite futuro; esta entrega não o integra.
