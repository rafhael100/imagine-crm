# CRM F3-56 — HTTP sender/receiver with PostgreSQL and signed readback

Uses an actual PHP built-in HTTP server on loopback (127.0.0.1:19156) and a distinct client process in GitHub Actions, plus a disposable PostgreSQL 16 service running nonprivileged test roles/RLS. The server imports the **real F3-52 signature boundary** and the **F3-55 readback boundary**, and transactionally persists the F3-53/F3-54 synthetic nonce and technical receipt.

Negative flows: reply lost **after COMMIT**, signed readback without resending, nonce replay, duplicated logical submission, wrong signature domain, missing row, forged organization, contact field injection, and non-POST method. The technical receipt explicitly states **NO_REAL_LEAD**. Every HTTP response denies tenant production authentication, visitor acknowledgment, automatic retry and deploy.

CI-only protections: require GitHub-hosted runner variables, fixed database, loopback address, random ephemeral independent HMAC keys and fixed synthetic server binding; the router is under tests/Labs and is **not** registered in Laravel. No real contact details, data migrations, deployment, VPS, DNS, or vendor credentials.

This closes a **transport simulation** gap only. The real production-sized prerequisites remain: service principal and independently verified tenant binding, operational lead schema, durable audit, live failure/restart recovery, signed receipt verification, private staging network, security/DBA review and final operator approval. **NO-GO**.
