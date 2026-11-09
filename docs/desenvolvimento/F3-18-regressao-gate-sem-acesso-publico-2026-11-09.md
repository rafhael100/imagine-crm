# CRM Imagine — F3-18 — Gate contínuo de não liberação de leads

**09/11/2026 — DEV ONLY, código de testes sintéticos, sem ativação funcional.**

Motivação: o ecossistema já contém laboratórios de recebimento, idempotência, HMAC, conciliação, retenção e demonstração visual. Esses artefatos **não são serviços de clientes** e não podem ser interpretados como política `ALLOW` ou acesso público. A [Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27) e o [PR #49 do ledger](https://github.com/rafhael100/imagine-crm/pull/49) continuam pendentes de revisão independente.

O teste `LeadIntakeReleaseGateTest` roda na suíte Laravel e na suíte PostgreSQL, verificando:

1. Que a aplicação não publique verbos mutadores ou rotas públicas `api`, `leads`, `contacts`, `imports`, `exports`, `login` ou `register` antes de revisão e autorização específicas.
2. Que um `TenantContext` construído com identificadores não concede operações comerciais no `DenyByDefaultAuthorizer`.
3. Que `AuthorizationDecision` não exponha criação pública ou fábrica `allow`.
4. Que as classes de laboratório **permaneçam negando** recebimento, confirmação, entrega, retry, replay seguro, exclusão e importação — mesmo quando dados sintéticos pareçam válidos.

**Esse teste congela o estado de não liberação e deliberadamente deverá ser revisado antes da futura habilitação de endpoints operacionais.** Não concede legitimidade jurídica, segurança de KMS, controle distribuído entre duas APIs ou homologação LGPD. Não substitui testes reais de autenticação, memberships, RLS, ACLs, race conditions ou parecer externo.

**Achado real durante a primeira CI:** o scaffold Laravel tinha `'serve' => true` no disco local privado, habilitando a rota interna `PUT storage/{path}` (URL temporária/assinada). O teste inicial falhou corretamente ao detectar a rota de escrita. Como não há fluxos homologados de arquivos/anexos, F3-18 **desabilita `serve` para o disco local** e testa que continua `false`. Trata-se de hardening mínimo na DEV, não de prova de que os demais discos ou URLs estejam homologados. Reativar upload/links temporários exige desenho de ACL, assinatura, TTL, escopo por tenant e revisão SEG/DBA.

**Escopo técnico:** configuração mínima do disco local, adição de um teste e deste documento. Nenhum endpoint, route, schema, grant, `ALLOW`, SQL, migration, credencial, webhook, fila, dado de cliente, mudança `main`, VPS, deploy ou despesa. Todos os 67 CORE, SEG/DBA, AUT/INT/LGPD e DEV-GATE permanecem abertos.
