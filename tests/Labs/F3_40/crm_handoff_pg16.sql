-- CRM F3-40: CI ONLY. PostgreSQL 16 independent fake CRM receiver.
-- Invoke ONLY against fresh imagine_crm_handoff_ci in GitHub-hosted Actions.
-- No live migrations, PII, visitor fields, contact payload or production grants.
CREATE SCHEMA crm_portal_handoff_ci;
REVOKE ALL ON SCHEMA crm_portal_handoff_ci FROM PUBLIC;
CREATE ROLE crm_f340_receiver_a NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS;
CREATE ROLE crm_f340_receiver_b NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS;

CREATE TABLE crm_portal_handoff_ci.metadata_receipts (
  organization_reference uuid NOT NULL,
  submission_reference uuid NOT NULL,
  binding_reference uuid NOT NULL,
  site_reference uuid NOT NULL,
  form_reference uuid NOT NULL,
  destination_reference uuid NOT NULL,
  stored_at timestamptz NOT NULL DEFAULT transaction_timestamp(),
  PRIMARY KEY(organization_reference,submission_reference)
);
CREATE TABLE crm_portal_handoff_ci.request_nonces (
  organization_reference uuid NOT NULL,
  nonce uuid NOT NULL,
  submission_reference uuid NOT NULL,
  registered_at timestamptz NOT NULL DEFAULT transaction_timestamp(),
  PRIMARY KEY(organization_reference,nonce),
  FOREIGN KEY(organization_reference,submission_reference)
    REFERENCES crm_portal_handoff_ci.metadata_receipts
      (organization_reference,submission_reference)
    DEFERRABLE INITIALLY DEFERRED
);

REVOKE ALL ON crm_portal_handoff_ci.metadata_receipts FROM PUBLIC;
REVOKE ALL ON crm_portal_handoff_ci.request_nonces FROM PUBLIC;
ALTER TABLE crm_portal_handoff_ci.metadata_receipts ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_portal_handoff_ci.metadata_receipts FORCE ROW LEVEL SECURITY;
ALTER TABLE crm_portal_handoff_ci.request_nonces ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_portal_handoff_ci.request_nonces FORCE ROW LEVEL SECURITY;

GRANT USAGE ON SCHEMA crm_portal_handoff_ci TO crm_f340_receiver_a,crm_f340_receiver_b;
GRANT SELECT, INSERT ON crm_portal_handoff_ci.metadata_receipts TO crm_f340_receiver_a,crm_f340_receiver_b;
GRANT SELECT, INSERT ON crm_portal_handoff_ci.request_nonces TO crm_f340_receiver_a,crm_f340_receiver_b;

CREATE POLICY f340_receipt_a ON crm_portal_handoff_ci.metadata_receipts
  FOR ALL TO crm_f340_receiver_a
  USING (organization_reference='11111111-1111-4111-8111-111111111111'::uuid)
  WITH CHECK(organization_reference='11111111-1111-4111-8111-111111111111'::uuid);
CREATE POLICY f340_receipt_b ON crm_portal_handoff_ci.metadata_receipts
  FOR ALL TO crm_f340_receiver_b
  USING (organization_reference='99999999-9999-4999-8999-999999999999'::uuid)
  WITH CHECK(organization_reference='99999999-9999-4999-8999-999999999999'::uuid);
CREATE POLICY f340_nonce_a ON crm_portal_handoff_ci.request_nonces
  FOR ALL TO crm_f340_receiver_a
  USING (organization_reference='11111111-1111-4111-8111-111111111111'::uuid)
  WITH CHECK(organization_reference='11111111-1111-4111-8111-111111111111'::uuid);
CREATE POLICY f340_nonce_b ON crm_portal_handoff_ci.request_nonces
  FOR ALL TO crm_f340_receiver_b
  USING (organization_reference='99999999-9999-4999-8999-999999999999'::uuid)
  WITH CHECK(organization_reference='99999999-9999-4999-8999-999999999999'::uuid);
COMMENT ON SCHEMA crm_portal_handoff_ci IS
  'F3-40 disposable CI-only receiver built on real CRM PHP classes, NOT an operational API.';
