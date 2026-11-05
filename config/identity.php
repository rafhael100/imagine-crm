<?php

return [
    // Candidate laboratory profile only. No public authentication routes.
    'experimental' => (bool) env('CRM_AUTH_EXPERIMENTAL', false),
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
