# UX F4-16 — Navegação offline entre os protótipos

A tela **Visão geral** de `prototypes/s01-prot-01/index.html` aponta, por link relativo local, para `prototypes/f4-15-followup-dashboard/index.html`; este apresenta um link de retorno para o protótipo principal.

Ambos são arquivos estáticos que podem ser abertos com `file://` no navegador, desde que toda a pasta `prototypes/` seja mantida com a mesma estrutura. Não há JavaScript de navegação em rede, integração Laravel, armazenamento, usuário real, acesso a empresas, lead real ou credencial.

## Testes de contrato

`OfflineFollowUpNavigationContractTest` valida links relativos e arquivos de destino e preserva as diretivas CSP de proibição de conexão e formulário. **É teste estático**: não substitui navegação no Chromium, teste de usabilidade, acessibilidade ou análise de segurança operacional.

## Gates preservados

Sem `ALLOW`, API, persistência ou migrações novas. Não altera `main`, VPS ou produção. SEG/DBA #27, CORE e privacidade permanecem abertos. Merge somente na branch DEV após F0 CI + PostgreSQL RLS F1 verdes na SHA da mudança.
