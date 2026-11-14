<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2).'/app/Core/Billing/PortalGatewayDecisionEnvelope.php';

use App\Core\Billing\PortalGatewayDecisionEnvelope;

$key = 'synthetic-gateway-crossproduct-review-key-2026-11';
$payload = [
    'schema_version' => 'imagine.payment-routing.review.v1',
    'organization_ref' => 'org-synthetic-001',
    'order_ref' => 'order-synthetic-001',
    'gateway' => 'asaas',
    'method' => 'pix',
    'currency' => 'BRL',
    'amount_cents' => 10000,
    'estimated_fee_cents' => 175,
    'fee_version' => 'SIM-ASAAS-01',
    'policy_revision' => 1,
    'can_charge' => false,
    'can_activate' => false,
    'environment' => 'offline_review',
];
$envelope = PortalGatewayDecisionEnvelope::sign($payload, $key);
if ($envelope['mac'] !== '7a6738f4a62c15ab7748c7e31fc07b74cf0b2ec1bbf01ae13b48ab1d5b63fc31') {
    throw new RuntimeException('Portal/CRM HMAC contract mismatch');
}
if (PortalGatewayDecisionEnvelope::verify($envelope, $key, 'org-synthetic-001') !== $payload) {
    throw new RuntimeException('CRM signed message verification failed');
}
$forged = $envelope;
$forged['payload']['amount_cents'] = 1;
try {
    PortalGatewayDecisionEnvelope::verify($forged, $key, 'org-synthetic-001');
    throw new RuntimeException('Forged amount accepted');
} catch (InvalidArgumentException) {
}
try {
    PortalGatewayDecisionEnvelope::verify($envelope, $key, 'org-synthetic-other');
    throw new RuntimeException('Cross-tenant replay accepted');
} catch (InvalidArgumentException) {
}
echo "IMAGINE CRM multigateway offline contract: OK\n";
