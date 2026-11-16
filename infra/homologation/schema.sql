-- Dedicated fictional-only staging database. Never run against another database.
DO $$ BEGIN
 IF current_database() <> 'imagine_crm_private_homologation' THEN
  RAISE EXCEPTION 'Wrong private CRM database';
 END IF;
END $$;

CREATE TABLE crm_private_installation (id integer PRIMARY KEY CHECK(id=1), mode text NOT NULL CHECK(mode='synthetic-v1'));
INSERT INTO crm_private_installation VALUES (1,'synthetic-v1');
CREATE TABLE crm_global_identities(id uuid PRIMARY KEY, created_at timestamptz, updated_at timestamptz);
CREATE TABLE crm_organizations(id uuid PRIMARY KEY, created_at timestamptz, updated_at timestamptz);
CREATE TABLE crm_memberships(id uuid PRIMARY KEY, organization_id uuid NOT NULL REFERENCES crm_organizations,
 global_identity_id uuid NOT NULL REFERENCES crm_global_identities, status varchar(16) NOT NULL CHECK(status IN ('active','suspended','revoked')),
 version bigint NOT NULL DEFAULT 1 CHECK(version>0), lock_marker bigint NOT NULL DEFAULT 0,
 created_at timestamptz, updated_at timestamptz, UNIQUE(organization_id,global_identity_id));
CREATE TABLE crm_auth_credentials(identity_id uuid PRIMARY KEY REFERENCES crm_global_identities,
 login_key varchar(64) UNIQUE NOT NULL, password_hash varchar(255) NOT NULL, status varchar(16) NOT NULL,
 verified_at bigint, version bigint NOT NULL DEFAULT 1, factor_ciphertext text, factor_confirmed_at bigint, last_totp_step bigint,
 lock_marker bigint NOT NULL DEFAULT 0);
CREATE TABLE crm_auth_sessions(id uuid PRIMARY KEY, token_hash varchar(64) UNIQUE NOT NULL,
 identity_id uuid NOT NULL REFERENCES crm_auth_credentials, state varchar(16) NOT NULL,
 credential_version bigint NOT NULL, binding varchar(64) NOT NULL, created_at bigint NOT NULL,
 last_seen_at bigint NOT NULL, expires_at bigint NOT NULL, absolute_expires_at bigint NOT NULL,
 mfa_verified_at bigint, operation_id uuid, security_hold uuid);
CREATE INDEX ON crm_auth_sessions(identity_id,state);
CREATE TABLE crm_auth_recovery_codes(id uuid PRIMARY KEY, identity_id uuid NOT NULL REFERENCES crm_auth_credentials,
 code_hash varchar(255) NOT NULL, credential_version bigint NOT NULL, consumed_at bigint);
CREATE TABLE crm_auth_operations(id uuid PRIMARY KEY, action varchar(32) NOT NULL, actor_id uuid,
 subject_id uuid, mandate_id uuid, state varchar(16) NOT NULL, created_at bigint NOT NULL);
CREATE TABLE crm_auth_mandates(id uuid PRIMARY KEY, actor_id uuid NOT NULL, subject_id uuid NOT NULL,
 actor_version bigint NOT NULL, action varchar(32) NOT NULL, expires_at bigint NOT NULL, revoked_at bigint);
CREATE TABLE crm_iam_audit_events(id uuid PRIMARY KEY, correlation_id uuid NOT NULL, action varchar(32) NOT NULL,
 outcome varchar(16) NOT NULL, actor_id uuid, subject_id uuid, origin_key varchar(64), occurred_at bigint NOT NULL);

CREATE TABLE crm_private_accounts(identity_id uuid PRIMARY KEY REFERENCES crm_global_identities,
 organization_id uuid NOT NULL REFERENCES crm_organizations, label varchar(64) NOT NULL CHECK(label ~ '^EMPRESA_TESTE_[A-Z0-9_]{1,40}$'),
 role varchar(16) NOT NULL DEFAULT 'client' CHECK(role IN ('client','admin')), status varchar(16) NOT NULL DEFAULT 'active'
 CHECK(status IN ('active','suspended')), created_at bigint NOT NULL,
 team_role varchar(16) NOT NULL DEFAULT 'owner' CHECK(team_role IN ('owner','member')),
 desired_plan varchar(16) NOT NULL DEFAULT 'basic' CHECK(desired_plan IN ('basic','essential','professional','enterprise')));
