# CRM UX F3-63 — Integração sem duplicação com a DEV atual

**Objetivo:** transportar a única jornada de UX ainda ausente na DEV — a conferência ilustrativa de resultados de entrega Portal–CRM — sem fazer merge da pilha antiga de testes/laboratórios de IAM, SQL ou novos protótipos duplicados.

## Evidência de concorrência e decisão

A DEV `dev/fast-track-crm-2026-11-04` avançou de `9e35d222` para `58e08ba4` com os incrementos **F3-59 Kanban fictício**, **F3-60 hub offline único** e **F3-61 criação/undo fictícios no Kanban**; chegou em seguida a `0006cf25` com **F3-62 valores por etapa**. Esse conjunto já fornece uma experiência interativa **mais completa** do que a versão anterior F3-55 e uma página inicial existente (`prototypes/index.html`), portanto seria erro reaplicar os protótipos em arquivos paralelos ou sobrescrever o hub.

A PR draft #246 (`dev/f3-54-reconciliation-ui-focused-dev-2026-11-11`) tinha base antiga e adicionava novamente `prototypes/index.html`, outro simulador de funil e documentação de caminhos duplicados. Isso causou divergência e possível conflito. Esta branch F3-63, **derivada diretamente da DEV mais recente**, seleciona apenas o incremento não duplicado e preserva as funcionalidades F3-59..62 existentes. A PR antiga deve permanecer como histórico até esta substituição estar validada; não mesclar as duas.

## Resultado de produto

- `prototypes/f3-52-delivery-reconciliation/index.html`: painel de seis cenários de transporte inteiramente sintéticos, filtros, ordenação, detalhes e linha do tempo; distinção entre pendência, resposta perdida, conflito de tenant, contingência e recibo técnico só de CI
- `prototypes/index.html`: expande o **hub existente de três para seis cards**, sem substituí-lo, adicionando conferência Portal–CRM, painel de acompanhamentos F4-15 e lista fictícia F4-28; preserva Kanban F3-59 e CSV F3-22 preexistentes
- `prototypes/s01-prot-01/index.html`: adiciona somente links locais às telas Leads fictícios e Prévia de importação; mantém as nove telas, sem formulários, upload, novos dados ou rede
- Contratos PHP de DOM, contagem, links, CSP e avisos; as whitelists de navegação são atualizadas explicitamente, não flexibilizadas
- Workflow `ci-f3-63-reconciliation-current-dev.yml` executa PHPUnit e **Chromium real no desktop e em 320 CSS px**, inclusive o hub preexistente. Os workflows F0 e PostgreSQL/RLS permanecem separados para verificação da SHA final.

**Limites:** o painel representa apenas estados de demonstração, não uma infraestrutura de recepção ou worker. Nenhuma autenticação real, IAM/ALLOW, autorização de empresa, PII, fila produtiva, retrigger de envio, recibo verdadeiro, armazenamento LGPD, custo, deploy, VPS, DNS ou `main` é alterado.

**Gates:** F70 Imagine Comercial permanece bloqueado. CORE/SEG/DBA/LGPD e parecer independente ainda requerem evidências próprias; aprovação de CI não equivale à liberação para captar leads reais.

**Revisão:** PR DRAFT direcionada à DEV; sem merge automático. Verificar novamente head/base, conflitos e todos os checks na SHA exata antes de qualquer integração.
