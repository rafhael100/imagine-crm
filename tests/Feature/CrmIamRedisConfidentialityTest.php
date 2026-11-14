<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\Totp;
use Tests\Concerns\SyntheticIam;
use Tests\TestCase;

/**
 * CRM-IAM-REDIS-02: physical encrypted Redis session and rotation evidence.
 * Runs only inside the CI synthetic-only Redis + PostgreSQL laboratory.
 * No public routes, customer accounts or operational authorization.
 */
final class CrmIamRedisConfidentialityTest extends TestCase
{
    use SyntheticIam;

    private function assertEncryptedRedisPayload(string $sessionId, string $sensitiveToken): void
    {
        $stored = $this->browserSession->getHandler()->read($sessionId);
        $this->assertIsString($stored);
        $this->assertNotSame('', $stored, 'A session must be persisted in real Redis.');
        $this->assertStringNotContainsString($sensitiveToken, $stored);
        $this->assertStringNotContainsString('crm.auth.', $stored);
        $this->assertStringNotContainsString(self::A, $stored);

        // Laravel's Encrypter formats authenticated ciphertext as base64 JSON.
        // Read the raw session handler, NOT the decrypted Session Store.
        $decoded = base64_decode($stored, true);
        $this->assertNotFalse($decoded, 'Stored session must be an encrypted envelope.');
        $envelope = json_decode($decoded, true);
        $this->assertIsArray($envelope);
        foreach (['iv', 'value', 'mac'] as $field) {
            $this->assertArrayHasKey($field, $envelope);
            $this->assertIsString($envelope[$field]);
            $this->assertNotSame('', $envelope[$field]);
        }
    }

    public function test_redis_payload_is_encrypted_and_old_session_ids_are_destroyed(): void
    {
        if (getenv('CRM_IAM_REDIS_LAB') !== 'synthetic-only') {
            $this->markTestSkipped('Real ephemeral Redis test requires the explicit CI synthetic gate.');
        }
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('redis', config('session.driver'));
        $this->assertSame('redis', config('session.store'));
        $this->assertTrue(config('session.encrypt'));
        $this->assertSame('strict', config('session.same_site'));
        $this->assertTrue(config('session.secure'));
        $this->assertTrue(config('session.http_only'));

        $this->auth->begin('fixture-a', self::PASSWORD);
        $pendingId = $this->browserSession->getId();
        $pendingToken = $this->browserSession->get('crm.auth.pending');
        $this->assertIsString($pendingToken);
        $this->browserSession->save();
        $this->assertEncryptedRedisPayload($pendingId, $pendingToken);

        $this->auth->completeMfa((new Totp())->code(self::SECRET, now()->getTimestamp()));
        $activeId = $this->browserSession->getId();
        $activeToken = $this->browserSession->get('crm.auth.token');
        $this->assertNotSame($pendingId, $activeId, 'MFA must rotate the browser session.');
        $this->assertIsString($activeToken);
        $this->browserSession->save();
        $this->assertSame('', $this->browserSession->getHandler()->read($pendingId),
            'Redis must destroy the pending session during MFA rotation.');
        $this->assertEncryptedRedisPayload($activeId, $activeToken);
        $this->assertSame(1, $this->db->table('crm_auth_sessions')
            ->where('identity_id', self::A)->where('state', 'active')->count());

        $this->auth->logout();
        $this->browserSession->save();
        $this->assertSame('', $this->browserSession->getHandler()->read($activeId),
            'Redis must destroy the previous authenticated session on logout.');
        $this->assertSame(0, $this->db->table('crm_auth_sessions')
            ->where('identity_id', self::A)->where('state', 'active')->count());
    }
}
