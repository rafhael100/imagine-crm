# CRM F3-52 — painel offline de conferência de entrega Portal–CRM

**Objetivo de produto:** permitir revisar uma interface de reconciliação e recuperação de entregas sem acessar ambientes reais nem confundir metadados de laboratório com leads. Este recurso é uma **prévia navegável separada**, em `prototypes/f3-52-delivery-reconciliation/index.html`, aberta diretamente via `file://`. Não é uma tela conectada ao Laravel.

O painel contém seis casos artificiais: pendente, lease em processamento, resultado desconhecido, divergência de vínculo/empresa, recibo de banco descartável e contingência não confirmada. Traz indicadores calculados **a partir das seis linhas estáticas**, filtro Todos/Revisão/Aguardando/CI, ordenação estável de revisões prioritárias, seleção de linha que exibe sequência ilustrativa e mensagens claras de não confirmar, não reenviar automaticamente, não excluir e não permitir alteração de tenant.

O código tem uma navegação local de volta ao protótipo principal. Não introduz form, input, dados pessoais, autenticação, APIs, armazenamento, cookies, exportação ou operações de negócio. O CSP explícito bloqueia `connect-src`, `form-action`, imagens, frames e outros canais. DOM gerado exclusivamente via `textContent`. O resultado `Somente CI` não é equiparado a entrega real, mesmo quando os testes físicos de laboratório verificam commit de metadados fictícios.

**Testes**: PHPUnit de estrutura, contagem, mensagens, acessibilidade básica e ausência de redes/PII; script de **Chrome/Chromium headless real** em desktop e iframe a **320 CSS px**, com filtros, ordenação, abertura/fechamento de detalhes, foco e overflow. Workflow `ci-f3-52-reconciliation-browser.yml`, Laravel/PHP 8.4 conforme `composer.lock` atual, inclui o selo de rotas públicas. Os testes do navegador falham quando Chrome não está disponível, sem simular passe.

**Gates abertos**: sem identidade de serviço, KMS, permissões reais, consentimento, persistência durável de dados de contato, anti-replay operacional, audit trail, worker, fila e parecer SEG/DBA/LGPD. A F70 do Imagine Comercial continua bloqueada. PR em rascunho filha de F3-51; `main`, VPS, DNS, produção e custos inalterados.
