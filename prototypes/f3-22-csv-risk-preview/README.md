# UX F3-22 — Prévia de alertas CSV, exclusivamente fictícia

Abra `index.html` diretamente via `file://`. Não há servidor web, conta, dados pessoais, importação, download, parser CSV, XLSX, armazenamento ou rede.

## O que a tela mostra

Duas amostras de **relatórios inventados**:
- **A**: estrutura com três colunas, duas linhas de dados e nenhum alerta. O aviso informa que isso **não certifica** segurança ou permissão para importar.
- **B**: estrutura com quatro colunas, três linhas de dados e cinco alertas: um cabeçalho vazio, um cabeçalho duplicado (colunas 1 e 3), uma linha de largura diferente (linha 3), um prefixo de fórmula (linha 2, coluna 3) e um apóstrofo ambíguo (linha 3, coluna 2).

A tela exibe apenas quantidades, posições e motivos, sem reproduzir conteúdo de uma célula, contato ou cabeçalho. Botões nativos `aria-pressed`, legenda `aria-live`, tabela com contêiner rolável e CSS para 320 px permitem revisar o fluxo sem capturar informações.

## Evidência técnica

O PHPUnit `OfflineCsvRiskPreviewParityTest` extrai as duas estruturas JSON fixas do script inline e compara seus **metadados** com `CsvStructurePreview::summarize`, que por sua vez reutiliza `CsvCellRiskPreview` F3-20. Os dois serviços PHP processam somente matrizes sintéticas do teste, independentes do navegador. Isso evita divergência entre os números ilustrados e o algoritmo de laboratório; **não** implementa integração de importação do navegador para PHP.

`scripts/ci/check-offline-csv-risk-preview-browser.php` usa Chromium real para alternância, focos, renderização dos cinco alertas, limpeza de linhas ao retornar à amostra A e ausência de transbordamento de página em viewport de 320 CSS px. O workflow `ci-f0.yml` exige esse smoke, sem dependência nova.

**Limitação:** a prévia é uma demonstração de apresentação dos diagnósticos, não uma inspeção de arquivo real, XLSX, proteção contra todas as variantes de fórmula, decisão de encoding, mapeamento operacional de colunas, deduplicação, autorização de exportação ou conformidade LGPD. Não há chamada a banco, API, tenant, `ALLOW`, migrações, VPS, produção, custo ou alteração de `main`. Os gates de [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) e do [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) permanecem abertos; o segundo continua em rascunho.

## UX F3-24 — Prévia separada dos conflitos de mapeamento

A mesma página possui uma seção **independente das amostras de estrutura acima**. Ela apresenta dois relatórios estáticos do mapeamento de colunas da amostra A: (A) três atribuições sem conflito identificado e (B) três atribuições com uma coluna não mapeada, origem duplicada e destino fictício repetido. A interface apresenta somente contagens e posições, nunca o conteúdo do cabeçalho nem os nomes dos destinos.

As informações são deliberadamente não operacionais. Não há campo para escolher destino real; `fixture_*` é um marcador inventado. Uma prévia sem alertas continua com **revisão humana obrigatória**, sem autorização de importação ou exclusão de colunas não mapeadas.

O `OfflineCsvMappingPreviewParityTest` compara os dois relatórios JSON com o `CsvMappingPlanPreview` PHP F3-23 usando matrizes fictícias. O smoke em Chromium desktop e 320 CSS px também alterna os dois exemplos, confirma os conflitos e verifica a limpeza dos resultados antigos. O produto real ainda dependerá das políticas de encoding, validação dos campos, autorização por tenant e aprovação da Central IN-118.

## UX F3-26 — Diagnósticos por linha, sem importação

A seção **Revisão por linha — dados fictícios** acompanha a amostra de estrutura A ou B. Para cada linha de dados ela mostra somente número original, quantidade de colunas e tipos de alertas. Valores, cabeçalhos e nomes de campos continuam ocultos.

- Exemplo A: duas linhas de dados, nenhuma sinalizada pelo analisador limitado; **ambas exigem revisão**, sem autorização de importação.
- Exemplo B: três linhas de dados, duas sinalizadas. A linha 2 tem prefixo semelhante a fórmula na coluna 3; a linha 3 tem quantidade de colunas divergente e apóstrofo ambíguo na coluna 2. A linha 4 não apresenta alertas detectados, mas também não é aprovada.
- Alertas globais de cabeçalho e conflito de mapeamento permanecem nos respectivos resumos; não são atribuídos artificialmente a uma linha de dados.

`OfflineCsvRowReviewParityTest` confronta os dois relatórios imutáveis `ROW_REPORTS` com a análise PHP `CsvRowReviewPreview` F3-25, usando matrizes sintéticas e atribuições `fixture_*`. O smoke Chromium F0 exercita as contagens, a renderização dos motivos e a limpeza de linhas antigas em desktop e 320 CSS px.

**Sem importação real, direitos de uso, seleção de CSV, persistência, ALLOW, autorização de organização/usuário, exclusão de linhas, conclusão de revisão ou upload.** A interface é somente uma demonstração de apresentação dos resultados da análise sintética, não a ferramenta operacional.

