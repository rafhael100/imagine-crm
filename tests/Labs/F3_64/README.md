# CRM F3-64 — shared Portal service principal, tenant-scoped site authorization

The existing F3-60 candidate assumes a separate service identity for every organization. The Imagine Portal will serve multiple customer companies, so this separate Core candidate models **one authenticated service principal authorized for multiple distinct sites**, each resolved from server-owned PostgreSQL state.

The (untrusted) site reference selects a row only within the previously authenticated principal's scope, using a parameterized query and a unique `(principal_id, site_reference)` key. The **server's** row determines organization, binding, form and destination; the signed envelope must match all of them via the existing Core HMAC boundary. A client-supplied organization is never used to select a tenant.

GitHub Actions PG16 exercises two companies under one principal, another isolated service principal, missing/revoked/expired sites, RLS isolation and forbidden writes. This is a **candidate pattern**, not the finished identity or schema: production roles, multi-site permissions, principal revocation, key rotation, consent, audit and security review remain unimplemented. No route, new operational migration, customer lead, PII, VPS or deployment. **NO-GO**.
