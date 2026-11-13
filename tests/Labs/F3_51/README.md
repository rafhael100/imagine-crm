# CRM F3-51 — evidence-completeness gate (DRAFT / NO-GO)

This is an offline, dependency-free checker for a later **private Imagine infrastructure** homologation of Comercial → Portal → CRM. It is **not** evidence of infrastructure deployment, not proof of security, not credential validation, and not a production release switch.

Run `node --test tests/Labs/F3_51/homologation-gate.test.mjs`. After collecting independent private homologation evidence, run `node tests/Labs/F3_51/homologation-gate.mjs <evidence.json>` (never commit secrets, visitor data, or raw reports).

Strict JSON schema: `imagine.crm.operational-homologation-evidence.v1`, `environment: private-imagine-homologation`, `revisions` with three immutable 40-character SHA-1 commit IDs (`comercial`, `portal`, `crm`), and `gates` with **exactly** twelve keys exported as `GATES` in the checker. Each gate must have exactly `status: verified`, `execution_id`, `reviewer` and `evidence_url` pointing to a GitHub run/issue/PR/commit in an approved Imagine repository.

Checks cover: isolated environment, immutable provenance, service-to-service authentication, multi-tenant segregation, durable delivery, replay/idempotency, persisted CRM leads, failure/recovery, sanitized audit trail, PostgreSQL least privilege, backup restoration, and independent security review.

A fully populated document outputs **EVIDENCE_COMPLETE_REVIEW_REQUIRED**, never GO. This utility cannot confirm that evidence URLs are authentic, validate reviewers' authority, or prove the results correspond to the recorded commits. Such confirmation requires independent security/DBA verification and actual infrastructure tests.

**Deployment remains forbidden** until operational authentication, safe persistence, end-to-end negative tests, independent reviews and explicit release gates have been satisfied. CRM #259, Portal #315 and Comercial #56 remain unresolved. No production changes, real client data, DNS/Cloudflare changes, VPS access or new costs are introduced by F3-51.
