# CRM UX F3-51 — Visões rápidas acessíveis para leads fictícios

Implementação incremental no protótipo offline existente do Imagine CRM. A página de leads mantém os quatro registros sintéticos e os controles de etapa, faixa, ordem e paginação já desenvolvidos; acrescenta quatro atalhos visuais que **combinam** esses controles sem introduzir novos dados, chamadas, permissões ou persistência.

## Visões disponíveis

| Visão rápida | Etapa | Faixa | Ordenação | Resultado com fixtures |
|---|---|---|---|---|
| Todos os exemplos | Todas | Todas | Original | 4 exemplos |
| Quentes primeiro | Todas | Quente | Maior pontuação | 2 exemplos, C antes de D |
| Em contato | Em contato | Todas | Original | 1 exemplo, B |
| Em qualificação | Qualificação | Todas | Original | 1 exemplo, C |

Selecionar um atalho chama as funções existentes `showLeadStage`, `showLeadTemperature` e `sortLeadFixtures`, portanto mantém filtros, aria-pressed, resumo ao vivo, paginação, tabela ordenada e ficha do lead sincronizados. Mudanças manuais nos controles individuais que não correspondam a qualquer preset fazem o resumo informar **Filtros personalizados: nenhuma visão rápida selecionada.**, sem fingir que um preset continua ativo. As mudanças não sobrevivem ao recarregamento; não usam armazenamento local.

## Acessibilidade e isolamento

Os quatro controles são `button type=button` com `aria-pressed`, agrupados em `role=group` e `aria-label`. O texto de resultado está em `role=status aria-live=polite aria-atomic=true`. Não há campos pessoais, formulários, autenticação, acesso por empresa, requisições a API, permissões reais, edição de estágio comercial, importação/exportação, gravação ou agenda.

`tests/Unit/OfflineLeadQuickViewsTest.php` verifica a estrutura HTML, valores dos presets, seleção inicial, composição das funções e ausência de canais de rede/armazenamento/PII. Os testes Chromium existentes em `scripts/ci/check-offline-prototype-browser.php` e `check-offline-prototype-320.php` foram ampliados para clicar em todos os atalhos e comprovar a seleção e as contagens corretas, inclusive **320 pixels reais**, com retorno a Todos sem overflow. O workflow `ci-f3-51-lead-quick-views.yml` executa PHPUnit e os dois smokes de navegador, além do bloqueio de rotas.

## Limites

Somente UX offline de demonstração. Não é lista real do CRM, busca de leads, scoring dinâmico ou lead autorizado. Portal–CRM operacional e F70 Comercial continuam dependentes de autenticação de serviços, autorização CORE, SEG/DBA/LGPD, staging, confirmação de recibos e consentimento antes de publicação. Nenhuma alteração da `main`, VPS, DNS, produção ou custos.
