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
];
