# CRM UX F3-85 — Prévia CSV estritamente fictícia e sincronizada com o Kanban

**Data:** 11/11/2026. **Escopo:** navegador local de demonstração, sem contatos, banco, exportação operacional ou integração externa.

## Funcionalidade entregue

O Kanban fictício ganhou uma seção **Prévia CSV dos cartões fictícios visíveis**, inicialmente oculta. O controle nativo **Mostrar/Ocultar prévia CSV fictícia** usa `aria-expanded`, preserva foco no botão e atualiza o conteúdo em um elemento `pre` somente por `textContent`. Não há upload, download, clipboard, `Blob`, API, arquivo criado ou armazenamento.

Ao abrir, a prévia mostra **somente a projeção técnica dos cartões visíveis nos filtros combinados**, com seis colunas de dados artificiais delimitadas por ponto e vírgula (padrão comum no Brasil):

`id;etapa;prioridade;acompanhamento;valor_ficticio;pontuacao`

Exemplo inventado: `F01;0;high;pending;1800;92`. A interface **não inclui nome, telefone, e-mail, organização, consentimento, cliente, número de lead real ou qualquer texto livre**. O serializador verifica todos os campos: ID `F01..F12`, etapa `0..3`, prioridades `high/normal`, estados `pending/done`, números inteiros seguros e pontuação `0..100`, com limite máximo de 12 linhas. Caso alguma estrutura não corresponda ao contrato, a prévia inteira é substituída por mensagem genérica sem refletir dados. Assim não existe célula arbitrária capaz de virar fórmula em planilha.

A prévia é derivada do **mesmo subconjunto da renderização**, sem manter cópia, cache ou segundo estado. Filtros por etapa/prioridade/acompanhamento, movimentos individuais e em lote, conclusão de acompanhamentos, criação de cartão fictício, undo e reset atualizam imediatamente as linhas visíveis; quando não há cartões, resta **somente o cabeçalho**.

## QA

`OfflineKanbanFunnelTest` exige contrato restrito das seis colunas, whitelist de IDs, enums/inteiros e uso de `textContent`, sem formulários, `Blob`, download, clipboard, rede ou storage.

`check-offline-kanban-browser.php` valida em **Chromium real** desktop + viewport 320 CSS px: oito cartões iniciais, filtro Alta (quatro), filtro Qualificação (um), movimento para outra etapa (cabeçalho apenas), desfazer, reset, criação F09 e undo, filtro vazio e abrir/fechar com foco e `aria-expanded`. A prévia tem overflow horizontal **interno**, e a página não apresenta overflow horizontal a 320 CSS px.

A CI mantém um limite explícito de **32.000 bytes UTF-8** para o HTML offline (antes 30.000), sem flexibilizar CSP, varredura de dados, conexões, testes de navegador ou políticas de não captura. F0, PostgreSQL/RLS, Sites e Agents precisam terminar `success` na HEAD exata, com diff revisado e branch sem defasagem para merge **somente DEV**.

## Segurança e gates

Este recurso é **pré-visualização de CSV técnico com dados fabricados**, não exportação operacional de contatos, dados comerciais ou backup do CRM. Não lê clientes, não cria arquivos, não aciona API, não oferece permissão de importação e não identifica organização. `main`, SQL, RLS real, IAM/ALLOW, produção, VPS, credenciais, gastos, LGPD e autorização de dados reais permanecem inalterados. Issues de revisão CORE/SEG/DBA/LGPD e Portal→CRM continuam **NO-GO**.
