# F3-AIA — integração de laboratório: prova HMAC seguida de nonce SQL compartilhado

**14/11/2026, exclusivamente DEV/CI. Decisão operacional NO-GO.**

Depois de comprovar a concorrência de PostgreSQL na [PR #592](https://github.com/rafhael100/imagine-crm/pull/592) e extrair o adaptador com bloqueio de produção na [PR #593](https://github.com/rafhael100/imagine-crm/pull/593), esta entrega verifica **os dois controles em uma única execução**.

A classe `AiaPgHmacLabBridge` primeiro submete o JSON canônico e a prova HMAC pública de teste à classe original `AiaHmacFixturePreflight` (issuer, audience, tenant, nonce, janela temporal, hash e assinatura). **Somente depois do sucesso dessa verificação** tenta reivindicar o nonce pelo `AiaPgNonceCandidateLedger`, que recusa ambientes distintos do banco efêmero. Falha em qualquer estágio retorna `false`; a fachada `inspectLabFixture` devolve sempre a negativa pública uniforme, não importa a assinatura ou a gravação.

Os testes no PostgreSQL 16 descartável cobrem: um mesmo nonce assinado submetido a duas conexões reais independentes (primeira aceita, segunda negada), resposta pública sempre `DENIED_OR_NOT_FOUND`, **7 ataques** de corpo/assinatura/tempo/tenant/audience e falha segura após queda da tabela. O adaptador não é ligado ao service container, middleware, rotas ou produto; nenhum cliente participa.

**Isso ainda NÃO implementa IAM real.** A chave de laboratório é publicamente conhecida, a prova de assinatura não comprova identidade, a tabela existe somente durante o teste, não há controle de revogação, rotação, rate limiting, key management, retention, autorização por empresa/recurso/campo ou API. [CRM #57](https://github.com/rafhael100/imagine-crm/pull/57) continua OPEN/DRAFT, 14 gates, 7 decisões e 13 cenários reais NÃO_EXECUTADOS. Não alteramos `main`, VPS ou produção. **NO-GO**.
