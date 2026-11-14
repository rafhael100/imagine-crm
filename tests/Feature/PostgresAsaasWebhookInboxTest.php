<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasWebhookEnvelope;
use App\Core\Billing\AsaasWebhookInboxCandidate;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * BILL-ASAAS-02 — disposable PostgreSQL real connections + PHPUnit-only HTTP.
 * All values are fictional. Neither app/routes nor the VPS exposes this route.
 */
final class PostgresAsaasWebhookInboxTest extends TestCase
{
    private const AUTH = 'ci-asaas-webhook-only-auth-token-over-32-characters';
    private const BINDING = 'ci-only-asaas-hmac-separation-key-with-32bytes';
    private const EVENT_ID = 'evt_0123456789abcdef';
    private const OTHER_ID = 'evt_fedcba9876543210';

    private Connection $owner;
    private Connection $one;
    private Connection $two;

    protected function setUp(): void
    {
        parent::setUp();
        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_WEBHOOK_LAB') !== 'synthetic-only'
            || getenv('BILLING_TEST_USERNAME') !== 'crm_billing_ci'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('PostgreSQL Asaas inbox is CI-only.');
        }
        $this->owner = DB::connection();
        $this->clear();

        $settings = $pg;
        $settings['url'] = null;
        $settings['username'] = getenv('BILLING_TEST_USERNAME');
        $settings['password'] = getenv('BILLING_TEST_PASSWORD');
        config([
            'database.connections.asaas_first_lab' => $settings,
            'database.connections.asaas_second_lab' => $settings,
        ]);
        DB::purge('asaas_first_lab');
        DB::purge('asaas_second_lab');
        $this->one = DB::connection('asaas_first_lab');
        $this->two = DB::connection('asaas_second_lab');
        $this->two->statement("SET lock_timeout = '250ms'");

