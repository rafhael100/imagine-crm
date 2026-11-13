# CRM F3-53 — PostgreSQL nonce replay isolation, CI only

Adds a dedicated, disposable PostgreSQL 16 database and two synthetic non-privileged login roles with separate RLS policies to test **atomic request nonce claiming**.

A primary key on (organization_reference, request_nonce) and `INSERT ... ON CONFLICT DO NOTHING RETURNING` ensure that a retransmitted request cannot claim the same nonce twice, even when the sender loses a response. The test also verifies cross-tenant invisibility, RLS write denial and lack of UPDATE/DELETE privilege. The roles have no production grants, no lead persistence and no access to real records.

The policy uses fixed synthetic identities and is **not** a proposed dynamic production RLS pattern. An operational system must bind the authorized tenant using server-side authentication and DBA-reviewed policies, rotate credentials, and perform transactionally coupled nonce/lead persistence. Do not deploy this SQL. No HTTP endpoint or customer acknowledgment is created.

Execute via the dedicated workflow on a GitHub-hosted runner. F3-51/F3-52 remain NO-GO.
