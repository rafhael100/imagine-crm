# CRM UX Experience v1 — navegação por tarefas e contexto ativo

**Data:** 12/11/2026. **Fonte:** [Experience System Imagine](https://github.com/rafhael100/imagine-portal/blob/design/imagine-node-original-visual-v1-2026-11-12/design-system/experience-system.md) e [CRM issue #416](https://github.com/rafhael100/imagine-crm/issues/416).

## Mudança concreta
O protótipo principal offline tem nove telas existentes e nenhum backend real. A navegação foi reorganizada em três grupos sem acrescentar botões inexistentes:
- **Início:** Boas-vindas, Minha organização, Visão geral.
- **Operação comercial:** Leads fictícios, Funil ilustrativo, Monitoramento de leads.
- **Revisão e suporte:** Prévia de importação, Estados da tela, Sobre o protótipo.

Os grupos são semanticamente rotulados (`role=group`, `aria-label`) sem criar navegação de terceira camada. Preserva a ordem das nove ações e `aria-current=page` de um único item. O breadcrumb acompanha o grupo e a tela efetiva a cada navegação. O foco continua indo ao título quando a pessoa ativa uma tela; setas/Home/End movem somente o foco como antes.

**Responsividade:** lateral branca 240px no desktop, menu horizontal com rolagem dentro da barra ao reduzir abaixo de 900px, cada botão com altura mínima de 44px em dispositivos móveis. Não se criam entradas falsas para automações, relatórios ou configurações ainda indisponíveis no protótipo. Sem acesso, armazenamento, API ou telemetria.

## Testes e aceite
- PHPUnit verifica os três grupos e suas nove ações, rótulos, breadcrumb e política de não armazenamento.
- Chromium real confere grupos, breadcrumb após click, foco e altura dos botões em tela normal e viewport 320px.
- Evidências de screenshot comparativa DEV histórica versus proposta serão disponibilizadas no artefato do fluxo de QA visual; a existência do artefato não substitui a revisão humana do layout.
- Não fazer merge/deploy automático: PR filha da #418 (design), dependente do aceite visual da #416. Gates #27 e #259 permanecem NO-GO.
