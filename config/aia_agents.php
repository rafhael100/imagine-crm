<?php

declare(strict_types=1);

// Contract P0: these settings are intentionally NOT sourced from .env or
// request headers. Until independent OP-01 approval, no operator can switch
// a live CRM lead read on merely by setting an environment variable.
return [
    'op01_review_probe_enabled' => false,
    'op01_business_read_enabled' => false,
    'op01_service_ingress_enabled' => false,
    'provider_calls_enabled' => false,
];
