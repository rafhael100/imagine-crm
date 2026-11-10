# F4-15 — Prévia offline dos indicadores de follow-up

Abra `index.html` diretamente no navegador; funciona com `file://`, sem instalação, sem ambiente remoto ou conexão externa. O usuário pode alternar três períodos fictícios e consultar contagens e diferenças assinadas.

## Avisos de escopo
- É **um protótipo independente** do painel existente em `prototypes/s01-prot-01/`, sem backend, autenticação ou integração com o PHP F4-06..F4-14.
- O JavaScript contém três conjuntos de dados inventados, fixos e imutáveis. Não importa CSV, não grava mudanças, não usa rede, cookies ou armazenamento.
- A UI fornece navegação por teclado, foco visível, aviso persistente de ficção, tabela e resumo com `aria-live`.
- O teste `OfflineFollowUpDashboardContractTest` é **análise estática de contrato**. Não representa teste visual no Chromium, integração funcional, homologação de acessibilidade ou segurança de produção.
- Uma integração futura exigirá fonte confiável por tenant, política de acesso e regras de minimização, além das revisões SEG/DBA, CORE e LGPD. `main`, VPS e produção não devem sofrer alterações nesta fase.

## UX F4-19 — Distribuição visual acessível e paridade dos números

O painel ganhou três indicadores nativos `<meter>` (vencidos, até 24 h e após 24 h) com legendas explícitas `N de total`. A proporção atualiza somente no navegador quando se escolhe A, B ou C. As barras são **fictícias**, não medem SLA nem tarefas concluídas.

`tests/Unit/OfflineFollowUpDashboardParityTest.php` confronta os números fixos do JavaScript, as linhas da tabela e contagens independentes produzidas por `FollowUpSnapshot` com identificadores e datas inteiramente sintéticos. Também verifica os valores iniciais das barras e a ausência de acessos de rede ou inserção de HTML dinâmico. Esses testes previnem divergências na demonstração; **não são integração PHP/JavaScript, homologação de navegador, acessibilidade, isolamento de tenants ou relatório real**.

## UX F4-22 — QA funcional no Chromium em CI

O CI F0 agora executa `php scripts/ci/check-followup-dashboard-browser.php`, além dos testes existentes do protótipo principal. O script usa o `BrowserSmokeRunner` já adotado pelo projeto e **falha se Chromium não estiver disponível**, se o navegador não executar as asserções ou se a interface apresentar resultado incorreto. Não basta avaliar strings do HTML.

O navegador testa a interação real dos três períodos, o estado `aria-pressed`, totais, variações assinadas, valores/legendas das barras nativas, conservação de foco e a restauração do estado inicial. A segunda execução usa um iframe `srcdoc` com viewport **real de 320 CSS px**, validando seleções, texto acessível e ausência de transbordamento horizontal. Perfis e arquivos de teste são temporários e descartados.

**Limite do resultado:** prova de regressão da UI totalmente fictícia e offline, não é auditoria independente de acessibilidade, segurança da aplicação, autorização por tenant, tráfego externo, homologaçao comercial ou integração Laravel. As fontes continuam números estáticos inventados. Nenhuma execução de negócio, credencial, migração, mudança da `main`, VPS ou produção.
