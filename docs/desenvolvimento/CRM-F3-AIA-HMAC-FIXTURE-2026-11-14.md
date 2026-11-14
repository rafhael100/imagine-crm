# F3-AIA — laboratório isolado de prova HMAC e replay para integração CRM/Agents

**14/11/2026 — NO-GO para identidade de serviço real.** Este incremento testa uma forma de vincular **bytes JSON canônicos**, destino, tenant sintético, validade temporal e nonce a uma assinatura HMAC-SHA256, sem resolver identidade/autoridade real.

`AiaHmacFixturePreflight::matchesFixture` aceita apenas a chave de **prefixo público de teste** `SYNTHETIC_PUBLIC_TEST_ONLY_` e as referências `SYNTHETIC_...`. Não possui rota, header reader, configuração de segredo, signing key real, API, cache, DB, integração de serviço, identidade verificável ou permissões. Seu resultado `true` informa exclusivamente a conformidade de **uma prova de laboratório conhecida e forjável**. Nenhum chamador ganha autorização CRM por isso. `inspectFixture` retorna sempre a mesma resposta negativa, mesmo após HMAC correto.

O `AiaFixtureNonceLedger` mantém nonces consumidos apenas **na memória de um objeto PHP** e, portanto, não oferece atomicidade ou proteção a replay entre workers/instâncias. Capacidade esgotada, indisponibilidade e repetição negam; não há eviction que restaure nonce. A janela máxima sintética é 30 segundos e rejeita `now >= expires_at`. A mensagem assinada inclui tag de domínio, emissor, destinatário, tenant, nonce, tempos e SHA256 do corpo exato. Usa `hash_equals` para digests/assinaturas.

A CI executa testes PHPUnit com adulteração de issuer/audience/tenant/nonce/timestamp/body/signature, chave divergente, replay e indisponibilidade; também executa prova PHP isolada em 8.3 e 8.4. Os valores de chave visíveis são **públicos e absolutamente não devem ser utilizados fora dos testes**.

**Para criar IAM de serviço verdadeiro**, SEG/CORE/AUT/INT e LGPD ainda devem revisar threat model, cofre/rotação, autenticação de origem, chave por empresa/serviço, autorização independente, revogação, relógio, nonce store durável/atômico, auditoria e staging isolado. [CRM #57](https://github.com/rafhael100/imagine-crm/pull/57) permanece OPEN/DRAFT; 14 gates, 7 decisões, 13 testes reais NAO_EXECUTADO. Sem main/VPS, clientes, endpoints, migrations ou custos.
