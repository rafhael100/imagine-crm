<?php

return [
    // Candidate laboratory profile only. No public authentication routes.
    'experimental' => (bool) env('CRM_AUTH_EXPERIMENTAL', false),
    // Candidate tenant DDL requires a separate, explicit laboratory opt-in.
    'tenant_schema_experimental' => (bool) env('CRM_TENANT_SCHEMA_EXPERIMENTAL', false),
    // Exact database name to validate against PostgreSQL current_database().
    'tenant_schema_lab_database' => env('CRM_TENANT_SCHEMA_LAB_DATABASE', ''),
    'pending_seconds' => 300,
    'idle_seconds' => 1800,
    'absolute_seconds' => 43200,
    'step_up_seconds' => 300,
    'attempts' => 5,
    'decay_seconds' => 60,
    // Candidate laboratory budgets. No fallback to array/file on Redis failure.
    'abuse_store' => env('CRM_IAM_ABUSE_STORE', 'redis'),
    'abuse_window' => 60,
    'abuse_identity_limit' => 10,
    'abuse_origin_limit' => 30,
    'abuse_backoff' => 1,
];
