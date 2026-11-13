\set ON_ERROR_STOP on
-- Apply only after F3_53/nonce_pg16.sql on disposable CI PG16.
CREATE TABLE crm_f353_nonce_lab.synthetic_delivery_receipts (
 organization_reference uuid NOT NULL,
 submission_reference uuid NOT NULL,
 request_nonce uuid NOT NULL,
 delivery_state text NOT NULL CHECK (delivery_state='SYNTHETIC_COMMITTED_NO_REAL_LEAD'),
 PRIMARY KEY (organization_reference,submission_reference),
 FOREIGN KEY (organization_reference,request_nonce)
  REFERENCES crm_f353_nonce_lab.request_nonces (organization_reference,request_nonce)
);
REVOKE ALL ON crm_f353_nonce_lab.synthetic_delivery_receipts FROM PUBLIC;
GRANT INSERT,SELECT ON crm_f353_nonce_lab.synthetic_delivery_receipts TO f353_sender_a,f353_sender_b;
ALTER TABLE crm_f353_nonce_lab.synthetic_delivery_receipts ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_f353_nonce_lab.synthetic_delivery_receipts FORCE ROW LEVEL SECURITY;
CREATE POLICY f354_a ON crm_f353_nonce_lab.synthetic_delivery_receipts
 FOR ALL TO f353_sender_a
 USING (organization_reference='11111111-1111-4111-8111-111111111111'::uuid)
 WITH CHECK (organization_reference='11111111-1111-4111-8111-111111111111'::uuid);
CREATE POLICY f354_b ON crm_f353_nonce_lab.synthetic_delivery_receipts
 FOR ALL TO f353_sender_b
 USING (organization_reference='99999999-9999-4999-8999-999999999999'::uuid)
 WITH CHECK (organization_reference='99999999-9999-4999-8999-999999999999'::uuid);
