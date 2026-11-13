# CRM F3-62 — independently signed synthetic technical receipt

This increment introduces a dormant CRM Core verifier for a **four-field receipt**: `issued_at`, `expires_at`, `nonce` and `mac`. The MAC is domain-separated and binds to all **six metadata identifiers from the locally expected frame**, which are **never echoed back in the HTTP response**. It expires in at most two minutes.

A signer exists **only under tests/Labs**, and the disposable PHP HTTP receiver uses it only after a committed synthetic technical receipt or its PG-backed readback. The client checks integrity with an independent receipt key, not the sender's request or lookup key.

Tests cover wrong keys, expiration, tampering, wrong destination, missing signature, unexpected fields, and lost HTTP response after commit. After a lost response, there is **no** signed receipt in the 503 reply; only a subsequent authorized readback returns one. Every response keeps flags for real CRM lead, tenant authentication, visitor acknowledgment, automatic retry and deployment **false**.

This is not an operational CRM receipt or proof of lead durability. The signer must never be deployed as a production handler; key rotation, genuine service identity, durable CRM lead storage, receipt replay policy, TLS, staging and independent AppSec/DBA reviews are still required. **NO-GO**.
