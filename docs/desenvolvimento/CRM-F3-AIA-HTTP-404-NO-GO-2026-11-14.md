# CRM — F3-AIA: prova HTTP negativa de nenhuma rota OP-01

**14/11/2026 — laboratório CI, NO-GO.**

O código CRM contém `AiaReadOnlyMockBoundary` e verifica os contratos fictícios Agents, mas **não possui endpoint CRM operacional**. Um futuro registro acidental de rota para os agentes poderia permitir que um contrato interpretado como válido fosse confundido com autorização real.

A classe `tests/Feature/AgentsOp01NoHttpSurfaceTest.php` verifica o inventário de rotas **efetivamente registrado pelo Laravel**, a ausência de `api:` no bootstrap e de `routes/api.php`, e envia **40 requisições HTTP pelo kernel de testes**, cobrindo 8 caminhos com 5 métodos, com cabeçalhos adversariais, identidade fictícia, operação `crm.lead.read_minimal` e flags de simulação. Todas devem retornar 404 sem expor marcadores sintéticos em corpo. A prova roda pelas suítes F0 e PostgreSQL efêmero do GitHub e falha se uma rota OP-01 aparecer sem revisão.

A proteção é **negativa**, não implanta nem configura um endpoint verdadeiro, não testa identidade de serviço real e não é substituto de autenticação, ACL/RLS, rate limit, observabilidade e homologação de staging.

**CRM #57 continua OPEN/DRAFT, 14 gates e 13 cenários reais permanecem NAO_EXECUTADO/NO-GO.** Sem `main`, VPS, dados reais, mudanças de rotas, credenciais ou custos.
