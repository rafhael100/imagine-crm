# CRM UX F3-81 — Prévia local de conflitos técnicos Portal→CRM

**Data:** 11/11/2026. **Escopo:** interface HTML/JS offline, quatro exemplos inventados; nenhuma submissão real.

## Entrega visual

Nova página `prototypes/f3-81-submission-review/index.html`, acessível pelo link local na prévia `prototypes/f3-72-csv-checklist/index.html`, sem modificar `prototypes/index.html` (que permanece sob revisão da UX Portal↔CRM #255). Exibe quatro casos de **relações entre referências sintéticas**: submissões diferentes, mesma submissão com vínculo igual, mesma submissão com vínculo declarado divergente e mesma submissão declarada sob organizações divergentes.

A página exibe somente **número de referências**, contagem de cada tipo de alerta e **posições numéricas** dos pares, sem mostrar UUIDs, nome de organização, dados de formulário, e-mail, telefone, payload, hash ou pessoa. As quatro amostras são relatórios estáticos do tipo `{claims,same,binding,organization,allowed:false}`, mas **cada número é verificado automaticamente contra o Core PHP real F3-80** por `OfflineTechnicalSubmissionReviewTest`. Assim, a UI não simula um resultado que contradiga a validação de laboratório.

O documento HTML é local, em português, responsivo, com botões nativos e `aria-pressed`, aviso de dados fictícios, mensagem `aria-live`, skip link e tabela de avisos com texto seguro por `textContent`. A CSP define `connect-src 'none'`, sem formulário, upload, recursos remotos, armazenamento local, cookies, APIs ou mensagens. Sem alertas, mantém sempre visível **“Entrega não autorizada. Nenhuma organização foi autenticada.”**

## QA

`OfflineTechnicalSubmissionReviewTest` executa os quatro cenários **em PHP real** com `SyntheticSubmissionBindingBatchInspector` F3-80 e exige coincidência exata das contagens/posições com os objetos JSON fixos da tela, além de verificação de ausência de PII, rede e links externos.

`scripts/ci/check-offline-submission-review-browser.php` roda **Chromium real** nas larguras de 1100 e 320 CSS px, seleciona todos os exemplos, confere contadores, linhas e mensagens, estado único `aria-pressed`, foco de teclado, restauração do cenário inicial e ausência de overflow horizontal. O smoke foi incluído no workflow F0, sem dependências novas. Teste de navegação da checklist CSV foi atualizado para aprovar somente o novo destino relativo que existe fisicamente.

## Limites

**Não é um visor de submissões reais.** O link apenas navega entre páginas locais de laboratório. Não consulta logs, não autentica Portal, não aceita leads, não armazena eventos, não define política de retry, não confirma persistência, não executa importação nem concede identidade/consentimento/tenant. Nenhuma API, rota, SQL, IAM/ALLOW, migração, dado de cliente, serviço, custo, VPS, `main` ou produção foi alterada. Issues de parecer independente SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27) e CORE/SEG/DBA/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259) permanecem NO-GO; integração UX #255 continua DRAFT.

Merge permitido **somente DEV**, após quatro workflows F0, PostgreSQL/RLS, Sites e Agents aprovados na HEAD exata, diff revisado e `behind_by=0`.
