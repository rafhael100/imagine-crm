# CRM F3-54 — coupled nonce + synthetic metadata receipt

This lab tests a PostgreSQL **single-transaction** boundary: the request nonce and a metadata-only synthetic receipt either commit together or roll back together. The test covers a constraint failure after nonce reservation, duplicate replay, and a new nonce used against the same logical submission, ensuring neither failure leaves an orphan nonce.

It reuses the independently restricted synthetic roles and RLS policies from F3-53 and adds a unique receipt key and foreign key. No leads, contacts, budget messages or personally identifying fields are stored; the receipt status explicitly says **NO_REAL_LEAD**.

This is not a production service, reliable network delivery, authenticated operational tenant, or proof of compatibility with actual CRM lead tables. Private homologation still requires a reviewed schema and service identity, an actual API receiver, crash recovery, signed readback and independent AppSec/DBA approval. **NO-GO** for production.
