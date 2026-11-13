# CRM UI DS-V1 — segunda onda de migração visual offline

Data: 12/11/2026. Referência: [issue CRM #416](https://github.com/rafhael100/imagine-crm/issues/416), [Portal PR #589](https://github.com/rafhael100/imagine-portal/pull/589), `imagine-portal/portal.css` da versão original Node.

## Independência da primeira onda
Este recorte é **uma PR independente, filha direta da DEV**, sem depender do merge da PR CRM #418, que prepara tokens/documentação, o menu principal, o hub e o Kanban. Ambas permanecem DRAFT conforme a issue. Não substitui o código Node nem transfere logins ou backends.

## Dez telas atualizadas
1. Prévia CSV (`f3-22`).
2. Trial, cancelamento e retenção (`f3-104`).
3. Subtotais de itens de propostas (`f3-109`).
4. Tendência de scoring (`f3-113`).
5. Previsão ponderada (`f3-115`).
6. Ganhos, perdas e motivos (`f3-117`).
7. Jornada comercial (`f3-138`).
8. Painel de funil com privacidade (`f3-148`).
9. Carga comercial e tarefas (`f3-155`).
10. Triagem de leads fictícios (`f3-164`).

Aliases locais `--imagine-*` repetem estritamente a referência original (laranja #F9941D, grafite #171B19, branco, superfície #F7F7F8, laranja acessível e foco). A cópia CSS inline evita dependências de rede incompatíveis com o CSP offline. Cabeçalhos, cartões, controles, estados selecionados e foco usam a família visual; avisos/erros/sucessos conservam significados independentes da cor de marca.

## Verificação
`ImagineExtendedVisualIdentityV1Test` exige tokens, única instância do bloco visual, CSP sem conexões nem envio e preservação de estados de rádio. `check-imagine-extended-brand-browser.php` testa as dez páginas com Chromium real em 1100 e 320 CSS px, validando background, cabeçalho, tipografia e ausência de overflow em conjunto com todos os testes de interação existentes do F0 CI.

## Sem autorização operacional
A migração é de apresentação visual de fixtures sintéticas locais: não altera Laravel runtime, IAM, rotas, tenant, SQL, leads, LGPD, custo, VPS, main ou produção. A issue #416 exige revisão visual e capturas comparativas para aceite; não fazer merge automático. Issues #27 (SEG/DBA) e #259 (CORE/SEG/DBA/LGPD) permanecem NO-GO.
