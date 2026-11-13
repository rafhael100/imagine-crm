# CRM F3-57 — signed technical frame moved into CRM Core (dormant)

Core class: `app/Core/Leads/SignedServiceHandoffBoundary.php`. It has no Laravel route, controller, worker, DB access or environment credentials and is not invoked by an operational intake.

It validates only a strict eight-field technical envelope against a **separately provided, trusted server-side binding**, time window, domain-separated HMAC-SHA256 and nonce syntax. Crucially, **a valid MAC yields `SIGNED_TECHNICAL_FRAME_ONLY`**, never an authenticated service principal/tenant, a nonce claim, a real lead, a visitor ACK or deployment permission.

F3-56's disposable HTTP receiver exercises this actual Core class, not just its earlier F3-52 laboratory predecessor. The full signed transport and transactional receipt still occur only in GitHub-hosted CI.

Additional requirements for private Leaseweb homologation: server-provisioned principal, independently queried authorized binding, DBA-reviewed schema/roles, transactional real-lead persistence, audited receipt verification, nonpublic TLS/mTLS ingress, privacy and recovery tests, plus independent review. Current GO gate remains **NO-GO**.