## UX F3-28 — escolhas de revisão inteiramente simuladas

A seção **Simulação de revisão das linhas fictícias** apresenta posições e estados temporários, nunca valores de células, nomes de contatos ou informações de empresas. Em cada linha há botões locais para voltar a pendente, simular revisão sem alertas ou simular necessidade de correção. **A opção “sem alertas” fica desabilitada se aquela linha tiver problema detectado**. Alterar a amostra estrutural A/B apaga todas as escolhas para evitar decisões residuais de outra amostra.

As escolhas são mantidas apenas em um `Map` no JavaScript desta página; **não há persistência, login, identidade verificada, token, importação nem certificação de aprovação humana**. Os botões usam rótulos claros, `aria-pressed`, e preservam o foco após redesenhar a tabela. A tabela possui rolagem horizontal local no viewport de 320 CSS px, sem alargar a página. Zero pendências significa apenas que os estados de simulação foram preenchidos, **não** que o lote possa ser importado.

`OfflineCsvReviewChoicesParityTest` confere resultados esperados dessas escolhas contra `CsvRowDispositionPreview` F3-27 usando matrizes inventadas, incluindo um lote todo classificado que ainda retorna `human_verified=false` e `import_permitted=false`. O smoke Chromium desktop/320 px exercita seleção, opção desabilitada nas linhas sinalizadas, restauração do foco, contagens e limpeza dos estados ao trocar a amostra. A revisão verdadeira requer contrato próprio com ator autenticado, snapshot imutável, política por campo/tenant e trilha auditável, além dos gates SEG/DBA, CORE, LGPD e Central IN-118.

## UX F3-31 — aviso de lote alterado, sem acesso a dados reais

Foi incorporada à mesma página a seção **Mudanças de versão — comparação fictícia**, independente dos demais painéis. Ela demonstra quatro situações inventadas: (1) sem alteração detectada; (2) uma célula alterada na linha 2; (3) destino do mapeamento alterado na associação 2; (4) uma linha acrescentada na posição 4. O relatório mostra apenas contagens, tipo e posição da modificação — **não mostra valores, identificadores, nomes de destino ou o fingerprint**.

Os quatro objetos `SNAPSHOT_REPORTS` são metadados estáticos, verificados em `OfflineCsvSnapshotComparisonParityTest` contra `CsvSnapshotRevisionDeltaPreview::compare` executado em PHP com matrizes/mapeamentos fictícios. Portanto a demonstração não recalcula hashes no navegador nem interpreta arquivos reais. `check-offline-csv-risk-preview-browser.php` percorre os quatro cenários no Chromium, em desktop e **320 CSS px**, verificando foco do teclado, seleção `aria-pressed`, posições corretas e limpeza das diferenças anteriores.

Uma versão alterada significa que as escolhas **associadas àquela versão** seriam consideradas desatualizadas no laboratório. Os botões de revisão fictícia em outra seção **não são as mesmas escolhas** e não são alterados por este exemplo independente. Em qualquer cenário, inclusive sem mudanças, **identidade humana não é verificada e importação continua proibida**.

Este exercício não estabelece snapshot imutável ou transacional, autenticação, assinatura, trilha, idempotência, política de retenção, integridade de dados reais ou autorização multiempresa. O checksum público de F3-29 não é um mecanismo de segurança suficiente para produção. CORE/IAM/S01, LGPD, Central IN-118 e parecer SEG/DBA independente permanecem obrigatórios; nenhum acesso real, API, banco, upload, `ALLOW`, VPS, `main` ou deploy foi habilitado.

## UX F3-34 — conflitos e idempotência simulados

A prévia inclui **Conflitos de revisão — simulação**, com seis resultados isolados do restante da página: estado inicial, primeiro registro fictício, repetição idempotente, mesma chave com payload diferente, segunda revisão com versão antiga e alteração de lote inventado. São objetos estáticos, **não** botões que executam operações no PHP, no servidor ou em qualquer dado de cliente.

O arquivo `OfflineCsvReviewRaceParityTest.php` executa a classe PHP `CsvReviewRaceFixture` F3-33 com matrizes fictícias e confronta, campo a campo, os seis resultados apresentados em `RACE_REPORTS`. O smoke Chromium F0 alterna todas as situações no desktop e verifica foco, estado, rolagem e ausência de autorização no viewport de 320 CSS px.

**Registros e versões exibidos são simulados e independentes de outros painéis.** O usuário não fornece CSV, revisão, chave, identidade ou campo real. O fluxo não tem autenticação, mutex, transação, banco durável, idempotência interprocessos, trilha imutável ou upload. `human_verified=false`, `review_required=true` e `import_permitted=false` em todos os seis estados. A interface não libera `ALLOW` nem importa contatos, mesmo quando uma revisão fictícia é exibida como registrada. SEG/DBA, CORE/IAM, S01, LGPD, Central IN-118 e o contrato F3-32 seguem sem GO.
