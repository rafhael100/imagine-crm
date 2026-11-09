# UX-F3-15 — Ordenação local de leads fictícios

**Data:** 09/11/2026. **Natureza:** protótipo HTML offline e sintético, sem controle de conta, banco, API, fornecedor, custo ou produção.

## Incremento

A tela **Leads fictícios** apresenta três ordenações reversíveis: ordem original, maior e menor pontuação. Os quatro registros e respectivas pontuações são números inventados e fixos no HTML. O botão ativo usa `aria-pressed`, a mudança de ordem é descrita em um `role=status` com `aria-live=polite` e os filtros por etapa continuam independentes.

O código só reordena nós HTML pré-existentes dentro do `tbody`, sem acrescentar ou modificar leads. A comparação por pontuação tem desempate estável pelo índice fixo. Não há armazenamento, entrada de usuário, eventos persistentes, scoring de clientes, exportação ou permissões.

## Testes e critérios

- `OfflinePrototypeLeadSortTest`: exatamente 4 fixtures com pontuações/índices esperados, 3 controles acessíveis, ausência de dados de clientes e fluxos externos.
- `check-offline-prototype-browser.php`: Chromium real verifica a ordem inicial, maior/menor pontuação, restauração e compatibilidade com filtro.
- `check-offline-prototype-320.php`: mesmas verificações essenciais na viewport real de 320 CSS px e sem overflow.
- Regressões anteriores de navegação por teclado, filtros, CSP offline, banco/RLS e PHP não devem ser removidas.

## Limites

Não representa lead scoring automático, ação sobre clientes, cálculo autorizado, direito por plano ou trabalho concluído da UI produtiva. Não abre rotas, arquivos, migrations, `ALLOW`, jobs, credenciais ou integração com CRM/API. Inspeção humana/WCAG e gates SEG/AUT/CORE/FIN/CON ainda são exigidos para produto real.

**Merge permitido apenas na branch DEV se diff revisado e duas CIs verdes na HEAD exata.**
