"""Closed PostgreSQL 16 catalog fidelity for synthetic private restores.

This module reads schema metadata and compares it. Profile admission, credentials,
dump orchestration and restore activation remain outside this contract.
"""
from __future__ import annotations

import json

# Only these fifteen pairs were observed in job 134571533998 on PG16. These
# are complete expected forms, not a general SQL parser or cast-removal rule.
CATALOG_RULE = "CRM_PG16_VARCHAR_LITERAL_ARRAY_CAST_V1"
ARRAY_CHECKS = {
    ("public.crm_memberships", "crm_memberships_status_check"):
        ("status", 16, ("active", "suspended", "revoked")),
    ("public.crm_private_accounts", "crm_private_accounts_desired_plan_check"):
        ("desired_plan", 16, ("basic", "essential", "professional", "enterprise")),
    ("public.crm_private_accounts", "crm_private_accounts_role_check"):
        ("role", 16, ("client", "admin")),
    ("public.crm_private_accounts", "crm_private_accounts_status_check"):
        ("status", 16, ("active", "suspended")),
    ("public.crm_private_accounts", "crm_private_accounts_team_role_check"):
        ("team_role", 16, ("owner", "member")),
    ("public.crm_private_automation_runs", "crm_private_automation_runs_state_check"):
        ("state", 16, ("pending", "completed", "cancelled", "failed")),
    ("public.crm_private_automation_runs", "crm_private_automation_runs_template_check"):
        ("template", 32, ("followup_qualified", "followup_proposal")),
    ("public.crm_private_invitations", "crm_private_invitations_state_check"):
        ("state", 16, ("pending", "accepted", "cancelled")),
    ("public.crm_private_leads", "crm_private_leads_stage_check"):
        ("stage", 16, ("new", "qualified", "proposal", "won", "lost")),
    ("public.crm_private_operator_events", "crm_private_operator_events_action_check"):
        ("action", 32, ("suspend", "resume")),
    ("public.crm_private_orders", "crm_private_orders_payment_method_check"):
        ("payment_method", 16, ("pix", "card", "boleto")),
    ("public.crm_private_orders", "crm_private_orders_plan_check"):
        ("plan", 16, ("basic", "essential", "professional", "enterprise")),
    ("public.crm_private_orders", "crm_private_orders_state_check"):
        ("state", 16, ("pending", "approved", "refused", "cancelled")),
    ("public.crm_private_preferences", "crm_private_preferences_language_check"):
        ("language", 2, ("pt", "en", "es")),
    ("public.crm_private_tasks", "crm_private_tasks_state_check"):
        ("state", 16, ("todo", "done", "cancelled")),
}


def catalog_manifest(query, *, schemas=("public",)):
    if type(schemas) is not tuple or schemas not in (("public",), ("public", "crm_auto_service")):
        raise ValueError("Unsupported private catalog schema scope.")
    predicate = ("n.nspname='public'" if schemas == ("public",)
                 else "n.nspname IN ('public','crm_auto_service')")
    # security_manifest covers owners, ACLs, column grants and policy expressions;
    # these definitions additionally bind FK/unique/check and column semantics.
    return json.loads(query(f"""
      SELECT jsonb_build_object(
       'database_collation',(SELECT jsonb_build_object(
         'encoding',pg_encoding_to_char(encoding),'provider',datlocprovider,
         'collate',datcollate,'ctype',datctype,'icu_locale',daticulocale,'icu_rules',daticurules,'version',datcollversion)
         FROM pg_database WHERE datname=current_database()),
       'constraints',COALESCE((SELECT jsonb_agg(jsonb_build_object(
         'table',n.nspname||'.'||c.relname,'name',k.conname,'type',k.contype,
         'deferrable',k.condeferrable,'deferred',k.condeferred,'validated',k.convalidated,
         'definition',pg_get_constraintdef(k.oid,false)) ORDER BY n.nspname,c.relname,k.conname)
         FROM pg_constraint k JOIN pg_class c ON c.oid=k.conrelid
         JOIN pg_namespace n ON n.oid=c.relnamespace WHERE {predicate}),'[]'::jsonb),
       'columns',COALESCE((SELECT jsonb_agg(jsonb_build_object(
         'table',n.nspname||'.'||c.relname,'name',a.attname,'position',a.attnum,
         'type',format_type(a.atttypid,a.atttypmod),'not_null',a.attnotnull,
         'collation',(SELECT cn.nspname||'.'||co.collname FROM pg_collation co
           JOIN pg_namespace cn ON cn.oid=co.collnamespace WHERE co.oid=a.attcollation),
         'default',pg_get_expr(d.adbin,d.adrelid),'identity',a.attidentity,'generated',a.attgenerated)
         ORDER BY n.nspname,c.relname,a.attnum)
         FROM pg_attribute a JOIN pg_class c ON c.oid=a.attrelid
         JOIN pg_namespace n ON n.oid=c.relnamespace
         LEFT JOIN pg_attrdef d ON d.adrelid=a.attrelid AND d.adnum=a.attnum
         WHERE {predicate} AND c.relkind='r' AND a.attnum>0 AND NOT a.attisdropped),'[]'::jsonb));
    """))



