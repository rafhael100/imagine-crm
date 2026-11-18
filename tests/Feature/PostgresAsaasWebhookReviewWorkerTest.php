<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Billing\AsaasWebhookEnvelope;
use App\Core\Billing\AsaasWebhookInboxCandidate;
use App\Core\Billing\AsaasWebhookReviewWorkerCandidate;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class PostgresAsaasWebhookReviewWorkerTest extends TestCase
{
    private const AUTH = 'ci-billing-worker-webhook-token-over-32-characters';
    private const KEY = 'ci-billing-worker-hmac-key-32bytes-synthetic-data';
    private const EVENT_A = 'evt_billing_worker_aaaa1111';
    private const EVENT_B = 'evt_billing_worker_bbbb2222';

    private Connection $owner;
    private Connection $receiver;
    private Connection $reviewOne;
    private Connection $reviewTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $pg = config('database.connections.pgsql');
        if (DB::getDriverName() !== 'pgsql'
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('CRM_ASAAS_WEBHOOK_LAB') !== 'synthetic-only'
            || getenv('BILLING_TEST_USERNAME') !== 'crm_billing_ci'
            || getenv('BILLING_REVIEW_USERNAME') !== 'crm_billing_review_ci'
            || !is_array($pg)
            || ($pg['host'] ?? null) !== '127.0.0.1'
            || ($pg['database'] ?? null) !== 'imagine_crm_ci') {
            $this->markTestSkipped('Only disposable CI Asaas PostgreSQL role split.');
        }
        $this->owner = DB::connection();
        $this->clear();

        $config = $pg;
        $config['url'] = null;
        foreach ([
            'asaas_worker_receiver_ci' => [
                getenv('BILLING_TEST_USERNAME'), getenv('BILLING_TEST_PASSWORD')],
            'asaas_worker_first_ci' => [
                getenv('BILLING_REVIEW_USERNAME'), getenv('BILLING_REVIEW_PASSWORD')],
            'asaas_worker_second_ci' => [
                getenv('BILLING_REVIEW_USERNAME'), getenv('BILLING_REVIEW_PASSWORD')],
        ] as $name => [$user, $password]) {
            $settings = $config;
            $settings['username'] = $user;
            $settings['password'] = $password;
            config(['database.connections.'.$name => $settings]);
            DB::purge($name);
        }

        $this->receiver = DB::connection('asaas_worker_receiver_ci');
        $this->reviewOne = DB::connection('asaas_worker_first_ci');
        $this->reviewTwo = DB::connection('asaas_worker_second_ci');
    }

    protected function tearDown(): void
    {
        if (isset($this->receiver)) {
            foreach ([$this->reviewOne, $this->reviewTwo, $this->receiver] as $connection) {
                while ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            }
            $this->clear();
            foreach ([
                'asaas_worker_receiver_ci',
                'asaas_worker_first_ci',
                'asaas_worker_second_ci',
            ] as $name) {
                DB::purge($name);
            }
        }
        parent::tearDown();
    }

    private function clear(): void
    {
        $keys = [];
        foreach ([self::EVENT_A, self::EVENT_B] as $id) {
            $keys[] = hash_hmac('sha256', 'imagine:asaas:v3:event:'.$id, self::KEY);
        }
        $this->owner->table('crm_lab_asaas_events')->whereIn('event_key', $keys)->delete();
    }

    private function envelope(string $event = self::EVENT_A): AsaasWebhookEnvelope
    {
        return AsaasWebhookEnvelope::parse([
            'asaas-access-token' => self::AUTH,
            'content-type' => 'application/json',
        ], json_encode([
            'id' => $event,
            'event' => 'PAYMENT_RECEIVED',
            'payment' => ['id' => 'pay_billing_worker_private'],
        ], JSON_THROW_ON_ERROR), self::AUTH, self::KEY);
    }

    private function insert(string $event = self::EVENT_A): array
    {
        return (new AsaasWebhookInboxCandidate($this->receiver))
            ->record($this->envelope($event), 1700000000);
    }

    private function worker(Connection $db): AsaasWebhookReviewWorkerCandidate
    {
        return new AsaasWebhookReviewWorkerCandidate($db);
    }

    public function test_worker_uses_no_effects_review_state_and_no_second_processing(): void
    {
        $this->assertSame('synthetic_inbox_accepted', $this->insert()['status_code']);

        $first = $this->worker($this->reviewOne)->reviewNext();
        $second = $this->worker($this->reviewTwo)->reviewNext();

        $this->assertSame('synthetic_reviewed_no_effect', $first['status_code']);
        $this->assertSame('synthetic_nothing_to_review', $second['status_code']);
        foreach ([$first, $second] as $out) {
            $this->assertTrue($out['informational_only']);
            foreach ([
                'can_grant_access', 'can_charge', 'can_invoice',
                'can_update_subscription', 'can_send',
            ] as $flag) {
                $this->assertFalse($out[$flag], $flag);
            }
        }
        $this->assertSame('reviewed_no_effect', $this->owner->table('crm_lab_asaas_events')->first()->state);

        $this->assertSame('synthetic_inbox_duplicate', $this->insert()['status_code']);
        $this->assertSame('synthetic_nothing_to_review', $this->worker($this->reviewOne)->reviewNext()['status_code']);
        $this->assertSame(1, $this->owner->table('crm_lab_asaas_events')->count());
    }

    public function test_second_reviewer_skips_pending_but_locked_row_and_rollback_recovers_it(): void
    {
        $this->insert();
        $this->reviewOne->beginTransaction();

        $this->assertSame('synthetic_reviewed_no_effect',
            $this->worker($this->reviewOne)->reviewNext()['status_code']);

        // Work committed only in an inner savepoint: outer transaction
        // still holds the row lock and can roll back the state transition.
        $this->assertSame('synthetic_nothing_to_review',
            $this->worker($this->reviewTwo)->reviewNext()['status_code']);

        $this->reviewOne->rollBack();
        $this->assertSame('pending_review', $this->owner->table('crm_lab_asaas_events')->first()->state);
        $this->assertSame('synthetic_reviewed_no_effect',
            $this->worker($this->reviewTwo)->reviewNext()['status_code']);
        $this->assertSame('reviewed_no_effect', $this->owner->table('crm_lab_asaas_events')->first()->state);
    }

    public function test_two_markers_are_reviewed_once_each_without_updates_to_billing(): void
    {
        $this->insert(self::EVENT_A);
        $this->insert(self::EVENT_B);

        $this->assertSame('synthetic_reviewed_no_effect',
            $this->worker($this->reviewOne)->reviewNext()['status_code']);
        $this->assertSame('synthetic_reviewed_no_effect',
            $this->worker($this->reviewTwo)->reviewNext()['status_code']);
        $this->assertSame('synthetic_nothing_to_review',
            $this->worker($this->reviewOne)->reviewNext()['status_code']);
        $this->assertSame(2, $this->owner->table('crm_lab_asaas_events')->count());
        $this->assertSame(2, $this->owner->table('crm_lab_asaas_events')
            ->where('state', 'reviewed_no_effect')->count());
    }

    public function test_inbox_and_reviewer_are_different_least_privilege_roles(): void
    {
        $this->assertFalse((bool) $this->receiver->selectOne(
            "SELECT has_column_privilege(current_user, 'crm_lab_asaas_events', 'state', 'SELECT') AS allowed",
        )->allowed);

        foreach ([$this->reviewOne, $this->reviewTwo] as $db) {
            $identity = $db->selectOne(
                'SELECT rolsuper, rolbypassrls FROM pg_roles WHERE rolname = current_user',
            );
            $this->assertFalse((bool) $identity->rolsuper);
            $this->assertFalse((bool) $identity->rolbypassrls);

            foreach (['INSERT', 'DELETE', 'TRUNCATE', 'UPDATE', 'SELECT'] as $op) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_table_privilege(current_user, ?, ?) AS allowed',
                    ['crm_lab_asaas_events', $op],
                )->allowed);
            }

            foreach (['event_key', 'event_kind', 'state', 'observed_at'] as $column) {
                $this->assertTrue((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_events', $column, 'SELECT'],
                )->allowed);
            }
            foreach (['resource_key', 'body_digest'] as $column) {
                $this->assertFalse((bool) $db->selectOne(
                    'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                    ['crm_lab_asaas_events', $column, 'SELECT'],
                )->allowed);
            }
            $this->assertTrue((bool) $db->selectOne(
                'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                ['crm_lab_asaas_events', 'state', 'UPDATE'],
            )->allowed);
            $this->assertFalse((bool) $db->selectOne(
                'SELECT has_column_privilege(current_user, ?, ?, ?) AS allowed',
                ['crm_lab_asaas_events', 'event_kind', 'UPDATE'],
            )->allowed);
        }
    }

    public function test_admin_and_receiver_roles_cannot_run_reviewer(): void
    {
        foreach ([$this->owner, $this->receiver] as $db) {
            try {
                $this->worker($db)->reviewNext();
                $this->fail('Unexpected privileged/receiver review.');
            } catch (RuntimeException $e) {
                $this->assertSame('Papel de revisao do laboratorio nao autorizado.', $e->getMessage());
                $this->assertNull($e->getPrevious());
            }
        }
    }
}
