# CRM UX F3-72 — Checklist visual dos avisos CSV do Core

**Data:** 11/11/2026. **Escopo:** protótipo totalmente offline, sem acesso a dados pessoais ou importação.

## Experiência implementada

Foi criada a página independente `prototypes/f3-72-csv-checklist/index.html`, acessível pela prévia CSV existente `prototypes/f3-22-csv-risk-preview/index.html`, sem alterar o hub `prototypes/index.html` que a candidata UX Portal↔CRM #255 ainda modifica.

A nova tela apresenta **quatro relatórios numéricos inteiramente fictícios** selecionáveis por botões acessíveis: ausência de avisos; células obrigatórias vazias + linhas repetidas + cabeçalho ausente; fórmulas perigosas; e colunas técnicas necessárias ausentes. A interface mostra totais de linhas, colunas, colunas sugeridas e avisos, além de tabela de **somente posições** de células/linhas/colunas. Não mostra cabeçalhos, valores, nomes, contatos ou conteúdo CSV.

Cada ação alterna um objeto estático sem formulário, input, drag/drop, upload, API, rede, cookie, local/session storage ou persistência. A CSP bloqueia conexões e recursos externos; há skip link, `aria-pressed` exclusivo, feedback por `aria-live`, criação de texto por `textContent` e layout responsivo para 320 CSS px. **Nenhum resultado permite importação**, inclusive quando não houver aviso.

## Paridade com o Core e QA no navegador real

`tests/Unit/OfflineCsvDiagnosticsChecklistTest.php` calcula os quatro relatórios usando o Core PHP **real** `SyntheticCsvImportPreview::inspectComposite` (F3-71), com CSV `,` e `;`, mapeamento exato, fórmulas, repetições e campos necessários. O teste compara os dados numericamente com as quatro constantes da página, garantindo que a demonstração não invente um comportamento diferente do parser. Também valida whitelist de links locais e ausência de captura, storage ou divulgação de payload.

`scripts/ci/check-offline-csv-checklist-browser.php` usa **Chromium real** em viewport 1100px e iframe de 320 CSS px, alterna os quatro cenários, verifica métricas, tabela, foco e `aria-pressed`, ausência de avisos antigos e de overflow horizontal. O novo smoke está no workflow F0, sem instalar dependências adicionais. O teste antigo `OfflineCsvRiskPreviewParityTest` foi atualizado para validar o novo destino relativo; sua página original continua intacta fora do link de navegação.

## Limites

A tela apresenta **metadados estáticos de testes** e não é importador, exportador, tratamento de dados pessoais, motor de regras reais, autorização por empresa, identificação de contato ou revisão de segurança independente. Nenhuma rota, API, SQL, IAM/ALLOW, migrations, VPS, deploy, custo, `main` ou produção foi alterada. CORE/SEG/DBA/LGPD e homologação funcional continuam NO-GO.