CREATE TABLE crm_private_trials(organization_id uuid PRIMARY KEY REFERENCES crm_organizations,
 started_at bigint NOT NULL, expires_at bigint NOT NULL CHECK(expires_at=started_at+1209600));
CREATE TABLE crm_private_trial_claims(key_version integer NOT NULL, cnpj_hmac varchar(64) NOT NULL,
 organization_id uuid NOT NULL REFERENCES crm_organizations, PRIMARY KEY(key_version,cnpj_hmac));
CREATE TABLE crm_private_key_versions(version integer PRIMARY KEY, fingerprint varchar(64) NOT NULL);
CREATE TABLE crm_private_signup_requests(nonce varchar(32) PRIMARY KEY, contact_binding varchar(64) NOT NULL,
 session_binding varchar(64) NOT NULL, digest varchar(64) NOT NULL, issued_at bigint NOT NULL,
 expires_at bigint NOT NULL, attempts_used integer NOT NULL DEFAULT 0 CHECK(attempts_used BETWEEN 0 AND 5),
 consumed_at bigint, completed_at bigint, mfa_attempts integer NOT NULL DEFAULT 0 CHECK(mfa_attempts BETWEEN 0 AND 5),
 payload_ciphertext text NOT NULL, delivery_ciphertext text NOT NULL);
CREATE INDEX ON crm_private_signup_requests(contact_binding,issued_at);
CREATE TABLE crm_private_recovery_attempts(login_key varchar(64) NOT NULL, window_start bigint NOT NULL,
 attempts integer NOT NULL DEFAULT 0 CHECK(attempts BETWEEN 0 AND 5), PRIMARY KEY(login_key,window_start));
CREATE TABLE crm_private_funnels(id uuid PRIMARY KEY, organization_id uuid NOT NULL REFERENCES crm_organizations,
 title varchar(64) NOT NULL CHECK(title ~ '^FUNIL_TESTE_[A-Z0-9_]{1,40}$'), created_at bigint NOT NULL,
 UNIQUE(organization_id,id));
CREATE TABLE crm_private_invitations(id uuid PRIMARY KEY, organization_id uuid NOT NULL REFERENCES crm_organizations,
 token_hash varchar(64) UNIQUE NOT NULL, expires_at bigint NOT NULL, created_at bigint NOT NULL,
 state varchar(16) NOT NULL DEFAULT 'pending' CHECK(state IN ('pending','accepted','cancelled')));
CREATE TABLE crm_private_leads(id uuid PRIMARY KEY, organization_id uuid NOT NULL REFERENCES crm_organizations,
 funnel_id uuid NOT NULL,
 title varchar(64) NOT NULL CHECK(title ~ '^LEAD_TESTE_[A-Z0-9_]{1,40}$'),
 stage varchar(16) NOT NULL DEFAULT 'new' CHECK(stage IN ('new','qualified','proposal','won','lost')),
 value_cents integer NOT NULL DEFAULT 0 CHECK(value_cents BETWEEN 0 AND 100000000),
 version bigint NOT NULL DEFAULT 1 CHECK(version>0), created_at bigint NOT NULL, UNIQUE(organization_id,id),
 FOREIGN KEY(organization_id,funnel_id) REFERENCES crm_private_funnels(organization_id,id));
CREATE TABLE crm_private_tasks(id uuid PRIMARY KEY, organization_id uuid NOT NULL REFERENCES crm_organizations,
 lead_id uuid NOT NULL, title varchar(64) NOT NULL CHECK(title ~ '^TASK_TESTE_[A-Z0-9_]{1,40}$'),
 due_on date NOT NULL, state varchar(16) NOT NULL DEFAULT 'todo' CHECK(state IN ('todo','done','cancelled')),
 version bigint NOT NULL DEFAULT 1 CHECK(version>0),
 FOREIGN KEY(organization_id,lead_id) REFERENCES crm_private_leads(organization_id,id));
