# CRM F3-61 — service binding resolved from PostgreSQL in HTTP integration

The F3-56 PHP loopback receiver now calls the F3-60 **real CRM Core** `TrustedServiceBindingCandidateResolver`. A principal ID supplied by the **CI process environment** selects the active, unexpired server binding in PostgreSQL under a restricted service role. No principal or tenant from an HTTP header/body is trusted as authorization.

The receiver uses that DB-sourced binding for both signed delivery and signed readback, and persists one nonce and **synthetic metadata-only** receipt atomically under PG16/RLS. The HTTP regression explicitly forges `X-Verified-Service-Principal` and a foreign organization; the server ignores the untrusted header and rejects tenant spoofing. Unknown or revoked principals fail closed.

CI fixture is provisioned only on a GitHub-hosted runner; no real mTLS, OAuth service credential or principal lifecycle is implemented, and the synthetic role remains fixed to one company. No contact/PII, CRM lead, public Laravel route, VPS credential, new migration, deploy, worker or customer ACK is created. A **staging identity service and reviewed multi-tenant principal/resource model** remain mandatory to continue toward functional homologation.

This verifies a real Core-to-PG-to-HTTP path, **not** operational approval. CRM #259, Portal #315, Comercial #56 remain NO-GO.