def catalog_comparison(expected, actual):
    """Exact catalog, or the fifteen observed PG16 pairs with every other byte exact."""
    if set(expected) != {"constraints", "columns", "database_collation"} or set(actual) != set(expected):
        return None
    if actual == expected:
        return {"rule": "EXACT", "equivalences": 0, "constraints": []}
    if expected["columns"] != actual["columns"] or expected["database_collation"] != actual["database_collation"]:
        return None
    if len(expected["constraints"]) != len(actual["constraints"]):
        return None
    changed = []
    # Compare the original ordered lists, not dictionaries that could hide a
    # duplicate, deletion or reordering of a constraint.
    for before, after in zip(expected["constraints"], actual["constraints"]):
        if before == after:
            continue
        identity = (before.get("table"), before.get("name"))
        specification = ARRAY_CHECKS.get(identity)
        if specification is None or set(before) != set(after) or set(before) != {
                "table", "name", "type", "deferrable", "deferred", "validated", "definition"}:
            return None
        if {key: value for key, value in before.items() if key != "definition"} != {
                key: value for key, value in after.items() if key != "definition"}:
            return None
        if before["type"] != "c" or before["deferrable"] or before["deferred"] or before["validated"] is not True:
            return None
        column, width, literals = specification
        columns = [item for item in expected["columns"] if item["table"] == identity[0] and item["name"] == column]
        if len(columns) != 1 or columns[0]["type"] != "character varying(" + str(width) + ")" \
                or columns[0]["collation"] != "pg_catalog.default" or columns[0]["not_null"] is not True:
            return None
        source_items = ", ".join("'" + value + "'::character varying" for value in literals)
        target_items = ", ".join("('" + value + "'::character varying)::text" for value in literals)
        source_form = "CHECK (((" + column + ")::text = ANY ((ARRAY[" + source_items + "])::text[])))"
        target_form = "CHECK (((" + column + ")::text = ANY (ARRAY[" + target_items + "])))"
        if before["definition"] != source_form or after["definition"] != target_form:
            return None
        changed.append(identity[0] + "." + identity[1])
    if not changed or len(changed) != len(set(changed)):
        return None
    return {"rule": CATALOG_RULE, "equivalences": len(changed), "constraints": changed}

def security_constraints_match(security, catalog):
    """Bind every security constraint field to its canonical catalog record."""
    fields = ("table", "name", "type", "validated", "definition")
    constraints = catalog.get("constraints")
    if not isinstance(constraints, list) or not all(
            isinstance(row, dict) and set(fields) <= set(row) for row in constraints):
        return False
    return security.get("constraints") == [
        {field: row[field] for field in fields} for row in constraints]

def security_catalog_comparison(expected, actual, source_catalog, restored_catalog):
    """Check both bindings and the closed catalog rule before any equivalence."""
    if not security_constraints_match(expected, source_catalog) \
            or not security_constraints_match(actual, restored_catalog):
        return None
    comparison = catalog_comparison(source_catalog, restored_catalog)
    if comparison is None:
        return None
    # This key is equivalent only after every field has been bound and the
    # unchanged catalog verifier accepted it. All other keys, values and list
    # order remain part of the original complete security-manifest equality.
    if {**actual, "constraints": expected["constraints"]} != expected:
        return None
    return comparison
