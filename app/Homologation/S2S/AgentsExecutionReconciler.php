<?php

declare(strict_types=1);

namespace App\Homologation\S2S;

use PDO;
use RuntimeException;
use Throwable;

/** Explicit synthetic status lookup; it cannot send events, execute work or change a lead. */
final class AgentsExecutionReconciler
{
    /** Return the persisted read model after a verified observation, including idempotent polls. */
    public function reconcile(string $company, string $event): array
    {
        if (!app()->environment('staging')
            || getenv('CRM_AGENTS_S2S_PROFILE') !== 'synthetic-private-v1'
            || getenv('CRM_AGENTS_EXECUTION_PROFILE') !== 'synthetic-private-v1') {
            throw new RuntimeException('EXECUTION_RECONCILER_DISABLED');
        }
        $password = @file_get_contents('/certs/execution-reader.password');
        if (!is_string($password) || trim($password) === '') {
            throw new RuntimeException('EXECUTION_DATABASE_UNAVAILABLE');
        }
        $db = new PDO(getenv('CRM_AUTO_S2S_DSN'), 'crm_auto_execution_reconciler', trim($password),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
        if ($db->query("SELECT current_user = 'crm_auto_execution_reconciler'
            AND NOT rolsuper AND NOT rolbypassrls AND NOT rolinherit
            AND NOT rolcreatedb AND NOT rolcreaterole AND NOT rolreplication
            AND NOT EXISTS(SELECT 1 FROM pg_auth_members WHERE member=pg_roles.oid)
            FROM pg_roles WHERE rolname=current_user")->fetchColumn() !== true) {
            throw new RuntimeException('EXECUTION_DATABASE_ROLE');
        }
        $db->exec("SET statement_timeout='2000ms'; SET lock_timeout='1000ms'; SET idle_in_transaction_session_timeout='6000ms'");
        $db->beginTransaction();
        try {
            // This existing source row serializes queries and preserves their causal order.
            // The independent read grant remains locked through verification and projection.
            $candidate = $db->prepare('SELECT crm_auto_service.execution_candidate(CAST(? AS uuid),CAST(? AS uuid))');
            $candidate->execute([$company, $event]);
            $row = json_decode($candidate->fetchColumn(), true, 12, JSON_THROW_ON_ERROR);
            $ingressKey = @file_get_contents('/certs/agents-signing.key');
            $executionKey = @file_get_contents('/certs/agents-execution.key');
            $receiptKey = @file_get_contents('/certs/agents-receipt.key');
            if (!is_string($ingressKey) || !is_string($executionKey)
                || hash_equals($ingressKey, $executionKey)
                || (is_string($receiptKey) && hash_equals($receiptKey, $executionKey))) {
                throw new RuntimeException('EXECUTION_KEY_UNAVAILABLE');
            }
            $query = AgentsExecutionReceipt::query($company, $row['tenant_ref'],
                $row['event_ref'], $row['payload_hash'], $ingressKey, time(),
                'SYNTHETIC_' . strtoupper(bin2hex(random_bytes(16))),
                'SYNTHETIC_' . strtoupper(bin2hex(random_bytes(16))));
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => true, 'verify_peer_name' => true,
                    'allow_self_signed' => false, 'peer_name' => 'agents-s2s',
                    'cafile' => '/certs/ca.crt', 'local_cert' => '/certs/crm.crt',
                    'local_pk' => '/certs/crm.key',
                ],
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nConnection: close\r\n",
                    'content' => json_encode($query, JSON_THROW_ON_ERROR),
                    'timeout' => 3, 'follow_location' => 0, 'max_redirects' => 0,
                    'ignore_errors' => true,
                ],
            ]);
            $raw = @file_get_contents('https://agents-s2s:8443/private/events/execution',
                false, $context, 0, 4097);
            if (!is_string($raw) || strlen($raw) > 4096
                || !preg_match('/\AHTTP\/\S+ 200(?: |$)/', $http_response_header[0] ?? '')) {
                throw new RuntimeException('EXECUTION_TRANSPORT_DENIED');
            }
            $status = AgentsExecutionReceipt::validate($raw, $query, $executionKey, time());
            if ($status['lead_id'] !== $row['lead_id']) {
                throw new RuntimeException('EXECUTION_LEAD_BINDING');
            }
            $project = $db->prepare('SELECT crm_auto_service.project_execution(CAST(? AS uuid),CAST(? AS uuid),CAST(? AS jsonb),?)');
            $project->execute([$company, $event, json_encode($query, JSON_THROW_ON_ERROR), $raw]);
            $view = json_decode($project->fetchColumn(), true, 16, JSON_THROW_ON_ERROR);
            $db->commit();

            return $view;
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
    }
}
