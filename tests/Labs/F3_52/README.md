# CRM F3-52 — Signed handoff preparation, fail closed

A standalone PHP verification boundary for metadata-only Comercial → Portal → CRM requests. It is deliberately under `tests/Labs/` and is **not** registered as a Laravel route, controller, job, migration or production service.

The frame must have exactly eight keys and match a five-field binding independently supplied by the server. An HMAC-SHA256 signature is checked against a fixed, domain-separated serialization, strict timestamps and nonce syntax, with a distinct server-provisioned secret of at least 32 bytes. No secret or personal field is written or logged.

A valid signature produces `SIGNED_FRAME_REQUIRES_ATOMIC_NONCE_CLAIM` and **all** production, persistence, ACK and nonce-claim flags remain `false`. A transport MAC does not by itself authenticate a tenant, guarantee replay protection, or establish durable delivery. The next operational increment must add an atomically committed nonce store, scoped server-side identity, least-privilege PG role, a durable record and signed CRM readback, followed by isolated infrastructure validation.

Run `php -l tests/Labs/F3_52/AuthenticatedHandoffBoundary.php && php tests/Labs/F3_52/test_authenticated_handoff_boundary.php`. No network, database, credentials or real data involved. Keep F3-51 NO-GO and the independent review gates.
