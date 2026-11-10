# F4-15 — Prévia offline dos indicadores de follow-up

Abra `index.html` diretamente no navegador; funciona com `file://`, sem instalação, sem ambiente remoto ou conexão externa. O usuário pode alternar três períodos fictícios e consultar contagens e diferenças assinadas.

## Avisos de escopo
- É **um protótipo independente** do painel existente em `prototypes/s01-prot-01/`, sem backend, autenticação ou integração com o PHP F4-06..F4-14.
- O JavaScript contém três conjuntos de dados inventados, fixos e imutáveis. Não importa CSV, não grava mudanças, não usa rede, cookies ou armazenamento.
- A UI fornece navegação por teclado, foco visível, aviso persistente de ficção, tabela e resumo com `aria-live`.
- O teste `OfflineFollowUpDashboardContractTest` é **análise estática de contrato**. Não representa teste visual no Chromium, integração funcional, homologação de acessibilidade ou segurança de produção.
- Uma integração futura exigirá fonte confiável por tenant, política de acesso e regras de minimização, além das revisões SEG/DBA, CORE e LGPD. `main`, VPS e produção não devem sofrer alterações nesta fase.
