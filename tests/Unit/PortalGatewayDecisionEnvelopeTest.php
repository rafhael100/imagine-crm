<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Billing\PortalGatewayDecisionEnvelope;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PortalGatewayDecisionEnvelopeTest extends TestCase
{
    private const KEY = 'synthetic-gateway-crossproduct-review-key-2026-11';
    private function fields(string $gateway = 'asaas'): array
    {
        return [
            'schema_version' => 'imagine.payment-routing.review.v1',
            'organization_ref' => 'org-synthetic-001',
            'order_ref' => 'order-synthetic-001',
            'gateway' => $gateway,
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
    }

    public function test_asaas_review_is_authenticated_but_cannot_charge(): void
    {
        $envelope = PortalGatewayDecisionEnvelope::sign($this->fields(), self::KEY);
        $result = PortalGatewayDecisionEnvelope::verify($envelope, self::KEY, 'org-synthetic-001');
        self::assertSame('asaas', $result['gateway']);
        self::assertFalse($result['can_charge']);
        self::assertFalse($result['can_activate']);
    }

    public function test_c6_is_a_valid_future_review_code_but_not_a_payment(): void
    {
        $envelope = PortalGatewayDecisionEnvelope::sign($this->fields('c6'), self::KEY);
        self::assertSame('c6', PortalGatewayDecisionEnvelope::verify($envelope, self::KEY, 'org-synthetic-001')['gateway']);
    }

    public function test_tampered_tariff_is_rejected(): void
    {
        $envelope = PortalGatewayDecisionEnvelope::sign($this->fields(), self::KEY);
        $envelope['payload']['estimated_fee_cents'] = 1;
        $this->expectException(InvalidArgumentException::class);
        PortalGatewayDecisionEnvelope::verify($envelope, self::KEY, 'org-synthetic-001');
    }

    public function test_cross_tenant_replay_is_rejected(): void
    {
        $envelope = PortalGatewayDecisionEnvelope::sign($this->fields(), self::KEY);
        $this->expectException(InvalidArgumentException::class);
        PortalGatewayDecisionEnvelope::verify($envelope, self::KEY, 'org-synthetic-OTHER');
    }

    public function test_charge_activation_flag_is_rejected_even_with_valid_mac(): void
    {
        $p = $this->fields();
        $p['can_charge'] = true;
        $this->expectException(InvalidArgumentException::class);
        PortalGatewayDecisionEnvelope::sign($p, self::KEY);
    }

    public function test_unknown_field_or_gateway_is_rejected(): void
    {
        $p = $this->fields();
        $p['api_key'] = 'secret';
        $this->expectException(InvalidArgumentException::class);
        PortalGatewayDecisionEnvelope::sign($p, self::KEY);
    }

    public function test_missing_key_never_falls_back_to_insecure_signature(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PortalGatewayDecisionEnvelope::sign($this->fields(), '123');
    }
}
