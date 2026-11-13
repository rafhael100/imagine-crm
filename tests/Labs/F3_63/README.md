# CRM F3-63 — dynamic binding revocation across HTTP requests

After the existing F3-56/F3-61 loopback receiver starts and passes its signed readback tests, the CI harness uses the synthetic DBA (not the restricted service role) to **revoke** the active service binding in PostgreSQL. Without restarting the PHP server or changing any HMAC key, the same authorized-looking signed **delivery** and **readback** requests must return HTTP 503 with `SERVER_BINDING_NOT_VERIFIED`, without a receipt or any state change.

The CI harness then switches the fixture to a deliberately **expired** binding and repeats the negative checks, again without restarting. The service-side Core resolver queries the server-owned PG row for every request. No untrusted header or client payload can restore the binding.

This tests immediate revocation/expiry only on disposable PostgreSQL 16 with fake service principals and fixed identities. It does not implement live credential revocation, KMS, mTLS identity, rotation propagation, multi-tenant principal authorization, lead storage or deployment. AppSec/DBA must decide revocation SLA, caching behavior and source of truth before operational homologation. **NO-GO**.
