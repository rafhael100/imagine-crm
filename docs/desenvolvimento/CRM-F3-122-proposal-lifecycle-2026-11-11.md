# CRM F3-122 — Redutor de ciclo de proposta por versão e validade

**Data:** 11/11/2026 · **Branch:** DEV, sem endpoints · **Requisitos:** VEN-R11/R13/R14/R15/R16/R17/R18, VEN-T06..T11 e VEN-P05/P06 (pendentes de homologação)

## Implementação utilizável no Core

`SyntheticProposalLifecycleInspector` processa até **24 eventos artificiais em UTC canônico**, em ordem estritamente crescente e com no máximo oito versões. Cada evento contém exclusivamente `version`, `action`, `occurred_at_utc` e `expires_at_utc` (somente `send` pode receber prazo).

As ações de laboratório são `create`, `send`, `view`, `accept`, `decline`, `expire` e `revise`. Uma sequência começa com `create` da versão 1. Uma versão `draft` pode ser `send` somente uma vez; um envio valida a janela e o calendário usando o módulo PHP `SyntheticProposalValidityPreview` já integrado. `view` não muda o estado comercial para `accepted`; `accept`/`decline` precisam de versão enviada ainda dentro do prazo. `expire` é válido somente **no instante de expiração ou depois**. Estados terminais não podem receber outro aceite ou recusa na mesma versão. `revise` gera a versão seguinte em `draft` e **nunca herda aceite**.

O relatório de saída é produzido em conjunto com `SyntheticProposalVersionReview`, a implementação já integrada das contagens de status, para evitar divergências entre componentes. Expõe apenas versões e rótulos de status de laboratório, não datas, links, IDs, partes, valores, texto ou dados pessoais. Rejeições não refletem a entrada.

## Cobertura de regressão

`SyntheticProposalLifecycleInspectorTest` exercita aceite declarado em v1 e recusa em v2, visualização sem aceite, expiração no segundo exato, revisão de versão expirada, oito versões, limite de 24 eventos, validade e comparação UTC, duplicação, eventos fora de ordem, versão antiga ou futura, transição terminal indevida, PII e chaves extras. Nenhuma operação comercial real é permitida.

## Limites e exclusões explícitos

**Este é um redutor de alegações inventadas, não uma máquina de estados autorizada de produção.** `accepted` significa apenas que o evento fictício afirma um aceite, sem comprovar usuário, intenção, prazo, documento exato, assinatura, aprovação de desconto, versão de política ou prova auditável.

Todas as flags `can_*`, `authorized` e `*_verified` permanecem falsas. Não existe fonte de relógio confiável, scheduler, revogação de link, autenticação de destinatário, webhook, disparo de e-mail, geração de PDF, gravação, banco de dados, contrato ou cobrança. Nenhuma rota pública ou acesso por tenant foi alterado.

Não integrar a produção antes da revisão independente VEN-P05/P06, CORE/SEG/DBA/LGPD; `main`, VPS e custos permanecem intactos.
