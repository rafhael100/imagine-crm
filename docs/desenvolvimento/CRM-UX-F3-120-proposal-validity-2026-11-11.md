# CRM UX F3-120 — Revisão offline de validade de versão de proposta

**Data:** 11/11/2026. **Ambiente:** DEV, HTML offline, PHPUnit e Chromium descartável. **Dependência:** F3-119 integrado à DEV.

## Entrega

A partir da página de revisão de versões de proposta (`prototypes/f3-111-proposal-versions/index.html`), há um link local para `prototypes/f3-120-proposal-validity/index.html`. Ela exibe cinco instantes fixos e inventados: segundo anterior à emissão, instante inicial, último segundo da vigência, expiração exata e instante posterior. Mostra fase, segundos até início/fim e horários UTC fixos.

A interface não recebe entrada do cliente, documento, link real ou credencial. Os cinco casos ficam em JSON literal do próprio protótipo e são conferidos novamente pela função JavaScript ao trocar de cenário. A fase `expired` começa **no exato instante do término**, não um segundo depois.

## Validação executável

- `OfflineProposalValidityParityTest`: lê os cinco exemplos JSON diretamente do HTML e verifica todos contra a classe PHP real `SyntheticProposalValidityPreview`, inclusive timestamps e proibições de aceite, renovação, assinatura, cobrança e persistência.
- `check-offline-proposal-validity-browser.php`: usa Chromium **real** em 1100 e 320 px; troca cinco cenários, confere fase e segundos, foco/seleção, negação visível e ausência de overflow horizontal.
- O workflow F0 executa o novo smoke, e a página anterior passa a oferecer navegação local; testes de links ajustados.

## Limites

**Uma versão dentro do prazo não é uma proposta que possa ser aceita.** Não há identidade, consentimento, política de validade homologada, aprovação, expiração real, revogação de token, envio ou assinatura. Todos os direitos estão negados. Nenhum endpoint, PDF, pagamento, API externa, SQL, tenant, conta, custo, VPS, `main` ou produção.

VEN-P05/CORE/SEG/DBA/LGPD permanecem NO-GO. A implementação operacional exigirá contrato jurídico e revisão independente, além de testes em staging autorizado.