CREATE TABLE crm_private_events(id uuid PRIMARY KEY, organization_id uuid NOT NULL REFERENCES crm_organizations,
 actor_id uuid NOT NULL REFERENCES crm_global_identities, object_id uuid NOT NULL,
 action varchar(40) NOT NULL, occurred_at bigint NOT NULL);
CREATE TABLE crm_private_orders(id uuid PRIMARY KEY, organization_id uuid NOT NULL REFERENCES crm_organizations,
 plan varchar(16) NOT NULL CHECK(plan IN ('basic','essential','professional','enterprise')),
 state varchar(16) NOT NULL DEFAULT 'pending' CHECK(state IN ('pending','approved','refused','cancelled')),
 payment_method varchar(16) NOT NULL CHECK(payment_method IN ('pix','card','boleto')),
 idempotency_key uuid NOT NULL, version bigint NOT NULL DEFAULT 1, created_at bigint NOT NULL,
 UNIQUE(organization_id,idempotency_key));
CREATE TABLE crm_private_preferences(organization_id uuid PRIMARY KEY REFERENCES crm_organizations,
 language varchar(2) NOT NULL DEFAULT 'pt' CHECK(language IN ('pt','en','es')),
 automation_rules jsonb NOT NULL DEFAULT '[]'::jsonb);
-- Candidate CI-only extension; never apply to an existing VPS installation without migration review.
CREATE TABLE crm_private_automation_runs(
 id uuid PRIMARY KEY, organization_id uuid NOT NULL REFERENCES crm_organizations,
 actor_id uuid NOT NULL REFERENCES crm_global_identities, authority_session_id uuid NOT NULL, -- Verified through IAM connection; no cross-connection FK lock inversion.
 template varchar(32) NOT NULL CHECK(template IN ('followup_qualified','followup_proposal')),
 settings_version integer NOT NULL, lead_id uuid NOT NULL, lead_version bigint NOT NULL,
 execution_key varchar(64) NOT NULL, command jsonb NOT NULL,
 state varchar(16) NOT NULL DEFAULT 'pending' CHECK(state IN ('pending','completed','cancelled','failed')),
 due_at bigint NOT NULL, created_at bigint NOT NULL, attempts integer NOT NULL DEFAULT 0 CHECK(attempts BETWEEN 0 AND 3),
 UNIQUE(organization_id,execution_key), FOREIGN KEY(organization_id,lead_id) REFERENCES crm_private_leads(organization_id,id));
CREATE TABLE crm_private_operator_events(id uuid PRIMARY KEY, actor_id uuid NOT NULL REFERENCES crm_global_identities,
 subject_id uuid NOT NULL, action varchar(32) NOT NULL CHECK(action IN ('suspend','resume')),
 occurred_at bigint NOT NULL);

DO $$ DECLARE t text; BEGIN
 FOREACH t IN ARRAY ARRAY['crm_memberships','crm_private_trials','crm_private_leads','crm_private_tasks',
 'crm_private_automation_runs','crm_private_events','crm_private_orders','crm_private_preferences','crm_private_funnels','crm_private_invitations'] LOOP
  EXECUTE format('ALTER TABLE %I ENABLE ROW LEVEL SECURITY',t);
  EXECUTE format('ALTER TABLE %I FORCE ROW LEVEL SECURITY',t);
  EXECUTE format('CREATE POLICY tenant_scope ON %I USING (organization_id = NULLIF(current_setting(''app.tenant_id'',true),'''')::uuid) WITH CHECK (organization_id = NULLIF(current_setting(''app.tenant_id'',true),'''')::uuid)',t);
 END LOOP;
END $$;
REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON ALL TABLES IN SCHEMA public FROM PUBLIC;