        // Test-only delivery endpoint; NOT in routes/web.php, bootstrap,
        // providers, controllers, or any deployable handler.
        Route::post('/__ci/asaas-webhook-review', function (Request $request) {
            $headers = [
                'asaas-access-token' => $request->header('asaas-access-token'),
                'content-type' => $request->header('content-type'),
            ];
            try {
                $envelope = AsaasWebhookEnvelope::parse(
                    $headers, (string) $request->getContent(),
                    self::AUTH, self::BINDING,
                );
            } catch (InvalidArgumentException) {
                return response()->json(['received' => false], 401, [
                    'Cache-Control' => 'no-store, private',
                ]);
            }
            try {
                (new AsaasWebhookInboxCandidate($this->one))->record(
                    $envelope, 1700000000,
                );
            } catch (RuntimeException) {
                // Cannot acknowledge non-durable persistence.
                return response()->json(['received' => false], 503, [
                    'Cache-Control' => 'no-store, private',
                ]);
            }
            return response()->json(['received' => true], 200, [
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        });
    }

    protected function tearDown(): void
    {
        if (isset($this->one)) {
            foreach ([$this->one, $this->two] as $db) {
                while ($db->transactionLevel() > 0) {
                    $db->rollBack();
                }
            }
            $this->clear();
            DB::purge('asaas_first_lab');
            DB::purge('asaas_second_lab');
        }
        parent::tearDown();
    }

    private function clear(): void
    {
        foreach ([self::EVENT_ID, self::OTHER_ID] as $eventId) {
            $eventKey = hash_hmac(
                'sha256', 'imagine:asaas:v3:event:'.$eventId, self::BINDING,
            );
            $this->owner->table('crm_lab_asaas_events')->where('event_key', $eventKey)->delete();
        }
    }

    private static function payload(
        string $id = self::EVENT_ID,
        string $kind = 'PAYMENT_RECEIVED',
    ): string {
        return json_encode([
            'id' => $id,
            'event' => $kind,
            'dateCreated' => '2026-11-14 04:00:00',
            'payment' => [
                'id' => 'pay_012345678901',
                'customer' => 'cus_fake_PRIVATE',
                'value' => 89.90,
                'description' => 'Personal data NOT to persist',
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function envelope(string $id = self::EVENT_ID): AsaasWebhookEnvelope
    {
        return AsaasWebhookEnvelope::parse([
            'asaas-access-token' => self::AUTH,
            'content-type' => 'application/json',
        ], self::payload($id), self::AUTH, self::BINDING);
    }

    private function deliver(
        string $body,
        string $token = self::AUTH,
    ): TestResponse {
        return $this->call(
            'POST', '/__ci/asaas-webhook-review',
            [], [], [],
            ['CONTENT_TYPE' => 'application/json',
             'HTTP_ASAAS_ACCESS_TOKEN' => $token,
             'HTTP_ACCEPT' => 'application/json'],
            $body,
        );
    }

    public function test_identical_events_are_one_row_and_public_ack_is_neutral(): void
    {
        $first = $this->deliver(self::payload())->assertOk();
        $duplicate = $this->deliver(self::payload())->assertOk();

        $this->assertSame(['received' => true], $first->json());
        $this->assertSame($first->json(), $duplicate->json());
        $this->assertCount(1, $this->owner->table('crm_lab_asaas_events')->get());

        $row = $this->owner->table('crm_lab_asaas_events')->first();
        $this->assertSame('PAYMENT_RECEIVED', $row->event_kind);
        $this->assertSame('pending_review', $row->state);
        $this->assertSame($this->envelope()->eventKey, $row->event_key);
        $this->assertSame($this->envelope()->bodyDigest, $row->body_digest);
        foreach (['cus_fake_PRIVATE', 'pay_012345678901', 'Personal data', '89.9'] as $secret) {
            $this->assertStringNotContainsString($secret, json_encode($row, JSON_THROW_ON_ERROR));
            $this->assertStringNotContainsString($secret, (string) $first->getContent());
        }
    }

    public function test_invalid_token_and_payload_do_not_persist_and_do_not_ack(): void
    {
        foreach ([
            [self::payload(), 'incorrect-token-over-32-characters'],
            ['{invalid-json', self::AUTH],
            [json_encode(['id' => self::EVENT_ID, 'event' => 'PAYMENT_RECEIVED']),
                self::AUTH],
        ] as [$body, $auth]) {
            $res = $this->deliver((string) $body, $auth)->assertStatus(401);
            $this->assertSame(['received' => false], $res->json());
        }
        $this->assertSame(0, $this->owner->table('crm_lab_asaas_events')->count());
    }

    public function test_write_is_physically_idempotent_across_two_independent_connections(): void
    {
        $a = (new AsaasWebhookInboxCandidate($this->one))
            ->record($this->envelope(), 1700000000);
        $b = (new AsaasWebhookInboxCandidate($this->two))
            ->record($this->envelope(), 1700000001);

        $this->assertSame('synthetic_inbox_accepted', $a['status_code']);
        $this->assertSame('synthetic_inbox_duplicate', $b['status_code']);
        $this->assertTrue($a['persisted_before_ack']);
        foreach ([$a, $b] as $result) {
            $this->assertFalse($result['can_acknowledge_delivery']);
            $this->assertFalse($result['can_activate']);
            $this->assertFalse($result['can_charge']);
            $this->assertFalse($result['can_provision']);
        }
        $this->assertSame(1, $this->owner->table('crm_lab_asaas_events')->count());
    }

    public function test_unique_conflict_blocks_uncommitted_race_and_resolves_after_commit(): void
    {
        $this->one->beginTransaction();
        $this->assertSame('synthetic_inbox_accepted', (new AsaasWebhookInboxCandidate($this->one))
            ->record($this->envelope(), 1700000000)['status_code']);
        $this->two->beginTransaction();
        try {
            (new AsaasWebhookInboxCandidate($this->two))
                ->record($this->envelope(), 1700000001);
            $this->fail('PostgreSQL must hold the duplicate behind the uncommitted index entry.');
        } catch (RuntimeException $e) {
            $this->assertSame('Persistencia do webhook indisponivel.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        } finally {
            $this->two->rollBack();
        }

        $this->one->commit();
        $this->assertSame('synthetic_inbox_duplicate', (new AsaasWebhookInboxCandidate($this->two))
            ->record($this->envelope(), 1700000002)['status_code']);
        $this->assertSame(1, $this->owner->table('crm_lab_asaas_events')->count());
    }

    public function test_rollback_preserves_delivery_retry_opportunity(): void
    {
        $this->one->beginTransaction();
        (new AsaasWebhookInboxCandidate($this->one))
            ->record($this->envelope(self::OTHER_ID), 1700000000);
        $this->one->rollBack();
        $this->assertSame(0, $this->owner->table('crm_lab_asaas_events')->count());

        $this->assertSame('synthetic_inbox_accepted',
            (new AsaasWebhookInboxCandidate($this->two))
                ->record($this->envelope(self::OTHER_ID), 1700000002)['status_code']);
    }

    public function test_ci_role_has_only_insert_on_approved_columns(): void
    {
        foreach ([$this->one, $this->two] as $db) {
            $role = $db->selectOne(
                'SELECT rolsuper, rolbypassrls, rolcreaterole FROM pg_roles WHERE rolname = current_user',
            );
            $this->assertFalse((bool) $role->rolsuper);
            $this->assertFalse((bool) $role->rolbypassrls);
            $this->assertFalse((bool) $role->rolcreaterole);
            foreach (['SELECT', 'UPDATE', 'DELETE', 'TRUNCATE', 'INSERT'] as $op) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                    ['crm_lab_asaas_events', $op],
                )->allowed);
            }
            foreach (['event_key', 'resource_key', 'event_kind', 'body_digest', 'observed_at'] as $col) {
                $this->assertTrue((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_events', $col, 'INSERT'],
                )->allowed);
            }
            $this->assertFalse((bool) $db->selectOne(
                'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                ['crm_lab_asaas_events', 'state', 'INSERT'],
            )->allowed);
        }
    }

    public function test_wrong_database_role_cannot_accept_event(): void
    {
        try {
            (new AsaasWebhookInboxCandidate($this->owner))->record($this->envelope(), 1700000000);
            $this->fail('Owner/superuser must not act as the webhook receiver.');
        } catch (RuntimeException $e) {
            $this->assertSame('Papel de cobranca do laboratorio nao autorizado.', $e->getMessage());
        }
        $this->assertSame(0, $this->owner->table('crm_lab_asaas_events')->count());
    }

    public function test_no_business_webhook_or_subscription_routes_are_published(): void
    {
        $this->get('/api/billing/asaas/webhook')->assertNotFound();
        $this->postJson('/api/billing/asaas/webhook', [])->assertNotFound();
        $this->postJson('/api/subscription/activate', [])->assertNotFound();
        $this->get('/crm/login')->assertNotFound();

        foreach (['routes/web.php', 'bootstrap/app.php', 'app/Providers/AppServiceProvider.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertIsString($source);
            $this->assertStringNotContainsString('__ci/asaas-webhook-review', $source);
        }
    }
}
