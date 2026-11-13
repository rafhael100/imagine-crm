\set ON_ERROR_STOP on
-- Disposable CI-only roles. Never deploy this SQL or credentials.
CREATE ROLE f353_sender_a LOGIN PASSWORD 'f353-synthetic-a-only';
CREATE ROLE f353_sender_b LOGIN PASSWORD 'f353-synthetic-b-only';
CREATE SCHEMA crm_f353_nonce_lab AUTHORIZATION postgres;
REVOKE ALL ON SCHEMA crm_f353_nonce_lab FROM PUBLIC;
GRANT USAGE ON SCHEMA crm_f353_nonce_lab TO f353_sender_a, f353_sender_b;
CREATE TABLE crm_f353_nonce_lab.request_nonces (
  organization_reference uuid NOT NULL,
  request_nonce uuid NOT NULL,
  issued_at bigint NOT NULL CHECK (issued_at > 0),
  PRIMARY KEY (organization_reference, request_nonce)
);
REVOKE ALL ON crm_f353_nonce_lab.request_nonces FROM PUBLIC;
GRANT INSERT, SELECT ON crm_f353_nonce_lab.request_nonces TO f353_sender_a, f353_sender_b;
ALTER TABLE crm_f353_nonce_lab.request_nonces ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_f353_nonce_lab.request_nonces FORCE ROW LEVEL SECURITY;
CREATE POLICY f353_a ON crm_f353_nonce_lab.request_nonces
  FOR ALL TO f353_sender_a
  USING (organization_reference='11111111-1111-4111-8111-111111111111'::uuid)
  WITH CHECK (organization_reference='11111111-1111-4111-8111-111111111111'::uuid);
CREATE POLICY f353_b ON crm_f353_nonce_lab.request_nonces
  FOR ALL TO f353_sender_b
  USING (organization_reference='99999999-9999-4999-8999-999999999999'::uuid)
  WITH CHECK (organization_reference='99999999-9999-4999-8999-999999999999'::uuid);
