# F3-55 — signed readback after lost ACK, synthetic PG16 only

This builds on the F3-53/54 **disposable test database**, with separate PostgreSQL roles and RLS. A technical receipt is first written in the same transaction as a nonce. The sender's response is intentionally discarded, after which the read-only lookup checks the existing technical ledger rather than attempting a second delivery.

The query is protected by a dedicated, domain-separated HMAC, TTL and server-pinned five-field binding. A different lookup key is used from the simulated delivery key. The result never reports a real CRM lead, tenant authentication, visitor acknowledgement, automatic retry authorization or deployment approval.

Checks reject invalid signatures, replay of altered parameters, expired authentication, extra fields and forged binding. Cross-tenant readback remains invisible under RLS. Missing or unavailable readback **does not** authorize retransmission.

**Limits:** no actual Portal HTTP receiver or production CRM lead, signed response/receipt verification, external secret rotation, real authenticated tenancy, deployment, or private infrastructure validation. Reviewer still needs to assess nonces for readback, signed receipts, DB permissions and audit trails. F3-51 operational gate remains NO-GO.
