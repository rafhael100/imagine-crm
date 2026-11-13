# CRM F3-62 — independently signed synthetic technical receipt

Introduces a **dormant CRM Core verifier** for an exact twelve-field, metadata-only receipt with an independent MAC domain and key, expiring in no more than two minutes. The signer exists **only under tests/Labs**, and the CI-only HTTP receiver uses it for a committed technical metadata fixture or a post-commit readback.

The Portal/client simulation verifies MAC integrity against the original six technical identifiers using a separate receipt key. Tests cover tampering, alternate destination, wrong key, expiration, missing signature, wrong state, and the distinction between an absent receipt after response loss and a later readback. A request signing key is **not** allowed to verify the receiver's receipt.

A matched MAC returns `SYNTHETIC_RECEIPT_INTEGRITY_ONLY`, with all claims about real leads, operational tenant identity, visitor acknowledgment, automatic retry or deploy **false**. This is not an operational CRM receipt or proof of real lead durability; signing source, secret handling, replay ledger, principal identity, TLS, staging and independent review still need validation. Do not include the laboratory signer in any deployed application or copy its methods into production.
