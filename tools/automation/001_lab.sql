-- CI disposable database only. NOT a Laravel migration or an operational IAM grant.
CREATE TABLE automation_tenants (
 tenant text PRIMARY KEY, enabled boolean NOT NULL DEFAULT false,
 rule_limit integer NOT NULL CHECK(rule_limit BETWEEN 0 AND 100),
 daily_limit integer NOT NULL CHECK(daily_limit BETWEEN 0 AND 10000),
 max_attempts integer NOT NULL CHECK(max_attempts BETWEEN 1 AND 5)
);
CREATE TABLE automation_rules (
 tenant text NOT NULL REFERENCES automation_tenants, rule_id text NOT NULL,
 version integer NOT NULL CHECK(version > 0), active boolean NOT NULL DEFAULT false,
 definition jsonb NOT NULL, PRIMARY KEY(tenant, rule_id)
);
CREATE TABLE automation_versions (
 tenant text NOT NULL, rule_id text NOT NULL, version integer NOT NULL,
 definition jsonb NOT NULL, PRIMARY KEY(tenant,rule_id,version)
);
CREATE TABLE automation_executions (
 tenant text NOT NULL REFERENCES automation_tenants, execution_key text NOT NULL,
 event_id text NOT NULL, rule_id text NOT NULL, version integer NOT NULL,
 payload_hash text NOT NULL, command jsonb NOT NULL, due_at bigint NOT NULL,
 admitted_at bigint NOT NULL, attempts integer NOT NULL DEFAULT 0 CHECK(attempts BETWEEN 0 AND 5),
 state text NOT NULL CHECK(state IN ('pending','completed','cancelled','failed')),
 PRIMARY KEY(tenant,execution_key)
);
CREATE TABLE automation_effects (
 tenant text NOT NULL, execution_key text NOT NULL, command jsonb NOT NULL,
 PRIMARY KEY(tenant,execution_key),
 FOREIGN KEY(tenant,execution_key) REFERENCES automation_executions
);
CREATE TABLE automation_audit (
 id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 tenant text NOT NULL, event text NOT NULL, reference text NOT NULL, occurred_at bigint NOT NULL
);
-- RLS is an additional boundary, not proof of identity. Trusted backend resolves the scope.
DO $$ DECLARE t text; BEGIN
 FOREACH t IN ARRAY ARRAY['automation_tenants','automation_rules','automation_versions','automation_executions','automation_effects','automation_audit'] LOOP
  EXECUTE format('ALTER TABLE %I ENABLE ROW LEVEL SECURITY',t);
  EXECUTE format('ALTER TABLE %I FORCE ROW LEVEL SECURITY',t);
  EXECUTE format('CREATE POLICY tenant_scope ON %I USING (tenant = current_setting(''app.automation_tenant'',true)) WITH CHECK (tenant = current_setting(''app.automation_tenant'',true))',t);
 END LOOP;
END $$;
