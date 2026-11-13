# CRM F3-60 — Candidate trusted service binding backed by PostgreSQL

This increment adds a **dormant Core candidate** resolver for an externally authenticated service identity. Unlike synthetic hard-coded bindings, it reads a server-owned row in PostgreSQL with prepared statements, verifies active/expiry state and returns a five-field technical binding for the real F3-57 HMAC inspector. It **never** obtains a principal ID or tenant permission from an HTTP envelope.

A synthetic PostgreSQL 16 fixture models separate non-superuser service roles with FORCE RLS per organization, active, revoked and expired bindings. It tests forged organization, cross-tenant invisibility, forbidden writes and the strict Core signature boundary. No actual client identity is authenticated in the fixture: the principal IDs are **supplied by the test runner**, not derived from mTLS or credentials.

Important limitations: the proposed single-organization-per-principal schema is **not a final multi-tenant design**. The eventual Portal may serve multiple independent companies; approval of a scalable principal/tenant/resource mapping, principal revocation, rotation, scoped roles, online backfill, LGPD and independent AppSec/DBA reviews is required before an operational migration. No Laravel route, table migration, production credentials, real lead or visitor receipt is created. **NO-GO**.
